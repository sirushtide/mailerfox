<?php

namespace App\Services;

use App\Models\DeliveryServer;
use App\Models\DeliveryServerLog;
use App\Models\OutreachCampaign;
use App\Models\OutreachLead;
use App\Models\OutreachSequence;
use App\Support\EmailFooter;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

/**
 * Drives the cold-email outreach send pipeline.
 *
 * The send model is per-lead, not per-campaign:
 *   - Each lead has its own `current_step` and `next_send_at`.
 *   - When `next_send_at <= now()`, the next step (sequences[current_step]) is sent.
 *   - After a successful send the lead's `current_step` is incremented and
 *     `next_send_at` is set to `now() + next_step_delay`. If there is no next
 *     step the lead's `sequence_completed_at` is recorded.
 *
 * This means leads added today start at step 1 even if other leads in the same
 * campaign are already on step 2/3/etc — exactly the behaviour requested by
 * the user.
 */
class OutreachSendService
{
    /**
     * Statuses that indicate the lead should NOT receive any further messages.
     */
    private const TERMINAL_STATUSES = ['replied', 'bounced', 'unsubscribed'];

    public function __construct(
        protected DeliveryServerService $deliveryServerService,
    ) {}

    /**
     * Process every campaign that is currently active and has due leads.
     *
     * @return array{leads_processed: int, leads_sent: int, leads_failed: int}
     */
    public function processAll(): array
    {
        $stats = ['leads_processed' => 0, 'leads_sent' => 0, 'leads_failed' => 0];
        $startTime = time();
        $maxRuntime = 50; // seconds — leave headroom before next minute tick

        OutreachCampaign::query()
            ->where('status', 'active')
            ->cursor()
            ->each(function (OutreachCampaign $campaign) use (&$stats, $startTime, $maxRuntime) {
                if (time() - $startTime > $maxRuntime) {
                    Log::warning('[Outreach] processAll runtime budget exceeded, skipping remaining campaigns');

                    return false; // break cursor iteration
                }

                try {
                    $campaignStats = $this->processCampaign($campaign);
                    $stats['leads_processed'] += $campaignStats['leads_processed'];
                    $stats['leads_sent'] += $campaignStats['leads_sent'];
                    $stats['leads_failed'] += $campaignStats['leads_failed'];
                } catch (\Throwable $e) {
                    Log::error('[Outreach] Campaign processing failed, continuing to next', [
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return $stats;
    }

    /**
     * Process all due leads for a single campaign.
     *
     * @return array{leads_processed: int, leads_sent: int, leads_failed: int}
     */
    public function processCampaign(OutreachCampaign $campaign): array
    {
        $stats = ['leads_processed' => 0, 'leads_sent' => 0, 'leads_failed' => 0];

        // ── Sending-window guard ──────────────────────────────────────────
        // Check whether right now (in the campaign's timezone) falls inside
        // an allowed day + time-block.  If not, skip the entire campaign.
        if (! $this->isInsideSendingWindow($campaign)) {
            Log::debug('[Outreach] Skipped campaign — outside sending window', [
                'campaign_id' => $campaign->id,
                'timezone' => $campaign->getSetting('timezone', 'UTC'),
                'send_days' => $campaign->getSetting('send_days', []),
                'time_blocks' => $campaign->getSetting('send_time_blocks', []),
            ]);

            return $stats;
        }

        $sequences = OutreachSequence::where('campaign_id', $campaign->id)
            ->orderBy('sort_order')
            ->get()
            ->values();

        if ($sequences->isEmpty()) {
            Log::debug('[Outreach] Skipped campaign — no sequences', [
                'campaign_id' => $campaign->id,
            ]);

            return $stats;
        }

        $deliveryServers = $this->resolveDeliveryServers($campaign);
        if ($deliveryServers->isEmpty()) {
            Log::warning('[Outreach] No active delivery server for campaign', [
                'campaign_id' => $campaign->id,
                'sender_ids' => $campaign->getSetting('sender_account_ids', []),
            ]);

            return $stats;
        }

        $now = Carbon::now();
        $minDelayEnabled = (bool) $campaign->getSetting('min_delay_enabled', false);
        $minDelayValue = max(1, (int) $campaign->getSetting('min_delay', 1));
        $minDelayUnit = (string) $campaign->getSetting('min_delay_unit', 'minutes');
        $minDelaySeconds = match ($minDelayUnit) {
            'seconds' => $minDelayValue,
            'hours' => $minDelayValue * 3600,
            default => $minDelayValue * 60, // minutes
        };
        $cacheKey = "outreach:campaign:{$campaign->id}:last_sent_at";

        // Optional per-minute rate limit (0 = inherit type defaults via resolver).
        $ratePerMinute = max(0, (int) $campaign->getSetting('rate_per_minute', 0));
        $rateLimitKeyPrefix = 'outreach-send:'.$campaign->id.':server:';

        $totalSteps = $this->scalarTotalSteps($sequences);

        // ── Diagnostic breakdown ────────────────────────────────────────
        $totalLeads = OutreachLead::where('campaign_id', $campaign->id)->count();
        $nonTerminalLeads = OutreachLead::where('campaign_id', $campaign->id)->whereNotIn('status', self::TERMINAL_STATUSES)->count();
        $notCompletedLeads = OutreachLead::where('campaign_id', $campaign->id)->whereNotIn('status', self::TERMINAL_STATUSES)->whereNull('sequence_completed_at')->count();
        $timeDueLeads = OutreachLead::where('campaign_id', $campaign->id)->whereNotIn('status', self::TERMINAL_STATUSES)->whereNull('sequence_completed_at')->where(function ($q) use ($now) {
            $q->whereNull('next_send_at')->orWhere('next_send_at', '<=', $now);
        })->count();
        $stepValidLeads = OutreachLead::where('campaign_id', $campaign->id)->whereNotIn('status', self::TERMINAL_STATUSES)->whereNull('sequence_completed_at')->where(function ($q) use ($now) {
            $q->whereNull('next_send_at')->orWhere('next_send_at', '<=', $now);
        })->where('current_step', '<', $totalSteps)->count();

        Log::debug('[Outreach] Lead funnel breakdown', [
            'campaign_id' => $campaign->id,
            'total_leads' => $totalLeads,
            'non_terminal' => $nonTerminalLeads,
            'not_completed' => $notCompletedLeads,
            'time_due' => $timeDueLeads,
            'step_valid' => $stepValidLeads,
            'total_steps' => $totalSteps,
            'now' => $now->toDateTimeString(),
        ]);

        $maxPerDay = max(1, (int) $campaign->getSetting('max_per_day', 150));
        $campaignTimezone = $campaign->getSetting('timezone', 'UTC');
        $localToday = Carbon::now($campaignTimezone);
        $sentToday = DeliveryServerLog::query()
            ->where('event', 'outreach_sent')
            ->whereBetween('created_at', [
                $localToday->copy()->startOfDay()->setTimezone('UTC'),
                $localToday->copy()->endOfDay()->setTimezone('UTC'),
            ])
            ->where(function ($query) use ($campaign) {
                // Portable across MySQL/SQLite/Postgres and avoids the
                // non-indexable CAST(json_extract(...)) full scan.
                $query->where('meta->outreach_campaign_id', (int) $campaign->id)
                    ->orWhereRaw("json_extract(meta, '$.outreach_campaign_id') = ?", [(int) $campaign->id]);
            })
            ->count();
        $remainingToday = max(0, $maxPerDay - $sentToday);

        if ($remainingToday === 0) {
            Log::debug('[Outreach] Daily sending limit reached', [
                'campaign_id' => $campaign->id,
                'max_per_day' => $maxPerDay,
                'sent_today' => $sentToday,
            ]);

            return $stats;
        }

        $dueQuery = OutreachLead::query()
            ->where('campaign_id', $campaign->id)
            ->whereNotIn('status', self::TERMINAL_STATUSES)
            ->whereNull('sequence_completed_at')
            ->where(function ($q) use ($now) {
                $q->whereNull('next_send_at')->orWhere('next_send_at', '<=', $now);
            })
            ->where('current_step', '<', $totalSteps)
            ->orderBy('next_send_at')
            ->limit(min(500, $remainingToday));

        $dueCount = (clone $dueQuery)->count();
        Log::debug('[Outreach] Due leads found', [
            'campaign_id' => $campaign->id,
            'due_count' => $dueCount,
            'now' => $now->toDateTimeString(),
        ]);

        $campaignStart = time();
        $maxCampaignTime = 280; // queue jobs have a five-minute timeout; do not cap normal batches at ~23 sends

        $dueQuery->cursor()
            ->each(function (OutreachLead $lead) use (
                $campaign,
                $sequences,
                $deliveryServers,
                &$stats,
                $cacheKey,
                $minDelayEnabled,
                $minDelaySeconds,
                $ratePerMinute,
                $rateLimitKeyPrefix,
                $campaignStart,
                $maxCampaignTime
            ) {
                if (time() - $campaignStart > $maxCampaignTime) {
                    Log::warning('[Outreach] Campaign processing time exceeded, yielding to next campaign', [
                        'campaign_id' => $campaign->id,
                        'elapsed_sec' => time() - $campaignStart,
                    ]);

                    return false; // break cursor iteration
                }

                // ── Min-delay guard (sleep remaining time within the job budget) ──
                if ($minDelayEnabled) {
                    $lastSentTimestamp = Cache::get($cacheKey);
                    if ($lastSentTimestamp) {
                        $elapsed = time() - (int) $lastSentTimestamp;
                        $remaining = $minDelaySeconds - $elapsed;
                        if ($remaining > 0) {
                            $budgetLeft = $maxCampaignTime - (time() - $campaignStart);
                            if ($remaining > $budgetLeft) {
                                Log::debug('[Outreach] Min-delay would exceed job budget; yielding', [
                                    'campaign_id' => $campaign->id,
                                    'min_delay_sec' => $minDelaySeconds,
                                    'remaining_sec' => $remaining,
                                    'budget_left' => $budgetLeft,
                                ]);

                                return false;
                            }

                            sleep($remaining);
                        }
                    }
                }

                $stats['leads_processed']++;

                if ($ratePerMinute > 0) {
                    $loopRateKey = $rateLimitKeyPrefix.($deliveryServers->first()?->id ?? 0);
                    if (RateLimiter::tooManyAttempts($loopRateKey, $ratePerMinute)) {
                        $loopWait = max(1, (int) RateLimiter::availableIn($loopRateKey));
                        if ($loopWait <= 60 && (time() - $campaignStart + $loopWait) <= $maxCampaignTime) {
                            sleep($loopWait);
                        } else {
                            return false;
                        }
                    }
                }

                if ($this->processLead($campaign, $lead, $sequences, $deliveryServers, $campaign)) {
                    $stats['leads_sent']++;
                    if ($minDelayEnabled) {
                        Cache::put($cacheKey, time(), now()->addDay());
                    }
                } else {
                    $stats['leads_failed']++;
                }

                return null;
            });

        Log::debug('[Outreach] Campaign processing complete', [
            'campaign_id' => $campaign->id,
            'leads_processed' => $stats['leads_processed'],
            'leads_sent' => $stats['leads_sent'],
            'leads_failed' => $stats['leads_failed'],
        ]);

        return $stats;
    }

    /**
     * Send the next due step for a single lead and advance the lead's state.
     *
     * Returns true when the email was actually sent.
     */
    public function processLead(
        OutreachCampaign $campaign,
        OutreachLead $lead,
        Collection $sequences,
        Collection $deliveryServers,
        ?OutreachCampaign $campaignForSchedule = null,
    ): bool {
        $stepIndex = (int) ($lead->current_step ?? 0);
        /** @var OutreachSequence|null $step */
        $step = $sequences->get($stepIndex);
        if (! $step) {
            $lead->forceFill([
                'sequence_completed_at' => Carbon::now(),
                'next_send_at' => null,
            ])->save();

            return false;
        }

        [$subjectTemplate, $bodyTemplate] = $this->pickVariant($step);

        $subject = $lead->renderTokensForCampaign($subjectTemplate, $campaign);
        $body = $lead->renderTokensForCampaign($bodyTemplate, $campaign);
        $footer = $lead->renderTokensForCampaign((string) $campaign->getSetting('email_footer', ''), $campaign);
        $body = $this->appendFooter($body, $footer);

        $groupFooter = EmailFooter::forCustomerId((int) ($campaign->customer_id ?? 0));
        if ($groupFooter !== '') {
            $groupFooter = $lead->renderTokensForCampaign($groupFooter, $campaign);
            $body = $this->appendFooter($body, $groupFooter);
        }

        $body = $this->injectUnsubscribeLink($body, $lead, $campaign);

        $lastError = null;
        $fromEmail = '';
        $fromName = '';

        foreach ($deliveryServers as $deliveryServer) {
            // Optional campaign rate limit: smooth-wait short windows; skip server if long.
            $campaignRate = max(0, (int) $campaign->getSetting('rate_per_minute', 0));
            if ($campaignRate > 0) {
                $rateKey = 'outreach-send:'.$campaign->id.':server:'.$deliveryServer->id;
                $waited = 0;
                while (RateLimiter::tooManyAttempts($rateKey, $campaignRate)) {
                    $wait = max(1, (int) RateLimiter::availableIn($rateKey));
                    if ($wait > 60 || ($waited + $wait) > 60) {
                        Log::debug('[Outreach] Rate limit reached; deferring lead', [
                            'campaign_id' => $campaign->id,
                            'server_id' => $deliveryServer->id,
                            'wait_sec' => $wait,
                        ]);

                        return false;
                    }
                    sleep($wait);
                    $waited += $wait;
                }
            }

            $attemptBody = $body;
            if ($deliveryServer->type !== 'resend') {
                $attemptBody = $this->injectOpenPixel($attemptBody, $lead, $campaign);
                $attemptBody = $this->rewriteClickLinks($attemptBody, $lead, $campaign);
            }
            $attemptBody = $campaign->getSetting('send_styled_body', true)
                ? $attemptBody
                : $this->convertToBareEmailBody($attemptBody);

            $fromEmail = (string) ($deliveryServer->from_email ?: config('mail.from.address'));
            $fromName = (string) ($deliveryServer->from_name ?: config('mail.from.name'));

            try {
                $this->deliveryServerService->configureMailFromServer($deliveryServer);

                Mail::send([], [], function ($message) use ($lead, $subject, $attemptBody, $fromEmail, $fromName, $campaign, $deliveryServer) {
                    $message->to($lead->email, trim(($lead->first_name ?? '').' '.($lead->last_name ?? '')) ?: null)
                        ->subject($subject)
                        ->from($fromEmail, $fromName);

                    if (! empty($attemptBody)) {
                        if (preg_match('/<\s*\w+[^>]*>/', $attemptBody) === 1) {
                            $message->html($attemptBody);
                        } else {
                            $htmlBody = '<div style="white-space: pre-wrap;">'.nl2br(e($attemptBody)).'</div>';

                            if ($campaign->getSetting('track_opens', true) && $deliveryServer->type !== 'resend') {
                                $htmlBody .= '<img src="'.e(route('outreach.track.open', ['uuid' => $lead->uuid])).'" alt="" width="1" height="1" style="display:none;border:0;" />';
                            }

                            $message->html($htmlBody);
                            $message->text($attemptBody);
                        }
                    }

                    $headers = $message->getHeaders();
                    $headers->addTextHeader('X-Outreach-Campaign-ID', (string) $campaign->id);
                    $headers->addTextHeader('X-Outreach-Lead-ID', (string) $lead->id);
                    $headers->addTextHeader('X-Outreach-Step', (string) ($lead->current_step + 1));

                    if ($deliveryServer->bounceServer && ! empty($deliveryServer->bounceServer->username)) {
                        $message->returnPath($deliveryServer->bounceServer->username);
                    }
                });

                // Success on this server – log and advance lead.
                DeliveryServerLog::create([
                    'delivery_server_id' => $deliveryServer->id,
                    'event' => 'outreach_sent',
                    'to_email' => $lead->email,
                    'status' => 'success',
                    'meta' => [
                        'outreach_campaign_id' => $campaign->id,
                        'outreach_lead_id' => $lead->id,
                        'outreach_step' => $stepIndex + 1,
                        'subject' => $subject,
                        'rendered_body' => $body,
                        'from' => $fromEmail,
                    ],
                ]);

                $this->advanceLead($lead, $sequences, $stepIndex, $campaignForSchedule ?? $campaign);

                if ($campaignRate > 0) {
                    RateLimiter::hit('outreach-send:'.$campaign->id.':server:'.$deliveryServer->id, 60);
                }

                return true;

            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                Log::error('[Outreach] Failed sending step', [
                    'campaign_id' => $campaign->id,
                    'lead_id' => $lead->id,
                    'step' => $stepIndex + 1,
                    'server_id' => $deliveryServer->id,
                    'error' => $lastError,
                ]);

                DeliveryServerLog::create([
                    'delivery_server_id' => $deliveryServer->id,
                    'event' => 'outreach_failed',
                    'to_email' => $lead->email,
                    'status' => 'failed',
                    'error_message' => mb_substr($lastError, 0, 1000),
                    'diagnostic' => $lastError,
                    'error_category' => DeliveryServerLog::categorizeError($lastError),
                    'meta' => [
                        'outreach_campaign_id' => $campaign->id,
                        'outreach_lead_id' => $lead->id,
                        'outreach_step' => $stepIndex + 1,
                        'subject' => $subject,
                        'from' => $fromEmail,
                    ],
                ]);
            }
        }

        // All servers exhausted – mark lead for retry and surface failure in UI.
        $campaign->appendStatusLog('failed', __('Step :step failed for lead :email: :error', [
            'step' => $stepIndex + 1,
            'email' => $lead->email,
            'error' => $lastError ?? 'Unknown error',
        ]));

        $retryCampaign = $campaignForSchedule ?? $campaign;
        $meta = $lead->meta ?? [];
        $meta['failed_step'] = $stepIndex + 1;
        $meta['failed_error'] = mb_substr((string) $lastError, 0, 500);

        $lead->forceFill([
            'status' => 'failed',
            'next_send_at' => $this->clampToSendingWindow(Carbon::now()->addMinutes(1), $retryCampaign),
            'last_activity_at' => Carbon::now(),
            'meta' => $meta,
        ])->save();

        return false;
    }

    /**
     * Advance the lead pointer after a successful send.
     */
    private function advanceLead(OutreachLead $lead, Collection $sequences, int $sentStepIndex, OutreachCampaign $campaign): void
    {
        $nextIndex = $sentStepIndex + 1;
        $nextStep = $sequences->get($nextIndex);

        $update = [
            'current_step' => $nextIndex,
            'last_activity_at' => Carbon::now(),
        ];

        // Mark as 'sent' the first time, if still pending, or if recovering
        // from a previous failure. Never overwrite stronger engagement
        // signals (opened, clicked, replied) or terminal states.
        if (in_array($lead->status, ['pending', 'failed', null, ''], true)) {
            $update['status'] = 'sent';
        }

        // Clear any transient failure tracking so the UI no longer shows
        // "Failed at step X" once the lead has successfully advanced.
        $meta = $lead->meta ?? [];
        if (isset($meta['failed_step']) || isset($meta['failed_error'])) {
            unset($meta['failed_step'], $meta['failed_error']);
            $update['meta'] = $meta;
        }

        if ($nextStep) {
            $update['next_send_at'] = $this->computeNextSendAt($nextStep, $campaign);
        } else {
            $update['next_send_at'] = null;
            $update['sequence_completed_at'] = Carbon::now();
        }

        $lead->forceFill($update)->save();
    }

    /**
     * Pick the variant_a or variant_b body+subject for the given step.
     *
     * @return array{0:string,1:string}
     */
    private function pickVariant(OutreachSequence $step): array
    {
        if ($step->has_variant_b && $step->subject_b !== null) {
            $split = max(0, min(100, (int) ($step->variant_split ?? 50)));
            if (random_int(1, 100) > $split) {
                return [(string) $step->subject_b, (string) ($step->body_b ?? '')];
            }
        }

        return [(string) $step->subject_a, (string) ($step->body_a ?? '')];
    }

    private function appendFooter(string $body, string $footer): string
    {
        $footer = trim($footer);

        if ($footer === '') {
            return $body;
        }

        $body = (string) $body;
        if (trim($body) === '') {
            return $footer;
        }

        $bodyIsHtml = preg_match('/<\s*\w+[^>]*>/', $body) === 1;
        $footerIsHtml = preg_match('/<\s*\w+[^>]*>/', $footer) === 1;

        if ($bodyIsHtml || $footerIsHtml) {
            $normalizedFooter = $footerIsHtml
                ? $footer
                : '<div style="white-space: pre-wrap; margin-top: 24px;">'.nl2br(e($footer)).'</div>';

            if (stripos($body, '</body>') !== false) {
                return str_ireplace('</body>', $normalizedFooter.'</body>', $body);
            }

            return $body.$normalizedFooter;
        }

        return rtrim($body)."\n\n".$footer;
    }

    private function convertToBareEmailBody(string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            return $body;
        }

        if (preg_match('/<\s*\w+[^>]*>/', $body) !== 1) {
            return $body;
        }

        $normalized = preg_replace('/\sstyle=("|\').*?\1/i', '', $body);
        $normalized = preg_replace('/\sclass=("|\').*?\1/i', '', (string) $normalized);
        $normalized = preg_replace('/<(span|font)(\s[^>]*)?>/i', '', (string) $normalized);
        $normalized = preg_replace('/<\/(span|font)>/i', '', (string) $normalized);
        $normalized = preg_replace('/<(div|section|article|main|header|footer)(\s[^>]*)?>/i', '<p>', (string) $normalized);
        $normalized = preg_replace('/<\/(div|section|article|main|header|footer)>/i', '</p>', (string) $normalized);
        $normalized = preg_replace('/<(p|h[1-6]|blockquote)(\s[^>]*)?>/i', '<p>', (string) $normalized);
        $normalized = preg_replace('/<\/(p|h[1-6]|blockquote)>/i', '</p>', (string) $normalized);
        $normalized = preg_replace('/<a([^>]*?)>/i', '<a$1>', (string) $normalized);
        $normalized = preg_replace('/<(ol|ul|li)(\s[^>]*)?>/i', '<$1>', (string) $normalized);
        $normalized = preg_replace('/<img([^>]*?)>/i', static function (array $matches): string {
            return Str::contains($matches[1], 'outreach/track/open/') ? '<img'.$matches[1].'>' : '';
        }, (string) $normalized);

        return trim((string) $normalized);
    }

    /**
     * Compute when a step should fire based on its delay configuration.
     * The resulting timestamp is clamped into the campaign's next valid
     * sending window (respecting timezone, allowed days, and time blocks).
     */
    private function computeNextSendAt(OutreachSequence $step, OutreachCampaign $campaign): CarbonInterface
    {
        $delay = max(0, (int) ($step->delay_days ?? 0));
        $unit = (string) ($step->delay_type ?? 'days');

        $now = Carbon::now();

        $candidate = match ($unit) {
            'minutes' => $now->copy()->addMinutes($delay),
            'hours' => $now->copy()->addHours($delay),
            'weeks' => $now->copy()->addWeeks($delay),
            'months' => $now->copy()->addMonths($delay),
            default => $now->copy()->addDays($delay),
        };

        return $this->clampToSendingWindow($candidate, $campaign);
    }

    /**
     * Check whether the current moment (in campaign timezone) is inside an
     * allowed sending day AND at least one configured time block.
     */
    private function isInsideSendingWindow(OutreachCampaign $campaign): bool
    {
        $tz = $campaign->getSetting('timezone', 'UTC');
        if (! in_array($tz, timezone_identifiers_list(), true)) {
            Log::warning('[Outreach] Invalid timezone on campaign, falling back to UTC', [
                'campaign_id' => $campaign->id,
                'timezone' => $tz,
            ]);
            $tz = 'UTC';
        }
        $now = Carbon::now($tz);

        // ── Day check ─────────────────────────────────────────────────────
        $allowedDays = array_map('strtolower', (array) $campaign->getSetting('send_days', []));
        if (! empty($allowedDays)) {
            $todayShort = strtolower($now->format('D')); // Mon→mon, Tue→tue …
            // Also accept full day name and 3-letter abbreviation
            $todayFull = strtolower($now->format('l'));  // monday, tuesday …
            if (! in_array($todayShort, $allowedDays, true) && ! in_array($todayFull, $allowedDays, true)) {
                return false;
            }
        }

        // ── Time-block check ──────────────────────────────────────────────
        $timeBlocks = (array) $campaign->getSetting('send_time_blocks', []);

        // Fallback: use legacy single start/end pair
        if (empty($timeBlocks)) {
            $start = $campaign->getSetting('send_hours_start', '00:00');
            $end = $campaign->getSetting('send_hours_end', '23:59');
            $timeBlocks = [['start' => $start, 'end' => $end]];
        }

        $currentTime = $now->format('H:i');

        foreach ($timeBlocks as $block) {
            $start = $block['start'] ?? '00:00';
            $end = $block['end'] ?? '23:59';
            if ($currentTime >= $start && $currentTime <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Given a candidate UTC timestamp, ensure it falls inside the campaign's
     * sending window. If not, push it forward to the start of the next valid
     * time block on a valid day (up to 14 days ahead to prevent infinite loops).
     */
    private function clampToSendingWindow(CarbonInterface $candidate, OutreachCampaign $campaign): CarbonInterface
    {
        $tz = $campaign->getSetting('timezone', 'UTC');
        if (! in_array($tz, timezone_identifiers_list(), true)) {
            $tz = 'UTC';
        }
        $allowedDays = array_map('strtolower', (array) $campaign->getSetting('send_days', []));
        $timeBlocks = (array) $campaign->getSetting('send_time_blocks', []);

        if (empty($timeBlocks)) {
            $start = $campaign->getSetting('send_hours_start', '00:00');
            $end = $campaign->getSetting('send_hours_end', '23:59');
            $timeBlocks = [['start' => $start, 'end' => $end]];
        }

        // Sort time blocks by start time
        usort($timeBlocks, fn ($a, $b) => ($a['start'] ?? '00:00') <=> ($b['start'] ?? '00:00'));

        // Work in campaign timezone
        $local = $candidate->copy()->setTimezone($tz);

        // Try up to 14 days (to find a valid day + time block)
        for ($dayOffset = 0; $dayOffset < 14; $dayOffset++) {
            $checkDay = $local->copy()->addDays($dayOffset);

            // ── Day check ─────────────────────────────────────────────────
            if (! empty($allowedDays)) {
                $dayShort = strtolower($checkDay->format('D'));
                $dayFull = strtolower($checkDay->format('l'));
                if (! in_array($dayShort, $allowedDays, true) && ! in_array($dayFull, $allowedDays, true)) {
                    continue;
                }
            }

            // ── Time-block check ──────────────────────────────────────────
            foreach ($timeBlocks as $block) {
                $blockStart = $block['start'] ?? '00:00';
                $blockEnd = $block['end'] ?? '23:59';

                // Build start/end Carbon instances for this block on $checkDay
                [$sh, $sm] = array_map('intval', explode(':', $blockStart));
                [$eh, $em] = array_map('intval', explode(':', $blockEnd));

                $windowStart = $checkDay->copy()->setTime($sh, $sm, 0);
                $windowEnd = $checkDay->copy()->setTime($eh, $em, 59);

                if ($dayOffset === 0) {
                    // Same day: candidate might already be inside this block
                    if ($local->lte($windowEnd) && $local->gte($windowStart)) {
                        // Already inside — return as-is (converted back to UTC)
                        return $candidate;
                    }
                    // Candidate is before this block's start on the same day
                    if ($local->lt($windowStart)) {
                        return $windowStart->setTimezone('UTC');
                    }
                    // Candidate is past this block — try next block or next day
                } else {
                    // Different day: snap to block start
                    return $windowStart->setTimezone('UTC');
                }
            }
        }

        // Fallback: return candidate as-is (should not happen with <= 14 days)
        return $candidate;
    }

    /**
     * Return ALL active delivery servers for the campaign in priority order.
     */
    private function resolveDeliveryServers(OutreachCampaign $campaign): Collection
    {
        $senderIds = (array) ($campaign->getSetting('sender_account_ids', []) ?? []);
        $servers = collect();

        foreach ($senderIds as $id) {
            $server = DeliveryServer::where('customer_id', $campaign->customer_id)
                ->where('id', (int) $id)
                ->where('status', 'active')
                ->first();
            if ($server) {
                $servers->push($server);
            }
        }

        if ($servers->isEmpty()) {
            $fallback = DeliveryServer::where('customer_id', $campaign->customer_id)
                ->where('status', 'active')
                ->orderByDesc('is_primary')
                ->get();
            $servers = $servers->concat($fallback);
        }

        return $servers->unique('id')->values();
    }

    private function scalarTotalSteps(Collection $sequences): int
    {
        return $sequences->count();
    }

    /**
     * Inject (or replace) the unsubscribe link in the email body when the
     * campaign has unsubscribe link injection enabled.
     *
     * Resolution order:
     *   - `{unsubscribe_url}` / `{{unsubscribe_url}}` placeholders are replaced
     *     with the bare URL.
     *   - Otherwise a small footer link is appended (before `</body>` if
     *     present, or to the end of the body for plain-text content).
     */
    private function injectUnsubscribeLink(string $body, OutreachLead $lead, OutreachCampaign $campaign): string
    {
        if (! $campaign->getSetting('include_unsubscribe_link', true)) {
            return $body;
        }

        $url = route('outreach.unsubscribe', ['uuid' => $lead->uuid]);

        // Substitute placeholders first so users keep full control over
        // placement when they include them in the template.
        if (preg_match('/\{\{?\s*unsubscribe_url\s*\}?\}/i', $body) === 1) {
            return preg_replace('/\{\{?\s*unsubscribe_url\s*\}?\}/i', $url, $body) ?? $body;
        }

        $linkText = (string) $campaign->getSetting(
            'unsubscribe_text',
            'If you no longer wish to receive these emails, click here to unsubscribe.'
        );

        $isHtml = preg_match('/<\s*\w+[^>]*>/', $body) === 1;

        if ($isHtml) {
            $footer = '<p style="font-size:12px;color:#888;margin-top:24px;text-align:center;">'
                .'<a href="'.e($url).'" style="color:#888;text-decoration:underline;">'.e($linkText).'</a>'
                .'</p>';

            if (stripos($body, '</body>') !== false) {
                return str_ireplace('</body>', $footer.'</body>', $body);
            }

            return $body.$footer;
        }

        return rtrim($body)."\n\n".$linkText."\n".$url;
    }

    /**
     * Append a 1×1 open-tracking pixel to the body when `track_opens` is on.
     */
    private function injectOpenPixel(string $body, OutreachLead $lead, OutreachCampaign $campaign): string
    {
        if (! $campaign->getSetting('track_opens', true)) {
            return $body;
        }

        $url = route('outreach.track.open', ['uuid' => $lead->uuid]);
        $pixel = '<img src="'.e($url).'" alt="" width="1" height="1" style="display:none;border:0;" />';

        // Plain-text body: don't inject HTML — the mailer will wrap the text in
        // a basic HTML version which gets the pixel via the second pass below.
        $isHtml = preg_match('/<\s*\w+[^>]*>/', $body) === 1;
        if (! $isHtml) {
            return $body;
        }

        if (stripos($body, '</body>') !== false) {
            return str_ireplace('</body>', $pixel.'</body>', $body);
        }

        return $body.$pixel;
    }

    /**
     * Rewrite every `<a href="...">` to point at the click-tracking endpoint
     * when `track_clicks` is enabled. Already-rewritten links and the
     * unsubscribe link are skipped.
     */
    private function rewriteClickLinks(string $body, OutreachLead $lead, OutreachCampaign $campaign): string
    {
        if (! $campaign->getSetting('track_clicks', false)) {
            return $body;
        }

        $unsubscribeUrl = route('outreach.unsubscribe', ['uuid' => $lead->uuid]);

        return preg_replace_callback(
            '/<a\s+([^>]*?)href="([^"]+)"([^>]*)>/i',
            static function (array $matches) use ($lead, $unsubscribeUrl) {
                $original = $matches[2];

                if ($original === '' || $original === $unsubscribeUrl) {
                    return $matches[0];
                }

                // Only rewrite http(s) links.
                $scheme = parse_url($original, PHP_URL_SCHEME);
                if (! in_array($scheme, ['http', 'https'], true)) {
                    return $matches[0];
                }

                // Skip already-tracked outreach links.
                if (str_contains($original, '/outreach/track/click/')) {
                    return $matches[0];
                }

                $encoded = rtrim(strtr(base64_encode($original), '+/', '-_'), '=');
                $tracked = route('outreach.track.click', [
                    'uuid' => $lead->uuid,
                    'url' => $encoded,
                ]);

                return '<a '.$matches[1].'href="'.e($tracked).'"'.$matches[3].'>';
            },
            $body
        ) ?? $body;
    }
}

<?php

namespace App\Jobs;

use App\Mail\CampaignMailable;
use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\CampaignRecipient;
use App\Models\DeliveryServer;
use App\Models\SuppressionList;
use App\Services\AutomationTriggerService;
use App\Services\Billing\UsageService;
use App\Services\CampaignService;
use App\Services\DeliveryServerService;
use App\Services\EmailitApiService;
use App\Services\PersonalizationService;
use App\Services\SendingSpeedResolver;
use App\Services\SpamScoringService;
use App\Services\ZeptoMailApiService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class SendCampaignChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30; // 30 seconds between retries

    public int $timeout = 300; // 5 minutes timeout

    /**
     * Max seconds to smooth-wait in-process when a short rate/quota window
     * resets. Longer waits (hour/day/month) pause the campaign instead.
     */
    protected const SMOOTH_WAIT_MAX_SECONDS = 120;

    /**
     * Refresh campaign status every N recipients instead of every recipient.
     */
    protected const STATUS_REFRESH_EVERY = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign,
        public array $recipientIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh campaign to get latest status
        $this->campaign->refresh();

        // Check if campaign is still running
        if (! $this->campaign->isRunning()) {
            Log::info("Campaign {$this->campaign->id} is not running (status: {$this->campaign->status}). Skipping chunk.");

            return;
        }

        try {
            app(CampaignService::class)->ensureCanRun($this->campaign);
        } catch (\RuntimeException $e) {
            $this->campaign->update([
                'status' => 'failed',
                'finished_at' => now(),
                'failure_reason' => $e->getMessage(),
            ]);

            return;
        }

        // Get delivery server for campaign (we'll configure mailer before each send)
        $deliveryServer = null;
        $usingFallback = false;

        $this->campaign->loadMissing('customer');
        $customer = $this->campaign->customer;
        $mustAddDelivery = $customer ? (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false) : false;
        $canUseSystem = $customer ? (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false) : false;

        $requestedServerId = $this->campaign->delivery_server_id ? (int) $this->campaign->delivery_server_id : null;
        if (! $requestedServerId) {
            Log::warning('Campaign has no delivery server configured, using fallback default', [
                'campaign_id' => $this->campaign->id,
                'campaign_name' => $this->campaign->name,
                'message' => 'Campaign delivery_server_id is null. Please select a delivery server in campaign settings.',
            ]);
            $usingFallback = true;
        }

        if ($customer) {
            $deliveryServer = app(DeliveryServerService::class)->resolveDeliveryServerForCustomer(
                $customer,
                $requestedServerId,
                $mustAddDelivery,
                $canUseSystem
            );
        } else {
            $deliveryServer = DeliveryServer::query()
                ->with('bounceServer')
                ->whereNull('customer_id')
                ->where('status', 'active')
                ->where('use_for', true)
                ->orderBy('id')
                ->first();
        }

        if (! $deliveryServer) {
            if ($mustAddDelivery) {
                $this->campaign->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'failure_reason' => 'You must select a delivery server before running a campaign.',
                ]);

                return;
            }

            $this->campaign->update([
                'status' => 'failed',
                'finished_at' => now(),
                'failure_reason' => 'No available delivery server for this campaign.',
            ]);

            return;
        }

        if ($requestedServerId && (int) $deliveryServer->id !== (int) $requestedServerId) {
            Log::warning('Campaign has invalid or unavailable delivery server, falling back to default', [
                'campaign_id' => $this->campaign->id,
                'configured_delivery_server_id' => $requestedServerId,
                'fallback_delivery_server_id' => $deliveryServer->id,
            ]);
            $usingFallback = true;
        }

        if ($usingFallback) {
            Log::info('Using fallback delivery server for campaign', [
                'campaign_id' => $this->campaign->id,
                'fallback_delivery_server_id' => $deliveryServer->id,
                'fallback_delivery_server_name' => $deliveryServer->name,
                'configured_delivery_server_id' => $requestedServerId,
            ]);
        }

        $rotationEnabled = false;
        $rotationServers = collect([$deliveryServer]);
        $rotationServerIds = collect((array) data_get($this->campaign->settings, 'inbox_rotation_server_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if (
            (bool) data_get($this->campaign->settings, 'inbox_rotation_enabled', false)
            && $customer
            && $rotationServerIds->count() >= 2
        ) {
            $rotationServers = app(DeliveryServerService::class)
                ->querySelectableDeliveryServersForCustomer($customer, $mustAddDelivery, $canUseSystem)
                ->with('bounceServer')
                ->whereIn('id', $rotationServerIds->all())
                ->where('type', '!=', 'zeptomail-api')
                ->where('type', '!=', 'emailit')
                ->orderBy('id')
                ->get();

            if ($rotationServers->count() >= 2) {
                $rotationEnabled = true;
            } else {
                $rotationServers = collect([$deliveryServer]);
            }
        }

        if ($rotationEnabled) {
            Log::info('Inbox rotation enabled for campaign chunk', [
                'campaign_id' => $this->campaign->id,
                'rotation_server_ids' => $rotationServers->pluck('id')->values()->all(),
            ]);
        }

        if (
            $deliveryServer
            && $deliveryServer->type === 'amazon-ses'
            && (bool) config('services.clock_skew_check.auto_pause_campaigns', true)
            && $this->isClockSkewTooHigh()
        ) {
            $this->pauseCampaignForClockSkew((int) config('services.clock_skew_check.auto_pause_seconds', 300));

            return;
        }

        // Get recipients
        $recipients = CampaignRecipient::whereIn('id', $this->recipientIds)
            ->where('campaign_id', $this->campaign->id)
            ->where('status', 'pending')
            ->get();

        // Filter out suppressed recipients
        $suppressedEmails = SuppressionList::where('customer_id', $this->campaign->customer_id)
            ->pluck('email')
            ->toArray();

        $recipients = $recipients->reject(function ($recipient) use ($suppressedEmails) {
            return in_array($recipient->email, $suppressedEmails);
        });

        if ($recipients->isEmpty()) {
            Log::info("No pending recipients found for chunk in campaign {$this->campaign->id}");

            return;
        }

        $sentCount = 0;
        $failedCount = 0;
        $consecutiveTransportFailures = 0;
        $maxConsecutiveTransportFailures = 5;
        $abortedDueToTransportFailures = false;
        $lastTransportException = null;

        if (! $rotationEnabled && $deliveryServer && $deliveryServer->type === 'zeptomail-api') {
            $this->sendChunkViaZeptoBatch($deliveryServer, $recipients, $sentCount, $failedCount);

            Log::info("Campaign chunk processed for campaign {$this->campaign->id}: {$sentCount} sent, {$failedCount} failed");

            $this->campaign->refresh();
            $this->campaign->checkCompletion();

            return;
        }

        if (! $rotationEnabled && $deliveryServer && $deliveryServer->type === 'emailit') {
            $this->sendChunkViaEmailitBatch($deliveryServer, $recipients, $sentCount, $failedCount);

            Log::info("Campaign chunk processed for campaign {$this->campaign->id}: {$sentCount} sent, {$failedCount} failed");

            $this->campaign->refresh();
            $this->campaign->checkCompletion();

            return;
        }

        $recipientOffset = 0;
        $speed = app(SendingSpeedResolver::class);
        $gapMs = $speed->gapMs($this->campaign, $deliveryServer);
        $scheduleEnforced = $speed->scheduleEnforced();
        $processedSinceRefresh = 0;

        if ($scheduleEnforced && ! $speed->isInsideSendingSchedule($this->campaign)) {
            Log::info("Campaign {$this->campaign->id} is outside its sending schedule. Deferring chunk.", [
                'campaign_id' => $this->campaign->id,
            ]);

            return;
        }

        foreach ($recipients as $recipient) {
            if ($processedSinceRefresh === 0 || $processedSinceRefresh >= self::STATUS_REFRESH_EVERY) {
                $this->campaign->refresh();
                $processedSinceRefresh = 0;

                if ($scheduleEnforced && ! $speed->isInsideSendingSchedule($this->campaign)) {
                    Log::info("Campaign {$this->campaign->id} left its sending schedule. Stopping chunk.", [
                        'campaign_id' => $this->campaign->id,
                    ]);
                    break;
                }
            }
            $processedSinceRefresh++;

            if (! $this->campaign->isRunning()) {
                Log::info("Campaign {$this->campaign->id} was paused/failed. Stopping chunk processing.");
                break;
            }

            if ($consecutiveTransportFailures >= $maxConsecutiveTransportFailures) {
                Log::error("Campaign {$this->campaign->id}: {$consecutiveTransportFailures} consecutive transport failures. Stopping chunk to avoid further errors.", [
                    'campaign_id' => $this->campaign->id,
                    'consecutive_failures' => $consecutiveTransportFailures,
                ]);
                $abortedDueToTransportFailures = true;
                break;
            }

            $attemptResult = ['status' => 'failed', 'server' => $deliveryServer, 'exception' => null, 'wait_seconds' => null, 'offset_increment' => 1];

            if ($rotationEnabled && $rotationServers->count() > 1) {
                $attemptResult = $this->sendRecipientWithRotationFailover($recipient, $rotationServers, $recipientOffset, $sentCount);
            } elseif ($deliveryServer instanceof DeliveryServer) {
                $attemptResult = $this->attemptSendRecipientViaDeliveryServer($recipient, $deliveryServer, $sentCount);
            } else {
                Log::error('No delivery server available for recipient', [
                    'campaign_id' => $this->campaign->id,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                ]);
            }

            if (($attemptResult['status'] ?? null) === 'success') {
                $consecutiveTransportFailures = 0;
            } elseif (($attemptResult['status'] ?? null) === 'paused') {
                $pauseServer = $attemptResult['server'] ?? $deliveryServer;
                $waitSeconds = max(1, (int) ($attemptResult['wait_seconds'] ?? 0));

                if ($pauseServer instanceof DeliveryServer) {
                    $this->pauseCampaignForDeliveryServerQuota($pauseServer, $waitSeconds);
                }

                return;
            } else {
                $failedCount++;
                $consecutiveTransportFailures++;

                $failedException = $attemptResult['exception'] ?? new \RuntimeException('Failed to send campaign email.');
                $lastTransportException = $failedException;
                $failedServer = $attemptResult['server'] ?? $deliveryServer;

                if ($this->isRateLimitError($failedException)) {
                    $this->handleRateLimitError($failedException);

                    return;
                }

                $this->markRecipientAsFailed($recipient, $failedServer instanceof DeliveryServer ? $failedServer : null, $failedException);
            }

            $recipientOffset += max(1, (int) ($attemptResult['offset_increment'] ?? 1));

            if ($gapMs > 0) {
                usleep($gapMs * 1000);
            }

            if ($rotationEnabled) {
                $rotationMinDelay = max(0, (int) data_get($this->campaign->settings, 'inbox_rotation_min_delay', 0));
                $rotationMaxDelay = max($rotationMinDelay, (int) data_get($this->campaign->settings, 'inbox_rotation_max_delay', 0));
                $rotationDelay = $rotationMaxDelay > $rotationMinDelay
                    ? random_int($rotationMinDelay, $rotationMaxDelay)
                    : $rotationMinDelay;
                if ($rotationDelay > 0) {
                    sleep($rotationDelay);
                }
            }
        }

        if ($abortedDueToTransportFailures) {
            $failureReason = 'Campaign sending stopped after repeated transport failures.';
            if ($lastTransportException instanceof \Throwable && $lastTransportException->getMessage() !== '') {
                $failureReason .= ' Last error: '.$lastTransportException->getMessage();
            }

            $this->campaign->update([
                'status' => 'failed',
                'finished_at' => now(),
                'failure_reason' => $failureReason,
            ]);

            return;
        }

        Log::info("Campaign chunk processed for campaign {$this->campaign->id}: {$sentCount} sent, {$failedCount} failed");

        // Check for bounces after chunk completion if bounce server is configured
        $this->campaign->loadMissing('bounceServer');
        $bounceServer = $this->campaign->bounceServer ?: $deliveryServer?->bounceServer;
        if ($bounceServer && $bounceServer->isActive()) {
            $this->checkBounces($bounceServer);
        }

        // Check if campaign is complete
        $this->campaign->refresh();
        $this->campaign->checkCompletion();
    }

    /**
     * Check for bounced emails from the bounce server.
     */
    protected function checkBounces($bounceServer): void
    {
        try {
            // Check if IMAP extension is available
            if (! function_exists('imap_open')) {
                Log::warning("IMAP extension not available. Bounce checking skipped for campaign {$this->campaign->id}. Please install php-imap extension.", [
                    'campaign_id' => $this->campaign->id,
                    'bounce_server_id' => $bounceServer->id,
                    'message' => 'Install php-imap extension to enable automatic bounce processing. You can manually process bounces using: php artisan bounces:process',
                ]);

                return;
            }

            $bounceProcessor = app(\App\Services\BounceProcessorService::class);
            $processedCount = $bounceProcessor->processBounces($bounceServer);

            if ($processedCount > 0) {
                Log::info("Processed {$processedCount} bounce(s) for campaign {$this->campaign->id}", [
                    'campaign_id' => $this->campaign->id,
                    'bounce_server_id' => $bounceServer->id,
                    'processed_count' => $processedCount,
                ]);

                // Refresh campaign to get updated bounce count
                $this->campaign->refresh();

                // Sync stats to ensure accuracy
                $this->campaign->syncStats();
            }
        } catch (\Exception $e) {
            // Log error but don't fail the chunk job
            Log::error("Failed to check bounces for campaign {$this->campaign->id}: ".$e->getMessage(), [
                'campaign_id' => $this->campaign->id,
                'bounce_server_id' => $bounceServer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Consume delivery server quotas for a number of messages.
     *
     * Returns null if allowed, or the number of seconds until the earliest quota window resets.
     */
    protected function consumeDeliveryServerQuotaOrReturnDelaySeconds(DeliveryServer $deliveryServer, int $tokens): ?int
    {
        if ($tokens <= 0) {
            return null;
        }

        $limits = [
            ['suffix' => 'second', 'max' => (int) ($deliveryServer->second_quota ?? 0), 'decay' => 1],
            ['suffix' => 'minute', 'max' => (int) ($deliveryServer->minute_quota ?? 0), 'decay' => 60],
            ['suffix' => 'hour', 'max' => (int) ($deliveryServer->hourly_quota ?? 0), 'decay' => 3600],
            ['suffix' => 'day', 'max' => (int) ($deliveryServer->daily_quota ?? 0), 'decay' => 86400],
            ['suffix' => 'month', 'max' => (int) ($deliveryServer->monthly_quota ?? 0), 'decay' => 2592000],
        ];

        // First check all quotas without consuming.
        $maxWait = 0;
        foreach ($limits as $limit) {
            if ($limit['max'] <= 0) {
                continue;
            }

            $key = $this->deliveryServerQuotaKey($deliveryServer, $limit['suffix']);
            if (RateLimiter::remaining($key, $limit['max']) < $tokens) {
                $maxWait = max($maxWait, RateLimiter::availableIn($key));
            }
        }

        if ($maxWait > 0) {
            return $maxWait;
        }

        // Consume tokens for all quotas.
        foreach ($limits as $limit) {
            if ($limit['max'] <= 0) {
                continue;
            }

            $key = $this->deliveryServerQuotaKey($deliveryServer, $limit['suffix']);
            for ($i = 0; $i < $tokens; $i++) {
                RateLimiter::hit($key, $limit['decay']);
            }
        }

        return null;
    }

    protected function deliveryServerQuotaKey(DeliveryServer $deliveryServer, string $suffix): string
    {
        return 'delivery-server-quota:'.$deliveryServer->id.':'.$suffix;
    }

    /**
     * Consume quota tokens, sleeping through short windows when possible.
     * Returns null on success, or seconds to pause for long windows.
     */
    protected function smoothWaitForQuota(DeliveryServer $deliveryServer, int $tokens): ?int
    {
        $waitedSeconds = 0;

        while (true) {
            $delay = $this->consumeDeliveryServerQuotaOrReturnDelaySeconds($deliveryServer, $tokens);
            if ($delay === null) {
                return null;
            }

            if (
                $delay > self::SMOOTH_WAIT_MAX_SECONDS
                || ($waitedSeconds + $delay) > self::SMOOTH_WAIT_MAX_SECONDS
            ) {
                return $delay;
            }

            sleep($delay);
            $waitedSeconds += $delay;
        }
    }

    protected function pauseCampaignForDeliveryServerQuota(DeliveryServer $deliveryServer, int $waitSeconds): void
    {
        $waitSeconds = max(1, $waitSeconds);
        $resumeAt = now()->addSeconds($waitSeconds + 10);

        $settings = is_array($this->campaign->settings) ? $this->campaign->settings : [];
        $settings['auto_resume_at'] = $resumeAt->toDateTimeString();
        $settings['auto_resume_reason'] = 'delivery_server_quota';

        Log::warning('Delivery server quota reached. Pausing campaign.', [
            'campaign_id' => $this->campaign->id,
            'delivery_server_id' => $deliveryServer->id,
            'delivery_server_name' => $deliveryServer->name,
            'wait_seconds' => $waitSeconds,
            'resume_at' => $resumeAt->toIso8601String(),
            'hourly_quota' => (int) ($deliveryServer->hourly_quota ?? 0),
            'daily_quota' => (int) ($deliveryServer->daily_quota ?? 0),
            'monthly_quota' => (int) ($deliveryServer->monthly_quota ?? 0),
        ]);

        $this->campaign->update([
            'status' => 'paused',
            'failure_reason' => 'Delivery server quota reached. Auto-resume scheduled.',
            'settings' => $settings,
        ]);

        \App\Jobs\ResumeCampaignJob::dispatch($this->campaign)
            ->delay($resumeAt)
            ->onQueue('campaigns');
    }

    protected function isClockSkewTooHigh(): bool
    {
        $cacheSeconds = max(0, (int) config('services.clock_skew_check.cache_seconds', 60));
        $cacheKey = 'system:clock_skew_status';

        $status = Cache::remember($cacheKey, now()->addSeconds($cacheSeconds), function () {
            $url = (string) config('services.clock_skew_check.url', 'https://www.google.com');
            $thresholdSeconds = (int) config('services.clock_skew_check.threshold_seconds', 120);
            $timeoutSeconds = (int) config('services.clock_skew_check.timeout_seconds', 5);

            try {
                $response = Http::timeout($timeoutSeconds)
                    ->withHeaders([
                        'Cache-Control' => 'no-cache',
                        'Pragma' => 'no-cache',
                    ])
                    ->head($url);

                $dateHeader = $response->header('Date');
                if (! is_string($dateHeader) || trim($dateHeader) === '') {
                    throw new \RuntimeException('No Date header returned by time-check endpoint.');
                }

                $remoteUtc = CarbonImmutable::parse($dateHeader, 'UTC');
                $localUtc = CarbonImmutable::now('UTC');
                $skewSeconds = abs($localUtc->diffInSeconds($remoteUtc, false));

                return [
                    'checked_at' => CarbonImmutable::now()->toIso8601String(),
                    'url' => $url,
                    'threshold_seconds' => $thresholdSeconds,
                    'timeout_seconds' => $timeoutSeconds,
                    'skew_seconds' => $skewSeconds,
                    'server_utc' => $localUtc->toIso8601String(),
                    'remote_utc' => $remoteUtc->toIso8601String(),
                    'ok' => $skewSeconds <= $thresholdSeconds,
                ];
            } catch (\Throwable $e) {
                return [
                    'checked_at' => CarbonImmutable::now()->toIso8601String(),
                    'url' => $url,
                    'threshold_seconds' => $thresholdSeconds,
                    'timeout_seconds' => $timeoutSeconds,
                    'ok' => true,
                    'error' => $e->getMessage(),
                ];
            }
        });

        if (! is_array($status)) {
            return false;
        }

        if (! empty($status['error'] ?? null)) {
            Log::warning('Clock skew check failed (campaign send will continue)', [
                'campaign_id' => $this->campaign->id,
                'error' => $status['error'],
            ]);
        }

        return ($status['ok'] ?? true) === false;
    }

    protected function pauseCampaignForClockSkew(int $waitSeconds): void
    {
        $waitSeconds = max(60, $waitSeconds);
        $resumeAt = now()->addSeconds($waitSeconds);

        Log::warning('Clock skew too high. Pausing campaign to avoid AWS signature errors.', [
            'campaign_id' => $this->campaign->id,
            'wait_seconds' => $waitSeconds,
            'resume_at' => $resumeAt->toIso8601String(),
        ]);

        $this->campaign->update([
            'status' => 'paused',
            'failure_reason' => 'Server time appears out of sync. Campaign paused to avoid AWS SES signature errors. Auto-resume scheduled.',
        ]);

        \App\Jobs\ResumeCampaignJob::dispatch($this->campaign)
            ->delay($resumeAt)
            ->onQueue('campaigns');
    }

    protected function sendRecipientWithRotationFailover(CampaignRecipient $recipient, Collection $rotationServers, int $recipientOffset, int &$sentCount): array
    {
        $candidateCount = $rotationServers->count();
        $lastAttemptServer = null;
        $lastException = null;
        $quotaWaitSeconds = null;

        for ($i = 0; $i < $candidateCount; $i++) {
            $attemptDeliveryServer = $rotationServers->get(($recipientOffset + $i) % $candidateCount);

            if (! $attemptDeliveryServer instanceof DeliveryServer) {
                continue;
            }

            $lastAttemptServer = $attemptDeliveryServer;

            $attemptResult = $this->attemptSendRecipientViaDeliveryServer($recipient, $attemptDeliveryServer, $sentCount);
            $status = $attemptResult['status'] ?? 'failed';

            if ($status === 'success') {
                return [
                    'status' => 'success',
                    'server' => $attemptDeliveryServer,
                    'offset_increment' => 1,
                ];
            }

            if ($status === 'paused') {
                $quotaWaitSeconds = max($quotaWaitSeconds ?? 0, (int) ($attemptResult['wait_seconds'] ?? 0));

                continue;
            }

            $lastException = $attemptResult['exception'] ?? null;

            Log::warning('Delivery server failed during rotation failover; trying the next server', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $recipient->id,
                'recipient_email' => $recipient->email,
                'delivery_server_id' => $attemptDeliveryServer->id,
                'delivery_server_name' => $attemptDeliveryServer->name,
                'error' => $lastException?->getMessage(),
            ]);
        }

        if ($quotaWaitSeconds !== null && $lastException === null) {
            return [
                'status' => 'paused',
                'server' => $lastAttemptServer,
                'wait_seconds' => $quotaWaitSeconds,
                'offset_increment' => 1,
            ];
        }

        return [
            'status' => 'failed',
            'server' => $lastAttemptServer,
            'exception' => $lastException,
            'offset_increment' => 1,
        ];
    }

    protected function attemptSendRecipientViaDeliveryServer(CampaignRecipient $recipient, DeliveryServer $attemptDeliveryServer, int &$sentCount): array
    {
        if (! $attemptDeliveryServer->isActive()) {
            return [
                'status' => 'failed',
                'server' => $attemptDeliveryServer,
                'exception' => new \RuntimeException('Delivery server is not active.'),
            ];
        }

        // Inbox-rotation daily cap (per campaign, per server, per day).
        // Check now; hit only after a successful send so failures don't burn the cap.
        $rotationDailyCap = max(0, (int) data_get($this->campaign->settings, 'inbox_rotation_daily_cap', 0));
        $rotationEnabledForCap = (bool) data_get($this->campaign->settings, 'inbox_rotation_enabled', false);
        $dailyKey = null;
        if ($rotationDailyCap > 0 && $rotationEnabledForCap) {
            $dailyKey = 'rotation-daily:'.$this->campaign->id.':server:'.$attemptDeliveryServer->id;
            if (RateLimiter::tooManyAttempts($dailyKey, $rotationDailyCap)) {
                return [
                    'status' => 'paused',
                    'server' => $attemptDeliveryServer,
                    'wait_seconds' => max(1, (int) RateLimiter::availableIn($dailyKey)),
                ];
            }
        }

        // Smooth-wait short quota windows in-process; long windows pause the campaign.
        $waitedSeconds = 0;
        while (true) {
            $quotaPauseDelaySeconds = $this->consumeDeliveryServerQuotaOrReturnDelaySeconds($attemptDeliveryServer, 1);
            if ($quotaPauseDelaySeconds === null) {
                break;
            }

            if (
                $quotaPauseDelaySeconds > self::SMOOTH_WAIT_MAX_SECONDS
                || ($waitedSeconds + $quotaPauseDelaySeconds) > self::SMOOTH_WAIT_MAX_SECONDS
            ) {
                return [
                    'status' => 'paused',
                    'server' => $attemptDeliveryServer,
                    'wait_seconds' => $quotaPauseDelaySeconds,
                ];
            }

            sleep($quotaPauseDelaySeconds);
            $waitedSeconds += $quotaPauseDelaySeconds;
        }

        if ($this->shouldBlockDueToSpamScore($recipient, $attemptDeliveryServer)) {
            return [
                'status' => 'failed',
                'server' => $attemptDeliveryServer,
                'exception' => new \RuntimeException('Campaign email blocked by spam scoring.'),
            ];
        }

        $mailerName = $this->configureMailer($attemptDeliveryServer);
        $perMinute = $this->getRateLimitForServer($attemptDeliveryServer);
        $rateLimitKey = $this->campaignSendRateLimitKey($attemptDeliveryServer);

        // Smooth-wait the per-minute rate limiter window before sending.
        $rateWaited = 0;
        while ($perMinute > 0 && RateLimiter::tooManyAttempts($rateLimitKey, $perMinute)) {
            $waitSeconds = max(1, (int) RateLimiter::availableIn($rateLimitKey));
            if (
                $waitSeconds > self::SMOOTH_WAIT_MAX_SECONDS
                || ($rateWaited + $waitSeconds) > self::SMOOTH_WAIT_MAX_SECONDS
            ) {
                return [
                    'status' => 'paused',
                    'server' => $attemptDeliveryServer,
                    'wait_seconds' => $waitSeconds,
                ];
            }

            sleep($waitSeconds);
            $rateWaited += $waitSeconds;
        }

        $mailConfigSnapshot = app(DeliveryServerService::class)->snapshotMailConfig();

        try {
            $this->campaign->loadMissing('bounceServer');
            $bounceServer = $this->campaign->bounceServer ?: $attemptDeliveryServer?->bounceServer;
            Log::info('Sending campaign email', [
                'campaign_id' => $this->campaign->id,
                'recipient_email' => $recipient->email,
                'recipient_id' => $recipient->id,
                'delivery_server_id' => $attemptDeliveryServer?->id,
                'delivery_server_name' => $attemptDeliveryServer?->name,
                'bounce_server_id' => $bounceServer?->id,
                'bounce_server_username' => $bounceServer?->username,
            ]);

            $mailer = $mailerName ? Mail::mailer($mailerName) : Mail::mailer();
            $mailer->to($recipient->email)
                ->send(new CampaignMailable($this->campaign, $recipient, $attemptDeliveryServer));

            DB::transaction(function () use ($recipient, $attemptDeliveryServer) {
                $recipientMeta = is_array($recipient->meta) ? $recipient->meta : [];
                $recipientMeta['delivery_server_id'] = $attemptDeliveryServer?->id;
                $recipientMeta['delivery_server_name'] = $attemptDeliveryServer?->name;
                $recipientMeta['delivery_server_from_email'] = $attemptDeliveryServer?->from_email;

                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'meta' => $recipientMeta,
                ]);
                $this->campaign->incrementSentCount();

                CampaignLog::logEvent(
                    $this->campaign->id,
                    'accepted',
                    $recipient->id,
                    [
                        'email' => $recipient->email,
                        'message' => 'Email accepted by mail server',
                        'delivery_server_id' => $attemptDeliveryServer?->id,
                        'delivery_server_name' => $attemptDeliveryServer?->name,
                        'delivery_server_from_email' => $attemptDeliveryServer?->from_email,
                    ]
                );
            });

            if ($this->campaign->customer) {
                try {
                    app(UsageService::class)->log(
                        $this->campaign->customer,
                        'emails_sent_this_month',
                        1,
                        [
                            'campaign_id' => $this->campaign->id,
                            'recipient_id' => $recipient->id,
                            'delivery_server_id' => $attemptDeliveryServer?->id,
                        ]
                    );
                } catch (\Throwable $usageException) {
                    Log::warning('Failed to record campaign usage log; continuing send', [
                        'campaign_id' => $this->campaign->id,
                        'recipient_id' => $recipient->id,
                        'error' => $usageException->getMessage(),
                    ]);
                }
            }

            try {
                app(AutomationTriggerService::class)->scheduleNegativeCampaignTriggersForRecipient($this->campaign, $recipient);
            } catch (\Throwable $e) {
                Log::warning('Failed scheduling negative campaign automation triggers', [
                    'campaign_id' => $this->campaign->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $sentCount++;

            if ($perMinute > 0) {
                RateLimiter::hit($rateLimitKey, 60);
            }

            if ($dailyKey !== null) {
                RateLimiter::hit($dailyKey, 86400);
            }

            return [
                'status' => 'success',
                'server' => $attemptDeliveryServer,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'server' => $attemptDeliveryServer,
                'exception' => $e,
            ];
        } finally {
            // The send used its own resolved mailer; restore global config so the
            // next job/server in this long-lived worker cannot leak into it.
            $this->restoreMailConfig($mailConfigSnapshot);
        }
    }

    protected function markRecipientAsFailed(CampaignRecipient $recipient, ?DeliveryServer $attemptDeliveryServer, \Throwable $exception): void
    {
        try {
            $recipient->refresh();

            if ($recipient->status === 'failed') {
                return;
            }

            DB::transaction(function () use ($recipient, $attemptDeliveryServer, $exception) {
                $recipientMeta = is_array($recipient->meta) ? $recipient->meta : [];
                $recipientMeta['delivery_server_id'] = $attemptDeliveryServer?->id;
                $recipientMeta['delivery_server_name'] = $attemptDeliveryServer?->name;
                $recipientMeta['delivery_server_from_email'] = $attemptDeliveryServer?->from_email;

                $recipient->markAsFailed($exception->getMessage());
                $recipient->update([
                    'meta' => $recipientMeta,
                ]);
                $this->campaign->incrementFailedCount();

                CampaignLog::logEvent(
                    $this->campaign->id,
                    'failed',
                    $recipient->id,
                    [
                        'email' => $recipient->email,
                        'delivery_server_id' => $attemptDeliveryServer?->id,
                        'delivery_server_name' => $attemptDeliveryServer?->name,
                        'delivery_server_from_email' => $attemptDeliveryServer?->from_email,
                    ],
                    null,
                    null,
                    null,
                    $exception->getMessage()
                );
            });
        } catch (\Throwable $markError) {
            Log::error("Failed to mark recipient {$recipient->id} as failed: ".$markError->getMessage());
        }
    }

    /**
     * Configure mailer based on delivery server.
     * Returns the mailer name to use, or null if using default.
     */
    protected function configureMailer(?DeliveryServer $deliveryServer): ?string
    {
        if (! $deliveryServer || ! $deliveryServer->isActive()) {
            Log::warning("No active delivery server found for campaign {$this->campaign->id}. Using default mailer.");

            return null;
        }

        // Ensure bounce server relation is loaded so we can log its email
        $deliveryServer->loadMissing('bounceServer');

        // Determine the effective sending domain for this campaign (if verified)
        $this->campaign->loadMissing('sendingDomain', 'emailList.sendingDomain');
        $sendingDomainOverride = null;
        if ($this->campaign->sendingDomain && $this->campaign->sendingDomain->isVerified()) {
            $sendingDomainOverride = $this->campaign->sendingDomain->domain;
        } elseif ($this->campaign->emailList && $this->campaign->emailList->sendingDomain && $this->campaign->emailList->sendingDomain->isVerified()) {
            $sendingDomainOverride = $this->campaign->emailList->sendingDomain->domain;
        }

        $bounceServer = $deliveryServer->bounceServer;
        $bounceServerId = $bounceServer?->id;
        $bounceEmail = $bounceServer?->username;

        // Log which bounce email is configured for this delivery server
        Log::info('Configuring mailer with delivery/bounce server for campaign', [
            'campaign_id' => $this->campaign->id,
            'delivery_server_id' => $deliveryServer->id,
            'delivery_server_name' => $deliveryServer->name,
            'delivery_server_type' => $deliveryServer->type,
            'delivery_server_from_email' => $this->campaign->from_email,
            'bounce_server_id' => $bounceServerId,
            'bounce_email' => $bounceEmail,
            'has_bounce_server' => $bounceServer !== null,
        ]);

        // Warn if no bounce server is configured
        if (! $bounceServer) {
            Log::warning('Delivery server has no bounce server configured', [
                'campaign_id' => $this->campaign->id,
                'delivery_server_id' => $deliveryServer->id,
                'delivery_server_name' => $deliveryServer->name,
                'message' => 'Bounced emails will not be properly tracked. Consider configuring a bounce server for this delivery server.',
            ]);
        }

        // Configure mailer using DeliveryServerService
        $deliveryServerService = app(DeliveryServerService::class);
        $deliveryServerService->configureMailFromServer($deliveryServer, $sendingDomainOverride);

        // Get the configured mailer name
        $mailerName = config('mail.default', 'smtp');

        Log::debug("Configured mailer for campaign {$this->campaign->id} using delivery server: {$deliveryServer->name} ({$deliveryServer->type}), mailer: {$mailerName}");

        return $mailerName;
    }

    /**
     * Restore the mail config snapshot captured before configureMailFromServer.
     */
    protected function restoreMailConfig(array $snapshot): void
    {
        try {
            app(DeliveryServerService::class)->restoreMailConfig($snapshot);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    protected function sendChunkViaZeptoBatch(DeliveryServer $deliveryServer, $recipients, int &$sentCount, int &$failedCount): void
    {
        $this->campaign->loadMissing('trackingDomain');

        $trackingBase = null;
        $hasCustomTrackingDomain = false;
        if ($this->campaign->trackingDomain && $this->campaign->trackingDomain->isVerified()) {
            $trackingBase = 'https://'.$this->campaign->trackingDomain->domain;
            $hasCustomTrackingDomain = true;
        }
        $trackingBase = $trackingBase ?: rtrim((string) config('app.url'), '/');
        $fallbackTrackingBase = rtrim((string) config('app.url'), '/');

        $subject = $this->convertPlaceholdersToZeptoMergeTags((string) ($this->campaign->subject ?? ''), false);

        $htmlBody = $this->campaign->html_content ?? null;
        $textBody = $this->campaign->plain_text_content ?? null;

        $htmlBody = is_string($htmlBody) ? $this->convertPlaceholdersToZeptoMergeTags($htmlBody) : null;
        $textBody = is_string($textBody) ? $this->convertPlaceholdersToZeptoMergeTags($textBody) : null;

        // Append footer template to Zepto HTML if configured
        $footerTemplateId = $this->campaign->settings['footer_template_id'] ?? null;
        if ($footerTemplateId && is_string($htmlBody)) {
            $footerTemplate = \App\Models\Template::find($footerTemplateId);
            if ($footerTemplate && $footerTemplate->type === 'footer') {
                $footerHtml = $this->convertPlaceholdersToZeptoMergeTags($footerTemplate->html_content ?? '');
                if (stripos($htmlBody, '</body>') !== false) {
                    $htmlBody = str_ireplace('</body>', $footerHtml.'</body>', $htmlBody);
                } else {
                    $htmlBody .= $footerHtml;
                }
            }
        }

        // Append signature template to Zepto HTML if configured
        $signatureTemplateId = $this->campaign->settings['signature_template_id'] ?? null;
        if ($signatureTemplateId && is_string($htmlBody)) {
            $signatureTemplate = \App\Models\Template::find($signatureTemplateId);
            if ($signatureTemplate && $signatureTemplate->type === 'signature') {
                $signatureHtml = $this->convertPlaceholdersToZeptoMergeTags($signatureTemplate->html_content ?? '');
                if (stripos($htmlBody, '</body>') !== false) {
                    $htmlBody = str_ireplace('</body>', $signatureHtml.'</body>', $htmlBody);
                } else {
                    $htmlBody .= $signatureHtml;
                }
            }
        }

        // Append footer and signature to Zepto plain text if configured
        if ($footerTemplateId && is_string($textBody)) {
            $footerTemplate = \App\Models\Template::find($footerTemplateId);
            if ($footerTemplate && $footerTemplate->type === 'footer') {
                $footerText = $this->convertPlaceholdersToZeptoMergeTags($footerTemplate->plain_text_content ?? '');
                $textBody .= "\n\n".$footerText;
            }
        }
        if ($signatureTemplateId && is_string($textBody)) {
            $signatureTemplate = \App\Models\Template::find($signatureTemplateId);
            if ($signatureTemplate && $signatureTemplate->type === 'signature') {
                $signatureText = $this->convertPlaceholdersToZeptoMergeTags($signatureTemplate->plain_text_content ?? '');
                $textBody .= "\n\n".$signatureText;
            }
        }

        $unsubscribeUrl = $trackingBase.'/unsubscribe/{{recipient_uuid}}';
        $trackOpenUrl = $trackingBase.'/track/open/{{recipient_uuid}}';

        if (is_string($htmlBody) && $htmlBody !== '') {
            if ($this->campaign->track_opens) {
                $trackPixel = '<img src="'.$trackOpenUrl.'" width="1" height="1" style="display:none;" alt="" />';

                if ($hasCustomTrackingDomain) {
                    $trackHost = parse_url($trackingBase, PHP_URL_HOST);
                    $fallbackHost = parse_url($fallbackTrackingBase, PHP_URL_HOST);
                    if (is_string($trackHost) && $trackHost !== '' && is_string($fallbackHost) && $fallbackHost !== '' && strcasecmp($trackHost, $fallbackHost) !== 0) {
                        $fallbackOpenUrl = $fallbackTrackingBase.'/track/open/{{recipient_uuid}}';
                        $trackPixel .= '<img src="'.$fallbackOpenUrl.'" width="1" height="1" style="display:none;" alt="" />';
                    }
                }
                if (stripos($htmlBody, '</body>') !== false) {
                    $htmlBody = str_ireplace('</body>', $trackPixel.'</body>', $htmlBody);
                } else {
                    $htmlBody .= $trackPixel;
                }
            }

            if ($this->campaign->track_clicks) {
                $htmlBody = $this->wrapLinksWithZeptoTracking($htmlBody, $trackingBase);
            }

            $htmlBody = str_ireplace(['{{unsubscribe_url}}', '{unsubscribe_url}'], $unsubscribeUrl, $htmlBody);
            $htmlBody = (string) preg_replace('/(?<![a-zA-Z0-9_])unsubscribe_url(?![a-zA-Z0-9_])/i', $unsubscribeUrl, $htmlBody);
        }

        if (! is_string($textBody) || trim($textBody) === '') {
            $textBody = is_string($htmlBody) ? strip_tags($htmlBody) : '';
        }
        $textBody = str_ireplace(['{{unsubscribe_url}}', '{unsubscribe_url}'], $unsubscribeUrl, (string) $textBody);
        $textBody = (string) preg_replace('/(?<![a-zA-Z0-9_])unsubscribe_url(?![a-zA-Z0-9_])/i', $unsubscribeUrl, $textBody);

        $recipientByEmail = $recipients instanceof Collection ? $recipients->groupBy('email') : null;

        $to = [];
        foreach ($recipients as $recipient) {
            $toName = trim((string) (($recipient->first_name ?? '').' '.($recipient->last_name ?? '')));

            $meta = is_array($recipient->meta) ? $recipient->meta : [];
            $custom = (isset($meta['custom_fields']) && is_array($meta['custom_fields'])) ? $meta['custom_fields'] : [];

            $customMerge = [];
            foreach ($custom as $k => $v) {
                if (! is_string($k) || $k === '') {
                    continue;
                }
                if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $k)) {
                    continue;
                }
                if ($v === null) {
                    $customMerge['cf_'.$k] = '';

                    continue;
                }
                if (is_scalar($v)) {
                    $customMerge['cf_'.$k] = (string) $v;
                }
            }

            $to[] = [
                'email_address' => [
                    'address' => (string) $recipient->email,
                    'name' => $toName,
                ],
                'merge_info' => [
                    'first_name' => (string) ($recipient->first_name ?? ''),
                    'last_name' => (string) ($recipient->last_name ?? ''),
                    'email' => (string) $recipient->email,
                    'full_name' => $toName,
                    'recipient_uuid' => (string) ($recipient->uuid ?? ''),
                ] + $customMerge,
            ];
        }

        $message = [
            'from_email' => (string) ($this->campaign->from_email ?? ''),
            'from_name' => (string) ($this->campaign->from_name ?? ''),
            'subject' => $subject,
            'htmlbody' => $htmlBody,
            'textbody' => $textBody,
            'track_clicks' => (bool) ($this->campaign->track_clicks ?? false),
            'track_opens' => (bool) ($this->campaign->track_opens ?? false),
            'client_reference' => 'campaign-'.$this->campaign->id.'-{{recipient_uuid}}',
            'headers' => [
                'X-Campaign-ID' => (string) $this->campaign->id,
                'X-List-ID' => (string) ($this->campaign->list_id ?? ''),
                'X-Delivery-Server-ID' => (string) ($deliveryServer->id ?? ''),
            ],
        ];

        $deliveryServer->loadMissing('bounceServer');
        if (empty(($deliveryServer->settings ?? [])['bounce_address']) && ! empty($deliveryServer->bounceServer?->username)) {
            $message['bounce_address'] = (string) $deliveryServer->bounceServer->username;
        }

        $replyTrackingEnabled = (bool) config('mailpurse.reply_tracking.enabled', false);

        $this->campaign->loadMissing('replyServer');
        $replyDomain = trim((string) ($this->campaign->replyServer?->reply_domain ?? config('mailpurse.reply_tracking.reply_domain', '')));
        if ($replyTrackingEnabled && $replyDomain !== '') {
            $message['headers']['Reply-To'] = 'reply+{{recipient_uuid}}@'.$replyDomain;
        }

        $service = app(ZeptoMailApiService::class);
        $chunks = array_chunk($to, 500);

        foreach ($chunks as $chunk) {
            try {
                $quotaPauseDelaySeconds = $this->smoothWaitForQuota($deliveryServer, count($chunk));
                if ($quotaPauseDelaySeconds !== null) {
                    $this->pauseCampaignForDeliveryServerQuota($deliveryServer, $quotaPauseDelaySeconds);

                    return;
                }

                $service->sendBatch($deliveryServer, $message, $chunk);

                DB::transaction(function () use ($chunk, $recipientByEmail, &$sentCount) {
                    foreach ($chunk as $toItem) {
                        $email = $toItem['email_address']['address'] ?? null;
                        if (! is_string($email) || $email === '') {
                            continue;
                        }

                        $recipient = null;
                        if ($recipientByEmail) {
                            $recipient = $recipientByEmail->get($email)?->first();
                        }

                        if (! $recipient) {
                            $recipient = CampaignRecipient::where('campaign_id', $this->campaign->id)
                                ->where('email', $email)
                                ->where('status', 'pending')
                                ->first();
                        }

                        if (! $recipient || $recipient->status !== 'pending') {
                            continue;
                        }

                        $recipient->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                        $this->campaign->incrementSentCount();

                        CampaignLog::logEvent(
                            $this->campaign->id,
                            'accepted',
                            $recipient->id,
                            [
                                'email' => $recipient->email,
                                'message' => 'Email accepted by ZeptoMail API',
                                'delivery_server_id' => $deliveryServer->id,
                                'delivery_server_name' => $deliveryServer->name,
                                'delivery_server_from_email' => $deliveryServer->from_email,
                            ]
                        );

                        if ($this->campaign->customer) {
                            try {
                                app(UsageService::class)->log(
                                    $this->campaign->customer,
                                    'emails_sent_this_month',
                                    1,
                                    [
                                        'campaign_id' => $this->campaign->id,
                                        'recipient_id' => $recipient->id,
                                        'delivery_server_id' => $deliveryServer->id,
                                    ]
                                );
                            } catch (\Throwable $usageException) {
                                Log::warning('Failed to record campaign usage log for ZeptoMail send; continuing send', [
                                    'campaign_id' => $this->campaign->id,
                                    'recipient_id' => $recipient->id,
                                    'error' => $usageException->getMessage(),
                                ]);
                            }
                        }

                        $sentCount++;

                        try {
                            app(AutomationTriggerService::class)->scheduleNegativeCampaignTriggersForRecipient($this->campaign, $recipient);
                        } catch (\Throwable $e) {
                            Log::warning('Failed scheduling negative campaign automation triggers', [
                                'campaign_id' => $this->campaign->id,
                                'recipient_id' => $recipient->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                $failedCount += count($chunk);
                Log::error('ZeptoMail batch send failed: '.$e->getMessage(), [
                    'campaign_id' => $this->campaign->id,
                    'delivery_server_id' => $deliveryServer->id,
                ]);

                DB::transaction(function () use ($chunk, $e) {
                    foreach ($chunk as $toItem) {
                        $email = $toItem['email_address']['address'] ?? null;
                        if (! is_string($email) || $email === '') {
                            continue;
                        }

                        $recipient = CampaignRecipient::where('campaign_id', $this->campaign->id)
                            ->where('email', $email)
                            ->where('status', 'pending')
                            ->first();

                        if (! $recipient) {
                            continue;
                        }

                        $recipient->markAsFailed($e->getMessage());
                        $this->campaign->incrementFailedCount();

                        CampaignLog::logEvent(
                            $this->campaign->id,
                            'failed',
                            $recipient->id,
                            [
                                'email' => $recipient->email,
                                'delivery_server_id' => $deliveryServer->id,
                                'delivery_server_name' => $deliveryServer->name,
                                'delivery_server_from_email' => $deliveryServer->from_email,
                            ],
                            null,
                            null,
                            null,
                            $e->getMessage()
                        );
                    }
                });
            }
        }
    }

    protected function sendChunkViaEmailitBatch(DeliveryServer $deliveryServer, $recipients, int &$sentCount, int &$failedCount): void
    {
        $this->campaign->loadMissing('trackingDomain');

        $trackingBase = null;
        $hasCustomTrackingDomain = false;
        if ($this->campaign->trackingDomain && $this->campaign->trackingDomain->isVerified()) {
            $trackingBase = 'https://'.$this->campaign->trackingDomain->domain;
            $hasCustomTrackingDomain = true;
        }
        $trackingBase = $trackingBase ?: rtrim((string) config('app.url'), '/');
        $fallbackTrackingBase = rtrim((string) config('app.url'), '/');

        $subject = (string) ($this->campaign->subject ?? '');

        $htmlBody = $this->campaign->html_content ?? null;
        $textBody = $this->campaign->plain_text_content ?? null;

        // Append footer template to HTML if configured
        $footerTemplateId = $this->campaign->settings['footer_template_id'] ?? null;
        if ($footerTemplateId && is_string($htmlBody)) {
            $footerTemplate = \App\Models\Template::find($footerTemplateId);
            if ($footerTemplate && $footerTemplate->type === 'footer') {
                $footerHtml = $footerTemplate->html_content ?? '';
                if (stripos($htmlBody, '</body>') !== false) {
                    $htmlBody = str_ireplace('</body>', $footerHtml.'</body>', $htmlBody);
                } else {
                    $htmlBody .= $footerHtml;
                }
            }
        }

        // Append signature template to HTML if configured
        $signatureTemplateId = $this->campaign->settings['signature_template_id'] ?? null;
        if ($signatureTemplateId && is_string($htmlBody)) {
            $signatureTemplate = \App\Models\Template::find($signatureTemplateId);
            if ($signatureTemplate && $signatureTemplate->type === 'signature') {
                $signatureHtml = $signatureTemplate->html_content ?? '';
                if (stripos($htmlBody, '</body>') !== false) {
                    $htmlBody = str_ireplace('</body>', $signatureHtml.'</body>', $htmlBody);
                } else {
                    $htmlBody .= $signatureHtml;
                }
            }
        }

        if (! is_string($textBody) || trim($textBody) === '') {
            $textBody = is_string($htmlBody) ? strip_tags($htmlBody) : '';
        }

        $unsubscribeUrl = $trackingBase.'/unsubscribe/{{recipient_uuid}}';

        if (is_string($htmlBody) && $htmlBody !== '') {
            if ($this->campaign->track_opens) {
                $trackPixel = '<img src="'.$trackingBase.'/track/open/{{recipient_uuid}}" width="1" height="1" style="display:none;" alt="" />';

                if ($hasCustomTrackingDomain) {
                    $trackHost = parse_url($trackingBase, PHP_URL_HOST);
                    $fallbackHost = parse_url($fallbackTrackingBase, PHP_URL_HOST);
                    if (is_string($trackHost) && $trackHost !== '' && is_string($fallbackHost) && $fallbackHost !== '' && strcasecmp($trackHost, $fallbackHost) !== 0) {
                        $trackPixel .= '<img src="'.$fallbackTrackingBase.'/track/open/{{recipient_uuid}}" width="1" height="1" style="display:none;" alt="" />';
                    }
                }
                if (stripos($htmlBody, '</body>') !== false) {
                    $htmlBody = str_ireplace('</body>', $trackPixel.'</body>', $htmlBody);
                } else {
                    $htmlBody .= $trackPixel;
                }
            }

            if ($this->campaign->track_clicks) {
                $htmlBody = $this->wrapLinksWithEmailitTracking($htmlBody, $trackingBase);
            }

            $htmlBody = str_ireplace(['{{unsubscribe_url}}', '{unsubscribe_url}'], $unsubscribeUrl, $htmlBody);
            $htmlBody = (string) preg_replace('/(?<![a-zA-Z0-9_])unsubscribe_url(?![a-zA-Z0-9_])/i', $unsubscribeUrl, $htmlBody);
        }

        if (is_string($textBody)) {
            $textBody = str_ireplace(['{{unsubscribe_url}}', '{unsubscribe_url}'], $unsubscribeUrl, $textBody);
            $textBody = (string) preg_replace('/(?<![a-zA-Z0-9_])unsubscribe_url(?![a-zA-Z0-9_])/i', $unsubscribeUrl, $textBody);
        }

        $recipientByEmail = $recipients instanceof Collection ? $recipients->groupBy('email') : null;

        $to = [];
        foreach ($recipients as $recipient) {
            $toName = trim((string) (($recipient->first_name ?? '').' '.($recipient->last_name ?? '')));

            $to[] = [
                'email' => (string) $recipient->email,
                'name' => $toName,
            ];
        }

        $message = [
            'from_email' => (string) ($this->campaign->from_email ?? ''),
            'from_name' => (string) ($this->campaign->from_name ?? ''),
            'subject' => $subject,
            'htmlbody' => $htmlBody,
            'textbody' => $textBody,
            'headers' => [
                'X-Campaign-ID' => (string) $this->campaign->id,
                'X-List-ID' => (string) ($this->campaign->list_id ?? ''),
                'X-Delivery-Server-ID' => (string) ($deliveryServer->id ?? ''),
            ],
        ];

        $this->campaign->loadMissing('replyServer');
        $replyTrackingEnabled = (bool) config('mailpurse.reply_tracking.enabled', false);
        $replyDomain = trim((string) ($this->campaign->replyServer?->reply_domain ?? config('mailpurse.reply_tracking.reply_domain', '')));
        if ($replyTrackingEnabled && $replyDomain !== '') {
            $message['reply_to'] = 'reply+{{recipient_uuid}}@'.$replyDomain;
        }

        $service = app(EmailitApiService::class);
        $chunks = array_chunk($to, 500);

        foreach ($chunks as $chunk) {
            try {
                $quotaPauseDelaySeconds = $this->smoothWaitForQuota($deliveryServer, count($chunk));
                if ($quotaPauseDelaySeconds !== null) {
                    $this->pauseCampaignForDeliveryServerQuota($deliveryServer, $quotaPauseDelaySeconds);

                    return;
                }

                $service->sendBatch($deliveryServer, $message, $chunk);

                DB::transaction(function () use ($chunk, $recipientByEmail, &$sentCount) {
                    foreach ($chunk as $toItem) {
                        $email = $toItem['email'] ?? null;
                        if (! is_string($email) || $email === '') {
                            continue;
                        }

                        $recipient = null;
                        if ($recipientByEmail) {
                            $recipient = $recipientByEmail->get($email)?->first();
                        }

                        if (! $recipient) {
                            $recipient = CampaignRecipient::where('campaign_id', $this->campaign->id)
                                ->where('email', $email)
                                ->where('status', 'pending')
                                ->first();
                        }

                        if (! $recipient || $recipient->status !== 'pending') {
                            continue;
                        }

                        $recipientMeta = is_array($recipient->meta) ? $recipient->meta : [];
                        $recipientMeta['delivery_server_id'] = $this->deliveryServer?->id;
                        $recipientMeta['delivery_server_name'] = $this->deliveryServer?->name;
                        $recipientMeta['delivery_server_from_email'] = $this->deliveryServer?->from_email;

                        $recipient->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                            'meta' => $recipientMeta,
                        ]);
                        $this->campaign->incrementSentCount();

                        CampaignLog::logEvent(
                            $this->campaign->id,
                            'accepted',
                            $recipient->id,
                            [
                                'email' => $recipient->email,
                                'message' => 'Email accepted by Emailit API',
                                'delivery_server_id' => $this->deliveryServer?->id,
                                'delivery_server_name' => $this->deliveryServer?->name,
                                'delivery_server_from_email' => $this->deliveryServer?->from_email,
                            ]
                        );

                        if ($this->campaign->customer) {
                            try {
                                app(UsageService::class)->log(
                                    $this->campaign->customer,
                                    'emails_sent_this_month',
                                    1,
                                    [
                                        'campaign_id' => $this->campaign->id,
                                        'recipient_id' => $recipient->id,
                                        'delivery_server_id' => $this->deliveryServer?->id,
                                    ]
                                );
                            } catch (\Throwable $usageException) {
                                Log::warning('Failed to record campaign usage log for Emailit send; continuing send', [
                                    'campaign_id' => $this->campaign->id,
                                    'recipient_id' => $recipient->id,
                                    'error' => $usageException->getMessage(),
                                ]);
                            }
                        }

                        $sentCount++;

                        try {
                            app(AutomationTriggerService::class)->scheduleNegativeCampaignTriggersForRecipient($this->campaign, $recipient);
                        } catch (\Throwable $e) {
                            Log::warning('Failed scheduling negative campaign automation triggers', [
                                'campaign_id' => $this->campaign->id,
                                'recipient_id' => $recipient->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                $failedCount += count($chunk);
                Log::error('Emailit batch send failed: '.$e->getMessage(), [
                    'campaign_id' => $this->campaign->id,
                    'delivery_server_id' => $deliveryServer->id,
                ]);

                DB::transaction(function () use ($chunk, $e) {
                    foreach ($chunk as $toItem) {
                        $email = $toItem['email'] ?? null;
                        if (! is_string($email) || $email === '') {
                            continue;
                        }

                        $recipient = CampaignRecipient::where('campaign_id', $this->campaign->id)
                            ->where('email', $email)
                            ->where('status', 'pending')
                            ->first();

                        if (! $recipient) {
                            continue;
                        }

                        $recipientMeta = is_array($recipient->meta) ? $recipient->meta : [];
                        $recipientMeta['delivery_server_id'] = $this->deliveryServer?->id;
                        $recipientMeta['delivery_server_name'] = $this->deliveryServer?->name;
                        $recipientMeta['delivery_server_from_email'] = $this->deliveryServer?->from_email;

                        $recipient->markAsFailed($e->getMessage());
                        $recipient->update(['meta' => $recipientMeta]);
                        $this->campaign->incrementFailedCount();

                        CampaignLog::logEvent(
                            $this->campaign->id,
                            'failed',
                            $recipient->id,
                            [
                                'email' => $recipient->email,
                                'delivery_server_id' => $this->deliveryServer?->id,
                                'delivery_server_name' => $this->deliveryServer?->name,
                                'delivery_server_from_email' => $this->deliveryServer?->from_email,
                            ],
                            null,
                            null,
                            null,
                            $e->getMessage()
                        );
                    }
                });
            }
        }
    }

    protected function wrapLinksWithEmailitTracking(string $content, string $trackingBase): string
    {
        $trackingHost = parse_url($trackingBase, PHP_URL_HOST);

        return preg_replace_callback(
            '/<a\s+([^>]*\s+)?href=["\']([^"\']+)["\']([^>]*)>/i',
            function ($matches) use ($trackingBase, $trackingHost) {
                $originalUrl = $matches[2];

                if (str_contains($originalUrl, '/track/click/') || str_contains($originalUrl, '/t/click/')) {
                    return $matches[0];
                }

                if (is_string($trackingHost) && $trackingHost !== '') {
                    $host = parse_url($originalUrl, PHP_URL_HOST);
                    if (is_string($host) && $host !== '' && strcasecmp($trackingHost, $host) === 0) {
                        $path = (string) (parse_url($originalUrl, PHP_URL_PATH) ?? '');
                        if (str_starts_with($path, '/track/click/') || str_starts_with($path, '/t/click/')) {
                            return $matches[0];
                        }
                    }
                }

                $encodedUrl = rtrim(strtr(base64_encode($originalUrl), '+/', '-_'), '=');
                $trackUrl = rtrim($trackingBase, '/').'/track/click/{{recipient_uuid}}/'.$encodedUrl;
                $attributes = $matches[1].$matches[3];

                return '<a '.trim($attributes).' href="'.$trackUrl.'">';
            },
            $content
        );
    }

    protected function convertPlaceholdersToZeptoMergeTags(string $content): string
    {
        return app(PersonalizationService::class)->convertPlaceholdersToZeptoMergeTags($content);
    }

    protected function wrapLinksWithZeptoTracking(string $content, string $trackingBase): string
    {
        $trackingHost = parse_url($trackingBase, PHP_URL_HOST);

        return preg_replace_callback(
            '/<a\s+([^>]*\s+)?href=["\']([^"\']+)["\']([^>]*)>/i',
            function ($matches) use ($trackingBase, $trackingHost) {
                $originalUrl = $matches[2];

                if (str_contains($originalUrl, '/track/click/') || str_contains($originalUrl, '/t/click/')) {
                    return $matches[0];
                }

                if (is_string($trackingHost) && $trackingHost !== '') {
                    $host = parse_url($originalUrl, PHP_URL_HOST);
                    if (is_string($host) && $host !== '' && strcasecmp($trackingHost, $host) === 0) {
                        $path = (string) (parse_url($originalUrl, PHP_URL_PATH) ?? '');
                        if (str_starts_with($path, '/track/click/') || str_starts_with($path, '/t/click/')) {
                            return $matches[0];
                        }
                    }
                }

                $encodedUrl = rtrim(strtr(base64_encode($originalUrl), '+/', '-_'), '=');
                $trackUrl = rtrim($trackingBase, '/').'/track/click/{{recipient_uuid}}/'.$encodedUrl;
                $attributes = $matches[1].$matches[3];

                return '<a '.trim($attributes).' href="'.$trackUrl.'">';
            },
            $content
        );
    }

    /**
     * Check if an exception is related to rate limiting/SMTP quotas.
     */
    protected function isRateLimitError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());

        // Precise patterns only — bare words like "hour"/"limit"/"550" misclassify
        // ordinary failures and used to pause campaigns for 30+ minutes.
        $rateLimitPatterns = [
            'rate limit',
            'rate-limit',
            'too many',
            'throttl',
            'try again later',
            'temporarily deferred',
            'temporarily rejected',
            'exceeded the',
            'quota exceeded',
            'messages for',
            'already sent',
            'message rejected',
            '4.7.0',
            '4.7.1',
            '4.7.2',
            '4.7.28',
            '421 4.7',
            '451 4.7',
            '450 4.7',
            '550 5.7',
            'smtp 421',
            'smtp 450',
            'smtp 451',
        ];

        foreach ($rateLimitPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle rate limit errors by pausing the campaign and scheduling resume.
     */
    protected function handleRateLimitError(\Exception $e, ?int $resumeDelaySeconds = null): void
    {
        $errorMessage = $e->getMessage();

        Log::warning("Rate limit detected for campaign {$this->campaign->id}. Pausing campaign.", [
            'campaign_id' => $this->campaign->id,
            'error_message' => $errorMessage,
            'delivery_server_id' => $this->campaign->delivery_server_id,
        ]);

        if ($resumeDelaySeconds !== null) {
            $resumeDelaySeconds = max(1, $resumeDelaySeconds);
            $resumeAt = now()->addSeconds($resumeDelaySeconds + 10);
            $resumeDelayLabel = $resumeDelaySeconds.' seconds';
        } else {
            $resumeDelay = $this->calculateResumeDelay($errorMessage);
            $resumeAt = now()->addMinutes($resumeDelay);
            $resumeDelayLabel = $resumeDelay.' minutes';
        }

        // Pause the campaign
        $this->campaign->update([
            'status' => 'paused',
            'failure_reason' => 'Rate limit reached: '.substr($errorMessage, 0, 200),
        ]);

        Log::info("Campaign {$this->campaign->id} will resume automatically at {$resumeAt->toIso8601String()} ({$resumeDelayLabel})");

        // Schedule automatic resume
        \App\Jobs\ResumeCampaignJob::dispatch($this->campaign)
            ->delay($resumeAt)
            ->onQueue('campaigns');
    }

    /**
     * Calculate how long to wait before resuming based on the error message.
     */
    protected function calculateResumeDelay(string $errorMessage): int
    {
        $message = strtolower($errorMessage);

        // Extract time from error messages
        if (preg_match('/(\d+)\s*hour/', $message, $matches)) {
            return (int) $matches[1] * 60 + 5; // Add 5 minute buffer
        }

        if (preg_match('/(\d+)\s*minute/', $message, $matches)) {
            return (int) $matches[1] + 5; // Add 5 minute buffer
        }

        // Default delays based on common patterns
        if (str_contains($message, 'hour')) {
            return 65; // 1 hour + 5 minutes
        }

        if (str_contains($message, 'minute')) {
            return 10; // Assume 5 minutes + buffer
        }

        return max(1, app(SendingSpeedResolver::class)->rateLimitErrorPauseMinutes());
    }

    protected function campaignSendRateLimitKey(?DeliveryServer $deliveryServer): string
    {
        return 'campaign-send:'.$this->campaign->id.':delivery-server:'.($deliveryServer?->id ?? 'default');
    }

    /**
     * Get appropriate rate limit based on delivery server type and 3-layer config.
     */
    protected function getRateLimitForServer(?DeliveryServer $deliveryServer): int
    {
        return app(SendingSpeedResolver::class)->ratePerMinute($this->campaign, $deliveryServer, true);
    }

    /**
     * Check if email should be blocked due to spam score.
     */
    protected function shouldBlockDueToSpamScore(CampaignRecipient $recipient, ?DeliveryServer $deliveryServer): bool
    {
        // Check if spam scoring is enabled for this campaign/customer
        $spamScoringEnabled = $this->campaign->settings['spam_scoring_enabled'] ??
                              $this->campaign->customer?->groupSetting('spam_scoring.enabled', false);

        if (! $spamScoringEnabled) {
            return false;
        }

        try {
            $spamScoringService = app(SpamScoringService::class);

            // Get sender info for scoring
            $fromEmail = $this->campaign->from_email ??
                        $deliveryServer?->from_email ??
                        $this->campaign->emailList?->from_email ??
                        config('mail.from.address');

            $replyToEmail = $this->campaign->reply_to ?? $fromEmail;

            $senderOptions = [
                'from_email' => $fromEmail,
                'reply_to' => $replyToEmail,
                'from_name' => $this->campaign->from_name,
                'delivery_server_id' => $deliveryServer?->id,
                'delivery_server_type' => $deliveryServer?->type,
                'delivery_server_from_email' => $deliveryServer?->from_email,
            ];

            // Calculate spam score
            $spamResult = $spamScoringService->calculateSpamScore(
                $this->campaign->subject,
                $this->campaign->html_content ?? '',
                $this->campaign->plain_text_content ?? '',
                $senderOptions
            );

            // Log spam score for analytics
            Log::info('Spam score calculated for campaign email', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $recipient->id,
                'recipient_email' => $recipient->email,
                'spam_score' => $spamResult['score'],
                'assessment' => $spamResult['assessment'],
                'should_block' => $spamResult['should_block'],
            ]);

            if ($spamResult['should_block']) {
                Log::warning('Campaign email has low deliverability score but send is allowed to continue', [
                    'campaign_id' => $this->campaign->id,
                    'recipient_email' => $recipient->email,
                    'spam_score' => $spamResult['score'],
                    'assessment' => $spamResult['assessment'],
                    'issues' => $spamResult['issues'],
                    'delivery_server_id' => $deliveryServer?->id,
                    'delivery_server_name' => $deliveryServer?->name,
                ]);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to calculate spam score', [
                'campaign_id' => $this->campaign->id,
                'recipient_email' => $recipient->email,
                'error' => $e->getMessage(),
            ]);

            // Don't block on error, but log it
            return false;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendCampaignChunkJob failed for campaign {$this->campaign->id}: ".$exception->getMessage());

        // Mark recipients as failed
        CampaignRecipient::whereIn('id', $this->recipientIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);
    }
}

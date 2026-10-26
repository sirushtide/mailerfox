<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\CampaignLog;
use App\Models\CampaignRecipient;
use App\Models\DeliveryServer;
use App\Models\EmailProviderEvent;
use App\Models\ListSubscriber;
use App\Models\OutreachLead;
use App\Models\SuppressionList;
use App\Services\ComplaintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResendWebhookController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService
    ) {}

    /**
     * Handle Resend webhook events.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        $type = $payload['type'] ?? null;

        Log::info('Resend webhook received', [
            'ip' => $request->ip(),
            'type' => $type,
            'has_payload' => !empty($payload),
        ]);

        if (!is_string($type) || trim($type) === '') {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $webhookSecret = $this->resolveWebhookSecret($payload);

        if ($webhookSecret !== null && !$this->validateSignature($request, $webhookSecret)) {
            Log::warning('Invalid Resend webhook signature', [
                'ip' => $request->ip(),
                'type' => $type,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $eventType = str_replace('email.', '', $type);

        $this->logProviderEvent('resend', $eventType, $payload);

        match ($eventType) {
            'opened' => $this->handleOpen($request, $payload),
            'clicked' => $this->handleClick($request, $payload),
            'bounced' => $this->handleBounce($request, $payload),
            'delivery_delayed' => $this->handleFailed($request, $payload),
            'complained' => $this->handleComplaint($payload),
            'delivered' => $this->handleDelivered($request, $payload),
            'sent' => null,
            default => Log::info('Ignored Resend webhook event', ['type' => $type]),
        };

        return response()->json(['status' => 'processed'], 200);
    }

    protected function handleOpen(Request $request, array $payload): void
    {
        $recipient = $this->resolveRecipientFromPayload($payload);
        if ($recipient) {
            $campaign = $recipient->campaign;
            if ($campaign && $campaign->track_opens && !$recipient->isOpened()) {
                $recipient->markAsOpened();
                $campaign->incrementOpenedCount();

                CampaignLog::logEvent(
                    $campaign->id,
                    'opened',
                    $recipient->id,
                    ['email' => $recipient->email, 'provider' => 'resend'],
                    $request->ip(),
                    data_get($payload, 'data.open.user_agent')
                );
            }
            return;
        }

        $lead = $this->resolveOutreachLeadFromPayload($payload);
        if ($lead && !in_array($lead->status, ['opened', 'clicked', 'replied'])) {
            $lead->update(['status' => 'opened', 'last_activity_at' => now()]);
            Log::info('Resend outreach open processed', ['lead_id' => $lead->id, 'email' => $lead->email]);
        }
    }

    protected function handleClick(Request $request, array $payload): void
    {
        $recipient = $this->resolveRecipientFromPayload($payload);
        if ($recipient) {
            $campaign = $recipient->campaign;
            $url = data_get($payload, 'data.click.link');

            if ($campaign && $campaign->track_clicks) {
                $wasOpened = $recipient->isOpened();
                $wasClicked = $recipient->isClicked();

                if ($campaign->track_opens && !$wasOpened) {
                    $recipient->markAsOpened();
                    $campaign->incrementOpenedCount();
                }

                if (!$wasClicked) {
                    $recipient->markAsClicked();
                    $campaign->incrementClickedCount();
                }

                CampaignLog::logEvent(
                    $campaign->id,
                    'clicked',
                    $recipient->id,
                    ['email' => $recipient->email, 'provider' => 'resend'],
                    $request->ip(),
                    data_get($payload, 'data.click.user_agent'),
                    is_string($url) ? $url : null
                );
            }
            return;
        }

        $lead = $this->resolveOutreachLeadFromPayload($payload);
        if ($lead && !in_array($lead->status, ['clicked', 'replied'])) {
            $lead->update(['status' => 'clicked', 'last_activity_at' => now()]);
            Log::info('Resend outreach click processed', ['lead_id' => $lead->id, 'email' => $lead->email]);
        }
    }

    protected function handleBounce(Request $request, array $payload): void
    {
        $recipient = $this->resolveRecipientFromPayload($payload);
        if ($recipient) {
            $campaign = $recipient->campaign;
            $email = (string) ($recipient->email ?? '');

            $bounceType = data_get($payload, 'data.bounce.type', 'hard');
            $isHardBounce = in_array(strtolower((string) $bounceType), ['hard', 'permanent', 'undetermined'], true);
            $reason = (string) (data_get($payload, 'data.bounce.message') ?? 'Resend reported bounce');

            $alreadyBounced = $recipient->status === 'bounced';
            if (!$alreadyBounced) {
                $recipient->markAsBounced();
                if ($campaign) {
                    $deliveryServerMeta = $this->resolveDeliveryServerMeta($recipient, $campaign->deliveryServer);
                    $campaign->incrementBouncedCount();

                    CampaignLog::logEvent(
                        $campaign->id,
                        'bounced',
                        $recipient->id,
                        [
                            'email' => $email,
                            'provider' => 'resend',
                            'bounce_type' => $bounceType,
                            'reason' => $reason,
                        ] + $deliveryServerMeta,
                        null,
                        null,
                        null,
                        $reason
                    );
                }
            }

            $subscriber = null;
            if ($email !== '') {
                $subscriber = ListSubscriber::query()
                    ->when($campaign?->list_id, fn ($q) => $q->where('list_id', $campaign->list_id))
                    ->where('email', strtolower(trim($email)))
                    ->first();
            }

            if ($subscriber && $isHardBounce) {
                $subscriber->update([
                    'status' => 'bounced',
                    'is_bounced' => true,
                    'bounced_at' => now(),
                    'suppressed_at' => now(),
                ]);

                SuppressionList::firstOrCreate(
                    [
                        'customer_id' => $subscriber->list?->customer_id,
                        'email' => $subscriber->email,
                    ],
                    [
                        'reason' => 'bounce',
                        'reason_description' => $reason,
                        'subscriber_id' => $subscriber->id,
                        'campaign_id' => $campaign?->id,
                        'suppressed_at' => now(),
                    ]
                );
            }

            Log::info('Resend bounce processed', [
                'email' => $email,
                'bounce_type' => $bounceType,
                'reason' => $reason,
                'campaign_id' => $campaign?->id,
                'recipient_id' => $recipient->id,
            ]);
            return;
        }

        $lead = $this->resolveOutreachLeadFromPayload($payload);
        if ($lead && $lead->status !== 'bounced') {
            $lead->update(['status' => 'bounced', 'last_activity_at' => now()]);
            Log::info('Resend outreach bounce processed', ['lead_id' => $lead->id, 'email' => $lead->email]);
        }
    }

    protected function handleFailed(Request $request, array $payload): void
    {
        $recipient = $this->resolveRecipientFromPayload($payload);
        if ($recipient) {
            $campaign = $recipient->campaign;
            $email = (string) ($recipient->email ?? '');
            $reason = (string) (data_get($payload, 'data.delay.message') ?? 'Resend reported delivery delay/failure');

            $alreadyFailed = $recipient->status === 'failed';
            if (!$alreadyFailed) {
                $recipient->markAsFailed($reason);
                if ($campaign) {
                    $deliveryServerMeta = $this->resolveDeliveryServerMeta($recipient, $campaign->deliveryServer);
                    $campaign->incrementFailedCount();

                    CampaignLog::logEvent(
                        $campaign->id,
                        'failed',
                        $recipient->id,
                        [
                            'email' => $email,
                            'provider' => 'resend',
                            'reason' => $reason,
                        ] + $deliveryServerMeta,
                        null,
                        null,
                        null,
                        $reason
                    );
                }
            }
            return;
        }

        $lead = $this->resolveOutreachLeadFromPayload($payload);
        if ($lead && !in_array($lead->status, ['bounced', 'failed'])) {
            $lead->update(['status' => 'failed', 'last_activity_at' => now()]);
            Log::info('Resend outreach failed processed', ['lead_id' => $lead->id, 'email' => $lead->email]);
        }
    }

    protected function handleComplaint(array $payload): void
    {
        $email = $this->extractEmailFromPayload($payload);
        if (!$email) {
            return;
        }

        $emailId = data_get($payload, 'data.email_id');

        try {
            $this->complaintService->processComplaint(
                email: $email,
                provider: 'resend',
                providerMessageId: is_string($emailId) ? $emailId : null,
                feedbackId: null,
                rawData: json_encode($payload),
                meta: $payload
            );
        } catch (\Exception $e) {
            Log::error('Error processing Resend complaint: ' . $e->getMessage(), [
                'payload' => $payload,
            ]);
        }

        $lead = $this->resolveOutreachLeadFromPayload($payload);
        if ($lead && $lead->status !== 'unsubscribed') {
            $lead->update(['status' => 'unsubscribed', 'last_activity_at' => now()]);
            Log::info('Resend outreach complaint processed', ['lead_id' => $lead->id, 'email' => $lead->email]);
        }
    }

    protected function handleDelivered(Request $request, array $payload): void
    {
        $recipient = $this->resolveRecipientFromPayload($payload);
        if ($recipient) {
            $campaign = $recipient->campaign;
            if (!$campaign) {
                return;
            }

            $meta = is_array($recipient->meta) ? $recipient->meta : [];
            $alreadyDelivered = (bool) ($meta['resend_delivered'] ?? false);
            if ($alreadyDelivered) {
                return;
            }

            $meta['resend_delivered'] = true;
            $meta['resend_delivered_at'] = data_get($payload, 'data.created_at') ?: now()->toISOString();
            $meta['resend_email_id'] = data_get($payload, 'data.email_id');

            $recipient->update(['meta' => $meta]);

            if ((int) $campaign->delivered_count < (int) $campaign->sent_count) {
                $campaign->increment('delivered_count');
            }

            $deliveryServerMeta = $this->resolveDeliveryServerMeta($recipient, $campaign->deliveryServer);

            CampaignLog::logEvent(
                $campaign->id,
                'delivered',
                $recipient->id,
                [
                    'email' => $recipient->email,
                    'provider' => 'resend',
                    'email_id' => data_get($payload, 'data.email_id'),
                ] + $deliveryServerMeta,
                $request->ip(),
                $request->userAgent()
            );
            return;
        }

        $lead = $this->resolveOutreachLeadFromPayload($payload);
        if ($lead && in_array($lead->status, ['pending', 'sent'])) {
            $meta = is_array($lead->meta) ? $lead->meta : [];
            $meta['resend_delivered'] = true;
            $meta['resend_delivered_at'] = data_get($payload, 'data.created_at') ?: now()->toISOString();
            $lead->update(['meta' => $meta, 'last_activity_at' => now()]);
            Log::info('Resend outreach delivered processed', ['lead_id' => $lead->id, 'email' => $lead->email]);
        }
    }

    protected function resolveRecipientFromPayload(array $payload): ?CampaignRecipient
    {
        $email = $this->extractEmailFromPayload($payload);
        if (!$email) {
            return null;
        }

        // Try to find by email_id stored in recipient meta
        $emailId = data_get($payload, 'data.email_id');
        if (is_string($emailId) && trim($emailId) !== '') {
            $found = CampaignRecipient::whereJsonContains('meta->resend_email_id', $emailId)
                ->orWhereJsonContains('meta->resend_message_id', $emailId)
                ->first();
            if ($found) {
                return $found;
            }
        }

        // Fallback: match by email address (most recent sent/pending recipient)
        return CampaignRecipient::where('email', trim($email))
            ->whereIn('status', ['sent', 'pending', 'delivered', 'failed', 'bounced'])
            ->latest('id')
            ->first();
    }

    protected function extractEmailFromPayload(array $payload): ?string
    {
        $to = data_get($payload, 'data.to');
        if (is_array($to) && isset($to[0]) && is_string($to[0]) && trim($to[0]) !== '') {
            return trim($to[0]);
        }

        $to = data_get($payload, 'to');
        if (is_array($to) && isset($to[0]) && is_string($to[0]) && trim($to[0]) !== '') {
            return trim($to[0]);
        }

        return null;
    }

    protected function resolveOutreachLeadFromPayload(array $payload): ?OutreachLead
    {
        $email = $this->extractEmailFromPayload($payload);
        if (!$email) {
            return null;
        }

        $from = data_get($payload, 'data.from');
        if (!is_string($from) || trim($from) === '') {
            return null;
        }

        $domain = null;
        if (str_contains($from, '@')) {
            $domain = substr($from, strrpos($from, '@') + 1);
        }

        // Find Resend delivery servers matching the from email so we can
        // scope the lead search to the right customer.
        $serverQuery = DeliveryServer::query()
            ->where('type', 'resend')
            ->where(function ($q) use ($from, $domain) {
                $q->where('from_email', $from);
                if ($domain) {
                    $q->orWhereJsonContains('settings->sending_domain', $domain);
                }
            });

        $customerIds = $serverQuery->pluck('customer_id')->filter()->unique()->values();

        if ($customerIds->isEmpty()) {
            return null;
        }

        return OutreachLead::query()
            ->whereHas('campaign', function ($q) use ($customerIds) {
                $q->whereIn('customer_id', $customerIds);
            })
            ->where('email', trim($email))
            ->whereIn('status', ['sent', 'pending', 'opened', 'clicked'])
            ->latest('id')
            ->first();
    }

    protected function resolveDeliveryServerMeta(CampaignRecipient $recipient, ?DeliveryServer $fallbackServer = null): array
    {
        $meta = is_array($recipient->meta) ? $recipient->meta : [];

        $deliveryServerId = (int) ($meta['delivery_server_id'] ?? 0);
        $deliveryServerName = trim((string) ($meta['delivery_server_name'] ?? ''));
        $deliveryServerFromEmail = trim((string) ($meta['delivery_server_from_email'] ?? ''));

        if ($deliveryServerId <= 0 && $fallbackServer) {
            $deliveryServerId = (int) $fallbackServer->id;
            if ($deliveryServerName === '') {
                $deliveryServerName = (string) $fallbackServer->name;
            }
            if ($deliveryServerFromEmail === '') {
                $deliveryServerFromEmail = (string) $fallbackServer->from_email;
            }
        }

        return array_filter([
            'delivery_server_id' => $deliveryServerId > 0 ? $deliveryServerId : null,
            'delivery_server_name' => $deliveryServerName !== '' ? $deliveryServerName : null,
            'delivery_server_from_email' => $deliveryServerFromEmail !== '' ? $deliveryServerFromEmail : null,
        ], static fn ($value) => $value !== null);
    }

    /**
     * Resolve webhook secret from delivery server settings.
     */
    protected function resolveWebhookSecret(array $payload): ?string
    {
        $from = data_get($payload, 'data.from');
        if (!is_string($from) || trim($from) === '') {
            return null;
        }

        $email = $this->extractEmailFromPayload($payload);
        if (!$email) {
            return null;
        }

        // Try to find a resend delivery server matching the from domain
        $domain = null;
        if (str_contains($from, '@')) {
            $domain = substr($from, strrpos($from, '@') + 1);
        }

        $query = DeliveryServer::query()->where('type', 'resend');

        if ($domain) {
            $query->where(function ($q) use ($domain, $from) {
                $q->where('from_email', $from)
                    ->orWhereJsonContains('settings->sending_domain', $domain);
            });
        }

        $server = $query->first();

        if (!$server) {
            return null;
        }

        $settings = $server->settings ?? [];
        $secret = $settings['webhook_secret'] ?? null;

        return is_string($secret) && trim($secret) !== '' ? trim($secret) : null;
    }

    /**
     * Validate Svix webhook signature.
     */
    protected function validateSignature(Request $request, string $secret): bool
    {
        $svixId = $request->header('svix-id');
        $svixTimestamp = $request->header('svix-timestamp');
        $svixSignature = $request->header('svix-signature');

        if (!is_string($svixId) || !is_string($svixTimestamp) || !is_string($svixSignature)) {
            return false;
        }

        $payload = (string) $request->getContent();
        $signedContent = $svixId . '.' . $svixTimestamp . '.' . $payload;

        $key = $this->resolveSigningKey($secret);
        if ($key === null) {
            return false;
        }

        $computed = base64_encode(hash_hmac('sha256', $signedContent, $key, true));

        // svix-signature can contain multiple v1,signature pairs
        $parts = explode(',', $svixSignature);
        foreach ($parts as $part) {
            $part = trim($part);
            if (str_starts_with($part, 'v1,')) {
                $signature = substr($part, 3);
                if (hash_equals($computed, $signature)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function resolveSigningKey(string $secret): ?string
    {
        // Svix secrets may be base64-encoded or prefixed with whsec_
        if (str_starts_with($secret, 'whsec_')) {
            $decoded = base64_decode(substr($secret, 6), true);
            if ($decoded !== false) {
                return $decoded;
            }
            return substr($secret, 6);
        }

        $decoded = base64_decode($secret, true);
        if ($decoded !== false && $decoded !== '' && $decoded !== $secret) {
            return $decoded;
        }

        return $secret;
    }

    /**
     * Persist the webhook event to EmailProviderEvent for log viewing.
     */
    protected function logProviderEvent(string $provider, string $eventType, array $payload): void
    {
        try {
            $email = data_get($payload, 'data.to.0.email')
                ?? data_get($payload, 'data.email')
                ?? data_get($payload, 'email')
                ?? null;

            $occurredAt = data_get($payload, 'created_at')
                ?? data_get($payload, 'data.created_at')
                ?? now()->toIso8601String();

            $recipient = $this->resolveRecipientFromPayload($payload);

            EmailProviderEvent::create([
                'provider' => $provider,
                'event_type' => $eventType,
                'campaign_id' => $recipient?->campaign_id,
                'recipient_id' => $recipient?->id,
                'subscriber_id' => $recipient?->subscriber_id,
                'email' => $email,
                'occurred_at' => $occurredAt,
                'payload' => $payload,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log provider event', [
                'provider' => $provider,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

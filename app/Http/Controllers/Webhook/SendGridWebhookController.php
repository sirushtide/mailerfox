<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\CampaignLog;
use App\Models\CampaignRecipient;
use App\Models\ListSubscriber;
use App\Models\SuppressionList;
use App\Services\ComplaintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendGridWebhookController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService
    ) {}

    /**
     * Verify SendGrid signed Event Webhook (ECDSA over the raw body).
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
        $keyId = $request->header('X-Twilio-Email-Event-Webhook-Key-ID');

        if (!is_string($signature) || $signature === '') {
            Log::warning('SendGrid webhook rejected: missing signature header.');

            return false;
        }

        $publicKey = $this->resolvePublicKey(is_string($keyId) ? $keyId : null);

        if (!$publicKey) {
            Log::error('SendGrid webhook rejected: no public key configured or fetchable. Set SENDGRID_WEBHOOK_PUBLIC_KEY or SENDGRID_API_KEY.');

            return false;
        }

        $verified = openssl_verify(
            $request->getContent(),
            base64_decode($signature, true) ?: '',
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        if ($verified !== 1) {
            Log::warning('SendGrid webhook rejected: signature mismatch.');

            return false;
        }

        return true;
    }

    protected function resolvePublicKey(?string $keyId): ?string
    {
        $configured = (string) config('services.sendgrid.webhook_public_key');

        if ($configured !== '') {
            return $this->normalizePem($configured) ?: $configured;
        }

        $apiKey = (string) config('services.sendgrid.api_key');

        if ($apiKey === '') {
            return null;
        }

        return Cache::remember('sendgrid_webhook_public_key', now()->addDay(), function () use ($apiKey, $keyId) {
            try {
                $response = Http::withToken($apiKey)
                    ->acceptJson()
                    ->timeout(10)
                    ->get('https://api.sendgrid.com/v3/settings/webhooks/event/signed');

                $raw = $response->json('public_key');

                if (!is_string($raw) || trim($raw) === '') {
                    return null;
                }

                return $this->normalizePem($raw) ?: $raw;
            } catch (\Throwable $e) {
                Log::warning('SendGrid webhook public key fetch failed.', ['message' => $e->getMessage()]);

                return null;
            }
        });
    }

    protected function normalizePem(string $raw): ?string
    {
        $raw = trim($raw);

        if (str_contains($raw, '-----BEGIN')) {
            return $raw;
        }

        $body = wordwrap(str_replace([' ', "\n", "\r"], '', $raw), 64, "\n", true);

        if ($body === '') {
            return null;
        }

        return "-----BEGIN PUBLIC KEY-----\n{$body}\n-----END PUBLIC KEY-----";
    }

    /**
     * Handle SendGrid webhook events.
     */
    public function handle(Request $request)
    {
        if (!$this->verifySignature($request)) {
            return response()->json(['error' => 'Signature verification failed'], 401);
        }

        // SendGrid sends an array of events
        $events = $request->input();

        if (!is_array($events) || empty($events)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $processed = 0;

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $eventType = $event['event'] ?? null;

            // Handle spam report (complaint)
            if ($eventType === 'spamreport') {
                $this->handleComplaint($event);
                $processed++;
            }

            // Handle bounce (optional)
            if ($eventType === 'bounce' || $eventType === 'dropped') {
                $this->handleBounce($request, $event);
                $processed++;
            }

            if ($eventType === 'open') {
                $this->handleOpen($request, $event);
                $processed++;
            }

            if ($eventType === 'click') {
                $this->handleClick($request, $event);
                $processed++;
            }
        }

        return response()->json(['status' => 'processed', 'count' => $processed], 200);
    }

    /**
     * Handle spam complaint event.
     */
    protected function handleComplaint(array $event): void
    {
        $email = $event['email'] ?? null;
        $messageId = $event['sg_message_id'] ?? null;

        if (!$email) {
            Log::warning('SendGrid complaint event missing email', ['event' => $event]);
            return;
        }

        try {
            $this->complaintService->processComplaint(
                email: $email,
                provider: 'sendgrid',
                providerMessageId: $messageId,
                feedbackId: $event['sg_event_id'] ?? null,
                rawData: json_encode($event),
                meta: $event
            );
        } catch (\Exception $e) {
            Log::error('Error processing SendGrid complaint: ' . $e->getMessage(), [
                'event' => $event,
            ]);
        }
    }

    /**
     * Handle bounce event (optional).
     */
    protected function handleBounce(Request $request, array $event): void
    {
        $eventType = $event['event'] ?? null;
        $recipient = $this->resolveRecipientFromEvent($event);
        if (!$recipient) {
            return;
        }

        $campaign = $recipient->campaign;
        $email = (string) ($recipient->email ?? ($event['email'] ?? ''));

        $reason = (string) (
            $event['reason']
            ?? $event['response']
            ?? $event['smtp-id']
            ?? 'SendGrid reported delivery failure'
        );

        $shouldMarkAsBounce = $eventType === 'bounce';
        $isHardBounce = $shouldMarkAsBounce;

        if ($shouldMarkAsBounce) {
            $alreadyBounced = $recipient->status === 'bounced';
            $deliveryServerMeta = [];
            if (!$alreadyBounced) {
                $recipient->markAsBounced();
                if ($campaign) {
                    $deliveryServerMeta = $this->resolveDeliveryServerMeta($recipient, $campaign?->deliveryServer);

                    $campaign->incrementBouncedCount();
                }
                CampaignLog::logEvent(
                    $campaign?->id ?? 0,
                    'bounced',
                    $recipient->id,
                    [
                        'email' => $email,
                        'provider' => 'sendgrid',
                        'reason' => $reason,
                    ] + ($deliveryServerMeta ?? []),
                    $request->ip(),
                    $request->userAgent(),
                    null,
                    $reason
                );
            }
        } else {
            $alreadyFailed = $recipient->status === 'failed';
            $deliveryServerMeta = [];
            if (!$alreadyFailed) {
                $recipient->markAsFailed($reason);
                if ($campaign) {
                    $deliveryServerMeta = $this->resolveDeliveryServerMeta($recipient, $campaign?->deliveryServer);

                    $campaign->incrementFailedCount();
                }
                CampaignLog::logEvent(
                    $campaign?->id ?? 0,
                    'failed',
                    $recipient->id,
                    [
                        'email' => $email,
                        'provider' => 'sendgrid',
                        'reason' => $reason,
                    ] + ($deliveryServerMeta ?? []),
                    $request->ip(),
                    $request->userAgent(),
                    null,
                    $reason
                );
            }
        }

        $subscriber = null;
        if (is_string($email) && trim($email) !== '') {
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

        Log::info('SendGrid bounce processed', [
            'event' => $eventType,
            'email' => $email,
            'reason' => $reason,
            'campaign_id' => $campaign?->id,
            'recipient_id' => $recipient->id,
        ]);
    }

    protected function handleOpen(Request $request, array $event): void
    {
        $recipient = $this->resolveRecipientFromEvent($event);
        if (!$recipient) {
            return;
        }

        $campaign = $recipient->campaign;
        if ($campaign && $campaign->track_opens && !$recipient->isOpened()) {
            $recipient->markAsOpened();
            $campaign->incrementOpenedCount();

            CampaignLog::logEvent(
                $campaign->id,
                'opened',
                $recipient->id,
                ['email' => $recipient->email, 'provider' => 'sendgrid'],
                $request->ip(),
                $request->userAgent()
            );
        }
    }

    protected function handleClick(Request $request, array $event): void
    {
        $recipient = $this->resolveRecipientFromEvent($event);
        if (!$recipient) {
            return;
        }

        $campaign = $recipient->campaign;
        $url = $event['url'] ?? null;

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
                ['email' => $recipient->email, 'provider' => 'sendgrid'],
                $request->ip(),
                $request->userAgent(),
                is_string($url) ? $url : null
            );
        }
    }

    protected function resolveDeliveryServerMeta(CampaignRecipient $recipient, ?\App\Models\DeliveryServer $fallbackServer = null): array
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

    protected function resolveRecipientFromEvent(array $event): ?CampaignRecipient
    {
        $uuid = $event['X-Recipient-UUID'] ?? $event['x-recipient-uuid'] ?? $event['recipient_uuid'] ?? null;
        if (is_string($uuid) && trim($uuid) !== '') {
            $found = CampaignRecipient::where('uuid', trim($uuid))->first();
            if ($found) {
                return $found;
            }
        }

        $email = $event['email'] ?? null;
        if (!is_string($email) || trim($email) === '') {
            return null;
        }

        return CampaignRecipient::where('email', trim($email))
            ->latest('id')
            ->first();
    }
}

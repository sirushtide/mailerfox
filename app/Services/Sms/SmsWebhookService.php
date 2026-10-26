<?php

namespace App\Services\Sms;

use App\Models\ChannelAccount;
use App\Models\ChannelWebhookEvent;
use App\Services\Channels\ChannelMessageService;
use Illuminate\Http\Request;

class SmsWebhookService
{
    public function __construct(
        protected SmsManager $manager,
        protected ChannelMessageService $messages,
    ) {
    }

    /**
     * Handle a Twilio status callback ("status:Campaign" style events).
     */
    public function handleTwilio(Request $request): ChannelWebhookEvent
    {
        $payload = $request->all();

        $event = ChannelWebhookEvent::create([
            'channel'    => 'sms',
            'provider'   => 'twilio',
            'event_type' => $payload['SmsStatus'] ?? ($payload['MessageStatus'] ?? 'status'),
            'payload'    => $payload,
        ]);

        try {
            $sid = $payload['MessageSid'] ?? $payload['SmsSid'] ?? null;
            $status = $payload['MessageStatus'] ?? $payload['SmsStatus'] ?? null;

            if ($sid && $status) {
                $this->messages->applyStatus($sid, $this->mapStatus($status));
            }

            $event->update(['processed' => true, 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $event->update(['error' => $e->getMessage()]);
        }

        return $event;
    }

    /**
     * Handle an inbound SMS: persist contact/thread/message and fire the
     * "channel_inbound_message" action for automations and the AI assistant.
     */
    public function handleInbound(ChannelAccount $account, array $payload): void
    {
        $from = (string) ($payload['From'] ?? '');
        $body = (string) ($payload['Body'] ?? '');

        if ($from === '') {
            return;
        }

        // Basic STOP/START opt-out compliance.
        $keyword = strtoupper(trim($body));
        if (in_array($keyword, ['STOP', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'], true)) {
            $contact = $this->messages->findOrCreateContact($account->customer_id, 'sms', $from);
            $contact?->update(['consent' => false, 'unsubscribed_at' => now()]);
        } elseif (in_array($keyword, ['START', 'UNSTOP', 'YES'], true)) {
            $contact = $this->messages->findOrCreateContact($account->customer_id, 'sms', $from);
            $contact?->update(['consent' => true, 'unsubscribed_at' => null]);
        }

        $message = $this->messages->recordInbound([
            'customer_id'         => $account->customer_id,
            'channel'             => 'sms',
            'account'             => $account,
            'from'                => $from,
            'to'                  => $account->from_address,
            'body'                => $body,
            'provider_message_id' => $payload['MessageSid'] ?? $payload['SmsSid'] ?? null,
            'media'               => $this->extractMedia($payload),
            'payload'             => $payload,
        ]);

        do_action('channel_inbound_message', $message, $account);
    }

    protected function extractMedia(array $payload): array
    {
        $count = (int) ($payload['NumMedia'] ?? 0);
        $media = [];
        for ($i = 0; $i < $count; $i++) {
            if (!empty($payload['MediaUrl' . $i])) {
                $media[] = [
                    'url'  => $payload['MediaUrl' . $i],
                    'type' => $payload['MediaContentType' . $i] ?? null,
                ];
            }
        }

        return $media;
    }

    protected function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'sent'                  => 'sent',
            'delivered'             => 'delivered',
            'read'                  => 'read',
            'failed', 'undelivered' => 'failed',
            default                 => 'queued',
        };
    }
}

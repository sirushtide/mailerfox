<?php

namespace App\Services\WhatsApp;

use App\Models\ChannelAccount;
use App\Services\Channels\ChannelMessageService;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookService
{
    public function __construct(protected ChannelMessageService $messages)
    {
    }

    /**
     * @param array<string, mixed> $entry A single entry from the webhook payload.
     */
    public function handleEntry(array $entry): void
    {
        foreach (($entry['changes'] ?? []) as $change) {
            $value = $change['value'] ?? [];
            $account = $this->resolveAccount($value);

            if (!$account) {
                Log::warning('WhatsApp webhook: no matching account', ['metadata' => $value['metadata'] ?? []]);
                continue;
            }

            $this->handleContacts($account, $value['contacts'] ?? []);

            foreach (($value['messages'] ?? []) as $message) {
                $this->handleInbound($account, $message);
            }

            foreach (($value['statuses'] ?? []) as $status) {
                $this->handleStatus($status);
            }
        }
    }

    protected function resolveAccount(array $value): ?ChannelAccount
    {
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        if ($phoneNumberId) {
            $account = ChannelAccount::forChannel('whatsapp')
                ->where(function ($q) use ($phoneNumberId) {
                    $q->where('from_address', $phoneNumberId)
                        ->orWhere('external_id', $phoneNumberId);
                })
                ->first();

            if ($account) {
                return $account;
            }
        }

        $displayPhone = $value['metadata']['display_phone_number'] ?? null;
        if ($displayPhone) {
            return ChannelAccount::forChannel('whatsapp')->where('from_address', $displayPhone)->first();
        }

        return null;
    }

    protected function handleContacts(ChannelAccount $account, array $contacts): void
    {
        foreach ($contacts as $contact) {
            if (!empty($contact['wa_id'])) {
                $this->messages->findOrCreateContact(
                    $account->customer_id,
                    'whatsapp',
                    (string) $contact['wa_id'],
                    $contact['profile']['name'] ?? null
                );
            }
        }
    }

    protected function handleInbound(ChannelAccount $account, array $message): void
    {
        $from = (string) ($message['from'] ?? '');
        if ($from === '') {
            return;
        }

        $body = $this->extractBody($message);
        $keyword = strtoupper(trim($body));

        if (in_array($keyword, ['STOP', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'], true)) {
            $contact = $this->messages->findOrCreateContact($account->customer_id, 'whatsapp', $from);
            $contact?->update(['consent' => false, 'unsubscribed_at' => now()]);
        } elseif (in_array($keyword, ['START', 'UNSTOP'], true)) {
            $contact = $this->messages->findOrCreateContact($account->customer_id, 'whatsapp', $from);
            $contact?->update(['consent' => true, 'unsubscribed_at' => null]);
        }

        $recorded = $this->messages->recordInbound([
            'customer_id'         => $account->customer_id,
            'channel'             => 'whatsapp',
            'account'             => $account,
            'from'                => $from,
            'to'                  => $account->from_address,
            'body'                => $body,
            'provider_message_id' => $message['id'] ?? null,
            'media'               => $this->extractMedia($message),
            'payload'             => $message,
        ]);

        do_action('channel_inbound_message', $recorded, $account);
    }

    protected function handleStatus(array $status): void
    {
        $id = $status['id'] ?? null;
        $state = $status['status'] ?? null;

        if (!$id || !$state) {
            return;
        }

        $mapped = match ($state) {
            'sent'      => 'sent',
            'delivered' => 'delivered',
            'read'      => 'read',
            'failed'    => 'failed',
            default     => 'queued',
        };

        $this->messages->applyStatus($id, $mapped);
    }

    protected function extractBody(array $message): string
    {
        $type = $message['type'] ?? '';

        return match ($type) {
            'text'     => (string) ($message['text']['body'] ?? ''),
            'button'   => (string) ($message['button']['text'] ?? ''),
            'interactive' => (string) ($message['interactive']['button_reply']['title']
                ?? $message['interactive']['list_reply']['title'] ?? ''),
            'image', 'video', 'document', 'audio' => (string) ($message[$type]['caption'] ?? ''),
            default    => '',
        };
    }

    protected function extractMedia(array $message): array
    {
        $type = $message['type'] ?? '';
        if (in_array($type, ['image', 'video', 'document', 'audio', 'sticker'], true)) {
            return [[
                'type' => $type,
                'id'   => $message[$type]['id'] ?? null,
                'mime' => $message[$type]['mime_type'] ?? null,
            ]];
        }

        return [];
    }
}

<?php

namespace App\Services\Channels;

use App\Models\ChannelAccount;
use App\Models\ChannelContact;
use App\Models\ChannelMessage;
use App\Models\ChannelThread;
use App\Models\ChannelUsageLog;

/**
 * Persists channel traffic (contacts, threads, messages) and usage in a
 * provider-agnostic way so every channel shares one history.
 */
class ChannelMessageService
{
    public function findOrCreateContact(?int $customerId, string $channel, string $address, ?string $name = null): ?ChannelContact
    {
        if ($customerId === null || trim($address) === '') {
            return null;
        }

        return ChannelContact::firstOrCreate(
            [
                'customer_id' => $customerId,
                'channel'     => $channel,
                'address'     => $address,
            ],
            [
                'name'          => $name,
                'consent'       => true,
                'subscribed_at' => now(),
            ]
        );
    }

    public function findOrCreateThread(
        int $customerId,
        string $channel,
        ?int $accountId,
        ?int $contactId,
        ?string $subject = null
    ): ChannelThread {
        if ($contactId !== null) {
            $thread = ChannelThread::where('customer_id', $customerId)
                ->where('channel', $channel)
                ->where('contact_id', $contactId)
                ->whereIn('status', ['open', 'snoozed'])
                ->latest('last_message_at')
                ->first();

            if ($thread) {
                return $thread;
            }
        }

        return ChannelThread::create([
            'customer_id'     => $customerId,
            'channel'         => $channel,
            'account_id'      => $accountId,
            'contact_id'      => $contactId,
            'subject'         => $subject,
            'status'          => 'open',
            'last_message_at' => now(),
        ]);
    }

    public function recordOutbound(OutboundMessage $message, ChannelResult $result): ChannelMessage
    {
        $contact = $this->findOrCreateContact($message->customerId, $message->channel, $message->to);
        $thread = $message->threadId
            ? ChannelThread::find($message->threadId)
            : null;

        if (!$thread && $message->customerId !== null) {
            $thread = $this->findOrCreateThread(
                $message->customerId,
                $message->channel,
                $message->account?->id,
                $contact?->id,
                $message->subject
            );
        }

        return ChannelMessage::create([
            'customer_id'         => $message->customerId,
            'thread_id'           => $thread?->id,
            'account_id'          => $message->account?->id,
            'contact_id'          => $contact?->id,
            'channel'             => $message->channel,
            'direction'           => 'out',
            'provider_message_id' => $result->providerMessageId,
            'from_address'        => $message->from ?? $message->account?->from_address,
            'to_address'          => $message->to,
            'body'                => $message->body,
            'media'               => $message->media ?: null,
            'payload'             => $message->meta ?: null,
            'status'              => $result->success ? ($result->status ?: 'sent') : 'failed',
            'error'               => $result->error,
            'sent_at'             => $result->success ? now() : null,
        ]);
    }

    /**
     * @param array{
     *   customer_id?: ?int, channel: string, account?: ?ChannelAccount,
     *   from: string, to?: ?string, body?: ?string, provider_message_id?: ?string,
     *   media?: array, payload?: array, contact_name?: ?string
     * } $data
     */
    public function recordInbound(array $data): ChannelMessage
    {
        $channel = $data['channel'];
        $customerId = $data['customer_id'] ?? null;
        $account = $data['account'] ?? null;

        $contact = $this->findOrCreateContact(
            $customerId,
            $channel,
            $data['from'],
            $data['contact_name'] ?? null
        );

        $thread = null;
        if ($customerId !== null) {
            $thread = $this->findOrCreateThread(
                $customerId,
                $channel,
                $account?->id,
                $contact?->id
            );
            $thread->increment('unread_count');
            $thread->forceFill(['last_message_at' => now()])->save();
        }

        return ChannelMessage::create([
            'customer_id'         => $customerId,
            'thread_id'           => $thread?->id,
            'account_id'          => $account?->id,
            'contact_id'          => $contact?->id,
            'channel'             => $channel,
            'direction'           => 'in',
            'provider_message_id' => $data['provider_message_id'] ?? null,
            'from_address'        => $data['from'],
            'to_address'          => $data['to'] ?? null,
            'body'                => $data['body'] ?? null,
            'media'               => $data['media'] ?? null,
            'payload'             => $data['payload'] ?? null,
            'status'              => 'received',
            'sent_at'             => now(),
        ]);
    }

    public function applyStatus(string $providerMessageId, string $status): void
    {
        $message = ChannelMessage::where('provider_message_id', $providerMessageId)->latest()->first();
        if (!$message) {
            return;
        }

        $attributes = ['status' => $status];
        if ($status === 'delivered') {
            $attributes['delivered_at'] = now();
        } elseif ($status === 'read') {
            $attributes['read_at'] = now();
        }

        $message->update($attributes);
    }

    public function logUsage(?int $customerId, string $channel, ?string $provider, int $units = 1, array $meta = []): void
    {
        if ($customerId === null) {
            return;
        }

        ChannelUsageLog::create([
            'customer_id' => $customerId,
            'channel'     => $channel,
            'provider'    => $provider,
            'units'       => $units,
            'meta'        => $meta ?: null,
        ]);
    }
}

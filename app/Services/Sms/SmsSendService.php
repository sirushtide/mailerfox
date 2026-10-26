<?php

namespace App\Services\Sms;

use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Services\Channels\ChannelMessageService;
use App\Services\Channels\OutboundMessage;

class SmsSendService
{
    public function __construct(
        protected SmsManager $manager,
        protected ChannelMessageService $messages,
    ) {
    }

    /**
     * Send an SMS/MMS and persist it to the unified channel history.
     *
     * @param array<int, string> $media
     * @param array<string, mixed> $context
     */
    public function send(ChannelAccount $account, string $to, string $body, array $media = [], array $context = []): ChannelMessage
    {
        $customerId = $context['customer_id'] ?? $account->customer_id;
        $to = $this->normalizeNumber($to);

        $outbound = new OutboundMessage(
            channel: 'sms',
            account: $account,
            to: $to,
            body: $body,
            from: $account->from_address,
            media: $media,
            meta: $context,
            customerId: $customerId,
            threadId: $context['thread_id'] ?? null,
            campaignId: $context['campaign_id'] ?? null,
        );

        $result = $this->manager->resolve($account)->send($account, $to, $body, $media);

        $message = $this->messages->recordOutbound($outbound, $result);

        if ($customerId !== null) {
            $this->messages->logUsage($customerId, 'sms', $account->provider, $this->segments($body), [
                'message_id' => $message->id,
                'account_id' => $account->id,
            ]);
        }

        return $message;
    }

    public function normalizeNumber(string $number): string
    {
        $number = trim($number);

        return preg_replace('/(?!^\+)[^\d]/', '', $number) ?? $number;
    }

    public function segments(string $body): int
    {
        $length = mb_strlen($body);
        if ($length <= 160) {
            return 1;
        }

        return max(1, (int) ceil($length / 153));
    }
}

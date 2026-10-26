<?php

namespace App\Services\Channels;

use App\Models\ChannelAccount;

/**
 * Provider-agnostic outbound message payload.
 */
class OutboundMessage
{
    public function __construct(
        public string $channel,
        public ?ChannelAccount $account,
        public string $to,
        public ?string $body = null,
        public ?string $subject = null,
        public ?string $from = null,
        public array $media = [],
        public array $meta = [],
        public ?int $customerId = null,
        public ?int $contactId = null,
        public ?int $threadId = null,
        public ?int $campaignId = null,
        public ?string $templateName = null,
        public array $templateParams = [],
    ) {
    }

    public static function make(string $channel, ?ChannelAccount $account, string $to, ?string $body = null): self
    {
        return new self(channel: $channel, account: $account, to: $to, body: $body);
    }

    public function withCustomer(?int $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }
}

<?php

namespace App\Services\Sms\Contracts;

use App\Models\ChannelAccount;
use App\Services\Channels\ChannelResult;

interface SmsProvider
{
    /**
     * Provider key (twilio, vonage, ...).
     */
    public function key(): string;

    /**
     * Send an SMS/MMS through the given account.
     *
     * @param array<int, string> $media
     */
    public function send(ChannelAccount $account, string $to, string $body, array $media = []): ChannelResult;

    /**
     * Validate a webhook request signature.
     */
    public function verifyWebhook(ChannelAccount $account, string $url, array $params, ?string $signature): bool;
}

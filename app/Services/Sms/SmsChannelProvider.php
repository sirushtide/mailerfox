<?php

namespace App\Services\Sms;

use App\Models\ChannelAccount;
use App\Services\Channels\ChannelResult;
use App\Services\Channels\Contracts\ChannelProvider;
use App\Services\Channels\OutboundMessage;
use InvalidArgumentException;

/**
 * Registered with ChannelManager under the "sms" channel by the SMS addon.
 */
class SmsChannelProvider implements ChannelProvider
{
    public function __construct(protected SmsManager $manager)
    {
    }

    public function channel(): string
    {
        return 'sms';
    }

    public function send(OutboundMessage $message): ChannelResult
    {
        $account = $message->account;
        if (!$account instanceof ChannelAccount) {
            throw new InvalidArgumentException('SMS send requires a channel account.');
        }

        return $this->manager->resolve($account)->send(
            $account,
            $message->to,
            (string) ($message->body ?? ''),
            $message->media
        );
    }
}

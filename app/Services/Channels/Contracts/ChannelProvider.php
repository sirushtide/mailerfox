<?php

namespace App\Services\Channels\Contracts;

use App\Services\Channels\ChannelResult;
use App\Services\Channels\OutboundMessage;

interface ChannelProvider
{
    /**
     * The channel identifier this provider handles (sms, whatsapp, email).
     */
    public function channel(): string;

    /**
     * Send an outbound message.
     */
    public function send(OutboundMessage $message): ChannelResult;
}

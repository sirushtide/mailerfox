<?php

namespace App\Services\Channels\Providers;

use App\Services\Channels\ChannelResult;
use App\Services\Channels\Contracts\ChannelProvider;
use App\Services\Channels\OutboundMessage;
use Illuminate\Support\Facades\Mail;

/**
 * Thin wrapper that lets the unified channel pipeline fall back to email.
 * Rich email behaviour continues to live in the existing campaign pipeline.
 */
class EmailChannelProvider implements ChannelProvider
{
    public function channel(): string
    {
        return 'email';
    }

    public function send(OutboundMessage $message): ChannelResult
    {
        try {
            Mail::raw($message->body ?? '', function ($mail) use ($message) {
                $mail->to($message->to);
                if ($message->from) {
                    $mail->from($message->from);
                }
                if ($message->subject) {
                    $mail->subject($message->subject);
                }
            });

            return ChannelResult::ok(null, 'sent');
        } catch (\Throwable $e) {
            return ChannelResult::fail($e->getMessage());
        }
    }
}

<?php

namespace App\Services\Channels;

use App\Services\Channels\Contracts\ChannelProvider;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Resolves and dispatches to the right channel provider.
 *
 * Addons register additional providers through the "channel_providers" filter:
 *   add_filter('channel_providers', function ($providers) {
 *       $providers['sms'] = \App\Services\Sms\SmsChannelProvider::class;
 *       return $providers;
 *   });
 */
class ChannelManager
{
    public function __construct(protected Container $container)
    {
    }

    /**
     * @return array<string, class-string<ChannelProvider>>
     */
    public function providers(): array
    {
        $providers = apply_filters('channel_providers', [
            'email' => Providers\EmailChannelProvider::class,
        ]);

        return is_array($providers) ? $providers : [];
    }

    public function has(string $channel): bool
    {
        return isset($this->providers()[$channel]);
    }

    public function driver(string $channel): ChannelProvider
    {
        $providers = $this->providers();

        if (!isset($providers[$channel])) {
            throw new InvalidArgumentException("No channel provider registered for [{$channel}].");
        }

        return $this->container->make($providers[$channel]);
    }

    public function send(OutboundMessage $message): ChannelResult
    {
        return $this->driver($message->channel)->send($message);
    }
}

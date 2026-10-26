<?php

namespace App\Services\AI;

use InvalidArgumentException;

class AiProviderFactory
{
    /**
     * Resolve a provider slug into an AiProviderInterface instance.
     */
    public static function make(string $provider): AiProviderInterface
    {
        $provider = strtolower(trim($provider));
        $config = AiProviderRegistry::config($provider);

        if ($config === null) {
            throw new InvalidArgumentException("Unknown AI provider: {$provider}");
        }

        $clientClass = $config['client'];

        return app($clientClass);
    }

    /**
     * Get the admin Setting key for a provider's API key.
     */
    public static function settingKey(string $provider): string
    {
        return AiProviderRegistry::settingKey($provider);
    }

    /**
     * Get the Customer model field name for a provider's own API key.
     */
    public static function customerField(string $provider): string
    {
        return AiProviderRegistry::customerField($provider);
    }
}

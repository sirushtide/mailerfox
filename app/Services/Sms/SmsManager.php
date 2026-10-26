<?php

namespace App\Services\Sms;

use App\Models\ChannelAccount;
use App\Services\Sms\Contracts\SmsProvider;
use InvalidArgumentException;

class SmsManager
{
    /**
     * @return array<string, class-string<SmsProvider>>
     */
    public function providers(): array
    {
        $providers = apply_filters('sms_providers', [
            'twilio' => TwilioSmsProvider::class,
        ]);

        return is_array($providers) ? $providers : [];
    }

    public function resolve(ChannelAccount $account): SmsProvider
    {
        $key = strtolower(trim((string) $account->provider));
        $providers = $this->providers();

        if (!isset($providers[$key])) {
            throw new InvalidArgumentException("Unsupported SMS provider [{$key}].");
        }

        return app($providers[$key]);
    }
}

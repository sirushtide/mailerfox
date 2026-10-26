<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class EnrichmentKeyResolver
{
    public function resolve(string $provider): ?string
    {
        $customer = auth('customer')->user();
        $group = $customer?->groups?->first();

        // 1. Customer BYOK
        if ($group && data_get($group->settings, 'enrichment.allow_byok', true)) {
            $byok = $customer->{"{$provider}_api_key"} ?? null;
            if (filled($byok)) {
                return $byok;
            }
        }

        // 2. Group-level key
        if ($group) {
            $groupKey = data_get($group->settings, "enrichment.{$provider}_key");
            if (filled($groupKey)) {
                return $groupKey;
            }
        }

        // 3. Global fallback
        return Setting::get("superscrape_{$provider}_key") ?: null;
    }
}

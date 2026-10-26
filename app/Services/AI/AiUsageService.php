<?php

namespace App\Services\AI;

use App\Models\AiGeneration;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\AiBuilder\ModelRouter;
use Illuminate\Support\Facades\DB;

class AiUsageService
{
    public function estimateTokens(string $prompt, int $maxOutputTokens = 1200): int
    {
        $prompt = trim($prompt);

        $inputEstimate = max(1, (int) ceil(strlen($prompt) / 4));

        return $inputEstimate + max(1, $maxOutputTokens);
    }

    public function getAdminCostPer1mCentsForModel(string $provider, ?string $model): int
    {
        $input = $this->getAdminInputCostPer1mCentsForModel($provider, $model);
        $output = $this->getAdminOutputCostPer1mCentsForModel($provider, $model);
        if ($input > 0 || $output > 0) {
            return max($input, $output);
        }
        return $this->getAdminCostPer1mCents($provider);
    }

    public function getAdminInputCostPer1mCentsForModel(string $provider, ?string $model): int
    {
        return $this->getAdminSplitCostPer1mCentsForModel($provider, $model, 'input');
    }

    public function getAdminOutputCostPer1mCentsForModel(string $provider, ?string $model): int
    {
        return $this->getAdminSplitCostPer1mCentsForModel($provider, $model, 'output');
    }

    public function getAdminInputCostPer1mCents(string $provider): int
    {
        return $this->getAdminSplitCostPer1mCents(strtolower(trim($provider)), 'input');
    }

    public function getAdminOutputCostPer1mCents(string $provider): int
    {
        return $this->getAdminSplitCostPer1mCents(strtolower(trim($provider)), 'output');
    }

    private function getAdminSplitCostPer1mCentsForModel(string $provider, ?string $model, string $type): int
    {
        $provider = strtolower(trim($provider));
        $model = is_string($model) && trim($model) !== '' ? trim($model) : null;

        if ($model !== null) {
            $splitSpecificKey = $this->splitCostPer1mModelKey($provider, $model, $type);
            $splitSpecific = (int) Setting::get($splitSpecificKey, 0);
            if ($splitSpecific > 0) {
                return $splitSpecific;
            }
        }

        if ($model !== null) {
            $slug = $this->modelKeySlug($model);

            foreach ($this->providerSettingPrefixes($provider) as $prefix) {
                $splitKey = $prefix . '_' . $type . '_cost_per_1m_cents_' . $slug;
                $splitValue = (int) Setting::get($splitKey, 0);
                if ($splitValue > 0) {
                    return $splitValue;
                }

                $splitOldKey = $prefix . '_' . $type . '_cost_per_1k_cents_' . $slug;
                $splitOldValue = (int) Setting::get($splitOldKey, 0);
                if ($splitOldValue > 0) {
                    return $splitOldValue * 1000;
                }

                $newKey = $prefix . '_cost_per_1m_cents_' . $slug;
                $specific = (int) Setting::get($newKey, 0);
                if ($specific > 0) {
                    return $specific;
                }

                $oldKey = $prefix . '_cost_per_1k_cents_' . $slug;
                $oldSpecific = (int) Setting::get($oldKey, 0);
                if ($oldSpecific > 0) {
                    return $oldSpecific * 1000;
                }
            }

            $providerDefault = $this->getAdminSplitCostPer1mCents($provider, $type);
            if ($providerDefault > 0) {
                return $providerDefault;
            }

            $rates = ModelRouter::COST_PER_1M_TOKENS_CENTS[$model] ?? null;
            if ($rates !== null && isset($rates[$type])) {
                return (int) $rates[$type];
            }

            return 0;
        }

        return $this->getAdminSplitCostPer1mCents($provider, $type);
    }

    public function getAdminCostPer1mCents(string $provider): int
    {
        $provider = strtolower(trim($provider));

        $input = $this->getAdminSplitCostPer1mCents($provider, 'input');
        $output = $this->getAdminSplitCostPer1mCents($provider, 'output');

        if ($input > 0 || $output > 0) {
            return max($input, $output);
        }

        foreach ($this->providerSettingPrefixes($provider) as $prefix) {
            $newKey = $prefix . '_cost_per_1m_cents';
            $val = (int) Setting::get($newKey, 0);
            if ($val > 0) {
                return $val;
            }

            $oldKey = $prefix . '_cost_per_1k_cents';
            $oldVal = (int) Setting::get($oldKey, 0);
            if ($oldVal > 0) {
                return $oldVal * 1000;
            }
        }

        return 0;
    }

    public function getAdminMonthlyLimit(string $provider): int
    {
        $provider = strtolower(trim($provider));

        foreach ($this->providerSettingPrefixes($provider) as $prefix) {
            $value = (int) Setting::get($prefix . '_token_limit_monthly', 0);
            if ($value > 0) {
                return $value;
            }
        }

        return 0;
    }

    public function getAdminDailyLimit(string $provider): int
    {
        $provider = strtolower(trim($provider));

        foreach ($this->providerSettingPrefixes($provider) as $prefix) {
            $value = (int) Setting::get($prefix . '_token_limit_daily', 0);
            if ($value > 0) {
                return $value;
            }
        }

        return 0;
    }

    public function getAdminCostPer1kCentsForModel(string $provider, ?string $model): int
    {
        $provider = strtolower(trim($provider));
        $model = is_string($model) && trim($model) !== '' ? trim($model) : null;

        if ($model === null) {
            return $this->getAdminCostPer1kCents($provider);
        }

        $prefix = $this->costPer1kPrefix($provider);
        if (!$prefix) {
            return 0;
        }

        $key = $prefix . $this->modelKeySlug($model);
        $specific = (int) Setting::get($key, 0);

        if ($specific > 0) {
            return $specific;
        }

        return $this->getAdminCostPer1kCents($provider);
    }

    public function getAdminCostPer1kCents(string $provider): int
    {
        $provider = strtolower(trim($provider));

        $key = $this->costPer1kKey($provider);
        if (!$key) {
            return 0;
        }

        return (int) Setting::get($key, 0);
    }

    public function getAdminTokensUsedThisMonth(string $provider): int
    {
        $provider = strtolower(trim($provider));

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return (int) AiGeneration::query()
            ->where('used_admin_keys', true)
            ->where('success', true)
            ->where('provider', $provider)
            ->whereBetween('created_at', [$start, $end])
            ->sum(DB::raw('COALESCE(tokens_used, 0)'));
    }

    public function getAdminTokensUsedToday(string $provider): int
    {
        $provider = strtolower(trim($provider));

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        return (int) AiGeneration::query()
            ->where('used_admin_keys', true)
            ->where('success', true)
            ->where('provider', $provider)
            ->whereBetween('created_at', [$start, $end])
            ->sum(DB::raw('COALESCE(tokens_used, 0)'));
    }

    public function canUseAdminTokens(string $provider, int $estimatedTokens): bool
    {
        $estimatedTokens = max(1, (int) $estimatedTokens);

        $dailyLimit = $this->getAdminDailyLimit($provider);
        if ($dailyLimit > 0) {
            $usedToday = $this->getAdminTokensUsedToday($provider);
            if (($usedToday + $estimatedTokens) > $dailyLimit) {
                return false;
            }
        }

        $monthlyLimit = $this->getAdminMonthlyLimit($provider);
        if ($monthlyLimit > 0) {
            $usedMonth = $this->getAdminTokensUsedThisMonth($provider);
            if (($usedMonth + $estimatedTokens) > $monthlyLimit) {
                return false;
            }
        }

        return true;
    }

    public function canUseCustomerOwnKeyTokens(Customer $customer, int $estimatedTokens): bool
    {
        $estimatedTokens = max(1, (int) $estimatedTokens);

        return (bool) DB::transaction(function () use ($customer, $estimatedTokens) {
            $locked = Customer::query()->whereKey($customer->id)->lockForUpdate()->first();
            if (!$locked) {
                return false;
            }

            $this->resetOwnKeyCountersIfNeeded($locked);

            $dailyLimit = (int) ($locked->ai_own_daily_limit ?? 0);
            $monthlyLimit = (int) ($locked->ai_own_monthly_limit ?? 0);

            $dailyUsage = (int) ($locked->ai_own_daily_usage ?? 0);
            $monthlyUsage = (int) ($locked->ai_own_monthly_usage ?? 0);

            if ($dailyLimit > 0 && ($dailyUsage + $estimatedTokens) > $dailyLimit) {
                return false;
            }

            if ($monthlyLimit > 0 && ($monthlyUsage + $estimatedTokens) > $monthlyLimit) {
                return false;
            }

            return true;
        });
    }

    public function consumeCustomerOwnKeyTokens(Customer $customer, int $tokens): bool
    {
        $tokens = max(1, (int) $tokens);

        return (bool) DB::transaction(function () use ($customer, $tokens) {
            $locked = Customer::query()->whereKey($customer->id)->lockForUpdate()->first();
            if (!$locked) {
                return false;
            }

            $this->resetOwnKeyCountersIfNeeded($locked);

            $locked->ai_own_daily_usage = (int) ($locked->ai_own_daily_usage ?? 0) + $tokens;
            $locked->ai_own_monthly_usage = (int) ($locked->ai_own_monthly_usage ?? 0) + $tokens;
            $locked->save();

            return true;
        });
    }

    private function resetOwnKeyCountersIfNeeded(Customer $customer): void
    {
        $today = now()->toDateString();
        $month = now()->format('Y-m');

        $storedDay = $customer->ai_own_daily_usage_date ? $customer->ai_own_daily_usage_date->toDateString() : null;
        if ($storedDay !== $today) {
            $customer->ai_own_daily_usage = 0;
            $customer->ai_own_daily_usage_date = $today;
        }

        $storedMonth = is_string($customer->ai_own_monthly_usage_month ?? null) ? (string) $customer->ai_own_monthly_usage_month : null;
        if ($storedMonth !== $month) {
            $customer->ai_own_monthly_usage = 0;
            $customer->ai_own_monthly_usage_month = $month;
        }

        if ($customer->isDirty()) {
            $customer->save();
        }
    }

    private function modelKeySlug(string $model): string
    {
        $model = strtolower(trim($model));
        $model = preg_replace('/[^a-z0-9]+/', '_', $model) ?? $model;
        $model = trim($model, '_');
        return $model;
    }

    private function providerSettingPrefix(string $provider): ?string
    {
        return match ($provider) {
            'chatgpt' => 'openai',
            'claude' => 'anthropic',
            'gemini' => 'gemini',
            'deepseek' => 'deepseek',
            'kimi' => 'kimi',
            'glm' => 'glm',
            'grok' => 'grok',
            'openrouter' => 'openrouter',
            default => null,
        };
    }

    private function providerSettingPrefixes(string $provider): array
    {
        return match ($provider) {
            'claude' => ['anthropic', 'claude'],
            default => array_values(array_filter([$this->providerSettingPrefix($provider)])),
        };
    }

    private function getAdminSplitCostPer1mCents(string $provider, string $type): int
    {
        $provider = strtolower(trim($provider));

        foreach ($this->providerSettingPrefixes($provider) as $prefix) {
            $splitKey = 'ai_provider_' . $type . '_cost_per_1m_' . $provider;
            $splitValue = (int) Setting::get($splitKey, 0);
            if ($splitValue > 0) {
                return $splitValue;
            }

            $newKey = $prefix . '_cost_per_1m_cents';
            $newValue = (int) Setting::get($newKey, 0);
            if ($newValue > 0) {
                return $newValue;
            }

            $oldKey = $prefix . '_cost_per_1k_cents';
            $oldValue = (int) Setting::get($oldKey, 0);
            if ($oldValue > 0) {
                return $oldValue * 1000;
            }
        }

        return 0;
    }

    private function splitCostPer1mModelKey(string $provider, string $model, string $type): string
    {
        return 'ai_model_' . $type . '_cost_per_1m_' . $provider . '_' . $this->modelKeySlug($model);
    }

    private function costPer1mPrefix(string $provider): ?string
    {
        $prefix = $this->providerSettingPrefix($provider);
        return $prefix ? $prefix . '_cost_per_1m_cents_' : null;
    }

    private function costPer1mKey(string $provider): ?string
    {
        $prefix = $this->providerSettingPrefix($provider);
        return $prefix ? $prefix . '_cost_per_1m_cents' : null;
    }

    private function costPer1kPrefix(string $provider): ?string
    {
        $prefix = $this->providerSettingPrefix($provider);
        return $prefix ? $prefix . '_cost_per_1k_cents_' : null;
    }

    private function costPer1kKey(string $provider): ?string
    {
        $prefix = $this->providerSettingPrefix($provider);
        return $prefix ? $prefix . '_cost_per_1k_cents' : null;
    }

    private function monthlyLimitKey(string $provider): ?string
    {
        $prefix = $this->providerSettingPrefix($provider);
        return $prefix ? $prefix . '_token_limit_monthly' : null;
    }

    private function dailyLimitKey(string $provider): ?string
    {
        $prefix = $this->providerSettingPrefix($provider);
        return $prefix ? $prefix . '_token_limit_daily' : null;
    }
}

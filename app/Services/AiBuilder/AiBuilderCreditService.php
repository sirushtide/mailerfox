<?php

namespace App\Services\AiBuilder;

use App\Models\Customer;
use App\Services\AI\AiUsageService;

class AiBuilderCreditService
{
    public function __construct(
        private AiUsageService $usageEstimator,
        private AiBuilderAccessService $accessService,
    ) {
    }

    public function settings(Customer $customer): array
    {
        return $this->accessService->settings($customer);
    }

    public function creditSystem(Customer $customer): string
    {
        $settings = $this->settings($customer);
        $value = data_get($settings, 'credit_system');

        return in_array($value, ['token_based', 'generation_based'], true) ? $value : 'token_based';
    }

    public function periodStart(): string
    {
        return now()->startOfMonth()->toDateTimeString();
    }

    public function nextRenewalAt(): \Carbon\Carbon
    {
        return now()->startOfMonth()->addMonth();
    }

    public function dailyResetAt(): \Carbon\Carbon
    {
        return now('UTC')->startOfDay()->addDay();
    }

    public function dailyLimit(Customer $customer): float
    {
        return (float) data_get($this->settings($customer), 'daily_limit', 0);
    }

    public function resetCreditsIfNeeded(Customer $customer): void
    {
        $settings = $this->settings($customer);
        $stored = $customer->ai_builder_credits ?? [];
        $periodStart = $this->periodStart();

        $tokensPerCredit = max(1, (float) data_get($settings, 'token_based.tokens_per_credit', 1000));
        $totalTokenCredits = (float) data_get($settings, 'token_based.total_credits', 0);
        $totalGenCredits = (float) data_get($settings, 'generation_based.total_credits', 0);

        $shouldResetMonthly = ($stored['period_start'] ?? null) !== $periodStart;
        $shouldResetDaily = $this->shouldResetDaily($stored['daily'] ?? []);

        // Detect plan total mismatch: if the plan's total_credits changed after initialisation,
        // the stored remaining values may be stale (e.g. 0 when plan was 0 and later set to 100).
        $expectedTokenTotal = $totalTokenCredits > 0 ? (float) ($totalTokenCredits * $tokensPerCredit) : 0.0;
        $expectedGenTotal = $totalGenCredits > 0 ? (float) $totalGenCredits : 0.0;
        $storedPlanTokenTotal = (float) data_get($stored, 'plan_totals.token_based', -1);
        $storedPlanGenTotal = (float) data_get($stored, 'plan_totals.generation_based', -1);
        $planTotalMismatch = (
            $storedPlanTokenTotal !== $expectedTokenTotal
            || $storedPlanGenTotal !== $expectedGenTotal
        );

        if (! $shouldResetMonthly && ! $shouldResetDaily && ! $planTotalMismatch) {
            return;
        }

        if ($shouldResetMonthly || $planTotalMismatch) {
            $usedTokens = (float) data_get($stored, 'token_based.used_tokens', 0);
            $usedCredits = (float) data_get($stored, 'generation_based.used_credits', 0);
            $existingDaily = $stored['daily'] ?? [];
            $stored = [
                'period_start' => $periodStart,
                'plan_totals' => [
                    'token_based' => $expectedTokenTotal,
                    'generation_based' => $expectedGenTotal,
                ],
                'token_based' => [
                    'remaining_tokens' => max(0, $expectedTokenTotal - $usedTokens),
                    'used_tokens' => $usedTokens,
                ],
                'generation_based' => [
                    'remaining_credits' => max(0, $expectedGenTotal - $usedCredits),
                    'used_credits' => $usedCredits,
                ],
                'daily' => $existingDaily,
            ];
        }

        if ($shouldResetDaily) {
            $dailyLimit = (float) data_get($settings, 'daily_limit', 0);
            $stored['daily'] = [
                'used' => 0,
                'limit' => $dailyLimit,
                'reset_at' => $this->dailyResetAt()->toDateTimeString(),
            ];
        }

        $customer->ai_builder_credits = $stored;
        $customer->save();
    }

    private function shouldResetDaily(array $daily): bool
    {
        if (empty($daily) || ! isset($daily['reset_at'])) {
            return true;
        }

        $resetAt = data_get($daily, 'reset_at');
        try {
            $resetAtCarbon = \Carbon\Carbon::parse($resetAt, 'UTC');
        } catch (\Throwable $e) {
            return true;
        }

        return now('UTC')->greaterThanOrEqualTo($resetAtCarbon);
    }

    public function status(Customer $customer): array
    {
        $this->resetCreditsIfNeeded($customer);
        $settings = $this->settings($customer);
        $system = $this->creditSystem($customer);
        $credits = $customer->ai_builder_credits ?? [];
        $dailyLimit = (float) data_get($settings, 'daily_limit', 0);
        $dailyUsed = (float) data_get($credits, 'daily.used', 0);

        $dailyPayload = [
            'daily_limit' => $dailyLimit,
            'daily_used' => $dailyUsed,
            'daily_remaining' => $dailyLimit > 0 ? max(0, $dailyLimit - $dailyUsed) : 0,
            'daily_renews_at' => data_get($credits, 'daily.reset_at'),
            'monthly_renews_at' => $this->nextRenewalAt()->toDateTimeString(),
        ];

        if ($system === 'token_based') {
            $total = (float) data_get($settings, 'token_based.total_credits', 0);
            $tokensPerCredit = max(1, (float) data_get($settings, 'token_based.tokens_per_credit', 1000));
            $remainingTokens = (float) data_get($credits, 'token_based.remaining_tokens', 0);
            $usedTokens = (float) data_get($credits, 'token_based.used_tokens', 0);

            return array_merge($dailyPayload, [
                'system' => 'token_based',
                'total_credits' => $total,
                'tokens_per_credit' => $tokensPerCredit,
                'remaining_credits' => $total > 0 ? floor($remainingTokens / $tokensPerCredit) : 0,
                'remaining_tokens' => $remainingTokens,
                'used_credits' => $total > 0 ? floor($usedTokens / $tokensPerCredit) : 0,
                'used_tokens' => $usedTokens,
            ]);
        }

        $total = (float) data_get($settings, 'generation_based.total_credits', 0);
        $usedCredits = (float) data_get($credits, 'generation_based.used_credits', 0);

        return array_merge($dailyPayload, [
            'system' => 'generation_based',
            'total_credits' => $total,
            'remaining_credits' => (float) data_get($credits, 'generation_based.remaining_credits', 0),
            'used_credits' => $usedCredits,
        ]);
    }

    private function operationCreditCost(Customer $customer, string $operation, int $tokens = 0): float
    {
        $settings = $this->settings($customer);
        $system = $this->creditSystem($customer);

        if ($system === 'token_based') {
            $tokensPerCredit = max(1, (float) data_get($settings, 'token_based.tokens_per_credit', 1000));

            if ($operation === 'generate') {
                return $tokens > 0 ? $tokens / $tokensPerCredit : 0;
            }

            $creditsCost = (float) data_get($settings, 'token_based.credits_per_iteration', 0);
            if ($operation === 'selection_iteration') {
                $creditsCost = (float) data_get($settings, 'token_based.credits_per_selection_iteration', 0);
            } elseif ($operation === 'discuss') {
                $creditsCost = (float) data_get($settings, 'token_based.credits_per_discussion', 0);
            }

            return $creditsCost;
        }

        return match ($operation) {
            'generate' => (float) data_get($settings, 'generation_based.credits_per_generation', 0),
            'selection_iteration' => (float) data_get($settings, 'generation_based.credits_per_iteration', 0),
            'discuss' => (float) data_get($settings, 'generation_based.credits_per_discussion', 0),
            default => (float) data_get($settings, 'generation_based.credits_per_iteration', 0),
        };
    }

    public function canAfford(Customer $customer, string $operation, int $estimatedTokens = 0): bool
    {
        $this->resetCreditsIfNeeded($customer);
        $settings = $this->settings($customer);
        $system = $this->creditSystem($customer);
        $credits = $customer->ai_builder_credits ?? [];
        $creditCost = $this->operationCreditCost($customer, $operation, $estimatedTokens);
        $dailyLimit = (float) data_get($settings, 'daily_limit', 0);
        $dailyUsed = (float) data_get($credits, 'daily.used', 0);

        if ($dailyLimit > 0 && $dailyUsed + $creditCost > $dailyLimit) {
            return false;
        }

        if ($system === 'token_based') {
            $totalCredits = (float) data_get($settings, 'token_based.total_credits', 0);
            if ($totalCredits <= 0) {
                return true;
            }
            $tokensPerCredit = max(1, (float) data_get($settings, 'token_based.tokens_per_credit', 1000));
            $remainingTokens = (float) data_get($credits, 'token_based.remaining_tokens', 0);

            if ($operation === 'generate') {
                $estimate = max(1, $estimatedTokens);
                return $remainingTokens >= $estimate;
            }

            return $remainingTokens >= $creditCost * $tokensPerCredit;
        }

        $totalCredits = (float) data_get($settings, 'generation_based.total_credits', 0);
        if ($totalCredits <= 0) {
            return true;
        }
        $remaining = (float) data_get($credits, 'generation_based.remaining_credits', 0);

        return $remaining >= $creditCost;
    }

    public function deduct(Customer $customer, string $operation, int $tokens = 0): bool
    {
        $this->resetCreditsIfNeeded($customer);
        $settings = $this->settings($customer);
        $system = $this->creditSystem($customer);
        $credits = $customer->ai_builder_credits ?? [];
        $creditCost = $this->operationCreditCost($customer, $operation, $tokens);
        $dailyLimit = (float) data_get($settings, 'daily_limit', 0);
        $dailyUsed = (float) data_get($credits, 'daily.used', 0);

        if ($system === 'token_based') {
            $totalCredits = (float) data_get($settings, 'token_based.total_credits', 0);
            $tokensPerCredit = max(1, (float) data_get($settings, 'token_based.tokens_per_credit', 1000));
            $remainingTokens = (float) data_get($credits, 'token_based.remaining_tokens', 0);
            $usedTokens = (float) data_get($credits, 'token_based.used_tokens', 0);

            if ($operation === 'generate') {
                $tokenCost = max(0, $tokens);
            } else {
                $tokenCost = $creditCost * $tokensPerCredit;
            }

            $credits['token_based']['remaining_tokens'] = max(0, $remainingTokens - $tokenCost);
            $credits['token_based']['used_tokens'] = $usedTokens + $tokenCost;
        } else {
            $totalCredits = (float) data_get($settings, 'generation_based.total_credits', 0);
            $remaining = (float) data_get($credits, 'generation_based.remaining_credits', 0);
            $used = (float) data_get($credits, 'generation_based.used_credits', 0);

            $credits['generation_based']['remaining_credits'] = max(0, $remaining - $creditCost);
            $credits['generation_based']['used_credits'] = $used + $creditCost;
        }

        if ($dailyLimit > 0) {
            $credits['daily']['used'] = $dailyUsed + $creditCost;
        }

        $customer->ai_builder_credits = $credits;
        $customer->save();

        return true;
    }

    public function estimateGenerationTokens(string $prompt, int $maxOutputTokens = 1200): int
    {
        return $this->usageEstimator->estimateTokens($prompt, $maxOutputTokens);
    }
}

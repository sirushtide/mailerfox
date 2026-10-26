<?php

namespace App\Services\AI;

use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class AiTemplateService
{
    public function __construct(
        protected AiUsageService $aiUsage,
        protected ?AiProviderRouter $router = null,
    ) {
        $this->router = $router ?? app(AiProviderRouter::class);
    }

    public function generate(Customer $customer, string $provider, string $prompt, string $builder = 'grapesjs', ?string $model = null): array
    {
        $provider = strtolower(trim($provider));
        $builder = strtolower(trim($builder));
        $prompt = trim($prompt);
        $model = is_string($model) && trim($model) !== '' ? trim($model) : null;

        if (!in_array($builder, ['grapesjs', 'unlayer'], true)) {
            return [
                'success' => false,
                'message' => 'Invalid builder.',
                'status' => 422,
            ];
        }

        if (!AiProviderRegistry::exists($provider)) {
            return [
                'success' => false,
                'message' => 'Invalid provider.',
                'status' => 422,
            ];
        }

        $mustUseOwnKeys = (bool) $customer->groupSetting('ai.must_use_own_keys', false);

        $tokenLimit = (int) $customer->groupSetting('ai.token_limit', 0);

        $customerField = AiProviderRegistry::customerField($provider);
        $settingKey = AiProviderRegistry::settingKey($provider);

        $ownApiKey = is_string($customer->{$customerField} ?? null) ? trim((string) $customer->{$customerField}) : '';
        $adminApiKey = is_string(Setting::get($settingKey)) ? trim((string) Setting::get($settingKey)) : '';
        $usingAdminKeys = false;

        if ($ownApiKey !== '') {
            $apiKey = $ownApiKey;
            $usingAdminKeys = false;
        } else {
            $apiKey = $adminApiKey;
            $usingAdminKeys = true;
        }

        if (!is_string($apiKey) || trim($apiKey) === '') {
            return [
                'success' => false,
                'message' => $mustUseOwnKeys && $ownApiKey === ''
                    ? 'You must configure your API key before using AI Creator.'
                    : 'Admin AI API key is not configured yet.',
                'status' => 422,
            ];
        }

        $inputEstimate = max(1, (int) ceil(strlen($prompt) / 4));
        $maxOutputTokens = 1200;

        if ($usingAdminKeys) {
            $budget = null;

            $dailyLimit = $this->aiUsage->getAdminDailyLimit($provider);
            if ($dailyLimit > 0) {
                $usedToday = $this->aiUsage->getAdminTokensUsedToday($provider);
                $budget = $dailyLimit - $usedToday;
            }

            $monthlyLimit = $this->aiUsage->getAdminMonthlyLimit($provider);
            if ($monthlyLimit > 0) {
                $usedMonth = $this->aiUsage->getAdminTokensUsedThisMonth($provider);
                $monthlyBudget = $monthlyLimit - $usedMonth;
                $budget = $budget === null ? $monthlyBudget : min($budget, $monthlyBudget);
            }

            $current = (int) ($customer->ai_token_usage ?? 0);
            if ($tokenLimit > 0) {
                $customerBudget = $tokenLimit - $current;
                $budget = $budget === null ? $customerBudget : min($budget, $customerBudget);
            }

            if ($budget !== null) {
                $budget = (int) $budget;
                if ($budget <= 0) {
                    return [
                        'success' => false,
                        'message' => $tokenLimit > 0 && ($tokenLimit - $current) <= 0
                            ? 'AI token limit reached for your plan. Please add your own API key or upgrade your plan.'
                            : 'Admin AI monthly token limit reached. Please try again later or increase the limit in settings.',
                        'status' => 429,
                    ];
                }

                $availableForOutput = $budget - $inputEstimate;
                if ($availableForOutput <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Your prompt is too long for the remaining token budget. Please shorten the prompt or increase your plan limits.',
                        'status' => 422,
                    ];
                }

                $minOutputBudget = 50;
                if ($provider === 'chatgpt' && is_string($model) && stripos($model, 'gpt-5') === 0) {
                    $minOutputBudget = 250;
                }

                if ($availableForOutput < $minOutputBudget) {
                    return [
                        'success' => false,
                        'message' => 'Not enough token budget remaining to generate a useful response. Please shorten the prompt, increase your plan limits, or use gpt-4.1.',
                        'status' => 422,
                    ];
                }

                $maxCap = 1200;
                if ($provider === 'chatgpt' && is_string($model) && stripos($model, 'gpt-5') === 0) {
                    $maxCap = 2400;
                }

                $maxOutputTokens = min($maxCap, max(1, $availableForOutput));
            }

            $estimatedTokens = $inputEstimate + max(1, (int) $maxOutputTokens);

            if (!$this->aiUsage->canUseAdminTokens($provider, $estimatedTokens)) {
                return [
                    'success' => false,
                    'message' => 'Admin AI monthly token limit reached. Please try again later or increase the limit in settings.',
                    'status' => 429,
                ];
            }
        } else {
            $estimatedTokens = $inputEstimate + max(1, (int) $maxOutputTokens);
            if (!$this->aiUsage->canUseCustomerOwnKeyTokens($customer, $estimatedTokens)) {
                return [
                    'success' => false,
                    'message' => 'Your daily or monthly AI usage limit has been reached. Increase your limits in settings or try again later.',
                    'status' => 429,
                ];
            }
        }

        $result = null;
        $html = '';
        $plainText = '';
        $tokens = null;
        $builderData = null;

        $effectiveModel = $model ?? AiProviderRegistry::defaultModel($provider);

        if ($builder === 'unlayer') {
            $result = $this->router->generateTemplateUnlayerDesign($effectiveModel, $prompt, $maxOutputTokens, $customer);

            if (!is_array($result) || !($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => is_array($result) && is_string($result['message'] ?? null)
                        ? $result['message']
                        : 'Failed to generate template.',
                    'status' => 502,
                ];
            }

            $builderData = is_array($result['design'] ?? null) ? $result['design'] : null;
            if (!is_array($builderData)) {
                return [
                    'success' => false,
                    'message' => 'AI returned an invalid Unlayer design.',
                    'status' => 502,
                ];
            }

            $designJson = json_encode($builderData);
            if (is_string($designJson) && stripos($designJson, 'data:image/') !== false) {
                return [
                    'success' => false,
                    'message' => 'Generated content contains inline base64 images (data:image/...). Please try again and ask for image URLs instead.',
                    'status' => 422,
                ];
            }

            $tokens = is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : null;
            if ($tokens === null) {
                $tokens = max(1, (int) ceil(strlen($prompt) / 4));
            }

            // HTML/plain-text will be produced server-side for previewing.
            $html = '';
            $plainText = '';
        } else {
            $result = $this->router->generateTemplateHtml($effectiveModel, $prompt, $maxOutputTokens, $customer);

            if (!is_array($result) || !($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => is_array($result) && is_string($result['message'] ?? null)
                        ? $result['message']
                        : 'Failed to generate template.',
                    'status' => 502,
                ];
            }

            $html = is_string($result['html'] ?? null) ? trim($result['html']) : '';
            if ($html === '') {
                return [
                    'success' => false,
                    'message' => 'AI returned an empty response.',
                    'status' => 502,
                ];
            }

            if (stripos($html, 'data:image/') !== false) {
                return [
                    'success' => false,
                    'message' => 'Generated content contains inline base64 images (data:image/...). Please try again and ask for image URLs instead.',
                    'status' => 422,
                ];
            }

            $plainText = $this->htmlToPlainText($html);

            $tokens = null;
            if (is_numeric($result['tokens'] ?? null)) {
                $tokens = (int) $result['tokens'];
            }
            if ($tokens === null) {
                $tokens = max(1, (int) ceil(strlen($prompt) / 4));
            }
        }

        if ($usingAdminKeys) {
            $ok = DB::transaction(function () use ($customer, $tokens) {
                $locked = Customer::query()->whereKey($customer->id)->lockForUpdate()->first();
                if (!$locked) {
                    return false;
                }

                $locked->ai_token_usage = (int) ($locked->ai_token_usage ?? 0) + $tokens;
                $locked->save();

                return true;
            });

            if (!$ok) {
                return [
                    'success' => false,
                    'message' => 'Failed to record AI token usage. Please try again.',
                    'status' => 500,
                ];
            }
        } else {
            $ok = $this->aiUsage->consumeCustomerOwnKeyTokens($customer, $tokens);
            if (!$ok) {
                return [
                    'success' => false,
                    'message' => 'Your daily or monthly AI usage limit has been reached. Increase your limits in settings or try again later.',
                    'status' => 429,
                ];
            }
        }

        if ($builder === 'grapesjs') {
            $builderData = [
                'components' => $html,
                'styles' => [],
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'data' => [
                'name' => 'AI Generated',
                'description' => null,
                'html_content' => $html,
                'plain_text_content' => $plainText,
                'builder' => $builder,
                'builder_data' => $builderData,
                'tokens_used' => $tokens,
                'used_admin_keys' => $usingAdminKeys,
            ],
        ];
    }

    private function htmlToPlainText(string $html): string
    {
        $plain = $html;
        $plain = preg_replace('/<style[\s\S]*?<\/style>/i', ' ', $plain) ?? $plain;
        $plain = preg_replace('/<script[\s\S]*?<\/script>/i', ' ', $plain) ?? $plain;
        $plain = preg_replace('/<[^>]+>/', ' ', $plain) ?? $plain;
        $plain = preg_replace('/\s+/', ' ', $plain) ?? $plain;
        return trim($plain);
    }

    private function buildUnlayerDesignFromHtml(string $html): array
    {
        $html = trim($html);

        return [
            'counters' => [],
            'body' => [
                'id' => 'body_ai_generated',
                'rows' => [
                    [
                        'id' => 'row_ai_generated',
                        'cells' => [1],
                        'columns' => [
                            [
                                'id' => 'col_ai_generated',
                                'contents' => [
                                    [
                                        'id' => 'content_ai_generated',
                                        'type' => 'text',
                                        'values' => [
                                            'text' => $html,
                                            'textAlign' => 'left',
                                            'fontSize' => '14px',
                                            'containerPadding' => '10px',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

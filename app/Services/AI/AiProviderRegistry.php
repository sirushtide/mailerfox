<?php

namespace App\Services\AI;

use App\Models\AiCustomModel;
use App\Models\Setting;

class AiProviderRegistry
{
    /**
     * Central registry of all supported AI providers.
     *
     * @return array<string, array{
     *     label: string,
     *     client: class-string<AiProviderInterface>,
     *     default_model: string,
     *     setting_key: string,
     *     customer_field: string,
     *     models: string[],
     * }>
     */
    public static function providers(): array
    {
        return [
            'chatgpt' => [
                'label' => 'ChatGPT (OpenAI)',
                'client' => OpenAIClient::class,
                'default_model' => 'gpt-5-mini',
                'setting_key' => 'openai_api_key',
                'customer_field' => 'openai_api_key',
                'models' => [
                    'gpt-5.6-sol',
                    'gpt-5.6-tera',
                    'gpt-5.6-luna',
                    'gpt-5.6-sol-pro',
                    'gpt-5.6-tera-pro',
                    'gpt-5.6-luna-pro',
                    'gpt-5.5',
                    'gpt-5.5-pro',
                    'gpt-5.4',
                    'gpt-5.4-pro',
                    'gpt-5.4-mini',
                    'gpt-5.4-nano',
                    'gpt-5.2',
                    'gpt-5.2-pro',
                    'gpt-5.1',
                    'gpt-5',
                    'gpt-5-pro',
                    'gpt-5-mini',
                    'gpt-5-nano',
                    'gpt-4.1',
                    'gpt-4.1-mini',
                    'gpt-4.1-nano',
                    'gpt-4o',
                    'gpt-4o-mini',
                    'o4-mini',
                    'o3',
                    'o3-mini',
                    'o3-pro',
                    'gpt-4-32k',
                    'gpt-3.5-turbo',
                ],
            ],
            'claude' => [
                'label' => 'Claude (Anthropic)',
                'client' => AnthropicClient::class,
                'default_model' => 'claude-sonnet-5',
                'setting_key' => 'anthropic_api_key',
                'customer_field' => 'anthropic_api_key',
                'models' => [
                    'claude-fable-5',
                    'claude-mythos-5',
                    'claude-opus-4-8',
                    'claude-opus-4-7',
                    'claude-opus-4-6',
                    'claude-opus-4-5',
                    'claude-opus-4-1',
                    'claude-opus-4',
                    'claude-sonnet-5',
                    'claude-sonnet-5-20260901',
                    'claude-sonnet-4-6',
                    'claude-sonnet-4-5',
                    'claude-sonnet-4',
                    'claude-haiku-4-5',
                    'claude-haiku-3-5',
                ],
            ],
            'gemini' => [
                'label' => 'Gemini (Google)',
                'client' => GeminiClient::class,
                'default_model' => 'gemini-2.0-flash',
                'setting_key' => 'gemini_api_key',
                'customer_field' => 'gemini_api_key',
                'models' => [
                    'gemini-3.5-flash',
                    'gemini-3.1-flash-lite',
                    'gemini-3.1-pro',
                    'gemini-3.1-flash',
                    'gemini-2.5-pro',
                    'gemini-2.5-flash',
                    'gemini-2.0-flash',
                    'gemini-1.5-flash',
                    'gemini-1.5-pro',
                    'gemini-1.0-pro',
                ],
            ],
            'deepseek' => [
                'label' => 'DeepSeek',
                'client' => DeepSeekClient::class,
                'default_model' => 'deepseek-chat',
                'setting_key' => 'deepseek_api_key',
                'customer_field' => 'deepseek_api_key',
                'models' => [
                    'deepseek-v4-flash',
                    'deepseek-v4-pro',
                    'deepseek-chat',
                    'deepseek-reasoner',
                ],
            ],
            'kimi' => [
                'label' => 'Kimi (Moonshot)',
                'client' => MoonshotClient::class,
                'default_model' => 'kimi-k2.6',
                'setting_key' => 'kimi_api_key',
                'customer_field' => 'kimi_api_key',
                'models' => [
                    'kimi-k3',
                    'kimi-k2.6',
                    'kimi-k2.7-code',
                ],
            ],
            'glm' => [
                'label' => 'GLM (Zhipu)',
                'client' => ZhipuClient::class,
                'default_model' => 'glm-5.2',
                'setting_key' => 'glm_api_key',
                'customer_field' => 'glm_api_key',
                'models' => [
                    'glm-5.2',
                    'glm-5.1',
                    'glm-5',
                    'glm-4.7-flash',
                    'glm-4.7',
                    'glm-4.6',
                ],
            ],
            'grok' => [
                'label' => 'Grok (xAI)',
                'client' => XaiClient::class,
                'default_model' => 'grok-4.3',
                'setting_key' => 'grok_api_key',
                'customer_field' => 'grok_api_key',
                'models' => [
                    'grok-4.5',
                    'grok-4.3',
                    'grok-4.2',
                ],
            ],
            'qwen' => [
                'label' => 'Qwen (Alibaba)',
                'client' => QwenClient::class,
                'default_model' => 'qwen/qwen3.7-max',
                'setting_key' => 'qwen_api_key',
                'customer_field' => 'qwen_api_key',
                'models' => [
                    'qwen/qwen3.7-max',
                    'qwen/qwen3.7-plus',
                    'qwen/qwen3.6-plus',
                ],
            ],
            'minimax' => [
                'label' => 'MiniMax',
                'client' => MiniMaxClient::class,
                'default_model' => 'minimax/minimax-m3',
                'setting_key' => 'minimax_api_key',
                'customer_field' => 'minimax_api_key',
                'models' => [
                    'minimax/minimax-m3',
                    'minimax/minimax-m2.7',
                ],
            ],
            'xiaomi' => [
                'label' => 'Xiaomi AI',
                'client' => XiaomiClient::class,
                'default_model' => 'xiaomi/mimo-v2.5',
                'setting_key' => 'xiaomi_api_key',
                'customer_field' => 'xiaomi_api_key',
                'models' => [
                    'xiaomi/mimo-v2.5',
                    'xiaomi/mimo-v2.5-pro',
                ],
            ],
            'openrouter' => [
                'label' => 'OpenRouter',
                'client' => OpenRouterClient::class,
                'default_model' => 'openai/gpt-4o-mini',
                'setting_key' => 'openrouter_api_key',
                'customer_field' => 'openrouter_api_key',
                'models' => [
                    // OpenAI (19)
                    'openai/gpt-5.6-sol',
                    'openai/gpt-5.6-terra',
                    'openai/gpt-5.6-luna',
                    'openai/gpt-5.6-sol-pro',
                    'openai/gpt-5.6-terra-pro',
                    'openai/gpt-5.6-luna-pro',
                    'openai/gpt-5.4-mini',
                    'openai/gpt-5.4-nano',
                    'openai/gpt-5.2',
                    'openai/gpt-4o',
                    'openai/gpt-4o-mini',
                    'openai/gpt-4.1',
                    'openai/gpt-4.1-mini',
                    'openai/gpt-4.1-nano',
                    'openai/o3-mini',
                    'openai/o4-mini',
                    'openai/gpt-4-turbo',
                    'openai/gpt-3.5-turbo',
                    'openai/chatgpt-4o-latest',
                    // Anthropic (9)
                    'anthropic/claude-fable-5',
                    'anthropic/claude-opus-5',
                    'anthropic/claude-sonnet-5',
                    'anthropic/claude-opus-4.8-fast',
                    'anthropic/claude-opus-4.8',
                    'anthropic/claude-opus-4.7',
                    'anthropic/claude-opus-4.6',
                    'anthropic/claude-opus-4.5',
                    'anthropic/claude-haiku-4.5',
                    // Google Gemini (4)
                    'google/gemini-3.6-flash',
                    'google/gemini-3.5-flash-lite',
                    'google/gemini-3.5-flash',
                    'google/gemini-3.1-flash-lite',
                    // Meta Llama (4)
                    'meta-llama/llama-3.3-70b-instruct',
                    'meta-llama/llama-3.1-405b-instruct',
                    'meta-llama/llama-3.1-70b-instruct',
                    'meta-llama/llama-3.1-8b-instruct',
                    // Mistral (4)
                    'mistralai/mistral-large',
                    'mistralai/codestral-2508',
                    'mistralai/mistral-medium-3',
                    'mistralai/mistral-small-3.2-24b-instruct',
                    // Qwen (3)
                    'qwen/qwen3.7-max',
                    'qwen/qwen3.7-plus',
                    'qwen/qwen3.6-plus',
                    // DeepSeek (3)
                    'deepseek/deepseek-v4-flash',
                    'deepseek/deepseek-v4-pro',
                    'deepseek/deepseek-v3.2',
                    // Kimi / Moonshot (3)
                    'moonshotai/kimi-k3',
                    'moonshotai/kimi-k2.7-code',
                    'moonshotai/kimi-k2.6',
                    // Grok (2)
                    'x-ai/grok-4.5',
                    'x-ai/grok-4.3',
                    // GLM / Zhipu (2)
                    'z-ai/glm-5.2',
                    'z-ai/glm-5.1',
                     // MiniMax (2)
                     'minimax/minimax-m3',
                     'minimax/minimax-m2.7',
                    // Poolside (1)
                    'poolside/laguna-s-2.1',
                    // InclusionAI (2)
                    'inclusionai/ling-3.0-flash',
                    'inclusionai/ling-2.6-flash',
                    // Tencent (1)
                    'tencent/hy3',
                     // Mimo (2)
                     'xiaomi/mimo-v2.5',
                     'xiaomi/mimo-v2.5-pro',
                ],
                'admin_only_models' => [
                    // Router modes
                    'openrouter/auto-beta',
                    'openrouter/free',
                    // Free models
                    'poolside/laguna-s-2.1:free',
                    'inclusionai/ling-3.0-flash:free',
                    'nvidia/nemotron-3-ultra-550b-a55b:free',
                    'meta-llama/llama-3.1-8b-instruct:free',
                    'mistralai/mistral-7b-instruct:free',
                    'google/gemma-2-9b-it:free',
                    'microsoft/phi-3-mini-128k-instruct:free',
                ],
            ],
        ];
    }

    public static function detectProvider(string $model): string
    {
        $model = strtolower(trim($model));
        if ($model === '') {
            return 'chatgpt';
        }

        $matches = [];
        foreach (self::providers() as $key => $config) {
            $providerModels = self::allModels($key);
            foreach ($providerModels as $providerModel) {
                if (strtolower(trim($providerModel)) === $model) {
                    $matches[] = $key;
                    break;
                }
            }
        }

        if (count($matches) <= 1) {
            if (isset($matches[0])) {
                return $matches[0];
            }

            // OpenRouter can expose models before they are added to the local
            // catalog. Free/hosted IDs should not silently fall back to ChatGPT.
            if (str_contains($model, 'free') || str_contains($model, '/')) {
                return 'openrouter';
            }

            return 'chatgpt';
        }

        // Some model strings are hosted by multiple providers (e.g. xiaomi/mimo-v2.5
        // is listed under both the xiaomi and openrouter providers). Prefer the first
        // match that is enabled and has an API key configured so a saved selection
        // routes to the provider the admin actually set up instead of reverting.
        foreach ($matches as $key) {
            $settingKey = self::providers()[$key]['setting_key'] ?? null;
            if (! is_string($settingKey) || $settingKey === '') {
                continue;
            }

            $enabled = filter_var(Setting::get('ai_provider_enabled_' . $key, 1), FILTER_VALIDATE_BOOLEAN);
            $apiKey = Setting::get($settingKey);
            if ($enabled && is_string($apiKey) && trim($apiKey) !== '') {
                return $key;
            }
        }

        return $matches[0];
    }

    public static function providerKeys(): array
    {
        return array_keys(self::providers());
    }

    public static function providerLabels(): array
    {
        $labels = [];
        foreach (self::providers() as $key => $config) {
            $labels[$key] = $config['label'];
        }
        return $labels;
    }

    public static function exists(string $provider): bool
    {
        return array_key_exists(strtolower(trim($provider)), self::providers());
    }

    public static function config(string $provider): ?array
    {
        $provider = strtolower(trim($provider));
        return self::providers()[$provider] ?? null;
    }

    public static function defaultModel(string $provider): string
    {
        $config = self::config($provider);
        return $config['default_model'] ?? '';
    }

    public static function models(string $provider): array
    {
        $config = self::config($provider);
        $models = $config['models'] ?? [];

        if ($provider === 'openrouter') {
            $models = array_merge($models, AiCustomModel::where('provider', $provider)->pluck('model_id')->all());
        }

        return array_values(array_unique($models));
    }

    public static function adminOnlyModels(string $provider): array
    {
        $config = self::config($provider);
        return $config['admin_only_models'] ?? [];
    }

    public static function allModels(string $provider): array
    {
        $config = self::config($provider);
        $models = array_merge(
            $config['models'] ?? [],
            $config['admin_only_models'] ?? [],
        );

        // Models added from a provider's catalog are admin-configurable models.
        if ($provider === 'openrouter') {
            $models = array_merge($models, AiCustomModel::where('provider', $provider)->pluck('model_id')->all());
        }

        return array_values(array_unique($models));
    }

    public static function settingKey(string $provider): string
    {
        $config = self::config($provider);
        return $config['setting_key'] ?? '';
    }

    public static function customerField(string $provider): string
    {
        $config = self::config($provider);
        return $config['customer_field'] ?? '';
    }

    public static function label(string $provider): string
    {
        $config = self::config($provider);
        return $config['label'] ?? ucfirst($provider);
    }
}

<?php

namespace App\Services\AiBuilder;

use App\Models\Setting;
use App\Services\AI\AiProviderRegistry;

class ModelRouter
{
    public const DEFAULT_MODELS = [
        'enhancer' => 'gpt-5.4-mini',
        'orchestrator' => 'gpt-5.4-mini',
        'worker' => 'gpt-5.4-mini',
        'iteration' => 'gpt-5.4-mini',
    ];

    /**
     * Hardcoded per-1M-token pricing (cents) for the 24 supported OpenAI models.
     * Prices are taken from the short-context columns provided by the user.
     */
    public const COST_PER_1M_TOKENS_CENTS = [
        // OpenAI direct models (USD per 1M tokens)
        'gpt-5.6-sol' => ['input' => 500, 'output' => 3000],
        'gpt-5.6-tera' => ['input' => 100, 'output' => 600],
        'gpt-5.6-luna' => ['input' => 10, 'output' => 60],
        'gpt-5.6-sol-pro' => ['input' => 500, 'output' => 3000],
        'gpt-5.6-tera-pro' => ['input' => 100, 'output' => 600],
        'gpt-5.6-luna-pro' => ['input' => 10, 'output' => 60],
        'gpt-5.5' => ['input' => 500, 'output' => 3000],
        'gpt-5.5-pro' => ['input' => 3000, 'output' => 18000],
        'gpt-5.4' => ['input' => 250, 'output' => 1500],
        'gpt-5.4-pro' => ['input' => 3000, 'output' => 18000],
        'gpt-5.4-mini' => ['input' => 75, 'output' => 450],
        'gpt-5.4-nano' => ['input' => 20, 'output' => 125],
        'gpt-5.2' => ['input' => 175, 'output' => 1400],
        'gpt-5.2-pro' => ['input' => 2100, 'output' => 16800],
        'gpt-5.1' => ['input' => 125, 'output' => 1000],
        'gpt-5' => ['input' => 125, 'output' => 1000],
        'gpt-5-pro' => ['input' => 1500, 'output' => 12000],
        'gpt-5-mini' => ['input' => 25, 'output' => 200],
        'gpt-5-nano' => ['input' => 5, 'output' => 40],
        'gpt-4.1' => ['input' => 200, 'output' => 800],
        'gpt-4.1-mini' => ['input' => 40, 'output' => 160],
        'gpt-4.1-nano' => ['input' => 10, 'output' => 40],
        'gpt-4o' => ['input' => 250, 'output' => 1000],
        'gpt-4o-mini' => ['input' => 15, 'output' => 60],
        'o4-mini' => ['input' => 110, 'output' => 440],
        'o3' => ['input' => 200, 'output' => 800],
        'o3-mini' => ['input' => 110, 'output' => 440],
        'o3-pro' => ['input' => 2000, 'output' => 8000],
        'gpt-4-32k' => ['input' => 6000, 'output' => 12000],
        'gpt-3.5-turbo' => ['input' => 50, 'output' => 150],
        // Google direct models
        'gemini-3.5-flash' => ['input' => 150, 'output' => 900],
        'gemini-3.1-flash-lite' => ['input' => 25, 'output' => 150],
        'gemini-3.1-pro' => ['input' => 35, 'output' => 105],
        'gemini-3.1-flash' => ['input' => 8, 'output' => 30],
        'gemini-2.5-pro' => ['input' => 125, 'output' => 1000],
        'gemini-2.5-flash' => ['input' => 15, 'output' => 60],
        'gemini-2.0-flash' => ['input' => 10, 'output' => 40],
        'gemini-1.5-flash' => ['input' => 8, 'output' => 30],
        'gemini-1.5-pro' => ['input' => 35, 'output' => 105],
        'gemini-1.0-pro' => ['input' => 50, 'output' => 150],
        // DeepSeek direct models
        'deepseek-v4-flash' => ['input' => 14, 'output' => 28],
        'deepseek-v4-pro' => ['input' => 44, 'output' => 87],
        'deepseek-chat' => ['input' => 14, 'output' => 28],
        'deepseek-reasoner' => ['input' => 55, 'output' => 220],
        // Kimi direct models
        'kimi-k3' => ['input' => 300, 'output' => 1500],
        'kimi-k2.6' => ['input' => 59, 'output' => 248],
        'kimi-k2.7-code' => ['input' => 73, 'output' => 350],
        'moonshot-v1-8k' => ['input' => 60, 'output' => 60],
        'moonshot-v1-32k' => ['input' => 60, 'output' => 60],
        'moonshot-v1-128k' => ['input' => 60, 'output' => 60],
        // Zhipu direct models
        'glm-5.2' => ['input' => 76, 'output' => 242],
        'glm-5.1' => ['input' => 97, 'output' => 304],
        'glm-5' => ['input' => 100, 'output' => 320],
        'glm-5-turbo' => ['input' => 120, 'output' => 400],
        'glm-4.7-flash' => ['input' => 0, 'output' => 0],
        'glm-4.7' => ['input' => 60, 'output' => 220],
        'glm-4.6' => ['input' => 60, 'output' => 220],
        'glm-4.5-air' => ['input' => 20, 'output' => 110],
        // xAI direct models
        'grok-4.5' => ['input' => 200, 'output' => 600],
        'grok-4.3' => ['input' => 125, 'output' => 250],
        'grok-4.2' => ['input' => 125, 'output' => 250],
        // Anthropic direct models
        'claude-fable-5' => ['input' => 1000, 'output' => 5000],
        'claude-mythos-5' => ['input' => 1000, 'output' => 5000],
        'claude-opus-4.8' => ['input' => 500, 'output' => 2500],
        'claude-opus-4.7' => ['input' => 500, 'output' => 2500],
        'claude-opus-4.6' => ['input' => 500, 'output' => 2500],
        'claude-opus-4.5' => ['input' => 500, 'output' => 2500],
        'claude-opus-4-1' => ['input' => 1500, 'output' => 7500],
        'claude-opus-4' => ['input' => 1500, 'output' => 7500],
        'claude-sonnet-5' => ['input' => 200, 'output' => 1000],
        'claude-sonnet-5-20260901' => ['input' => 300, 'output' => 1500],
        'claude-sonnet-4-6' => ['input' => 300, 'output' => 1500],
        'claude-sonnet-4-5' => ['input' => 300, 'output' => 1500],
        'claude-sonnet-4' => ['input' => 300, 'output' => 1500],
        'claude-haiku-4.5' => ['input' => 100, 'output' => 500],
        'claude-haiku-3-5' => ['input' => 80, 'output' => 400],
        // OpenRouter models (accurate pricing as of Aug 2026)
        'openai/gpt-4o' => ['input' => 250, 'output' => 1000],
        'openai/gpt-4o-mini' => ['input' => 15, 'output' => 60],
        'openai/gpt-5.6-sol' => ['input' => 500, 'output' => 3000],
        'openai/gpt-5.6-terra' => ['input' => 100, 'output' => 600],
        'openai/gpt-5.6-luna' => ['input' => 10, 'output' => 60],
        'openai/gpt-5.4-mini' => ['input' => 75, 'output' => 450],
        'openai/gpt-5.4-nano' => ['input' => 20, 'output' => 125],
        'openai/gpt-5.2' => ['input' => 175, 'output' => 1400],
        'openai/gpt-4.1' => ['input' => 200, 'output' => 800],
        'openai/gpt-4.1-mini' => ['input' => 40, 'output' => 160],
        'openai/gpt-4.1-nano' => ['input' => 10, 'output' => 40],
        'openai/o3-mini' => ['input' => 110, 'output' => 440],
        'openai/o4-mini' => ['input' => 110, 'output' => 440],
        'openai/gpt-4-turbo' => ['input' => 1000, 'output' => 3000],
        'openai/gpt-3.5-turbo' => ['input' => 50, 'output' => 150],
        'openai/chatgpt-4o-latest' => ['input' => 250, 'output' => 1000],
        // Anthropic (OpenRouter)
        'anthropic/claude-fable-5' => ['input' => 1000, 'output' => 5000],
        'anthropic/claude-opus-5' => ['input' => 500, 'output' => 2500],
        'anthropic/claude-sonnet-5' => ['input' => 200, 'output' => 1000],
        'anthropic/claude-opus-4.8-fast' => ['input' => 1000, 'output' => 5000],
        'anthropic/claude-opus-4.8' => ['input' => 500, 'output' => 2500],
        'anthropic/claude-opus-4.7' => ['input' => 500, 'output' => 2500],
        'anthropic/claude-opus-4.6' => ['input' => 500, 'output' => 2500],
        'anthropic/claude-opus-4.5' => ['input' => 500, 'output' => 2500],
        'anthropic/claude-haiku-4.5' => ['input' => 100, 'output' => 500],
        // Google (OpenRouter)
        'google/gemini-3.6-flash' => ['input' => 150, 'output' => 750],
        'google/gemini-3.5-flash-lite' => ['input' => 30, 'output' => 250],
        'google/gemini-3.5-flash' => ['input' => 150, 'output' => 900],
        'google/gemini-3.1-flash-lite' => ['input' => 25, 'output' => 150],
        // Meta Llama (OpenRouter)
        'meta-llama/llama-3.3-70b-instruct' => ['input' => 10, 'output' => 32],
        'meta-llama/llama-3.1-405b-instruct' => ['input' => 80, 'output' => 80],
        'meta-llama/llama-3.1-70b-instruct' => ['input' => 40, 'output' => 40],
        'meta-llama/llama-3.1-8b-instruct' => ['input' => 5, 'output' => 8],
        // Mistral (OpenRouter)
        'mistralai/mistral-large' => ['input' => 200, 'output' => 600],
        'mistralai/codestral-2508' => ['input' => 30, 'output' => 90],
        'mistralai/mistral-medium-3' => ['input' => 40, 'output' => 200],
        'mistralai/mistral-small-3.2-24b-instruct' => ['input' => 9, 'output' => 25],
        // Qwen (OpenRouter)
        'qwen/qwen3.7-max' => ['input' => 148, 'output' => 443],
        'qwen/qwen3.7-plus' => ['input' => 32, 'output' => 128],
        'qwen/qwen3.6-plus' => ['input' => 33, 'output' => 195],
        // MiniMax (OpenRouter)
        'minimax/minimax-m3' => ['input' => 24, 'output' => 96],
        'minimax/minimax-m2.7' => ['input' => 24, 'output' => 96],
        // Xiaomi (OpenRouter)
        'xiaomi/mimo-v2.5' => ['input' => 11, 'output' => 22],
        'xiaomi/mimo-v2.5-pro' => ['input' => 35, 'output' => 70],
        // Poolside (OpenRouter)
        'poolside/laguna-s-2.1' => ['input' => 9, 'output' => 18],
        // InclusionAI (OpenRouter)
        'inclusionai/ling-3.0-flash' => ['input' => 1, 'output' => 3],
        'inclusionai/ling-2.6-flash' => ['input' => 1, 'output' => 3],
        // Tencent (OpenRouter)
        'tencent/hy3' => ['input' => 13, 'output' => 53],
        // DeepSeek (OpenRouter)
        'deepseek/deepseek-v4-flash' => ['input' => 14, 'output' => 28],
        'deepseek/deepseek-v4-pro' => ['input' => 44, 'output' => 87],
        'deepseek/deepseek-v3.2' => ['input' => 27, 'output' => 40],
        // Kimi (OpenRouter)
        'moonshotai/kimi-k3' => ['input' => 300, 'output' => 1500],
        'moonshotai/kimi-k2.7-code' => ['input' => 73, 'output' => 350],
        'moonshotai/kimi-k2.6' => ['input' => 59, 'output' => 248],
        // Grok (OpenRouter)
        'x-ai/grok-4.5' => ['input' => 200, 'output' => 600],
        'x-ai/grok-4.3' => ['input' => 125, 'output' => 250],
        // GLM (OpenRouter)
        'z-ai/glm-5.2' => ['input' => 76, 'output' => 242],
        'z-ai/glm-5.1' => ['input' => 97, 'output' => 304],
        // OpenRouter admin-only (free + router modes)
        'openrouter/auto-beta' => ['input' => 50, 'output' => 150],
        'openrouter/free' => ['input' => 0, 'output' => 0],
        'inclusionai/ling-3.0-flash:free' => ['input' => 0, 'output' => 0],
        'nvidia/nemotron-3-ultra-550b-a55b:free' => ['input' => 0, 'output' => 0],
        'meta-llama/llama-3.1-8b-instruct:free' => ['input' => 0, 'output' => 0],
        'mistralai/mistral-7b-instruct:free' => ['input' => 0, 'output' => 0],
        'google/gemma-2-9b-it:free' => ['input' => 0, 'output' => 0],
        'microsoft/phi-3-mini-128k-instruct:free' => ['input' => 0, 'output' => 0],
    ];

    public static function resolve(string $stage, ?string $override = null): string
    {
        if ($override && $override !== 'auto' && $override !== '') {
            return $override;
        }

        $default = Setting::get('ai_builder_model_' . $stage);
        if (is_string($default) && trim($default) !== '') {
            $resolved = trim($default);
        } else {
            $resolved = self::DEFAULT_MODELS[$stage] ?? 'gpt-5.4-mini';
        }

        // Auto mode: if the resolved model has no API credentials, try fallbacks
        $router = app(\App\Services\AI\AiProviderRouter::class);
        if ($router->hasCredentials($resolved)) {
            return $resolved;
        }

        // Try a prioritized list of capable, cost-effective models
        $fallbacks = [
            'gpt-5.4-mini',
            'gpt-5-mini',
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4.1-mini',
            'gpt-5.4-nano',
            'gpt-5-nano',
        ];

        foreach ($fallbacks as $fallback) {
            if ($fallback === $resolved) {
                continue;
            }
            if ($router->hasCredentials($fallback)) {
                return $fallback;
            }
        }

        // No fallback had credentials; return the original so the caller
        // produces a clear "API key not configured" error.
        return $resolved;
    }

    public static function estimateCostCents(string $model, int $inputTokens, int $outputTokens, ?int $totalTokens = null): float
    {
        $provider = self::detectProvider($model);
        $inputRate = self::getCostFromSettings($provider, $model, 'input');
        $outputRate = self::getCostFromSettings($provider, $model, 'output');

        if ($inputTokens <= 0 && $outputTokens <= 0 && $totalTokens > 0) {
            $averageRate = ($inputRate + $outputRate) / 2;
            return ($totalTokens / 1_000_000) * $averageRate;
        }

        $inputCost = ($inputTokens / 1_000_000) * $inputRate;
        $outputCost = ($outputTokens / 1_000_000) * $outputRate;
        return $inputCost + $outputCost;
    }

    public static function detectProvider(string $model): string
    {
        return AiProviderRegistry::detectProvider($model);
    }

    private static function getCostFromSettings(string $provider, string $model, string $type): int
    {
        $app = app();
        if (!$app->bound('App\Services\AI\AiUsageService')) {
            $rates = self::COST_PER_1M_TOKENS_CENTS[$model] ?? ['input' => 50, 'output' => 150];
            return $rates[$type] ?? ($type === 'input' ? 50 : 150);
        }

        $service = $app->make('App\Services\AI\AiUsageService');
        $cost = $type === 'input'
            ? $service->getAdminInputCostPer1mCentsForModel($provider, $model)
            : $service->getAdminOutputCostPer1mCentsForModel($provider, $model);

        if ($cost > 0) {
            return $cost;
        }

        $rates = self::COST_PER_1M_TOKENS_CENTS[$model] ?? ['input' => 50, 'output' => 150];
        return $rates[$type] ?? ($type === 'input' ? 50 : 150);
    }

    public static function routeWorkerByComplexity(string $complexity): string
    {
        return match ($complexity) {
            'simple' => self::resolve('worker'),
            'medium' => Setting::get('ai_builder_model_worker_medium') ?: 'gpt-5.4-mini',
            'complex' => Setting::get('ai_builder_model_worker_complex') ?: 'gpt-5.4-mini',
            default => self::resolve('worker'),
        };
    }

    public static function allModels(): array
    {
        return [
            'gpt-5.6-sol' => 'GPT-5.6 Sol',
            'gpt-5.6-tera' => 'GPT-5.6 Tera',
            'gpt-5.6-luna' => 'GPT-5.6 Luna',
            'gpt-5.6-sol-pro' => 'GPT-5.6 Sol Pro',
            'gpt-5.6-tera-pro' => 'GPT-5.6 Tera Pro',
            'gpt-5.6-luna-pro' => 'GPT-5.6 Luna Pro',
            'gpt-5.5' => 'GPT-5.5',
            'gpt-5.5-pro' => 'GPT-5.5 Pro',
            'gpt-5.4' => 'GPT-5.4',
            'gpt-5.4-pro' => 'GPT-5.4 Pro',
            'gpt-5.4-mini' => 'GPT-5.4 Mini',
            'gpt-5.4-nano' => 'GPT-5.4 Nano',
            'gpt-5.2' => 'GPT-5.2',
            'gpt-5.2-pro' => 'GPT-5.2 Pro',
            'gpt-5.1' => 'GPT-5.1',
            'gpt-5' => 'GPT-5',
            'gpt-5-pro' => 'GPT-5 Pro',
            'gpt-5-mini' => 'GPT-5 Mini',
            'gpt-5-nano' => 'GPT-5 Nano',
            'gpt-4.1' => 'GPT-4.1',
            'gpt-4.1-mini' => 'GPT-4.1 Mini',
            'gpt-4.1-nano' => 'GPT-4.1 Nano',
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini',
            'o4-mini' => 'o4-mini',
            'o3' => 'o3',
            'o3-mini' => 'o3-mini',
            'o3-pro' => 'o3-pro',
            'gpt-4-32k' => 'GPT-4 32k',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gemini-3.5-flash' => 'Gemini 3.5 Flash',
            'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash Lite',
            'gemini-3.1-pro' => 'Gemini 3.1 Pro',
            'gemini-3.1-flash' => 'Gemini 3.1 Flash',
            'gemini-2.5-pro' => 'Gemini 2.5 Pro',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash',
            'gemini-2.0-flash' => 'Gemini 2.0 Flash',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro',
            'gemini-1.0-pro' => 'Gemini 1.0 Pro',
            'deepseek-v4-flash' => 'DeepSeek v4 Flash',
            'deepseek-v4-pro' => 'DeepSeek v4 Pro',
            'deepseek-chat' => 'DeepSeek Chat',
            'deepseek-reasoner' => 'DeepSeek Reasoner',
            'kimi-k3' => 'Kimi K3',
            'kimi-k2.6' => 'Kimi K2.6',
            'kimi-k2.7-code' => 'Kimi K2.7 Code',
            'glm-5.2' => 'GLM 5.2',
            'glm-5.1' => 'GLM 5.1',
            'glm-5' => 'GLM 5',
            'glm-5-turbo' => 'GLM 5 Turbo',
            'glm-4.7-flash' => 'GLM 4.7 Flash',
            'glm-4.7' => 'GLM 4.7',
            'glm-4.6' => 'GLM 4.6',
            'glm-4.5-air' => 'GLM 4.5 Air',
            'grok-4.5' => 'Grok 4.5',
            'grok-4.3' => 'Grok 4.3',
            'grok-4.2' => 'Grok 4.2',
            'claude-fable-5' => 'Claude Fable 5',
            'claude-mythos-5' => 'Claude Mythos 5',
            'claude-opus-4-8' => 'Claude Opus 4.8',
            'claude-opus-4-7' => 'Claude Opus 4.7',
            'claude-opus-4-6' => 'Claude Opus 4.6',
            'claude-opus-4-5' => 'Claude Opus 4.5',
            'claude-opus-4-1' => 'Claude Opus 4.1',
            'claude-opus-4' => 'Claude Opus 4',
            'claude-sonnet-5' => 'Claude Sonnet 5',
            'claude-sonnet-5-20260901' => 'Claude Sonnet 5 (from Sep 2026)',
            'claude-sonnet-4-6' => 'Claude Sonnet 4.6',
            'claude-sonnet-4-5' => 'Claude Sonnet 4.5',
            'claude-sonnet-4' => 'Claude Sonnet 4',
            'claude-haiku-4-5' => 'Claude Haiku 4.5',
            'claude-haiku-3-5' => 'Claude Haiku 3.5',
            // OpenRouter
            'openai/gpt-5.6-sol' => 'GPT-5.6 Sol',
            'openai/gpt-5.6-tera' => 'GPT-5.6 Tera',
            'openai/gpt-5.6-luna' => 'GPT-5.6 Luna',
            'openai/gpt-5.6-sol-pro' => 'GPT-5.6 Sol Pro',
            'openai/gpt-5.6-tera-pro' => 'GPT-5.6 Terra Pro',
            'openai/gpt-5.6-luna-pro' => 'GPT-5.6 Luna Pro',
            'openai/gpt-5.4-mini' => 'GPT-5.4 Mini',
            'openai/gpt-5.4-nano' => 'GPT-5.4 Nano',
            'openai/gpt-5.2' => 'GPT-5.2',
            'openai/gpt-4o' => 'GPT-4o',
            'openai/gpt-4o-mini' => 'GPT-4o Mini',
            'openai/gpt-4.1' => 'GPT-4.1',
            'openai/gpt-4.1-mini' => 'GPT-4.1 Mini',
            'openai/gpt-4.1-nano' => 'GPT-4.1 Nano',
            'openai/o3-mini' => 'o3-mini',
            'openai/o4-mini' => 'o4-mini',
            'openai/gpt-4-turbo' => 'GPT-4 Turbo',
            'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'openai/chatgpt-4o-latest' => 'ChatGPT-4o Latest',
            'anthropic/claude-fable-5' => 'Claude Fable 5',
            'anthropic/claude-opus-5' => 'Claude Opus 5',
            'anthropic/claude-sonnet-5' => 'Claude Sonnet 5',
            'anthropic/claude-opus-4-8-fast' => 'Claude Opus 4.8 Fast',
            'anthropic/claude-opus-4-8' => 'Claude Opus 4.8',
            'anthropic/claude-opus-4-7' => 'Claude Opus 4.7',
            'anthropic/claude-opus-4-6' => 'Claude Opus 4.6',
            'anthropic/claude-opus-4-5' => 'Claude Opus 4.5',
            'anthropic/claude-haiku-4-5' => 'Claude Haiku 4.5',
            'google/gemini-3.6-flash' => 'Gemini 3.6 Flash',
            'google/gemini-3.5-flash-lite' => 'Gemini 3.5 Flash Lite',
            'google/gemini-3.5-flash' => 'Gemini 3.5 Flash',
            'google/gemini-3.1-flash-lite' => 'Gemini 3.1 Flash Lite',
            'meta-llama/llama-3.3-70b-instruct' => 'Llama 3.3 70B',
            'meta-llama/llama-3.1-405b-instruct' => 'Llama 3.1 405B',
            'meta-llama/llama-3.1-70b-instruct' => 'Llama 3.1 70B',
            'meta-llama/llama-3.1-8b-instruct' => 'Llama 3.1 8B',
            'mistralai/mistral-large' => 'Mistral Large',
            'mistralai/codestral' => 'Codestral',
            'mistralai/mistral-medium' => 'Mistral Medium',
            'mistralai/mistral-small' => 'Mistral Small',
            'qwen/qwen3.7-max' => 'Qwen3.7 Max',
            'qwen/qwen3.7-plus' => 'Qwen3.7 Plus',
            'qwen/qwen3.6-plus' => 'Qwen3.6 Plus',
            'minimax/minimax-m3' => 'MiniMax M3',
            'minimax/minimax-m2.7' => 'MiniMax M2.7',
            'xiaomi/mimo-v2.5' => 'MiMo-V2.5',
            'xiaomi/mimo-v2.5-pro' => 'MiMo-V2.5-Pro',
            'deepseek/deepseek-v4-flash' => 'DeepSeek V4 Flash',
            'deepseek/deepseek-v4-pro' => 'DeepSeek V4 Pro',
            'deepseek/deepseek-v3.2' => 'DeepSeek V3.2',
            'moonshotai/kimi-k3' => 'Kimi K3',
            'moonshotai/kimi-k2.7-code' => 'Kimi K2.7 Code',
            'moonshotai/kimi-k2.6' => 'Kimi K2.6',
            'x-ai/grok-4.5' => 'Grok 4.5',
            'x-ai/grok-4.3' => 'Grok 4.3',
            'zhipuai/glm-5.2' => 'GLM 5.2',
            'zhipuai/glm-5.1' => 'GLM 5.1',
            'openrouter/auto-beta' => 'OpenRouter Auto (Beta)',
            'openrouter/free' => 'OpenRouter Free Router',
            'inclusionai/ling-3.0-flash' => 'Ling 3.0 Flash',
            'inclusionai/ling-3.0-flash:free' => 'Ling 3.0 Flash Free',
            'nvidia/nemotron-3-ultra-550b-a55b:free' => 'Nemotron 3 Ultra Free',
            'meta-llama/llama-3.1-8b-instruct:free' => 'Llama 3.1 8B Free',
            'mistralai/mistral-7b-instruct:free' => 'Mistral 7B Free',
            'google/gemma-2-9b-it:free' => 'Gemma 2 9B Free',
            'microsoft/phi-3-mini-128k-instruct:free' => 'Phi-3 Mini Free',
        ];
    }
}

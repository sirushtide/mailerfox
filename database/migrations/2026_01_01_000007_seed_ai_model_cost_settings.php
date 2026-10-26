<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $openAiModels = [
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4-turbo',
            'gpt-4',
            'gpt-3.5-turbo',
        ];

        $geminiModels = [
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
        ];

        $deepseekModels = [
            'deepseek-v4-flash',
            'deepseek-v4-pro',
            'deepseek-chat',
            'deepseek-reasoner',
        ];

        $kimiModels = [
            'kimi-k2.6',
            'kimi-k2.7-code',
        ];

        $glmModels = [
            'glm-5.2',
            'glm-5.1',
            'glm-5',
            'glm-4.7-flash',
            'glm-4.7',
            'glm-4.6',
        ];

        $claudeModels = [
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
        ];

        $items = [];

        foreach ($openAiModels as $m) {
            $items[] = [
                'key' => 'openai_cost_per_1k_cents_' . $this->modelKeySlug($m),
                'category' => 'ai',
                'value' => 0,
                'type' => 'integer',
                'description' => 'Estimated cost per 1K tokens for OpenAI model ' . $m . ' (in cents). Used for dashboard estimates.',
                'is_public' => false,
            ];
        }

        foreach ($geminiModels as $m) {
            $items[] = [
                'key' => 'gemini_cost_per_1k_cents_' . $this->modelKeySlug($m),
                'category' => 'ai',
                'value' => 0,
                'type' => 'integer',
                'description' => 'Estimated cost per 1K tokens for Gemini model ' . $m . ' (in cents). Used for dashboard estimates.',
                'is_public' => false,
            ];
        }

        foreach ($deepseekModels as $m) {
            $items[] = [
                'key' => 'deepseek_cost_per_1k_cents_' . $this->modelKeySlug($m),
                'category' => 'ai',
                'value' => 0,
                'type' => 'integer',
                'description' => 'Estimated cost per 1K tokens for DeepSeek model ' . $m . ' (in cents). Used for dashboard estimates.',
                'is_public' => false,
            ];
        }

        foreach ($kimiModels as $m) {
            $items[] = [
                'key' => 'kimi_cost_per_1k_cents_' . $this->modelKeySlug($m),
                'category' => 'ai',
                'value' => 0,
                'type' => 'integer',
                'description' => 'Estimated cost per 1K tokens for Kimi model ' . $m . ' (in cents). Used for dashboard estimates.',
                'is_public' => false,
            ];
        }

        foreach ($glmModels as $m) {
            $items[] = [
                'key' => 'glm_cost_per_1k_cents_' . $this->modelKeySlug($m),
                'category' => 'ai',
                'value' => 0,
                'type' => 'integer',
                'description' => 'Estimated cost per 1K tokens for GLM model ' . $m . ' (in cents). Used for dashboard estimates.',
                'is_public' => false,
            ];
        }

        foreach ($claudeModels as $m) {
            $items[] = [
                'key' => 'claude_cost_per_1k_cents_' . $this->modelKeySlug($m),
                'category' => 'ai',
                'value' => 0,
                'type' => 'integer',
                'description' => 'Estimated cost per 1K tokens for Claude model ' . $m . ' (in cents). Used for dashboard estimates.',
                'is_public' => false,
            ];
        }

        foreach ($items as $row) {
            Setting::firstOrCreate(
                ['key' => $row['key']],
                [
                    'category' => $row['category'],
                    'value' => $row['value'],
                    'type' => $row['type'],
                    'description' => $row['description'],
                    'is_public' => $row['is_public'],
                ]
            );
        }
    }

    public function down(): void
    {
        $prefixes = [
            'openai_cost_per_1k_cents_',
            'gemini_cost_per_1k_cents_',
            'deepseek_cost_per_1k_cents_',
            'kimi_cost_per_1k_cents_',
            'glm_cost_per_1k_cents_',
            'claude_cost_per_1k_cents_',
        ];

        foreach ($prefixes as $prefix) {
            Setting::query()->where('key', 'like', $prefix . '%')->delete();
        }
    }

    private function modelKeySlug(string $model): string
    {
        $model = strtolower(trim($model));
        $model = preg_replace('/[^a-z0-9]+/', '_', $model) ?? $model;
        $model = trim($model, '_');
        return $model;
    }
};

<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $claudeModels = [
            ['id' => 'claude-haiku-4-5-20251001', 'input' => 100, 'output' => 500],
            ['id' => 'claude-sonnet-4-5', 'input' => 300, 'output' => 1500],
            ['id' => 'claude-sonnet-4-6', 'input' => 300, 'output' => 1500],
            ['id' => 'claude-opus-4-5', 'input' => 500, 'output' => 2500],
            ['id' => 'claude-opus-4-6', 'input' => 500, 'output' => 2500],
            ['id' => 'claude-opus-4-7', 'input' => 500, 'output' => 2500],
            ['id' => 'claude-opus-4-8', 'input' => 500, 'output' => 2500],
        ];

        foreach ($claudeModels as $m) {
            $slug = $this->modelKeySlug($m['id']);

            // Input cost
            Setting::firstOrCreate(
                ['key' => 'claude_input_cost_per_1m_cents_' . $slug],
                [
                    'category' => 'ai',
                    'value' => $m['input'],
                    'type' => 'integer',
                    'description' => 'Estimated input cost per 1M tokens for Claude model ' . $m['id'] . ' (in cents). $' . ($m['input'] / 100) . ' / MTok. Used for dashboard estimates.',
                    'is_public' => false,
                ]
            );

            // Output cost
            Setting::firstOrCreate(
                ['key' => 'claude_output_cost_per_1m_cents_' . $slug],
                [
                    'category' => 'ai',
                    'value' => $m['output'],
                    'type' => 'integer',
                    'description' => 'Estimated output cost per 1M tokens for Claude model ' . $m['id'] . ' (in cents). $' . ($m['output'] / 100) . ' / MTok. Used for dashboard estimates.',
                    'is_public' => false,
                ]
            );

            // Combined/fallback cost (input cost as default)
            Setting::firstOrCreate(
                ['key' => 'claude_cost_per_1m_cents_' . $slug],
                [
                    'category' => 'ai',
                    'value' => $m['input'],
                    'type' => 'integer',
                    'description' => 'Estimated cost per 1M tokens for Claude model ' . $m['id'] . ' (in cents). Input $' . ($m['input'] / 100) . ' / MTok, Output $' . ($m['output'] / 100) . ' / MTok. Used for dashboard estimates.',
                    'is_public' => false,
                ]
            );
        }
    }

    public function down(): void
    {
        $claudeModels = [
            'claude-haiku-4-5-20251001',
            'claude-sonnet-4-5',
            'claude-sonnet-4-6',
            'claude-opus-4-5',
            'claude-opus-4-6',
            'claude-opus-4-7',
            'claude-opus-4-8',
        ];

        foreach ($claudeModels as $m) {
            $slug = $this->modelKeySlug($m);
            Setting::query()->where('key', 'claude_input_cost_per_1m_cents_' . $slug)->delete();
            Setting::query()->where('key', 'claude_output_cost_per_1m_cents_' . $slug)->delete();
            Setting::query()->where('key', 'claude_cost_per_1m_cents_' . $slug)->delete();
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

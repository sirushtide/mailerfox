<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $claudeModels = [
            'claude-opus-4-8',
            'claude-opus-4-7',
            'claude-opus-4-6',
            'claude-opus-4-5',
            'claude-sonnet-4-6',
            'claude-sonnet-4-5',
            'claude-haiku-4-5',
            'claude-opus-4-20250514',
            'claude-sonnet-4-20250514',
            'claude-3-7-sonnet-20250219',
        ];

        foreach ($claudeModels as $m) {
            Setting::firstOrCreate(
                ['key' => 'claude_cost_per_1k_cents_' . $this->modelKeySlug($m)],
                [
                    'category' => 'ai',
                    'value' => 0,
                    'type' => 'integer',
                    'description' => 'Estimated cost per 1K tokens for Claude model ' . $m . ' (in cents). Used for dashboard estimates.',
                    'is_public' => false,
                ]
            );
        }
    }

    public function down(): void
    {
        $models = [
            'claude-opus-4-8',
            'claude-opus-4-7',
            'claude-opus-4-6',
            'claude-opus-4-5',
            'claude-sonnet-4-6',
            'claude-sonnet-4-5',
            'claude-haiku-4-5',
            'claude-opus-4-20250514',
            'claude-sonnet-4-20250514',
            'claude-3-7-sonnet-20250219',
        ];

        foreach ($models as $m) {
            Setting::query()
                ->where('key', 'claude_cost_per_1k_cents_' . $this->modelKeySlug($m))
                ->delete();
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

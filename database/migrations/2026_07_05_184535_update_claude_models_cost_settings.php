<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $models = [
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

        foreach ($models as $model) {
            Setting::firstOrCreate(
                ['key' => 'claude_cost_per_1k_cents_' . $this->modelKeySlug($model)],
                [
                    'category' => 'ai',
                    'value' => 0,
                    'type' => 'integer',
                    'description' => 'Estimated cost per 1K tokens for Claude model ' . $model . ' (in cents). Used for dashboard estimates.',
                    'is_public' => false,
                ]
            );
        }
    }

    public function down(): void
    {
        $models = [
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

        foreach ($models as $model) {
            Setting::query()->where('key', 'claude_cost_per_1k_cents_' . $this->modelKeySlug($model))->delete();
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

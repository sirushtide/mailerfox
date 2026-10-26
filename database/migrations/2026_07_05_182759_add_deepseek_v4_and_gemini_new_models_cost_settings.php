<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $models = [
            'gemini-3.5-flash',
            'gemini-3.1-flash-lite',
            'gemini-3.1-pro',
            'gemini-3.1-flash',
            'gemini-2.5-pro',
            'gemini-2.5-flash',
            'deepseek-v4-flash',
            'deepseek-v4-pro',
            'kimi-k2.6',
            'kimi-k2.7-code',
        ];

        foreach ($models as $model) {
            $provider = match (true) {
                str_starts_with($model, 'gemini') => 'gemini',
                str_starts_with($model, 'kimi') => 'kimi',
                default => 'deepseek',
            };
            Setting::firstOrCreate(
                ['key' => $provider . '_cost_per_1k_cents_' . $this->modelKeySlug($model)],
                [
                    'category' => 'ai',
                    'value' => 0,
                    'type' => 'integer',
                    'description' => 'Estimated cost per 1K tokens for ' . ucfirst($provider) . ' model ' . $model . ' (in cents). Used for dashboard estimates.',
                    'is_public' => false,
                ]
            );
        }
    }

    public function down(): void
    {
        $models = [
            'gemini-3.5-flash',
            'gemini-3.1-flash-lite',
            'gemini-3.1-pro',
            'gemini-3.1-flash',
            'gemini-2.5-pro',
            'gemini-2.5-flash',
            'deepseek-v4-flash',
            'deepseek-v4-pro',
            'kimi-k2.6',
            'kimi-k2.7-code',
        ];

        foreach ($models as $model) {
            $provider = match (true) {
                str_starts_with($model, 'gemini') => 'gemini',
                str_starts_with($model, 'kimi') => 'kimi',
                default => 'deepseek',
            };
            Setting::query()->where('key', $provider . '_cost_per_1k_cents_' . $this->modelKeySlug($model))->delete();
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

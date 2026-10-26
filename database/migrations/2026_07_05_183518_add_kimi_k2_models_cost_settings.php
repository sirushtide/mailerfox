<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $models = [
            'kimi-k2.6',
            'kimi-k2.7-code',
        ];

        foreach ($models as $model) {
            Setting::firstOrCreate(
                ['key' => 'kimi_cost_per_1k_cents_' . $this->modelKeySlug($model)],
                [
                    'category' => 'ai',
                    'value' => 0,
                    'type' => 'integer',
                    'description' => 'Estimated cost per 1K tokens for Kimi model ' . $model . ' (in cents). Used for dashboard estimates.',
                    'is_public' => false,
                ]
            );
        }
    }

    public function down(): void
    {
        $models = [
            'kimi-k2.6',
            'kimi-k2.7-code',
        ];

        foreach ($models as $model) {
            Setting::query()->where('key', 'kimi_cost_per_1k_cents_' . $this->modelKeySlug($model))->delete();
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

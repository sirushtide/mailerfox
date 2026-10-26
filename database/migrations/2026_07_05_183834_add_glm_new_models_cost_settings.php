<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $models = [
            'glm-5.2',
            'glm-5.1',
            'glm-5',
            'glm-4.7-flash',
            'glm-4.7',
            'glm-4.6',
        ];

        foreach ($models as $model) {
            Setting::firstOrCreate(
                ['key' => 'glm_cost_per_1k_cents_' . $this->modelKeySlug($model)],
                [
                    'category' => 'ai',
                    'value' => 0,
                    'type' => 'integer',
                    'description' => 'Estimated cost per 1K tokens for GLM model ' . $model . ' (in cents). Used for dashboard estimates.',
                    'is_public' => false,
                ]
            );
        }
    }

    public function down(): void
    {
        $models = [
            'glm-5.2',
            'glm-5.1',
            'glm-5',
            'glm-4.7-flash',
            'glm-4.7',
            'glm-4.6',
        ];

        foreach ($models as $model) {
            Setting::query()->where('key', 'glm_cost_per_1k_cents_' . $this->modelKeySlug($model))->delete();
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

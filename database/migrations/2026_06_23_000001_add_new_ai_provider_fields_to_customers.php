<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('anthropic_api_key')->nullable()->after('gemini_api_key');
            $table->text('deepseek_api_key')->nullable()->after('anthropic_api_key');
            $table->text('kimi_api_key')->nullable()->after('deepseek_api_key');
            $table->text('glm_api_key')->nullable()->after('kimi_api_key');
            $table->text('grok_api_key')->nullable()->after('glm_api_key');
            $table->text('openrouter_api_key')->nullable()->after('grok_api_key');
        });

        $providers = [
            'anthropic' => 'anthropic_api_key',
            'deepseek' => 'deepseek_api_key',
            'kimi' => 'kimi_api_key',
            'glm' => 'glm_api_key',
            'grok' => 'grok_api_key',
            'openrouter' => 'openrouter_api_key',
        ];

        foreach ($providers as $slug => $settingKey) {
            Setting::firstOrCreate(
                ['key' => $settingKey],
                [
                    'category' => 'ai',
                    'value' => env(strtoupper($slug) . '_API_KEY'),
                    'type' => 'string',
                    'description' => 'Admin ' . ucfirst($slug) . ' API key (used when customers do not provide their own keys).',
                    'is_public' => false,
                ]
            );

            Setting::firstOrCreate(
                ['key' => $slug . '_token_limit_monthly'],
                [
                    'category' => 'ai',
                    'value' => 0,
                    'type' => 'integer',
                    'description' => 'Monthly token limit for ' . ucfirst($slug) . ' (0 = unlimited).',
                    'is_public' => false,
                ]
            );

            Setting::firstOrCreate(
                ['key' => $slug . '_token_limit_daily'],
                [
                    'category' => 'ai',
                    'value' => 0,
                    'type' => 'integer',
                    'description' => 'Daily token limit for ' . ucfirst($slug) . ' (0 = unlimited).',
                    'is_public' => false,
                ]
            );

            Setting::firstOrCreate(
                ['key' => $slug . '_cost_per_1m_cents'],
                [
                    'category' => 'ai',
                    'value' => 0,
                    'type' => 'integer',
                    'description' => 'Cost per 1 million tokens in cents for ' . ucfirst($slug) . '.',
                    'is_public' => false,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'anthropic_api_key',
                'deepseek_api_key',
                'kimi_api_key',
                'glm_api_key',
                'grok_api_key',
                'openrouter_api_key',
            ]);
        });

        Setting::whereIn('key', [
            'anthropic_api_key',
            'deepseek_api_key',
            'kimi_api_key',
            'glm_api_key',
            'grok_api_key',
            'openrouter_api_key',
            'anthropic_token_limit_monthly',
            'deepseek_token_limit_monthly',
            'kimi_token_limit_monthly',
            'glm_token_limit_monthly',
            'grok_token_limit_monthly',
            'openrouter_token_limit_monthly',
            'anthropic_token_limit_daily',
            'deepseek_token_limit_daily',
            'kimi_token_limit_daily',
            'glm_token_limit_daily',
            'grok_token_limit_daily',
            'openrouter_token_limit_daily',
            'anthropic_cost_per_1m_cents',
            'deepseek_cost_per_1m_cents',
            'kimi_cost_per_1m_cents',
            'glm_cost_per_1m_cents',
            'grok_cost_per_1m_cents',
            'openrouter_cost_per_1m_cents',
        ])->delete();
    }
};

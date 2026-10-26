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
            $table->text('qwen_api_key')->nullable()->after('openrouter_api_key');
            $table->text('minimax_api_key')->nullable()->after('qwen_api_key');
            $table->text('xiaomi_api_key')->nullable()->after('minimax_api_key');
        });

        $providers = [
            'qwen' => 'qwen_api_key',
            'minimax' => 'minimax_api_key',
            'xiaomi' => 'xiaomi_api_key',
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

        Setting::whereIn('key', [
            'ai_model_enabled_openrouter_qwen_qwen_2_5_72b_instruct',
            'ai_model_enabled_openrouter_qwen_qwen_2_5_coder_32b_instruct',
            'ai_model_enabled_openrouter_qwen_qwen_2_5_7b_instruct',
            'ai_model_enabled_openrouter_qwen_qwen_2_5_7b_instruct_free',
            'ai_model_enabled_openrouter_qwen_qwq_32b',
            'ai_model_enabled_openrouter_xiaomi_mimo_7b',
            'ai_model_enabled_openrouter_xiaomi_mimo_7b_instruct',
            'ai_model_enabled_openrouter_xiaomi_mimo_8b',
        ])->delete();
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'qwen_api_key',
                'minimax_api_key',
                'xiaomi_api_key',
            ]);
        });

        Setting::whereIn('key', [
            'qwen_api_key',
            'minimax_api_key',
            'xiaomi_api_key',
            'qwen_token_limit_monthly',
            'minimax_token_limit_monthly',
            'xiaomi_token_limit_monthly',
            'qwen_token_limit_daily',
            'minimax_token_limit_daily',
            'xiaomi_token_limit_daily',
            'qwen_cost_per_1m_cents',
            'minimax_cost_per_1m_cents',
            'xiaomi_cost_per_1m_cents',
        ])->delete();
    }
};

<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'claude_api_key'],
            [
                'category' => 'ai',
                'value' => env('CLAUDE_API_KEY', ''),
                'type' => 'string',
                'description' => 'Admin Claude API key (used when customers do not provide their own keys).',
                'is_public' => false,
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key', 'claude_api_key')->delete();
    }
};

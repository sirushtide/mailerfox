<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::whereIn('key', [
            'home_page_variant',
            'home_redirect_enabled',
            'home_redirect_url',
            'public_header_sticky',
            'toast_position',
        ])->update([
            'category' => 'general',
        ]);
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'home_page_variant',
            'home_redirect_enabled',
            'home_redirect_url',
            'public_header_sticky',
            'toast_position',
        ])->update([
            'category' => 'appearance',
        ]);
    }
};

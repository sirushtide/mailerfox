<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_builder_sessions', 'app_meta')) {
                $table->json('app_meta')->nullable()->after('page_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            $table->dropColumn('app_meta');
        });
    }
};

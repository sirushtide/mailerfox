<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_builder_messages', function (Blueprint $table) {
            $table->string('type')->nullable()->index()->after('role');
            $table->json('plan_data')->nullable()->after('section_data');
        });
    }

    public function down(): void
    {
        Schema::table('ai_builder_messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'plan_data']);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->json('ai_builder_credits')->nullable()->after('ai_image_credits_used');
            $table->timestamp('ai_builder_credits_reset_at')->nullable()->after('ai_builder_credits');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['ai_builder_credits', 'ai_builder_credits_reset_at']);
        });
    }
};

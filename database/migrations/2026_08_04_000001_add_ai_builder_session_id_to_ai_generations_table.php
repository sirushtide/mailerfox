<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->foreignId('ai_builder_session_id')
                ->nullable()
                ->after('id')
                ->constrained('ai_builder_sessions')
                ->nullOnDelete();

            $table->index('ai_builder_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropIndex(['ai_builder_session_id']);
            $table->dropForeign(['ai_builder_session_id']);
            $table->dropColumn('ai_builder_session_id');
        });
    }
};

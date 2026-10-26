<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            $table->json('project_files')->nullable()->after('preview_html');
        });
    }

    public function down(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            $table->dropColumn('project_files');
        });
    }
};

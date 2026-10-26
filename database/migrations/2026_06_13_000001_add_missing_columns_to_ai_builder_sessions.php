<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_builder_sessions', 'project_files')) {
                $table->json('project_files')->nullable()->after('preview_html');
            }
            if (! Schema::hasColumn('ai_builder_sessions', 'react_code')) {
                $table->longText('react_code')->nullable()->after('project_files');
            }
            if (! Schema::hasColumn('ai_builder_sessions', 'preview_mode')) {
                $table->string('preview_mode', 20)->default('html')->after('react_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            $table->dropColumn(['project_files', 'react_code', 'preview_mode']);
        });
    }
};

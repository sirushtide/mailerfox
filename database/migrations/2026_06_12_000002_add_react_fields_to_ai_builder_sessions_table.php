<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            $table->longText('react_code')->nullable()->after('preview_html');
            $table->string('preview_mode', 20)->default('html')->after('react_code');
        });
    }

    public function down(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            $table->dropColumn(['react_code', 'preview_mode']);
        });
    }
};

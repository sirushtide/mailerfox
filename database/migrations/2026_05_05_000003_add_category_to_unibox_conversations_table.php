<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unibox_conversations', function (Blueprint $table) {
            $table->string('category', 32)->nullable()->index()->after('folder');
        });
    }

    public function down(): void
    {
        Schema::table('unibox_conversations', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};

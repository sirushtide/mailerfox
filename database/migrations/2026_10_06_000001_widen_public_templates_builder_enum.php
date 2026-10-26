<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_templates', function (Blueprint $table) {
            $table->enum('builder', ['grapesjs', 'unlayer', 'mailpurse', 'text'])
                ->default('unlayer')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('public_templates', function (Blueprint $table) {
            $table->enum('builder', ['grapesjs', 'unlayer'])
                ->default('unlayer')
                ->change();
        });
    }
};

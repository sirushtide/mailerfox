<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_templates', function (Blueprint $table) {
            $table->enum('type', ['email', 'campaign', 'transactional', 'autoresponder', 'footer', 'signature'])
                ->default('email')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('public_templates', function (Blueprint $table) {
            $table->enum('type', ['email', 'campaign', 'transactional', 'autoresponder'])
                ->default('email')
                ->change();
        });
    }
};

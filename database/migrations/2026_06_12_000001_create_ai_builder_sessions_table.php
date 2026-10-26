<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_builder_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 64)->unique()->index();
            $table->json('messages')->nullable();
            $table->longText('preview_html')->nullable();
            $table->string('preview_title')->nullable();
            $table->string('page_title')->nullable();
            $table->string('page_slug')->nullable();
            $table->string('page_status', 20)->default('draft');
            $table->string('model')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_builder_sessions');
    }
};

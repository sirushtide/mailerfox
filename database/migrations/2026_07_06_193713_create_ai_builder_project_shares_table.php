<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_builder_project_shares', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('email');
            $table->enum('role', ['owner', 'editor', 'viewer'])->default('viewer');
            $table->string('invite_token', 64)->nullable()->unique();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('session_id')->on('ai_builder_sessions')->onDelete('cascade');
            $table->index(['session_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_builder_project_shares');
    }
};

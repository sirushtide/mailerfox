<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_run_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_run_id')->constrained()->onDelete('cascade');
            $table->foreignId('automation_id')->constrained()->onDelete('cascade');
            $table->string('node_id');
            $table->string('node_type')->nullable();
            $table->string('event')->default('entered');
            $table->json('meta')->nullable();
            $table->timestamp('created_at');

            $table->index(['automation_id', 'node_id']);
            $table->index(['automation_run_id', 'node_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_run_logs');
    }
};

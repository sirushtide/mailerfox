<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_builder_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('ai_generation_id')->nullable()->constrained('ai_generations')->nullOnDelete();

            $table->string('role')->index(); // user | assistant
            $table->longText('content')->nullable();
            $table->json('section_data')->nullable();
            $table->string('scope')->nullable();
            $table->string('model')->nullable();
            $table->string('status')->default('ok'); // ok | error
            $table->unsignedInteger('tokens_used')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_builder_messages');
    }
};

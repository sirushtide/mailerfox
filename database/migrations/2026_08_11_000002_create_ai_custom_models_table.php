<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_custom_models', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('model_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('context_length')->nullable();
            $table->unsignedInteger('input_cost_per_1m_cents')->default(0);
            $table->unsignedInteger('output_cost_per_1m_cents')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['provider', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_custom_models');
    }
};

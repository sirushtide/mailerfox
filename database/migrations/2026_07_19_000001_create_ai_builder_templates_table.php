<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_builder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_id')->unique();
            $table->string('category')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->string('thumbnail')->nullable();
            $table->longText('component_source');
            $table->json('fields');
            $table->json('defaults');
            $table->json('example_props')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_builder_templates');
    }
};

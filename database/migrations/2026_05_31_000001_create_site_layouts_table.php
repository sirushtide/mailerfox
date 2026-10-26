<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_layouts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['header', 'footer'])->default('header');
            $table->string('name');
            $table->json('display_conditions')->nullable();
            $table->json('builder_data')->nullable();
            $table->longText('html_content')->nullable();
            $table->enum('status', ['draft', 'publish'])->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_layouts');
    }
};

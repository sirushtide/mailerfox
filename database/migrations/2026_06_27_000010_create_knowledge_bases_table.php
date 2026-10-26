<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('general'); // general, brand, product, faq, campaign
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, processing, inactive
            $table->string('source_type')->nullable(); // text, file, url
            $table->string('source_url')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('total_chunks')->default(0);
            $table->integer('total_words')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};

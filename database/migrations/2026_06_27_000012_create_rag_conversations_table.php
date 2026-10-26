<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rag_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('mode')->default('chat'); // chat, campaign, insights
            $table->string('title')->nullable();
            $table->json('messages')->nullable(); // [{role, content, sources, tokens, timestamp}]
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->integer('total_tokens')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_conversations');
    }
};

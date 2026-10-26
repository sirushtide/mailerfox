<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unibox_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('author_name')->nullable();
            $table->text('content');
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('unibox_conversations')->onDelete('cascade');
            $table->index('conversation_id');
        });

        Schema::create('unibox_contact_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('type'); // replied, opened, viewed, etc.
            $table->string('description');
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('unibox_contacts')->onDelete('cascade');
            $table->index(['contact_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unibox_contact_activities');
        Schema::dropIfExists('unibox_notes');
    }
};

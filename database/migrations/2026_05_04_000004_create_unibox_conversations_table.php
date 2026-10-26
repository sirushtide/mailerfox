<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unibox_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable(); // customer_id of agent
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('subject');
            $table->string('thread_id')->nullable()->index();
            $table->enum('status', ['open', 'resolved', 'snoozed', 'spam', 'trash'])->default('open');
            $table->enum('folder', ['inbox', 'sent', 'drafts', 'snoozed', 'archive', 'spam', 'trash'])->default('inbox');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->json('tags')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->json('participants')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('unibox_accounts')->onDelete('cascade');
            $table->index(['customer_id', 'folder']);
            $table->index(['customer_id', 'status']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unibox_conversations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unibox_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('message_id')->nullable()->index();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->json('to')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->enum('type', ['inbound', 'outbound', 'note'])->default('inbound');
            $table->enum('status', ['sent', 'draft', 'failed', 'received'])->default('received');
            $table->json('attachments')->nullable();
            $table->string('in_reply_to')->nullable();
            $table->json('headers')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('unibox_conversations')->onDelete('cascade');
            $table->index('conversation_id');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unibox_messages');
    }
};

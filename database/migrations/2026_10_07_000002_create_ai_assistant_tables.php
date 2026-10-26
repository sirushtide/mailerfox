<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_agents')) {
            Schema::table('ai_agents', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_agents', 'channels')) {
                    $table->json('channels')->nullable()->after('instructions');
                }
                if (!Schema::hasColumn('ai_agents', 'tools')) {
                    $table->json('tools')->nullable()->after('channels');
                }
                if (!Schema::hasColumn('ai_agents', 'autonomy')) {
                    $table->string('autonomy', 16)->default('suggest')->after('tools');
                }
                if (!Schema::hasColumn('ai_agents', 'widget_enabled')) {
                    $table->boolean('widget_enabled')->default(false)->after('autonomy');
                }
            });
        }

        Schema::create('ai_assistant_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('title')->nullable();
            $table->string('channel', 32)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'channel']);
        });

        Schema::create('ai_assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->string('role', 16); // user, assistant, tool, system
            $table->longText('content')->nullable();
            $table->string('tool_name')->nullable();
            $table->json('tool_result')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->foreign('conversation_id')->references('id')->on('ai_assistant_conversations')->cascadeOnDelete();
        });

        Schema::create('ai_reply_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('channel', 32);
            $table->unsignedBigInteger('thread_id')->nullable();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->longText('draft')->nullable();
            $table->string('status', 32)->default('pending'); // pending, sent, dismissed
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['channel', 'thread_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_reply_drafts');
        Schema::dropIfExists('ai_assistant_messages');
        Schema::dropIfExists('ai_assistant_conversations');

        if (Schema::hasTable('ai_agents')) {
            Schema::table('ai_agents', function (Blueprint $table) {
                foreach (['channels', 'tools', 'autonomy', 'widget_enabled'] as $column) {
                    if (Schema::hasColumn('ai_agents', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

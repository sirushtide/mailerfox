<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->string('provider')->default('chatgpt');
            $table->string('model')->nullable();
            $table->string('avatar_color')->default('#1E5FEA');
            $table->string('status')->default('active'); // active, inactive
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('ai_agent_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->constrained('ai_agents')->cascadeOnDelete();
            $table->foreignId('knowledge_base_id')->constrained('knowledge_bases')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ai_agent_id', 'knowledge_base_id']);
        });

        Schema::table('rag_conversations', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('admin_user_id')->constrained('ai_agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rag_conversations', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropColumn('agent_id');
        });

        Schema::dropIfExists('ai_agent_knowledge_base');
        Schema::dropIfExists('ai_agents');
    }
};

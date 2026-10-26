<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->integer('chunk_index')->default(0);
            $table->text('content');
            $table->integer('word_count')->default(0);
            $table->string('title')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['knowledge_base_id', 'chunk_index']);
        });

        // Full-text index for RAG retrieval — MySQL 5.7+ InnoDB only
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE knowledge_chunks ADD FULLTEXT INDEX knowledge_chunks_content_fulltext (content)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};

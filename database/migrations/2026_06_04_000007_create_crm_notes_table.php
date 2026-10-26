<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->morphs('notable');
            $table->foreignId('created_by')->nullable()->constrained('workspace_members')->nullOnDelete();
            $table->text('content');
            $table->string('type')->default('note');
            $table->timestamps();

            $table->index('workspace_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_notes');
    }
};

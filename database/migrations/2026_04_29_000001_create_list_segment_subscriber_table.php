<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('list_segment_subscriber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('list_segments')->onDelete('cascade');
            $table->foreignId('subscriber_id')->constrained('list_subscribers')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['segment_id', 'subscriber_id']);
            $table->index('segment_id');
            $table->index('subscriber_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_segment_subscriber');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visitor_tracking_session_id');
            $table->string('event_type', 30);
            $table->text('url')->nullable();
            $table->string('route_name')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('element_label')->nullable();
            $table->string('element_selector')->nullable();
            $table->string('element_track_id')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('created_at');

            $table->foreign('visitor_tracking_session_id', 'vt_events_session_fk')
                ->references('id')->on('visitor_tracking_sessions')->onDelete('cascade');
            $table->index('visitor_tracking_session_id', 'vt_events_session_idx');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_tracking_events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_uid', 36)->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedBigInteger('ip_geolocation_id')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 4)->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_guest')->default(true);
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('page_views_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('ip_geolocation_id')->references('id')->on('ip_geolocations')->onDelete('set null');
            $table->index('session_uid');
            $table->index('customer_id');
            $table->index('ip');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_tracking_sessions');
    }
};

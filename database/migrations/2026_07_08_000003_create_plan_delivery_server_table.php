<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan_delivery_server')) {
            return;
        }

        Schema::create('plan_delivery_server', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->foreignId('delivery_server_id')->constrained('delivery_servers')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['plan_id', 'delivery_server_id'], 'plan_del_srv_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_delivery_server');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unibox_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('name');
            $table->string('color')->default('#6366f1');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        Schema::create('unibox_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('customer_id');
            $table->enum('role', ['owner', 'agent'])->default('agent');
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('unibox_teams')->onDelete('cascade');
            $table->unique(['team_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unibox_team_members');
        Schema::dropIfExists('unibox_teams');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supabase_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('connection_type')->default('hosted'); // hosted, self_hosted
            $table->string('project_url');
            $table->string('project_id')->nullable();
            $table->text('anon_key');
            $table->text('service_role_key')->nullable();
            $table->text('db_password')->nullable();
            $table->string('region')->nullable();
            $table->string('status')->default('disconnected'); // connected, error, disconnected
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supabase_integrations');
    }
};

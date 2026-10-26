<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_supabase_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('connection_type', 30)->default('hosted');
            $table->string('project_url')->nullable();
            $table->string('project_id')->nullable();
            $table->text('anon_key')->nullable();
            $table->text('service_role_key')->nullable();
            $table->string('db_password')->nullable();
            $table->string('region', 100)->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('disconnected');
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('is_default');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_supabase_configs');
    }
};

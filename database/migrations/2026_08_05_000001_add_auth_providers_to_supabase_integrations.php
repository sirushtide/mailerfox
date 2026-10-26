<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supabase_integrations', function (Blueprint $table) {
            if (! Schema::hasColumn('supabase_integrations', 'auth_providers')) {
                $table->json('auth_providers')->nullable()->after('last_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supabase_integrations', function (Blueprint $table) {
            $table->dropColumn('auth_providers');
        });
    }
};
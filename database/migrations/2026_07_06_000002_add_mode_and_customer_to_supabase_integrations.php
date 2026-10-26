<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supabase_integrations', function (Blueprint $table) {
            if (! Schema::hasColumn('supabase_integrations', 'mode')) {
                $table->string('mode', 30)->default('byos_manual')->after('connection_type');
            }
            if (! Schema::hasColumn('supabase_integrations', 'admin_supabase_config_id')) {
                $table->unsignedBigInteger('admin_supabase_config_id')->nullable()->after('mode');
                $table->foreign('admin_supabase_config_id')->references('id')->on('admin_supabase_configs')->nullOnDelete();
            }
            if (! Schema::hasColumn('supabase_integrations', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('page_id');
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            }
            if (! Schema::hasColumn('supabase_integrations', 'access_token')) {
                $table->text('access_token')->nullable()->after('region');
            }
            if (! Schema::hasColumn('supabase_integrations', 'refresh_token')) {
                $table->text('refresh_token')->nullable()->after('access_token');
            }
            if (! Schema::hasColumn('supabase_integrations', 'token_expires_at')) {
                $table->timestamp('token_expires_at')->nullable()->after('refresh_token');
            }

            $table->index(['mode', 'status']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('supabase_integrations', function (Blueprint $table) {
            $table->dropColumn([
                'mode',
                'admin_supabase_config_id',
                'customer_id',
                'access_token',
                'refresh_token',
                'token_expires_at',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_servers') && !Schema::hasColumn('delivery_servers', 'workspace_id')) {
            Schema::table('delivery_servers', function (Blueprint $table) {
                $table->unsignedBigInteger('workspace_id')->nullable()->after('customer_id');
                $table->index('workspace_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_servers') && Schema::hasColumn('delivery_servers', 'workspace_id')) {
            Schema::table('delivery_servers', function (Blueprint $table) {
                $table->dropIndex(['workspace_id']);
                $table->dropColumn('workspace_id');
            });
        }
    }
};

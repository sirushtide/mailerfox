<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'settings')) {
                $table->json('settings')->nullable()->after('limits');
            }
            if (!Schema::hasColumn('plans', 'permissions')) {
                $table->json('permissions')->nullable()->after('settings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'settings')) {
                $table->dropColumn('settings');
            }
            if (Schema::hasColumn('plans', 'permissions')) {
                $table->dropColumn('permissions');
            }
        });
    }
};

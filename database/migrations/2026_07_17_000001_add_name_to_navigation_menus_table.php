<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('navigation_menus', function (Blueprint $table) {
            $table->string('name')->nullable()->after('key');
        });

        // Backfill the existing public_main menu with a name
        \DB::table('navigation_menus')
            ->whereNull('name')
            ->where('key', 'public_main')
            ->update(['name' => 'Main Menu']);
    }

    public function down(): void
    {
        Schema::table('navigation_menus', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};

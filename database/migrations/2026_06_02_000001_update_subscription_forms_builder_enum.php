<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE subscription_forms MODIFY builder VARCHAR(50) NOT NULL DEFAULT 'basic'");
            return;
        }

        if ($driver === 'sqlite') {
            // SQLite already uses VARCHAR from the previous migration, nothing to do.
            return;
        }

        // Other drivers: ensure it's a plain string.
        Schema::table('subscription_forms', function ($table) {
            $table->string('builder', 50)->default('basic')->change();
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE subscription_forms MODIFY builder ENUM('basic','unlayer','colt') NOT NULL DEFAULT 'basic'");
            return;
        }

        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('subscription_forms', function ($table) {
            $table->enum('builder', ['basic', 'unlayer', 'colt'])->default('basic')->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * When the previous migration renamed delivery_servers -> delivery_servers_old,
     * SQLite automatically updated FK references in all child tables to point to
     * delivery_servers_old. After we dropped that temp table the FKs became broken.
     * This migration patches sqlite_master to restore the correct FK target name.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('PRAGMA writable_schema = ON');

        // Replace every occurrence of "delivery_servers_old" in schema SQL
        // (quoted and unquoted variants) so FK constraints point to the correct table.
        DB::statement("UPDATE sqlite_master
            SET sql = REPLACE(REPLACE(sql,
                '\"delivery_servers_old\"', '\"delivery_servers\"'),
                'delivery_servers_old', 'delivery_servers')
            WHERE sql LIKE '%delivery_servers_old%'
              AND type IN ('table', 'trigger', 'index', 'view')");

        DB::statement('PRAGMA writable_schema = OFF');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Not reversible
    }
};

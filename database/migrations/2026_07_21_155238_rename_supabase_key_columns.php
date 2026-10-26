<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE supabase_integrations CHANGE COLUMN anon_key publishable_key TEXT NOT NULL, CHANGE COLUMN service_role_key secret_key TEXT NULL');
            DB::statement('ALTER TABLE admin_supabase_configs CHANGE COLUMN anon_key publishable_key TEXT NULL, CHANGE COLUMN service_role_key secret_key TEXT NULL');
        } else {
            DB::statement('ALTER TABLE supabase_integrations RENAME COLUMN anon_key TO publishable_key');
            DB::statement('ALTER TABLE supabase_integrations RENAME COLUMN service_role_key TO secret_key');
            DB::statement('ALTER TABLE admin_supabase_configs RENAME COLUMN anon_key TO publishable_key');
            DB::statement('ALTER TABLE admin_supabase_configs RENAME COLUMN service_role_key TO secret_key');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE supabase_integrations CHANGE COLUMN publishable_key anon_key TEXT NOT NULL, CHANGE COLUMN secret_key service_role_key TEXT NULL');
            DB::statement('ALTER TABLE admin_supabase_configs CHANGE COLUMN publishable_key anon_key TEXT NULL, CHANGE COLUMN secret_key service_role_key TEXT NULL');
        } else {
            DB::statement('ALTER TABLE supabase_integrations RENAME COLUMN publishable_key TO anon_key');
            DB::statement('ALTER TABLE supabase_integrations RENAME COLUMN secret_key TO service_role_key');
            DB::statement('ALTER TABLE admin_supabase_configs RENAME COLUMN publishable_key TO anon_key');
            DB::statement('ALTER TABLE admin_supabase_configs RENAME COLUMN secret_key TO service_role_key');
        }
    }
};

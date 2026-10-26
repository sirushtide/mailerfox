<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildForSqlite();
        } else {
            try {
                DB::statement("ALTER TABLE delivery_servers MODIFY type ENUM('smtp','sendmail','gmail','outlook','amazon-ses','mailgun','sendgrid','postmark','sparkpost','zeptomail','zeptomail-api','gmail-api') DEFAULT 'smtp'");
            } catch (\Throwable $e) {
                // Already applied or unsupported driver
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'sqlite') {
            try {
                DB::statement("ALTER TABLE delivery_servers MODIFY type ENUM('smtp','sendmail','gmail','outlook','amazon-ses','mailgun','sendgrid','postmark','sparkpost','zeptomail','zeptomail-api') DEFAULT 'smtp'");
            } catch (\Throwable $e) {
            }
        }
    }

    private function rebuildForSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('PRAGMA legacy_alter_table = ON'); // Prevent FK ref auto-update on rename

        DB::statement('ALTER TABLE delivery_servers RENAME TO delivery_servers_old');
        DB::statement('PRAGMA legacy_alter_table = OFF');

        DB::statement('CREATE TABLE "delivery_servers" (
            "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
            "customer_id" integer NULL,
            "name" varchar NOT NULL,
            "is_primary" tinyint(1) NOT NULL DEFAULT 0,
            "type" varchar NOT NULL DEFAULT \'smtp\' CHECK("type" in (\'smtp\',\'sendmail\',\'gmail\',\'outlook\',\'amazon-ses\',\'mailgun\',\'sendgrid\',\'postmark\',\'sparkpost\',\'zeptomail\',\'zeptomail-api\',\'gmail-api\')),
            "status" varchar NOT NULL DEFAULT \'pending\' CHECK("status" in (\'active\',\'inactive\',\'pending\')),
            "hostname" varchar NULL,
            "port" integer NOT NULL DEFAULT 587,
            "username" varchar NULL,
            "password" varchar NULL,
            "encryption" varchar NOT NULL DEFAULT \'tls\' CHECK("encryption" in (\'ssl\',\'tls\',\'none\')),
            "from_email" varchar NULL,
            "from_name" varchar NULL,
            "reply_to_email" varchar NULL,
            "timeout" integer NOT NULL DEFAULT 30,
            "max_connection_messages" integer NOT NULL DEFAULT 100,
            "second_quota" integer NOT NULL DEFAULT 0,
            "minute_quota" integer NOT NULL DEFAULT 0,
            "hourly_quota" integer NOT NULL DEFAULT 0,
            "daily_quota" integer NOT NULL DEFAULT 0,
            "monthly_quota" integer NOT NULL DEFAULT 0,
            "pause_after_send" integer NOT NULL DEFAULT 0,
            "settings" text NULL,
            "locked" tinyint(1) NOT NULL DEFAULT 0,
            "use_for" tinyint(1) NOT NULL DEFAULT 1,
            "use_for_email_to_list" tinyint(1) NOT NULL DEFAULT 0,
            "use_for_transactional" tinyint(1) NOT NULL DEFAULT 0,
            "bounce_server_id" integer NULL,
            "tracking_domain_id" integer NULL,
            "notes" text NULL,
            "verification_token" varchar NULL,
            "verified_at" datetime NULL,
            "created_at" datetime NULL,
            "updated_at" datetime NULL,
            "deleted_at" datetime NULL
        )');

        DB::statement('INSERT INTO delivery_servers SELECT
            id, customer_id, name,
            COALESCE(is_primary, 0),
            type, status, hostname, port, username, password, encryption,
            from_email, from_name, reply_to_email, timeout, max_connection_messages,
            COALESCE(second_quota, 0), COALESCE(minute_quota, 0),
            hourly_quota, daily_quota, monthly_quota, pause_after_send,
            settings, locked, use_for, use_for_email_to_list, use_for_transactional,
            bounce_server_id, tracking_domain_id, notes,
            verification_token, verified_at, created_at, updated_at, deleted_at
        FROM delivery_servers_old');

        DB::statement('DROP TABLE delivery_servers_old');

        DB::statement('CREATE INDEX delivery_servers_type_index ON delivery_servers (type)');
        DB::statement('CREATE INDEX delivery_servers_status_index ON delivery_servers (status)');
        DB::statement('CREATE INDEX delivery_servers_is_primary_index ON delivery_servers (is_primary)');
        DB::statement('CREATE INDEX delivery_servers_customer_id_index ON delivery_servers (customer_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};

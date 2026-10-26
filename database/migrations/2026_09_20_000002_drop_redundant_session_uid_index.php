<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The unique constraint on session_uid already creates an index; the
        // extra index just adds write overhead on a high-insert table.
        if (Schema::hasTable('visitor_tracking_sessions')) {
            Schema::table('visitor_tracking_sessions', function ($table) {
                try {
                    $table->dropIndex(['session_uid']);
                } catch (\Throwable) {
                    // Index may not exist on this installation.
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('visitor_tracking_sessions')) {
            Schema::table('visitor_tracking_sessions', function ($table) {
                $table->index('session_uid');
            });
        }
    }
};

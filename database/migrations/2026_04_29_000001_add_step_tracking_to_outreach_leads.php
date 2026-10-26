<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outreach_leads', function (Blueprint $table) {
            $table->unsignedInteger('current_step')->default(0)->after('status');
            $table->timestamp('next_send_at')->nullable()->after('current_step');
            $table->timestamp('sequence_completed_at')->nullable()->after('next_send_at');

            $table->index(['campaign_id', 'next_send_at']);
            $table->index(['campaign_id', 'current_step']);
        });
    }

    public function down(): void
    {
        Schema::table('outreach_leads', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'next_send_at']);
            $table->dropIndex(['campaign_id', 'current_step']);
            $table->dropColumn(['current_step', 'next_send_at', 'sequence_completed_at']);
        });
    }
};

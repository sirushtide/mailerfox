<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_recipients')) {
            return;
        }

        // Remove duplicate rows (same campaign + email, case-insensitive) before
        // adding the unique constraint, keeping the earliest recipient per email.
        //
        // The keep-list is wrapped in a derived table because MySQL forbids
        // selecting from the same table that is being deleted from in a
        // subquery (error 1093).
        $keepIds = DB::table('campaign_recipients')
            ->selectRaw('MIN(id) as id')
            ->groupBy('campaign_id')
            ->groupByRaw('LOWER(TRIM(email))');

        DB::table('campaign_recipients')
            ->whereNotIn('id', function ($query) use ($keepIds) {
                $query->select('id')->fromSub($keepIds, 'keep_campaign_recipient_ids');
            })
            ->delete();

        try {
            Schema::table('campaign_recipients', function (Blueprint $table) {
                $table->unique(['campaign_id', 'email'], 'campaign_recipients_campaign_email_unique');
            });
        } catch (\Throwable $e) {
            // Index already exists (e.g. re-run or pre-existing constraint).
            Log::warning('Could not add unique index on campaign_recipients', ['message' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        try {
            Schema::table('campaign_recipients', function (Blueprint $table) {
                $table->dropUnique('campaign_recipients_campaign_email_unique');
            });
        } catch (\Throwable) {
            // Index does not exist.
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('plans', 'customer_group_id')) {
            return;
        }

        DB::table('plans')
            ->whereNotNull('customer_group_id')
            ->whereNull('settings')
            ->orWhereNull('permissions')
            ->eachById(function ($plan) {
                $group = DB::table('customer_groups')
                    ->where('id', $plan->customer_group_id)
                    ->first();

                if (!$group) {
                    return;
                }

                $settings = null;
                if (is_string($group->settings)) {
                    $settings = json_decode($group->settings, true);
                } elseif (!empty($group->settings)) {
                    $settings = (array) $group->settings;
                }

                $permissions = null;
                if (is_string($group->permissions)) {
                    $permissions = json_decode($group->permissions, true);
                } elseif (!empty($group->permissions)) {
                    $permissions = (array) $group->permissions;
                }

                DB::table('plans')
                    ->where('id', $plan->id)
                    ->update([
                        'settings' => $settings ? json_encode($settings) : null,
                        'permissions' => $permissions ? json_encode($permissions) : null,
                    ]);
            });
    }

    public function down(): void
    {
        // No-op: backfill is forward-only data migration.
    }
};

<?php

use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $systemSlugs = ['superadmin', 'admin', 'author', 'moderator', 'support-engineer', 'customer'];

        foreach ($systemSlugs as $slug) {
            $roles = Role::where('slug', 'like', $slug . '%')
                ->orderBy('id')
                ->get();

            if ($roles->count() <= 1) {
                continue;
            }

            $primary = $roles->first();
            $duplicates = $roles->slice(1);

            foreach ($duplicates as $duplicate) {
                $userIds = DB::table('role_user')
                    ->where('role_id', $duplicate->id)
                    ->pluck('user_id')
                    ->unique()
                    ->values()
                    ->all();

                foreach ($userIds as $userId) {
                    DB::table('role_user')->updateOrInsert(
                        ['role_id' => $primary->id, 'user_id' => $userId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }

                DB::table('role_user')->where('role_id', $duplicate->id)->delete();
                $duplicate->delete();
            }
        }

        app(RoleService::class)->ensureDefaultRoles();
    }

    public function down(): void
    {
        // No-op.
    }
};

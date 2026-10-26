<?php

use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the legacy 'admin.*' wildcard from all role permissions because it
        // unintentionally matches every admin ability (e.g. admin.roles.access).
        foreach (Role::all() as $role) {
            $permissions = (array) ($role->permissions ?? []);
            $permissions = array_values(array_filter($permissions, fn ($p) => $p !== 'admin.*'));
            $role->update(['permissions' => $permissions]);
        }

        // Merge duplicate backfilled admin roles into a single role and reassign users.
        $adminRoles = Role::where('slug', 'like', 'admin%')->orderBy('id')->get();
        if ($adminRoles->count() > 1) {
            $primary = $adminRoles->first();
            $duplicates = $adminRoles->slice(1);

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

        // Refresh default role definitions so system roles get the intended permissions.
        app(RoleService::class)->ensureDefaultRoles();
    }

    public function down(): void
    {
        // No-op.
    }
};

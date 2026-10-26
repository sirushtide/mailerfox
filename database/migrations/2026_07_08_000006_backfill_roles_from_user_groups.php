<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('user_groups') || !DB::getSchemaBuilder()->hasTable('user_user_group')) {
            return;
        }

        $groups = DB::table('user_groups')->get();
        $usedSlugs = [];

        foreach ($groups as $group) {
            $baseSlug = Str::slug($group->name);
            $slug = $baseSlug;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true) || DB::table('roles')->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $usedSlugs[] = $slug;
            $permissions = json_decode($group->permissions ?? '[]', true);

            $roleId = DB::table('roles')->insertGetId([
                'name' => $group->name,
                'slug' => $slug,
                'description' => $group->description,
                'permissions' => json_encode($permissions ?: []),
                'is_system' => (bool) $group->is_system,
                'is_default' => false,
                'guard_name' => 'admin',
                'created_at' => $group->created_at ?? now(),
                'updated_at' => $group->updated_at ?? now(),
            ]);

            $userIds = DB::table('user_user_group')
                ->where('user_group_id', $group->id)
                ->pluck('user_id')
                ->unique()
                ->filter(fn ($id) => (int) $id > 0)
                ->values()
                ->all();

            foreach ($userIds as $userId) {
                DB::table('role_user')->insertOrIgnore([
                    'role_id' => $roleId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_user')->delete();
        DB::table('roles')->delete();
    }
};

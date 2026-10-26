<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleService
{
    /**
     * The default system roles with their default permissions.
     */
    public function defaultRoles(): array
    {
        return [
            'superadmin' => [
                'name' => 'Superadmin',
                'description' => 'Full access to all system actions including role management.',
                'permissions' => ['*'],
            ],
            'admin' => [
                'name' => 'Admin',
                'description' => 'Administrator with full access to manage the application. Role management is still restricted to superadmins.',
                'permissions' => ['*'],
            ],
            'author' => [
                'name' => 'Author',
                'description' => 'Can create and manage content such as templates and pages.',
                'permissions' => [
                    'admin.dashboard.*',
                    'admin.templates.*',
                    'admin.pages.*',
                    'admin.campaigns.access',
                    'admin.campaigns.create',
                    'admin.campaigns.edit',
                    'admin.campaigns.view',
                    'admin.lists.access',
                    'admin.lists.view',
                    'admin.automations.access',
                    'admin.automations.view',
                    'admin.autoresponders.access',
                    'admin.autoresponders.view',
                    'admin.support.*',
                ],
            ],
            'moderator' => [
                'name' => 'Moderator',
                'description' => 'Can review and moderate campaigns, lists, and support.',
                'permissions' => [
                    'admin.dashboard.*',
                    'admin.campaigns.*',
                    'admin.lists.*',
                    'admin.templates.access',
                    'admin.templates.view',
                    'admin.automations.access',
                    'admin.automations.view',
                    'admin.autoresponders.access',
                    'admin.autoresponders.view',
                    'admin.support.*',
                    'admin.customers.access',
                    'admin.customers.view',
                    'admin.email_validation.*',
                ],
            ],
            'support_engineer' => [
                'name' => 'Support Engineer',
                'slug' => 'support-engineer',
                'description' => 'Can view users, customers, and manage support tickets.',
                'permissions' => [
                    'admin.dashboard.*',
                    'admin.support.*',
                    'admin.users.access',
                    'admin.users.view',
                    'admin.customers.access',
                    'admin.customers.view',
                    'admin.email_validation.access',
                    'admin.email_validation.view',
                    'admin.servers.access',
                    'admin.servers.view',
                ],
            ],
            'customer' => [
                'name' => 'Customer',
                'guard_name' => 'web',
                'description' => 'Customer role used to group subscription plans. This role is not assigned to admin users.',
                'permissions' => [],
            ],
        ];
    }

    /**
     * Ensure all default system roles exist in the database.
     */
    public function ensureDefaultRoles(): void
    {
        foreach ($this->defaultRoles() as $slug => $data) {
            $roleSlug = $data['slug'] ?? $slug;

            $role = Role::withTrashed()
                ->where('slug', $roleSlug)
                ->first();

            if (!$role) {
                Role::create([
                    'name' => $data['name'],
                    'slug' => $roleSlug,
                    'description' => $data['description'] ?? null,
                    'permissions' => $data['permissions'] ?? [],
                    'is_system' => true,
                    'is_default' => false,
                    'guard_name' => $data['guard_name'] ?? 'admin',
                ]);
                continue;
            }

            if ($role->trashed()) {
                $role->restore();
            }

            // Only set default permissions when the role is first created so
            // customizations made by admins are not overwritten later.
            $role->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? $role->description,
                'is_system' => true,
                'guard_name' => $data['guard_name'] ?? 'admin',
            ]);
        }
    }

    /**
     * Get all admin roles for select dropdowns.
     */
    public function getAdminRolesForSelect(): array
    {
        $this->ensureDefaultRoles();

        return Role::admin()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get all admin roles as a collection.
     */
    public function getAdminRoles(): Collection
    {
        $this->ensureDefaultRoles();

        return Role::admin()->orderBy('name')->get();
    }

    /**
     * Assign the superadmin role to a user.
     */
    public function assignSuperAdmin(User $user): void
    {
        $this->ensureDefaultRoles();
        $role = Role::where('slug', 'superadmin')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Sync roles for a user by role IDs.
     *
     * @param array<int, mixed> $roleIds
     */
    public function syncUserRoles(User $user, array $roleIds): void
    {
        $validIds = collect($roleIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $user->roles()->sync($validIds);
    }
}

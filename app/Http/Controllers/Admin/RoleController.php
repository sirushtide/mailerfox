<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService
    ) {
    }

    private function authorizeRoleManagement(): void
    {
        $user = auth('admin')->user();

        if (!$user || !$user->isSuperAdmin()) {
            abort(403, __('Only Superadmins can manage roles.'));
        }
    }

    public function index(): View
    {
        $roles = Role::orderByRaw("CASE WHEN slug = 'superadmin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(15);

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->authorizeRoleManagement();
        $permissionGroups = $this->permissionGroups();

        return view('admin.roles.form', compact('permissionGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRoleManagement();
        $validated = $this->validated($request);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'] ?? [],
            'is_system' => false,
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'guard_name' => 'admin',
        ]);

        if ($role->is_default) {
            Role::admin()->where('id', '!=', $role->id)->update(['is_default' => false]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('Role ":name" created.', ['name' => $role->name]));
    }

    public function edit(Role $role): View
    {
        $this->authorizeRoleManagement();
        $permissionGroups = $this->permissionGroups();

        return view('admin.roles.form', compact('role', 'permissionGroups'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeRoleManagement();
        $validated = $this->validated($request, $role);

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $role->slug,
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'] ?? [],
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ]);

        if ($role->is_default) {
            Role::admin()->where('id', '!=', $role->id)->update(['is_default' => false]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('Role ":name" updated.', ['name' => $role->name]));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizeRoleManagement();

        if ($role->is_system) {
            return back()->with('error', __('System roles cannot be deleted.'));
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', __('Cannot delete a role that is assigned to users.'));
        }

        $name = $role->name;
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('Role ":name" deleted.', ['name' => $name]));
    }

    /**
     * Build the permission groups available for admin roles.
     */
    private function permissionGroups(): array
    {
        $actions = [
            ['key' => 'access', 'label' => 'Access'],
            ['key' => 'create', 'label' => 'Create'],
            ['key' => 'edit', 'label' => 'Edit'],
            ['key' => 'delete', 'label' => 'Delete'],
        ];

        $groups = [
            ['label' => 'Dashboard', 'prefix' => 'admin.dashboard', 'actions' => ['access']],
            ['label' => 'Users', 'prefix' => 'admin.users', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Customers', 'prefix' => 'admin.customers', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Plans', 'prefix' => 'admin.plans', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Addons', 'prefix' => 'admin.addons', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Integrations', 'prefix' => 'admin.integrations', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Settings', 'prefix' => 'admin.settings', 'actions' => ['access', 'edit', 'view']],
            ['label' => 'Templates', 'prefix' => 'admin.templates', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Campaigns', 'prefix' => 'admin.campaigns', 'actions' => ['access', 'create', 'edit', 'delete', 'view', 'send']],
            ['label' => 'Email Lists', 'prefix' => 'admin.lists', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Automations', 'prefix' => 'admin.automations', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Auto Responders', 'prefix' => 'admin.autoresponders', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Delivery Servers', 'prefix' => 'admin.delivery_servers', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Bounce Servers', 'prefix' => 'admin.bounce_servers', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Reply Servers', 'prefix' => 'admin.reply_servers', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Sending Domains', 'prefix' => 'admin.sending_domains', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Tracking Domains', 'prefix' => 'admin.tracking_domains', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Email Validation', 'prefix' => 'admin.email_validation', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Support', 'prefix' => 'admin.support', 'actions' => ['access', 'create', 'edit', 'delete', 'view', 'reply', 'close']],
            ['label' => 'RAG / AI', 'prefix' => 'admin.rag', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Pages', 'prefix' => 'admin.pages', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Workspaces', 'prefix' => 'admin.workspaces', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Outreach', 'prefix' => 'admin.outreach', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Scraper', 'prefix' => 'admin.scraper', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Enrichment', 'prefix' => 'admin.enrichment', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Supabase', 'prefix' => 'admin.supabase', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Warmups', 'prefix' => 'admin.warmups', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Billing', 'prefix' => 'admin.billing', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Affiliates', 'prefix' => 'admin.affiliates', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'Reports', 'prefix' => 'admin.reports', 'actions' => ['access', 'view']],
            ['label' => 'Unibox', 'prefix' => 'admin.unibox', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
            ['label' => 'API', 'prefix' => 'admin.api', 'actions' => ['access', 'create', 'delete', 'view']],
            ['label' => 'Roles', 'prefix' => 'admin.roles', 'actions' => ['access', 'create', 'edit', 'delete', 'view']],
        ];

        $result = [];
        foreach ($groups as $group) {
            $perms = [];
            foreach ($group['actions'] as $action) {
                $perms[$action] = "{$group['prefix']}.{$action}";
            }
            $result[] = ['label' => $group['label'], 'perms' => $perms];
        }

        return [
            'actions' => $actions,
            'modules' => $result,
        ];
    }

    /**
     * Validate role request data.
     */
    private function validated(Request $request, ?Role $role = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:roles,slug' . ($role ? ',' . $role->id : '')],
            'description' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ];

        return $request->validate($rules);
    }
}

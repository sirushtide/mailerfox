<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Workspace;
use App\Models\WorkspaceActivityLog;
use App\Models\WorkspaceRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkspaceRoleController extends Controller
{
    public function index(Request $request, Workspace $workspace): View|RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

        $roles = $workspace->roles()->withCount('members')->orderBy('name')->get();
        $permissionGroups = WorkspaceRole::permissionGroups();

        return view('customer.workspaces.roles.index', compact('workspace', 'roles', 'permissionGroups'));
    }

    public function create(Request $request, Workspace $workspace): View|RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

        $permissionGroups = WorkspaceRole::permissionGroups();

        return view('customer.workspaces.roles.form', compact('workspace', 'permissionGroups'));
    }

    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

        $validated = $request->validate([
            'name'          => 'required|string|max:50',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
            'is_default'    => 'nullable|boolean',
        ]);

        $slug = Str::slug($validated['name']);
        if ($workspace->roles()->where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(4);
        }

        if (!empty($validated['is_default'])) {
            $workspace->roles()->update(['is_default' => false]);
        }

        $role = $workspace->roles()->create([
            'name'        => $validated['name'],
            'slug'        => $slug,
            'permissions' => $validated['permissions'] ?? [],
            'is_default'  => !empty($validated['is_default']),
        ]);

        $member = $workspace->memberForCustomer($customer);
        WorkspaceActivityLog::log($workspace->id, 'role.created', $member?->id, WorkspaceRole::class, $role->id, [
            'name' => $role->name,
        ]);

        return redirect()->route('customer.workspaces.roles.index', $workspace)
            ->with('success', __('Role ":name" created.', ['name' => $role->name]));
    }

    public function edit(Request $request, Workspace $workspace, WorkspaceRole $role): View|RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

        if ((int) $role->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $permissionGroups = WorkspaceRole::permissionGroups();

        return view('customer.workspaces.roles.form', compact('workspace', 'role', 'permissionGroups'));
    }

    public function update(Request $request, Workspace $workspace, WorkspaceRole $role): RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

        if ((int) $role->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:50',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
            'is_default'    => 'nullable|boolean',
        ]);

        if (!empty($validated['is_default'])) {
            $workspace->roles()->where('id', '!=', $role->id)->update(['is_default' => false]);
        }

        $role->update([
            'name'        => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
            'is_default'  => !empty($validated['is_default']),
        ]);

        return redirect()->route('customer.workspaces.roles.index', $workspace)
            ->with('success', __('Role ":name" updated.', ['name' => $role->name]));
    }

    public function destroy(Request $request, Workspace $workspace, WorkspaceRole $role): RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

        if ((int) $role->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        if ($role->members()->count() > 0) {
            return back()->with('error', __('Cannot delete a role that has members assigned. Reassign members first.'));
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('customer.workspaces.roles.index', $workspace)
            ->with('success', __('Role ":name" deleted.', ['name' => $name]));
    }

    private function authorizeManage(Workspace $workspace, $customer): void
    {
        if ($workspace->isOwner($customer)) {
            return;
        }

        $member = $workspace->memberForCustomer($customer);
        if (!$member || !$member->hasPermission('roles.manage')) {
            abort(403, __('You do not have permission to manage roles.'));
        }
    }
}

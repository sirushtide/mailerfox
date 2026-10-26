<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Workspace;
use App\Models\WorkspaceActivityLog;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();

        $ownedWorkspaces = Workspace::where('customer_id', $customer->id)
            ->withCount('members')
            ->latest()
            ->get();

        $memberWorkspaces = Workspace::whereHas('members', function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
              ->where('status', 'active');
        })
            ->where('customer_id', '!=', $customer->id)
            ->withCount('members')
            ->latest()
            ->get();

        return view('customer.workspaces.index', compact('ownedWorkspaces', 'memberWorkspaces'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        if (!$customer->groupAllows('workspaces.permissions.can_create_workspace')) {
            return redirect()->route('customer.dashboard')
                ->with('error', __('Your customer group does not allow creating workspaces.'));
        }

        $maxWorkspaces = (int) $customer->groupSetting('workspaces.limits.max_workspaces', 0);
        $currentCount = Workspace::where('customer_id', $customer->id)->count();

        if ($maxWorkspaces > 0 && $currentCount >= $maxWorkspaces) {
            return redirect()->route('customer.workspaces.index')
                ->with('error', __('You have reached the maximum number of workspaces (:max).', ['max' => $maxWorkspaces]));
        }

        return view('customer.workspaces.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        if (!$customer->groupAllows('workspaces.permissions.can_create_workspace')) {
            return redirect()->route('customer.dashboard')
                ->with('error', __('Your customer group does not allow creating workspaces.'));
        }

        $maxWorkspaces = (int) $customer->groupSetting('workspaces.limits.max_workspaces', 0);
        $currentCount = Workspace::where('customer_id', $customer->id)->count();

        if ($maxWorkspaces > 0 && $currentCount >= $maxWorkspaces) {
            return redirect()->route('customer.workspaces.index')
                ->with('error', __('You have reached the maximum number of workspaces (:max).', ['max' => $maxWorkspaces]));
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $workspace = Workspace::create([
            'customer_id' => $customer->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status'      => 'active',
        ]);

        $workspace->seedDefaultRoles();

        $adminRole = $workspace->roles()->where('slug', 'admin')->first();
        WorkspaceMember::create([
            'workspace_id'      => $workspace->id,
            'customer_id'       => $customer->id,
            'email'             => $customer->email,
            'name'              => $customer->full_name,
            'workspace_role_id' => $adminRole->id,
            'status'            => 'active',
            'accepted_at'       => now(),
        ]);

        WorkspaceActivityLog::log($workspace->id, 'workspace.created', null, Workspace::class, $workspace->id, [
            'name' => $workspace->name,
        ]);

        return redirect()->route('customer.workspaces.show', $workspace)
            ->with('success', __('Workspace ":name" created successfully.', ['name' => $workspace->name]));
    }

    public function show(Request $request, Workspace $workspace): View|RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeWorkspaceAccess($workspace, $customer);

        $workspace->loadCount(['members', 'activeMembers', 'emailLists', 'campaigns']);
        $member = $workspace->memberForCustomer($customer);

        $recentActivity = $workspace->activityLogs()
            ->with('member')
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('customer.workspaces.show', compact('workspace', 'member', 'recentActivity'));
    }

    public function edit(Request $request, Workspace $workspace): View|RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeWorkspaceOwner($workspace, $customer);

        return view('customer.workspaces.edit', compact('workspace'));
    }

    public function update(Request $request, Workspace $workspace): RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeWorkspaceOwner($workspace, $customer);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $workspace->update($validated);

        return redirect()->route('customer.workspaces.show', $workspace)
            ->with('success', __('Workspace updated successfully.'));
    }

    public function switchWorkspace(Request $request, Workspace $workspace): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (!$workspace->isActive()) {
            return back()->with('error', __('This workspace is not active.'));
        }

        if (!$workspace->isOwner($customer) && !$workspace->hasMember($customer)) {
            return back()->with('error', __('You are not a member of this workspace.'));
        }

        session(['active_workspace_id' => $workspace->id]);

        return redirect()->to(url('customer/w/' . $workspace->slug . '/dashboard'))
            ->with('success', __('Switched to workspace ":name".', ['name' => $workspace->name]));
    }

    public function switchPersonal(Request $request): RedirectResponse
    {
        session()->forget('active_workspace_id');
        session()->forget('active_workspace_member_id');

        return redirect()->to(url('customer/dashboard'))
            ->with('success', __('Switched to personal account.'));
    }

    private function authorizeWorkspaceAccess(Workspace $workspace, $customer): void
    {
        if (!$workspace->isOwner($customer) && !$workspace->hasMember($customer)) {
            abort(403, __('You do not have access to this workspace.'));
        }
    }

    private function authorizeWorkspaceOwner(Workspace $workspace, $customer): void
    {
        if (!$workspace->isOwner($customer)) {
            abort(403, __('Only the workspace owner can perform this action.'));
        }
    }
}

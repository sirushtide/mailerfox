<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Workspace;
use App\Models\Setting;
use App\Models\WorkspaceActivityLog;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class WorkspaceSettingsController extends Controller
{
    // ── General Tab ─────────────────────────────────────────────────

    public function general(Request $request): View|RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);

        return view('customer.workspace-settings.general', compact('workspace', 'member'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizeOwner($workspace);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z0-9_-]+$/', \Illuminate\Validation\Rule::unique('workspaces', 'slug')->ignore($workspace->id)],
            'description' => 'nullable|string|max:500',
        ]);

        $oldSlug = $workspace->slug;
        $workspace->update($validated);

        $this->logActivity($workspace, $member, 'workspace.updated', Workspace::class, $workspace->id, [
            'name' => $workspace->name,
        ]);

        if ($workspace->slug !== $oldSlug) {
            return redirect()->to(url('customer/w/' . $workspace->slug . '/workspace-settings'))
                ->with('success', __('Workspace settings updated.'));
        }

        return back()->with('success', __('Workspace settings updated.'));
    }

    // ── Members Tab ─────────────────────────────────────────────────

    public function members(Request $request): View|RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);

        $members = $workspace->members()
            ->with(['role', 'customer'])
            ->orderByDesc('accepted_at')
            ->get();

        $pendingInvitations = $workspace->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('role')
            ->latest()
            ->get();

        $roles = $workspace->roles()->orderBy('name')->get();

        $canManage = $workspace->isOwner(Auth::guard('customer')->user())
            || ($member && $member->hasPermission('members.manage'));

        return view('customer.workspace-settings.members', compact(
            'workspace', 'member', 'members', 'pendingInvitations', 'roles', 'canManage'
        ));
    }

    public function inviteMember(Request $request): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizeManageMembers($workspace, $member);

        $validated = $request->validate([
            'email'   => 'required|email|max:255',
            'role_id' => 'required|exists:workspace_roles,id',
        ]);

        $email = strtolower(trim($validated['email']));

        if ($workspace->members()->where('email', $email)->where('status', 'active')->exists()) {
            return back()->with('error', __('This person is already a member of the workspace.'));
        }

        $maxMembers = (int) Setting::get('workspace_max_members', 10);
        if ($maxMembers > 0 && $workspace->members()->where('status', 'active')->count() >= $maxMembers) {
            return back()->with('error', __('This workspace has reached the maximum number of members (:max).', ['max' => $maxMembers]));
        }

        $expiryDays = (int) Setting::get('workspace_invitation_expiry_days', 7);
        $customer = Auth::guard('customer')->user();

        $existingInvite = $workspace->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvite) {
            return back()->with('error', __('An active invitation already exists for this email.'));
        }

        $invitation = WorkspaceInvitation::create([
            'workspace_id'      => $workspace->id,
            'email'             => $email,
            'workspace_role_id' => $validated['role_id'],
            'invited_by'        => $member?->id,
            'expires_at'        => now()->addDays($expiryDays),
        ]);

        try {
            Mail::send('emails.workspace-invitation', [
                'workspace'  => $workspace,
                'invitation' => $invitation,
                'acceptUrl'  => route('workspace.invite.accept', $invitation->token),
                'inviter'    => $customer,
            ], function ($message) use ($email, $workspace) {
                $message->to($email)
                    ->subject(__('You\'ve been invited to join ":name" on MailerFox', ['name' => $workspace->name]));
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send workspace invitation email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        $this->logActivity($workspace, $member, 'member.invited', null, null, [
            'email' => $email,
            'role'  => $invitation->role?->name,
        ]);

        return back()->with('success', __('Invitation sent to :email.', ['email' => $email]));
    }

    public function updateMemberRole(Request $request, WorkspaceMember $member): RedirectResponse
    {
        [$workspace, $currentMember] = $this->resolveContext($request);
        $this->authorizeManageMembers($workspace, $currentMember);

        if ((int) $member->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        if ($member->isOwner()) {
            return back()->with('error', __('Cannot change the workspace owner\'s role.'));
        }

        $validated = $request->validate([
            'role_id' => 'required|exists:workspace_roles,id',
        ]);

        $member->update(['workspace_role_id' => $validated['role_id']]);

        $this->logActivity($workspace, $currentMember, 'member.role_changed', WorkspaceMember::class, $member->id, [
            'member_email' => $member->email,
            'new_role'     => $member->role?->name,
        ]);

        return back()->with('success', __('Role updated for :name.', ['name' => $member->displayName()]));
    }

    public function removeMember(Request $request, WorkspaceMember $member): RedirectResponse
    {
        [$workspace, $currentMember] = $this->resolveContext($request);
        $this->authorizeManageMembers($workspace, $currentMember);

        if ((int) $member->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        if ($member->isOwner()) {
            return back()->with('error', __('Cannot remove the workspace owner.'));
        }

        $name = $member->displayName();

        $this->logActivity($workspace, $currentMember, 'member.removed', null, null, [
            'member_email' => $member->email,
            'member_name'  => $name,
        ]);

        $member->delete();

        return back()->with('success', __(':name has been removed from the workspace.', ['name' => $name]));
    }

    public function cancelInvitation(Request $request, WorkspaceInvitation $invitation): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizeManageMembers($workspace, $member);

        if ((int) $invitation->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $invitation->delete();

        return back()->with('success', __('Invitation cancelled.'));
    }

    // ── Permissions (Roles) Tab ─────────────────────────────────────

    public function permissions(Request $request): View|RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizeOwner($workspace);

        $roles = $workspace->roles()->withCount('members')->orderBy('name')->get();
        $permissionGroups = WorkspaceRole::permissionGroups();

        return view('customer.workspace-settings.permissions', compact(
            'workspace', 'member', 'roles', 'permissionGroups'
        ));
    }

    public function storeRole(Request $request): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizeOwner($workspace);

        $validated = $request->validate([
            'name'          => 'required|string|max:50',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
            'is_default'    => 'nullable|boolean',
        ]);

        $slug = \Illuminate\Support\Str::slug($validated['name']);
        if ($workspace->roles()->where('slug', $slug)->exists()) {
            $slug .= '-' . \Illuminate\Support\Str::random(4);
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

        $this->logActivity($workspace, $member, 'role.created', WorkspaceRole::class, $role->id, [
            'name' => $role->name,
        ]);

        return redirect()->route('customer.workspace-settings.permissions')
            ->with('success', __('Role ":name" created.', ['name' => $role->name]));
    }

    public function updateRole(Request $request, WorkspaceRole $role): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizeOwner($workspace);

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

        return redirect()->route('customer.workspace-settings.permissions')
            ->with('success', __('Role ":name" updated.', ['name' => $role->name]));
    }

    public function destroyRole(Request $request, WorkspaceRole $role): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizeOwner($workspace);

        if ((int) $role->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        if ($role->members()->count() > 0) {
            return back()->with('error', __('Cannot delete a role that has members assigned.'));
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('customer.workspace-settings.permissions')
            ->with('success', __('Role ":name" deleted.', ['name' => $name]));
    }

    // ── Helpers ─────────────────────────────────────────────────────

    private function resolveContext(Request $request): array
    {
        if (!Addon::isActive('workspace')) {
            abort(404);
        }

        $workspace = $request->attributes->get('workspace');
        $member = $request->attributes->get('workspace_member');

        if (!$workspace) {
            abort(redirect()->route('customer.dashboard')
                ->with('error', __('Please select a workspace first.')));
        }

        return [$workspace, $member];
    }

    private function authorizeManageMembers(Workspace $workspace, ?WorkspaceMember $member): void
    {
        $customer = Auth::guard('customer')->user();
        if ($workspace->isOwner($customer)) {
            return;
        }

        if (!$member || !$member->hasPermission('members.manage')) {
            abort(403, __('You do not have permission to manage members.'));
        }
    }

    private function authorizeOwner(Workspace $workspace): void
    {
        $customer = Auth::guard('customer')->user();
        if (!$workspace->isOwner($customer)) {
            abort(403, __('Only the workspace owner can manage this setting.'));
        }
    }

    private function logActivity(Workspace $workspace, ?WorkspaceMember $member, string $action, ?string $subjectType = null, ?int $subjectId = null, ?array $meta = null): void
    {
        WorkspaceActivityLog::log($workspace->id, $action, $member?->id, $subjectType, $subjectId, $meta);
    }
}

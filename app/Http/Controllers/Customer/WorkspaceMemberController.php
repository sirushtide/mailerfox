<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Workspace;
use App\Models\WorkspaceActivityLog;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request, Workspace $workspace): View|RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeAccess($workspace, $customer);

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

        return view('customer.workspaces.members.index', compact('workspace', 'members', 'pendingInvitations', 'roles'));
    }

    public function invite(Request $request, Workspace $workspace): RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

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

        $existingInvite = $workspace->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvite) {
            return back()->with('error', __('An active invitation already exists for this email.'));
        }

        $currentMember = $workspace->memberForCustomer($customer);

        $invitation = WorkspaceInvitation::create([
            'workspace_id'      => $workspace->id,
            'email'             => $email,
            'workspace_role_id' => $validated['role_id'],
            'invited_by'        => $currentMember?->id,
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
                    ->subject(__('You\'ve been invited to join ":name" on MailPurse', ['name' => $workspace->name]));
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send workspace invitation email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        WorkspaceActivityLog::log($workspace->id, 'member.invited', $currentMember?->id, null, null, [
            'email' => $email,
            'role'  => $invitation->role?->name,
        ]);

        return back()->with('success', __('Invitation sent to :email.', ['email' => $email]));
    }

    public function updateRole(Request $request, Workspace $workspace, WorkspaceMember $member): RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

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

        $currentMember = $workspace->memberForCustomer($customer);
        WorkspaceActivityLog::log($workspace->id, 'member.role_changed', $currentMember?->id, WorkspaceMember::class, $member->id, [
            'member_email' => $member->email,
            'new_role'     => $member->role?->name,
        ]);

        return back()->with('success', __('Role updated for :name.', ['name' => $member->displayName()]));
    }

    public function remove(Request $request, Workspace $workspace, WorkspaceMember $member): RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

        if ((int) $member->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        if ($member->isOwner()) {
            return back()->with('error', __('Cannot remove the workspace owner.'));
        }

        $name = $member->displayName();

        $currentMember = $workspace->memberForCustomer($customer);
        WorkspaceActivityLog::log($workspace->id, 'member.removed', $currentMember?->id, null, null, [
            'member_email' => $member->email,
            'member_name'  => $name,
        ]);

        $member->delete();

        return back()->with('success', __(':name has been removed from the workspace.', ['name' => $name]));
    }

    public function cancelInvitation(Request $request, Workspace $workspace, WorkspaceInvitation $invitation): RedirectResponse
    {
        if (!Addon::isActive('workspace')) {
            return redirect()->route('customer.dashboard');
        }

        $customer = Auth::guard('customer')->user();
        $this->authorizeManage($workspace, $customer);

        if ((int) $invitation->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $invitation->delete();

        return back()->with('success', __('Invitation cancelled.'));
    }

    private function authorizeAccess(Workspace $workspace, $customer): void
    {
        if (!$workspace->isOwner($customer) && !$workspace->hasMember($customer)) {
            abort(403, __('You do not have access to this workspace.'));
        }
    }

    private function authorizeManage(Workspace $workspace, $customer): void
    {
        if ($workspace->isOwner($customer)) {
            return;
        }

        $member = $workspace->memberForCustomer($customer);
        if (!$member || !$member->hasPermission('members.manage')) {
            abort(403, __('You do not have permission to manage members.'));
        }
    }
}

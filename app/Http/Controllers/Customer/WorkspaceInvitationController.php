<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\WorkspaceActivityLog;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class WorkspaceInvitationController extends Controller
{
    public function accept(Request $request, string $token): View|RedirectResponse
    {
        $invitation = WorkspaceInvitation::where('token', $token)
            ->with(['workspace', 'role'])
            ->first();

        if (!$invitation) {
            abort(404, __('Invitation not found.'));
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('login')
                ->with('info', __('This invitation has already been accepted.'));
        }

        if ($invitation->isExpired()) {
            return redirect()->route('login')
                ->with('error', __('This invitation has expired. Please ask the workspace owner to send a new one.'));
        }

        $customer = Auth::guard('customer')->user();

        if ($customer) {
            return $this->acceptForCustomer($invitation, $customer);
        }

        $existingCustomer = Customer::where('email', $invitation->email)->first();
        if ($existingCustomer) {
            return redirect()->route('login')
                ->with('info', __('Please log in to accept the workspace invitation.'));
        }

        return view('customer.workspaces.accept-invitation', compact('invitation'));
    }

    public function acceptAndRegister(Request $request, string $token): RedirectResponse
    {
        $invitation = WorkspaceInvitation::where('token', $token)
            ->with(['workspace', 'role'])
            ->first();

        if (!$invitation || $invitation->isAccepted() || $invitation->isExpired()) {
            return redirect()->route('login')
                ->with('error', __('This invitation is no longer valid.'));
        }

        $existingCustomer = Customer::where('email', $invitation->email)->first();

        if ($existingCustomer) {
            return $this->acceptForCustomer($invitation, $existingCustomer);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $nameParts = explode(' ', $validated['name'], 2);

        $customer = Customer::create([
            'first_name'        => $nameParts[0],
            'last_name'         => $nameParts[1] ?? '',
            'email'             => $invitation->email,
            'password'          => Hash::make($validated['password']),
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $member = $this->createMember($invitation, $customer);
        $invitation->update(['accepted_at' => now()]);

        WorkspaceActivityLog::log($invitation->workspace_id, 'member.joined', $member->id, WorkspaceMember::class, $member->id, [
            'email' => $customer->email,
            'via'   => 'registration',
        ]);

        Auth::guard('customer')->login($customer);
        session(['active_workspace_id' => $invitation->workspace_id]);

        return redirect()->route('customer.workspaces.show', $invitation->workspace)
            ->with('success', __('Welcome to ":name"!', ['name' => $invitation->workspace->name]));
    }

    private function acceptForCustomer(WorkspaceInvitation $invitation, Customer $customer): RedirectResponse
    {
        $existing = WorkspaceMember::where('workspace_id', $invitation->workspace_id)
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            $invitation->update(['accepted_at' => now()]);

            return redirect()->route('customer.workspaces.show', $invitation->workspace)
                ->with('info', __('You are already a member of this workspace.'));
        }

        $member = $this->createMember($invitation, $customer);
        $invitation->update(['accepted_at' => now()]);

        WorkspaceActivityLog::log($invitation->workspace_id, 'member.joined', $member->id, WorkspaceMember::class, $member->id, [
            'email' => $customer->email,
            'via'   => 'existing_account',
        ]);

        session(['active_workspace_id' => $invitation->workspace_id]);

        return redirect()->route('customer.workspaces.show', $invitation->workspace)
            ->with('success', __('You\'ve joined ":name"!', ['name' => $invitation->workspace->name]));
    }

    private function createMember(WorkspaceInvitation $invitation, Customer $customer): WorkspaceMember
    {
        $existing = WorkspaceMember::where('workspace_id', $invitation->workspace_id)
            ->where('email', $invitation->email)
            ->first();

        if ($existing) {
            $existing->update([
                'customer_id'       => $customer->id,
                'workspace_role_id' => $invitation->workspace_role_id,
                'status'            => 'active',
                'accepted_at'       => now(),
            ]);

            return $existing;
        }

        return WorkspaceMember::create([
            'workspace_id'      => $invitation->workspace_id,
            'customer_id'       => $customer->id,
            'email'             => $invitation->email,
            'name'              => $customer->full_name,
            'workspace_role_id' => $invitation->workspace_role_id,
            'status'            => 'active',
            'invited_by'        => $invitation->invited_by,
            'invited_at'        => $invitation->created_at,
            'accepted_at'       => now(),
        ]);
    }
}

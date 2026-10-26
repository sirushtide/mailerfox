<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return $next($request);
        }

        // If the URL contained /w/{slug}, resolve workspace from the slug
        $urlSlug = $request->attributes->get('workspace_url_slug');
        if ($urlSlug) {
            $wsFromUrl = Workspace::where('slug', $urlSlug)->where('status', 'active')->first();
            if ($wsFromUrl) {
                session(['active_workspace_id' => $wsFromUrl->id]);
            }
        }

        $workspaceId = session('active_workspace_id');

        if ($workspaceId) {
            $workspace = Workspace::find($workspaceId);

            if (!$workspace || !$workspace->isActive()) {
                session()->forget('active_workspace_id');
                session()->forget('active_workspace_member_id');
                $request->attributes->set('workspace', null);
                $request->attributes->set('workspace_member', null);
                View::share('activeWorkspace', null);
                View::share('activeWorkspaceMember', null);

                return $next($request);
            }

            $member = $workspace->members()
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->first();

            if (!$member && $workspace->isOwner($customer)) {
                $member = $this->ensureOwnerMember($workspace, $customer);
            }

            if (!$member || !$member->isActive()) {
                session()->forget('active_workspace_id');
                session()->forget('active_workspace_member_id');
                $request->attributes->set('workspace', null);
                $request->attributes->set('workspace_member', null);
                View::share('activeWorkspace', null);
                View::share('activeWorkspaceMember', null);

                return $next($request);
            }

            $member->update(['last_active_at' => now()]);

            session(['active_workspace_member_id' => $member->id]);
            $request->attributes->set('workspace', $workspace);
            $request->attributes->set('workspace_member', $member);
            View::share('activeWorkspace', $workspace);
            View::share('activeWorkspaceMember', $member);
        } else {
            $request->attributes->set('workspace', null);
            $request->attributes->set('workspace_member', null);
            View::share('activeWorkspace', null);
            View::share('activeWorkspaceMember', null);
        }

        // Share all workspaces the customer belongs to (for the switcher)
        $customerWorkspaces = $this->getCustomerWorkspaces($customer);
        View::share('customerWorkspaces', $customerWorkspaces);

        return $next($request);
    }

    private function ensureOwnerMember(Workspace $workspace, $customer): WorkspaceMember
    {
        $adminRole = $workspace->roles()->where('slug', 'admin')->first();

        if (!$adminRole) {
            $adminRole = $workspace->roles()->first();
        }

        return WorkspaceMember::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'customer_id'  => $customer->id,
            ],
            [
                'email'             => $customer->email,
                'name'              => $customer->full_name,
                'workspace_role_id' => $adminRole ? $adminRole->id : 0,
                'status'            => 'active',
                'accepted_at'       => now(),
            ]
        );
    }

    private function getCustomerWorkspaces($customer): \Illuminate\Support\Collection
    {
        $ownedIds = Workspace::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->pluck('id');

        $memberIds = WorkspaceMember::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->pluck('workspace_id');

        $allIds = $ownedIds->merge($memberIds)->unique();

        return Workspace::whereIn('id', $allIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}

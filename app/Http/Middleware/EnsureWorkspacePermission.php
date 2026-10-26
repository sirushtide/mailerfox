<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspacePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $member = $request->attributes->get('workspace_member');

        if (!$member) {
            $customer = Auth::guard('customer')->user();

            if (!$customer) {
                abort(403, __('Access denied.'));
            }

            $workspace = $request->attributes->get('workspace');
            if ($workspace && $workspace->isOwner($customer)) {
                return $next($request);
            }

            abort(403, __('No active workspace context.'));
        }

        if ($member->isOwner()) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if (!$member->hasPermission($permission)) {
                abort(403, __('You do not have the ":permission" permission in this workspace.', [
                    'permission' => $permission,
                ]));
            }
        }

        return $next($request);
    }
}

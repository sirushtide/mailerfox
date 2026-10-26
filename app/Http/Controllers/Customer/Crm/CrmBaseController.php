<?php

namespace App\Http\Controllers\Customer\Crm;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;

abstract class CrmBaseController extends Controller
{
    protected function resolveContext(Request $request): array
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

    protected function authorizePermission(Workspace $workspace, ?WorkspaceMember $member, string $permission): void
    {
        $customer = auth('customer')->user();
        if ($workspace->isOwner($customer)) {
            return;
        }

        if (!$member || !$member->role || !$member->role->hasPermission($permission)) {
            abort(403, __('You do not have permission to access this feature.'));
        }
    }

    protected function authorizeAnyPermission(Workspace $workspace, ?WorkspaceMember $member, array $permissions): void
    {
        $customer = auth('customer')->user();
        if ($workspace->isOwner($customer)) {
            return;
        }

        if (!$member || !$member->role || !$member->role->hasAnyPermission($permissions)) {
            abort(403, __('You do not have permission to access this feature.'));
        }
    }
}

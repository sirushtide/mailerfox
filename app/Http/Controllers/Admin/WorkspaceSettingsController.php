<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = [
            'workspace_max_per_customer'      => Setting::get('workspace_max_per_customer', '3'),
            'workspace_max_members'            => Setting::get('workspace_max_members', '10'),
            'workspace_invitation_expiry_days' => Setting::get('workspace_invitation_expiry_days', '7'),
        ];

        return view('admin.addons.workspace-settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'workspace_max_per_customer'      => 'required|integer|min:0|max:100',
            'workspace_max_members'            => 'required|integer|min:0|max:500',
            'workspace_invitation_expiry_days' => 'required|integer|min:1|max:90',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) $value, 'workspace', 'integer');
        }

        return back()->with('success', __('Workspace settings updated.'));
    }
}

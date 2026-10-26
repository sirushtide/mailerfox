<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class UniboxSettingsController extends Controller
{
    public function edit()
    {
        $settings = [
            'unibox_sync_interval' => Setting::get('unibox_sync_interval', '5'),
            'unibox_max_accounts_per_customer' => Setting::get('unibox_max_accounts_per_customer', '5'),
            'unibox_sla_hours' => Setting::get('unibox_sla_hours', '2'),
            'unibox_ai_enabled' => Setting::get('unibox_ai_enabled', '0'),
        ];
        return view('admin.addons.unibox-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'unibox_sync_interval' => 'required|integer|min:1|max:60',
            'unibox_max_accounts_per_customer' => 'required|integer|min:1|max:50',
            'unibox_sla_hours' => 'required|integer|min:1|max:72',
            'unibox_ai_enabled' => 'nullable|boolean',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, (string) ($value ?? '0'), 'unibox', 'string');
        }

        return back()->with('success', 'UniBox settings saved.');
    }
}

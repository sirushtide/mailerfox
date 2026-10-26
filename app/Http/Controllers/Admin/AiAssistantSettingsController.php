<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.addons.ai-assistant-settings', [
            'defaultModel'   => (string) Setting::get('ai_assistant_default_model', ''),
            'widgetEnabled'  => filter_var(Setting::get('ai_assistant_widget_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'autonomyDefault' => (string) Setting::get('ai_assistant_autonomy_default', 'suggest'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_assistant_default_model'   => ['nullable', 'string', 'max:191'],
            'ai_assistant_widget_enabled'  => ['nullable', 'boolean'],
            'ai_assistant_autonomy_default' => ['required', 'in:suggest,auto'],
        ]);

        Setting::set('ai_assistant_default_model', trim((string) ($validated['ai_assistant_default_model'] ?? '')), 'addon', 'string');
        Setting::set('ai_assistant_widget_enabled', $request->boolean('ai_assistant_widget_enabled') ? '1' : '0', 'addon', 'boolean');
        Setting::set('ai_assistant_autonomy_default', $validated['ai_assistant_autonomy_default'], 'addon', 'string');

        return redirect()->route('admin.ai-assistant.settings')->with('success', __('AI Assistant settings saved.'));
    }
}

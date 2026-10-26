<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSupabaseConfig;
use App\Models\Setting;
use App\Services\Supabase\SupabaseService;
use Illuminate\Http\Request;

class SupabaseConfigController extends Controller
{
    public function index()
    {
        $configs = AdminSupabaseConfig::latest()->paginate(20);
        return view('admin.supabase-configs.index', compact('configs'));
    }

    public function storeOAuthSettings(Request $request)
    {
        $validated = $request->validate([
            'supabase_oauth_client_id' => ['nullable', 'string', 'max:255'],
            'supabase_oauth_client_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $clientId = trim($validated['supabase_oauth_client_id'] ?? '');
        $clientSecret = trim($validated['supabase_oauth_client_secret'] ?? '');

        if ($clientId !== '') {
            Setting::set('supabase_oauth_client_id', $clientId, 'integrations', 'string');
        }
        if ($clientSecret !== '' && $clientSecret !== '********') {
            Setting::set('supabase_oauth_client_secret', $clientSecret, 'integrations', 'string');
        }

        return redirect()->route('admin.supabase-configs.index')
            ->with('success', 'Supabase OAuth credentials saved.');
    }

    public function list()
    {
        $configs = AdminSupabaseConfig::where('status', 'connected')->get([
            'id',
            'name',
            'project_url',
            'project_id',
            'region',
            'is_default',
        ]);

        return response()->json(['success' => true, 'configs' => $configs]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'connection_type' => ['required', 'string', 'in:hosted,self_hosted'],
            'project_url' => ['required', 'string', 'url', 'max:500'],
            'project_id' => ['nullable', 'string', 'max:255'],
            'publishable_key' => ['nullable', 'string', 'max:2000'],
            'secret_key' => ['nullable', 'string', 'max:2000'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $publishableKey = trim((string) ($validated['publishable_key'] ?? ''));
        $secretKey = trim((string) ($validated['secret_key'] ?? ''));

        if ($publishableKey === '') {
            return back()->withErrors(['publishable_key' => 'A Supabase publishable key is required.'])->withInput();
        }

        if ($secretKey !== '' && ! SupabaseService::isValidSecretKeyFormat($secretKey)) {
            return back()->withErrors(['secret_key' => 'The secret key must be a valid Supabase service-role key (JWT or sb_secret_...).'])->withInput();
        }

        $config = new AdminSupabaseConfig([
            'name' => $validated['name'],
            'connection_type' => $validated['connection_type'],
            'project_url' => rtrim($validated['project_url'], '/'),
            'project_id' => $validated['project_id'] ?? null,
            'publishable_key' => $publishableKey,
            'secret_key' => $secretKey !== '' ? $secretKey : null,
            'db_password' => $validated['db_password'] ?? null,
            'region' => $validated['region'] ?? null,
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'status' => 'disconnected',
        ]);

        $service = new SupabaseService($config);
        $test = $service->testConnection();

        $config->status = $test['success'] ? 'connected' : 'error';
        $config->last_error = $test['error'];
        $config->save();

        if ($config->is_default) {
            AdminSupabaseConfig::where('id', '!=', $config->id)->update(['is_default' => false]);
        }

        return redirect()->route('admin.supabase-configs.index')
            ->with($test['success'] ? 'success' : 'error', $test['success'] ? 'Admin Supabase preset connected.' : 'Could not connect: ' . $test['error']);
    }

    public function update(Request $request, AdminSupabaseConfig $config)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'connection_type' => ['required', 'string', 'in:hosted,self_hosted'],
            'project_url' => ['required', 'string', 'url', 'max:500'],
            'project_id' => ['nullable', 'string', 'max:255'],
            'publishable_key' => ['nullable', 'string', 'max:2000'],
            'secret_key' => ['nullable', 'string', 'max:2000'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $publishableKey = trim((string) ($validated['publishable_key'] ?? ''));
        $secretKey = trim((string) ($validated['secret_key'] ?? ''));

        if ($publishableKey === '') {
            return back()->withErrors(['publishable_key' => 'A Supabase publishable key is required.'])->withInput();
        }

        if ($secretKey !== '' && ! SupabaseService::isValidSecretKeyFormat($secretKey)) {
            return back()->withErrors(['secret_key' => 'The secret key must be a valid Supabase service-role key (JWT or sb_secret_...).'])->withInput();
        }

        $config->fill([
            'name' => $validated['name'],
            'connection_type' => $validated['connection_type'],
            'project_url' => rtrim($validated['project_url'], '/'),
            'project_id' => $validated['project_id'] ?? null,
            'publishable_key' => $publishableKey,
            'secret_key' => $secretKey !== '' ? $secretKey : null,
            'db_password' => $validated['db_password'] ?? null,
            'region' => $validated['region'] ?? null,
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ]);

        $service = new SupabaseService($config);
        $test = $service->testConnection();

        $config->status = $test['success'] ? 'connected' : 'error';
        $config->last_error = $test['error'];
        $config->save();

        if ($config->is_default) {
            AdminSupabaseConfig::where('id', '!=', $config->id)->update(['is_default' => false]);
        }

        return redirect()->route('admin.supabase-configs.index')
            ->with($test['success'] ? 'success' : 'error', $test['success'] ? 'Admin Supabase preset updated.' : 'Could not connect: ' . $test['error']);
    }

    public function destroy(AdminSupabaseConfig $config)
    {
        $config->delete();
        return redirect()->route('admin.supabase-configs.index')
            ->with('success', 'Admin Supabase preset removed.');
    }

    public function setDefault(AdminSupabaseConfig $config)
    {
        AdminSupabaseConfig::where('id', '!=', $config->id)->update(['is_default' => false]);
        $config->update(['is_default' => true]);

        return redirect()->route('admin.supabase-configs.index')
            ->with('success', 'Default admin Supabase preset updated.');
    }
}

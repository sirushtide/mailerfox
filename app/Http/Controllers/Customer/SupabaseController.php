<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AdminSupabaseConfig;
use App\Models\SupabaseIntegration;
use App\Services\Supabase\SupabaseService;
use Illuminate\Http\Request;

class SupabaseController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user('customer');

        $access = (bool) $customer->groupSetting('supabase.access', false);
        $allowAdminManaged = (bool) $customer->groupSetting('supabase.allow_admin_managed', false);
        $allowByos = (bool) $customer->groupSetting('supabase.allow_byos', false);
        $allowByosManual = (bool) $customer->groupSetting('supabase.allow_byos_manual', true);
        $allowByosOauth = (bool) $customer->groupSetting('supabase.allow_byos_oauth', false);

        if (! $access) {
            return redirect()->route('customer.dashboard')
                ->with('error', 'Supabase is not available for your plan.');
        }

        $integration = $customer->supabaseIntegrations()
            ->whereNull('page_id')
            ->latest()
            ->first();

        $adminConfigs = [];
        if ($allowAdminManaged) {
            $adminConfigs = AdminSupabaseConfig::where('status', 'connected')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'project_url', 'project_id', 'region', 'is_default']);
        }

        $adminTables = null;
        $adminBuckets = null;
        $adminConfig = null;

        if ($integration && $integration->status === 'connected') {
            if ($integration->mode === 'admin_managed' && $integration->adminSupabaseConfig) {
                $adminConfig = $integration->adminSupabaseConfig;
            }

            $service = new SupabaseService($integration);
            $tablesResult = $service->getTables();
            $bucketsResult = $service->getBuckets();
            $adminTables = $tablesResult['success'] ? ($tablesResult['data'] ?? []) : null;
            $adminBuckets = $bucketsResult['success'] ? ($bucketsResult['data'] ?? []) : null;
        }

        return view('customer.supabase.index', compact(
            'customer',
            'integration',
            'allowAdminManaged',
            'allowByos',
            'allowByosManual',
            'allowByosOauth',
            'adminConfigs',
            'adminConfig',
            'adminTables',
            'adminBuckets',
        ));
    }

    public function connectAdmin(Request $request)
    {
        $customer = $request->user('customer');

        if (! (bool) $customer->groupSetting('supabase.allow_admin_managed', false)) {
            return back()->with('error', 'Admin-managed Supabase is not available for your plan.');
        }

        $validated = $request->validate([
            'admin_supabase_config_id' => ['required', 'integer', 'min:1'],
        ]);

        $config = AdminSupabaseConfig::where('status', 'connected')
            ->where('id', $validated['admin_supabase_config_id'])
            ->first();

        if (! $config) {
            return back()->with('error', 'Selected Supabase preset is not available.');
        }

        $customer->supabaseIntegrations()->whereNull('page_id')->delete();

        $integration = $customer->supabaseIntegrations()->create([
            'page_id' => null,
            'connection_type' => $config->connection_type ?? 'hosted',
            'mode' => 'admin_managed',
            'admin_supabase_config_id' => $config->id,
            'project_url' => $config->project_url,
            'project_id' => $config->project_id,
            'region' => $config->region,
            'status' => 'disconnected',
        ]);

        $service = new SupabaseService($integration);
        $test = $service->testConnection();
        $integration->update([
            'status' => $test['success'] ? 'connected' : 'error',
            'last_error' => $test['error'],
        ]);

        return back()->with(
            $test['success'] ? 'success' : 'error',
            $test['success'] ? 'Admin Supabase connected.' : 'Connection failed: ' . $test['error']
        );
    }

    public function storeByos(Request $request)
    {
        $customer = $request->user('customer');

        if (! (bool) $customer->groupSetting('supabase.allow_byos', false)) {
            return back()->with('error', 'BYOS Supabase is not available for your plan.');
        }

        $validated = $request->validate([
            'project_url' => ['required', 'string', 'url', 'max:500'],
            'publishable_key' => ['required', 'string', 'max:2000'],
            'secret_key' => ['required', 'string', 'max:2000'],
            'region' => ['nullable', 'string', 'max:100'],
            'auth_providers' => ['nullable', 'array'],
            'auth_providers.google' => ['nullable', 'array'],
            'auth_providers.google.enabled' => ['nullable', 'boolean'],
            'auth_providers.google.client_id' => ['nullable', 'string', 'max:2000'],
            'auth_providers.google.client_secret' => ['nullable', 'string'],
            'auth_providers.google.redirect_uri' => ['nullable', 'string', 'max:2000'],
            'auth_providers.apple' => ['nullable', 'array'],
            'auth_providers.apple.enabled' => ['nullable', 'boolean'],
            'auth_providers.apple.client_id' => ['nullable', 'string', 'max:2000'],
            'auth_providers.apple.client_secret' => ['nullable', 'string'],
            'auth_providers.apple.redirect_uri' => ['nullable', 'string', 'max:2000'],
            'auth_providers.apple.team_id' => ['nullable', 'string', 'max:255'],
            'auth_providers.apple.key_id' => ['nullable', 'string', 'max:255'],
            'auth_providers.apple.private_key' => ['nullable', 'string'],
        ]);

        $publishableKey = trim((string) $validated['publishable_key']);
        $secretKey = trim((string) $validated['secret_key']);

        if (! SupabaseService::isValidSecretKeyFormat($secretKey)) {
            return back()->withErrors([
                'secret_key' => 'The secret key must be a valid Supabase service-role key (JWT or sb_secret_...).',
            ])->withInput();
        }

        $customer->supabaseIntegrations()->whereNull('page_id')->delete();

        $integration = $customer->supabaseIntegrations()->create([
            'page_id' => null,
            'connection_type' => 'hosted',
            'mode' => 'byos_manual',
            'project_url' => rtrim($validated['project_url'], '/'),
            'publishable_key' => $publishableKey,
            'secret_key' => $secretKey,
            'region' => $validated['region'] ?? null,
            'auth_providers' => $validated['auth_providers'] ?? null,
            'status' => 'disconnected',
        ]);

        $service = new SupabaseService($integration);
        $test = $service->testConnection();
        $integration->update([
            'status' => $test['success'] ? 'connected' : 'error',
            'last_error' => $test['error'],
        ]);

        return back()->with(
            $test['success'] ? 'success' : 'error',
            $test['success'] ? 'Supabase connected successfully.' : 'Connection failed: ' . $test['error']
        );
    }

    public function disconnect(Request $request)
    {
        $customer = $request->user('customer');
        $customer->supabaseIntegrations()->whereNull('page_id')->delete();

        return back()->with('success', 'Supabase disconnected.');
    }

    public function status(Request $request)
    {
        $customer = $request->user('customer');
        $integration = $customer->supabaseIntegrations()
            ->whereNull('page_id')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'integration' => $integration,
        ]);
    }

    public function tables(Request $request)
    {
        $customer = $request->user('customer');
        $integration = $customer->supabaseIntegrations()->whereNull('page_id')->latest()->first();

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $service = new SupabaseService($integration);
        return response()->json($service->getTables());
    }

    public function users(Request $request)
    {
        $customer = $request->user('customer');
        $integration = $customer->supabaseIntegrations()->whereNull('page_id')->latest()->first();

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $appId = (string) $integration->getKey();
        $service = new SupabaseService($integration);
        return response()->json($service->getUsers($appId));
    }

    public function buckets(Request $request)
    {
        $customer = $request->user('customer');
        $integration = $customer->supabaseIntegrations()->whereNull('page_id')->latest()->first();

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $service = new SupabaseService($integration);
        return response()->json($service->getBuckets());
    }

    public function tableData(Request $request)
    {
        $customer = $request->user('customer');
        $integration = $customer->supabaseIntegrations()->whereNull('page_id')->latest()->first();

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $table = trim((string) $request->query('table', ''));
        if ($table === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            return response()->json(['success' => false, 'error' => 'A valid table name is required.']);
        }

        $limit = $request->integer('limit', 100);

        $service = new SupabaseService($integration);

        return response()->json($service->getTableData($table, $limit));
    }

    public function login(Request $request)
    {
        $customer = $request->user('customer');
        $integration = $customer->supabaseIntegrations()->whereNull('page_id')->latest()->first();

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6'],
            'app_id' => ['required', 'string'],
        ]);

        $appId = trim((string) $validated['app_id']);
        $expectedAppId = (string) $integration->getKey();

        if ($appId !== $expectedAppId) {
            return response()->json(['success' => false, 'error' => 'Invalid application.']);
        }

        $service = new SupabaseService($integration);

        return response()->json($service->loginUser($appId, $validated['email'], $validated['password']));
    }

    public function signup(Request $request)
    {
        $customer = $request->user('customer');
        $integration = $customer->supabaseIntegrations()->whereNull('page_id')->latest()->first();

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6'],
            'app_id' => ['required', 'string'],
        ]);

        $appId = trim((string) $validated['app_id']);
        $expectedAppId = (string) $integration->getKey();

        if ($appId !== $expectedAppId) {
            return response()->json(['success' => false, 'error' => 'Invalid application.']);
        }

        $service = new SupabaseService($integration);

        return response()->json($service->signupUser($appId, $validated['email'], $validated['password']));
    }
}

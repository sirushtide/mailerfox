<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSupabaseConfig;
use App\Models\Customer;
use App\Models\Page;
use App\Models\SupabaseIntegration;
use App\Services\Supabase\SupabaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupabaseIntegrationController extends Controller
{
    public function show(Page $page)
    {
        $integration = $page->supabaseIntegration;
        $customer = $this->resolveCustomer($page, null);
        $capabilities = $this->supabaseCapabilities($customer);

        if (! $integration) {
            return response()->json([
                'success' => true,
                'integration' => null,
                'capabilities' => $capabilities,
            ]);
        }

        return response()->json([
            'success' => true,
            'capabilities' => $capabilities,
            'integration' => [
                'id' => $integration->id,
                'connection_type' => $integration->connection_type,
                'mode' => $integration->mode,
                'admin_supabase_config_id' => $integration->admin_supabase_config_id,
                'project_url' => $integration->project_url,
                'project_id' => $integration->project_id,
                'publishable_key' => $integration->publishable_key,
                'secret_key' => maskApiKey($integration->secret_key),
                'region' => $integration->region,
                'status' => $integration->status,
                'last_error' => $integration->last_error,
                'auth_providers' => $integration->auth_providers,
                'updated_at' => $integration->updated_at,
            ],
        ]);
    }

    public function connect(Request $request, Page $page)
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:admin_managed,byos_manual,byos_oauth'],
            'connection_type' => ['required', 'string', 'in:hosted,self_hosted'],
            'admin_supabase_config_id' => ['nullable', 'integer', 'min:1'],
            'project_url' => ['nullable', 'string', 'url', 'max:500'],
            'project_id' => ['nullable', 'string', 'max:255'],
            'publishable_key' => ['nullable', 'string', 'max:2000'],
            'secret_key' => ['nullable', 'string', 'max:2000'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
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

        $publishableKey = trim((string) ($validated['publishable_key'] ?? ''));
        $secretKey = trim((string) ($validated['secret_key'] ?? ''));

        if ($secretKey !== '' && ! SupabaseService::isValidSecretKeyFormat($secretKey)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'secret_key' => 'The secret key must be a valid Supabase service-role key (JWT or sb_secret_...).',
            ]);
        }

        $customer = $this->resolveCustomer($page, $validated['customer_id'] ?? null);
        $this->ensureCustomerCanUseMode($customer, $validated['mode']);

        $integration = DB::transaction(function () use ($page, $validated, $customer) {
            $page->supabaseIntegration()->delete();

            $mode = $validated['mode'];
            $base = [
                'connection_type' => $validated['connection_type'],
                'mode' => $mode,
                'customer_id' => $customer?->id,
                'region' => $validated['region'] ?? null,
                'status' => 'disconnected',
                'last_error' => null,
                'auth_providers' => $validated['auth_providers'] ?? null,
            ];

            if ($mode === 'admin_managed') {
                $config = $validated['admin_supabase_config_id']
                    ? AdminSupabaseConfig::find($validated['admin_supabase_config_id'])
                    : AdminSupabaseConfig::where('is_default', true)->where('status', 'connected')->first();

                if (! $config) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'admin_supabase_config_id' => 'No connected admin Supabase preset is available.',
                    ]);
                }

                if ($customer) {
                    $customer->enforceGroupLimit(
                        'supabase.limits.max_admin_managed_projects',
                        $customer->supabaseIntegrations()
                            ->where('mode', 'admin_managed')
                            ->where('page_id', '!=', $page->id)
                            ->count(),
                        'Admin-managed Supabase project limit reached.'
                    );
                }

                return $page->supabaseIntegration()->create(array_merge($base, [
                    'connection_type' => $config->connection_type ?? 'hosted',
                    'region' => $config->region,
                    'admin_supabase_config_id' => $config->id,
                    'project_url' => $config->project_url,
                    'project_id' => $config->project_id,
                ]));
            }

            if ($mode === 'byos_manual') {
                if (empty($validated['project_url']) || $publishableKey === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'project_url' => 'Project URL and publishable key are required for manual BYOS connection.',
                    ]);
                }

                if ($customer) {
                    $customer->enforceGroupLimit(
                        'supabase.limits.max_byos_projects',
                        $customer->supabaseIntegrations()->where(function (Builder $query) {
                            $query->where('mode', 'byos_manual')
                                ->orWhere('mode', 'byos_oauth');
                        })->where('page_id', '!=', $page->id)->count(),
                        'BYOS Supabase project limit reached.'
                    );
                }

                return $page->supabaseIntegration()->create(array_merge($base, [
                    'project_url' => rtrim($validated['project_url'], '/'),
                    'project_id' => $validated['project_id'] ?? null,
                    'publishable_key' => $publishableKey,
                    'secret_key' => $secretKey !== '' ? $secretKey : null,
                    'db_password' => $validated['db_password'] ?? null,
                ]));
            }

            // byos_oauth payload is expected from OAuth callback, but accept a reconnection payload here
            if (empty($validated['project_url']) || $publishableKey === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'project_url' => 'Project URL and publishable key are required for BYOS OAuth connection.',
                ]);
            }

            if ($customer) {
                $customer->enforceGroupLimit(
                    'supabase.limits.max_byos_projects',
                    $customer->supabaseIntegrations()->where(function (Builder $query) {
                        $query->where('mode', 'byos_manual')
                            ->orWhere('mode', 'byos_oauth');
                    })->where('page_id', '!=', $page->id)->count(),
                    'BYOS Supabase project limit reached.'
                );
            }

            return $page->supabaseIntegration()->create(array_merge($base, [
                'project_url' => rtrim($validated['project_url'], '/'),
                'project_id' => $validated['project_id'] ?? null,
                'publishable_key' => $publishableKey,
                'secret_key' => $secretKey !== '' ? $secretKey : null,
            ]));
        });

        $service = new SupabaseService($integration);
        $test = $service->testConnection();

        $integration->update([
            'status' => $test['success'] ? 'connected' : 'error',
            'last_error' => $test['error'],
        ]);

        return response()->json([
            'success' => $test['success'],
            'message' => $test['success'] ? 'Supabase connected successfully.' : 'Could not connect to Supabase.',
            'error' => $test['error'],
'integration' => [
                'id' => $integration->id,
                'connection_type' => $integration->connection_type,
                'mode' => $integration->mode,
                'admin_supabase_config_id' => $integration->admin_supabase_config_id,
                'project_url' => $integration->project_url,
                'project_id' => $integration->project_id,
                'publishable_key' => $integration->publishable_key,
                'secret_key' => maskApiKey($integration->secret_key),
                'region' => $integration->region,
                'status' => $integration->status,
                'last_error' => $integration->last_error,
                'auth_providers' => $integration->auth_providers,
                'updated_at' => $integration->updated_at,
            ],
        ]);
    }

    public function disconnect(Request $request, Page $page)
    {
        $page->supabaseIntegration()->delete();

        return response()->json(['success' => true, 'message' => 'Supabase integration removed.']);
    }

    private function resolveCustomer(Page $page, ?int $customerId): ?Customer
    {
        if ($customerId) {
            return Customer::find($customerId);
        }

        if ($page->customer_id) {
            return $page->customer;
        }

        return null;
    }

    private function supabaseCapabilities(?Customer $customer): array
    {
        if (! $customer) {
            return [
                'access' => true,
                'allow_admin_managed' => true,
                'allow_byos' => true,
                'allow_byos_oauth' => true,
                'allow_byos_manual' => true,
            ];
        }

        return [
            'access' => (bool) $customer->groupSetting('supabase.access', false),
            'allow_admin_managed' => (bool) $customer->groupSetting('supabase.allow_admin_managed', false),
            'allow_byos' => (bool) $customer->groupSetting('supabase.allow_byos', false),
            'allow_byos_oauth' => (bool) $customer->groupSetting('supabase.allow_byos_oauth', false),
            'allow_byos_manual' => (bool) $customer->groupSetting('supabase.allow_byos_manual', true),
        ];
    }

    private function ensureCustomerCanUseMode(?Customer $customer, string $mode): void
    {
        if (! $customer) {
            return;
        }

        $capabilities = $this->supabaseCapabilities($customer);

        if (! $capabilities['access']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'mode' => 'This customer group cannot access Supabase.',
            ]);
        }

        if ($mode === 'admin_managed' && ! $capabilities['allow_admin_managed']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'mode' => 'This customer group cannot use admin-managed Supabase.',
            ]);
        }

        if (in_array($mode, ['byos_manual', 'byos_oauth'], true) && ! $capabilities['allow_byos']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'mode' => 'This customer group cannot use BYOS Supabase.',
            ]);
        }

        if ($mode === 'byos_manual' && ! $capabilities['allow_byos_manual']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'mode' => 'This customer group cannot use manual BYOS Supabase.',
            ]);
        }

        if ($mode === 'byos_oauth' && ! $capabilities['allow_byos_oauth']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'mode' => 'This customer group cannot use one-click BYOS Supabase.',
            ]);
        }
    }

    public function users(Page $page, Request $request)
    {
        $integration = $page->supabaseIntegration;

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $appId = $request->query('app_id', (string) $integration->getKey());
        $service = new SupabaseService($integration);
        $result = $service->getUsers($appId);

        return response()->json($result);
    }

    public function buckets(Page $page)
    {
        $integration = $page->supabaseIntegration;

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $service = new SupabaseService($integration);
        $result = $service->getBuckets();

        return response()->json($result);
    }

    public function tables(Page $page)
    {
        $integration = $page->supabaseIntegration;

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $service = new SupabaseService($integration);
        $result = $service->getTables();

        return response()->json($result);
    }

    public function tableData(Request $request, Page $page)
    {
        $integration = $page->supabaseIntegration;

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

    public function updateAuthProviders(Request $request, Page $page)
    {
        $integration = $page->supabaseIntegration;

        if (! $integration || $integration->status !== 'connected') {
            return response()->json(['success' => false, 'error' => 'Supabase is not connected.']);
        }

        $validated = $request->validate([
            'auth_providers' => ['required', 'array'],
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

        $integration->update(['auth_providers' => $validated['auth_providers']]);

        return response()->json([
            'success' => true,
            'integration' => [
                'id' => $integration->id,
                'auth_providers' => $integration->auth_providers,
            ],
        ]);
    }
}

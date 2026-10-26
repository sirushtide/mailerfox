<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Models\DeliveryServer;
use App\Models\Plan;
use App\Models\Setting;
use App\Services\AI\AiProviderRegistry;
use App\Services\CustomerGroupService;
use App\Services\UpdateServerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function __construct(
        protected CustomerGroupService $customerGroupService,
        protected UpdateServerService $updateServerService
    ) {}

    protected function isExtendedLicenseAvailable(): bool
    {
        $baseUrl = (string) config('services.update_server.base_url', Setting::get('update_api_base_url'));
        $licenseKey = Setting::get('update_license_key');
        $licenseKey = is_string($licenseKey) ? trim($licenseKey) : '';

        if ($baseUrl === '' || $licenseKey === '' || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $licenseKey)) {
            return false;
        }

        $appUrl = (string) config('app.url');
        $parsed = parse_url($appUrl);
        $domain = is_array($parsed) && is_string($parsed['host'] ?? null) && trim((string) $parsed['host']) !== ''
            ? (string) $parsed['host']
            : $appUrl;

        $cacheKey = 'update_server:license_check:' . md5((string) $baseUrl) . ':' . md5((string) $licenseKey) . ':' . md5((string) $domain);
        $licenseCheck = Cache::get($cacheKey);

        if (!is_array($licenseCheck)) {
            $productSecret = (string) config('services.update_server.product_secret', Setting::get('update_product_secret'));
            $productName = (string) config('services.update_server.product_name', Setting::get('update_product_name'));

            if ($productSecret === '' || $productName === '') {
                return false;
            }

            $licenseCheck = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($licenseKey, $domain, $productSecret, $productName) {
                return $this->updateServerService->licenseCheck(
                    (string) $licenseKey,
                    $domain,
                    (string) $productSecret,
                    (string) $productName
                );
            });
        }

        if (!is_array($licenseCheck) || !($licenseCheck['valid'] ?? false)) {
            return false;
        }

        $payload = is_array($licenseCheck['data'] ?? null) ? $licenseCheck['data'] : [];
        $license = [];

        if (is_array($payload['data'] ?? null) && is_array($payload['data']['license'] ?? null)) {
            $license = $payload['data']['license'];
        } elseif (is_array($payload['license'] ?? null)) {
            $license = $payload['license'];
        }

        $typeCandidates = array_filter([
            is_string($license['license_type'] ?? null) ? $license['license_type'] : null,
            is_string($payload['license_type'] ?? null) ? $payload['license_type'] : null,
            is_string($license['type'] ?? null) ? $license['type'] : null,
            is_string($payload['type'] ?? null) ? $payload['type'] : null,
            is_string($license['name'] ?? null) ? $license['name'] : null,
            is_string($payload['name'] ?? null) ? $payload['name'] : null,
        ]);

        foreach ($typeCandidates as $candidate) {
            if (str_contains(strtolower(trim((string) $candidate)), 'extended')) {
                return true;
            }
        }

        return false;
    }
    public function index()
    {
        $plans = Plan::withCount(['subscriptions' => function ($query) {
            $query->whereIn('status', ['active', 'trialing']);
        }])->latest()->paginate(15);

        $pricingSettings = [
            'badge' => (string) Setting::get('pricing_section_badge', (string) Setting::get('home_pricing_badge', 'Our Pricing')),
            'title' => (string) Setting::get('pricing_section_title', (string) Setting::get('pricing_hero_title', 'Simple, transparent pricing')),
            'subtitle' => (string) Setting::get('pricing_section_subtitle', (string) Setting::get('pricing_hero_subtitle', "Choose the plan that's right for your business. All plans include a 14-day free trial.")),
            'toggle_monthly' => (string) Setting::get('pricing_section_toggle_monthly', (string) Setting::get('home_pricing_toggle_monthly', 'Pay Monthly')),
            'toggle_annual' => (string) Setting::get('pricing_section_toggle_annual', (string) Setting::get('home_pricing_toggle_annual', 'Pay Annually')),
            'toggle_save' => (string) Setting::get('pricing_section_toggle_save', (string) Setting::get('home_pricing_toggle_save', '(save 20%)')),
            'show_all' => (bool) Setting::get('pricing_section_show_all', false),
            'columns' => (int) Setting::get('pricing_section_columns', 3),
            'popular_badge' => (string) Setting::get('pricing_section_popular_badge', (string) Setting::get('home_pricing_popular_badge', 'Popular')),
        ];

        return view('admin.plans.index', compact('plans', 'pricingSettings'));
    }

    public function export(): JsonResponse
    {
        $plans = Plan::query()
            ->orderBy('id')
            ->get()
            ->map(function (Plan $plan): array {
                return collect($plan->toArray())
                    ->except(['id', 'created_at', 'updated_at'])
                    ->all();
            })
            ->values()
            ->all();

        return response()
            ->json([
                'version' => 1,
                'exported_at' => now()->toIso8601String(),
                'plans' => $plans,
            ])
            ->withHeaders([
                'Content-Disposition' => 'attachment; filename="mailpurse-plans-' . now()->format('Y-m-d') . '.json"',
            ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'plans_file' => ['required', 'file', 'mimes:json,txt', 'max:5120'],
        ]);

        $contents = file_get_contents($request->file('plans_file')->getRealPath());
        $payload = json_decode($contents, true);

        if (!is_array($payload)) {
            return back()->withErrors(['plans_file' => __('The uploaded file contains invalid JSON.')]);
        }

        $plans = array_is_list($payload) ? $payload : ($payload['plans'] ?? null);
        if (!is_array($plans)) {
            return back()->withErrors(['plans_file' => __('The JSON must contain a plans array.')]);
        }

        $imported = 0;
        DB::transaction(function () use ($plans, &$imported): void {
            foreach ($plans as $planData) {
                if (!is_array($planData)) {
                    continue;
                }

                $data = $this->normalizeImportedPlan($planData);
                if ($data === null) {
                    continue;
                }

                Plan::updateOrCreate(['slug' => $data['slug']], $data);
                $imported++;
            }
        });

        if ($imported === 0) {
            return back()->withErrors(['plans_file' => __('No valid plans were found in the JSON file.')]);
        }

        return redirect()->route('admin.plans.index')->with('success', __(':count plans imported.', ['count' => $imported]));
    }

    public function importDefaults()
    {
        $defaults = [
            [
                'name' => 'Starter', 'slug' => 'starter', 'description' => 'Starter plan', 'price' => 19, 'trial_days' => 7,
                'monthly_quota' => 10000, 'max_delivery_servers' => 1, 'max_bounce_servers' => 1,
                'max_reply_servers' => 1, 'max_tracking_domains' => 1, 'max_sending_domains' => 1,
            ],
            [
                'name' => 'Pro', 'slug' => 'pro', 'description' => 'Professional plan', 'price' => 49, 'trial_days' => 14,
                'monthly_quota' => 100000, 'max_delivery_servers' => 5, 'max_bounce_servers' => 5,
                'max_reply_servers' => 5, 'max_tracking_domains' => 5, 'max_sending_domains' => 5,
            ],
            [
                'name' => 'Business', 'slug' => 'business', 'description' => 'Business plan', 'price' => 129, 'trial_days' => 14,
                'monthly_quota' => 500000, 'max_delivery_servers' => 20, 'max_bounce_servers' => 20,
                'max_reply_servers' => 20, 'max_tracking_domains' => 20, 'max_sending_domains' => 20,
            ],
        ];

        $imported = 0;
        foreach ($defaults as $default) {
            if (Plan::where('slug', $default['slug'])->exists()) {
                continue;
            }

            $settings = $this->defaultPlanSettings($default);
            Plan::create(array_merge($default, [
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'is_active' => true,
                'is_public' => true,
                'is_popular' => $default['slug'] === 'pro',
                'features' => [],
                'limits' => [
                    'max_delivery_servers' => $default['max_delivery_servers'],
                    'max_bounce_servers' => $default['max_bounce_servers'],
                    'max_reply_servers' => $default['max_reply_servers'],
                    'max_tracking_domains' => $default['max_tracking_domains'],
                    'max_sending_domains' => $default['max_sending_domains'],
                ],
                'settings' => $settings['settings'],
                'permissions' => $settings['permissions'],
            ]));
            $imported++;
        }

        return redirect()->route('admin.plans.index')->with('success', __(':count default plans imported.', ['count' => $imported]));
    }

    private function defaultPlanSettings(array $default): array
    {
        $settings = $this->customerGroupService->getDefaultSettings();
        $true = true;

        $settings = array_replace_recursive($settings, [
            'lists' => [
                'limits' => [
                    'max_lists' => -1,
                    'max_subscribers' => -1,
                    'max_subscribers_per_list' => -1,
                    'max_forms_per_list' => -1,
                ],
            ],
            'servers' => [
                'limits' => [
                    'max_delivery_servers' => $default['max_delivery_servers'],
                    'max_bounce_servers' => $default['max_bounce_servers'],
                    'max_reply_servers' => $default['max_reply_servers'],
                ],
                'permissions' => [
                    'can_add_delivery_servers' => $true,
                    'can_access_delivery_servers' => $true,
                    'can_create_delivery_servers' => $true,
                    'can_edit_delivery_servers' => $true,
                    'can_delete_delivery_servers' => $true,
                    'can_access_bounce_servers' => $true,
                    'can_add_bounce_servers' => $true,
                    'can_edit_bounce_servers' => $true,
                    'can_delete_bounce_servers' => $true,
                    'can_access_reply_servers' => $true,
                    'can_add_reply_servers' => $true,
                    'can_edit_reply_servers' => $true,
                    'can_delete_reply_servers' => $true,
                    'can_select_delivery_servers_for_campaigns' => $true,
                ],
            ],
            'domains' => [
                'tracking_domains' => [
                    'can_manage' => $true,
                    'max_tracking_domains' => $default['max_tracking_domains'],
                    'permissions' => [
                        'can_access_tracking_domains' => $true,
                        'can_create_tracking_domains' => $true,
                        'can_edit_tracking_domains' => $true,
                        'can_delete_tracking_domains' => $true,
                    ],
                ],
                'sending_domains' => [
                    'can_manage' => $true,
                    'max_sending_domains' => $default['max_sending_domains'],
                    'permissions' => [
                        'can_access_sending_domains' => $true,
                        'can_create_sending_domains' => $true,
                        'can_edit_sending_domains' => $true,
                        'can_delete_sending_domains' => $true,
                    ],
                ],
            ],
            'sending_quota' => [
                'daily_quota' => (int) ceil($default['monthly_quota'] / 30),
                'weekly_quota' => (int) ceil($default['monthly_quota'] / 4),
                'monthly_quota' => $default['monthly_quota'],
            ],
            'automations' => [
                'enabled' => $true,
                'max_automations' => -1,
            ],
            'autoresponders' => [
                'enabled' => $true,
                'max_autoresponders' => -1,
            ],
            'campaigns' => [
                'limits' => [
                    'max_campaigns' => -1,
                    'max_active_campaigns' => -1,
                ],
                'permissions' => [
                    'can_create_campaigns' => $true,
                    'can_edit_campaigns' => $true,
                    'can_start_campaigns' => $true,
                    'can_delete_campaigns' => $true,
                ],
            ],
            'email_validation' => [
                'access' => $true,
                'monthly_limit' => -1,
                'max_tools' => -1,
            ],
            'templates' => [
                'permissions' => [
                    'can_access_templates' => $true,
                    'can_access_footer_templates' => $true,
                    'can_access_signature_templates' => $true,
                    'can_create_templates' => $true,
                    'can_import_templates' => $true,
                ],
            ],
            'api' => [
                'enabled' => $true,
                'permissions' => [
                    'can_access_api' => $true,
                ],
            ],
        ]);

        $permissions = [
            'automations.enabled',
            'email_validation.access',
            'templates.permissions.can_access_templates',
            'templates.permissions.can_access_footer_templates',
            'templates.permissions.can_access_signature_templates',
            'templates.permissions.can_create_templates',
            'templates.permissions.can_import_templates',
            'api.permissions.can_access_api',
            'servers.permissions.can_access_delivery_servers',
            'servers.permissions.can_access_bounce_servers',
            'servers.permissions.can_access_reply_servers',
            'domains.tracking_domains.permissions.can_access_tracking_domains',
            'domains.sending_domains.permissions.can_access_sending_domains',
            'campaigns.permissions.can_create_campaigns',
            'campaigns.permissions.can_edit_campaigns',
            'campaigns.permissions.can_start_campaigns',
            'campaigns.permissions.can_delete_campaigns',
        ];

        return compact('settings', 'permissions');
    }

    private function normalizeImportedPlan(array $plan): ?array
    {
        $name = trim((string) ($plan['name'] ?? ''));
        $slug = Str::slug((string) ($plan['slug'] ?? $name));
        if ($name === '' || $slug === '') {
            return null;
        }

        $billingCycle = (string) ($plan['billing_cycle'] ?? 'monthly');
        if (!in_array($billingCycle, ['monthly', 'yearly', 'lifetime'], true)) {
            $billingCycle = 'monthly';
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $plan['description'] ?? null,
            'price' => max(0, (float) ($plan['price'] ?? 0)),
            'currency' => strtoupper(substr((string) ($plan['currency'] ?? 'USD'), 0, 3)),
            'billing_cycle' => $billingCycle,
            'trial_days' => max(0, (int) ($plan['trial_days'] ?? 0)),
            'features' => is_array($plan['features'] ?? null) ? $plan['features'] : [],
            'limits' => is_array($plan['limits'] ?? null) ? $plan['limits'] : [],
            'settings' => is_array($plan['settings'] ?? null) ? $plan['settings'] : [],
            'permissions' => is_array($plan['permissions'] ?? null) ? $plan['permissions'] : [],
            'customer_group_id' => $plan['customer_group_id'] ?? null,
            'stripe_price_id' => $plan['stripe_price_id'] ?? null,
            'stripe_product_id' => $plan['stripe_product_id'] ?? null,
            'dodo_product_id' => $plan['dodo_product_id'] ?? null,
            'paddle_price_id' => $plan['paddle_price_id'] ?? null,
            'lemonsqueezy_variant_id' => $plan['lemonsqueezy_variant_id'] ?? null,
            'cta_text' => $plan['cta_text'] ?? null,
            'is_active' => (bool) ($plan['is_active'] ?? true),
            'is_popular' => (bool) ($plan['is_popular'] ?? false),
            'is_public' => (bool) ($plan['is_public'] ?? true),
        ];
    }

    private function planFormPayload(?Plan $plan = null): array
    {
        $defaultSettings = $this->customerGroupService->getDefaultSettings();
        $effectiveSettings = $plan
            ? array_replace_recursive($defaultSettings, $plan->settings ?? [])
            : $defaultSettings;

        $deliveryServers = DeliveryServer::query()
            ->whereNull('customer_id')
            ->orderBy('name')
            ->get();

        $allocatedDeliveryServerIds = $plan
            ? DB::table('plan_delivery_server')
                ->where('plan_id', $plan->id)
                ->pluck('delivery_server_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all()
            : [];

        $aiProviderModels = [];
        foreach (AiProviderRegistry::providerKeys() as $key) {
            $aiProviderModels[$key] = AiProviderRegistry::allModels($key);
        }

        return [
            'defaultSettings' => $defaultSettings,
            'settings' => $effectiveSettings,
            'deliveryServers' => $deliveryServers,
            'allocatedDeliveryServerIds' => $allocatedDeliveryServerIds,
            'hasExtendedLicense' => $this->isExtendedLicenseAvailable(),
            'aiProviderModels' => $aiProviderModels,
        ];
    }

    public function create()
    {
        return view('admin.plans.create', $this->planFormPayload());
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $settingsPayload = $this->customerGroupService->buildPlanSettingsAndPermissions(
            $request->all(),
            []
        );
        $data['settings'] = $settingsPayload['settings'];
        $data['permissions'] = $settingsPayload['permissions'];

        $plan = Plan::create($data);
        $this->syncAllocatedDeliveryServers($plan, $request->all());

        if (($data['is_popular'] ?? false) === true) {
            Plan::query()
                ->where('id', '!=', $plan->id)
                ->where('billing_cycle', $plan->billing_cycle)
                ->update(['is_popular' => false]);
        }

        return redirect()->route('admin.plans.index')->with('success', __('Plan created.'));
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.edit', array_merge(
            ['plan' => $plan],
            $this->planFormPayload($plan)
        ));
    }

    public function show(Plan $plan)
    {
        $plan->load(['customerGroup', 'subscriptions']);

        return view('admin.plans.show', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validateData($request, $plan->id);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $settingsPayload = $this->customerGroupService->buildPlanSettingsAndPermissions(
            $request->all(),
            $plan->settings ?? []
        );
        $data['settings'] = $settingsPayload['settings'];
        $data['permissions'] = $settingsPayload['permissions'];

        $plan->update($data);
        $this->syncAllocatedDeliveryServers($plan, $request->all());

        if (($data['is_popular'] ?? false) === true) {
            Plan::query()
                ->where('id', '!=', $plan->id)
                ->where('billing_cycle', $plan->billing_cycle)
                ->update(['is_popular' => false]);
        }

        return redirect()->route('admin.plans.index')->with('success', __('Plan updated.'));
    }

    private function syncAllocatedDeliveryServers(Plan $plan, array $data): void
    {
        $serverIds = $this->customerGroupService->resolveAllocatedDeliveryServerIds($data);
        DB::table('plan_delivery_server')
            ->where('plan_id', $plan->id)
            ->whereNotIn('delivery_server_id', $serverIds)
            ->delete();

        foreach ($serverIds as $serverId) {
            DB::table('plan_delivery_server')->updateOrInsert(
                ['plan_id' => $plan->id, 'delivery_server_id' => $serverId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', __('Plan deleted.'));
    }

    public function updatePricingSettings(Request $request)
    {
        $data = $request->validate([
            'badge' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'toggle_monthly' => ['nullable', 'string', 'max:255'],
            'toggle_annual' => ['nullable', 'string', 'max:255'],
            'toggle_save' => ['nullable', 'string', 'max:255'],
            'show_all' => ['nullable', 'boolean'],
            'columns' => ['nullable', 'integer', 'min:1', 'max:5'],
            'popular_badge' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::set('pricing_section_badge', (string) ($data['badge'] ?? ''), 'pricing');
        Setting::set('pricing_section_title', (string) ($data['title'] ?? ''), 'pricing');
        Setting::set('pricing_section_subtitle', (string) ($data['subtitle'] ?? ''), 'pricing');
        Setting::set('pricing_section_toggle_monthly', (string) ($data['toggle_monthly'] ?? ''), 'pricing');
        Setting::set('pricing_section_toggle_annual', (string) ($data['toggle_annual'] ?? ''), 'pricing');
        Setting::set('pricing_section_toggle_save', (string) ($data['toggle_save'] ?? ''), 'pricing');
        Setting::set('pricing_section_show_all', (bool) ($data['show_all'] ?? false), 'pricing');
        Setting::set('pricing_section_columns', (int) ($data['columns'] ?? 3), 'pricing');
        Setting::set('pricing_section_popular_badge', (string) ($data['popular_badge'] ?? ''), 'pricing');

        return redirect()
            ->route('admin.plans.index')
            ->with('success', __('Pricing section updated.'));
    }

    private function validateData(Request $request, ?int $planId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('plans', 'slug')->ignore($planId)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'stripe_product_id' => ['nullable', 'string', 'max:255'],
            'dodo_product_id' => ['nullable', 'string', 'max:255'],
            'paddle_price_id' => ['nullable', 'string', 'max:255'],
            'lemonsqueezy_variant_id' => ['nullable', 'string', 'max:255'],
            'cta_text' => ['nullable', 'string', 'max:255'],
            'features_text' => ['nullable', 'string'],
            'features_pros' => ['nullable', 'array'],
            'features_pros.*' => ['nullable', 'string'],
            'features_cons' => ['nullable', 'array'],
            'features_cons.*' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
            'is_public' => ['boolean'],
        ]);

        $data['trial_days'] = $data['trial_days'] ?? 0;

        $featuresPros = $data['features_pros'] ?? null;
        $featuresCons = $data['features_cons'] ?? null;
        $featuresText = (string) ($data['features_text'] ?? '');

        unset($data['features_pros'], $data['features_cons'], $data['features_text']);

        $pros = [];
        if (is_array($featuresPros)) {
            foreach ($featuresPros as $f) {
                $f = is_string($f) ? trim($f) : '';
                if ($f !== '') {
                    $pros[] = $f;
                }
            }
        }

        $cons = [];
        if (is_array($featuresCons)) {
            foreach ($featuresCons as $f) {
                $f = is_string($f) ? trim($f) : '';
                if ($f !== '') {
                    $cons[] = $f;
                }
            }
        }

        // Backward compatible fallback: if no pros/cons provided, accept newline textarea as pros.
        if (count($pros) === 0 && count($cons) === 0 && trim($featuresText) !== '') {
            $lines = preg_split("/\r\n|\r|\n/", $featuresText) ?: [];
            foreach ($lines as $line) {
                $line = is_string($line) ? trim($line) : '';
                if ($line !== '') {
                    $pros[] = $line;
                }
            }
        }

        $data['features'] = (count($pros) > 0 || count($cons) > 0)
            ? ['pros' => array_values($pros), 'cons' => array_values($cons)]
            : null;

        return $data;
    }
}

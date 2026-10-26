@php
    $outreachAddonActive = \App\Models\Addon::isInstalled('cold-email-outreach') && \App\Models\Addon::isActive('cold-email-outreach');
    $superScrapeAddonActive = \App\Models\Addon::isActive('super-scrape');
    $workspaceAddonActive = \App\Models\Addon::isActive('workspace');

    $customerGroupModel = $customerGroup ?? null;
    $isCreate = !$customerGroupModel;
    $headerTitle = $isCreate ? __('Create Customer Group') : __('Edit Customer Group');
    $submitLabel = $isCreate ? __('Create Customer Group') : __('Update Customer Group');
    $cancelUrl = $isCreate ? route('admin.customer-groups.index') : route('admin.customer-groups.show', $customerGroupModel);
    $nameValue = old('name', $customerGroupModel->name ?? '');
    $descriptionValue = old('description', $customerGroupModel->description ?? '');

    $sectionNavItems = [
        ['key' => 'basic', 'label' => __('Basic Information'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>'],
        ['key' => 'messages', 'label' => __('Messages'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'],
        ['key' => 'email_lists', 'label' => __('Email Lists'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>'],
        ['key' => 'campaigns', 'label' => __('Campaigns'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone-icon lucide-megaphone"><path d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/><path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"/><path d="M8 6v8"/></svg>'],
        ['key' => 'email_footer', 'label' => __('Email Footer'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="15" x2="21" y2="15"/></svg>'],
        ['key' => 'autoresponders', 'label' => __('Auto Responders'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle-reply-icon lucide-message-circle-reply"><path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/><path d="m10 15-3-3 3-3"/><path d="M7 12h8a2 2 0 0 1 2 2v1"/></svg>'],
        ['key' => 'automations', 'label' => __('Automations'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>'],
    ];

    if ($outreachAddonActive) {
        $sectionNavItems[] = ['key' => 'outreach', 'label' => __('Outreach'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>'];
    }

    if ($superScrapeAddonActive) {
        $sectionNavItems[] = ['key' => 'super_scrape', 'label' => __('Lead Scraper'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'];
        $sectionNavItems[] = ['key' => 'enrichment', 'label' => __('Enrichment APIs'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"/><path d="M3 12h18"/><circle cx="12" cy="12" r="9"/></svg>'];
    }

    $sectionNavItems[] = ['key' => 'servers', 'label' => __('Servers'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="8" rx="2"/><rect x="2" y="13" width="20" height="8" rx="2"/><line x1="6" y1="7" x2="6.01" y2="7"/><line x1="6" y1="17" x2="6.01" y2="17"/></svg>'];
    $sectionNavItems[] = ['key' => 'tracking_domains', 'label' => __('Tracking Domain'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>'];
    $sectionNavItems[] = ['key' => 'sending_domains', 'label' => __('Sending Domain'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'];
    $sectionNavItems[] = ['key' => 'sending_quota', 'label' => __('Email Sending Quota'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'];
    $sectionNavItems[] = ['key' => 'email_validation', 'label' => __('Email Validation'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'];
    $sectionNavItems[] = ['key' => 'integrations', 'label' => __('Integrations'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 22V7a1 1 0 0 0-1-1H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5a1 1 0 0 0-1-1H2"/><rect x="14" y="2" width="8" height="8" rx="1"/></svg>'];
    $sectionNavItems[] = ['key' => 'supabase', 'label' => __('Supabase'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>'];
    $sectionNavItems[] = ['key' => 'ai', 'label' => __('AI'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>'];

    if ($workspaceAddonActive) {
        $sectionNavItems[] = ['key' => 'workspaces', 'label' => __('Workspaces'), 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'];
    }

    $tabDescriptions = [
        'basic' => __('Manage the name and description for this customer group.'),
        'messages' => __('Customize access denied and limit-reached messages shown to customers.'),
        'email_lists' => __('Control email list access and limits for this group.'),
        'campaigns' => __('Manage campaign permissions, limits, and behavior.'),
        'email_footer' => __('Set the default email footer appended to campaign emails.'),
        'autoresponders' => __('Configure auto responder access and limits.'),
        'automations' => __('Configure automation access and limits.'),
        'outreach' => __('Configure cold email outreach access and limits.'),
        'super_scrape' => __('Configure Lead Scraper access and scraping limits.'),
        'enrichment' => __('Set group-level enrichment API keys and BYOK policy.'),
        'servers' => __('Manage delivery and bounce server access and limits.'),
        'tracking_domains' => __('Control tracking domain access and limits.'),
        'sending_domains' => __('Control sending domain access and limits.'),
        'sending_quota' => __('Set daily, weekly, and monthly sending quotas.'),
        'email_validation' => __('Manage email validation features and providers.'),
        'integrations' => __('Toggle available third-party integrations.'),
        'supabase' => __('Configure Supabase access modes, BYOS options, and usage limits.'),
        'ai' => __('Configure AI feature access and credit limits.'),
        'workspaces' => __('Control workspace creation and per-workspace limits.'),
    ];

    $tabsMeta = [];
    foreach ($sectionNavItems as $sectionNavItem) {
        $tabsMeta[$sectionNavItem['key']] = [
            'title' => $sectionNavItem['label'],
            'description' => $tabDescriptions[$sectionNavItem['key']] ?? '',
        ];
    }
@endphp

<div class="flex flex-1 h-full overflow-hidden" x-data="{ tabs: {{ Js::from($tabsMeta) }} }">
    {{-- Vertical Tab Navigation --}}
    <div class="w-56 shrink-0 border-r border-gray-200 dark:border-gray-700 overflow-y-auto bg-white dark:bg-gray-900">
        <nav class="space-y-1" aria-label="Customer group sections">
            @foreach($sectionNavItems as $sectionNavItem)
                <button
                    type="button"
                    @click="activeTab = '{{ $sectionNavItem['key'] }}'"
                    :class="activeTab === '{{ $sectionNavItem['key'] }}' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'"
                    class="group flex w-full items-center gap-3 px-3 py-2 text-sm font-medium transition-colors text-left"
                >
                    <span class="h-4 w-4 shrink-0 inline-flex items-center justify-center">{!! $sectionNavItem['icon'] !!}</span>
                    <span class="truncate">{{ $sectionNavItem['label'] }}</span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Content Area --}}
    <div class="flex-1 min-w-0 h-full flex flex-col">
        <div class="flex-1 min-h-0 overflow-y-auto">
            <div class="max-w-4xl mx-auto px-6 py-6">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white" x-text="tabs[activeTab]?.title || '{{ $headerTitle }}'">{{ $headerTitle }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="tabs[activeTab]?.description || ''"></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ $cancelUrl }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">{{ __('Cancel') }}</a>
                        <x-button type="submit" variant="primary">{{ $submitLabel }}</x-button>
                    </div>
                </div>

<div x-show="activeTab === 'basic'">
    <x-settings-section>
        <x-settings-row :title="__('Name')" :description="__('A display name for this customer group.')" for="name">
            <input type="text" name="name" id="name" value="{{ $nameValue }}" required class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </x-settings-row>
        <x-settings-row :title="__('Description')" :description="__('Optional notes about this group, visible to admins only.')" for="description" align="start">
            <textarea name="description" id="description" rows="3" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">{{ $descriptionValue }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'messages'">
    <x-settings-section>
        <x-settings-row :title="__('Default access denied message')" :description="__('Shown when a customer hits a restricted area without a specific override.')">
            <input type="text" name="messages[access][default]" value="{{ old('messages.access.default', data_get($settings ?? $defaultSettings ?? [], 'messages.access.default', '')) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" placeholder="{{ __('You have no access here.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Access denied: Tracking Domains')" :description="__('Message shown when customers cannot manage tracking domains.')">
            <input type="text" name="messages[access][domains][tracking_domains][can_manage]" value="{{ old('messages.access.domains.tracking_domains.can_manage', data_get($settings ?? $defaultSettings ?? [], 'messages.access.domains.tracking_domains.can_manage', '')) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" placeholder="{{ __('You have no access to tracking domains.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Access denied: Sending Domains')" :description="__('Message shown when customers cannot manage sending domains.')">
            <input type="text" name="messages[access][domains][sending_domains][can_manage]" value="{{ old('messages.access.domains.sending_domains.can_manage', data_get($settings ?? $defaultSettings ?? [], 'messages.access.domains.sending_domains.can_manage', '')) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" placeholder="{{ __('You have no access to sending domains.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Access denied: Delivery Servers')" :description="__('Message shown when customers cannot add delivery servers.')">
            <input type="text" name="messages[access][servers][permissions][can_add_delivery_servers]" value="{{ old('messages.access.servers.permissions.can_add_delivery_servers', data_get($settings ?? $defaultSettings ?? [], 'messages.access.servers.permissions.can_add_delivery_servers', '')) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" placeholder="{{ __('You have no access to delivery servers.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Limit reached: Tracking Domains')" :description="__('Message shown when the tracking domain limit has been reached.')">
            <input type="text" name="messages[limits][domains][tracking_domains][max_tracking_domains]" value="{{ old('messages.limits.domains.tracking_domains.max_tracking_domains', data_get($settings ?? $defaultSettings ?? [], 'messages.limits.domains.tracking_domains.max_tracking_domains', '')) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" placeholder="{{ __('Your tracking domain limits expired.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Limit reached: Sending Domains')" :description="__('Message shown when the sending domain limit has been reached.')">
            <input type="text" name="messages[limits][domains][sending_domains][max_sending_domains]" value="{{ old('messages.limits.domains.sending_domains.max_sending_domains', data_get($settings ?? $defaultSettings ?? [], 'messages.limits.domains.sending_domains.max_sending_domains', '')) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" placeholder="{{ __('Your sending domain limits expired.') }}">
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'automations'">
    <x-settings-section>
        <x-settings-row :title="__('Access to automations')" :description="__('Allow customers in this group to use the automations feature.')">
            <x-settings-toggle name="automations[enabled]" :checked="(bool) old('automations.enabled', data_get($settings ?? $defaultSettings ?? [], 'automations.enabled', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max automations to create')" :description="__('Maximum number of automations a customer can create. Set 0 for unlimited.')">
            <input type="number" min="0" name="automations[max_automations]" value="{{ old('automations.max_automations', data_get($settings ?? $defaultSettings ?? [], 'automations.max_automations', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>

@if($outreachAddonActive)
<div x-show="activeTab === 'outreach'">
    <x-settings-section>
        <x-settings-row :title="__('Access to outreach')" :description="__('Show the Outreach section for customers in this group and allow them to use the cold outreach addon.')">
            <x-settings-toggle name="outreach[access]" :checked="(bool) old('outreach.access', data_get($settings ?? $defaultSettings ?? [], 'outreach.access', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max outreach campaigns')" :description="__('Maximum number of outreach campaigns. Set 0 for unlimited.')">
            <input type="number" min="0" name="outreach[max_campaigns]" value="{{ old('outreach.max_campaigns', data_get($settings ?? $defaultSettings ?? [], 'outreach.max_campaigns', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max sequences per outreach')" :description="__('Maximum number of sequences per outreach campaign. Set 0 for unlimited.')">
            <input type="number" min="0" name="outreach[max_sequences_per_campaign]" value="{{ old('outreach.max_sequences_per_campaign', data_get($settings ?? $defaultSettings ?? [], 'outreach.max_sequences_per_campaign', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max leads per outreach')" :description="__('Maximum number of leads per outreach campaign. Set 0 for unlimited.')">
            <input type="number" min="0" name="outreach[max_leads_per_campaign]" value="{{ old('outreach.max_leads_per_campaign', data_get($settings ?? $defaultSettings ?? [], 'outreach.max_leads_per_campaign', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>
@endif

@if($superScrapeAddonActive)
<div x-show="activeTab === 'super_scrape'">
    <x-settings-section>
        <x-settings-row :title="__('Access to Lead Scraper')" :description="__('Allow customers in this group to access the Lead Scraper and run scraping jobs.')">
            <x-settings-toggle name="super_scrape[access]" :checked="(bool) old('super_scrape.access', data_get($settings ?? $defaultSettings ?? [], 'super_scrape.access', true))" />
        </x-settings-row>
        <x-settings-row :title="__('Monthly scraper credits override')" :description="__('Set 0 to use the global default from SuperScrape settings.')">
            <input type="number" min="0" name="super_scrape[monthly_credits]" value="{{ old('super_scrape.monthly_credits', data_get($settings ?? $defaultSettings ?? [], 'super_scrape.monthly_credits', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max scraping jobs')" :description="__('Maximum total scraping jobs allowed. Set 0 for unlimited.')">
            <input type="number" min="0" name="super_scrape[max_jobs]" value="{{ old('super_scrape.max_jobs', data_get($settings ?? $defaultSettings ?? [], 'super_scrape.max_jobs', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <div class="px-5 py-5">
            <div class="mb-3">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Scraping sources') }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Toggle which scraping sources are available to this group.') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Google') }}</div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Allow access to Google scraping.') }}</p>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="super_scrape[google_access]" value="0">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="super_scrape[google_access]" value="1" {{ old('super_scrape.google_access', data_get($settings ?? $defaultSettings ?? [], 'super_scrape.google_access', true)) ? 'checked' : '' }} class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 dark:bg-gray-700 peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-4 opacity-70">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Instagram') }} <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('(Coming soon)') }}</span></div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Will stay inactive until released.') }}</p>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="super_scrape[instagram_access]" value="0">
                                <label class="inline-flex items-center cursor-not-allowed">
                                    <input type="checkbox" disabled class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 rounded-full dark:bg-gray-700 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-4 opacity-70">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('LinkedIn') }} <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('(Coming soon)') }}</span></div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Will stay inactive until released.') }}</p>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="super_scrape[linkedin_access]" value="0">
                                <label class="inline-flex items-center cursor-not-allowed">
                                    <input type="checkbox" disabled class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 rounded-full dark:bg-gray-700 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-4 opacity-70">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('TikTok') }} <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('(Coming soon)') }}</span></div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Will stay inactive until released.') }}</p>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="super_scrape[tiktok_access]" value="0">
                                <label class="inline-flex items-center cursor-not-allowed">
                                    <input type="checkbox" disabled class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 rounded-full dark:bg-gray-700 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-4 opacity-70">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Facebook') }} <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('(Coming soon)') }}</span></div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Will stay inactive until released.') }}</p>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="super_scrape[facebook_access]" value="0">
                                <label class="inline-flex items-center cursor-not-allowed">
                                    <input type="checkbox" disabled class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 rounded-full dark:bg-gray-700 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-4 opacity-70">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('X') }} <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('(Coming soon)') }}</span></div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Will stay inactive until released.') }}</p>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="super_scrape[x_access]" value="0">
                                <label class="inline-flex items-center cursor-not-allowed">
                                    <input type="checkbox" disabled class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-gray-200 rounded-full dark:bg-gray-700 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5"></div>
                                </label>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </x-settings-section>
</div>
@endif

@if($superScrapeAddonActive)
<div x-show="activeTab === 'enrichment'">
    <x-settings-section>
        <x-settings-row :title="__('Apollo API Key')" :description="__('Apollo.io API key for B2B enrichment. Leave blank to use global settings.')">
            <input type="password" name="enrichment[apollo_key]"
                value="{{ old('enrichment.apollo_key', data_get($settings ?? $defaultSettings ?? [], 'enrichment.apollo_key', '')) }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                placeholder="apollo_key_...">
        </x-settings-row>
        <x-settings-row :title="__('Hunter.io API Key')" :description="__('Hunter.io API key for email discovery and verification. Leave blank to use global settings.')">
            <input type="password" name="enrichment[hunter_key]"
                value="{{ old('enrichment.hunter_key', data_get($settings ?? $defaultSettings ?? [], 'enrichment.hunter_key', '')) }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                placeholder="hunter_key_...">
        </x-settings-row>
        <x-settings-row :title="__('RocketReach API Key')" :description="__('RocketReach API key for professional profile lookup. Leave blank to use global settings.')">
            <input type="password" name="enrichment[rocketreach_key]"
                value="{{ old('enrichment.rocketreach_key', data_get($settings ?? $defaultSettings ?? [], 'enrichment.rocketreach_key', '')) }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                placeholder="rocketreach_key_...">
        </x-settings-row>
        <x-settings-row :title="__('Allow BYOK')" :description="__('Let customers in this group use their own enrichment API keys in their settings.')">
            <x-settings-toggle name="enrichment[allow_byok]" :checked="(bool) old('enrichment.allow_byok', data_get($settings ?? $defaultSettings ?? [], 'enrichment.allow_byok', true))" />
        </x-settings-row>
    </x-settings-section>
</div>
@endif

<div x-show="activeTab === 'integrations'">
    <x-settings-section>
        <x-settings-row :title="__('Google Integrations')" :description="__('Enables the Integrations → Google page for customers in this group (Sheets/Drive connect & sync).')">
            <x-settings-toggle name="integrations[permissions][can_access_google]" :checked="(bool) old('integrations.permissions.can_access_google', data_get($settings ?? $defaultSettings ?? [], 'integrations.permissions.can_access_google', false))" />
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'email_validation'">
    <x-settings-section>
        <x-settings-row :title="__('Access')" :description="__('Allow customers in this group to use email validation.')">
            <x-settings-toggle name="email_validation[access]" :checked="(bool) old('email_validation.access', data_get($settings ?? $defaultSettings ?? [], 'email_validation.access', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Must add own tool')" :description="__('Require customers to add their own validation provider keys.')">
            <x-settings-toggle name="email_validation[must_add]" :checked="(bool) old('email_validation.must_add', data_get($settings ?? $defaultSettings ?? [], 'email_validation.must_add', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max number of tools to add')" :description="__('Maximum number of validation providers a customer can add.')">
            <input type="number" min="0" name="email_validation[max_tools]" value="{{ old('email_validation.max_tools', data_get($settings ?? $defaultSettings ?? [], 'email_validation.max_tools', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Monthly validation limit')" :description="__('Maximum email validations allowed per month. Set 0 for unlimited.')">
            <input type="number" min="0" name="email_validation[monthly_limit]" value="{{ old('email_validation.monthly_limit', data_get($settings ?? $defaultSettings ?? [], 'email_validation.monthly_limit', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'supabase'">
    <x-settings-section>
        <x-settings-row :title="__('Access Supabase')" :description="__('Allow customers in this group to connect Supabase for their apps.')">
            <x-settings-toggle name="supabase[access]" :checked="(bool) old('supabase.access', data_get($settings ?? $defaultSettings ?? [], 'supabase.access', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Allow admin-managed Supabase')" :description="__('Customers can use the admin preset instead of bringing their own project.')">
            <x-settings-toggle name="supabase[allow_admin_managed]" :checked="(bool) old('supabase.allow_admin_managed', data_get($settings ?? $defaultSettings ?? [], 'supabase.allow_admin_managed', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Allow BYOS')" :description="__('Customers can connect their own Supabase project.')">
            <x-settings-toggle name="supabase[allow_byos]" :checked="(bool) old('supabase.allow_byos', data_get($settings ?? $defaultSettings ?? [], 'supabase.allow_byos', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Allow one-click BYOS')" :description="__('Show the Supabase OAuth one-click connector for BYOS users.')">
            <x-settings-toggle name="supabase[allow_byos_oauth]" :checked="(bool) old('supabase.allow_byos_oauth', data_get($settings ?? $defaultSettings ?? [], 'supabase.allow_byos_oauth', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Allow manual BYOS')" :description="__('Show the manual credentials form for BYOS users.')">
            <x-settings-toggle name="supabase[allow_byos_manual]" :checked="(bool) old('supabase.allow_byos_manual', data_get($settings ?? $defaultSettings ?? [], 'supabase.allow_byos_manual', true))" />
        </x-settings-row>
        <x-settings-row :title="__('Max admin-managed projects')" :description="__('Maximum admin-managed Supabase projects per customer. Set 0 for unlimited.')">
            <input type="number" min="0" name="supabase[limits][max_admin_managed_projects]" value="{{ old('supabase.limits.max_admin_managed_projects', data_get($settings ?? $defaultSettings ?? [], 'supabase.limits.max_admin_managed_projects', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max BYOS projects')" :description="__('Maximum BYOS Supabase projects per customer. Set 0 for unlimited.')">
            <input type="number" min="0" name="supabase[limits][max_byos_projects]" value="{{ old('supabase.limits.max_byos_projects', data_get($settings ?? $defaultSettings ?? [], 'supabase.limits.max_byos_projects', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max storage (GB)')" :description="__('Storage limit for admin-managed Supabase usage. Set 0 for unlimited.')">
            <input type="number" min="0" name="supabase[limits][max_storage_gb]" value="{{ old('supabase.limits.max_storage_gb', data_get($settings ?? $defaultSettings ?? [], 'supabase.limits.max_storage_gb', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max monthly active users')" :description="__('MAU limit for admin-managed Supabase usage. Set 0 for unlimited.')">
            <input type="number" min="0" name="supabase[limits][max_monthly_active_users]" value="{{ old('supabase.limits.max_monthly_active_users', data_get($settings ?? $defaultSettings ?? [], 'supabase.limits.max_monthly_active_users', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'ai'">
    <x-settings-section>
        <x-settings-row :title="__('Customer must use their API keys')" :description="__('Require customers to supply their own AI provider API keys.')">
            <x-settings-toggle name="ai[must_use_own_keys]" :checked="(bool) old('ai.must_use_own_keys', data_get($settings ?? $defaultSettings ?? [], 'ai.must_use_own_keys', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Token limit')" :description="__('Token quota applied when customers use the admin-provided keys.')">
            <input type="number" min="0" name="ai[token_limit]" value="{{ old('ai.token_limit', data_get($settings ?? $defaultSettings ?? [], 'ai.token_limit', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Image generation credits')" :description="__('Image credits applied when customers use the admin-provided keys.')">
            <input type="number" min="0" name="ai[image_credits]" value="{{ old('ai.image_credits', data_get($settings ?? $defaultSettings ?? [], 'ai.image_credits', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'email_lists'">
    <x-settings-section>
        <x-settings-row :title="__('Max number of lists')" :description="__('Maximum email lists a customer can create. Set 0 for unlimited.')">
            <input type="number" min="0" name="lists[limits][max_lists]" value="{{ old('lists.limits.max_lists', data_get($settings ?? $defaultSettings ?? [], 'lists.limits.max_lists', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max subscribers per list')" :description="__('Subscriber cap for each individual list. Set 0 for unlimited.')">
            <input type="number" min="0" name="lists[limits][max_subscribers_per_list]" value="{{ old('lists.limits.max_subscribers_per_list', data_get($settings ?? $defaultSettings ?? [], 'lists.limits.max_subscribers_per_list', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max forms per list')" :description="__('Maximum subscription forms allowed per list.')">
            <input type="number" min="0" name="lists[limits][max_forms_per_list]" value="{{ old('lists.limits.max_forms_per_list', data_get($settings ?? $defaultSettings ?? [], 'lists.limits.max_forms_per_list', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max subscribers (all lists)')" :description="__('Subscriber cap across all of the customer&rsquo;s lists. Set 0 for unlimited.')">
            <input type="number" min="0" name="lists[limits][max_subscribers]" value="{{ old('lists.limits.max_subscribers', data_get($settings ?? $defaultSettings ?? [], 'lists.limits.max_subscribers', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'email_footer'">
    @php
        $emailFooterValue = old('email_footer.content', data_get($settings ?? $defaultSettings ?? [], 'email_footer.content', ''));
    @endphp
    <x-settings-section>
        <div class="px-5 py-5">
            <div class="mb-3">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Footer content') }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {!! __('This footer is automatically appended to every email sent by customers in this group (campaigns, test emails, and outreach). Personalization tokens (e.g. <code>@{{first_name}}</code>) are supported.') !!}
                </p>
            </div>
            <textarea name="email_footer[content]" id="email_footer_content" class="hidden">{{ $emailFooterValue }}</textarea>
            <div
                id="email_footer_editor"
                data-outreach-rich-editor
                data-upload-url="{{ route('admin.uploads.inline-image') }}"
                data-step-id="customer-group-footer"
                data-variant="a"
                data-field="content"
                data-value="{{ $emailFooterValue }}"
                data-placeholder="{{ __('e.g. -- Sent via MyCompany. 123 Main St, City, Country') }}"
                data-target-textarea="email_footer_content"
            ></div>
            @error('email_footer.content')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Leave empty to disable. Max 20,000 characters.') }}</p>
        </div>
    </x-settings-section>
</div>

@once
    @push('scripts')
    @vite('resources/js/outreach-sequence-editor.jsx')
    <script>
        (function () {
            const sync = (event) => {
                const detail = event.detail || {};
                if (detail.stepId !== 'customer-group-footer') {
                    return;
                }
                const mount = document.getElementById('email_footer_editor');
                if (!mount) return;
                const targetId = mount.dataset.targetTextarea;
                if (!targetId) return;
                const target = document.getElementById(targetId);
                if (!target) return;
                target.value = String(detail.value || '');
            };
            window.addEventListener('outreach-plate-change', sync);
        })();
    </script>
    @endpush
@endonce

<div x-show="activeTab === 'campaigns'">
    <x-settings-section>
        <x-settings-row :title="__('Max campaigns to create')" :description="__('Total number of campaigns a customer can create. Set 0 for unlimited.')">
            <input type="number" min="0" name="campaigns[limits][max_campaigns]" value="{{ old('campaigns.limits.max_campaigns', data_get($settings ?? $defaultSettings ?? [], 'campaigns.limits.max_campaigns', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max campaigns to run')" :description="__('Maximum number of campaigns that can be active concurrently. Set 0 for unlimited.')">
            <input type="number" min="0" name="campaigns[limits][max_active_campaigns]" value="{{ old('campaigns.limits.max_active_campaigns', data_get($settings ?? $defaultSettings ?? [], 'campaigns.limits.max_active_campaigns', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Access to A/B testing')" :description="__('Allow customers to use A/B testing in their campaigns.')">
            <x-settings-toggle name="campaigns[features][ab_testing]" :checked="(bool) old('campaigns.features.ab_testing', data_get($settings ?? $defaultSettings ?? [], 'campaigns.features.ab_testing', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Can create campaigns')" :description="__('Allow customers to create new campaigns.')">
            <x-settings-toggle name="campaigns[permissions][can_create_campaigns]" :checked="(bool) old('campaigns.permissions.can_create_campaigns', data_get($settings ?? $defaultSettings ?? [], 'campaigns.permissions.can_create_campaigns', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Can edit campaigns')" :description="__('Allow customers to edit existing campaigns.')">
            <x-settings-toggle name="campaigns[permissions][can_edit_campaigns]" :checked="(bool) old('campaigns.permissions.can_edit_campaigns', data_get($settings ?? $defaultSettings ?? [], 'campaigns.permissions.can_edit_campaigns', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Can start/pause/resume campaigns')" :description="__('Allow customers to control campaign run state.')">
            <x-settings-toggle name="campaigns[permissions][can_start_campaigns]" :checked="(bool) old('campaigns.permissions.can_start_campaigns', data_get($settings ?? $defaultSettings ?? [], 'campaigns.permissions.can_start_campaigns', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Can delete campaigns')" :description="__('Allow customers to delete campaigns.')">
            <x-settings-toggle name="campaigns[permissions][can_delete_campaigns]" :checked="(bool) old('campaigns.permissions.can_delete_campaigns', data_get($settings ?? $defaultSettings ?? [], 'campaigns.permissions.can_delete_campaigns', false))" />
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'autoresponders'">
    <x-settings-section>
        <x-settings-row :title="__('Access to auto responders')" :description="__('Allow customers in this group to use auto responders.')">
            <x-settings-toggle name="autoresponders[enabled]" :checked="(bool) old('autoresponders.enabled', data_get($settings ?? $defaultSettings ?? [], 'autoresponders.enabled', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max auto responders to create')" :description="__('Maximum number of auto responders a customer can create. Set 0 for unlimited.')">
            <input type="number" min="0" name="autoresponders[max_autoresponders]" value="{{ old('autoresponders.max_autoresponders', data_get($settings ?? $defaultSettings ?? [], 'autoresponders.max_autoresponders', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'servers'">
    @php
        $allocatedDeliveryServerOptions = collect($deliveryServers ?? [])->map(function ($server) {
            $ownerLabel = $server->customer ? ('Customer: ' . $server->customer->email) : 'System';

            return [
                'id' => (string) $server->id,
                'label' => $server->name . ' (' . $ownerLabel . ')',
            ];
        })->values()->all();
        $showExtendedFeaturePopup = !($hasExtendedLicense ?? false);
    @endphp

    <x-settings-section class="mb-6">
        <div class="px-5 py-5">
            <div class="mb-3">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Allocated Delivery Servers') }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('If set, this group can only use these delivery servers (unless a customer has their own allocation).') }}</p>
            </div>
            <x-tag-multiselect
                name="allocated_delivery_server_ids[]"
                :options="$allocatedDeliveryServerOptions"
                :selected="old('allocated_delivery_server_ids', $allocatedDeliveryServerIds ?? [])"
                placeholder="{{ __('Select delivery servers') }}"
                search-placeholder="{{ __('Search servers...') }}"
            />
            @error('allocated_delivery_server_ids')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('allocated_delivery_server_ids.*')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </x-settings-section>

    <x-modal name="pro-feature-extended-mailbox-providers-admin" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Pro Feature') }}</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                {{ __('Gmail and Outlook mailbox providers are available as an Extended/Pro feature.') }}
            </p>
            <div class="mt-6 flex justify-end">
                <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'pro-feature-extended-mailbox-providers-admin')">{{ __('Close') }}</x-button>
            </div>
        </div>
    </x-modal>

    <div class="space-y-6">
        <x-settings-section>
            <div class="px-5 pt-4 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Delivery servers') }}</div>
            <x-settings-row :title="__('Must add delivery server')" :description="__('Require customers to add their own delivery server before sending.')">
                <x-settings-toggle name="servers[permissions][must_add_delivery_server]" :checked="(bool) old('servers.permissions.must_add_delivery_server', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.must_add_delivery_server', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can add delivery servers')" :description="__('Allow customers to add their own delivery servers.')">
                <x-settings-toggle name="servers[permissions][can_add_delivery_servers]" :checked="(bool) old('servers.permissions.can_add_delivery_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_add_delivery_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can use Gmail/Outlook mailbox providers (Extended)')" :description="__('Premium feature: lets customers connect Gmail and Outlook mailbox providers.')">
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="if (@json($showExtendedFeaturePopup)) { $dispatch('open-modal', 'pro-feature-extended-mailbox-providers-admin') }"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-amber-300 bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:border-amber-700/70 dark:bg-amber-900/25 dark:text-amber-300 dark:hover:bg-amber-900/40"
                        title="{{ __('Pro feature') }}"
                        aria-label="{{ __('Pro feature') }}"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M5 18h14l1.5-9-4.5 2.5L12 4 7.5 11.5 3 9 5 18zm0 2a1 1 0 100 2h14a1 1 0 100-2H5z" />
                        </svg>
                    </button>
                    <input type="hidden" name="servers[permissions][can_use_extended_mailbox_providers]" value="0">
                    <label class="inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="servers[permissions][can_use_extended_mailbox_providers]"
                            value="1"
                            {{ old('servers.permissions.can_use_extended_mailbox_providers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_use_extended_mailbox_providers', false)) ? 'checked' : '' }}
                            class="sr-only peer"
                            @change="if (@json($showExtendedFeaturePopup) && $event.target.checked) { $event.target.checked = false; $dispatch('open-modal', 'pro-feature-extended-mailbox-providers-admin') }"
                        >
                        <div class="relative w-11 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 dark:bg-gray-700 peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                    </label>
                </div>
            </x-settings-row>
            <x-settings-row :title="__('Max delivery servers')" :description="__('Maximum delivery servers a customer can add. Set 0 for unlimited.')">
                <input type="number" min="0" name="servers[limits][max_delivery_servers]" value="{{ old('servers.limits.max_delivery_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.limits.max_delivery_servers', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
            </x-settings-row>
        </x-settings-section>

        <x-settings-section>
            <div class="px-5 pt-4 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Bounce servers') }}</div>
            <x-settings-row :title="__('Must add bounce server')" :description="__('Require customers to add a bounce server.')">
                <x-settings-toggle name="servers[permissions][must_add_bounce_server]" :checked="(bool) old('servers.permissions.must_add_bounce_server', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.must_add_bounce_server', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Access bounce servers')" :description="__('Allow customers to view bounce server pages.')">
                <x-settings-toggle name="servers[permissions][can_access_bounce_servers]" :checked="(bool) old('servers.permissions.can_access_bounce_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_access_bounce_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can add bounce servers')" :description="__('Allow customers to add their own bounce servers.')">
                <x-settings-toggle name="servers[permissions][can_add_bounce_servers]" :checked="(bool) old('servers.permissions.can_add_bounce_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_add_bounce_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can edit bounce servers')" :description="__('Allow customers to edit existing bounce servers.')">
                <x-settings-toggle name="servers[permissions][can_edit_bounce_servers]" :checked="(bool) old('servers.permissions.can_edit_bounce_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_edit_bounce_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can delete bounce servers')" :description="__('Allow customers to delete bounce servers.')">
                <x-settings-toggle name="servers[permissions][can_delete_bounce_servers]" :checked="(bool) old('servers.permissions.can_delete_bounce_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_delete_bounce_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Max bounce servers')" :description="__('Maximum bounce servers a customer can add. Set 0 for unlimited.')">
                <input type="number" min="0" name="servers[limits][max_bounce_servers]" value="{{ old('servers.limits.max_bounce_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.limits.max_bounce_servers', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
            </x-settings-row>
        </x-settings-section>

        <x-settings-section>
            <div class="px-5 pt-4 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Reply servers') }}</div>
            <x-settings-row :title="__('Must add reply server')" :description="__('Require customers to add a reply server.')">
                <x-settings-toggle name="servers[permissions][must_add_reply_server]" :checked="(bool) old('servers.permissions.must_add_reply_server', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.must_add_reply_server', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Access reply servers')" :description="__('Allow customers to view reply server pages.')">
                <x-settings-toggle name="servers[permissions][can_access_reply_servers]" :checked="(bool) old('servers.permissions.can_access_reply_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_access_reply_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can add reply servers')" :description="__('Allow customers to add their own reply servers.')">
                <x-settings-toggle name="servers[permissions][can_add_reply_servers]" :checked="(bool) old('servers.permissions.can_add_reply_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_add_reply_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can edit reply servers')" :description="__('Allow customers to edit existing reply servers.')">
                <x-settings-toggle name="servers[permissions][can_edit_reply_servers]" :checked="(bool) old('servers.permissions.can_edit_reply_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_edit_reply_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can delete reply servers')" :description="__('Allow customers to delete reply servers.')">
                <x-settings-toggle name="servers[permissions][can_delete_reply_servers]" :checked="(bool) old('servers.permissions.can_delete_reply_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.permissions.can_delete_reply_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Max reply servers')" :description="__('Maximum reply servers a customer can add. Set 0 for unlimited.')">
                <input type="number" min="0" name="servers[limits][max_reply_servers]" value="{{ old('servers.limits.max_reply_servers', data_get($settings ?? $defaultSettings ?? [], 'servers.limits.max_reply_servers', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
            </x-settings-row>
        </x-settings-section>
    </div>
</div>{{-- /x-show servers --}}


<div x-show="activeTab === 'tracking_domains'">
    <x-settings-section>
        <x-settings-row :title="__('Must add tracking domain')" :description="__('Require customers to add at least one tracking domain.')">
            <x-settings-toggle name="domains[tracking_domains][must_add]" :checked="(bool) old('domains.tracking_domains.must_add', data_get($settings ?? $defaultSettings ?? [], 'domains.tracking_domains.must_add', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Access')" :description="__('Allow customers to view and manage tracking domains.')">
            <x-settings-toggle name="domains[tracking_domains][can_manage]" :checked="(bool) old('domains.tracking_domains.can_manage', data_get($settings ?? $defaultSettings ?? [], 'domains.tracking_domains.can_manage', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max tracking domains')" :description="__('Maximum tracking domains a customer can add. Set 0 for unlimited.')">
            <input type="number" min="0" name="domains[tracking_domains][max_tracking_domains]" value="{{ old('domains.tracking_domains.max_tracking_domains', data_get($settings ?? $defaultSettings ?? [], 'domains.tracking_domains.max_tracking_domains', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'sending_domains'">
    <x-settings-section>
        <x-settings-row :title="__('Must add sending domain')" :description="__('Require customers to add at least one sending domain.')">
            <x-settings-toggle name="domains[sending_domains][must_add]" :checked="(bool) old('domains.sending_domains.must_add', data_get($settings ?? $defaultSettings ?? [], 'domains.sending_domains.must_add', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Access')" :description="__('Allow customers to view and manage sending domains.')">
            <x-settings-toggle name="domains[sending_domains][can_manage]" :checked="(bool) old('domains.sending_domains.can_manage', data_get($settings ?? $defaultSettings ?? [], 'domains.sending_domains.can_manage', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max sending domains')" :description="__('Maximum sending domains a customer can add. Set 0 for unlimited.')">
            <input type="number" min="0" name="domains[sending_domains][max_sending_domains]" value="{{ old('domains.sending_domains.max_sending_domains', data_get($settings ?? $defaultSettings ?? [], 'domains.sending_domains.max_sending_domains', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>

<div x-show="activeTab === 'sending_quota'">
    <x-settings-section>
        <x-settings-row :title="__('Daily sending quota')" :description="__('Maximum emails this group can send per day. Set 0 for unlimited.')">
            <input type="number" min="0" name="sending_quota[daily_quota]" value="{{ old('sending_quota.daily_quota', data_get($settings ?? $defaultSettings ?? [], 'sending_quota.daily_quota', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Weekly sending quota')" :description="__('Maximum emails this group can send per week. Set 0 for unlimited.')">
            <input type="number" min="0" name="sending_quota[weekly_quota]" value="{{ old('sending_quota.weekly_quota', data_get($settings ?? $defaultSettings ?? [], 'sending_quota.weekly_quota', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Monthly sending quota')" :description="__('Maximum emails this group can send per month. Set 0 for unlimited.')">
            <input type="number" min="0" name="sending_quota[monthly_quota]" value="{{ old('sending_quota.monthly_quota', data_get($settings ?? $defaultSettings ?? [], 'sending_quota.monthly_quota', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>

@if($workspaceAddonActive)
<div x-show="activeTab === 'workspaces'">
    <x-settings-section>
        <x-settings-row :title="__('Can create workspaces')" :description="__('Allow customers in this group to create and manage workspaces.')">
            <x-settings-toggle name="workspaces[permissions][can_create_workspace]" :checked="(bool) old('workspaces.permissions.can_create_workspace', data_get($settings ?? $defaultSettings ?? [], 'workspaces.permissions.can_create_workspace', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max workspaces')" :description="__('Maximum workspaces a customer can create. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_workspaces]" value="{{ old('workspaces.limits.max_workspaces', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_workspaces', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max team members per workspace')" :description="__('Cap on team members in each workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_team_members]" value="{{ old('workspaces.limits.max_team_members', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_team_members', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max email lists per workspace')" :description="__('Cap on email lists per workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_email_lists]" value="{{ old('workspaces.limits.max_email_lists', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_email_lists', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max templates per workspace')" :description="__('Cap on templates per workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_templates]" value="{{ old('workspaces.limits.max_templates', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_templates', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max campaigns per workspace')" :description="__('Cap on campaigns per workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_campaigns]" value="{{ old('workspaces.limits.max_campaigns', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_campaigns', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        @if($outreachAddonActive)
        <x-settings-row :title="__('Max outreach campaigns per workspace')" :description="__('Cap on outreach campaigns per workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_outreach_campaigns]" value="{{ old('workspaces.limits.max_outreach_campaigns', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_outreach_campaigns', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        @endif
        <x-settings-row :title="__('Max inbox accounts per workspace')" :description="__('Cap on inbox accounts per workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_inbox_accounts]" value="{{ old('workspaces.limits.max_inbox_accounts', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_inbox_accounts', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max delivery servers per workspace')" :description="__('Cap on delivery servers per workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_delivery_servers]" value="{{ old('workspaces.limits.max_delivery_servers', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_delivery_servers', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max bounce servers per workspace')" :description="__('Cap on bounce servers per workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_bounce_servers]" value="{{ old('workspaces.limits.max_bounce_servers', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_bounce_servers', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max sending domains per workspace')" :description="__('Cap on sending domains per workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_sending_domains]" value="{{ old('workspaces.limits.max_sending_domains', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_sending_domains', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
        <x-settings-row :title="__('Max tracking domains per workspace')" :description="__('Cap on tracking domains per workspace. Set 0 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_tracking_domains]" value="{{ old('workspaces.limits.max_tracking_domains', data_get($settings ?? $defaultSettings ?? [], 'workspaces.limits.max_tracking_domains', 0)) }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
        </x-settings-row>
    </x-settings-section>
</div>
@endif

            </div>{{-- /max-w-4xl --}}
        </div>{{-- /overflow-y-auto --}}
    </div>{{-- /content column --}}
</div>{{-- /flex flex-1 outer --}}

@csrf
@php
    $inputClass = 'block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm';
    $toggleClass = "relative w-11 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 dark:bg-gray-700 peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white";
    $planFeatures = $plan->features ?? null;
    $outreachAddonActive = \App\Models\Addon::isInstalled('cold-email-outreach') && \App\Models\Addon::isActive('cold-email-outreach');
    $superScrapeAddonActive = \App\Models\Addon::isActive('super-scrape');
    $workspaceAddonActive = \App\Models\Addon::isActive('workspace');
    $initialPros = [];
    $initialCons = [];

    if (is_array($planFeatures) && (array_key_exists('pros', $planFeatures) || array_key_exists('cons', $planFeatures))) {
        $initialPros = is_array($planFeatures['pros'] ?? null) ? $planFeatures['pros'] : [];
        $initialCons = is_array($planFeatures['cons'] ?? null) ? $planFeatures['cons'] : [];
    } elseif (is_array($planFeatures)) {
        $initialPros = $planFeatures;
    }

    $initialPros = array_values(array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : '', $initialPros), fn ($v) => $v !== ''));
    $initialCons = array_values(array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : '', $initialCons), fn ($v) => $v !== ''));
    $paymentUrl = isset($plan) && $plan->exists ? route('pricing.checkout', ['slug' => $plan->slug]) : '';
@endphp

<div x-data="{ activePlanSection: 'general' }" class="flex flex-1 h-full overflow-hidden">
    <aside class="w-56 shrink-0 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
        <nav class="space-y-1" aria-label="Plan sections">
            <button type="button" @click="activePlanSection = 'general'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'general' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <span>{{ __('General info') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'pricing'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'pricing' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-pound-sterling-icon lucide-circle-pound-sterling"><circle cx="12" cy="12" r="10"/><path d="M10 16V9.5a1 1 0 0 1 5 0"/><path d="M8 12h4"/><path d="M8 16h7"/></svg>
                <span>{{ __('Pricing') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'features'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'features' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-text-icon lucide-book-text"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"/><path d="M8 11h8"/><path d="M8 7h6"/></svg>
                <span>{{ __('Features') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'messages'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'messages' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>{{ __('Messages') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'email_lists'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'email_lists' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <span>{{ __('Email Lists') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'campaigns'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'campaigns' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone-icon lucide-megaphone"><path d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/><path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"/><path d="M8 6v8"/></svg>
                <span>{{ __('Campaigns') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'email_footer'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'email_footer' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
                <span>{{ __('Email Footer') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'email_templates'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'email_templates' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="14" x="3" y="5" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                <span>{{ __('Email Templates') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'autoresponders'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'autoresponders' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle-reply-icon lucide-message-circle-reply"><path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/><path d="m10 15-3-3 3-3"/><path d="M7 12h8a2 2 0 0 1 2 2v1"/></svg>
                <span>{{ __('Auto Responders') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'automations'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'automations' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                <span>{{ __('Automations') }}</span>
            </button>
            @if($outreachAddonActive ?? false)
            <button type="button" @click="activePlanSection = 'outreach'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'outreach' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                <span>{{ __('Outreach') }}</span>
            </button>
            @endif
            @if($superScrapeAddonActive ?? false)
            <button type="button" @click="activePlanSection = 'super_scrape'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'super_scrape' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <span>{{ __('Lead Scraper') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'enrichment'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'enrichment' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"/><path d="M3 12h18"/><circle cx="12" cy="12" r="9"/></svg>
                <span>{{ __('Enrichment APIs') }}</span>
            </button>
            @endif
            <button type="button" @click="activePlanSection = 'servers'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'servers' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="8" rx="2"/><rect x="2" y="13" width="20" height="8" rx="2"/><line x1="6" y1="7" x2="6.01" y2="7"/><line x1="6" y1="17" x2="6.01" y2="17"/></svg>
                <span>{{ __('Servers') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'tracking_domains'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'tracking_domains' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <span>{{ __('Tracking Domain') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'sending_domains'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'sending_domains' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ __('Sending Domain') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'sending_quota'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'sending_quota' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>{{ __('Email Sending Quota') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'email_validation'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'email_validation' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>{{ __('Email Validation') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'integrations'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'integrations' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 22V7a1 1 0 0 0-1-1H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5a1 1 0 0 0-1-1H2"/><rect x="14" y="2" width="8" height="8" rx="1"/></svg>
                <span>{{ __('Integrations') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'supabase'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'supabase' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
                <span>{{ __('Supabase') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'ai'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'ai' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                <span>{{ __('AI') }}</span>
            </button>
            <button type="button" @click="activePlanSection = 'ai_builder'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'ai_builder' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/></svg>
                <span>{{ __('AI Builder') }}</span>
            </button>
            @if($workspaceAddonActive ?? false)
            <button type="button" @click="activePlanSection = 'workspaces'" class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium transition-colors" :class="activePlanSection === 'workspaces' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>{{ __('Workspaces') }}</span>
            </button>
            @endif
        </nav>
    </aside>

    <div class="flex-1 min-w-0 h-full overflow-y-auto overscroll-contain">
        <div class="max-w-[60%] mx-auto px-6 py-6">
    <section x-show="activePlanSection === 'general'" x-cloak>
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('General info') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Manage identity, visibility, customer access, and checkout link details for this plan.') }}</p>
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">
                {{ __('Save') }}
            </button>
        </div>
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Plan name displayed to customers and admins.') }}</p>
                </div>
                <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}" class="{{ $inputClass }}">
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Slug') }}</label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('URL-friendly identifier for this plan.') }}</p>
                </div>
                <input type="text" name="slug" value="{{ old('slug', $plan->slug ?? '') }}" class="{{ $inputClass }}">
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Description') }}</label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Short summary shown on pricing and checkout views.') }}</p>
                </div>
                <textarea name="description" rows="3" class="{{ $inputClass }}">{{ old('description', $plan->description ?? '') }}</textarea>
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('CTA Button Text') }}</label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Text shown on the plan action button.') }}</p>
                </div>
                <input type="text" name="cta_text" value="{{ old('cta_text', $plan->cta_text ?? '') }}" class="{{ $inputClass }}">
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Active') }}</label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Enable this plan for subscriptions and checkout.') }}</p>
                </div>
                <div class="flex w-full justify-end">
                    <input type="hidden" name="is_active" value="0">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active ?? true)) class="sr-only peer">
                        <div class="{{ $toggleClass }}"></div>
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Popular') }}</label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Highlight this plan as the recommended option.') }}</p>
                </div>
                <div class="flex w-full justify-end">
                    <input type="hidden" name="is_popular" value="0">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_popular" value="1" @checked(old('is_popular', $plan->is_popular ?? false)) class="sr-only peer">
                        <div class="{{ $toggleClass }}"></div>
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Private') }}</label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Hide this plan from public pricing while keeping it available by direct link/admin use.') }}</p>
                </div>
                <div class="flex w-full justify-end">
                    <input type="hidden" name="is_public" value="1">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_public" value="0" @checked(! (bool) old('is_public', $plan->is_public ?? true)) class="sr-only peer">
                        <div class="{{ $toggleClass }}"></div>
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Legacy Customer Group (Optional)') }}</label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Optional: attach a legacy customer group for backward compatibility. Access and limits are now managed directly on this plan.') }}</p>
                </div>
                <div class="space-y-2">
                    <select name="customer_group_id" class="{{ $inputClass }}">
                        <option value="">{{ __('None') }}</option>
                        @foreach($groups ?? [] as $group)
                            <option value="{{ $group->id }}" @selected(old('customer_group_id', $plan->customer_group_id ?? '') == $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Auto Generated Payment URL') }}</label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Direct checkout URL generated after the plan is saved.') }}</p>
                </div>
                <input type="text" value="{{ $paymentUrl ?: __('Save the plan first to generate a payment URL.') }}" readonly class="{{ $inputClass }} bg-gray-50 dark:bg-gray-900">
            </div>
        </div>
    </section>

    <section x-show="activePlanSection === 'pricing'" x-cloak>
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Pricing') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Configure billing amount, renewal cycle, trial period, and payment provider mapping (Stripe, Dodo, Paddle, LemonSqueezy).') }}</p>
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">
                {{ __('Save') }}
            </button>
        </div>
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Price') }}</label><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Base amount charged for this plan.') }}</p></div>
                <input type="number" step="0.01" name="price" value="{{ old('price', $plan->price ?? '') }}" class="{{ $inputClass }}">
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Currency') }}</label><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Three-letter currency code, for example USD.') }}</p></div>
                <input type="text" name="currency" value="{{ old('currency', $plan->currency ?? 'USD') }}" class="{{ $inputClass }}">
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Cycle') }}</label><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('How often customers are billed for this plan.') }}</p></div>
                <select name="billing_cycle" class="{{ $inputClass }}">
                    <option value="monthly" @selected(old('billing_cycle', $plan->billing_cycle ?? 'monthly') === 'monthly')>{{ __('Monthly') }}</option>
                    <option value="yearly" @selected(old('billing_cycle', $plan->billing_cycle ?? '') === 'yearly')>{{ __('Yearly') }}</option>
                    <option value="lifetime" @selected(old('billing_cycle', $plan->billing_cycle ?? '') === 'lifetime')>{{ __('Lifetime') }}</option>
                </select>
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Trial Days') }}</label><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Number of free trial days before billing starts.') }}</p></div>
                <input type="number" name="trial_days" value="{{ old('trial_days', $plan->trial_days ?? 0) }}" class="{{ $inputClass }}">
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Stripe Price ID') }}</label><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Optional Stripe price identifier used for Stripe checkout/subscriptions.') }}</p></div>
                <input type="text" name="stripe_price_id" value="{{ old('stripe_price_id', $plan->stripe_price_id ?? '') }}" class="{{ $inputClass }}">
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Dodo Product ID') }}</label><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Dodo Payments product ID for this plan.') }}</p></div>
                <input type="text" name="dodo_product_id" value="{{ old('dodo_product_id', $plan->dodo_product_id ?? '') }}" class="{{ $inputClass }}">
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Paddle Price ID') }}</label><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Paddle Billing price ID for this plan.') }}</p></div>
                <input type="text" name="paddle_price_id" value="{{ old('paddle_price_id', $plan->paddle_price_id ?? '') }}" class="{{ $inputClass }}">
            </div>
            <div class="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:px-6">
                <div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('LemonSqueezy Variant ID') }}</label><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('LemonSqueezy variant ID for this plan.') }}</p></div>
                <input type="text" name="lemonsqueezy_variant_id" value="{{ old('lemonsqueezy_variant_id', $plan->lemonsqueezy_variant_id ?? '') }}" class="{{ $inputClass }}">
            </div>
        </div>
    </section>

    <section x-show="activePlanSection === 'features'" x-cloak>
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Features') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Add included benefits as pros and limitations as cons for pricing cards.') }}</p>
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">
                {{ __('Save') }}
            </button>
        </div>
        <div class="bg-white dark:border-gray-700 dark:bg-gray-800">
            <div x-data="{
                pros: @js(array_values(old('features_pros', $initialPros))),
                cons: @js(array_values(old('features_cons', $initialCons))),
                prosInput: '',
                consInput: '',
                addPros() { const v = (this.prosInput || '').trim(); if (!v) return; this.pros.push(v); this.prosInput = ''; },
                addCons() { const v = (this.consInput || '').trim(); if (!v) return; this.cons.push(v); this.consInput = ''; },
                removePros(i) { this.pros.splice(i, 1); },
                removeCons(i) { this.cons.splice(i, 1); },
                moveItem(listName, from, to) {
                    if (from === to || from === null || to === null) return;
                    const list = this[listName];
                    const item = list.splice(from, 1)[0];
                    list.splice(to, 0, item);
                },
            }" class="grid grid-cols-1 divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700 md:grid-cols-2 md:divide-x md:divide-y-0">
                <div class="p-4">
                    <div class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ __('Pros') }}</div>
                    <div class="mt-3 flex gap-2">
                        <input type="text" x-model="prosInput" @keydown.enter.prevent="addPros" class="{{ $inputClass }}" placeholder="{{ __('Add a pro...') }}">
                        <button type="button" @click="addPros" class="rounded-md border border-gray-300 px-3 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">{{ __('Add') }}</button>
                    </div>
                    <div class="mt-4 space-y-2">
                        <template x-for="(item, i) in pros" :key="'pro-' + i">
                            <div draggable="true" @dragstart="$event.dataTransfer.setData('text/plain', i)" @dragover.prevent @drop.prevent="moveItem('pros', Number($event.dataTransfer.getData('text/plain')), i)" class="flex cursor-move items-center gap-2 rounded-md bg-green-50 px-3 py-2 dark:bg-green-900/20">
                                <input type="hidden" name="features_pros[]" :value="item">
                                <svg class="h-5 w-5 flex-shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                <div class="flex-1 text-sm text-gray-700 dark:text-gray-200" x-text="item"></div>
                                <button type="button" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" @click="removePros(i)">×</button>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="p-4">
                    <div class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ __('Cons') }}</div>
                    <div class="mt-3 flex gap-2">
                        <input type="text" x-model="consInput" @keydown.enter.prevent="addCons" class="{{ $inputClass }}" placeholder="{{ __('Add a con...') }}">
                        <button type="button" @click="addCons" class="rounded-md border border-gray-300 px-3 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">{{ __('Add') }}</button>
                    </div>
                    <div class="mt-4 space-y-2">
                        <template x-for="(item, i) in cons" :key="'con-' + i">
                            <div draggable="true" @dragstart="$event.dataTransfer.setData('text/plain', i)" @dragover.prevent @drop.prevent="moveItem('cons', Number($event.dataTransfer.getData('text/plain')), i)" class="flex cursor-move items-center gap-2 rounded-md bg-red-50 px-3 py-2 dark:bg-red-900/20">
                                <input type="hidden" name="features_cons[]" :value="item">
                                <svg class="h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                <div class="flex-1 text-sm text-gray-700 dark:text-gray-200" x-text="item"></div>
                                <button type="button" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" @click="removeCons(i)">×</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('admin.plans._group_settings')

    @once
        @push('scripts')
        @vite('resources/js/outreach-sequence-editor.jsx')
        <script>
            (function () {
                const sync = (event) => {
                    const detail = event.detail || {};
                    if (detail.stepId !== 'plan-group-footer') {
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
    </div>
    </div>
</div>

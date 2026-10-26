@extends('layouts.admin')

@section('title', __('AI Dashboard'))
@section('page-title', __('AI Dashboard'))
@section('force-sidebar-collapsed', '1')

@section('content-padding-classes', 'p-0 overflow-hidden')

@section('content')
@php
    $tabs = [
        'ai-dashboard' => ['label' => __('AI Dashboard'), 'description' => __('Admin AI usage and cost overview.'), 'icon' => 'dashboard'],
        'api-keys' => ['label' => __('API Keys'), 'description' => __('Provider API keys used when customers do not supply their own.'), 'icon' => 'key'],
        'provider-controls' => ['label' => __('Provider Models'), 'description' => __('Manage which AI providers and models are available.'), 'icon' => 'toggle'],
        'api-pricing' => ['label' => __('API Pricing'), 'description' => __('Input/output cost per 1M tokens per provider and model.'), 'icon' => 'coins'],
        'generations' => ['label' => __('Generations'), 'description' => __('All admin AI generation logs and details.'), 'icon' => 'history'],
        'rag' => ['label' => __('RAG'), 'description' => __('Retrieval Augmented Generation configuration.'), 'icon' => 'database'],
    ];
    $generationTotalCost = $generations instanceof \Illuminate\Contracts\Pagination\Paginator
        ? $generations->getCollection()->sum(fn ($g) => $g->steps->sum('cost_cents'))
        : collect($generations ?? [])->sum(fn ($g) => $g->steps->sum('cost_cents'));
    $hasGenerationPages = $generations instanceof \Illuminate\Contracts\Pagination\Paginator && $generations->hasPages();
@endphp
<div
    x-data="{
        tabs: @js($tabs),
        activeTab: (() => {
            const params = new URLSearchParams(window.location.search);
            return params.get('tab') || (params.get('generation_id') ? 'generations' : 'ai-dashboard');
        })(),
        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        }
    }"
    class="flex flex-1 h-full overflow-hidden"
>
    {{-- Vertical Tab Navigation --}}
    <div class="w-56 shrink-0 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
        <nav class="space-y-1" aria-label="AI Dashboard tabs">
            @foreach($tabs as $tabKey => $tabConfig)
                <button
                    type="button"
                    @click="setTab('{{ $tabKey }}')"
                    :class="activeTab === '{{ $tabKey }}' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'"
                    class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors text-left"
                >
                    <span class="shrink-0">
                        @if(($tabConfig['icon'] ?? '') === 'dashboard')
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        @elseif(($tabConfig['icon'] ?? '') === 'key')
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-square-icon lucide-key-square"><path d="M12.4 2.7a2.5 2.5 0 0 1 3.4 0l5.5 5.5a2.5 2.5 0 0 1 0 3.4l-3.7 3.7a2.5 2.5 0 0 1-3.4 0L8.7 9.8a2.5 2.5 0 0 1 0-3.4z"/><path d="m14 7 3 3"/><path d="m9.4 10.6-6.814 6.814A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814"/></svg>
                        @elseif(($tabConfig['icon'] ?? '') === 'toggle')
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list-chevrons-up-down-icon lucide-list-chevrons-up-down"><path d="M3 5h8"/><path d="M3 12h8"/><path d="M3 19h8"/><path d="m15 8 3-3 3 3"/><path d="m15 16 3 3 3-3"/></svg>
                        @elseif(($tabConfig['icon'] ?? '') === 'layers')
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layers-icon lucide-layers"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/></svg>
                        @elseif(($tabConfig['icon'] ?? '') === 'coins')
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-pound-sterling-icon lucide-circle-pound-sterling"><circle cx="12" cy="12" r="10"/><path d="M10 16V9.5a1 1 0 0 1 5 0"/><path d="M8 12h4"/><path d="M8 16h7"/></svg>
                        @elseif(($tabConfig['icon'] ?? '') === 'history')
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-history-icon lucide-history"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                        @elseif(($tabConfig['icon'] ?? '') === 'database')
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-database-icon lucide-database"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
                        @elseif(($tabConfig['icon'] ?? '') === 'lightbulb')
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lightbulb-icon lucide-lightbulb"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>
                        @else
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        @endif
                    </span>
                    {{ $tabConfig['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Content Area --}}
    <div class="flex-1 min-w-0 h-full flex flex-col">
        <div class="flex-1 min-h-0 overflow-y-auto">
            <div class="max-w-full px-6 py-6 space-y-6">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white" x-text="activeTab && tabs[activeTab] ? tabs[activeTab].label : ''"></h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="activeTab && tabs[activeTab] ? tabs[activeTab].description : ''"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" form="ai-api-keys-form" x-show="activeTab === 'api-keys'" x-cloak class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">{{ __('Save API Keys') }}</button>
                        <button type="submit" form="ai-provider-controls-form" x-show="activeTab === 'provider-controls'" x-cloak class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">{{ __('Save Changes') }}</button>
                        <button type="submit" form="ai-api-pricing-form" x-show="activeTab === 'api-pricing'" x-cloak class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">{{ __('Save Pricing') }}</button>
                        <button type="submit" form="ai-rag-settings-form" x-show="activeTab === 'rag'" x-cloak class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">{{ __('Save RAG Settings') }}</button>
                    </div>
                </div>

    <div x-show="activeTab === 'ai-dashboard'" x-cloak class="space-y-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.ai-tools.dashboard', ['tab' => 'ai-dashboard', 'range' => '7d']) }}"
                    class="rounded-md border px-3 py-2 text-sm {{ ($range ?? '7d') === '7d' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                >
                    {{ __('Last 7 Days') }}
                </a>
                <a
                    href="{{ route('admin.ai-tools.dashboard', ['tab' => 'ai-dashboard', 'range' => '30d']) }}"
                    class="rounded-md border px-3 py-2 text-sm {{ ($range ?? '7d') === '30d' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                >
                    {{ __('Last 30 Days') }}
                </a>
            </div>

            <form method="GET" action="{{ route('admin.ai-tools.dashboard') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="hidden" name="tab" value="ai-dashboard">
                <input type="hidden" name="range" value="custom">
                <input name="from" type="date" value="{{ optional($startDate ?? null)->toDateString() }}" class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                <input name="to" type="date" value="{{ optional($endDate ?? null)->toDateString() }}" class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">{{ __('Apply') }}</button>
        </form>

        {{-- Add Models Modal --}}
        <template x-teleport="body">
        <div
            x-data="addModelModal()"
            x-on:open-add-model-modal.window="openModal($event.detail.provider)"
            x-show="isOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 sm:p-6"
            style="display: none;"
        >
            <div class="relative flex max-h-[calc(100vh-1.5rem)] w-full max-w-3xl flex-col overflow-hidden rounded-md bg-white shadow-2xl dark:bg-neutral-900 sm:max-h-[calc(100vh-3rem)]" @click.stop>
                <div class="flex items-start justify-between border-b border-gray-200 px-7 py-5 dark:border-gray-700">
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white" x-text="'{{ __('Add Models from') }} ' + providerLabel"></h3>
                        <p class="mt-2 text-base text-gray-500 dark:text-gray-400">{{ __('Search and add models available on the external provider.') }}</p>
                    </div>
                    <button type="button" @click="closeModal()" class="mt-1 rounded-lg p-1 text-2xl leading-none text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300" aria-label="{{ __('Close') }}">
                        &times;
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto px-7 py-5">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m20 20-4-4"/></svg>
                        <input type="text" x-model="query" @input.debounce.300ms="search()" placeholder="{{ __('Search models...') }}" class="block w-full rounded-md border border-indigo-400 bg-white px-12 py-3 text-base text-gray-700 outline-none ring-0 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-0 dark:border-indigo-400 dark:bg-gray-800 dark:text-gray-200">
                        <div x-show="loading" class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="h-4 w-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </div>
                    </div>
                    <div class="mt-5 divide-y divide-gray-200 border-t border-gray-200 dark:divide-gray-700 dark:border-gray-700" x-show="results.length > 0">
                        <template x-for="model in results" :key="model.id">
                            <div class="flex items-center gap-4 py-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-gray-100 p-2 dark:bg-gray-800">
                                    <img x-bind:src="model.logo" alt="" class="max-h-full max-w-full object-contain">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="text-base font-medium text-gray-900 dark:text-gray-100" x-text="model.name"></div>
                                        <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400" x-text="model.context_length ? formatContext(model.context_length) : ''"></span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        <span>{{ __('Input:') }} <strong class="font-medium" x-text="formatPrice(model.input_cost_per_1m_cents) + '/M'"></strong></span>
                                        <span class="ml-5">{{ __('Output:') }} <strong class="font-medium" x-text="formatPrice(model.output_cost_per_1m_cents) + '/M'"></strong></span>
                                    </div>
                                    <div class="mt-1 truncate text-sm text-gray-400" x-text="model.description || ''"></div>
                                </div>
                                <button type="button" @click="addModel(model)" class="shrink-0 rounded-md bg-violet-500 px-4 py-2 text-sm font-medium text-white hover:bg-violet-600 disabled:cursor-not-allowed disabled:opacity-60" x-text="alreadyAdded(model) ? '{{ __('Added') }}' : '{{ __('+ Add') }}'" :disabled="alreadyAdded(model)"></button>
                            </div>
                        </template>
                    </div>
                    <div x-show="!loading && query.length >= 2 && results.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No models found.') }}
                    </div>
                    <div x-show="query.length < 2" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Type at least 2 characters to search.') }}
                    </div>
                </div>
                <div class="border-t border-gray-200 px-7 py-4 dark:border-gray-700">
                    <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('Added models appear in the list above and can be toggled on/off. Models are fetched from the provider\'s public API.') }}</p>
                </div>
            </div>
        </div>
        </template>
    </div>

        <div class="rounded-md border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ __('Admin AI usage aggregated from successful generations that used admin keys.') }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Range:') }} {{ $rangeLabel ?? __('Last 7 Days') }}</div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total tokens used') }}</div>
                <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format((int) ($totalTokensUsed ?? 0)) }}</div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Monthly limit:') }} {{ is_numeric($totalLimit) ? number_format((int) $totalLimit) : __('Unlimited') }}
                </div>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Tokens used today') }}</div>
                <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format((int) ($totalTokensUsedToday ?? 0)) }}</div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Daily limit:') }} {{ is_numeric($totalDailyLimit) ? number_format((int) $totalDailyLimit) : __('Unlimited') }}
                </div>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Estimated total cost') }}</div>
                <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">${{ number_format(((int) ($totalCostCents ?? 0)) / 100, 2) }}</div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('Uses configured input/output pricing per 1M tokens.') }}</div>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Providers tracked') }}</div>
                <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ count($providerStats ?? []) }}</div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ implode(', ', array_values(\App\Services\AI\AiProviderRegistry::providerLabels())) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Usage by provider') }}</div>
                <div class="mt-5 space-y-4">
                    @foreach(($providerStats ?? []) as $key => $stat)
                        @php
                            $used = (int) ($stat['tokens_used'] ?? 0);
                            $limit = (int) ($stat['token_limit'] ?? 0);
                            $percent = $stat['percent'] ?? null;
                            $estimatedCostCents = (int) ($stat['estimated_cost_cents'] ?? 0);
                            $daily = $providerDailyStats[$key] ?? null;
                        @endphp
                        <div class="rounded-md border border-gray-100 p-4 dark:border-gray-800">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $stat['label'] ?? $key }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Today:') }} {{ number_format((int) ($daily['tokens_used'] ?? 0)) }} / {{ ((int) ($daily['token_limit'] ?? 0)) > 0 ? number_format((int) ($daily['token_limit'] ?? 0)) : __('Unlimited') }}
                                    </div>
                                </div>
                                <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                    <div>{{ number_format($used) }} / {{ $limit > 0 ? number_format($limit) : __('Unlimited') }}</div>
                                    <div class="mt-1">${{ number_format($estimatedCostCents / 100, 2) }}</div>
                                </div>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full bg-primary-500" style="width: {{ is_numeric($percent) ? (float) $percent : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Breakdown by provider and model') }}</div>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">{{ __('Provider') }}</th>
                                <th class="px-4 py-3">{{ __('Model') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Tokens') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Input / Output') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Est. Cost') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @if(!empty($breakdownRows))
                                @foreach($breakdownRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $row['provider_label'] ?? ($row['provider'] ?? '') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['model'] ?? __('Default') }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ number_format((int) ($row['tokens'] ?? 0)) }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">${{ number_format(((int) ($row['input_cost_per_1m_cents'] ?? 0)) / 100, 2) }} / ${{ number_format(((int) ($row['output_cost_per_1m_cents'] ?? 0)) / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">${{ number_format(((int) ($row['estimated_cost_cents'] ?? 0)) / 100, 2) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No AI usage yet for the selected range.') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Tokens by provider') }}</div>
                <div class="mt-4" style="height: 320px;">
                    <canvas id="aiProviderChart"></canvas>
                </div>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Tokens by provider / model') }}</div>
                <div class="mt-4" style="height: 320px;">
                    <canvas id="aiProviderModelChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Estimated cost by provider') }}</div>
                <div class="mt-4" style="height: 320px;">
                    <canvas id="aiProviderCostChart"></canvas>
                </div>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Estimated cost by provider / model') }}</div>
                <div class="mt-4" style="height: 320px;">
                    <canvas id="aiProviderModelCostChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div x-show="activeTab === 'api-keys'" x-cloak>
        <form id="ai-api-keys-form" method="POST" action="{{ route('admin.ai-tools.api-keys.update') }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="rounded-md border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach(($apiKeyRows ?? []) as $row)
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="{{ $row['setting_key'] }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $row['label'] }}</label>
                                @if(!empty($row['help_url']))
                                    <a href="{{ $row['help_url'] }}" target="_blank" rel="noopener noreferrer" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">{{ __('Get API Key') }} &rarr;</a>
                                @endif
                            </div>
                            <div class="relative mt-2" data-secret-wrapper>
                                <input
                                    type="password"
                                    name="{{ $row['setting_key'] }}"
                                    id="{{ $row['setting_key'] }}"
                                    value="{{ $row['has_value'] ? '********' : '' }}"
                                    autocomplete="new-password"
                                    placeholder="{{ __('Leave blank to keep current key') }}"
                                    data-secret-url="{{ route('admin.settings.secret', ['key' => $row['setting_key']]) }}"
                                    data-secret-input
                                    class="block w-full rounded-md border border-gray-200 bg-white px-4 py-3 pr-12 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                >
                                <button type="button" data-toggle-secret class="absolute inset-y-0 right-0 flex items-center px-4 text-gray-500 dark:text-gray-400" aria-label="{{ __('Toggle secret visibility') }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    <div x-show="activeTab === 'provider-controls'" x-cloak x-data="{ activeProvider: @js(array_key_first($providerControls ?? [])) ?? null }">
        @php
            $providerLogoFiles = [
                'chatgpt' => 'openai.png',
                'claude' => 'anthropic.png',
                'gemini' => 'google.png',
                'deepseek' => 'deepseek.png',
                'kimi' => 'kimi.png',
                'glm' => 'zai.png',
                'grok' => 'xai.png',
                'qwen' => 'qwen.png',
                'minimax' => 'minimax.png',
                'xiaomi' => 'xiaomi.png',
                'openrouter' => 'openrouter.png',
            ];
            $providersWithExternalSearch = ['openrouter'];
        @endphp
        <form id="ai-provider-controls-form" method="POST" action="{{ route('admin.ai-tools.provider-controls.update') }}">
            @csrf
            @method('PUT')
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
                @if(!empty($providerControls))
                <div class="w-full shrink-0 self-start space-y-1 lg:sticky lg:top-6 lg:w-64">
                    @foreach(($providerControls ?? []) as $providerControl)
                        @php
                            $logoFile = $providerLogoFiles[$providerControl['provider']] ?? null;
                            $hasLogo = $logoFile !== null && file_exists(public_path('images/model-logos/' . $logoFile));
                            $label = (string) ($providerControl['label'] ?? $providerControl['provider']);
                        @endphp
                        <button
                            type="button"
                            @click="activeProvider = @js($providerControl['provider'])"
                            :class="activeProvider === @js($providerControl['provider']) ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300' : 'text-gray-600 hover:bg-neutral-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-neutral-800/50 dark:hover:text-gray-200'"
                            class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-150 text-left"
                        >
                            @if($hasLogo)
                                <img src="{{ asset('images/model-logos/' . $logoFile) }}" alt="" class="h-5 w-5 shrink-0 rounded object-contain">
                            @else
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-neutral-100 text-[11px] font-semibold text-gray-500 dark:bg-neutral-800 dark:text-gray-400">{{ mb_strtoupper(mb_substr($label, 0, 1)) }}</span>
                            @endif
                            <span class="flex-1 truncate">{{ $label }}</span>
                            <span class="text-xs tabular-nums text-gray-400">{{ count($providerControl['models'] ?? []) }}</span>
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ !empty($providerControl['enabled']) ? 'bg-emerald-500' : 'bg-neutral-300 dark:bg-neutral-600' }}"></span>
                        </button>
                    @endforeach
                </div>
                @endif
                <div class="min-w-0 flex-1">
                    @forelse(($providerControls ?? []) as $providerControl)
                        <div x-show="activeProvider === @js($providerControl['provider'])" x-cloak class="space-y-5">
                            <div class="flex items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $providerControl['label'] }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Enable or disable the global provider switch and individual models.') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if(in_array($providerControl['provider'], $providersWithExternalSearch ?? []))
                                        <button type="button" @click="$dispatch('open-add-model-modal', { provider: '{{ $providerControl['provider'] }}' })" class="rounded-md bg-primary-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-600">
                                            {{ __('+ Add Models') }}
                                        </button>
                                    @endif
                                    <label class="inline-flex cursor-pointer items-center gap-3">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ !empty($providerControl['enabled']) ? __('Enabled') : __('Disabled') }}</span>
                                    <input type="checkbox" name="provider_enabled[{{ $providerControl['provider'] }}]" value="1" {{ !empty($providerControl['enabled']) ? 'checked' : '' }} class="sr-only peer">
                                    <div class="relative h-6 w-11 rounded-full bg-neutral-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-neutral-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-violet-600 peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-neutral-700"></div>
                                </label>
                            </div>
                            </div>
                            <div class="grid grid-cols-1 gap-px overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-200 md:grid-cols-2 xl:grid-cols-3 dark:border-neutral-800 dark:bg-neutral-800">
                                @foreach(($providerControl['models'] ?? []) as $model)
                                    @php
                                        $inputUsd = '$' . rtrim(rtrim(number_format(((int) ($model['input_cost_per_1m_cents'] ?? 0)) / 100, 2), '0'), '.');
                                        $outputUsd = '$' . rtrim(rtrim(number_format(((int) ($model['output_cost_per_1m_cents'] ?? 0)) / 100, 2), '0'), '.');
                                    @endphp
                                    <label class="flex cursor-pointer items-center justify-between gap-4 bg-white p-5 transition-colors duration-150 hover:bg-neutral-50 dark:bg-neutral-900 dark:hover:bg-neutral-800/60">
                                        <span class="min-w-0">
                                            <span class="block text-[15px] font-semibold leading-6 text-gray-900 dark:text-gray-100">{{ $model['name'] }}</span>
                                            <span class="mt-1.5 block text-xs leading-5 text-gray-500 dark:text-gray-400">
                                                <span class="mr-4 inline-flex items-center gap-1.5"><span class="text-gray-400">{{ __('Input') }}</span><span class="font-medium text-gray-700 dark:text-gray-300">{{ $inputUsd }}/M</span></span>
                                                <span class="inline-flex items-center gap-1.5"><span class="text-gray-400">{{ __('Output') }}</span><span class="font-medium text-gray-700 dark:text-gray-300">{{ $outputUsd }}/M</span></span>
                                            </span>
                                        </span>
                                        <span class="relative inline-flex shrink-0">
                                            <input type="checkbox" name="model_enabled[{{ $providerControl['provider'] }}][{{ $model['slug'] }}]" value="1" {{ !empty($model['enabled']) ? 'checked' : '' }} class="sr-only peer">
                                            <div class="relative h-6 w-11 rounded-full bg-neutral-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-neutral-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-violet-600 peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-neutral-700"></div>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('No providers configured.') }}</div>
                    @endforelse
                </div>
            </div>
        </form>
    </div>

    <div x-show="activeTab === 'builder-models'" x-cloak x-data="{ modelMeta: @js($builderModelMeta) }">
        <form id="ai-builder-models-form" method="POST" action="{{ route('admin.ai-tools.builder-defaults.update') }}" class="space-y-6">
            @csrf
            @method('PUT')
            @php
                $enabledKeys = $enabledProviderKeys ?? [];
                $enabledConfigs = array_filter($providerConfigs ?? [], function ($key) use ($enabledKeys) {
                    return in_array($key, $enabledKeys);
                }, ARRAY_FILTER_USE_KEY);
                $singleProvider = count($enabledConfigs) === 1;
            @endphp
            <div>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('These are the AI stages that accept a model selection. Validation and page assembly run locally and do not use AI credits.') }}
                </p>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach(($builderStages ?? []) as $stageKey => $stageLabel)
                        @php($stageCost = $builderStageCosts[$stageKey] ?? [])
                        <div class="rounded-md border border-gray-200 p-4 dark:border-gray-800" x-data="{ selectedModel: '{{ $builderDefaults[$stageKey] ?? '' }}' }">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $stageLabel }}</label>
                            <select name="{{ $stageKey }}" x-model="selectedModel" class="mt-3 block w-full rounded-md border border-gray-200 bg-white p-2 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                @foreach($enabledConfigs as $providerKey => $providerConfig)
                                    @php($allModelValues = \App\Services\AI\AiProviderRegistry::allModels($providerKey))
                                    @if($singleProvider)
                                        @foreach($allModelValues as $modelValue)
                                            <option value="{{ $modelValue }}" {{ ($builderDefaults[$stageKey] ?? '') === $modelValue ? 'selected' : '' }}>{{ $modelValue }}</option>
                                        @endforeach
                                    @else
                                        <optgroup label="{{ $providerConfig['label'] }}">
                                            @foreach($allModelValues as $modelValue)
                                                <option value="{{ $modelValue }}" {{ ($builderDefaults[$stageKey] ?? '') === $modelValue ? 'selected' : '' }}>{{ $modelValue }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Provider:') }} <span x-text="selectedModel && modelMeta[selectedModel] ? modelMeta[selectedModel].provider : '-'"></span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                $<span x-text="selectedModel && modelMeta[selectedModel] ? (modelMeta[selectedModel].input_cents_per_1m / 100).toFixed(2) : '-'"></span> {{ __('in') }} / $<span x-text="selectedModel && modelMeta[selectedModel] ? (modelMeta[selectedModel].output_cents_per_1m / 100).toFixed(2) : '-'"></span> {{ __('out') }} {{ __('per 1M') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    <div x-show="activeTab === 'suggestions'" x-cloak x-data="{
        rows: @js(collect($initialSuggestions)->values()->map(fn ($s) => [
            'title' => (string) ($s['title'] ?? ''),
            'description' => (string) ($s['description'] ?? ''),
            'icon' => isset($s['icon']) && is_string($s['icon']) ? $s['icon'] : '',
        ])->all()),
        addRow() {
            this.rows.push({ title: '', description: '', icon: '' });
        },
        removeRow(index) {
            this.rows.splice(index, 1);
        },
    }">
        <form id="ai-suggestions-form" method="POST" action="{{ route('admin.ai-tools.suggestions.update') }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="rounded-md border border-gray-200 p-4 dark:border-gray-800">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-[80px_1fr_1fr_auto]">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Icon') }}</label>
                                <input type="text" x-model="row.icon" :name="`suggestions[${index}][icon]`" maxlength="10" placeholder="🚀" class="mt-1 block w-full rounded-md border border-gray-200 bg-white p-2 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Title') }}</label>
                                <input type="text" x-model="row.title" :name="`suggestions[${index}][title]`" maxlength="255" placeholder="{{ __('e.g. AI content generator') }}" class="mt-1 block w-full rounded-md border border-gray-200 bg-white p-2 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Onclick Input Description') }}</label>
                                <textarea x-model="row.description" :name="`suggestions[${index}][description]`" maxlength="4000" rows="2" placeholder="{{ __('Prompt inserted into the AI input when this suggestion is clicked.') }}" class="mt-1 block w-full rounded-md border border-gray-200 bg-white p-2 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"></textarea>
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="removeRow(index)" class="rounded-md border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20">{{ __('Remove') }}</button>
                            </div>
                        </div>
                    </div>
                </template>
                <button type="button" @click="addRow()" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ __('+ Add Suggestion') }}</button>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('These suggestions are shown in the AI builder before a build starts. After a page is generated, suggestions are generated by AI based on the page content.') }}</p>
        </form>
    </div>

    <div x-show="activeTab === 'page-generation-mode'" x-cloak>
        <form id="ai-page-generation-mode-form" method="POST" action="{{ route('admin.ai-tools.page-generation-mode.update') }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <label class="cursor-pointer rounded-md border border-gray-200 p-4 dark:border-gray-800 has-[:checked]:border-primary-500 has-[:checked]:ring-1 has-[:checked]:ring-primary-500">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="ai_builder_section_source" value="templates" {{ $pageGenerationMode === 'templates' ? 'checked' : '' }} class="mt-1 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500">
                        <div>
                            <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Templates Priority') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Use premade templates first; fallback to AI only when no template is available. More consistent and cheaper.') }}</span>
                        </div>
                    </div>
                </label>
                <label class="cursor-pointer rounded-md border border-gray-200 p-4 dark:border-gray-800 has-[:checked]:border-primary-500 has-[:checked]:ring-1 has-[:checked]:ring-primary-500">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="ai_builder_section_source" value="ai_free" {{ $pageGenerationMode === 'ai_free' ? 'checked' : '' }} class="mt-1 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500">
                        <div>
                            <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('AI Free Generation') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('AI generates each section freely without using templates. More flexible but may be less consistent.') }}</span>
                        </div>
                    </div>
                </label>
                <label class="cursor-pointer rounded-md border border-gray-200 p-4 dark:border-gray-800 has-[:checked]:border-primary-500 has-[:checked]:ring-1 has-[:checked]:ring-primary-500">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="ai_builder_section_source" value="templates_test" {{ $pageGenerationMode === 'templates_test' ? 'checked' : '' }} class="mt-1 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500">
                        <div>
                            <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Templates Test') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Load all templates with default data on one page. No AI calls. Useful for testing template rendering and preview.') }}</span>
                        </div>
                    </div>
                </label>
            </div>
        </form>
    </div>

    <div x-show="activeTab === 'api-pricing'" x-cloak>
        <form id="ai-api-pricing-form" method="POST" action="{{ route('admin.ai-tools.pricing.update') }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="">
                <div class="space-y-6">
                    @foreach(($pricingProviders ?? []) as $providerPricing)
                        <div class="">
                            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $providerPricing['label'] }}</h4>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Default fallback pricing for this provider.') }}</p>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Default input cost / 1M') }}</label>
                                        <input type="number" min="0" name="provider_input_costs[{{ $providerPricing['provider'] }}]" value="{{ old('provider_input_costs.' . $providerPricing['provider'], (int) ($providerPricing['default_input_cost_per_1m_cents'] ?? 0)) }}" class="mt-2 block w-full rounded-md border border-gray-200 bg-white p-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Default output cost / 1M') }}</label>
                                        <input type="number" min="0" name="provider_output_costs[{{ $providerPricing['provider'] }}]" value="{{ old('provider_output_costs.' . $providerPricing['provider'], (int) ($providerPricing['default_output_cost_per_1m_cents'] ?? 0)) }}" class="mt-2 block w-full rounded-md border border-gray-200 bg-white p-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                                    <thead>
                                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <th class="px-4 py-3">{{ __('Model') }}</th>
                                            <th class="px-4 py-3">{{ __('Status') }}</th>
                                            <th class="px-4 py-3 text-right">{{ __('Input / 1M') }}</th>
                                            <th class="px-4 py-3 text-right">{{ __('Output / 1M') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach(($providerPricing['models'] ?? []) as $model)
                                            <tr>
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $model['name'] }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ !empty($model['enabled']) ? __('Active') : __('Inactive') }}</td>
                                                <td class="px-4 py-3 text-right">
                                                    <input type="number" min="0" name="model_input_costs[{{ $providerPricing['provider'] }}][{{ $model['slug'] }}]" value="{{ old('model_input_costs.' . $providerPricing['provider'] . '.' . $model['slug'], (int) ($model['input_cost_per_1m_cents'] ?? 0)) }}" class="w-32 rounded-md border border-gray-200 bg-white px-3 py-2 text-right text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <input type="number" min="0" name="model_output_costs[{{ $providerPricing['provider'] }}][{{ $model['slug'] }}]" value="{{ old('model_output_costs.' . $providerPricing['provider'] . '.' . $model['slug'], (int) ($model['output_cost_per_1m_cents'] ?? 0)) }}" class="w-32 rounded-md border border-gray-200 bg-white px-3 py-2 text-right text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    <div x-show="activeTab === 'generations'" x-cloak class="space-y-6">
        @if($generationDetail)
            <div>
                <a href="{{ route('admin.ai-tools.dashboard', array_merge(request()->except('generation_id'), ['tab' => 'generations'])) }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">← {{ __('Back to generations list') }}</a>
            </div>
            <div class="">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Generation Details') }}</h3>
                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">#{{ $generationDetail->id }} · {{ $generationDetail->created_at->format('Y-m-d H:i') }} · {{ $generationDetail->tool ?? '-' }} · {{ $generationDetail->provider ?? '-' }} / {{ $generationDetail->model ?? '-' }}</div>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Total cost:') }} <span class="font-semibold text-gray-900 dark:text-gray-100">${{ number_format($generationDetail->steps->sum('cost_cents') / 100, 3) }}</span>
                    </div>
                </div>
                @if(!empty($generationDetail->prompt) && $generationDetail->prompt !== '(no prompt)')
                    <div class="mt-5 rounded-md bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $generationDetail->prompt }}</div>
                @endif
                <div class="mt-5 overflow-x-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">{{ __('Step') }}</th>
                                <th class="px-4 py-3">{{ __('Provider') }}</th>
                                <th class="px-4 py-3">{{ __('Model') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Tokens') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Cost') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @if($generationDetail->steps->count() > 0)
                                @foreach($generationDetail->steps as $step)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $step->step_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $step->provider ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $step->model ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ number_format((int) $step->tokens_used) }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">${{ number_format($step->cost_cents / 100, 3) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ ucfirst($step->status) }}</td>
                                    </tr>
                                    @if(($step->status ?? '') === 'failed' && !empty($step->error_message))
                                        <tr>
                                            <td colspan="6" class="px-4 py-2 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/10">
                                                {{ $step->error_message }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No steps recorded for this generation.') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if($generationDetail->iterations->count() > 0)
                    <div class="mt-8">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Iterations') }} <span class="ml-1 text-sm font-normal text-gray-500 dark:text-gray-400">({{ $generationDetail->iterations->count() }})</span></h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Follow-up AI builder rounds for this build.') }}</p>
                        <div class="mt-3 overflow-x-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <th class="px-4 py-3">{{ __('Iteration') }}</th>
                                        <th class="px-4 py-3">{{ __('Date') }}</th>
                                        <th class="px-4 py-3">{{ __('Prompt') }}</th>
                                        <th class="px-4 py-3">{{ __('Model') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Tokens') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Cost') }}</th>
                                        <th class="px-4 py-3">{{ __('Status') }}</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($generationDetail->iterations as $iteration)
                                        @php($iterationCost = $iteration->steps->sum('cost_cents'))
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">#{{ $loop->iteration }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $iteration->created_at->format('Y-m-d H:i') }}</td>
                                            <td class="max-w-md px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                                <span class="line-clamp-2">{{ $iteration->prompt ?? '-' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $iteration->model ?? $iteration->provider ?? '-' }}</td>
                                            <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ number_format((int) $iteration->steps->sum('tokens_used')) }}</td>
                                            <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">${{ number_format($iterationCost / 100, 3) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $iteration->success ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' }}">
                                                    {{ $iteration->success ? __('Completed') : __('Failed') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <a href="{{ route('admin.ai-tools.dashboard', array_merge(request()->all(), ['generation_id' => $iteration->id, 'tab' => 'generations'])) }}" class="font-medium text-primary-600 hover:text-primary-500">{{ __('View') }}</a>
                                            </td>
                                        </tr>
                                        @if(!$iteration->success && !empty($iteration->error_message))
                                            <tr>
                                                <td colspan="8" class="px-4 py-2 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/10">{{ $iteration->error_message }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="mb-5 flex items-center justify-end gap-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Visible cost total:') }} <span class="font-semibold text-gray-900 dark:text-gray-100">${{ number_format($generationTotalCost / 100, 3) }}</span></div>
            </div>
            <div class="overflow-x-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">{{ __('ID') }}</th>
                                <th class="px-4 py-3">{{ __('Tool') }}</th>
                                <th class="px-4 py-3">{{ __('Type') }}</th>
                                <th class="px-4 py-3">{{ __('Provider') }}</th>
                                <th class="px-4 py-3">{{ __('Model') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Tokens') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Cost') }}</th>
                                <th class="px-4 py-3">{{ __('Date') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @if($generations->count() > 0)
                                @foreach($generations as $gen)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">#{{ $gen->id }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $gen->tool ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $gen->type ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $gen->provider ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $gen->model ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ number_format((int) $gen->steps->sum('tokens_used')) }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">${{ number_format($gen->steps->sum('cost_cents') / 100, 3) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $gen->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3 text-sm"><a href="{{ route('admin.ai-tools.dashboard', array_merge(request()->all(), ['generation_id' => $gen->id, 'tab' => 'generations'])) }}" class="font-medium text-primary-600 hover:text-primary-500">{{ __('View') }}</a></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No generations yet.') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if($hasGenerationPages)
                    <div class="mt-5">{{ $generations->links() }}</div>
                @endif
            </div>
        @endif
    </div>

    <div x-show="activeTab === 'rag'" x-cloak class="space-y-6">
        <form id="ai-rag-settings-form" method="POST" action="{{ route('admin.ai-tools.rag-settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('RAG Configuration') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('RAG uses MySQL full-text search to find relevant knowledge base content and passes it as context to AI providers. These settings control chunking and retrieval behavior.') }}</p>

                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="rag_chunk_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Chunk Size (words)') }}</label>
                        <input type="number" name="rag_chunk_size" id="rag_chunk_size" value="{{ $ragSettings['rag_chunk_size'] ?? 500 }}" min="100" max="10000" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>

                    <div>
                        <label for="rag_chunk_overlap" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Chunk Overlap (words)') }}</label>
                        <input type="number" name="rag_chunk_overlap" id="rag_chunk_overlap" value="{{ $ragSettings['rag_chunk_overlap'] ?? 50 }}" min="0" max="2000" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>

                    <div>
                        <label for="rag_top_k" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Top K Results') }}</label>
                        <input type="number" name="rag_top_k" id="rag_top_k" value="{{ $ragSettings['rag_top_k'] ?? 5 }}" min="1" max="50" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>

                    <div>
                        <label for="rag_max_context_tokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Max Context Tokens') }}</label>
                        <input type="number" name="rag_max_context_tokens" id="rag_max_context_tokens" value="{{ $ragSettings['rag_max_context_tokens'] ?? 3000 }}" min="500" max="32000" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>

                    <div>
                        <label for="rag_default_provider" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Default Provider') }}</label>
                        <select name="rag_default_provider" id="rag_default_provider" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                            @foreach($ragProviderOptions as $providerKey => $providerLabel)
                                <option value="{{ $providerKey }}" {{ ($ragSettings['rag_default_provider'] ?? 'chatgpt') === $providerKey ? 'selected' : '' }}>{{ $providerLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const providerStats = @json($providerStats ?? []);
            const breakdownRows = @json($breakdownRows ?? []);
            const isDark = () => document.documentElement.classList.contains('dark');
            const theme = () => isDark()
                ? { gridColor: 'rgba(255,255,255,0.10)', tickColor: 'rgba(255,255,255,0.70)' }
                : { gridColor: 'rgba(17,24,39,0.10)', tickColor: 'rgba(17,24,39,0.70)' };
            const providerLabels = [];
            const providerTokens = [];
            const providerCost = [];

            Object.keys(providerStats || {}).forEach(function (key) {
                const stat = providerStats[key] || {};
                providerLabels.push(stat.label || key);
                providerTokens.push(Number(stat.tokens_used || 0));
                providerCost.push(Number(stat.estimated_cost_cents || 0) / 100);
            });

            const pmLabels = [];
            const pmTokens = [];
            const pmCost = [];
            (Array.isArray(breakdownRows) ? breakdownRows : []).forEach(function (row) {
                pmLabels.push(String(row.provider_label || row.provider || '') + ' / ' + String(row.model || 'Default'));
                pmTokens.push(Number(row.tokens || 0));
                pmCost.push(Number(row.estimated_cost_cents || 0) / 100);
            });

            function makeBar(id, labels, values, label, colors, currency) {
                const el = document.getElementById(id);
                if (!el || typeof Chart === 'undefined') {
                    return null;
                }
                const ctx = el.getContext('2d');
                if (!ctx) {
                    return null;
                }
                const t = theme();
                return new Chart(ctx, {
                    type: 'bar',
                    data: { labels: labels, datasets: [{ label: label, data: values, backgroundColor: colors.bg, borderColor: colors.border, borderWidth: 1 }] },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: currency ? {
                                callbacks: {
                                    label: function (ctx) {
                                        return ' $' + Number(ctx.raw || 0).toFixed(2);
                                    }
                                }
                            } : undefined,
                        },
                        scales: {
                            x: { grid: { color: t.gridColor }, ticks: { color: t.tickColor, maxRotation: 60, minRotation: 0 } },
                            y: {
                                grid: { color: t.gridColor },
                                ticks: {
                                    color: t.tickColor,
                                    callback: currency ? function (v) { return '$' + Number(v || 0); } : undefined,
                                },
                                beginAtZero: true,
                            }
                        }
                    }
                });
            }

            window.__aiProviderChart = makeBar('aiProviderChart', providerLabels, providerTokens, 'Tokens', { bg: 'rgba(30,95,234,0.25)', border: '#1E5FEA' }, false);
            window.__aiProviderModelChart = makeBar('aiProviderModelChart', pmLabels, pmTokens, 'Tokens', { bg: 'rgba(16,148,137,0.25)', border: '#109489' }, false);
            window.__aiProviderCostChart = makeBar('aiProviderCostChart', providerLabels, providerCost, 'Cost ($)', { bg: 'rgba(245,158,11,0.25)', border: '#F59E0B' }, true);
            window.__aiProviderModelCostChart = makeBar('aiProviderModelCostChart', pmLabels, pmCost, 'Cost ($)', { bg: 'rgba(168,85,247,0.22)', border: '#A855F7' }, true);

            const applyTheme = () => {
                const t = theme();
                ['__aiProviderChart', '__aiProviderModelChart', '__aiProviderCostChart', '__aiProviderModelCostChart'].forEach(function (key) {
                    const chart = window[key];
                    if (!chart) {
                        return;
                    }
                    chart.options.scales.x.grid.color = t.gridColor;
                    chart.options.scales.y.grid.color = t.gridColor;
                    chart.options.scales.x.ticks.color = t.tickColor;
                    chart.options.scales.y.ticks.color = t.tickColor;
                    chart.update('none');
                });
            };

            const observer = new MutationObserver(applyTheme);
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>
    <script>
        function addModelModal() {
            return {
                isOpen: false,
                provider: '',
                providerLabel: 'OpenRouter',
                query: '',
                results: [],
                loading: false,
                addedModels: [],
                openModal(provider) {
                    this.provider = provider;
                    this.providerLabel = provider === 'openrouter' ? 'OpenRouter' : provider;
                    this.query = '';
                    this.results = [];
                    this.isOpen = true;
                },
                closeModal() {
                    this.isOpen = false;
                    this.query = '';
                    this.results = [];
                },
                formatContext(value) {
                    const context = Number(value);
                    if (!Number.isFinite(context) || context <= 0) return '';
                    return (context >= 1000000 ? (context / 1000000).toFixed(0) + 'M' : (context / 1000).toFixed(0) + 'K') + ' ctx';
                },
                formatPrice(cents) {
                    const price = Number(cents || 0) / 100;
                    return '$' + price.toFixed(2);
                },
                async search() {
                    if (this.query.length < 2) { this.results = []; return; }
                    this.loading = true;
                    try {
                         const resp = await fetch(`{{ route('admin.ai-tools.custom-models.search') }}?provider=${encodeURIComponent(this.provider)}&q=${encodeURIComponent(this.query)}`);
                        const data = await resp.json();
                        this.results = data.models || [];
                    } catch (e) {
                        this.results = [];
                    }
                    this.loading = false;
                },
                 async addModel(model) {
                     if (this.alreadyAdded(model)) return;
                     try {
                         const resp = await fetch('{{ route('admin.ai-tools.custom-models.store') }}', {
                             method: 'POST',
                             headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' },
                             body: JSON.stringify({
                                 provider: this.provider,
                                 model_id: model.id,
                                 name: model.name,
                                 description: model.description || null,
                                 context_length: model.context_length || null,
                                 input_cost_per_1m_cents: Number(model.input_cost_per_1m_cents || 0),
                                 output_cost_per_1m_cents: Number(model.output_cost_per_1m_cents || 0),
                             })
                         });
                         if (!resp.ok) throw new Error('Unable to add model');
                         this.addedModels.push(model.id);
                     } catch (e) {}
                 },
                 alreadyAdded(model) {
                     return model.already_added || this.addedModels.includes(model.id);
                 }
            };
        }
    </script>
@endpush

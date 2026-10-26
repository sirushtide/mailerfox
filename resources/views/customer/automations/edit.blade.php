@extends('layouts.customer')

@section('title', 'Edit Automation')
@section('page-title', '')
@section('force-sidebar-collapsed', '1')
@section('hide-top-header', '1')
@section('content-padding-classes', 'p-0')

@php
$automationSettings = (array) ($automation->settings ?? []);
$reEntry = (string) ($automationSettings['re_entry'] ?? 'always');
$freqCap = (int) ($automationSettings['frequency_cap_days'] ?? 0);
@endphp

@section('page-header')
<div class="px-4 sm:px-6 py-2 bg-white dark:bg-admin-sidebar dark:border-admin-border fixed z-20 w-[calc(100%-30px)] border-b" x-data="{ showSettings: false }">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 min-w-0 text-sm">
            <a href="{{ route('customer.automations.index') }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Automations
            </a>
            <span class="text-gray-300 dark:text-gray-600">/</span>
            <input
                type="text"
                name="name"
                form="automation-form"
                value="{{ $automation->name }}"
                required
                size="{{ max(10, mb_strlen($automation->name) + 1) }}"
                class="min-w-0 max-w-md font-semibold text-gray-900 dark:text-white bg-transparent border-0 focus:ring-0 focus:outline-none p-0 text-sm w-auto"
            >
            <select
                name="status"
                form="automation-form"
                class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs font-semibold uppercase text-gray-700 dark:text-gray-200 focus:border-primary-500 focus:ring-primary-500 py-1 pr-7"
            >
                <option value="draft" @selected($automation->status === 'draft')>Draft</option>
                <option value="active" @selected($automation->status === 'active')>Active</option>
                <option value="inactive" @selected($automation->status === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0" x-data="{ runOpen: false, runEmail: '', runError: '', runLoading: false, runResults: [], showResults: false }">
            <button type="button" @click="showSettings = !showSettings" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <span x-show="!showSettings">Settings</span>
                <span x-show="showSettings" x-cloak>Hide Settings</span>
            </button>
            <a href="{{ route('customer.automations.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Discard</a>
            <button type="submit" form="automation-form" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">Save</button>
            <div class="relative">
                <button type="button" @click="runOpen = !runOpen; runError = ''; showResults = false;" class="rounded-lg bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Run
                </button>
                <div x-show="runOpen" x-cloak @click.outside="runOpen = false" x-transition class="absolute right-0 mt-2 w-80 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg p-3 z-50 max-h-[80vh] overflow-y-auto">
                    <div x-show="!showResults">
                        <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">Run workflow</div>
                        <label class="block text-xs text-gray-600 dark:text-gray-300 mb-1">Subscriber email</label>
                        <input type="email" x-model="runEmail" placeholder="subscriber@example.com" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:text-gray-100 text-sm mb-2">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mb-2">For list-based triggers the subscriber must already exist. For campaign triggers any email is accepted.</div>
                        <button type="button"
                            @click="runLoading = true; runError = ''; fetch('{{ route('customer.automations.run-test', $automation) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ email: runEmail }) }).then(r => r.json().then(d => ({ ok: r.ok, data: d }))).then(({ ok, data }) => { runLoading = false; if (ok && data.success) { runResults = data.results; showResults = true; } else { runError = data.message || 'Something went wrong.'; } }).catch(e => { runLoading = false; runError = 'Request failed.'; })"
                            :disabled="runLoading || !runEmail"
                            class="w-full rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span x-show="!runLoading">Start run</span>
                            <span x-show="runLoading" x-cloak>Running...</span>
                        </button>
                        <div x-show="runError" x-cloak class="mt-2 text-xs text-red-600" x-text="runError"></div>
                    </div>

                    <div x-show="showResults" x-cloak>
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Test results</div>
                            <button type="button" @click="showResults = false" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Back</button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(step, idx) in runResults" :key="idx">
                                <div class="flex items-start gap-2 text-sm">
                                    <div class="mt-0.5 shrink-0">
                                        <span x-show="step.status === 'completed'" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold">&#10003;</span>
                                        <span x-show="step.status === 'skipped'" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-600 text-xs font-bold">&#10148;</span>
                                        <span x-show="step.status === 'failed'" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-100 text-red-600 text-xs font-bold">&#10005;</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-gray-100" x-text="step.label || step.type"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 break-words" x-text="step.output"></div>
                                        <div x-show="step.error" class="text-xs text-red-600 mt-0.5" x-text="step.error"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div x-show="showSettings" x-cloak x-transition class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <div class="flex items-center gap-2">
                <label class="text-gray-600 dark:text-gray-300 text-xs">Re-entry</label>
                <select name="automation_settings[re_entry]" form="automation-form" class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 focus:border-primary-500 focus:ring-primary-500 py-1 pr-6">
                    <option value="always" @selected($reEntry === 'always')>Always allow</option>
                    <option value="never" @selected($reEntry === 'never')>Never (once only)</option>
                    <option value="after_completed" @selected($reEntry === 'after_completed')>After completed</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-gray-600 dark:text-gray-300 text-xs">Frequency cap (days)</label>
                <input type="number" min="0" name="automation_settings[frequency_cap_days]" form="automation-form" value="{{ $freqCap }}" class="w-16 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 focus:border-primary-500 focus:ring-primary-500 py-1 px-2" placeholder="0">
                <span class="text-xs text-gray-400">0 = none</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<form id="automation-form" method="POST" action="{{ route('customer.automations.update', $automation) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="graph_json" value='@json($automation->graph ?? [])'>
</form>
<div class="fixed left-0 lg:left-[37px] right-0 bottom-0" style="top:50px;" x-data="{ activeTab: 'flow' }">
    <div class="absolute inset-0 flex flex-col">
        <div class="flex items-center gap-1 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 z-10">
            <button type="button" @click="activeTab = 'flow'" :class="activeTab === 'flow' ? 'border-b-2 border-primary-500 text-primary-700 dark:text-primary-300 font-semibold' : 'text-gray-500 dark:text-gray-400'" class="px-3 py-2 text-sm">Flow</button>
            <button type="button" @click="activeTab = 'analytics'; if (!$store.automationAnalytics) { $store.automationAnalytics = {}; } if (!$store.automationAnalytics.loaded) { fetch('{{ route('customer.automations.analytics', $automation) }}').then(r => r.json()).then(d => { $store.automationAnalytics.data = d; $store.automationAnalytics.loaded = true; }); }" :class="activeTab === 'analytics' ? 'border-b-2 border-primary-500 text-primary-700 dark:text-primary-300 font-semibold' : 'text-gray-500 dark:text-gray-400'" class="px-3 py-2 text-sm">Analytics</button>
        </div>

        <div class="relative flex-1 overflow-hidden" x-show="activeTab === 'flow'">
            <div
                id="automation-builder-react"
                class="absolute inset-0"
                data-full-height="true"
                data-floating-sidebar="true"
                data-automation-id="{{ $automation->id }}"
                data-initial-graph='@json($automation->graph ?? [])'
                data-email-lists='@json($emailLists->map(fn ($l) => ["id" => $l->id, "name" => $l->name])->values())'
                data-campaigns='@json($campaigns->map(fn ($c) => ["id" => $c->id, "name" => $c->name])->values())'
                data-templates='@json($templates->map(fn ($t) => ["id" => $t->id, "name" => $t->name])->values())'
                data-segments='@json($segments->map(fn ($s) => ["id" => $s->id, "name" => $s->name])->values())'
                data-delivery-servers='@json($deliveryServers->map(fn ($s) => ["id" => $s->id, "name" => $s->name])->values())'
                data-hide-attribution="true"
            ></div>
        </div>

        <div x-show="activeTab === 'analytics'" x-cloak class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900">
            <template x-if="$store.automationAnalytics?.loaded">
                <div class="max-w-5xl mx-auto space-y-6">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="$store.automationAnalytics.data.overview.total"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total runs</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center">
                            <div class="text-2xl font-bold text-primary-600" x-text="$store.automationAnalytics.data.overview.active"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Active</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center">
                            <div class="text-2xl font-bold text-green-600" x-text="$store.automationAnalytics.data.overview.completed"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Completed</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center">
                            <div class="text-2xl font-bold text-red-600" x-text="$store.automationAnalytics.data.overview.stopped"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Stopped</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600"><span x-text="$store.automationAnalytics.data.overview.conversion_rate"></span>%</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Conversion</div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Flow Funnel</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Node</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Type</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Unique runs</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Total entries</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <template x-for="node in $store.automationAnalytics.data.funnel" :key="node.node_id">
                                        <tr>
                                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100" x-text="node.label || node.node_id"></td>
                                            <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400 capitalize" x-text="node.type"></td>
                                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100 font-medium" x-text="node.unique_runs"></td>
                                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100" x-text="node.total_entries"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Daily Runs (Last 30 Days)</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-end gap-1 h-32">
                                <template x-for="day in $store.automationAnalytics.data.daily_runs" :key="day.date">
                                    <div class="flex-1 flex flex-col items-center gap-1">
                                        <div class="w-full bg-primary-200 dark:bg-primary-500/30 rounded-t" :style="'height: ' + Math.max(4, (day.count / Math.max(1, $store.automationAnalytics.data.daily_runs.reduce((a,b) => Math.max(a, b.count), 0))) * 100) + '%'"></div>
                                        <div class="text-[10px] text-gray-400" x-text="new Date(day.date).getDate()"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="!$store.automationAnalytics?.loaded">
                <div class="max-w-5xl mx-auto space-y-4">
                    <div class="animate-pulse space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="h-20 bg-gray-200 dark:bg-gray-800 rounded-lg"></div>
                            <div class="h-20 bg-gray-200 dark:bg-gray-800 rounded-lg"></div>
                            <div class="h-20 bg-gray-200 dark:bg-gray-800 rounded-lg"></div>
                            <div class="h-20 bg-gray-200 dark:bg-gray-800 rounded-lg"></div>
                            <div class="h-20 bg-gray-200 dark:bg-gray-800 rounded-lg"></div>
                        </div>
                        <div class="h-64 bg-gray-200 dark:bg-gray-800 rounded-lg"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .automation-dotted-bg {
        background-color: #ffffff;
    }
</style>
@endpush

@push('scripts')
@vite('resources/js/automation-builder.jsx')
<script>
(function () {
    // Keep old nodes subtitles in sync when Alpine hydrates after Turbo
    document.addEventListener('turbo:load', function () {
        if (typeof window.automationBuilderData === 'undefined') return;
        window.automationBuilderData.nodes.forEach(function (node) {
            if (typeof window.syncNodeSubtitle === 'function') {
                window.syncNodeSubtitle(node);
            }
        });
    });
})();
</script>
@endpush

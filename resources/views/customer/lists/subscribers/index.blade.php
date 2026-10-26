@extends('layouts.customer')

@section('title', 'Subscribers – ' . ($list->display_name ?? $list->name))

@php
    $listCustomColumns = [];
    $columnKeys = [];
    $reservedColumnKeys = ['email','first_name','last_name','status','source','subscribed_at','unsubscribed_at','tags','custom_fields','notes','campaigns','open_rate','click_rate'];

    $definedCustomFields = is_array($list->custom_fields ?? null)
        ? array_values(array_filter($list->custom_fields, fn ($f) => is_array($f) && !empty($f['key'])))
        : [];

    foreach ($definedCustomFields as $field) {
        $key = trim((string) ($field['key'] ?? ''));
        if ($key === '') continue;
        $kl = strtolower($key);
        if (isset($columnKeys[$kl]) || in_array($kl, $reservedColumnKeys, true)) continue;
        $listCustomColumns[] = ['key' => $key, 'label' => trim((string) ($field['label'] ?? '')) !== '' ? (string) $field['label'] : $key];
        $columnKeys[$kl] = true;
    }

    $subscriberRows = method_exists($subscribers, 'getCollection') ? $subscribers->getCollection() : collect($subscribers ?? []);
    foreach ($subscriberRows as $sr) {
        foreach (array_keys(is_array($sr->custom_fields ?? null) ? $sr->custom_fields : []) as $rawKey) {
            $key = trim((string) $rawKey);
            if ($key === '') continue;
            $kl = strtolower($key);
            if (isset($columnKeys[$kl]) || in_array($kl, $reservedColumnKeys, true)) continue;
            $listCustomColumns[] = ['key' => $key, 'label' => ucwords(str_replace('_', ' ', $key))];
            $columnKeys[$kl] = true;
        }
    }

    $avatarColors = ['bg-blue-500','bg-indigo-500','bg-violet-500','bg-emerald-500','bg-amber-500','bg-rose-500','bg-cyan-500','bg-fuchsia-500'];
    $confirmedCount = (int) ($list->confirmed_subscribers_count ?? 0);
    $lastActivity   = $list->last_subscriber_at ?? $list->updated_at ?? null;
    $activeStatusFilter = $filters['status'] ?? '';
    $activeTagFilter = trim((string) ($filters['tag'] ?? ''));
    $statusLabels = ['confirmed' => 'Confirmed','unconfirmed' => 'Unconfirmed','unsubscribed' => 'Unsubscribed','bounced' => 'Bounced'];
    $hasActiveSubscriberFilters = filled($filters['search'] ?? null) || filled($activeStatusFilter) || filled($activeTagFilter) || $activeSegment !== null;
    $allColumnOptions = [
        ['key' => 'subscriber', 'label' => 'Subscriber'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'tags', 'label' => 'Tags'],
        ['key' => 'engagement', 'label' => 'Engagement'],
        ['key' => 'date_added', 'label' => 'Date Added'],
    ];
    foreach ($listCustomColumns as $field) {
        $allColumnOptions[] = [
            'key' => 'custom:' . (string) ($field['key'] ?? ''),
            'label' => (string) ($field['label'] ?? $field['key'] ?? 'Custom Field'),
        ];
    }
    $allColumnKeys = array_values(array_map(static fn ($column) => (string) ($column['key'] ?? ''), $allColumnOptions));

    function subEngagementLevel(float $r): string {
        if ($r >= 40) return 'High';
        if ($r >= 15) return 'Medium';
        if ($r > 0)  return 'Low';
        return 'None';
    }
@endphp

@section('content')
<div class="space-y-5" x-data="{ showColMenu: false, visibleColumns: @js($allColumnKeys) }">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    @include('customer.lists.partials.header', ['list' => $list])


    {{-- ── Tab Nav ──────────────────────────────────────────────────────────── --}}
    @include('customer.lists.partials.subnav', ['list' => $list, 'segmentsCount' => $list->segments()->count()])

    {{-- ── Import Progress ──────────────────────────────────────────────────── --}}
    <div id="importProgressCard" class="hidden rounded-xl border border-admin-border bg-admin-sidebar p-5 shadow-sm">
        <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-gray-50">Import Progress</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                <p id="importStatus" class="mt-0.5 text-sm font-medium text-gray-900 dark:text-gray-100">-</p>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                <p id="importTotal" class="mt-0.5 text-sm font-medium text-gray-900 dark:text-gray-100">0</p>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Processed</p>
                <p id="importProcessed" class="mt-0.5 text-sm font-medium text-gray-900 dark:text-gray-100">0</p>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Imported</p>
                <p id="importImported" class="mt-0.5 text-sm font-medium text-gray-900 dark:text-gray-100">0</p>
            </div>
        </div>
        <div id="importProgressWrap" class="mt-3 hidden">
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                <span id="importProgressText">0 / 0 processed</span>
                <span id="importProgressPercentage">0%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div id="importProgressBar" class="h-2 rounded-full bg-blue-500 transition-all duration-300" style="width:0%"></div>
            </div>
        </div>
        <div id="importFailureWrap" class="mt-3 hidden rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300" role="alert" id="importFailureReason"></div>
    </div>

    {{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('customer.lists.subscribers.index', $list) }}" id="filterForm">
        @if($activeSegment)
            <input type="hidden" name="segment_id" value="{{ $activeSegment->id }}">
        @endif
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                {{-- Search --}}
                <div class="relative w-full sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by email or name..."
                           class="block w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-4 text-sm text-gray-900 shadow-sm placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500">
                </div>

                @if($activeSegment)
                    <div class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-primary-200 bg-primary-50 px-3 py-1.5 text-sm font-medium text-primary-700 dark:border-primary-800 dark:bg-primary-900/30 dark:text-primary-300">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/></svg>
                        <span class="truncate">{{ $activeSegment->name }}</span>
                        <a href="{{ route('customer.lists.subscribers.index', $list) }}" class="ml-1 inline-flex shrink-0 items-center rounded-full p-0.5 hover:bg-primary-100 dark:hover:bg-primary-800/40" title="Remove segment filter">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </a>
                    </div>
                @endif
            </div>

            {{-- Right controls --}}
            <div class="flex items-center gap-2">
                {{-- Status filter --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list-filter-icon lucide-list-filter h-4 w-4 text-gray-400"><path d="M2 5h20"/><path d="M6 12h12"/><path d="M9 19h6"/></svg>
                        Status
                        @if($activeStatusFilter)
                            <span class="rounded-full bg-primary-100 px-1.5 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">{{ $statusLabels[$activeStatusFilter] ?? ucfirst($activeStatusFilter) }}</span>
                        @endif
                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 z-20 mt-1 w-48 origin-top-right rounded-xl border border-admin-border bg-white shadow-lg dark:bg-gray-800">
                        <div class="p-1">
                            @foreach(['' => 'All Statuses', 'confirmed' => 'Confirmed', 'unconfirmed' => 'Unconfirmed', 'unsubscribed' => 'Unsubscribed', 'bounced' => 'Bounced'] as $val => $label)
                                <button type="submit" name="status" value="{{ $val }}"
                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700 {{ $activeStatusFilter === $val ? 'font-semibold text-primary-600 dark:text-primary-400' : '' }}">
                                    @if($activeStatusFilter === $val)
                                        <svg class="h-3.5 w-3.5 text-primary-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                    @else
                                        <span class="h-3.5 w-3.5"></span>
                                    @endif
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Tags filter --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag-icon lucide-tag h-4 w-4 text-gray-400"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                        Tags
                        @if($activeTagFilter)
                            <span class="max-w-28 truncate rounded-full bg-primary-100 px-1.5 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">{{ $activeTagFilter }}</span>
                        @endif
                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 z-20 mt-1 w-56 origin-top-right rounded-xl border border-admin-border bg-white shadow-lg dark:bg-gray-800">
                        <div class="max-h-72 overflow-y-auto p-1">
                            <button type="submit" name="tag" value=""
                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700 {{ $activeTagFilter === '' ? 'font-semibold text-primary-600 dark:text-primary-400' : '' }}">
                                @if($activeTagFilter === '')
                                    <svg class="h-3.5 w-3.5 text-primary-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                @else
                                    <span class="h-3.5 w-3.5"></span>
                                @endif
                                All Tags
                            </button>
                            @forelse($availableTags as $tagOption)
                                <button type="submit" name="tag" value="{{ $tagOption }}"
                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700 {{ $activeTagFilter === $tagOption ? 'font-semibold text-primary-600 dark:text-primary-400' : '' }}">
                                    @if($activeTagFilter === $tagOption)
                                        <svg class="h-3.5 w-3.5 text-primary-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                    @else
                                        <span class="h-3.5 w-3.5"></span>
                                    @endif
                                    <span class="truncate">{{ $tagOption }}</span>
                                </button>
                            @empty
                                <div class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500">No tags available</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Columns toggle --}}
                <div class="relative" @click.outside="showColMenu = false">
                    <button type="button" @click="showColMenu = !showColMenu"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-columns2-icon lucide-columns-2 h-4 w-4 text-gray-400"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M12 3v18"/></svg>
                        Columns
                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-cloak x-show="showColMenu" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 z-20 mt-1 w-64 origin-top-right rounded-xl border border-admin-border bg-white shadow-lg dark:bg-gray-800">
                        <div class="border-b border-admin-border px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Toggle columns
                        </div>
                        <div class="max-h-72 overflow-y-auto p-2">
                            @forelse($allColumnOptions as $column)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <input type="checkbox"
                                           class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                           :checked="visibleColumns.includes(@js((string) ($column['key'] ?? '')))"
                                           @change="if ($event.target.checked) { if (!visibleColumns.includes(@js((string) ($column['key'] ?? '')))) visibleColumns.push(@js((string) ($column['key'] ?? ''))); } else { visibleColumns = visibleColumns.filter(key => key !== @js((string) ($column['key'] ?? ''))); }">
                                    <span class="truncate">{{ $column['label'] ?? $column['key'] }}</span>
                                </label>
                            @empty
                                <div class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500">No columns available</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @if($hasActiveSubscriberFilters)
                    <a href="{{ route('customer.lists.subscribers.index', $list) }}" class="text-sm font-medium text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200">
                        Clear
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- ── Bulk Actions Bar ─────────────────────────────────────────────────── --}}
    <div id="bulkActionsBar" class="hidden rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 dark:border-primary-800 dark:bg-primary-900/20">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <span id="selectedCount" class="text-sm font-semibold text-primary-900 dark:text-primary-100">0 selected</span>
                <button type="button" id="selectAllMatchingBtn" onclick="toggleSelectAllMatching()" class="hidden text-sm text-primary-700 underline hover:text-primary-900 dark:text-primary-300">
                    Select all {{ $subscribers->total() }}
                </button>
                <div class="flex flex-wrap gap-2">
                    @if(count($availableSegments) > 0)
                        <form id="bulkAddToSegmentForm" method="POST" action="{{ route('customer.lists.subscribers.bulk-add-to-segment', $list) }}" class="inline">
                            @csrf
                            <input type="hidden" name="subscriber_ids" id="bulkAddToSegmentIds">
                            <input type="hidden" name="all_matching" class="bulk-all-matching" value="0">
                            <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                            <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                            <input type="hidden" name="tag" value="{{ $filters['tag'] ?? '' }}">
                            <input type="hidden" name="segment_id" value="{{ $activeSegment?->id ?? '' }}">
                            <div class="flex items-center gap-1">
                                <select name="target_segment_id" required
                                        class="rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Add to segment...</option>
                                    @foreach($availableSegments as $segId => $segName)
                                        <option value="{{ $segId }}">{{ $segName }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-700">Add</button>
                            </div>
                        </form>
                    @endif
                    <form id="bulkConfirmForm" method="POST" action="{{ route('customer.lists.subscribers.bulk-confirm', $list) }}" class="inline">
                        @csrf
                        <input type="hidden" name="subscriber_ids" id="bulkConfirmIds">
                        <input type="hidden" name="all_matching" class="bulk-all-matching" value="0">
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="tag" value="{{ $filters['tag'] ?? '' }}">
                        <input type="hidden" name="segment_id" value="{{ $activeSegment?->id ?? '' }}">
                        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Confirm</button>
                    </form>
                    <form id="bulkUnsubscribeForm" method="POST" action="{{ route('customer.lists.subscribers.bulk-unsubscribe', $list) }}" class="inline">
                        @csrf
                        <input type="hidden" name="subscriber_ids" id="bulkUnsubscribeIds">
                        <input type="hidden" name="all_matching" class="bulk-all-matching" value="0">
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="tag" value="{{ $filters['tag'] ?? '' }}">
                        <input type="hidden" name="segment_id" value="{{ $activeSegment?->id ?? '' }}">
                        <button type="submit" class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">Unsubscribe</button>
                    </form>
                    <form id="bulkResendForm" method="POST" action="{{ route('customer.lists.subscribers.bulk-resend', $list) }}" class="inline">
                        @csrf
                        <input type="hidden" name="subscriber_ids" id="bulkResendIds">
                        <input type="hidden" name="all_matching" class="bulk-all-matching" value="0">
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="tag" value="{{ $filters['tag'] ?? '' }}">
                        <input type="hidden" name="segment_id" value="{{ $activeSegment?->id ?? '' }}">
                        <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">Resend</button>
                    </form>
                    <form id="bulkDeleteForm" method="POST" action="{{ route('customer.lists.subscribers.bulk-delete', $list) }}" class="inline"
                          onsubmit="return confirm('Delete selected subscribers? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="subscriber_ids" id="bulkDeleteIds">
                        <input type="hidden" name="all_matching" class="bulk-all-matching" value="0">
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="tag" value="{{ $filters['tag'] ?? '' }}">
                        <input type="hidden" name="segment_id" value="{{ $activeSegment?->id ?? '' }}">
                        <button type="submit" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">Delete</button>
                    </form>
                </div>
            </div>
            <button onclick="clearSelection()" class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400">Clear</button>
        </div>
        @if($hasActiveSubscriberFilters && $subscribers->total() > 0)
        <div class="mt-3 flex items-center justify-between border-t border-primary-200 pt-3 dark:border-primary-800">
            <p class="text-xs text-primary-700 dark:text-primary-300">{{ number_format($subscribers->total()) }} subscriber(s) match current filters.</p>
            <form method="POST" action="{{ route('customer.lists.subscribers.bulk-delete', $list) }}"
                  onsubmit="return confirm('Delete ALL filtered subscribers? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="all_matching" value="1">
                <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                <input type="hidden" name="tag" value="{{ $filters['tag'] ?? '' }}">
                <input type="hidden" name="segment_id" value="{{ $activeSegment?->id ?? '' }}">
                <button type="submit" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">Delete All Filtered</button>
            </form>
        </div>
        @endif
    </div>

    {{-- ── Table ────────────────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-admin-border bg-admin-sidebar shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-admin-border bg-gray-50/70 dark:bg-gray-800/60">
                        <th class="w-12 px-4 py-3.5 text-left">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"
                                   class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                        </th>
                        <th x-show="visibleColumns.includes('subscriber')" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Subscriber</th>
                        <th x-show="visibleColumns.includes('status')" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        <th x-show="visibleColumns.includes('tags')" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tags</th>
                        @foreach($listCustomColumns as $field)
                            <th x-show="visibleColumns.includes(@js('custom:' . (string) ($field['key'] ?? '')))" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $field['label'] ?? $field['key'] }}</th>
                        @endforeach
                        <th x-show="visibleColumns.includes('engagement')" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Engagement</th>
                        <th x-show="visibleColumns.includes('date_added')" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Date Added</th>
                        <th class="w-10 px-4 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($subscribers as $subscriber)
                        @php
                            $openRate   = (float) ($subscriber->open_rate ?? 0);
                            $clickRate  = (float) ($subscriber->click_rate ?? 0);
                            $engLevel   = subEngagementLevel($openRate);
                            $barWidth   = (int) min(100, $openRate);
                            $barColor   = match($engLevel) {
                                'High'   => 'bg-blue-500',
                                'Medium' => 'bg-blue-400',
                                'Low'    => 'bg-blue-300',
                                default  => 'bg-gray-300 dark:bg-gray-600',
                            };
                            $initials = '';
                            if ($subscriber->first_name || $subscriber->last_name) {
                                $initials = strtoupper(
                                    substr((string) ($subscriber->first_name ?? ''), 0, 1) .
                                    substr((string) ($subscriber->last_name ?? ''), 0, 1)
                                );
                            } else {
                                $initials = strtoupper(substr($subscriber->email, 0, 2));
                            }
                            $avatarBg = $avatarColors[abs(crc32($subscriber->email)) % count($avatarColors)];

                            $statusStyle = match($subscriber->status) {
                                'confirmed'   => 'bg-emerald-500 text-white',
                                'unconfirmed' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                'unsubscribed'=> 'border border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-400',
                                'bounced'     => 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300',
                                default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            };
                            $statusLabel = match($subscriber->status) {
                                'confirmed'   => 'Subscribed',
                                'unconfirmed' => 'Unconfirmed',
                                'unsubscribed'=> 'Unsubscribed',
                                'bounced'     => 'Bounced',
                                default       => ucfirst((string) $subscriber->status),
                            };
                            $failedCount = (int) ($subscriber->failed_count ?? 0);
                            $bouncedCount = (int) ($subscriber->bounced_count ?? 0);
                        @endphp
                        <tr class="group transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
                            {{-- Checkbox --}}
                            <td class="px-4 py-3.5">
                                <input type="checkbox" name="subscriber_ids[]" value="{{ $subscriber->id }}"
                                       class="subscriber-checkbox h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                       onchange="updateBulkActions(false)">
                            </td>

                            {{-- Subscriber --}}
                            <td x-show="visibleColumns.includes('subscriber')" class="px-4 py-3.5">
                                <a href="{{ route('customer.lists.subscribers.show', ['list' => $list, 'subscriber' => $subscriber]) }}"
                                   class="flex min-w-0 items-center gap-3 hover:no-underline">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $avatarBg }} text-xs font-semibold text-white select-none">
                                        {{ $initials ?: '?' }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-900 group-hover:text-primary-600 dark:text-gray-100 dark:group-hover:text-primary-400">
                                            {{ trim(($subscriber->first_name ?? '') . ' ' . ($subscriber->last_name ?? '')) ?: 'Unnamed' }}
                                        </p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $subscriber->email }}</p>
                                    </div>
                                </a>
                            </td>

                            {{-- Status --}}
                            <td x-show="visibleColumns.includes('status')" class="px-4 py-3.5">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- Tags --}}
                            <td x-show="visibleColumns.includes('tags')" class="px-4 py-3.5">
                                @if(is_array($subscriber->tags ?? null) && count($subscriber->tags) > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(array_slice($subscriber->tags, 0, 2) as $tag)
                                            <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $tag }}</span>
                                        @endforeach
                                        @if(count($subscriber->tags) > 2)
                                            <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">+{{ count($subscriber->tags) - 2 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>

                            {{-- Custom fields --}}
                            @foreach($listCustomColumns as $field)
                                @php
                                    $fk  = (string) ($field['key'] ?? '');
                                    $scf = is_array($subscriber->custom_fields ?? null) ? $subscriber->custom_fields : [];
                                    $fv  = array_key_exists($fk, $scf) ? $scf[$fk] : null;
                                @endphp
                                <td x-show="visibleColumns.includes(@js('custom:' . (string) ($field['key'] ?? '')))" class="px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300">
                                    @if(is_array($fv))
                                        <div class="mp-line-clamp-3">{{ implode(', ', array_map(fn($v) => is_scalar($v) ? (string) $v : '', $fv)) ?: '—' }}</div>
                                    @elseif($fv !== null && $fv !== '')
                                        <div class="mp-line-clamp-3">{{ is_bool($fv) ? ($fv ? 'Yes' : 'No') : (string) $fv }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                            @endforeach

                            {{-- Engagement --}}
                            <td x-show="visibleColumns.includes('engagement')" class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $barWidth }}%"></div>
                                    </div>
                                    <span class="whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{{ $engLevel }}</span>
                                </div>
                            </td>

                            {{-- Date Added --}}
                            <td x-show="visibleColumns.includes('date_added')" class="px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span>{{ optional($subscriber->created_at)->format('M d, Y') ?? '—' }}</span>
                                    @if($failedCount > 0 || $bouncedCount > 0)
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            @if($failedCount > 0)
                                                <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200 dark:bg-red-900/20 dark:text-red-300 dark:ring-red-800/60">
                                                    {{ number_format($failedCount) }} failed
                                                </span>
                                            @endif
                                            @if($bouncedCount > 0)
                                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800/60">
                                                    {{ number_format($bouncedCount) }} bounced
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <x-button href="{{ route('customer.lists.subscribers.show', ['list' => $list, 'subscriber' => $subscriber]) }}" variant="table" size="action" :pill="true" class="p-2" title="View Details" aria-label="View Details"><x-lucide name="eye" class="h-4 w-4" /><span class="sr-only">View Details</span></x-button>
                                    @customercan('lists.permissions.can_edit_lists')
                                        <x-button href="{{ route('customer.lists.subscribers.edit', ['list' => $list, 'subscriber' => $subscriber]) }}" variant="table" size="action" :pill="true" class="p-2" title="Edit" aria-label="Edit"><x-lucide name="pencil" class="h-4 w-4" /><span class="sr-only">Edit</span></x-button>
                                        @if($subscriber->status === 'unconfirmed')
                                            <form method="POST" action="{{ route('customer.lists.subscribers.confirm', ['list' => $list, 'subscriber' => $subscriber]) }}" class="inline">
                                                @csrf
                                                <x-button type="submit" variant="table" size="action" :pill="true" class="p-2" title="Confirm" aria-label="Confirm"><x-lucide name="check" class="h-4 w-4" /><span class="sr-only">Confirm</span></x-button>
                                            </form>
                                        @elseif($subscriber->status === 'confirmed')
                                            <form method="POST" action="{{ route('customer.lists.subscribers.unsubscribe', ['list' => $list, 'subscriber' => $subscriber]) }}" class="inline">
                                                @csrf
                                                <x-button type="submit" variant="table" size="action" :pill="true" class="p-2" title="Unsubscribe" aria-label="Unsubscribe"><x-lucide name="user-minus" class="h-4 w-4" /><span class="sr-only">Unsubscribe</span></x-button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('customer.lists.subscribers.destroy', ['list' => $list, 'subscriber' => $subscriber]) }}" class="inline" onsubmit="return confirm('Delete this subscriber?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="table-danger" size="action" :pill="true" class="p-2" title="Delete" aria-label="Delete"><x-lucide name="trash-2" class="h-4 w-4" /><span class="sr-only">Delete</span></x-button>
                                        </form>
                                    @endcustomercan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 8 + count($listCustomColumns) }}">
                                <div class="mp-empty">
                                    <div class="mp-empty__icon">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
                                    </div>
                                    <p class="mp-empty__title">No subscribers found</p>
                                    <p class="mp-empty__desc">
                                        @if($hasActiveSubscriberFilters)
                                            <a href="{{ route('customer.lists.subscribers.index', $list) }}" class="font-medium" style="color: var(--tw-brand)">Clear filters</a>
                                        @else
                                            <a href="{{ route('customer.lists.subscribers.create', $list) }}" class="font-medium" style="color: var(--tw-brand)">Add first subscriber</a>
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex flex-col items-center justify-between gap-3 border-t border-admin-border px-5 py-4 sm:flex-row">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                @if($subscribers->total() > 0)
                    Showing <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($subscribers->firstItem()) }}</span>
                    to <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($subscribers->lastItem()) }}</span>
                    of <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($subscribers->total()) }}</span> subscribers
                @else
                    No subscribers
                @endif
            </p>
            <div>
                {{ $subscribers->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let importRefreshInterval = null;
    let allMatchingSelected = false;
    const totalMatchingSubscribers = {{ $subscribers->total() }};

    function formatNumber(value) {
        return new Intl.NumberFormat().format(parseInt(value || 0, 10));
    }

    function capitalize(value) {
        const text = String(value || '');
        if (!text) return '';
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function setHidden(el, hidden) {
        if (!el) return;
        if (hidden) el.classList.add('hidden');
        else el.classList.remove('hidden');
    }

    function updateImportStats() {
        fetch('{{ route('customer.lists.subscribers.import.stats', $list) }}', {
            method: 'GET', headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) return;
            const importCard = document.getElementById('importProgressCard');
            const stats = data.import;
            if (!stats) {
                setHidden(importCard, true);
                if (importRefreshInterval) { clearInterval(importRefreshInterval); importRefreshInterval = null; }
                return;
            }
            setHidden(importCard, false);
            const status   = String(stats.status || 'unknown');
            const total    = parseInt(stats.total_rows || 0, 10);
            const processed = parseInt(stats.processed_count || 0, 10);
            const imported  = parseInt(stats.imported_count || 0, 10);
            const percent   = stats.percent || 0;
            const el = id => document.getElementById(id);
            if (el('importStatus'))    el('importStatus').textContent    = capitalize(status);
            if (el('importTotal'))     el('importTotal').textContent     = formatNumber(total);
            if (el('importProcessed')) el('importProcessed').textContent = formatNumber(processed);
            if (el('importImported'))  el('importImported').textContent  = formatNumber(imported);
            const showProgress = (status === 'queued' || status === 'running') && total > 0;
            setHidden(el('importProgressWrap'), !showProgress);
            if (showProgress) {
                if (el('importProgressText'))       el('importProgressText').textContent       = formatNumber(processed) + ' / ' + formatNumber(total) + ' processed';
                if (el('importProgressPercentage')) el('importProgressPercentage').textContent = String(percent) + '%';
                if (el('importProgressBar'))        el('importProgressBar').style.width        = String(percent) + '%';
            }
            const isFailed = status === 'failed';
            setHidden(el('importFailureWrap'), !isFailed);
            if (isFailed && el('importFailureReason')) el('importFailureReason').textContent = String(stats.failure_reason || 'Import failed.');
            if (status === 'completed' || status === 'failed') {
                if (importRefreshInterval) { clearInterval(importRefreshInterval); importRefreshInterval = null; }
            }
        })
        .catch(() => {});
    }

    function toggleSelectAll(checkbox) {
        document.querySelectorAll('.subscriber-checkbox').forEach(cb => { cb.checked = checkbox.checked; });
        updateBulkActions(false);
    }

    function toggleSelectAllMatching() {
        allMatchingSelected = !allMatchingSelected;
        if (allMatchingSelected) {
            document.querySelectorAll('.subscriber-checkbox').forEach(cb => { cb.checked = true; });
        }
        updateBulkActions(true);
    }

    function updateBulkActions(preserveAllMatching = true) {
        if (!preserveAllMatching) allMatchingSelected = false;
        const checked = document.querySelectorAll('.subscriber-checkbox:checked');
        const selectedIds = Array.from(checked).map(cb => cb.value);
        const count = allMatchingSelected ? totalMatchingSubscribers : selectedIds.length;
        const bulkBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        const selectAllMatchingBtn = document.getElementById('selectAllMatchingBtn');
        if (count > 0) {
            bulkBar.classList.remove('hidden');
            selectedCount.textContent = (allMatchingSelected ? 'All ' : '') + count + ' selected';
            document.getElementById('bulkConfirmIds').value     = allMatchingSelected ? '' : selectedIds.join(',');
            document.getElementById('bulkUnsubscribeIds').value = allMatchingSelected ? '' : selectedIds.join(',');
            document.getElementById('bulkResendIds').value      = allMatchingSelected ? '' : selectedIds.join(',');
            document.getElementById('bulkDeleteIds').value      = allMatchingSelected ? '' : selectedIds.join(',');
            const addToSegmentInput = document.getElementById('bulkAddToSegmentIds');
            if (addToSegmentInput) {
                addToSegmentInput.value = allMatchingSelected ? '' : selectedIds.join(',');
            }
            document.querySelectorAll('.bulk-all-matching').forEach(el => { el.value = allMatchingSelected ? '1' : '0'; });
            if (selectAllMatchingBtn) {
                if (allMatchingSelected) {
                    selectAllMatchingBtn.classList.remove('hidden');
                    selectAllMatchingBtn.textContent = 'Select this page only';
                } else if (selectedIds.length > 0 && totalMatchingSubscribers > selectedIds.length) {
                    selectAllMatchingBtn.classList.remove('hidden');
                    selectAllMatchingBtn.textContent = 'Select all ' + totalMatchingSubscribers;
                } else {
                    selectAllMatchingBtn.classList.add('hidden');
                }
            }
        } else {
            bulkBar.classList.add('hidden');
            if (selectAllMatchingBtn) selectAllMatchingBtn.classList.add('hidden');
        }
        const allCbs = document.querySelectorAll('.subscriber-checkbox');
        const selectAll = document.getElementById('selectAll');
        if (allCbs.length > 0) {
            selectAll.checked = checked.length === allCbs.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < allCbs.length;
        }
    }

    function clearSelection() {
        allMatchingSelected = false;
        document.querySelectorAll('.subscriber-checkbox').forEach(cb => { cb.checked = false; });
        const sa = document.getElementById('selectAll');
        sa.checked = false; sa.indeterminate = false;
        updateBulkActions(true);
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateBulkActions(true);
        updateImportStats();
        if (!importRefreshInterval) importRefreshInterval = setInterval(updateImportStats, 3000);
    });
</script>
@endpush
@endsection


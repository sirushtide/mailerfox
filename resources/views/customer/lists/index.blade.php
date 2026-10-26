@extends('layouts.customer')

@section('title', 'Audience')

@section('page-title', 'Audience')

@section('page-actions')
    <div class="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
        <a href="{{ route('customer.segments.create') }}"
           class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Create Segment
        </a>
        <a href="{{ route('customer.lists.create') }}"
           data-track="list.create"
           class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Create List
        </a>
    </div>
@endsection

@section('content')
@php
    $statCards = [
        [
            'label' => 'Total Contacts',
            'value' => number_format($audienceStats['total_contacts']),
            'delta' => $audienceStats['total_contacts_delta'],
            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list-icon lucide-clipboard-list"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>',
            'down_is_good' => false,
        ],
        [
            'label' => 'Active Subscribers',
            'value' => number_format($audienceStats['active_subscribers']),
            'delta' => $audienceStats['active_subscribers_delta'],
            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="green-600" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-check-icon lucide-user-check"><path d="m16 11 2 2 4-4"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
            'down_is_good' => false,
        ],
        [
            'label' => 'Avg. Open Rate',
            'value' => $audienceStats['avg_open_rate'] . '%',
            'delta' => $audienceStats['avg_open_rate_delta'],
            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail-open-icon lucide-mail-open"><path d="M21.2 8.4c.5.38.8.97.8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10a2 2 0 0 1 .8-1.6l8-6a2 2 0 0 1 2.4 0l8 6Z"/><path d="m22 10-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 10"/></svg>',
            'down_is_good' => false,
        ],
        [
            'label' => 'Unsubscribed',
            'value' => number_format($audienceStats['unsubscribed']),
            'delta' => $audienceStats['unsubscribed_delta'],
            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-minus-icon lucide-user-minus"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="22" x2="16" y1="11" y2="11"/></svg>',
            'down_is_good' => true,
        ],
    ];

    $viewTabs = [
        ['key' => 'all',      'label' => 'All Lists & Segments', 'count' => $audienceStats['all_count']],
        ['key' => 'lists',    'label' => 'Lists',                'count' => $audienceStats['lists_count']],
        ['key' => 'segments', 'label' => 'Segments',             'count' => $audienceStats['segments_count']],
    ];

    $sortOptions = [
        'updated_at_desc' => 'Date',
        'name_asc'        => 'Name A–Z',
        'name_desc'       => 'Name Z–A',
        'subs_desc'       => 'Subscribers',
    ];
@endphp

<div class="space-y-6">

    {{-- ── Stat cards ─────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($statCards as $card)
        @php
            $d = $card['delta'];
            $isPositive = $d !== null && $d >= 0;
            $isNegative = $d !== null && $d < 0;
            $goodColor  = $card['down_is_good'] ? ($isNegative ? 'text-emerald-600 dark:text-emerald-400' : ($isPositive ? 'text-rose-500 dark:text-rose-400' : 'text-gray-400')) : ($isPositive ? 'text-emerald-600 dark:text-emerald-400' : ($isNegative ? 'text-rose-500 dark:text-rose-400' : 'text-gray-400'));
        @endphp
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</span>
                <svg class="h-5 w-5 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">{!! $card['icon'] !!}</svg>
            </div>
            <div class="mt-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-50">{{ $card['value'] }}</div>
            <div class="mt-2 flex items-center gap-1 {{ $goodColor }}">
                @if($d !== null)
                    @if($isPositive)
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
                    @else
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.306-4.306a11.95 11.95 0 0 1 5.814 5.518l2.74 1.22m0 0-5.94 2.281m5.94-2.28-2.28-5.941"/></svg>
                    @endif
                    <span class="text-xs font-medium">{{ $isPositive ? '+' : '' }}{{ $d }}% from last month</span>
                @else
                    <span class="text-xs text-gray-400 dark:text-gray-500">from last month</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Combined table card ─────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

        {{-- Filter tabs --}}
        <div class="border-b border-gray-200 px-6 dark:border-gray-700">
            <nav class="-mb-px flex gap-6" aria-label="View tabs">
                @foreach($viewTabs as $t)
                <a href="{{ request()->fullUrlWithQuery(['view' => $t['key'], 'page' => 1]) }}"
                   class="flex items-center gap-2 whitespace-nowrap border-b-2 py-4 text-sm font-medium transition {{ $viewTab === $t['key'] ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    {{ $t['label'] }}
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $viewTab === $t['key'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">{{ $t['count'] }}</span>
                </a>
                @endforeach
            </nav>
        </div>

        {{-- Toolbar --}}
        <form method="GET" action="{{ route('customer.lists.index') }}" class="flex flex-col gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between" x-data>
            <input type="hidden" name="view" value="{{ $viewTab }}">
            <div class="relative w-full sm:max-w-xs">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="{{ $viewTab === 'lists' ? 'Search lists…' : ($viewTab === 'segments' ? 'Search segments…' : 'Search lists and segments…') }}"
                       class="block w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500">
            </div>
            <div class="flex items-center gap-2">
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Sort by: {{ $sortOptions[$sortBy] ?? 'Date' }}
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak
                         class="absolute right-0 z-10 mt-1 w-44 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        @foreach($sortOptions as $val => $label)
                        <button type="submit" name="sort" value="{{ $val }}"
                                class="block w-full px-4 py-2 text-left text-sm {{ $sortBy === $val ? 'bg-primary-50 font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>
                <button type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/></svg>
                    Filter
                </button>
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px]">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70 dark:border-gray-700 dark:bg-gray-700/30">
                        <th class="w-10 px-4 py-3 text-left">
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                        </th>
                        <th class="w-10 px-2 py-3"></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subscribers</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Open Rate</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Click Rate</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Last Updated</th>
                        <th class="w-10 px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($paginator as $item)
                    <tr class="group transition hover:bg-gray-50/70 dark:hover:bg-gray-700/20">
                        <td class="px-4 py-4">
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                        </td>
                        <td class="px-2 py-4">
                            @if($item['type'] === 'list')
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-900/20">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                </span>
                            @else
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-500 dark:bg-violet-900/20">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/></svg>
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <a href="{{ $item['url'] }}"
                               class="font-semibold text-gray-900 hover:text-primary-600 dark:text-gray-100 dark:hover:text-primary-400">
                                {{ $item['name'] }}
                            </a>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $item['subtitle'] }}</p>
                        </td>
                        <td class="px-4 py-4">
                            @if($item['type'] === 'list')
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">List</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-900/20 dark:text-violet-400">Segment</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ number_format($item['subscribers']) }}</td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                            {{ $item['open_rate'] > 0 ? $item['open_rate'] . '%' : '—' }}
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                            {{ $item['click_rate'] > 0 ? $item['click_rate'] . '%' : '—' }}
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-400 dark:text-gray-500">
                            @if($item['updated_at'])
                                @php $ts = $item['updated_at']; @endphp
                                {{ $ts->isToday() ? 'Today, ' . $ts->format('g:i A') : ($ts->isYesterday() ? 'Yesterday, ' . $ts->format('g:i A') : $ts->format('M j, Y')) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($item['type'] === 'list')
                                    <x-button href="{{ route('customer.lists.subscribers.index', $item['id']) }}" variant="table" size="action" :pill="true" class="p-2" title="View Subscribers" aria-label="View Subscribers"><x-lucide name="users" class="h-4 w-4" /><span class="sr-only">View Subscribers</span></x-button>
                                    <x-button href="{{ route('customer.lists.settings', $item['id']) }}" variant="table" size="action" :pill="true" class="p-2" title="Settings" aria-label="Settings"><x-lucide name="settings" class="h-4 w-4" /><span class="sr-only">Settings</span></x-button>
                                    <form method="POST" action="{{ route('customer.lists.destroy', $item['id']) }}" class="inline" onsubmit="return confirm('Delete this list?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="table-danger" size="action" :pill="true" class="p-2" title="Delete" aria-label="Delete"><x-lucide name="trash-2" class="h-4 w-4" /><span class="sr-only">Delete</span></x-button>
                                    </form>
                                @else
                                    {{-- Segment actions --}}
                                    <x-button href="{{ route('customer.lists.subscribers.index', $item['list_id']) }}?segment_id={{ $item['id'] }}" variant="table" size="action" :pill="true" class="p-2" title="View Subscribers" aria-label="View Subscribers"><x-lucide name="eye" class="h-4 w-4" /><span class="sr-only">View Subscribers</span></x-button>
                                    @customercan('lists.permissions.can_edit_lists')
                                        <x-button href="{{ route('customer.segments.create', ['list_id' => $item['list_id']]) }}" variant="table" size="action" :pill="true" class="p-2" title="Duplicate" aria-label="Duplicate"><x-lucide name="copy" class="h-4 w-4" /><span class="sr-only">Duplicate</span></x-button>
                                        <form method="POST" action="{{ route('customer.lists.segments.destroy', ['list' => $item['list_id'], 'segment' => $item['id']]) }}" class="inline" onsubmit="return confirm('Delete segment \'{{ addslashes($item['name']) }}\'? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="table-danger" size="action" :pill="true" class="p-2" title="Delete" aria-label="Delete"><x-lucide name="trash-2" class="h-4 w-4" /><span class="sr-only">Delete</span></x-button>
                                        </form>
                                    @endcustomercan
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="mp-empty">
                                <div class="mp-empty__icon">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                </div>
                                <p class="mp-empty__title">
                                    @if($search) No results for "{{ $search }}" @else No lists or segments yet @endif
                                </p>
                                <p class="mp-empty__desc">
                                    @unless($search)
                                    <a href="{{ route('customer.lists.create') }}"
                                       data-track="list.create"
                                       class="font-medium" style="color: var(--tw-brand)">
                                        Create your first list
                                    </a>
                                    @endunless
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        @if($paginator->total() > 0)
        <div class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ number_format($paginator->total()) }} {{ $viewTab === 'segments' ? 'segments' : ($viewTab === 'lists' ? 'lists' : 'items') }}
            </p>
            <div class="flex items-center gap-2">
                @if($paginator->onFirstPage())
                    <span class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-400 dark:border-gray-700 dark:bg-gray-800">Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}"
                       class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Previous</a>
                @endif
                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}"
                       class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Next</a>
                @else
                    <span class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-400 dark:border-gray-700 dark:bg-gray-800">Next</span>
                @endif
            </div>
        </div>
        @endif

    </div>{{-- end table card --}}

</div>
@endsection

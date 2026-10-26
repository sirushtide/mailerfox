@extends('layouts.admin')

@section('title', 'Visitor Tracking')
@section('page-title', 'Visitor Tracking')

@section('content')
<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-4">
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary">Total Sessions</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ $stats['total_sessions'] }}</p>
        </div>
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-4">
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary">Page Views</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ $stats['total_page_views'] }}</p>
        </div>
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-4">
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary">Button Clicks</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ $stats['total_clicks'] }}</p>
        </div>
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-4">
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary">Avg Duration</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-admin-text-primary">
                @if($stats['avg_duration'] >= 60)
                    {{ floor($stats['avg_duration'] / 60) }}m {{ $stats['avg_duration'] % 60 }}s
                @else
                    {{ $stats['avg_duration'] }}s
                @endif
            </p>
        </div>
    </div>

    {{-- Sub stats --}}
    <div class="flex flex-wrap gap-4 text-sm">
        <span class="text-gray-500 dark:text-admin-text-secondary">Guests: <strong class="text-gray-900 dark:text-admin-text-primary">{{ $stats['guest_sessions'] }}</strong></span>
        <span class="text-gray-500 dark:text-admin-text-secondary">Customers: <strong class="text-gray-900 dark:text-admin-text-primary">{{ $stats['customer_sessions'] }}</strong></span>
        <span class="text-gray-500 dark:text-admin-text-secondary">Countries: <strong class="text-gray-900 dark:text-admin-text-primary">{{ $stats['unique_countries'] }}</strong></span>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-4 space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.visitor-tracking.index', ['range' => '24h']) }}" class="px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-admin-border hover:bg-gray-50 dark:hover:bg-white/5 {{ $range === '24h' ? 'bg-[#1E5FEA]/10 text-[#1E5FEA]' : 'text-gray-700 dark:text-admin-text-secondary' }}">24h</a>
                <a href="{{ route('admin.visitor-tracking.index', ['range' => '7d']) }}" class="px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-admin-border hover:bg-gray-50 dark:hover:bg-white/5 {{ $range === '7d' ? 'bg-[#1E5FEA]/10 text-[#1E5FEA]' : 'text-gray-700 dark:text-admin-text-secondary' }}">7d</a>
                <a href="{{ route('admin.visitor-tracking.index', ['range' => '30d']) }}" class="px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-admin-border hover:bg-gray-50 dark:hover:bg-white/5 {{ $range === '30d' ? 'bg-[#1E5FEA]/10 text-[#1E5FEA]' : 'text-gray-700 dark:text-admin-text-secondary' }}">30d</a>
            </div>

            <form method="GET" action="{{ route('admin.visitor-tracking.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="hidden" name="range" value="custom" />
                <input type="date" name="from" value="{{ optional($startDate)->toDateString() }}" class="px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-700 dark:text-admin-text-primary" />
                <input type="date" name="to" value="{{ optional($endDate)->toDateString() }}" class="px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-700 dark:text-admin-text-primary" />
                <button type="submit" class="px-3 py-2 rounded-lg text-sm bg-[#1E5FEA] text-white hover:bg-[#1a4fd0]">Apply</button>
            </form>
        </div>

        <form method="GET" action="{{ route('admin.visitor-tracking.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="range" value="{{ $range }}" />
            @if($range === 'custom')
                <input type="hidden" name="from" value="{{ optional($startDate)->toDateString() }}" />
                <input type="hidden" name="to" value="{{ optional($endDate)->toDateString() }}" />
            @endif
            <select name="filter_type" class="px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-700 dark:text-admin-text-primary">
                <option value="">All Visitors</option>
                <option value="guest" {{ $filterType === 'guest' ? 'selected' : '' }}>Guests Only</option>
                <option value="customer" {{ $filterType === 'customer' ? 'selected' : '' }}>Customers Only</option>
            </select>
            <select name="country" class="px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-700 dark:text-admin-text-primary">
                <option value="">All Countries</option>
                @foreach($countries as $code)
                    <option value="{{ $code }}" {{ $filterCountry === $code ? 'selected' : '' }}>{{ $code }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search IP or email..." class="px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-700 dark:text-admin-text-primary w-48" />
            <button type="submit" class="px-3 py-2 rounded-lg text-sm bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-admin-text-primary hover:bg-gray-200 dark:hover:bg-white/20">Filter</button>
        </form>
    </div>

    {{-- Sessions Table --}}
    <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-admin-border bg-gray-50 dark:bg-white/5">
                        <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-admin-text-secondary">Visitor</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-admin-text-secondary">Location</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-admin-text-secondary">Type</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-admin-text-secondary">Pages</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-admin-text-secondary">Clicks</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-admin-text-secondary">Duration</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-admin-text-secondary">Last Active</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-admin-text-secondary">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-admin-border/60">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900 dark:text-admin-text-primary">{{ $session->ip ?? 'Unknown' }}</span>
                                    @if($session->customer)
                                        <span class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ $session->customer->email }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">Guest</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-admin-text-secondary">
                                @if($session->city || $session->country_code)
                                    {{ trim(($session->city ?? '') . ', ' . ($session->country_code ?? ''), ', ') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($session->is_guest)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-admin-text-secondary">Guest</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400">Customer</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-admin-text-primary">{{ $session->page_views_count }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-admin-text-primary">{{ $session->clicks_count }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-admin-text-primary">
                                @if($session->duration_seconds >= 60)
                                    {{ floor($session->duration_seconds / 60) }}m {{ $session->duration_seconds % 60 }}s
                                @else
                                    {{ $session->duration_seconds }}s
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-admin-text-secondary whitespace-nowrap">
                                {{ $session->last_activity_at?->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if($session->customer_id)
                                        <a href="{{ route('admin.visitor-tracking.customer', $session->customer_id) }}" class="text-purple-500 hover:underline text-sm">Journey</a>
                                    @endif
                                    <a href="{{ route('admin.visitor-tracking.show', $session) }}" class="text-[#1E5FEA] hover:underline text-sm">View</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-400 dark:text-admin-text-secondary">No visitor sessions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sessions->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-admin-border">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

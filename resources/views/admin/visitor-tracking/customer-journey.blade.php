@extends('layouts.admin')

@section('title', 'Customer Journey')
@section('page-title', 'Customer Journey')

@section('content')
<div class="space-y-6">
    {{-- Back link --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.visitor-tracking.index') }}" class="text-sm text-[#1E5FEA] hover:underline">&larr; Back to Visitor Tracking</a>
    </div>

    {{-- Customer Info Card --}}
    <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#1E5FEA] to-purple-600 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                {{ strtoupper(substr($customer->name ?: $customer->email, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-admin-text-primary truncate">{{ $customer->name ?: $customer->email }}</h2>
                <p class="text-sm text-gray-500 dark:text-admin-text-secondary truncate">{{ $customer->email }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mt-6">
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Sessions</p>
                <p class="text-lg font-bold text-gray-900 dark:text-admin-text-primary">{{ $totalStats['sessions'] }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Page Views</p>
                <p class="text-lg font-bold text-gray-900 dark:text-admin-text-primary">{{ $totalStats['page_views'] }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Clicks</p>
                <p class="text-lg font-bold text-gray-900 dark:text-admin-text-primary">{{ $totalStats['clicks'] }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Total Time</p>
                <p class="text-lg font-bold text-gray-900 dark:text-admin-text-primary">
                    @if($totalStats['duration'] >= 3600)
                        {{ floor($totalStats['duration'] / 3600) }}h {{ floor(($totalStats['duration'] % 3600) / 60) }}m
                    @elseif($totalStats['duration'] >= 60)
                        {{ floor($totalStats['duration'] / 60) }}m {{ $totalStats['duration'] % 60 }}s
                    @else
                        {{ $totalStats['duration'] }}s
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">First Visit</p>
                <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary">{{ $totalStats['first_visit']?->format('M j, Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Last Active</p>
                <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary">{{ $totalStats['last_visit']?->format('M j, Y') }}</p>
            </div>
        </div>
    </div>

    @if($sessions->isEmpty())
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2a4 4 0 014-4h6m-6 0a4 4 0 01-4 4m4-4V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h6m0-18h6a2 2 0 012 2v0a2 2 0 01-2 2h-6m0 0V3"/></svg>
            <p class="text-sm text-gray-400 dark:text-admin-text-secondary">No tracking data for this customer yet.</p>
        </div>
    @else

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Sessions List --}}
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-admin-border">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">All Sessions ({{ $sessions->count() }})</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-admin-border/60 max-h-96 overflow-y-auto">
                @foreach($sessions as $session)
                    <a href="{{ route('admin.visitor-tracking.show', $session) }}" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary">
                                    {{ $session->started_at?->format('M j, Y g:i A') }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $session->ip ?? '—' }} · {{ trim(($session->city ?? '') . ', ' . ($session->country ?? ''), ', ') ?: 'Unknown' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0 ml-3">
                                <span class="text-xs text-gray-400">{{ $session->page_views_count }} views</span>
                                <span class="text-xs text-gray-400">{{ $session->clicks_count }} clicks</span>
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Click Summary Across All Sessions --}}
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-admin-border">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">Click Summary (All Sessions)</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-admin-border/60 max-h-96 overflow-y-auto">
                @forelse($clickSummary as $click)
                    <div class="px-4 py-2.5 flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-900 dark:text-admin-text-primary">{{ $click->element_label ?? $click->element_track_id ?? 'Unknown' }}</p>
                            @if($click->element_track_id && $click->element_track_id !== $click->element_label)
                                <p class="text-xs text-gray-400">{{ $click->element_track_id }}</p>
                            @endif
                        </div>
                        <span class="flex-shrink-0 ml-3 inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-[#1E5FEA]/10 text-[#1E5FEA] font-medium">{{ $click->cnt }}x</span>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-gray-400 dark:text-admin-text-secondary text-sm">No clicks recorded</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Full Journey Timeline --}}
    <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-admin-border">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">Full Journey Timeline ({{ $allEvents->count() }} events)</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-admin-border/60">
            @php
                $currentDate = '';
                $sessionMarkers = [];
                foreach ($sessions as $s) {
                    $sessionMarkers[$s->started_at->toDateTimeString()] = $s;
                }
            @endphp

            @foreach($allEvents as $event)
                @php
                    $eventDate = \Carbon\Carbon::parse($event->created_at)->format('M j, Y');
                    $showDateHeader = $eventDate !== $currentDate;
                    $currentDate = $eventDate;
                @endphp

                @if($showDateHeader)
                    <div class="px-4 py-2 bg-gray-50 dark:bg-white/5 sticky top-0 z-10">
                        <p class="text-xs font-semibold text-gray-500 dark:text-admin-text-secondary uppercase tracking-wide">{{ $eventDate }}</p>
                    </div>
                @endif

                <div class="px-4 py-3 flex items-start gap-3">
                    @php
                        $iconColor = match($event->event_type) {
                            'page_view' => 'bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400',
                            'click' => 'bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400',
                            'dwell' => 'bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400',
                            default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-admin-text-secondary',
                        };
                        $iconSvg = match($event->event_type) {
                            'page_view' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
                            'click' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11.24V7.5a2.5 2.5 0 0 1 5 0v3.74"/><path d="M14 11.24V5.5a2.5 2.5 0 0 1 5 0v6.74"/><path d="M19 12.24V8.5a2.5 2.5 0 0 1 5 0v6.26c0 4.24-3.39 7.74-7.63 7.74H12c-4.24 0-7.74-3.39-7.74-7.74V11.5a2.5 2.5 0 0 1 5 0"/></svg>',
                            'dwell' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
                            default => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>',
                        };
                    @endphp
                    <span class="flex-shrink-0 w-7 h-7 rounded-full {{ $iconColor }} flex items-center justify-center">{!! $iconSvg !!}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-medium text-gray-900 dark:text-admin-text-primary capitalize">{{ str_replace('_', ' ', $event->event_type) }}</span>
                            @if($event->element_label)
                                <span class="text-sm text-gray-600 dark:text-admin-text-secondary">— {{ $event->element_label }}</span>
                            @endif
                            @if($event->duration_seconds)
                                <span class="text-xs text-gray-400">{{ $event->duration_seconds }}s</span>
                            @endif
                        </div>
                        @if($event->url)
                            <p class="text-xs text-gray-400 truncate mt-0.5">{{ $event->url }}</p>
                        @endif
                        @if($event->element_track_id)
                            <p class="text-xs text-gray-400 mt-0.5">Track: {{ $event->element_track_id }}</p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ \Carbon\Carbon::parse($event->created_at)->format('g:i:s A') }}</span>
                </div>
            @endforeach
        </div>
    </div>

    @endif
</div>
@endsection

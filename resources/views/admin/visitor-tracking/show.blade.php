@extends('layouts.admin')

@section('title', 'Visitor Details')
@section('page-title', 'Visitor Details')

@section('content')
<div class="space-y-6">
    {{-- Back link --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.visitor-tracking.index') }}" class="text-sm text-[#1E5FEA] hover:underline">&larr; Back to Visitor Tracking</a>
            @if($session->customer_id)
                <a href="{{ route('admin.visitor-tracking.customer', $session->customer_id) }}" class="text-sm text-purple-500 hover:underline">View Full Customer Journey &rarr;</a>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.visitor-tracking.destroy', $session) }}" onsubmit="return confirm('Delete this visitor session and all events?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-500 hover:underline">Delete Session</button>
        </form>
    </div>

    {{-- Visitor Info Card --}}
    <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">IP Address</p>
                <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary">{{ $session->ip ?? 'Unknown' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Location</p>
                <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary">
                    @if($session->city || $session->country)
                        {{ trim(($session->city ?? '') . ', ' . ($session->country ?? ''), ', ') }}
                    @else
                        —
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Type</p>
                <p class="text-sm font-medium">
                    @if($session->is_guest)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-admin-text-secondary">Guest</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400">Customer</span>
                        @if($session->customer)
                            <span class="ml-2 text-gray-600 dark:text-admin-text-secondary">{{ $session->customer->email }}</span>
                        @endif
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Started</p>
                <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary">{{ $session->started_at?->format('M j, Y g:i A') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Last Activity</p>
                <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary">{{ $session->last_activity_at?->format('M j, Y g:i A') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Total Duration</p>
                <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary">
                    @if($session->duration_seconds >= 60)
                        {{ floor($session->duration_seconds / 60) }}m {{ $session->duration_seconds % 60 }}s
                    @else
                        {{ $session->duration_seconds }}s
                    @endif
                </p>
            </div>
        </div>

        @if($session->user_agent)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-admin-border">
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">User Agent</p>
                <p class="text-xs text-gray-600 dark:text-admin-text-secondary break-all">{{ $session->user_agent }}</p>
            </div>
        @endif

        @if($session->metadata && isset($session->metadata['referrer']))
            <div class="mt-2">
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mb-1">Referrer</p>
                <p class="text-xs text-gray-600 dark:text-admin-text-secondary break-all">{{ $session->metadata['referrer'] ?? '—' }}</p>
            </div>
        @endif
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-4 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ $session->page_views_count }}</p>
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-1">Page Views</p>
        </div>
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-4 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ $session->clicks_count }}</p>
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-1">Button Clicks</p>
        </div>
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border p-4 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-admin-text-primary">
                @if($session->duration_seconds >= 60)
                    {{ floor($session->duration_seconds / 60) }}m
                @else
                    {{ $session->duration_seconds }}s
                @endif
            </p>
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-1">Total Time</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Page Flow --}}
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-admin-border">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">Page Flow ({{ $pageFlow->count() }} pages)</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-admin-border/60 max-h-96 overflow-y-auto">
                @forelse($pageFlow as $i => $page)
                    <div class="px-4 py-2.5 flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#1E5FEA]/10 text-[#1E5FEA] text-xs flex items-center justify-center font-medium">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-gray-900 dark:text-admin-text-primary truncate">{{ $page->route_name ?? parse_url($page->url, PHP_URL_PATH) ?: $page->url }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $page->url }}</p>
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ \Carbon\Carbon::parse($page->created_at)->format('g:i:s A') }}</span>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-gray-400 dark:text-admin-text-secondary text-sm">No page views recorded</div>
                @endforelse
            </div>
        </div>

        {{-- Click Summary --}}
        <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-admin-border">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">Click Summary</h3>
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

    {{-- Full Event Timeline --}}
    <div class="bg-white dark:bg-admin-card rounded-xl border border-gray-200 dark:border-admin-border overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-admin-border">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">Event Timeline</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-admin-border/60">
            @forelse($events as $event)
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
                        <div class="flex items-center gap-2">
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
            @empty
                <div class="px-4 py-10 text-center text-gray-400 dark:text-admin-text-secondary text-sm">No events recorded</div>
            @endforelse
        </div>

        @if($events->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-admin-border">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

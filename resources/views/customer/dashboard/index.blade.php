@extends('layouts.customer')

@section('title', 'Dashboard')

@section('page-header')
    <x-page-header
        :eyebrow="'Overview · ' . $customerLocalTime->format('F Y')"
        :title="$greeting . ', ' . $firstName"
        :description="'You have ' . $activeCampaignsCount . ' active ' . ($activeCampaignsCount === 1 ? 'campaign' : 'campaigns') . ' right now.'"
    >
        <x-slot:actions>
        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
            <button type="button" @click="open = !open" class="mp-btn mp-btn--md mp-btn--primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New
                <svg class="w-4 h-4 -mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.right
                 class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl bg-white dark:bg-admin-card shadow-lg ring-1 ring-black/5 dark:ring-white/10 focus:outline-none z-30 py-1.5">
                <a href="{{ route('customer.campaigns.create') }}" data-track="campaign.create" class="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                    <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.2 8.4c.5.38.8.97.8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10a2 2 0 0 1 .8-1.6l8-6a2 2 0 0 1 2.4 0l8 6Z"/><path d="m22 10-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 10"/></svg>
                    Campaign
                </a>
                @if(\App\Models\Addon::isActive('cold-email-outreach'))
                    @customercan('outreach.access')
                    <a href="{{ route('customer.outreach.index') }}" data-track="outreach.view" class="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                        <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/></svg>
                        Outreach
                    </a>
                    @endcustomercan
                @endif
                <a href="{{ route('customer.warmups.index') }}" data-track="warmup.view" class="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                    <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/><circle cx="12" cy="12" r="3"/></svg>
                    Warmup
                </a>
                @customercan('automations.enabled')
                <a href="{{ route('customer.automations.create') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                    <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v12"/><path d="M18 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M6 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M15 6a9 9 0 0 0-9 9"/><path d="M18 15v6"/><path d="M21 18h-6"/></svg>
                    Automation
                </a>
                @endcustomercan
                <a href="{{ route('customer.lists.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                    <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Subscriber
                </a>
            </div>
        </div>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Pending account warning --}}
    @if(auth()->guard('customer')->user()->status === 'pending')
    <div class="flex gap-3 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl dark:bg-yellow-900/20 dark:border-yellow-700/40 dark:text-yellow-300">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <div>
            <p class="text-sm font-semibold">Account Pending Approval</p>
            <p class="text-sm mt-0.5 opacity-80">Your account is pending approval. Some features may be limited until an administrator approves it.</p>
        </div>
    </div>
    @endif

    {{-- ── Row 1: Plan usage ─────────────────────────────────────────────── --}}
    @php
        $usageCards = [
            [
                'label' => 'Lists',
                'count' => $emailListsCount,
                'limit' => $listLimit,
            ],
            [
                'label' => 'Campaigns',
                'count' => $campaignsCount,
                'limit' => $campaignLimit,
            ],
            [
                'label' => 'Subscribers',
                'count' => $subscribersCount,
                'limit' => $subscriberLimit,
            ],
            [
                'label' => 'Emails sent',
                'count' => $quotaUsed,
                'limit' => $quotaLimit,
            ],
        ];
    @endphp
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="mp-section-label">Plan usage</h2>
            <a href="{{ route('customer.usage.index') }}" class="text-xs font-medium text-admin-text-secondary transition hover:text-admin-text-primary">View usage</a>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($usageCards as $card)
                @php
                    $limit = $card['limit'];
                    $count = (int) $card['count'];
                    $pct = ($limit && (int) $limit > 0) ? min(100, round(($count / (int) $limit) * 100, 1)) : null;
                    $over = $limit && $count > (int) $limit;
                    $tone = $over ? 'danger' : (($pct !== null && $pct >= 80) ? 'warning' : 'success');
                @endphp
                <div class="mp-metric">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="mp-metric__label truncate">{{ $card['label'] }}</span>
                            @if($pct !== null)
                                <x-status-badge :tone="$tone">{{ $pct }}%</x-status-badge>
                            @endif
                        </div>
                        <span class="text-xs flex-shrink-0" style="color: var(--mp-text-subtle)">
                            @if($limit)
                                {{ number_format((int) $limit) }} max
                            @else
                                Unlimited
                            @endif
                        </span>
                    </div>
                    <p class="mp-metric__value">{{ number_format($count) }}</p>
                    @if($pct !== null)
                        <div class="mp-progress mt-3">
                            <div class="mp-progress__bar mp-progress__bar--{{ $tone }}" style="width: {{ $pct }}%"></div>
                        </div>
                    @else
                        <p class="mp-metric__meta mt-3">No limit set</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Row 2: KPI metrics ────────────────────────────────────────────── --}}
    @php
        $kpis = [
            [
                'label' => 'Total recipients',
                'value' => number_format($totalSent),
                'meta' => 'All-time sends',
                'metaClass' => '',
            ],
            [
                'label' => 'Avg. open rate',
                'value' => $avgOpenRate . '%',
                'meta' => $avgOpenRate >= 20 ? 'Above industry avg' : 'Industry avg ~20%',
                'metaClass' => $avgOpenRate >= 20 ? 'text-emerald-600 dark:text-emerald-400' : '',
            ],
            [
                'label' => 'Click-to-open',
                'value' => $clickToOpenRate . '%',
                'meta' => $clickToOpenRate >= 10 ? 'Good engagement' : ($clickToOpenRate > 0 ? 'Below avg (10%)' : 'No data yet'),
                'metaClass' => $clickToOpenRate >= 10 ? 'text-emerald-600 dark:text-emerald-400' : ($clickToOpenRate > 0 ? 'text-amber-600 dark:text-amber-400' : ''),
            ],
            [
                'label' => 'Unsubscribe rate',
                'value' => $unsubscribeRate . '%',
                'meta' => $unsubscribeRate < 0.5 ? 'Excellent' : ($unsubscribeRate < 1 ? 'Acceptable' : 'High — review content'),
                'metaClass' => $unsubscribeRate < 0.5 ? 'text-emerald-600 dark:text-emerald-400' : ($unsubscribeRate < 1 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400'),
            ],
        ];
    @endphp
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach($kpis as $kpi)
            <div class="mp-metric">
                <p class="mp-metric__label">{{ $kpi['label'] }}</p>
                <p class="mp-metric__value" data-stat-number>{{ $kpi['value'] }}</p>
                <p class="mp-metric__meta {{ $kpi['metaClass'] }}">{{ $kpi['meta'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── Row 3: Engagement chart + quick actions ───────────────────────── --}}
    @php
        $actions = [
            ['href' => route('customer.campaigns.create'), 'label' => 'New campaign',     'sub' => 'Create and send an email',     'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>'],
            ['href' => route('customer.lists.index'),     'label' => 'Import contacts',   'sub' => 'Upload your subscriber list',  'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 21a8 8 0 0 1 13.292-6"/><circle cx="10" cy="8" r="5"/><path d="M19 16v6"/><path d="M22 19h-6"/></svg>'],
            ['href' => route('customer.lists.create'),   'label' => 'Create list',        'sub' => 'Organize your contacts',       'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 5H3"/><path d="M11 12H3"/><path d="M16 19H3"/><path d="M18 9v6"/><path d="M21 12h-6"/></svg>'],
            ['href' => route('customer.analytics.index'),'label' => 'View reports',       'sub' => 'Check campaign performance',   'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21v-6"/><path d="M12 21V3"/><path d="M19 21V9"/></svg>'],
            ['href' => route('customer.warmups.index'),   'label' => 'Email warmups',     'sub' => 'Schedule gradual inbox ramp-up', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c1.2 2.4 2 4.2 2 6.5 0 2.2-1.2 3.7-2.4 5-1.4 1.5-2.6 3.1-2.6 5.5A5 5 0 0 0 14 23c3.9 0 7-3.1 7-7 0-4.4-2.9-7.6-5.5-10.5C13.9 3.7 13 2.8 12 2Z"/><path d="M12 14c.8-1.1 1.4-2.1 1.4-3.4"/></svg>'],
        ];
    @endphp
    <div class="grid grid-cols-1 gap-5 items-stretch lg:grid-cols-12">
        <div class="mp-card lg:col-span-8 xl:col-span-9 flex flex-col">
            <div class="mp-card__header">
                <div>
                    <h3 class="mp-card__title">Engagement over time</h3>
                    <p class="mp-card__desc">Daily opens and clicks for all campaigns — last 7 days.</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-admin-text-secondary">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-4 h-0.5 bg-blue-500 rounded-full"></span> Opens
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-4 h-0.5 rounded-full" style="border-top:2px dashed #10b981"></span> Clicks
                    </span>
                </div>
            </div>
            <div class="mp-card__body flex-1">
                <div class="relative h-56 sm:h-64">
                    <canvas id="engagementChart"></canvas>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 xl:col-span-3">
            <div class="mp-card flex h-full flex-col divide-y">
                @foreach($actions as $action)
                <a href="{{ $action['href'] }}" class="group flex items-center justify-between gap-3 p-4 transition-colors hover:bg-gray-50 dark:hover:bg-white/[0.03] flex-1">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg flex-shrink-0" style="background: var(--mp-surface-2); color: var(--mp-text-muted)">
                            {!! $action['icon'] !!}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-admin-text-primary">{{ $action['label'] }}</p>
                            <p class="text-xs text-admin-text-secondary mt-0.5 leading-snug">{{ $action['sub'] }}</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 flex-shrink-0 text-admin-text-secondary transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Row 4: Top campaigns + subscriber overview ────────────────────── --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

        {{-- Top Campaigns --}}
        <div class="mp-card">
            <div class="mp-card__header">
                <div>
                    <h3 class="mp-card__title">Top campaigns</h3>
                    <p class="mp-card__desc">Best performing by open rate</p>
                </div>
                <a href="{{ route('customer.campaigns.index') }}" class="text-xs font-medium text-admin-text-secondary hover:text-admin-text-primary">View all</a>
            </div>
            <div class="mp-card__body">
                @if($topCampaigns->isEmpty())
                    <div class="py-8 text-center text-sm text-admin-text-secondary">No completed campaigns yet</div>
                @else
                    <div class="space-y-4">
                        @foreach($topCampaigns as $i => $camp)
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-4 text-xs font-semibold text-admin-text-secondary text-right flex-shrink-0">{{ $i + 1 }}</span>
                                    <a href="{{ route('customer.campaigns.show', $camp) }}" class="text-sm font-medium text-admin-text-primary hover:underline truncate">{{ $camp->name }}</a>
                                </div>
                                <span class="flex-shrink-0 ml-3 text-sm font-semibold text-admin-text-primary" style="font-variant-numeric: tabular-nums">{{ number_format($camp->open_rate, 1) }}%</span>
                            </div>
                            <div class="ml-[26px] mp-progress">
                                <div class="mp-progress__bar mp-progress__bar--success" style="width:{{ min(100, $camp->open_rate) }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Subscriber Overview --}}
        <div class="mp-card">
            <div class="mp-card__header">
                <div>
                    <h3 class="mp-card__title">Subscriber overview</h3>
                    <p class="mp-card__desc">Contacts by status across all your lists.</p>
                </div>
            </div>
            <div class="mp-card__body">
                @php
                    $subTotal = max(1, $subscribersCount);
                    $subRows  = [
                        ['label' => 'Subscribed',   'count' => $subscribedCount,  'bar' => 'mp-progress__bar--success'],
                        ['label' => 'Unconfirmed',  'count' => $unconfirmedCount, 'bar' => 'mp-progress__bar--warning'],
                        ['label' => 'Blacklisted',  'count' => $blacklistedCount, 'bar' => 'mp-progress__bar--danger'],
                    ];
                @endphp
                <div class="space-y-4">
                    @foreach($subRows as $row)
                    @php $pct = $subTotal > 0 ? round(($row['count'] / $subTotal) * 100, 1) : 0; @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-24 flex-shrink-0 text-sm text-admin-text-secondary">{{ $row['label'] }}</span>
                        <div class="mp-progress flex-1">
                            <div class="mp-progress__bar {{ $row['bar'] }}" style="width:{{ $pct }}%"></div>
                        </div>
                        <span class="w-28 flex-shrink-0 text-right text-sm text-admin-text-primary" style="font-variant-numeric: tabular-nums">
                            {{ number_format($row['count']) }} <span class="text-admin-text-secondary">({{ $pct }}%)</span>
                        </span>
                    </div>
                    @endforeach
                </div>
                <div class="mt-5 flex items-center justify-between border-t pt-4" style="border-color: var(--mp-border)">
                    <span class="text-sm text-admin-text-secondary">Total contacts</span>
                    <span class="text-lg font-semibold text-admin-text-primary" style="font-variant-numeric: tabular-nums">{{ number_format($subscribersCount) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 5: Campaign performance + delivery breakdown ─────────────── --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-5">

        {{-- Campaign Performance table --}}
        <div class="mp-card lg:col-span-3">
            <div class="mp-card__header">
                <div>
                    <h3 class="mp-card__title">Campaign performance</h3>
                    <p class="mp-card__desc">Detailed metrics for recent sends.</p>
                </div>
                <a href="{{ route('customer.campaigns.index') }}" class="text-xs font-medium text-admin-text-secondary hover:text-admin-text-primary">View all</a>
            </div>
            @if($performanceCampaigns->isEmpty())
                <div class="mp-empty">
                    <div class="mp-empty__icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 21v-6M12 21V3M19 21V9"/></svg>
                    </div>
                    <p class="mp-empty__title">No campaigns yet</p>
                    <p class="mp-empty__desc">Your recent sends will appear here.</p>
                </div>
            @else
                <div class="mp-table-wrap">
                    <table class="mp-table">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th class="is-num">Sent</th>
                                <th class="is-num">Open rate</th>
                                <th class="is-num">Click rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($performanceCampaigns as $camp)
                            <tr>
                                <td>
                                    <a href="{{ route('customer.campaigns.show', $camp) }}" class="font-medium hover:underline line-clamp-1">{{ $camp->name }}</a>
                                    <span class="text-xs text-admin-text-secondary">
                                        {{ $camp->started_at ? $camp->started_at->format('M j, Y') : ucfirst($camp->status) }}
                                    </span>
                                </td>
                                <td class="is-num">{{ number_format($camp->sent_count) }}</td>
                                <td class="is-num">
                                    <span class="{{ $camp->open_rate >= 20 ? 'text-emerald-600 dark:text-emerald-400' : '' }}">{{ number_format($camp->open_rate, 1) }}%</span>
                                </td>
                                <td class="is-num">
                                    <span class="{{ $camp->click_rate >= 3 ? 'text-emerald-600 dark:text-emerald-400' : '' }}">{{ number_format($camp->click_rate, 1) }}%</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Delivery Breakdown + Sender Score --}}
        <div class="lg:col-span-2 flex flex-col gap-5">

            {{-- Delivery Breakdown --}}
            <div class="mp-card flex-1">
                <div class="mp-card__header">
                    <div>
                        <h3 class="mp-card__title">Delivery breakdown</h3>
                        <p class="mp-card__desc">Status of all emails sent.</p>
                    </div>
                </div>
                <div class="mp-card__body">
                    @php
                        $deliveryRate  = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0;
                        $bounceRatePct = $totalSent > 0 ? round(($totalBounced / $totalSent) * 100, 1) : 0;
                    @endphp
                    <div class="space-y-5">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div>
                                    <p class="text-sm font-medium text-admin-text-primary">Delivered</p>
                                    <p class="text-xs text-admin-text-secondary">{{ $deliveryRate }}% success rate</p>
                                </div>
                                <span class="text-base font-semibold text-admin-text-primary" style="font-variant-numeric: tabular-nums">{{ number_format($totalDelivered) }}</span>
                            </div>
                            <div class="mp-progress">
                                <div class="mp-progress__bar mp-progress__bar--info" style="width:{{ $deliveryRate }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div>
                                    <p class="text-sm font-medium text-admin-text-primary">Bounced</p>
                                    <p class="text-xs text-admin-text-secondary">{{ $bounceRatePct }}% bounce rate</p>
                                </div>
                                <span class="text-base font-semibold text-admin-text-primary" style="font-variant-numeric: tabular-nums">{{ number_format($totalBounced) }}</span>
                            </div>
                            <div class="mp-progress">
                                <div class="mp-progress__bar mp-progress__bar--warning" style="width:{{ min(100, $bounceRatePct * 20) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sender Score --}}
            @php
                $circumference = 2 * M_PI * 20;
                $dashOffset    = $circumference * (1 - $senderScore / 100);
                $scoreTone     = $senderScore >= 80 ? 'success' : ($senderScore >= 60 ? 'warning' : 'danger');
                $scoreLabel    = $senderScore >= 80 ? 'Excellent reputation' : ($senderScore >= 60 ? 'Fair reputation' : 'Needs improvement');
                $scoreLabelCls = $senderScore >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($senderScore >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
                $scoreColor    = $senderScore >= 80 ? '#16a34a' : ($senderScore >= 60 ? '#d97706' : '#dc2626');
            @endphp
            <div class="mp-card">
                <div class="mp-card__body flex items-center justify-between">
                    <div>
                        <p class="mp-section-label">Sender score</p>
                        <p class="mt-1 text-3xl font-semibold text-admin-text-primary" style="font-variant-numeric: tabular-nums">{{ $senderScore }}<span class="text-sm font-normal text-admin-text-secondary">/100</span></p>
                        <p class="mt-1 text-xs {{ $scoreLabelCls }}">{{ $scoreLabel }}</p>
                    </div>
                    <div class="relative w-14 h-14 flex-shrink-0">
                        <svg class="w-14 h-14 -rotate-90" viewBox="0 0 44 44">
                            <circle cx="22" cy="22" r="20" fill="none" stroke="currentColor" stroke-width="3.5" class="text-gray-100 dark:text-gray-700"/>
                            <circle cx="22" cy="22" r="20" fill="none" stroke="{{ $scoreColor }}" stroke-width="3.5" stroke-linecap="round"
                                stroke-dasharray="{{ round($circumference, 2) }}"
                                stroke-dashoffset="{{ round($dashOffset, 2) }}"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var _chart = null;

    function initChart() {
        var canvas = document.getElementById('engagementChart');
        if (!canvas) return;
        if (typeof Chart === 'undefined') return;
        if (_chart) { _chart.destroy(); _chart = null; }

        var isDark    = document.documentElement.classList.contains('dark');
        var gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        var tickColor = '#94a3b8';

        _chart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($engagementDates),
                datasets: [
                    {
                        label: 'Opens',
                        data: @json($engagementOpens),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.07)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#3b82f6',
                    },
                    {
                        label: 'Clicks',
                        data: @json($engagementClicks),
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 4],
                        fill: false,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#10b981',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: tickColor, font: { size: 11 } },
                        border: { display: false }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: { color: tickColor, font: { size: 11 } },
                        border: { display: false },
                        beginAtZero: true
                    }
                },
                interaction: { mode: 'nearest', axis: 'x', intersect: false }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initChart);
    document.addEventListener('turbo:load', initChart);
    document.addEventListener('turbo:render', function () {
        if (_chart) { _chart.destroy(); _chart = null; }
    });
})();
</script>
@endpush

@extends('layouts.admin')

@section('title', __('Dashboard'))
@section('page-title', __('Dashboard'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.dashboard', ['range' => '7d']) }}"
                class="px-3 py-2 rounded-lg text-sm border border-admin-border hover:bg-white/5 {{ ($range ?? '7d') === '7d' ? 'bg-white/10 text-admin-text-primary' : 'text-admin-text-secondary' }}"
            >
                {{ __('Last 7 Days') }}
            </a>
            <a
                href="{{ route('admin.dashboard', ['range' => '30d']) }}"
                class="px-3 py-2 rounded-lg text-sm border border-admin-border hover:bg-white/5 {{ ($range ?? '7d') === '30d' ? 'bg-white/10 text-admin-text-primary' : 'text-admin-text-secondary' }}"
            >
                {{ __('Last 30 Days') }}
            </a>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <input type="hidden" name="range" value="custom" />
            <div class="flex items-center gap-2">
                <div>
                    <label class="sr-only" for="from">{{ __('From') }}</label>
                    <input
                        id="from"
                        name="from"
                        type="date"
                        value="{{ optional($startDate ?? null)->toDateString() }}"
                        class="px-3 py-2 rounded-lg text-sm border border-admin-border bg-white/5 text-admin-text-primary"
                    />
                </div>
                <div>
                    <label class="sr-only" for="to">{{ __('To') }}</label>
                    <input
                        id="to"
                        name="to"
                        type="date"
                        value="{{ optional($endDate ?? null)->toDateString() }}"
                        class="px-3 py-2 rounded-lg text-sm border border-admin-border bg-white/5 text-admin-text-primary"
                    />
                </div>
                <button type="submit" class="px-3 py-2 rounded-lg text-sm bg-primary-500 text-white hover:bg-primary-600">
                    {{ __('Apply') }}
                </button>
            </div>
        </form>
    </div>

    @php
        $rangeLabel = __('Last 7 Days');
        if (($range ?? '7d') === '30d') {
            $rangeLabel = __('Last 30 Days');
        } elseif (($range ?? '') === 'custom') {
            $rangeLabel = __('Custom Range');
        }
    @endphp

    @php
        $kpiCards = [
            [
                'label' => __('Total User'),
                'value' => number_format($usersCount ?? 0),
                'footer' => $rangeLabel,
                'trend' => $kpiTrends['usersCount'] ?? ['percent' => 0.0, 'direction' => 'neutral'],
                'iconBg' => 'bg-blue-50 dark:bg-blue-900/20',
                'iconColor' => 'text-blue-600 dark:text-blue-400',
                'icon' => 'users',
            ],
            [
                'label' => __('Subscribers'),
                'value' => number_format($subscribersCount ?? 0),
                'footer' => $rangeLabel,
                'trend' => $kpiTrends['subscribersCount'] ?? ['percent' => 0.0, 'direction' => 'neutral'],
                'iconBg' => 'bg-emerald-50 dark:bg-emerald-900/20',
                'iconColor' => 'text-emerald-600 dark:text-emerald-400',
                'icon' => 'mail',
            ],
            [
                'label' => __('Earnings'),
                'value' => $currencySymbol . number_format((float) ($earnings ?? 0), 2),
                'footer' => $rangeLabel,
                'trend' => $kpiTrends['earnings'] ?? ['percent' => 0.0, 'direction' => 'neutral'],
                'iconBg' => 'bg-amber-50 dark:bg-amber-900/20',
                'iconColor' => 'text-amber-600 dark:text-amber-400',
                'icon' => 'wallet',
            ],
            [
                'label' => __('Campaigns Created'),
                'value' => number_format($campaignsCreated ?? 0),
                'footer' => $rangeLabel,
                'trend' => $kpiTrends['campaignsCreated'] ?? ['percent' => 0.0, 'direction' => 'neutral'],
                'iconBg' => 'bg-violet-50 dark:bg-violet-900/20',
                'iconColor' => 'text-violet-600 dark:text-violet-400',
                'icon' => 'megaphone',
            ],
            [
                'label' => __('Campaigns Ran'),
                'value' => number_format($campaignsRan ?? 0),
                'footer' => $rangeLabel,
                'trend' => $kpiTrends['campaignsRan'] ?? ['percent' => 0.0, 'direction' => 'neutral'],
                'iconBg' => 'bg-indigo-50 dark:bg-indigo-900/20',
                'iconColor' => 'text-indigo-600 dark:text-indigo-400',
                'icon' => 'activity',
            ],
            [
                'label' => __('Email Lists Created'),
                'value' => number_format($listsCreated ?? 0),
                'footer' => $rangeLabel,
                'trend' => $kpiTrends['listsCreated'] ?? ['percent' => 0.0, 'direction' => 'neutral'],
                'iconBg' => 'bg-cyan-50 dark:bg-cyan-900/20',
                'iconColor' => 'text-cyan-600 dark:text-cyan-400',
                'icon' => 'list',
            ],
            [
                'label' => __('Subscription Cancelled'),
                'value' => number_format($subscriptionsCancelled ?? 0),
                'footer' => $rangeLabel,
                'trend' => $kpiTrends['subscriptionsCancelled'] ?? ['percent' => 0.0, 'direction' => 'neutral'],
                'iconBg' => 'bg-rose-50 dark:bg-rose-900/20',
                'iconColor' => 'text-rose-600 dark:text-rose-400',
                'icon' => 'ban',
            ],
            [
                'label' => __('Templates Created'),
                'value' => number_format($templatesCreated ?? 0),
                'footer' => $rangeLabel,
                'trend' => $kpiTrends['templatesCreated'] ?? ['percent' => 0.0, 'direction' => 'neutral'],
                'iconBg' => 'bg-slate-50 dark:bg-slate-800/80',
                'iconColor' => 'text-slate-600 dark:text-slate-300',
                'icon' => 'layout',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($kpiCards as $card)
            <div class="bg-white dark:bg-admin-card rounded-lg p-3 border border-gray-100 dark:border-admin-border shadow-sm">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white leading-tight mt-0.5">{{ $card['value'] }}</p>
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">{{ $card['label'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $card['iconBg'] }}">
                        @if($card['icon'] === 'users')
                            <svg class="w-5 h-5 {{ $card['iconColor'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/></svg>
                        @elseif($card['icon'] === 'mail')
                            <svg class="w-5 h-5 {{ $card['iconColor'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        @elseif($card['icon'] === 'wallet')
                            <svg class="w-5 h-5 {{ $card['iconColor'] }}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-dollar-sign-icon lucide-badge-dollar-sign"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                        @elseif($card['icon'] === 'megaphone')
                            <svg class="w-5 h-5 {{ $card['iconColor'] }}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone-icon lucide-megaphone"><path d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/><path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"/><path d="M8 6v8"/></svg>
                        @elseif($card['icon'] === 'activity')
                            <svg class="w-5 h-5 {{ $card['iconColor'] }}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send-horizontal-icon lucide-send-horizontal"><path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"/><path d="M6 12h16"/></svg>
                        @elseif($card['icon'] === 'list')
                            <svg class="w-5 h-5 {{ $card['iconColor'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
                        @elseif($card['icon'] === 'ban')
                            <svg class="w-5 h-5 {{ $card['iconColor'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>
                        @else
                            <svg class="w-5 h-5 {{ $card['iconColor'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                        @endif
                    </div>
                </div>
                @php
                    $trend = $card['trend'] ?? ['percent' => 0.0, 'direction' => 'neutral'];
                    $trendDirection = $trend['direction'] ?? 'neutral';
                    $trendPercent = number_format((float) ($trend['percent'] ?? 0), 1) . '%';
                    $trendClass = $trendDirection === 'up'
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : ($trendDirection === 'down' ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400 dark:text-gray-500');
                @endphp
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $card['footer'] }}</p>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $trendClass }}">
                        {{ $trendPercent }}
                        @if($trendDirection === 'up')
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7 7 7" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18" />
                            </svg>
                        @elseif($trendDirection === 'down')
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7-7-7" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21V3" />
                            </svg>
                        @endif
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card :title="__('Earnings Data Chart')" :padding="false">
            <div class="px-6 py-4">
                <div class="w-full" style="height: 280px;">
                    <canvas id="earningsChart"></canvas>
                </div>
            </div>
        </x-card>

        @php
            $showAllUrl = route('admin.activities.index', request()->only(['range', 'from', 'to']));
        @endphp
        <x-card :padding="false">
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-admin-text-primary">{{ __('Recent Activity') }}</h3>
                    </div>
                    <a href="{{ $showAllUrl }}" class="text-sm text-primary-500 hover:text-primary-600">
                        {{ __('Show all activities') }}
                    </a>
                </div>
            </x-slot>

            <div class="divide-y divide-admin-border/10">
                @forelse(($recentActivity ?? []) as $item)
                    <div class="px-6 py-3 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-admin-text-primary">{{ $item['label'] ?? '' }}</p>
                            <p class="text-xs text-admin-text-secondary truncate">{{ $item['detail'] ?? '' }}</p>
                        </div>
                        <p class="text-xs text-admin-text-secondary whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($item['at'])->diffForHumans() }}
                        </p>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-admin-text-secondary">
                        <p>{{ __('No recent activity') }}</p>
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>

    <x-card :title="__('World Visitors')" :padding="false">
        <div class="px-6 py-4">
            <div class="relative">
                <div id="worldVisitorsMap" style="height: 360px;"></div>
                @if(empty($worldVisitors ?? []))
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <p class="text-sm text-admin-text-secondary bg-admin-sidebar/80 border border-admin-border rounded-lg px-3 py-2">
                            {{ __('No visitor data yet') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </x-card>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/js/jsvectormap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/maps/world.js"></script>
    <script>
        (function () {
            const labels = @json($earningsChartLabels ?? []);
            const values = @json($earningsChartValues ?? []);

            const destroyChart = () => {
                if (window.__earningsChart && typeof window.__earningsChart.destroy === 'function') {
                    window.__earningsChart.destroy();
                }
                window.__earningsChart = null;

                if (window.__earningsChartThemeObserver && typeof window.__earningsChartThemeObserver.disconnect === 'function') {
                    window.__earningsChartThemeObserver.disconnect();
                }
                window.__earningsChartThemeObserver = null;
            };

            const initChart = () => {
                destroyChart();

                const el = document.getElementById('earningsChart');
                if (!el) return;

                // Check if Chart.js is loaded, if not, retry after a short delay
                if (typeof Chart === 'undefined') {
                    setTimeout(initChart, 100);
                    return;
                }

                const ctx = el.getContext('2d');
                if (!ctx) return;

                const isDark = () => document.documentElement.classList.contains('dark');
                const theme = () => {
                    if (isDark()) {
                        return {
                            gridColor: 'rgba(255, 255, 255, 0.10)',
                            tickColor: 'rgba(255, 255, 255, 0.70)',
                            pointBorder: 'rgba(0,0,0,0.0)',
                        };
                    }
                    return {
                        gridColor: 'rgba(17, 24, 39, 0.10)',
                        tickColor: 'rgba(17, 24, 39, 0.70)',
                        pointBorder: 'rgba(255,255,255,0.95)',
                    };
                };

                const t0 = theme();
                window.__earningsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: @json(__('Earnings')),
                            data: values,
                            borderColor: '#1E5FEA',
                            backgroundColor: 'rgba(30, 95, 234, 0.15)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#1E5FEA',
                            pointBorderColor: t0.pointBorder,
                            pointBorderWidth: 2,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        const v = Number(ctx.raw || 0);
                                        return ' {{ $currencySymbol }}' + v.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: t0.gridColor },
                                ticks: { color: t0.tickColor }
                            },
                            y: {
                                grid: { color: t0.gridColor },
                                ticks: { color: t0.tickColor }
                            }
                        }
                    }
                });

                const applyTheme = () => {
                    const t = theme();
                    const c = window.__earningsChart;
                    if (!c) return;
                    c.options.scales.x.grid.color = t.gridColor;
                    c.options.scales.y.grid.color = t.gridColor;
                    c.options.scales.x.ticks.color = t.tickColor;
                    c.options.scales.y.ticks.color = t.tickColor;
                    if (c.data.datasets && c.data.datasets[0]) {
                        c.data.datasets[0].pointBorderColor = t.pointBorder;
                    }
                    c.update('none');
                };

                window.__earningsChartThemeObserver = new MutationObserver(applyTheme);
                window.__earningsChartThemeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            };

            document.addEventListener('turbo:before-cache', destroyChart);
            document.addEventListener('turbo:load', initChart);
            if (document.readyState !== 'loading') {
                initChart();
            }
        })();
    </script>

    <script>
        (function () {
            const visitors = @json($worldVisitors ?? []);
            const defaultVisitorLabel = @json(__('Visitor'));

            const destroyMap = () => {
                if (window.__worldMap && typeof window.__worldMap.destroy === 'function') {
                    window.__worldMap.destroy();
                }
                window.__worldMap = null;

                if (window.__worldMapThemeObserver && typeof window.__worldMapThemeObserver.disconnect === 'function') {
                    window.__worldMapThemeObserver.disconnect();
                }
                window.__worldMapThemeObserver = null;
            };

            const initMap = () => {
                destroyMap();

                const mapEl = document.getElementById('worldVisitorsMap');
                if (!mapEl) return;

                // Check if jsVectorMap is loaded, if not, retry after a short delay
                if (typeof jsVectorMap === 'undefined') {
                    setTimeout(initMap, 100);
                    return;
                }

                const isDark = () => document.documentElement.classList.contains('dark');
                const buildMarkers = () => {
                    if (!Array.isArray(visitors)) return [];
                    return visitors
                        .filter(v => v && v.lat != null && v.lng != null)
                        .map(v => ({
                            name: v.label || v.country || defaultVisitorLabel,
                            coords: [Number(v.lat), Number(v.lng)],
                            visitors: Number(v.visitors || 0),
                        }));
                };

                const t = () => {
                    if (isDark()) {
                        return {
                            regionFill: '#1b1b20',
                            regionStroke: 'rgba(255,255,255,0.10)',
                            markerFill: '#109489',
                            markerStroke: 'rgba(255,255,255,0.75)',
                        };
                    }
                    return {
                        regionFill: '#f8fafc',
                        regionStroke: 'rgba(17,24,39,0.12)',
                        markerFill: '#109489',
                        markerStroke: 'rgba(17,24,39,0.20)',
                    };
                };

                window.__worldMap = new jsVectorMap({
                    selector: '#worldVisitorsMap',
                    map: 'world',
                    zoomButtons: true,
                    markers: buildMarkers(),
                    markerStyle: {
                        initial: {
                            fill: t().markerFill,
                            stroke: t().markerStroke,
                            r: 5,
                        },
                        hover: {
                            r: 7,
                        }
                    },
                    regionStyle: {
                        initial: {
                            fill: t().regionFill,
                            stroke: t().regionStroke,
                            strokeWidth: 1,
                        },
                        hover: {
                            fill: '#e2e8f0',
                        }
                    },
                    onMarkerTooltipShow: function (tooltip, index) {
                        const marker = (window.__worldMap && window.__worldMap.markers && window.__worldMap.markers[index]) ? window.__worldMap.markers[index] : null;
                        if (!marker) return;
                        const data = marker.config || {};
                        const label = data.name || defaultVisitorLabel;
                        const count = data.visitors != null ? data.visitors : '';
                        tooltip.text(label + (count !== '' ? (' (' + count + ')') : ''));
                    },
                });

                const applyTheme = () => {
                    const map = window.__worldMap;
                    if (!map) return;
                    const tt = t();
                    map.setOptions({
                        regionStyle: {
                            initial: {
                                fill: tt.regionFill,
                                stroke: tt.regionStroke,
                                strokeWidth: 1,
                            }
                        },
                        markerStyle: {
                            initial: {
                                fill: tt.markerFill,
                                stroke: tt.markerStroke,
                                r: 5,
                            }
                        }
                    });
                };

                window.__worldMapThemeObserver = new MutationObserver(applyTheme);
                window.__worldMapThemeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            };

            document.addEventListener('turbo:before-cache', destroyMap);
            document.addEventListener('turbo:load', initMap);
            if (document.readyState !== 'loading') {
                initMap();
            }
        })();
    </script>
@endpush


@extends('layouts.customer')

@php
    $rangeLabel = ((int) $range === 30) ? 'Last 30 Days' : 'Last 7 Days';
@endphp

@section('title', 'Analytics')
@section('page-title', 'Email Analytics')

@section('page-title-details')
    Performance across every campaign you have sent.
@endsection

@section('page-actions')
    <div class="mp-toolbar">
        <x-ui.dropdown :label="$rangeLabel" icon="calendar" align="right">
            <x-slot:menu>
                <a href="{{ route('customer.analytics.index', ['range' => 7]) }}"
                   class="mp-menu-item {{ (int) $range === 7 ? 'is-active' : '' }}">Last 7 days</a>
                <a href="{{ route('customer.analytics.index', ['range' => 30]) }}"
                   class="mp-menu-item {{ (int) $range === 30 ? 'is-active' : '' }}">Last 30 days</a>
            </x-slot:menu>
        </x-ui.dropdown>

        <x-ui.button icon="refresh-cw" :icon-only="true" aria-label="Refresh" title="Refresh"
                     onclick="window.location.reload()" />

        <x-ui.button icon="download" :icon-only="true" aria-label="Export" title="Export" />
    </div>
@endsection

@section('content')
<div class="space-y-6">
    @php
        $deltas = $summary['deltas'] ?? [];

        $linePoints = collect($rateSeries ?? [])->map(function ($row, $i) {
            $x = (int) $i;
            $open = (float) ($row['open_rate'] ?? 0);
            $ct = (float) ($row['click_through'] ?? 0);
            $click = (float) ($row['click_rate'] ?? 0);
            return [
                'x' => $x,
                'day' => $row['day'] ?? null,
                'open_rate' => $open,
                'click_through' => $ct,
                'click_rate' => $click,
            ];
        })->values();

        $lineMax = max(1, (float) $linePoints->max(fn ($p) => max($p['open_rate'], $p['click_through'], $p['click_rate'])));

        $toSvgPath = function ($key) use ($linePoints, $lineMax) {
            if ($linePoints->count() <= 1) {
                return '';
            }
            $w = 100;
            $h = 100;
            $n = max(1, $linePoints->count() - 1);

            return $linePoints->map(function ($p, $idx) use ($key, $lineMax, $w, $h, $n) {
                $x = ($idx / $n) * $w;
                $y = $h - (($p[$key] / $lineMax) * $h);
                $cmd = $idx === 0 ? 'M' : 'L';
                return $cmd . number_format($x, 2, '.', '') . ' ' . number_format($y, 2, '.', '');
            })->implode(' ');
        };

        $pathOpen = $toSvgPath('open_rate');
        $pathClickThrough = $toSvgPath('click_through');
        $pathClick = $toSvgPath('click_rate');

        $deviceStats = collect($deviceStats ?? []);
        $deviceMax = max(1, (int) $deviceStats->max(fn ($r) => max((int) ($r['opened'] ?? 0), (int) ($r['clicked'] ?? 0))));
    @endphp

    {{-- Summary strip --}}
    <div class="mp-card">
        <div class="grid grid-cols-1 divide-y sm:grid-cols-2 sm:divide-y-0 sm:divide-x lg:grid-cols-4">
            @php
                $summaryCells = [
                    ['label' => 'Sent', 'value' => number_format((int) ($summary['total'] ?? 0)), 'meta' => $rangeLabel, 'delta' => $deltas['sent'] ?? null],
                    ['label' => 'Open rate', 'value' => number_format((float) ($summary['open_rate'] ?? 0), 2) . '%', 'meta' => number_format((int) ($summary['opened'] ?? 0)) . ' Opened', 'delta' => (float) ($deltas['open_rate'] ?? 0)],
                    ['label' => 'Click rate', 'value' => number_format((float) ($summary['click_rate'] ?? 0), 2) . '%', 'meta' => number_format((int) ($summary['clicked'] ?? 0)) . ' Clicked', 'delta' => (float) ($deltas['click_rate'] ?? 0)],
                    ['label' => 'Click through', 'value' => number_format((float) ($summary['click_through'] ?? 0), 2) . '%', 'meta' => number_format((int) ($summary['clicked'] ?? 0)) . ' Click Through', 'delta' => (float) ($deltas['click_through'] ?? 0)],
                ];
            @endphp

            @foreach($summaryCells as $cell)
                <div class="px-5 py-4">
                    <p class="mp-section-label">{{ $cell['label'] }}</p>
                    <p class="mt-1.5 text-2xl font-semibold text-admin-text-primary" style="font-variant-numeric: tabular-nums">{{ $cell['value'] }}</p>
                    <div class="mt-1 flex items-center gap-2 text-xs text-admin-text-secondary">
                        <span>{{ $cell['meta'] }}</span>
                        @if($cell['delta'] !== null)
                            <x-status-badge :tone="$cell['delta'] >= 0 ? 'success' : 'danger'">
                                {{ $cell['delta'] >= 0 ? '+' : '' }}{{ number_format((float) $cell['delta'], 2) }}%
                            </x-status-badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Delivery --}}
    <div class="flex items-center justify-between">
        <h2 class="mp-section-label">Delivery</h2>
        <button type="button" class="mp-btn mp-btn--ghost mp-btn--sm uppercase tracking-wide">Save report</button>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        @php
            $deliveryCards = [
                ['label' => 'Delivered rate', 'value' => number_format((float) ($summary['delivery_rate'] ?? 0), 2) . '%', 'meta' => number_format((int) ($summary['delivered'] ?? 0)) . ' Delivered', 'bar' => 'mp-progress__bar--success', 'pct' => (float) ($summary['delivery_rate'] ?? 0)],
                ['label' => 'Hard bounce rate', 'value' => number_format((float) ($summary['hard_bounce_rate'] ?? 0), 2) . '%', 'meta' => number_format((int) ($summary['hard_bounced'] ?? 0)) . ' Hard bounced', 'bar' => 'mp-progress__bar--danger', 'pct' => (float) ($summary['hard_bounce_rate'] ?? 0)],
                ['label' => 'Unsubscribed rate', 'value' => number_format((float) ($summary['unsubscribe_rate'] ?? 0), 2) . '%', 'meta' => number_format((int) ($summary['unsubscribed'] ?? 0)) . ' Unsubscribed', 'bar' => 'mp-progress__bar--warning', 'pct' => (float) ($summary['unsubscribe_rate'] ?? 0)],
                ['label' => 'Spam report rate', 'value' => number_format((float) ($summary['spam_rate'] ?? 0), 2) . '%', 'meta' => number_format((int) ($summary['complained'] ?? 0)) . ' Reported', 'bar' => 'mp-progress__bar--danger', 'pct' => (float) ($summary['spam_rate'] ?? 0)],
            ];
        @endphp
        @foreach($deliveryCards as $card)
            <div class="mp-metric">
                <p class="mp-section-label">{{ $card['label'] }}</p>
                <p class="mt-1.5 text-2xl font-semibold text-admin-text-primary" style="font-variant-numeric: tabular-nums">{{ $card['value'] }}</p>
                <p class="mp-metric__meta">{{ $card['meta'] }}</p>
                <div class="mp-progress mt-4">
                    <div class="mp-progress__bar {{ $card['bar'] }}" style="width: {{ min(100, max(0, $card['pct'])) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="mp-card">
            <div class="mp-card__header">
                <div>
                    <h3 class="mp-card__title">Email data chart</h3>
                    <p class="mp-card__desc">Rates over the selected period.</p>
                </div>
                <div class="hidden items-center gap-4 text-xs text-admin-text-secondary sm:flex">
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full" style="background:#8b5cf6"></span> Click through</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full" style="background:#6366f1"></span> Open</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full" style="background:#0ea5e9"></span> Click</span>
                </div>
            </div>
            <div class="mp-card__body">
                <div class="mt-1" data-analytics-line-root data-points='@json($linePoints)'>
                    <div class="relative w-full" style="height: 260px;">
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 h-full w-full">
                            <path d="{{ $pathClickThrough }}" fill="none" stroke="rgb(139 92 246)" stroke-width="1.2" vector-effect="non-scaling-stroke" />
                            <path d="{{ $pathOpen }}" fill="none" stroke="rgb(99 102 241)" stroke-width="1.2" vector-effect="non-scaling-stroke" />
                            <path d="{{ $pathClick }}" fill="none" stroke="rgb(14 165 233)" stroke-width="1.2" vector-effect="non-scaling-stroke" />
                        </svg>

                        <div data-analytics-tooltip class="hidden absolute z-10 rounded-md border px-3 py-2 text-xs text-admin-text-primary shadow" style="border-color: var(--mp-border); background: var(--mp-surface)">
                            <div class="font-medium" data-analytics-tooltip-day></div>
                            <div class="mt-1 text-admin-text-secondary">
                                <span class="inline-block w-28">Open rate</span>
                                <span data-analytics-tooltip-open></span>
                            </div>
                            <div class="text-admin-text-secondary">
                                <span class="inline-block w-28">Click rate</span>
                                <span data-analytics-tooltip-click></span>
                            </div>
                            <div class="text-admin-text-secondary">
                                <span class="inline-block w-28">Click through</span>
                                <span data-analytics-tooltip-ct></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-4 gap-2 text-[11px] text-admin-text-secondary">
                        @foreach(collect($rateSeries ?? [])->take(4) as $i => $row)
                            <div class="truncate">
                                {{ \Carbon\Carbon::parse($row['day'])->format('M d') }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="mp-card">
            <div class="mp-card__header">
                <div>
                    <h3 class="mp-card__title">Performance by device</h3>
                    <p class="mp-card__desc">Opens and clicks split by device type.</p>
                </div>
            </div>
            <div class="mp-card__body">
                <div class="space-y-4">
                    @foreach($deviceStats as $row)
                        @php
                            $openedCount = (int) ($row['opened'] ?? 0);
                            $clickedCount = (int) ($row['clicked'] ?? 0);
                            $openedW = round(($openedCount / $deviceMax) * 100, 2);
                            $clickedW = round(($clickedCount / $deviceMax) * 100, 2);
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs">
                                <div class="text-admin-text-primary">{{ $row['label'] }}</div>
                                <div class="text-admin-text-secondary">{{ $openedCount }} Opened · {{ $clickedCount }} Clicks</div>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="mp-progress flex-1">
                                    <div class="mp-progress__bar" style="width: {{ $openedW }}%"></div>
                                </div>
                                <div class="mp-progress flex-1">
                                    <div class="mp-progress__bar" style="width: {{ $clickedW }}%; opacity: 0.45"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if($deviceStats->isEmpty())
                        <p class="py-6 text-center text-sm text-admin-text-secondary">No device data for this period.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- All email performance table --}}
    <div class="mp-card" x-data="{
        q: '',
        view: 'all',
        filters: { clicks: false, delivered: false },
        apply() {
            if (! this.$refs.tbody) return;
            const rows = Array.from(this.$refs.tbody.querySelectorAll('tr[data-row]'));
            const q = this.q.trim().toLowerCase();
            rows.forEach((row) => {
                const name = (row.dataset.name || '').toLowerCase();
                const sent = parseFloat(row.dataset.sent || '0');
                const click = parseFloat(row.dataset.click || '0');
                const delivered = parseFloat(row.dataset.delivered || '0');
                let show = name.includes(q);
                if (this.view === 'sent') show = show && sent > 0;
                if (this.filters.clicks) show = show && click > 0;
                if (this.filters.delivered) show = show && delivered >= 95;
                row.style.display = show ? '' : 'none';
            });
        },
        sortBy(key) {
            if (! this.$refs.tbody) return;
            const tbody = this.$refs.tbody;
            const rows = Array.from(tbody.querySelectorAll('tr[data-row]'));
            rows.sort((a, b) => parseFloat(b.dataset[key] || '0') - parseFloat(a.dataset[key] || '0'));
            rows.forEach((row) => tbody.appendChild(row));
        }
    }" x-effect="apply()">
        <div class="mp-card__header">
            <div>
                <h3 class="mp-card__title">All email performance</h3>
                <p class="mp-card__desc">Every campaign in the selected period.</p>
            </div>
            <x-ui.button icon="download" variant="primary" size="sm">Export</x-ui.button>
        </div>

        <div class="mp-toolbar px-5 py-3" style="border-bottom: 1px solid var(--mp-border)">
            <x-ui.segmented label="View">
                <x-ui.segment x-bind:aria-pressed="view === 'all' ? 'true' : 'false'" x-on:click="view = 'all'">All</x-ui.segment>
                <x-ui.segment x-bind:aria-pressed="view === 'sent' ? 'true' : 'false'" x-on:click="view = 'sent'">Has sends</x-ui.segment>
            </x-ui.segmented>

            <x-ui.dropdown label="Group by">
                <x-slot:menu>
                    <button type="button" class="mp-menu-item" x-on:click="sortBy('sent'); open = false">Most sent</button>
                    <button type="button" class="mp-menu-item" x-on:click="sortBy('click'); open = false">Highest click-through</button>
                    <button type="button" class="mp-menu-item" x-on:click="sortBy('delivered'); open = false">Best delivered rate</button>
                </x-slot:menu>
            </x-ui.dropdown>

            <x-ui.dropdown label="Filter" icon="filter">
                <x-slot:menu>
                    <div class="flex items-center justify-between gap-3 px-2.5 py-1.5">
                        <span class="text-sm text-admin-text-primary">Only with clicks</span>
                        <x-ui.switch size="sm" x-model="filters.clicks" />
                    </div>
                    <div class="flex items-center justify-between gap-3 px-2.5 py-1.5">
                        <span class="text-sm text-admin-text-primary">Delivered 95%+</span>
                        <x-ui.switch size="sm" x-model="filters.delivered" />
                    </div>
                </x-slot:menu>
            </x-ui.dropdown>

            <div class="ml-auto w-40 sm:w-48">
                <x-ui.input icon="search" placeholder="Search" x-model="q" aria-label="Search campaigns" />
            </div>
        </div>

        <div class="mp-table-wrap">
            <table class="mp-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Publish date</th>
                        <th class="is-num">Sent</th>
                        <th class="is-num">Click-through</th>
                        <th class="is-num">Delivered</th>
                        <th class="is-num">Unsubscribed</th>
                        <th class="is-num">Spam</th>
                    </tr>
                </thead>
                <tbody x-ref="tbody">
                    @forelse($campaigns as $c)
                        <tr
                            data-row
                            data-name="{{ $c['name'] }}"
                            data-sent="{{ (int) ($c['sent'] ?? 0) }}"
                            data-click="{{ (float) ($c['click_through'] ?? 0) }}"
                            data-delivered="{{ (float) ($c['delivered_rate'] ?? 0) }}"
                        >
                            <td class="font-medium">{{ $c['name'] }}</td>
                            <td class="text-admin-text-secondary">
                                @php $publishedAt = $c['published_at'] ?? null; @endphp
                                {{ $publishedAt ? \Carbon\Carbon::parse($publishedAt)->format('n/j/Y') : '—' }}
                            </td>
                            <td class="is-num">{{ number_format((int) ($c['sent'] ?? 0)) }}</td>
                            <td class="is-num">{{ number_format((float) ($c['click_through'] ?? 0), 2) }}%</td>
                            <td class="is-num">{{ number_format((float) ($c['delivered_rate'] ?? 0), 2) }}%</td>
                            <td class="is-num">{{ number_format((float) ($c['unsubscribe_rate'] ?? 0), 2) }}%</td>
                            <td class="is-num">{{ number_format((float) ($c['spam_rate'] ?? 0), 2) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="mp-empty">
                                    <div class="mp-empty__icon">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7l9 6 9-6M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2Z"/></svg>
                                    </div>
                                    <p class="mp-empty__title">No campaigns found</p>
                                    <p class="mp-empty__desc">Try a different period or domain.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
            <div class="mp-card__footer">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const root = document.querySelector('[data-analytics-line-root]');
  if (!root) return;

  const pointsRaw = root.getAttribute('data-points');
  if (!pointsRaw) return;

  let points = [];
  try { points = JSON.parse(pointsRaw) || []; } catch (e) { points = []; }
  if (!Array.isArray(points) || points.length === 0) return;

  const tooltip = root.querySelector('[data-analytics-tooltip]');
  const dayEl = root.querySelector('[data-analytics-tooltip-day]');
  const openEl = root.querySelector('[data-analytics-tooltip-open]');
  const clickEl = root.querySelector('[data-analytics-tooltip-click]');
  const ctEl = root.querySelector('[data-analytics-tooltip-ct]');

  const container = root.querySelector('.relative');
  if (!container || !tooltip) return;

  function showTooltip(p, clientX) {
    if (!p) return;
    dayEl.textContent = p.day || '';
    openEl.textContent = `${Number(p.open_rate || 0).toFixed(2)}%`;
    clickEl.textContent = `${Number(p.click_rate || 0).toFixed(2)}%`;
    ctEl.textContent = `${Number(p.click_through || 0).toFixed(2)}%`;

    const rect = container.getBoundingClientRect();
    const x = clientX - rect.left;
    tooltip.style.left = `${Math.min(Math.max(8, x + 12), rect.width - 170)}px`;
    tooltip.style.top = '8px';
    tooltip.classList.remove('hidden');
  }

  function hideTooltip() {
    tooltip.classList.add('hidden');
  }

  container.addEventListener('mousemove', (e) => {
    const rect = container.getBoundingClientRect();
    const t = (e.clientX - rect.left) / rect.width;
    const idx = Math.min(points.length - 1, Math.max(0, Math.round(t * (points.length - 1))));
    showTooltip(points[idx], e.clientX);
  });

  container.addEventListener('mouseleave', hideTooltip);
})();
</script>
@endpush

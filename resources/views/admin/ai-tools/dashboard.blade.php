@extends('layouts.admin')

@section('title', __('AI Dashboard'))
@section('page-title', __('AI Dashboard'))

@section('content')
<div x-data="{
    activeTab: (() => {
        const params = new URLSearchParams(window.location.search);
        return params.get('tab') || (params.get('generation_id') ? 'generations' : 'cost');
    })(),
    expandedGeneration: null,
    setTab(tab) {
        this.activeTab = tab;
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }
}" class="space-y-4">
    {{-- Tabs --}}
    <div class="flex items-center gap-1 border-b border-admin-border pb-0">
        <button
            type="button"
            @click="setTab('cost')"
            :class="activeTab === 'cost' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-4 py-2 text-sm font-medium transition-colors"
        >
            {{ __('Cost') }}
        </button>
        <button
            type="button"
            @click="setTab('builder')"
            :class="activeTab === 'builder' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-4 py-2 text-sm font-medium transition-colors"
        >
            {{ __('Builder Models') }}
        </button>
        <button
            type="button"
            @click="setTab('generations')"
            :class="activeTab === 'generations' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-4 py-2 text-sm font-medium transition-colors"
        >
            {{ __('Generations') }}
        </button>
    </div>

    {{-- Cost Tab --}}
    <div x-show="activeTab === 'cost'" x-cloak class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.ai-tools.dashboard', ['range' => '7d']) }}"
                    class="px-3 py-2 rounded-lg text-sm border border-admin-border hover:bg-white/5 {{ ($range ?? '7d') === '7d' ? 'bg-white/10 text-admin-text-primary' : 'text-admin-text-secondary' }}"
                >
                    {{ __('Last 7 Days') }}
                </a>
                <a
                    href="{{ route('admin.ai-tools.dashboard', ['range' => '30d']) }}"
                    class="px-3 py-2 rounded-lg text-sm border border-admin-border hover:bg-white/5 {{ ($range ?? '7d') === '30d' ? 'bg-white/10 text-admin-text-primary' : 'text-admin-text-secondary' }}"
                >
                    {{ __('Last 30 Days') }}
                </a>
            </div>

            <form method="GET" action="{{ route('admin.ai-tools.dashboard') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="hidden" name="range" value="custom" />
                <div class="flex items-center gap-2">
                    <div>
                        <label class="sr-only" for="from">{{ __('From') }}</label>
                        <input id="from" name="from" type="date" value="{{ optional($startDate ?? null)->toDateString() }}" class="px-3 py-2 rounded-lg text-sm border border-admin-border bg-white/5 text-admin-text-primary" />
                    </div>
                    <div>
                        <label class="sr-only" for="to">{{ __('To') }}</label>
                        <input id="to" name="to" type="date" value="{{ optional($endDate ?? null)->toDateString() }}" class="px-3 py-2 rounded-lg text-sm border border-admin-border bg-white/5 text-admin-text-primary" />
                    </div>
                    <button type="submit" class="px-3 py-2 rounded-lg text-sm bg-primary-500 text-white hover:bg-primary-600">{{ __('Apply') }}</button>
                </div>
            </form>
        </div>

        <x-card>
            <div class="flex flex-col gap-1">
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Admin AI usage aggregated from successful AI generations that used admin keys.') }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-500">{{ __('Range:') }} {{ $rangeLabel ?? __('This month') }}</div>
            </div>
        </x-card>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-card>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total tokens used') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format((int) ($totalTokensUsed ?? 0)) }}</div>

            @php
                $tl = $totalLimit;
                $tp = $totalPercent;
            @endphp

            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                    <span>{{ __('Monthly limit') }}</span>
                    <span>
                        @if(is_numeric($tl))
                            {{ number_format((int) $tl) }}
                        @else
                            {{ __('Unlimited') }}
                        @endif
                    </span>
                </div>

                <div class="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                    @if(is_numeric($tp))
                        <div class="h-full bg-primary-500" style="width: {{ (float) $tp }}%"></div>
                    @else
                        <div class="h-full bg-primary-500" style="width: 0%"></div>
                    @endif
                </div>

                <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                    @if(is_numeric($tp))
                        {{ number_format((float) $tp, 1) }}%
                    @else
                        {{ __('No limit configured') }}
                    @endif
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Tokens used today') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format((int) ($totalTokensUsedToday ?? 0)) }}</div>

            @php
                $dl = $totalDailyLimit;
                $dp = $totalDailyPercent;
            @endphp

            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                    <span>{{ __('Daily limit') }}</span>
                    <span>
                        @if(is_numeric($dl))
                            {{ number_format((int) $dl) }}
                        @else
                            {{ __('Unlimited') }}
                        @endif
                    </span>
                </div>

                <div class="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                    @if(is_numeric($dp))
                        <div class="h-full bg-primary-500" style="width: {{ (float) $dp }}%"></div>
                    @else
                        <div class="h-full bg-primary-500" style="width: 0%"></div>
                    @endif
                </div>

                <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                    @if(is_numeric($dp))
                        {{ number_format((float) $dp, 1) }}%
                    @else
                        {{ __('No limit configured') }}
                    @endif
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Estimated total cost') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                ${{ number_format(((int) ($totalCostCents ?? 0)) / 100, 2) }}
            </div>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                {{ __('Based on the configured cost per 1M tokens in Settings > AI.') }}
            </div>
        </x-card>

        <x-card>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Providers tracked') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ is_array($providerStats ?? null) ? count($providerStats) : 0 }}</div>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                {{ implode(', ', array_values(\App\Services\AI\AiProviderRegistry::providerLabels())) }}
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Usage by provider') }}</div>

            <div class="mt-4 space-y-4">
                @foreach(($providerStats ?? []) as $key => $stat)
                    @php
                        $used = (int) ($stat['tokens_used'] ?? 0);
                        $limit = (int) ($stat['token_limit'] ?? 0);
                        $percent = $stat['percent'] ?? null;
                        $estimatedCostCents = (int) ($stat['estimated_cost_cents'] ?? 0);

                        $daily = is_array($providerDailyStats ?? null) ? ($providerDailyStats[$key] ?? null) : null;
                        $dailyUsed = (int) (($daily['tokens_used'] ?? null) ?? 0);
                        $dailyLimit = (int) (($daily['token_limit'] ?? null) ?? 0);
                    @endphp

                    <div>
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $stat['label'] ?? $key }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                {{ number_format($used) }}
                                /
                                {{ $limit > 0 ? number_format($limit) : __('Unlimited') }}
                                ·
                                ${{ number_format($estimatedCostCents / 100, 2) }}
                            </div>
                        </div>

                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                            {{ __('Today:') }}
                            {{ number_format($dailyUsed) }}
                            /
                            {{ $dailyLimit > 0 ? number_format($dailyLimit) : __('Unlimited') }}
                        </div>

                        <div class="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                            @if(is_numeric($percent))
                                <div class="h-full bg-primary-500" style="width: {{ (float) $percent }}%"></div>
                            @else
                                <div class="h-full bg-primary-500" style="width: 0%"></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Breakdown by provider / model') }}</div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Provider') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Model') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Tokens') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Est. Cost') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse(($breakdownRows ?? []) as $row)
                            @php
                                $tokens = (int) ($row['tokens'] ?? 0);
                                $costCents = (int) ($row['estimated_cost_cents'] ?? 0);
                            @endphp
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $row['provider_label'] ?? ($row['provider'] ?? '') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $row['model'] ?? __('Default') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">{{ number_format($tokens) }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">${{ number_format($costCents / 100, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400" colspan="4">{{ __('No AI usage yet for the selected range.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Tokens by provider') }}</div>
            <div class="mt-4" style="height: 320px;">
                <canvas id="aiProviderChart"></canvas>
            </div>
        </x-card>

        <x-card>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Tokens by provider / model') }}</div>
            <div class="mt-4" style="height: 320px;">
                <canvas id="aiProviderModelChart"></canvas>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Estimated cost by provider') }}</div>
            <div class="mt-4" style="height: 320px;">
                <canvas id="aiProviderCostChart"></canvas>
            </div>
        </x-card>

        <x-card>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Estimated cost by provider / model') }}</div>
            <div class="mt-4" style="height: 320px;">
                <canvas id="aiProviderModelCostChart"></canvas>
            </div>
        </x-card>
    </div>
    </div>{{-- /Cost Tab --}}

    {{-- Builder Models Tab --}}
    <div x-show="activeTab === 'builder'" x-cloak class="space-y-6">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('AI Builder Model Defaults') }}</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Used when "Auto" is selected in the builder') }}</span>
            </div>

            <form method="POST" action="{{ route('admin.ai-tools.builder-defaults.update') }}" class="space-y-3">
                @csrf
                @method('PUT')

                @php
                    $stages = [
                        'enhancer' => __('Prompt Enhancer'),
                        'orchestrator' => __('Orchestrator'),
                        'worker' => __('Component Worker'),
                        'iteration' => __('Iteration Handler'),
                    ];
                    $providers = \App\Services\AI\AiProviderRegistry::providers();
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($stages as $key => $label)
                        @php
                            $stageCost = $builderStageCosts[$key] ?? null;
                            $inputCost = $stageCost['input_cents_per_1m'] ?? 0;
                            $outputCost = $stageCost['output_cents_per_1m'] ?? 0;
                        @endphp
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</label>
                            <select
                                name="{{ $key }}"
                                class="block w-full rounded-lg border border-admin-border bg-white/5 px-3 py-2 text-sm text-admin-text-primary"
                            >
                                @foreach ($providers as $providerKey => $providerConfig)
                                    <optgroup label="{{ $providerConfig['label'] }}">
                                        @foreach ($providerConfig['models'] ?? [] as $modelValue)
                                            <option value="{{ $modelValue }}" {{ ($builderDefaults[$key] ?? '') === $modelValue ? 'selected' : '' }}>
                                                {{ $modelValue }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>${{ number_format($inputCost / 100, 2) }} {{ __('in') }} / ${{ number_format($outputCost / 100, 2) }} {{ __('out') }} {{ __('per 1M') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-primary-500 text-white hover:bg-primary-600">
                        {{ __('Save Defaults') }}
                    </button>
                </div>
            </form>
        </x-card>
    </div>{{-- /Builder Models Tab --}}

    {{-- Generations Tab --}}
    <div x-show="activeTab === 'generations'" x-cloak class="space-y-6">
        @php
            $generationsList = $generations ?? collect();
            $detail = $generationDetail ?? null;
        @endphp

        @if($detail)
            <div class="mb-4">
                <a href="{{ route('admin.ai-tools.dashboard', array_merge(request()->except('generation_id'), ['tab' => 'generations'])) }}" class="text-sm text-primary-600 hover:text-primary-500">
                    ← {{ __('Back to generations list') }}
                </a>
            </div>

            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Generation Details') }}</h3>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            #{{ $detail->id }} · {{ $detail->created_at->format('Y-m-d H:i') }}
                            · {{ $detail->type === 'full_page' ? __('Full Page') : ($detail->type === 'section' ? __('Section') : ($detail->type ?? '-')) }}
                            · {{ $detail->provider ?? '-' }} / {{ $detail->model ?? '-' }}
                            · {{ number_format((int) $detail->steps->sum('tokens_used')) }} {{ __('tokens') }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Total cost') }}</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            ${{ number_format($detail->steps->sum('cost_cents') / 100, 3) }}
                        </div>
                    </div>
                </div>

                @if(!empty($detail->prompt) && $detail->prompt !== '(no prompt)')
                    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Prompt') }}</div>
                        <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ $detail->prompt }}</div>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Step') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Provider') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Model') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Tokens') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Est. Cost') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Result') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($detail->steps as $step)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $step->step_name }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $step->provider ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $step->model ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">{{ number_format((int) $step->tokens_used) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">${{ number_format($step->cost_cents / 100, 3) }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $step->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : '' }}
                                            {{ $step->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : '' }}
                                            {{ $step->status === 'pending' ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' : '' }}
                                        ">
                                            {{ ucfirst($step->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 max-w-xs">
                                        @if($step->status === 'failed' && !empty($step->error_message))
                                            <span class="text-red-600 dark:text-red-400 text-xs">{{ Str::limit($step->error_message, 120) }}</span>
                                        @elseif($step->status === 'completed')
                                            <span class="text-green-600 dark:text-green-400 text-xs">{{ __('Completed successfully') }}</span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400" colspan="7">{{ __('No steps recorded for this generation.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        @else
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('AI Generations') }}</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Total:') }} ${{ number_format(($generationsList->sum(fn($g) => $g->steps->sum('cost_cents')) ?? 0) / 100, 3) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('ID') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Type') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Provider') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Model') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Tokens') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Cost') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($generationsList as $gen)
                                @php
                                    $genCost = $gen->steps->sum('cost_cents');
                                    $genTokens = $gen->steps->sum('tokens_used');
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">#{{ $gen->id }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $gen->type === 'full_page' ? __('Full Page') : ($gen->type === 'section' ? __('Section') : ($gen->type ?? '-')) }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $gen->provider ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $gen->model ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">{{ number_format((int) $genTokens) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">${{ number_format($genCost / 100, 3) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $gen->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <a href="{{ route('admin.ai-tools.dashboard', array_merge(request()->all(), ['generation_id' => $gen->id, 'tab' => 'generations'])) }}" class="text-primary-600 hover:text-primary-500 text-xs font-medium">
                                            {{ __('View') }} →
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400" colspan="8">{{ __('No generations yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($generationsList instanceof \Illuminate\Contracts\Pagination\Paginator && $generationsList->hasPages())
                    <div class="mt-4">
                        {{ $generationsList->links() }}
                    </div>
                @endif
            </x-card>
        @endif
    </div>{{-- /Generations Tab --}}
</div>{{-- /main container --}}
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const providerStats = @json($providerStats ?? []);
            const breakdownRows = @json($breakdownRows ?? []);

            const isDark = () => document.documentElement.classList.contains('dark');
            const theme = () => {
                if (isDark()) {
                    return {
                        gridColor: 'rgba(255, 255, 255, 0.10)',
                        tickColor: 'rgba(255, 255, 255, 0.70)',
                    };
                }
                return {
                    gridColor: 'rgba(17, 24, 39, 0.10)',
                    tickColor: 'rgba(17, 24, 39, 0.70)',
                };
            };

            const providerLabels = [];
            const providerTokens = [];
            const providerCost = [];

            Object.keys(providerStats || {}).forEach(function (key) {
                const stat = providerStats[key] || {};
                providerLabels.push(stat.label || key);
                providerTokens.push(Number(stat.tokens_used || 0));
                providerCost.push(Number(stat.estimated_cost_cents || 0) / 100);
            });

            const providerChartEl = document.getElementById('aiProviderChart');
            if (providerChartEl && typeof Chart !== 'undefined') {
                const ctx = providerChartEl.getContext('2d');
                if (ctx) {
                    const t0 = theme();
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: providerLabels,
                            datasets: [{
                                label: @json(__('Tokens')),
                                data: providerTokens,
                                backgroundColor: 'rgba(30, 95, 234, 0.25)',
                                borderColor: '#1E5FEA',
                                borderWidth: 1,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                            },
                            scales: {
                                x: {
                                    grid: { color: t0.gridColor },
                                    ticks: { color: t0.tickColor }
                                },
                                y: {
                                    grid: { color: t0.gridColor },
                                    ticks: { color: t0.tickColor },
                                    beginAtZero: true,
                                }
                            }
                        }
                    });

                    window.__aiProviderChart = Chart.getChart(providerChartEl);
                }
            }

            const pmLabels = [];
            const pmTokens = [];
            (Array.isArray(breakdownRows) ? breakdownRows : []).forEach(function (row) {
                const provider = row.provider_label || row.provider || '';
                const model = row.model || 'Default';
                pmLabels.push(String(provider) + ' / ' + String(model));
                pmTokens.push(Number(row.tokens || 0));
            });

            const providerModelChartEl = document.getElementById('aiProviderModelChart');
            if (providerModelChartEl && typeof Chart !== 'undefined') {
                const ctx = providerModelChartEl.getContext('2d');
                if (ctx) {
                    const t0 = theme();
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: pmLabels,
                            datasets: [{
                                label: @json(__('Tokens')),
                                data: pmTokens,
                                backgroundColor: 'rgba(16, 148, 137, 0.25)',
                                borderColor: '#109489',
                                borderWidth: 1,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                            },
                            scales: {
                                x: {
                                    grid: { color: t0.gridColor },
                                    ticks: {
                                        color: t0.tickColor,
                                        maxRotation: 60,
                                        minRotation: 0,
                                    }
                                },
                                y: {
                                    grid: { color: t0.gridColor },
                                    ticks: { color: t0.tickColor },
                                    beginAtZero: true,
                                }
                            }
                        }
                    });

                    window.__aiProviderModelChart = Chart.getChart(providerModelChartEl);
                }
            }

            const providerCostChartEl = document.getElementById('aiProviderCostChart');
            if (providerCostChartEl && typeof Chart !== 'undefined') {
                const ctx = providerCostChartEl.getContext('2d');
                if (ctx) {
                    const t0 = theme();
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: providerLabels,
                            datasets: [{
                                label: @json(__('Cost ($)')),
                                data: providerCost,
                                backgroundColor: 'rgba(245, 158, 11, 0.25)',
                                borderColor: '#F59E0B',
                                borderWidth: 1,
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
                                            return ' $' + v.toFixed(2);
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
                                    ticks: {
                                        color: t0.tickColor,
                                        callback: function (v) {
                                            const n = Number(v || 0);
                                            return '$' + n;
                                        }
                                    },
                                    beginAtZero: true,
                                }
                            }
                        }
                    });

                    window.__aiProviderCostChart = Chart.getChart(providerCostChartEl);
                }
            }

            const pmCost = [];
            (Array.isArray(breakdownRows) ? breakdownRows : []).forEach(function (row) {
                pmCost.push(Number(row.estimated_cost_cents || 0) / 100);
            });

            const providerModelCostChartEl = document.getElementById('aiProviderModelCostChart');
            if (providerModelCostChartEl && typeof Chart !== 'undefined') {
                const ctx = providerModelCostChartEl.getContext('2d');
                if (ctx) {
                    const t0 = theme();
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: pmLabels,
                            datasets: [{
                                label: @json(__('Cost ($)')),
                                data: pmCost,
                                backgroundColor: 'rgba(168, 85, 247, 0.22)',
                                borderColor: '#A855F7',
                                borderWidth: 1,
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
                                            return ' $' + v.toFixed(2);
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: t0.gridColor },
                                    ticks: {
                                        color: t0.tickColor,
                                        maxRotation: 60,
                                        minRotation: 0,
                                    }
                                },
                                y: {
                                    grid: { color: t0.gridColor },
                                    ticks: {
                                        color: t0.tickColor,
                                        callback: function (v) {
                                            const n = Number(v || 0);
                                            return '$' + n;
                                        }
                                    },
                                    beginAtZero: true,
                                }
                            }
                        }
                    });

                    window.__aiProviderModelCostChart = Chart.getChart(providerModelCostChartEl);
                }
            }

            const applyTheme = () => {
                const t = theme();

                const c1 = window.__aiProviderChart;
                if (c1) {
                    c1.options.scales.x.grid.color = t.gridColor;
                    c1.options.scales.y.grid.color = t.gridColor;
                    c1.options.scales.x.ticks.color = t.tickColor;
                    c1.options.scales.y.ticks.color = t.tickColor;
                    c1.update('none');
                }

                const c2 = window.__aiProviderModelChart;
                if (c2) {
                    c2.options.scales.x.grid.color = t.gridColor;
                    c2.options.scales.y.grid.color = t.gridColor;
                    c2.options.scales.x.ticks.color = t.tickColor;
                    c2.options.scales.y.ticks.color = t.tickColor;
                    c2.update('none');
                }

                const c3 = window.__aiProviderCostChart;
                if (c3) {
                    c3.options.scales.x.grid.color = t.gridColor;
                    c3.options.scales.y.grid.color = t.gridColor;
                    c3.options.scales.x.ticks.color = t.tickColor;
                    c3.options.scales.y.ticks.color = t.tickColor;
                    c3.update('none');
                }

                const c4 = window.__aiProviderModelCostChart;
                if (c4) {
                    c4.options.scales.x.grid.color = t.gridColor;
                    c4.options.scales.y.grid.color = t.gridColor;
                    c4.options.scales.x.ticks.color = t.tickColor;
                    c4.options.scales.y.ticks.color = t.tickColor;
                    c4.update('none');
                }
            };

            const observer = new MutationObserver(applyTheme);
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>
@endpush

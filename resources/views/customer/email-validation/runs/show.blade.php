@extends('layouts.customer')

@php
    $statusColors = [
        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'running'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'pending'   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        'paused'    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
        'failed'    => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    ];
    $statusDots = [
        'completed' => 'bg-green-500',
        'running'   => 'bg-blue-500',
        'pending'   => 'bg-gray-400',
        'paused'    => 'bg-yellow-500',
        'failed'    => 'bg-red-500',
    ];
    $statusColor = $statusColors[$run->status] ?? 'bg-gray-100 text-gray-600';
    $statusDot   = $statusDots[$run->status] ?? 'bg-gray-400';
@endphp

@section('title', 'Validation #' . $run->id)
@section('page-title', 'Validation #' . $run->id)

@section('breadcrumbs')
    <nav aria-label="Breadcrumb" class="mb-0">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
            <li>
                <a href="{{ route('customer.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">
                    Dashboard
                </a>
            </li>
            <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
            <li>
                <a href="{{ route('customer.email-validation.runs.index') }}" class="font-medium transition hover:text-admin-text-primary">
                    Email Validation
                </a>
            </li>
            <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
            <li class="font-medium text-admin-text-primary truncate">Validation #{{ $run->id }}</li>
        </ol>
    </nav>
@endsection

@section('page-title-meta')
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
        <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
        {{ ucfirst($run->status) }}
    </span>
@endsection

@section('page-title-details')
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <span>List: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $run->list?->name ?? '—' }}</span></span>
        @if($run->started_at)
            <span>
                {{ $run->started_at->format('M j, Y') }}
                @if($run->finished_at) – {{ $run->finished_at->format('M j, Y') }}@endif
            </span>
        @endif
    </div>
@endsection

@section('page-actions')
    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
        <a href="{{ route('customer.email-validation.runs.index') }}"
           class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            Back
        </a>

        <a href="{{ route('customer.email-validation.runs.export-valid', $run) }}"
           class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
            Export Valid
        </a>

        @php
        $isPaused = (bool) data_get($run->settings ?? [], 'is_paused', false);
        @endphp

        @if($run->status === 'running' && !$isPaused)
            <form method="POST" action="{{ route('customer.email-validation.runs.pause', $run) }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2 rounded-lg bg-yellow-500 text-white text-sm font-semibold hover:bg-yellow-600 transition-colors shadow-sm">
                    Pause
                </button>
            </form>
        @elseif($run->status === 'running' && $isPaused)
            <form method="POST" action="{{ route('customer.email-validation.runs.resume', $run) }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2 rounded-lg bg-[#1E5FEA] text-white text-sm font-semibold hover:bg-blue-700 transition-colors shadow-sm">
                    Resume
                </button>
            </form>
        @elseif($run->status === 'failed')
            <form method="POST" action="{{ route('customer.email-validation.runs.resume-failed', $run) }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2 rounded-lg bg-[#1E5FEA] text-white text-sm font-semibold hover:bg-blue-700 transition-colors shadow-sm">
                    Resume
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
<div class="space-y-5">
    @if($run->status === 'failed' && $run->failure_reason)
        <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl dark:bg-red-900/20 dark:border-red-800">
            <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-red-800 dark:text-red-200">Validation Failed</p>
                <p class="text-sm text-red-700 dark:text-red-300 mt-0.5" id="runFailureReason">{{ $run->failure_reason }}</p>
            </div>
        </div>
    @endif

    @php
    $pauseReason = is_string(data_get($run->settings ?? [], 'pause_reason')) ? (string) data_get($run->settings ?? [], 'pause_reason') : null;
    @endphp
    @if($isPaused && $pauseReason)
        <div class="flex items-start gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-xl dark:bg-yellow-900/20 dark:border-yellow-800">
            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-yellow-900 dark:text-yellow-100">Validation Paused</p>
                <p class="text-sm text-yellow-800 dark:text-yellow-200 mt-0.5" id="runPauseReason">{{ $pauseReason }}</p>
            </div>
        </div>
    @endif

    @php
        $tabs = [
            'overview' => [
                'label' => 'Overview',
                'icon'  => '<rect stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" x="3" y="3" width="7" height="7" rx="1"/><rect stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" x="14" y="3" width="7" height="7" rx="1"/><rect stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" x="3" y="14" width="7" height="7" rx="1"/><rect stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" x="14" y="14" width="7" height="7" rx="1"/>',
                'count' => null,
            ],
            'deliverables' => [
                'label' => 'Deliverables',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V6l8-4 8 4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4"/>',
                'count' => $deliverablesCount,
            ],
            'invalid' => [
                'label' => 'Invalid',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                'count' => $invalidItemsCount,
            ],
        ];
    @endphp
    <div class="border-b border-gray-200 dark:border-gray-700 -ml-6 pl-6">
        <nav class="flex overflow-x-auto" aria-label="Validation tabs">
            @foreach($tabs as $slug => $tab)
                @php $isActive = $activeTab === $slug; @endphp
                <a href="{{ route('customer.email-validation.runs.show', array_merge([$run], $slug !== 'overview' ? ['tab' => $slug] : [])) }}"
                   class="group inline-flex items-center gap-2 whitespace-nowrap px-0 py-3 mr-6 text-sm font-medium rounded-none bg-transparent no-underline border-b-2 transition-all duration-150
                       {{ $isActive
                           ? '!border-primary-600 text-primary-600 visited:text-primary-600 dark:text-primary-400 dark:!border-primary-400'
                           : '!border-transparent text-gray-500 visited:text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:!border-gray-300 dark:hover:!border-gray-500' }}">
                    <svg class="w-4 h-4 shrink-0 transition-colors
                             {{ $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        {!! $tab['icon'] !!}
                    </svg>
                    {{ $tab['label'] }}
                    @if(is_int($tab['count']) && $tab['count'] >= 0)
                        <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $isActive ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                            {{ number_format($tab['count']) }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    @if($activeTab === 'overview')
        @if(in_array($run->status, ['pending', 'running'], true) && $run->total_emails > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Validation Progress</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400" id="progressPercentage">
                        {{ number_format(($run->processed_count / max(1, $run->total_emails)) * 100, 1) }}%
                    </span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 mb-3">
                    <div class="bg-[#1E5FEA] h-2 rounded-full transition-all duration-500" id="progressBar"
                         style="width: {{ ($run->processed_count / max(1, $run->total_emails)) * 100 }}%"></div>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span id="progressText">{{ number_format($run->processed_count) }} / {{ number_format($run->total_emails) }} checked</span>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-5 flex flex-col gap-3 overflow-hidden relative">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Emails</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight" id="totalCount">{{ number_format($run->total_emails) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Emails in run</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-5 flex flex-col gap-3 overflow-hidden relative">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Processed</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight" id="processedCount">{{ number_format($run->processed_count) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Checked</p>
                </div>
            </div>

            <a href="{{ route('customer.email-validation.runs.show', [$run, 'tab' => 'deliverables']) }}" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-5 flex flex-col gap-3 overflow-hidden relative transition hover:border-green-200 dark:hover:border-green-600 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Deliverable</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight" id="deliverableCount">{{ number_format($run->deliverable_count) }}</p>
                    @if($run->processed_count > 0)
                        <p class="text-xs font-medium text-green-600 dark:text-green-400 mt-0.5">({{ number_format(($run->deliverable_count / max(1, $run->processed_count)) * 100, 1) }}%)</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Valid emails</p>
                    @endif
                </div>
            </a>

            <a href="{{ route('customer.email-validation.runs.show', [$run, 'tab' => 'invalid']) }}" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-5 flex flex-col gap-3 overflow-hidden relative transition hover:border-red-200 dark:hover:border-red-600 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Invalid</span>
                </div>
                <div>
                    @php $invalidTotal = $run->undeliverable_count + $run->accept_all_count + $run->unknown_count + $run->error_count + $inboxFullCount @endphp
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight" id="invalidTotal">{{ number_format($invalidItemsCount) }}</p>
                    @if($run->processed_count > 0)
                        <p class="text-xs font-medium text-red-600 dark:text-red-400 mt-0.5">({{ number_format(($invalidItemsCount / max(1, $run->processed_count)) * 100, 1) }}%)</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Invalid / risky</p>
                    @endif
                </div>
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Result Breakdown</h3>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="deliverableCount2">{{ number_format($run->deliverable_count) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Deliverable</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="undeliverableCount">{{ number_format($run->undeliverable_count) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Undeliverable</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="inboxFullCount">{{ number_format($inboxFullCount) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Inbox Full</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400" id="acceptAllCount">{{ number_format($run->accept_all_count) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Accept-all</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="unknownCount">{{ number_format($run->unknown_count) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Unknown</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="errorCount">{{ number_format($run->error_count) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Errors</div>
                </div>
            </div>
        </div>

        @if(in_array($run->status, ['pending', 'running'], true) && $run->total_emails > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Validation status</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Updates automatically while running</p>
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 dark:text-blue-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                    Live
                </span>
            </div>
        @endif
    @endif

    @if($activeTab === 'deliverables')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Deliverable Emails</h3>
                <a href="{{ route('customer.email-validation.runs.export-valid', $run) }}"
                   class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
                    Download CSV
                </a>
            </div>

            @if($deliverables->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Result</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Message</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Validated</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($deliverables as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 break-words">{{ $item->email }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                            {{ ucfirst($item->result) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 break-words">{{ $item->message ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $item->validated_at ? $item->validated_at->diffForHumans() : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($deliverables->hasPages())
                    <div class="mt-4">
                        {{ $deliverables->links() }}
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#474747" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle-icon"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-3">No deliverable emails found yet.</p>
                </div>
            @endif
        </div>
    @endif

    @if($activeTab === 'invalid')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Invalid Emails</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Undeliverable, inbox full, accept-all, unknown, and errors</span>
            </div>

            @if($invalidItems->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Result</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Message</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Error Details</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Validated</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($invalidItems as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 break-words">{{ $item->email }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @php
                                            $resultColor = match($item->result) {
                                                'undeliverable' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                'inbox_full' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                                'accept_all' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                'unknown' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                default => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $resultColor }}">
                                            {{ str_replace('_', '-', ucfirst($item->result ?? 'Error')) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 break-words">{{ $item->message ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        @if($item->provider_status || $item->provider_body_snippet)
                                            <div class="space-y-1">
                                                @if($item->provider_status)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        Status: {{ $item->provider_status }}
                                                    </span>
                                                @endif
                                                @if($item->provider_body_snippet)
                                                    <p class="text-xs text-gray-400 dark:text-gray-500 break-words">{{ \Illuminate\Support\Str::limit($item->provider_body_snippet, 120) }}</p>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $item->validated_at ? $item->validated_at->diffForHumans() : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($invalidItems->hasPages())
                    <div class="mt-4">
                        {{ $invalidItems->links() }}
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#474747" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check-icon"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V6l8-4 8 4z"/><path d="m9 12 2 2 4-4"/></svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-3">No invalid emails found.</p>
                </div>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script>
    let refreshInterval;

    function updateRunStats() {
        fetch('{{ route('customer.email-validation.runs.stats', $run) }}', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                return;
            }

            const stats = data.stats;

            const progressText = document.getElementById('progressText');
            const progressPercentage = document.getElementById('progressPercentage');
            const progressBar = document.getElementById('progressBar');

            if (progressText && progressPercentage && progressBar) {
                progressText.textContent = new Intl.NumberFormat().format(stats.processed_count) + ' / ' + new Intl.NumberFormat().format(stats.total_emails) + ' checked';
                progressPercentage.textContent = stats.percent + '%';
                progressBar.style.width = stats.percent + '%';
            }

            const totalCount = document.getElementById('totalCount');
            const processedCount = document.getElementById('processedCount');
            const deliverableCount = document.getElementById('deliverableCount');
            const deliverableCount2 = document.getElementById('deliverableCount2');
            const undeliverableCount = document.getElementById('undeliverableCount');
            const invalidTotal = document.getElementById('invalidTotal');
            const inboxFullEl = document.getElementById('inboxFullCount');
            const acceptAllEl = document.getElementById('acceptAllCount');
            const unknownEl = document.getElementById('unknownCount');
            const errorEl = document.getElementById('errorCount');

            if (totalCount) totalCount.textContent = new Intl.NumberFormat().format(stats.total_emails);
            if (processedCount) processedCount.textContent = new Intl.NumberFormat().format(stats.processed_count);
            if (deliverableCount) deliverableCount.textContent = new Intl.NumberFormat().format(stats.deliverable_count);
            if (deliverableCount2) deliverableCount2.textContent = new Intl.NumberFormat().format(stats.deliverable_count);
            if (undeliverableCount) undeliverableCount.textContent = new Intl.NumberFormat().format(stats.undeliverable_count);
            if (inboxFullEl) inboxFullEl.textContent = new Intl.NumberFormat().format(stats.inbox_full_count || 0);
            if (acceptAllEl) acceptAllEl.textContent = new Intl.NumberFormat().format(stats.accept_all_count);
            if (unknownEl) unknownEl.textContent = new Intl.NumberFormat().format(stats.unknown_count);
            if (errorEl) errorEl.textContent = new Intl.NumberFormat().format(stats.error_count);

            if (invalidTotal) {
                const invalid = stats.undeliverable_count + stats.accept_all_count + stats.unknown_count + stats.error_count + (stats.inbox_full_count || 0);
                invalidTotal.textContent = new Intl.NumberFormat().format(invalid);
            }

            const failureReason = document.getElementById('runFailureReason');
            if (failureReason && stats.status === 'failed' && stats.failure_reason) {
                failureReason.textContent = stats.failure_reason;
            }

            const pauseReason = document.getElementById('runPauseReason');
            const pauseWrapper = document.getElementById('runPauseWrapper');
            if (pauseReason && pauseWrapper) {
                if (stats.is_paused && stats.pause_reason) {
                    pauseReason.textContent = stats.pause_reason;
                    pauseWrapper.classList.remove('hidden');
                } else {
                    pauseWrapper.classList.add('hidden');
                }
            }

            if (stats.status === 'completed' || stats.status === 'failed') {
                clearInterval(refreshInterval);
            }
        })
        .catch(error => {
            console.error('Error updating stats:', error);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        if ('{{ $run->status }}' === 'pending' || '{{ $run->status }}' === 'running') {
            refreshInterval = setInterval(updateRunStats, 3000);
        }
    });

    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
</script>
@endpush
@endsection

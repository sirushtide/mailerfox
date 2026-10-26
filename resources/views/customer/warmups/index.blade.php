@extends('layouts.customer')

@section('title', 'Email Warmups')
@section('page-title', 'Email Warmups')

@section('page-actions')
    <div class="mp-toolbar w-full justify-end">
        <form method="GET" action="{{ route('customer.warmups.index') }}" class="mp-toolbar w-full sm:w-auto">
            <select name="status" class="mp-input w-full sm:w-44">
                <option value="">All Statuses</option>
                <option value="draft" {{ ($filters['status'] ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="paused" {{ ($filters['status'] ?? '') === 'paused' ? 'selected' : '' }}>Paused</option>
                <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
            <x-button type="submit" variant="primary" class="w-full sm:w-auto">Apply</x-button>
        </form>
        <x-button href="{{ route('customer.warmups.create') }}" variant="primary" class="w-full sm:w-auto" data-track="warmup.create">New Warmup</x-button>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Server</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Health</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($warmups as $warmup)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $warmup->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $warmup->from_email }}</div>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $warmup->deliveryServer?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $warmup->getProgressPercentage() }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $warmup->current_day }}/{{ $warmup->total_days }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($warmup->total_sent) }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                @php $health = $warmup->getHealthScore(); @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $health === 'excellent' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $health === 'good' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $health === 'fair' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $health === 'poor' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst($health) }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $warmup->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $warmup->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $warmup->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $warmup->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $warmup->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst($warmup->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <x-button href="{{ route('customer.warmups.show', $warmup) }}" variant="table" size="action" :pill="true" class="p-2" title="View" aria-label="View"><x-lucide name="eye" class="h-4 w-4" /><span class="sr-only">View</span></x-button>
                                    @if($warmup->canStart())
                                        <form method="POST" action="{{ route('customer.warmups.start', $warmup) }}" class="inline">
                                            @csrf
                                            <x-button type="submit" variant="table" size="action" :pill="true">
                                                <x-lucide name="play" class="h-4 w-4" />
                                                <span class="sr-only">Start</span>
                                            </x-button>
                                        </form>
                                    @endif
                                    @if($warmup->canPause())
                                        <form method="POST" action="{{ route('customer.warmups.pause', $warmup) }}" class="inline">
                                            @csrf
                                            <x-button type="submit" variant="table" size="action" :pill="true">
                                                <x-lucide name="pause" class="h-4 w-4" />
                                                <span class="sr-only">Pause</span>
                                            </x-button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="mp-empty">
                                    <div class="mp-empty__icon">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h3.75a2 2 0 0 1 1.6.8l.45.6a4 4 0 0 0 6.4 0l.45-.6a2 2 0 0 1 1.6-.8H21"/><path d="M3 7h18"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                    </div>
                                    <p class="mp-empty__title">No warmups found</p>
                                    <p class="mp-empty__desc">
                                        <a href="{{ route('customer.warmups.create') }}" data-track="warmup.create" class="font-medium" style="color: var(--tw-brand)">Create your first warmup</a>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($warmups->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $warmups->links() }}</div>
        @endif
    </x-card>
</div>
@endsection

@extends('layouts.customer')

@section('title', __('CRM Dashboard'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('CRM Dashboard') }}</h1>
        <a href="{{ route('customer.crm.contacts.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            {{ __('Add Contact') }}
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Contacts') }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['contacts']) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Pipeline Value') }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['deals_total_value'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Open Deals') }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['open_deals']) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Open Tasks') }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['open_tasks']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Recent Contacts') }}</h2>
                <a href="{{ route('customer.crm.contacts.index') }}" class="text-sm text-[#1E5FEA] hover:underline">{{ __('View all') }}</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentContacts as $contact)
                <a href="{{ route('customer.crm.contacts.show', $contact) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#1E5FEA]/10 text-[#1E5FEA] text-sm font-bold">
                        {{ strtoupper(substr($contact->first_name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $contact->full_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $contact->email ?? $contact->status }}</p>
                    </div>
                </a>
                @empty
                <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No contacts yet.') }}</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Upcoming Tasks') }}</h2>
                <a href="{{ route('customer.crm.tasks.index') }}" class="text-sm text-[#1E5FEA] hover:underline">{{ __('View all') }}</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($upcomingTasks as $task)
                <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                        <form method="POST" action="{{ route('customer.crm.tasks.toggle-status', $task) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="h-5 w-5 rounded border {{ $task->status === 'done' ? 'bg-[#1E5FEA] border-[#1E5FEA]' : 'border-gray-300 dark:border-gray-600' }} flex items-center justify-center">
                                @if($task->status === 'done')
                                <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </button>
                        </form>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate {{ $task->status === 'done' ? 'line-through opacity-50' : '' }}">{{ $task->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @if($task->deal){{ $task->deal->title }} &middot; @endif
                                @if($task->due_date){{ $task->due_date->format('M d') }}@endif
                            </p>
                        </div>
                    </div>
                    @if($task->priority === 'high')
                    <span class="shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400">{{ __('High') }}</span>
                    @elseif($task->priority === 'medium')
                    <span class="shrink-0 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400">{{ __('Medium') }}</span>
                    @else
                    <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ __('Low') }}</span>
                    @endif
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No upcoming tasks.') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

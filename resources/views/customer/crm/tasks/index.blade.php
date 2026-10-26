@extends('layouts.customer')

@section('title', __('Tasks'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Tasks') }}</h1>
        <a href="{{ route('customer.crm.tasks.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            {{ __('Add Task') }}
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <form method="GET" action="{{ route('customer.crm.tasks.index') }}" class="flex items-center gap-3 flex-1">
                <select name="status" onchange="this.form.submit()" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                    <option value="done" {{ request('status') === 'done' ? 'selected' : '' }}>{{ __('Done') }}</option>
                </select>
                <select name="priority" onchange="this.form.submit()" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ __('All Priority') }}</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>{{ __('Medium') }}</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3 font-medium w-10"></th>
                        <th class="px-5 py-3 font-medium">{{ __('Task') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Related') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Due') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Priority') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Assignee') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($tasks as $task)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors {{ $task->isOverdue() ? 'bg-red-50/50 dark:bg-red-900/10' : '' }}">
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('customer.crm.tasks.toggle-status', $task) }}" class="inline">
                                @csrf
                                <button type="submit" class="h-5 w-5 rounded border {{ $task->status === 'done' ? 'bg-[#1E5FEA] border-[#1E5FEA]' : 'border-gray-300 dark:border-gray-600' }} flex items-center justify-center transition-colors">
                                    @if($task->status === 'done')
                                    <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="px-5 py-3">
                            <p class="font-medium text-gray-900 dark:text-white {{ $task->status === 'done' ? 'line-through opacity-50' : '' }}">{{ $task->title }}</p>
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                            @if($task->deal){{ $task->deal->title }}@elseif($task->contact){{ $task->contact->full_name }}@else - @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                            @if($task->due_date)
                            <span class="{{ $task->isOverdue() ? 'text-red-600 dark:text-red-400 font-medium' : '' }}">{{ $task->due_date->format('M d') }}</span>
                            @else - @endif
                        </td>
                        <td class="px-5 py-3">
                            @if($task->priority === 'high')
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400">{{ __('High') }}</span>
                            @elseif($task->priority === 'medium')
                            <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400">{{ __('Medium') }}</span>
                            @else
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ __('Low') }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $task->assignee?->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('customer.crm.tasks.edit', $task) }}" class="text-gray-400 hover:text-[#1E5FEA] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('customer.crm.tasks.destroy', $task) }}" onsubmit="return confirm('{{ __('Delete this task?') }}')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    </button>
                                </form>
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
                                <p class="mp-empty__title">{{ __('No tasks found.') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tasks->hasPages())
        <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-700">{{ $tasks->links() }}</div>
        @endif
    </div>
</div>
@endsection

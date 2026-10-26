@extends('layouts.customer')

@section('title', $deal->title)

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('customer.crm.deals.index') }}" class="text-sm text-gray-500 hover:text-[#1E5FEA] transition-colors">&larr; {{ __('Back to deals') }}</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $deal->title }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.crm.deals.edit', $deal) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                {{ __('Edit') }}
            </a>
            <form method="POST" action="{{ route('customer.crm.deals.destroy', $deal) }}" onsubmit="return confirm('{{ __('Delete this deal?') }}')" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-900/30 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    {{ __('Delete') }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Details') }}</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Value') }}</dt>
                        <dd class="text-gray-900 dark:text-white font-medium">${{ number_format($deal->value, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Pipeline') }}</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $deal->pipeline?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Stage') }}</dt>
                        <dd><span class="rounded-full px-2 py-0.5 text-xs font-medium" style="background: {{ $deal->stage?->color }}20; color: {{ $deal->stage?->color }}">{{ $deal->stage?->name ?? '-' }}</span></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Contact') }}</dt>
                        <dd><a href="{{ route('customer.crm.contacts.show', $deal->contact) }}" class="text-[#1E5FEA] hover:underline">{{ $deal->contact?->full_name ?? '-' }}</a></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Owner') }}</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $deal->owner?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Expected Close') }}</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $deal->expected_close_date?->format('M d, Y') ?? '-' }}</dd>
                    </div>
                    @if($deal->actual_close_date)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Actual Close') }}</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $deal->actual_close_date->format('M d, Y') }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                        <dd>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $deal->status === 'won' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : ($deal->status === 'lost' ? 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400') }}">{{ ucfirst($deal->status) }}</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Activity') }}</h2>
                <div class="space-y-3">
                    @forelse($deal->activities as $activity)
                    <div class="flex gap-3">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-800 dark:text-gray-200">{{ $activity->description }}</p>
                            <p class="text-xs text-gray-400">{{ $activity->performer?->name ?? __('System') }} &middot; {{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No activity yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Tasks') }}</h2>
                @if($deal->tasks->isNotEmpty())
                <div class="space-y-2">
                    @foreach($deal->tasks as $task)
                    <div class="flex items-center gap-3 rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-700/50">
                        <form method="POST" action="{{ route('customer.crm.tasks.toggle-status', $task) }}" class="inline">
                            @csrf
                            <button type="submit" class="h-5 w-5 rounded border {{ $task->status === 'done' ? 'bg-[#1E5FEA] border-[#1E5FEA]' : 'border-gray-300 dark:border-gray-600' }} flex items-center justify-center">
                                @if($task->status === 'done')<svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>@endif
                            </button>
                        </form>
                        <span class="text-sm {{ $task->status === 'done' ? 'line-through opacity-50' : 'text-gray-900 dark:text-white' }}">{{ $task->title }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No tasks linked to this deal.') }}</p>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Notes') }}</h2>
                <form method="POST" action="{{ route('customer.crm.notes.store') }}" class="mb-4">
                    @csrf
                    <input type="hidden" name="notable_type" value="deal">
                    <input type="hidden" name="notable_id" value="{{ $deal->id }}">
                    <textarea name="content" rows="2" placeholder="{{ __('Add a note...') }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white mb-2"></textarea>
                    <button type="submit" class="rounded-lg bg-[#1E5FEA] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#174bc0] transition-colors">{{ __('Add Note') }}</button>
                </form>
                @if($deal->notes->isNotEmpty())
                <div class="space-y-3">
                    @foreach($deal->notes as $note)
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700/50">
                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ $note->content }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs text-gray-400">{{ $note->creator?->name ?? __('Unknown') }} &middot; {{ $note->created_at->diffForHumans() }}</span>
                            <form method="POST" action="{{ route('customer.crm.notes.destroy', $note) }}" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-gray-400 hover:text-red-500 transition-colors">{{ __('Delete') }}</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No notes yet.') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

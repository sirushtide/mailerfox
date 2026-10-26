@extends('layouts.customer')

@section('title', $contact->full_name)

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('customer.crm.contacts.index') }}" class="text-sm text-gray-500 hover:text-[#1E5FEA] transition-colors">&larr; {{ __('Back to contacts') }}</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $contact->full_name }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.crm.contacts.edit', $contact) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                {{ __('Edit') }}
            </a>
            <form method="POST" action="{{ route('customer.crm.contacts.destroy', $contact) }}" onsubmit="return confirm('{{ __('Delete this contact?') }}')" class="inline">
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
                    @if($contact->email)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $contact->email }}</dd>
                    </div>
                    @endif
                    @if($contact->phone)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Phone') }}</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $contact->phone }}</dd>
                    </div>
                    @endif
                    @if($contact->title)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Title') }}</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $contact->title }}</dd>
                    </div>
                    @endif
                    @if($contact->company)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Company') }}</dt>
                        <dd><a href="{{ route('customer.crm.companies.show', $contact->company) }}" class="text-[#1E5FEA] hover:underline">{{ $contact->company->name }}</a></dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                        <dd>
                            @if($contact->status === 'lead')
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">{{ __('Lead') }}</span>
                            @elseif($contact->status === 'prospect')
                            <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400">{{ __('Prospect') }}</span>
                            @elseif($contact->status === 'customer')
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">{{ __('Customer') }}</span>
                            @else
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ __('Churned') }}</span>
                            @endif
                        </dd>
                    </div>
                    @if($contact->source)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Source') }}</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $contact->source }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            @if($contact->subscribers->isNotEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Linked Subscribers') }}</h2>
                <div class="space-y-2">
                    @foreach($contact->subscribers as $subscriber)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-700/50">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $subscriber->email }}</span>
                        <form method="POST" action="{{ route('customer.crm.contacts.unlink-subscriber', [$contact, $subscriber->id]) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Deals') }}</h2>
                @if($contact->deals->isNotEmpty())
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($contact->deals as $deal)
                    <a href="{{ route('customer.crm.deals.show', $deal) }}" class="flex items-center justify-between py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors px-2 -mx-2 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $deal->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $deal->pipeline?->name }} &middot; {{ $deal->stage?->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($deal->value, 2) }}</p>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $deal->status === 'won' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : ($deal->status === 'lost' ? 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400') }}">{{ ucfirst($deal->status) }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No deals yet.') }}</p>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Notes') }}</h2>
                <form method="POST" action="{{ route('customer.crm.notes.store') }}" class="mb-4">
                    @csrf
                    <input type="hidden" name="notable_type" value="contact">
                    <input type="hidden" name="notable_id" value="{{ $contact->id }}">
                    <textarea name="content" rows="2" placeholder="{{ __('Add a note...') }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white mb-2"></textarea>
                    <button type="submit" class="rounded-lg bg-[#1E5FEA] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#174bc0] transition-colors">{{ __('Add Note') }}</button>
                </form>

                @if($contact->notes->isNotEmpty())
                <div class="space-y-3">
                    @foreach($contact->notes as $note)
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

@extends('layouts.customer')

@section('title', __('Contacts'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Contacts') }}</h1>
        <x-ui.button variant="primary" icon="plus" href="{{ route('customer.crm.contacts.create') }}">
            {{ __('Add Contact') }}
        </x-ui.button>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <form method="GET" action="{{ route('customer.crm.contacts.index') }}" class="mp-toolbar">
                <div class="w-full sm:max-w-sm">
                    <x-ui.input icon="search" name="search" :value="request('search')" placeholder="{{ __('Search contacts...') }}" aria-label="{{ __('Search contacts') }}" />
                </div>
                <select name="status" onchange="this.form.submit()" class="mp-input w-full sm:w-auto" aria-label="{{ __('Filter by status') }}">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="lead" {{ request('status') === 'lead' ? 'selected' : '' }}>{{ __('Lead') }}</option>
                    <option value="prospect" {{ request('status') === 'prospect' ? 'selected' : '' }}>{{ __('Prospect') }}</option>
                    <option value="customer" {{ request('status') === 'customer' ? 'selected' : '' }}>{{ __('Customer') }}</option>
                    <option value="churned" {{ request('status') === 'churned' ? 'selected' : '' }}>{{ __('Churned') }}</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Email') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Company') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Deals') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($contacts as $contact)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('customer.crm.contacts.show', $contact) }}" class="font-medium text-gray-900 dark:text-white hover:text-[#1E5FEA] transition-colors">
                                {{ $contact->full_name }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $contact->email ?? '-' }}</td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $contact->company?->name ?? '-' }}</td>
                        <td class="px-5 py-3">
                            @if($contact->status === 'lead')
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">{{ __('Lead') }}</span>
                            @elseif($contact->status === 'prospect')
                            <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400">{{ __('Prospect') }}</span>
                            @elseif($contact->status === 'customer')
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">{{ __('Customer') }}</span>
                            @else
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ __('Churned') }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $contact->deals_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('customer.crm.contacts.edit', $contact) }}" class="text-gray-400 hover:text-[#1E5FEA] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('customer.crm.contacts.destroy', $contact) }}" onsubmit="return confirm('{{ __('Delete this contact?') }}')" class="inline">
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
                        <td colspan="6">
                            <div class="mp-empty">
                                <div class="mp-empty__icon">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h3.75a2 2 0 0 1 1.6.8l.45.6a4 4 0 0 0 6.4 0l.45-.6a2 2 0 0 1 1.6-.8H21"/><path d="M3 7h18"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                </div>
                                <p class="mp-empty__title">{{ __('No contacts found.') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contacts->hasPages())
        <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-700">
            {{ $contacts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

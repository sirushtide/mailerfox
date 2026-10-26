@extends('layouts.customer')

@section('title', $company->name)

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('customer.crm.companies.index') }}" class="text-sm text-gray-500 hover:text-[#1E5FEA] transition-colors">&larr; {{ __('Back to companies') }}</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $company->name }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.crm.companies.edit', $company) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                {{ __('Edit') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Details') }}</h2>
                <dl class="space-y-3 text-sm">
                    @if($company->email)
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt><dd class="text-gray-900 dark:text-white">{{ $company->email }}</dd></div>
                    @endif
                    @if($company->phone)
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Phone') }}</dt><dd class="text-gray-900 dark:text-white">{{ $company->phone }}</dd></div>
                    @endif
                    @if($company->website)
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Website') }}</dt><dd><a href="{{ $company->website }}" target="_blank" class="text-[#1E5FEA] hover:underline">{{ $company->website }}</a></dd></div>
                    @endif
                    @if($company->industry)
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Industry') }}</dt><dd class="text-gray-900 dark:text-white">{{ $company->industry }}</dd></div>
                    @endif
                    @if($company->size)
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Size') }}</dt><dd class="text-gray-900 dark:text-white">{{ $company->size }}</dd></div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                        <dd><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $company->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($company->status) }}</span></dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Contacts') }} ({{ $company->contacts_count }})</h2>
                @if($company->contacts->isNotEmpty())
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($company->contacts as $contact)
                    <a href="{{ route('customer.crm.contacts.show', $contact) }}" class="flex items-center gap-3 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors px-2 -mx-2 rounded-lg">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1E5FEA]/10 text-[#1E5FEA] text-xs font-bold">
                            {{ strtoupper(substr($contact->first_name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $contact->full_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $contact->email ?? $contact->status }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No contacts linked to this company.') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

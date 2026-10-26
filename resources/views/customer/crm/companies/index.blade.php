@extends('layouts.customer')

@section('title', __('Companies'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Companies') }}</h1>
        <a href="{{ route('customer.crm.companies.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            {{ __('Add Company') }}
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <form method="GET" action="{{ route('customer.crm.companies.index') }}" class="flex items-center gap-3 flex-1">
                <div class="relative flex-1 max-w-sm">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search companies...') }}" class="block w-full rounded-lg border border-gray-200 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500">
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Industry') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Size') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Contacts') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($companies as $company)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('customer.crm.companies.show', $company) }}" class="font-medium text-gray-900 dark:text-white hover:text-[#1E5FEA] transition-colors">
                                {{ $company->name }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $company->industry ?? '-' }}</td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $company->size ?? '-' }}</td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $company->contacts_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('customer.crm.companies.edit', $company) }}" class="text-gray-400 hover:text-[#1E5FEA] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('customer.crm.companies.destroy', $company) }}" onsubmit="return confirm('{{ __('Delete this company?') }}')" class="inline">
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
                        <td colspan="5">
                            <div class="mp-empty">
                                <div class="mp-empty__icon">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h3.75a2 2 0 0 1 1.6.8l.45.6a4 4 0 0 0 6.4 0l.45-.6a2 2 0 0 1 1.6-.8H21"/><path d="M3 7h18"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                </div>
                                <p class="mp-empty__title">{{ __('No companies found.') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
        <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-700">{{ $companies->links() }}</div>
        @endif
    </div>
</div>
@endsection

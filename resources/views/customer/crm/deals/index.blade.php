@extends('layouts.customer')

@section('title', __('Deals'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Deals') }}</h1>
        <a href="{{ route('customer.crm.deals.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            {{ __('Add Deal') }}
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <form method="GET" action="{{ route('customer.crm.deals.index') }}" class="flex items-center gap-3 flex-1">
                <select name="pipeline_id" onchange="this.form.submit()" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ __('All Pipelines') }}</option>
                    @foreach($pipelines as $id => $name)
                    <option value="{{ $id }}" {{ request('pipeline_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>{{ __('Open') }}</option>
                    <option value="won" {{ request('status') === 'won' ? 'selected' : '' }}>{{ __('Won') }}</option>
                    <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>{{ __('Lost') }}</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('Title') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Contact') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Pipeline') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Value') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($deals as $deal)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('customer.crm.deals.show', $deal) }}" class="font-medium text-gray-900 dark:text-white hover:text-[#1E5FEA] transition-colors">{{ $deal->title }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $deal->contact?->full_name ?? '-' }}</td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $deal->pipeline?->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-gray-900 dark:text-white font-medium">${{ number_format($deal->value, 2) }}</td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $deal->status === 'won' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : ($deal->status === 'lost' ? 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400') }}">{{ ucfirst($deal->status) }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('customer.crm.deals.edit', $deal) }}" class="text-gray-400 hover:text-[#1E5FEA] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('customer.crm.deals.destroy', $deal) }}" onsubmit="return confirm('{{ __('Delete this deal?') }}')" class="inline">
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
                                <p class="mp-empty__title">{{ __('No deals found.') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deals->hasPages())
        <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-700">{{ $deals->links() }}</div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', __('Plans'))
@section('page-title', __('Plans'))

@section('content')

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold">{{ __('Subscription Plans') }}</h2>
    <div class="flex items-center gap-2" x-data="{ importOpen: false }">
        <div class="relative">
            <button
                type="button"
                @click="importOpen = !importOpen"
                @click.outside="importOpen = false"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                {{ __('Import Plans') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" /></svg>
            </button>
            <div x-show="importOpen" x-cloak x-transition class="absolute right-0 z-20 mt-2 w-52 rounded-lg border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                <form x-ref="uploadForm" method="POST" action="{{ route('admin.plans.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input x-ref="planFile" type="file" name="plans_file" accept=".json,application/json" class="hidden" @change="$refs.uploadForm.submit()">
                    <button type="button" @click="$refs.planFile.click(); importOpen = false" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        {{ __('Upload JSON') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.plans.import-defaults') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        {{ __('Import defaults') }}
                    </button>
                </form>
            </div>
        </div>
        <x-button href="{{ route('admin.plans.export') }}" variant="secondary">{{ __('Export Plans') }}</x-button>
        @admincan('admin.plans.create')
            <x-button href="{{ route('admin.plans.create') }}" variant="primary">{{ __('Create Plan') }}</x-button>
        @endadmincan
    </div>
</div>

<x-card :padding="false">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Name') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Price') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Billing') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Users') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Stripe Price') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Active') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Popular') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Visibility') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($plans as $plan)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $plan->name }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $plan->currency }} {{ number_format($plan->price, 2) }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($plan->billing_cycle) }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $plan->subscriptions_count ?? 0 }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $plan->stripe_price_id ?? '—' }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $plan->is_active ? __('Active') : __('Disabled') }}
                            </span>
                        </td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $plan->is_popular ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $plan->is_popular ? __('Yes') : __('No') }}
                            </span>
                        </td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $plan->is_public ? 'bg-emerald-100 text-emerald-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ $plan->is_public ? __('Public') : __('Private') }}
                            </span>
                        </td>
                        <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                            <x-button href="{{ route('admin.plans.show', $plan) }}" variant="table" size="action" :pill="true">{{ __('View') }}</x-button>
                            @admincan('admin.plans.edit')
                                <x-button href="{{ route('admin.plans.edit', $plan) }}" variant="table" size="action" :pill="true">{{ __('Edit') }}</x-button>
                            @endadmincan
                            @admincan('admin.plans.delete')
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="inline" onsubmit="return confirm(@json(__('Delete plan?')));">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="table-danger" size="action" :pill="true">{{ __('Delete') }}</x-button>
                                </form>
                            @endadmincan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No plans found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($plans->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $plans->links() }}</div>
    @endif
</x-card>
@endsection

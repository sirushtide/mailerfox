@extends('layouts.customer')

@section('title', 'Subscription Forms')
@section('page-title', 'Subscription Forms: ' . $list->name)

@section('content')
<div class="space-y-6">
    @include('customer.lists.partials.header', [
        'list'               => $list,
        'primaryActionUrl'   => route('customer.forms.create', ['list_id' => $list->id]),
        'primaryActionLabel' => 'Create Form',
    ])

    @include('customer.lists.partials.subnav', ['list' => $list])

    <!-- Forms List -->
    @if($forms->count())
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-admin-border bg-white dark:bg-admin-sidebar">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-admin-border">
                <thead class="bg-gray-50 dark:bg-admin-sidebar">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary uppercase tracking-wider">{{ __('Title') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary uppercase tracking-wider">{{ __('Type') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary uppercase tracking-wider">{{ __('Slug') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary uppercase tracking-wider">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-admin-text-secondary uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-admin-border">
                    @foreach($forms as $form)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('customer.lists.forms.show', [$list, $form]) }}" class="text-sm font-medium text-gray-900 dark:text-admin-text-primary hover:text-primary-600 dark:hover:text-primary-400">
                                    {{ $form->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-admin-text-secondary">
                                {{ ucfirst($form->type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-admin-text-secondary font-mono">
                                {{ $form->slug }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $form->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}
                                ">
                                    {{ $form->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('customer.lists.forms.builder.edit', [$list, $form]) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-md border border-gray-200 dark:border-gray-600 text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-admin-text-secondary dark:hover:text-admin-text-primary dark:hover:bg-white/5 transition-colors" title="{{ __('Builder') }}" aria-label="{{ __('Builder') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench-icon lucide-wrench h-4 w-4"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.106-3.105c.32-.322.863-.22.983.218a6 6 0 0 1-8.259 7.057l-7.91 7.91a1 1 0 0 1-2.999-3l7.91-7.91a6 6 0 0 1 7.057-8.259c.438.12.54.662.219.984z"/></svg>
                                    </a>
                                    <a href="{{ route('customer.lists.forms.show', [$list, $form]) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-md border border-gray-200 dark:border-gray-600 text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-admin-text-secondary dark:hover:text-admin-text-primary dark:hover:bg-white/5 transition-colors" title="{{ __('Preview') }}" aria-label="{{ __('Preview') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-icon lucide-eye h-4 w-4"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-card>
            <div class="text-center py-8">
                <p class="text-sm text-gray-500 dark:text-gray-400">No subscription forms created yet.</p>
                <a href="{{ route('customer.forms.create', ['list_id' => $list->id]) }}" class="mt-4 inline-block px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700">
                    Create Your First Form
                </a>
            </div>
        </x-card>
    @endif
</div>
@endsection


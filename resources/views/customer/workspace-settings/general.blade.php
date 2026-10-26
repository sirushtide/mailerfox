@extends('layouts.customer')

@section('title', __('Workspace Settings'))
@section('page-title', '')

@section('breadcrumbs')
<ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
    <li><a href="{{ wsRoute('customer.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Dashboard') }}</a></li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li class="font-medium text-admin-text-primary truncate">{{ __('Workspace Settings') }}</li>
</ol>
@endsection

@section('content')
<div class="mx-auto max-w-3xl">
    @include('customer.workspace-settings._tabs', ['currentTab' => 'general'])

    @php
        $customer = auth('customer')->user();
        $isOwner = $workspace->isOwner($customer);
    @endphp

    <div class="space-y-8">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('General settings') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Configure your overall workspace application settings.') }}</p>
        </div>

        <form method="POST" action="{{ wsRoute('customer.workspace-settings.general.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-8">
                    <div class="sm:w-1/3">
                        <label for="name" class="block text-sm font-medium text-gray-900 dark:text-white">{{ __('Workspace name') }}</label>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Specify the name of your workspace.') }}</p>
                    </div>
                    <div class="sm:w-2/3">
                        <input type="text" name="name" id="name"
                               value="{{ old('name', $workspace->name) }}"
                               {{ !$isOwner ? 'disabled' : '' }}
                               required
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-[#1E5FEA] focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        @error('name')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-8">
                    <div class="sm:w-1/3">
                        <label for="description" class="block text-sm font-medium text-gray-900 dark:text-white">{{ __('Description') }}</label>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('A brief description of this workspace.') }}</p>
                    </div>
                    <div class="sm:w-2/3">
                        <textarea name="description" id="description" rows="3"
                                  {{ !$isOwner ? 'disabled' : '' }}
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-[#1E5FEA] focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">{{ old('description', $workspace->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-8">
                    <div class="sm:w-1/3">
                        <label for="slug" class="block text-sm font-medium text-gray-900 dark:text-white">{{ __('Workspace slug') }}</label>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Used to identify this workspace in URLs.') }}</p>
                    </div>
                    <div class="sm:w-2/3">
                        <input type="text" name="slug" id="slug"
                               value="{{ old('slug', $workspace->slug) }}"
                               {{ !$isOwner ? 'disabled' : '' }}
                               required
                               pattern="[a-zA-Z0-9_-]+"
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-[#1E5FEA] focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        @error('slug')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-8">
                    <div class="sm:w-1/3">
                        <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ __('Owner') }}</span>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('The creator of this workspace.') }}</p>
                    </div>
                    <div class="sm:w-2/3">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600 dark:bg-gray-600 dark:text-gray-200">
                                {{ strtoupper(substr($workspace->owner->full_name ?? '', 0, 2)) }}
                            </div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $workspace->owner->full_name ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($isOwner)
            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                <a href="{{ wsRoute('customer.dashboard') }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </a>
                <button type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                    {{ __('Update') }}
                </button>
            </div>
            @endif
        </form>
    </div>
</div>
@endsection

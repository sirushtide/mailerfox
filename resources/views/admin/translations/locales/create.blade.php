@extends('layouts.admin')

@section('title', 'Add Locale')
@section('page-title', 'Add Locale')

@section('breadcrumbs')
<ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
    <li>
        <a href="{{ route('admin.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">
            {{ __('Dashboard') }}
        </a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li>
        <a href="{{ route('admin.settings.index') }}" class="font-medium transition hover:text-admin-text-primary">
            {{ __('Settings') }}
        </a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li>
        <a href="{{ route('admin.translations.locales.index') }}" class="font-medium transition hover:text-admin-text-primary">
            {{ __('Translations') }}
        </a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li class="font-medium text-admin-text-primary truncate">{{ __('Add Locale') }}</li>
</ol>
@endsection

@section('content')
<div class="space-y-6">
    <x-card title="Add Locale">
        <form method="POST" action="{{ route('admin.translations.locales.store') }}">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Locale Code</label>
                    <input
                        type="text"
                        name="code"
                        value="{{ old('code') }}"
                        placeholder="e.g. en, en_US, ar"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                    >
                    @error('code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="e.g. English, US English"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Active</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Enable this locale for translations.</p>
                    </div>
                    <div class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="sr-only peer">
                            <div class="relative h-6 w-11 rounded-full bg-gray-200 transition peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <x-button href="{{ route('admin.translations.locales.index') }}" variant="secondary">Back</x-button>
                <x-button type="submit" variant="primary">Create</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection

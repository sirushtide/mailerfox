@extends('layouts.admin')

@section('title', __('Create Menu'))
@section('page-title', __('Create Menu'))

@section('breadcrumbs')
<ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
    <li>
        <a href="{{ route('admin.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">
            {{ __('Dashboard') }}
        </a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li>
        <a href="{{ route('admin.menus.index') }}" class="font-medium transition hover:text-admin-text-primary">
            {{ __('Menus') }}
        </a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li class="font-medium text-admin-text-primary">{{ __('Create') }}</li>
</ol>
@endsection

@section('content')
<div class="mx-auto w-full max-w-xl space-y-6">
    @if($errors->any())
        <div class="rounded-lg border border-red-300 bg-red-50 p-4 dark:bg-red-900/20 dark:border-red-700">
            <ul class="text-sm text-red-800 dark:text-red-300 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card>
        <form method="POST" action="{{ route('admin.menus.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Menu Name') }}</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('e.g. Main Menu, Footer Menu') }}" class="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required autofocus>
                <p class="mt-1 text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('A unique key will be auto-generated from the name.') }}</p>
            </div>
            <div>
                <x-button type="submit" variant="primary">{{ __('Create Menu') }}</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection

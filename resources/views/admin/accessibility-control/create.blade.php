@extends('layouts.admin')

@section('title', __('Legacy RBAC'))
@section('page-title', __('Legacy RBAC'))

@section('content')
<div class="space-y-6">
    <x-card title="{{ __('Legacy Feature') }}">
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            {{ __('Customer group permissions have been merged into Plans. Use the Plans page to manage plan-based permissions.') }}
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            {{ __('Admin RBAC is now managed from the Roles menu under Users.') }}
        </p>
        <div class="flex gap-3">
            <x-button href="{{ route('admin.plans.index') }}" variant="primary">{{ __('Go to Plans') }}</x-button>
            <x-button href="{{ route('admin.roles.index') }}" variant="secondary">{{ __('Go to Roles') }}</x-button>
        </div>
    </x-card>
</div>
@endsection

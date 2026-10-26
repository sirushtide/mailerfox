@extends('layouts.admin')

@section('title', __('Workspace & Teams Settings'))

@section('page-title', __('Workspace & Teams — Addon Settings'))

@section('content')
<div class="mx-auto max-w-xl">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form method="POST" action="{{ route('admin.workspace.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <div>
                    <label for="workspace_max_per_customer" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Max Workspaces per Customer') }}
                    </label>
                    <input type="number" name="workspace_max_per_customer" id="workspace_max_per_customer"
                           value="{{ old('workspace_max_per_customer', $settings['workspace_max_per_customer']) }}"
                           min="0" max="100"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Set to 0 for unlimited.') }}</p>
                    @error('workspace_max_per_customer')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="workspace_max_members" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Max Members per Workspace') }}
                    </label>
                    <input type="number" name="workspace_max_members" id="workspace_max_members"
                           value="{{ old('workspace_max_members', $settings['workspace_max_members']) }}"
                           min="0" max="500"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Set to 0 for unlimited.') }}</p>
                    @error('workspace_max_members')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="workspace_invitation_expiry_days" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Invitation Expiry (days)') }}
                    </label>
                    <input type="number" name="workspace_invitation_expiry_days" id="workspace_invitation_expiry_days"
                           value="{{ old('workspace_invitation_expiry_days', $settings['workspace_invitation_expiry_days']) }}"
                           min="1" max="90"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @error('workspace_invitation_expiry_days')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button type="submit"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                    {{ __('Save Settings') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

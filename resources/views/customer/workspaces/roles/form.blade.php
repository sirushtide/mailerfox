@extends('layouts.customer')

@section('title', isset($role) ? __('Edit Role') : __('Create Role'))

@section('page-title', $workspace->name . ' — ' . (isset($role) ? __('Edit Role') : __('Create Role')))

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form method="POST"
              action="{{ isset($role) ? route('customer.workspaces.roles.update', [$workspace, $role]) : route('customer.workspaces.roles.store', $workspace) }}">
            @csrf
            @if(isset($role))
                @method('PUT')
            @endif

            <div class="space-y-6">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Role Name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $role->name ?? '') }}" required
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="{{ __('e.g. Campaign Manager') }}">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_default" id="is_default" value="1"
                           class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                           {{ old('is_default', isset($role) && $role->is_default) ? 'checked' : '' }}>
                    <label for="is_default" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Default role for new members') }}</label>
                </div>

                {{-- Permissions --}}
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Permissions') }}</h3>

                    @php
                        $currentPerms = old('permissions', isset($role) ? (array)$role->permissions : []);
                    @endphp

                    <div class="space-y-4">
                        @foreach($permissionGroups as $group => $perms)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="mb-2 flex items-center justify-between">
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ $group }}</h4>
                                <label class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 cursor-pointer"
                                       x-data
                                       @click="
                                           const checkboxes = $el.closest('.rounded-lg').querySelectorAll('input[type=checkbox]');
                                           const allChecked = Array.from(checkboxes).every(c => c.checked);
                                           checkboxes.forEach(c => c.checked = !allChecked);
                                       ">
                                    {{ __('Toggle all') }}
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach($perms as $perm)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                                           class="h-3.5 w-3.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                           {{ in_array($perm, $currentPerms) ? 'checked' : '' }}>
                                    <span class="text-xs">{{ ucfirst(str_replace(['_', '.'], [' ', ' '], last(explode('.', $perm)))) }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('customer.workspaces.roles.index', $workspace) }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </a>
                <button type="submit"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                    {{ isset($role) ? __('Save Changes') : __('Create Role') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

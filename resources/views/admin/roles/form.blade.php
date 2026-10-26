@extends('layouts.admin')

@section('title', isset($role) ? __('Edit Role') : __('Create Role'))
@section('page-title', isset($role) ? __('Edit Role') : __('Create Role'))

@section('content')
<div class="max-w-7xl mx-auto">
    <form method="POST" action="{{ isset($role) ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
        @csrf
        @if(isset($role))
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <!-- Left: Role Information (sticky) -->
            <div class="lg:sticky lg:top-6">
                <x-card title="{{ __('Role Information') }}">
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Role Name') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $role->name ?? '') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    required
                                >
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Slug') }}
                                </label>
                                <input
                                    type="text"
                                    id="slug"
                                    name="slug"
                                    value="{{ old('slug', $role->slug ?? '') }}"
                                    placeholder="{{ __('auto-generated from name') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                >
                                @error('slug')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Description') }}
                            </label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                            >{{ old('description', $role->description ?? '') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="inline-flex items-center">
                                <input
                                    type="checkbox"
                                    name="is_default"
                                    value="1"
                                    {{ old('is_default', $role->is_default ?? false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('Set as default role for new admin users') }}
                                </span>
                            </label>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Right: Permissions with sticky bottom actions -->
            <div class="lg:mt-6">
                <x-card title="{{ __('Permissions') }}">
                    <div class="flex items-center justify-between mb-4">
                        <span></span>
                        <div class="flex gap-2">
                            <button type="button" id="select-all" class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                {{ __('Select All') }}
                            </button>
                            <span class="text-gray-300">|</span>
                            <button type="button" id="deselect-all" class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                {{ __('Deselect All') }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @php
                            $selectedPermissions = (array) old('permissions', isset($role) ? ($role->permissions ?? []) : []);
                        @endphp

                        @foreach($permissionGroups['modules'] as $module)
                            <div class="permission-module border-b border-gray-200 dark:border-gray-700 pb-6 last:border-0 last:pb-0 -mx-6 px-6 first:-mt-4 first:pt-6">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $module['label'] }}</h4>
                                    <button type="button" class="module-toggle text-xs text-primary-600 hover:text-primary-500 dark:text-primary-400" data-action="select">
                                        {{ __('Select module') }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                    @foreach($module['perms'] as $action => $permission)
                                        <label class="inline-flex items-center p-2 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission }}"
                                                {{ in_array($permission, $selectedPermissions, true) ? 'checked' : '' }}
                                                class="permission-checkbox rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            >
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 capitalize">{{ $action }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('permissions')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </x-card>

                <div class="sticky bottom-0 mt-4 bg-admin-sidebar border border-admin-border shadow-sm p-4 flex justify-end gap-3 z-10">
                    <x-button href="{{ route('admin.roles.index') }}" variant="secondary">
                        {{ __('Cancel') }}
                    </x-button>
                    <x-button type="submit" variant="primary">
                        {{ isset($role) ? __('Update Role') : __('Create Role') }}
                    </x-button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    (function () {
        const selectAll = document.getElementById('select-all');
        const deselectAll = document.getElementById('deselect-all');
        const checkboxes = document.querySelectorAll('.permission-checkbox');

        if (selectAll) {
            selectAll.addEventListener('click', function (e) {
                e.preventDefault();
                checkboxes.forEach(cb => cb.checked = true);
                updateModuleToggles(true);
            });
        }

        if (deselectAll) {
            deselectAll.addEventListener('click', function (e) {
                e.preventDefault();
                checkboxes.forEach(cb => cb.checked = false);
                updateModuleToggles(false);
            });
        }

        function updateModuleToggles(checked) {
            document.querySelectorAll('.module-toggle').forEach(toggle => {
                toggle.textContent = checked ? '{{ __('Deselect module') }}' : '{{ __('Select module') }}';
            });
        }

        document.querySelectorAll('.module-toggle').forEach(toggle => {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                const module = this.closest('.permission-module');
                const moduleCheckboxes = module.querySelectorAll('.permission-checkbox');
                const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
                moduleCheckboxes.forEach(cb => cb.checked = !allChecked);
                this.textContent = allChecked ? '{{ __('Select module') }}' : '{{ __('Deselect module') }}';
            });
        });
    })();
</script>
@endpush
@endsection

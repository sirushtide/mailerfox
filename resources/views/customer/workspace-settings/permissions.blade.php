@extends('layouts.customer')

@section('title', __('Permissions') . ' — ' . __('Workspace Settings'))
@section('page-title', '')

@section('breadcrumbs')
<ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
    <li><a href="{{ wsRoute('customer.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Dashboard') }}</a></li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li><a href="{{ wsRoute('customer.workspace-settings.general') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Workspace Settings') }}</a></li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li class="font-medium text-admin-text-primary truncate">{{ __('Permissions') }}</li>
</ol>
@endsection

@section('content')
<div class="mx-auto max-w-3xl">
    @include('customer.workspace-settings._tabs', ['currentTab' => 'permissions'])

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Roles & Permissions') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Define roles and what each role can access. Only the workspace owner can manage permissions.') }}</p>
            </div>
        </div>

        {{-- Create new role --}}
        <div x-data="{ showCreate: false }" class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Create a new role') }}</h3>
                <button @click="showCreate = !showCreate" type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    {{ __('New Role') }}
                </button>
            </div>

            <form x-show="showCreate" x-cloak method="POST" action="{{ wsRoute('customer.workspace-settings.roles.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="new_role_name" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Role Name') }}</label>
                    <input type="text" name="name" id="new_role_name" required
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#1E5FEA] focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="{{ __('e.g. Marketing Manager') }}">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Permissions') }}</label>
                    <div class="space-y-3">
                        @foreach($permissionGroups as $groupName => $perms)
                        <div>
                            <p class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __($groupName) }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($perms as $perm)
                                <label class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 dark:border-gray-600 dark:text-gray-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm }}" class="rounded border-gray-300 text-[#1E5FEA] focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700">
                                    {{ str_replace('_', ' ', str_replace('.', ' › ', $perm)) }}
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-[#1E5FEA] focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700">
                    {{ __('Set as default role for new members') }}
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        {{ __('Create Role') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Existing roles --}}
        @foreach($roles as $role)
        <div x-data="{ editing: false }" class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $role->name }}
                        @if($role->is_default)
                            <span class="ml-1.5 inline-flex items-center rounded-full bg-[#1E5FEA]/10 px-2 py-0.5 text-[10px] font-medium text-[#1E5FEA]">{{ __('Default') }}</span>
                        @endif
                    </h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $role->members_count }} {{ __('members') }} &middot; {{ count((array)$role->permissions) }} {{ __('permissions') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="editing = !editing" type="button"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        <span x-text="editing ? '{{ __('Cancel') }}' : '{{ __('Edit') }}'"></span>
                    </button>
                    @if($role->members_count === 0)
                    <form method="POST" action="{{ wsRoute('customer.workspace-settings.roles.destroy', $role) }}" onsubmit="return confirm('{{ __('Delete this role?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-600 shadow-sm transition hover:bg-rose-50 dark:border-rose-700 dark:bg-gray-700 dark:text-rose-400 dark:hover:bg-rose-900/20">
                            {{ __('Delete') }}
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Permission badges (view mode) --}}
            <div x-show="!editing" class="mt-3 flex flex-wrap gap-1">
                @foreach((array)$role->permissions as $perm)
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">{{ $perm }}</span>
                @endforeach
                @if(empty($role->permissions))
                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">{{ __('No permissions assigned') }}</span>
                @endif
            </div>

            {{-- Edit form --}}
            <form x-show="editing" x-cloak method="POST" action="{{ wsRoute('customer.workspace-settings.roles.update', $role) }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Role Name') }}</label>
                    <input type="text" name="name" value="{{ $role->name }}" required
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#1E5FEA] focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Permissions') }}</label>
                    <div class="space-y-3">
                        @foreach($permissionGroups as $groupName => $perms)
                        <div>
                            <p class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __($groupName) }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($perms as $perm)
                                <label class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 dark:border-gray-600 dark:text-gray-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                                           {{ in_array($perm, (array)$role->permissions) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-[#1E5FEA] focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700">
                                    {{ str_replace('_', ' ', str_replace('.', ' › ', $perm)) }}
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="is_default" value="1" {{ $role->is_default ? 'checked' : '' }}
                           class="rounded border-gray-300 text-[#1E5FEA] focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700">
                    {{ __('Set as default role for new members') }}
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        {{ __('Save Changes') }}
                    </button>
                </div>
            </form>
        </div>
        @endforeach

        @if($roles->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-600 dark:bg-gray-800/50">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No roles defined yet. Create one to get started.') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection

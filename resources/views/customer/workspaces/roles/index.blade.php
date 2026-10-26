@extends('layouts.customer')

@section('title', __('Roles') . ' — ' . $workspace->name)

@section('page-title', $workspace->name . ' — ' . __('Roles & Permissions'))

@section('page-actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('customer.workspaces.show', $workspace) }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            {{ __('Back') }}
        </a>
        <a href="{{ route('customer.workspaces.roles.create', $workspace) }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            {{ __('Create Role') }}
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-4">
    @foreach($roles as $role)
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $role->name }}
                    @if($role->is_default)
                        <span class="ml-1.5 inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">{{ __('Default') }}</span>
                    @endif
                </h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $role->members_count }} {{ __('members') }} &middot; {{ count((array)$role->permissions) }} {{ __('permissions') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('customer.workspaces.roles.edit', [$workspace, $role]) }}"
                   class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    {{ __('Edit') }}
                </a>
                @if($role->members_count === 0)
                <form method="POST" action="{{ route('customer.workspaces.roles.destroy', [$workspace, $role]) }}" onsubmit="return confirm('{{ __('Delete this role?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-600 shadow-sm transition hover:bg-rose-50 dark:border-rose-700 dark:bg-gray-700 dark:text-rose-400 dark:hover:bg-rose-900/20">
                        {{ __('Delete') }}
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Permission badges --}}
        <div class="mt-3 flex flex-wrap gap-1">
            @foreach((array)$role->permissions as $perm)
                <span class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">{{ $perm }}</span>
            @endforeach
        </div>
    </div>
    @endforeach

    @if($roles->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-600 dark:bg-gray-800/50">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No roles defined yet.') }}</p>
        </div>
    @endif
</div>
@endsection

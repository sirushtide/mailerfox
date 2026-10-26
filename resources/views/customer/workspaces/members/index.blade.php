@extends('layouts.customer')

@section('title', __('Members') . ' — ' . $workspace->name)

@section('page-title', $workspace->name . ' — ' . __('Members'))

@section('page-actions')
    <a href="{{ route('customer.workspaces.show', $workspace) }}"
       class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
        {{ __('Back') }}
    </a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Invite form --}}
    @php
        $customer = auth('customer')->user();
        $canManage = $workspace->isOwner($customer) || ($workspace->memberForCustomer($customer)?->hasPermission('members.manage'));
    @endphp

    @if($canManage)
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Invite a New Member') }}</h3>
        <form method="POST" action="{{ route('customer.workspaces.members.invite', $workspace) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label for="email" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Email Address') }}</label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}"
                       class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                       placeholder="{{ __('colleague@example.com') }}">
            </div>
            <div class="w-full sm:w-48">
                <label for="role_id" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Role') }}</label>
                <select name="role_id" id="role_id"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ $role->is_default ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 whitespace-nowrap">
                {{ __('Send Invite') }}
            </button>
        </form>
    </div>
    @endif

    {{-- Active members --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Members') }} ({{ $members->count() }})</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($members as $member)
            <div class="flex items-center justify-between px-5 py-3.5">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600 dark:bg-gray-600 dark:text-gray-200">
                        {{ strtoupper(substr($member->displayName(), 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $member->displayName() }}
                            @if($member->isOwner())
                                <span class="ml-1 inline-flex items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('Owner') }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $member->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ $member->role?->name ?? '—' }}
                    </span>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $member->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                        {{ ucfirst($member->status) }}
                    </span>

                    @if($canManage && !$member->isOwner())
                    <div class="flex items-center gap-1" x-data="{ showRoleForm: false }">
                        <button @click="showRoleForm = !showRoleForm" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300" title="{{ __('Change Role') }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                        </button>
                        <form method="POST" action="{{ route('customer.workspaces.members.remove', [$workspace, $member]) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded p-1 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30 dark:hover:text-rose-400" title="{{ __('Remove') }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </form>

                        {{-- Inline role change form --}}
                        <form x-show="showRoleForm" x-cloak method="POST" action="{{ route('customer.workspaces.members.update-role', [$workspace, $member]) }}" class="flex items-center gap-2 ml-2">
                            @csrf
                            @method('PUT')
                            <select name="role_id" class="rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ (int)$member->workspace_role_id === (int)$role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded bg-primary-600 px-2 py-1 text-xs font-medium text-white hover:bg-primary-700">{{ __('Save') }}</button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No members yet.') }}</div>
            @endforelse
        </div>
    </div>

    {{-- Pending invitations --}}
    @if($pendingInvitations->isNotEmpty())
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Pending Invitations') }} ({{ $pendingInvitations->count() }})</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($pendingInvitations as $inv)
            <div class="flex items-center justify-between px-5 py-3.5">
                <div class="min-w-0">
                    <p class="text-sm text-gray-900 dark:text-white truncate">{{ $inv->email }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Expires') }} {{ $inv->expires_at->diffForHumans() }} &middot; {{ __('Role:') }} {{ $inv->role?->name ?? '—' }}</p>
                </div>
                @if($canManage)
                <form method="POST" action="{{ route('customer.workspaces.invitations.cancel', [$workspace, $inv]) }}" onsubmit="return confirm('{{ __('Cancel this invitation?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        {{ __('Cancel') }}
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection

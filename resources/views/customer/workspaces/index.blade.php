@extends('layouts.customer')

@section('title', __('Workspaces'))

@section('page-title', __('Workspaces'))

@section('page-actions')
    <a href="{{ route('customer.workspaces.create') }}"
       class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 w-full sm:w-auto">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        {{ __('Create Workspace') }}
    </a>
@endsection

@section('content')
<div class="space-y-8">

    {{-- Active workspace banner --}}
    @if(session('active_workspace_id'))
        @php $activeWs = \App\Models\Workspace::find(session('active_workspace_id')); @endphp
        @if($activeWs)
        <div class="flex items-center justify-between rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-900/30">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600 text-white font-bold text-sm">
                    {{ strtoupper(substr($activeWs->name, 0, 2)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-primary-900 dark:text-primary-100">{{ __('Active Workspace') }}</p>
                    <p class="text-sm text-primary-700 dark:text-primary-300">{{ $activeWs->name }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('customer.workspaces.switch-personal') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-primary-300 bg-white px-3 py-1.5 text-xs font-medium text-primary-700 shadow-sm transition hover:bg-primary-50 dark:border-primary-600 dark:bg-primary-800 dark:text-primary-200 dark:hover:bg-primary-700">
                    {{ __('Switch to Personal') }}
                </button>
            </form>
        </div>
        @endif
    @endif

    {{-- Owned workspaces --}}
    <div>
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('My Workspaces') }}</h2>

        @if($ownedWorkspaces->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-600 dark:bg-gray-800/50">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/></svg>
                <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-white">{{ __('No workspaces yet') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Create a workspace to start collaborating with your team.') }}</p>
                <a href="{{ route('customer.workspaces.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    {{ __('Create Workspace') }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($ownedWorkspaces as $ws)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600 text-white font-bold text-sm">
                                    {{ strtoupper(substr($ws->name, 0, 2)) }}
                                </div>
                                <div>
                                    <a href="{{ route('customer.workspaces.show', $ws) }}" class="text-sm font-semibold text-gray-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400">
                                        {{ $ws->name }}
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $ws->members_count }} {{ __('members') }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Owner') }}</span>
                        </div>
                        @if($ws->description)
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $ws->description }}</p>
                        @endif
                        <div class="mt-4 flex items-center gap-2">
                            <form method="POST" action="{{ route('customer.workspaces.switch', $ws) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                    {{ session('active_workspace_id') == $ws->id ? __('Active') : __('Switch') }}
                                </button>
                            </form>
                            <a href="{{ route('customer.workspaces.show', $ws) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                {{ __('Manage') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Member workspaces --}}
    @if($memberWorkspaces->isNotEmpty())
    <div>
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('Workspaces I Belong To') }}</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($memberWorkspaces as $ws)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-600 text-white font-bold text-sm">
                                {{ strtoupper(substr($ws->name, 0, 2)) }}
                            </div>
                            <div>
                                <a href="{{ route('customer.workspaces.show', $ws) }}" class="text-sm font-semibold text-gray-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400">
                                    {{ $ws->name }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $ws->members_count }} {{ __('members') }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">{{ __('Member') }}</span>
                    </div>
                    <div class="mt-4">
                        <form method="POST" action="{{ route('customer.workspaces.switch', $ws) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                {{ session('active_workspace_id') == $ws->id ? __('Active') : __('Switch') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection

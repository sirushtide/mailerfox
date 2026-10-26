@extends('layouts.customer')

@section('title', __('Supabase'))
@section('page-title', __(''))

@push('content-padding-classes')
    p-4 sm:p-6 max-w-[640px] mx-auto
@endpush

@section('content')
<div class="space-y-6 w-[450px] mx-auto" x-data="{ loadingTables: false, loadingBuckets: false }">
    @if(session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/30 px-4 py-3 text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-emerald-600 dark:text-emerald-400"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Supabase') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manage your database and storage connection.') }}</p>
        </div>
    </div>

    @if($integration && $integration->status === 'connected')
        <x-card>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-green-500"></span>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Connected') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $integration->mode === 'admin_managed' ? __('Admin-managed') : __('BYOS (Bring Your Own Supabase)') }}
                            &middot; {{ $integration->project_url }}
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('customer.supabase.disconnect') }}" onsubmit="return confirm('{{ __('Disconnect Supabase?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400">{{ __('Disconnect') }}</button>
                </form>
            </div>
        </x-card>

        <x-card>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Storage & Tables') }}</h3>

            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Storage Buckets') }}</h4>
                    <button type="button" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400" @click="loadingBuckets = true; fetch('{{ route('customer.supabase.buckets') }}').then(r => r.json()).then(d => { loadingBuckets = false; $refs.bucketsList.innerHTML = d.success ? d.data.map(b => '<div class=\'flex items-center justify-between py-1.5 text-sm\'><span>' + b.name + '</span><span class=\'text-xs text-gray-400\'>' + (b.public ? 'Public' : 'Private') + '</span></div>').join('') : '<p class=\'text-sm text-red-500\'>' + (d.error || 'Failed') + '</p>' })">
                        <span x-show="!loadingBuckets">{{ __('Refresh') }}</span>
                        <span x-show="loadingBuckets" class="inline-block h-3 w-3 rounded-full border-2 border-gray-300 border-t-gray-900 animate-spin"></span>
                    </button>
                </div>
                <div x-ref="bucketsList" class="divide-y divide-gray-100 dark:divide-gray-700">
                    @if($adminBuckets && is_array($adminBuckets))
                        @foreach($adminBuckets as $bucket)
                            <div class="flex items-center justify-between py-1.5 text-sm">
                                <span class="text-gray-700 dark:text-gray-300">{{ $bucket['name'] ?? '—' }}</span>
                                <span class="text-xs text-gray-400">{{ !empty($bucket['public']) ? 'Public' : 'Private' }}</span>
                            </div>
                        @endforeach
                        @if(count($adminBuckets) === 0)
                            <p class="py-2 text-sm text-gray-400">{{ __('No storage buckets found.') }}</p>
                        @endif
                    @else
                        <p class="py-2 text-sm text-gray-400">{{ $adminBuckets === null ? __('Unable to load buckets.') : __('No storage buckets found.') }}</p>
                    @endif
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Database Tables') }}</h4>
                    <button type="button" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400" @click="loadingTables = true; fetch('{{ route('customer.supabase.tables') }}').then(r => r.json()).then(d => { loadingTables = false; $refs.tablesList.innerHTML = d.success ? d.data.map(t => '<div class=\'py-1.5 text-sm text-gray-700 dark:text-gray-300\'>' + t.table_name + '</div>').join('') : '<p class=\'text-sm text-red-500\'>' + (d.error || 'Failed') + '</p>' })">
                        <span x-show="!loadingTables">{{ __('Refresh') }}</span>
                        <span x-show="loadingTables" class="inline-block h-3 w-3 rounded-full border-2 border-gray-300 border-t-gray-900 animate-spin"></span>
                    </button>
                </div>
                <div x-ref="tablesList" class="divide-y divide-gray-100 dark:divide-gray-700">
                    @if($adminTables && is_array($adminTables))
                        @foreach($adminTables as $table)
                            <div class="py-1.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $table['table_name'] ?? '—' }}
                            </div>
                        @endforeach
                        @if(count($adminTables) === 0)
                            <p class="py-2 text-sm text-gray-400">{{ __('No tables found.') }}</p>
                        @endif
                    @else
                        <p class="py-2 text-sm text-gray-400">{{ $adminTables === null ? __('Unable to load tables.') : __('No tables found.') }}</p>
                    @endif
                </div>
            </div>
        </x-card>
    @else
        @if($integration && $integration->status === 'error')
            <div class="rounded-lg bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                <p class="font-medium">{{ __('Connection error') }}</p>
                <p class="mt-1">{{ $integration->last_error }}</p>
            </div>
        @endif

        @if($allowAdminManaged && $adminConfigs->count() > 0)
            <x-card>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Use Admin Supabase') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Connect to a pre-configured Supabase project managed by the platform admin.') }}</p>
                <form method="POST" action="{{ route('customer.supabase.connect-admin') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="admin_supabase_config_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Select project') }}</label>
                        <select name="admin_supabase_config_id" id="admin_supabase_config_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" required>
                            @foreach($adminConfigs as $cfg)
                                <option value="{{ $cfg->id }}">{{ $cfg->name }}{{ $cfg->is_default ? ' (Default)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="primary">{{ __('Connect') }}</x-button>
                    </div>
                </form>
            </x-card>
        @endif

        @if($allowByos && $allowByosManual)
            <x-card class="rounded-md">
                <form method="POST" action="{{ route('customer.supabase.store-byos') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="project_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Project URL') }}</label>
                        <input type="url" name="project_url" id="project_url" value="{{ old('project_url') }}" placeholder="https://xxxxxxxx.supabase.co" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" required />
                        @error('project_url')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="publishable_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Publishable Key') }}</label>
                        <input type="text" name="publishable_key" id="publishable_key" value="{{ old('publishable_key') }}" placeholder="sb_publishable_..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" required />
                        @error('publishable_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Secret Key') }}</label>
                        <input type="text" name="secret_key" id="secret_key" value="{{ old('secret_key') }}" placeholder="sb_secret_..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" required />
                        @error('secret_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="primary">{{ __('Connect') }}</x-button>
                    </div>
                </form>
            </x-card>
        @endif

        @if(!$allowAdminManaged && !$allowByos)
            <x-card>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Supabase integration is not available for your plan. Please upgrade to use this feature.') }}</p>
                <div class="mt-4">
                    <x-button href="{{ wsRoute('customer.billing.index') }}" variant="primary">{{ __('Upgrade') }}</x-button>
                </div>
            </x-card>
        @endif
    @endif
</div>
@endsection

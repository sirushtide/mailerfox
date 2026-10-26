@extends('layouts.customer')
@section('title', __('UniBox — Accounts'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Email Accounts') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Connected Gmail, Outlook, and SMTP delivery servers are automatically fetched here for your unified inbox.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.unibox.index') }}" class="flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-white/10 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <x-lucide name="arrow-left" class="w-4 h-4" />
                {{ __('Back to Inbox') }}
            </a>
            <a href="{{ route('customer.unibox.accounts.create') }}" data-track="unibox.connect" class="flex items-center gap-1.5 px-4 py-1.5 bg-[#1E5FEA] hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                <x-lucide name="plus" class="w-4 h-4" />
                {{ __('Connect Account') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-500/30 text-sm text-green-700 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if($accounts->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-gray-200 dark:border-white/10 rounded-xl text-center">
            <x-lucide name="mail" class="w-12 h-12 text-gray-200 dark:text-gray-700 mb-4" />
            <h3 class="text-base font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('No accounts connected') }}</h3>
            <p class="text-sm text-gray-400 dark:text-gray-600 mb-5">{{ __('Add a delivery server or connect a manual inbox account to start receiving messages here.') }}</p>
            <a href="{{ route('customer.unibox.accounts.create') }}" data-track="unibox.connect" class="px-5 py-2 bg-[#1E5FEA] hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                {{ __('Connect Account') }}
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($accounts as $account)
            <div class="flex items-center justify-between px-5 py-4 bg-white dark:bg-[#1a1a1a] border border-gray-100 dark:border-white/10 rounded-xl">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($account->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $account->name }}</p>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                @if($account->status === 'active') bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400
                                @elseif($account->status === 'error') bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400
                                @else bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-400 @endif">
                                {{ ucfirst($account->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $account->email }}</p>
                        @if($account->last_synced_at)
                            <p class="text-xs text-gray-400 dark:text-gray-600 mt-0.5">{{ __('Last synced') }}: {{ $account->last_synced_at->diffForHumans() }}</p>
                        @endif
                        @if($account->last_error)
                            <p class="text-xs text-red-500 mt-0.5">{{ $account->last_error }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('customer.unibox.accounts.sync', $account) }}">
                        @csrf
                        <button type="submit" class="p-2 text-gray-400 hover:text-[#1E5FEA] rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors" title="{{ __('Sync Now') }}">
                            <x-lucide name="refresh-cw" class="w-4 h-4" />
                        </button>
                    </form>
                    <a href="{{ route('customer.unibox.accounts.edit', $account) }}" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors" title="{{ __('Edit') }}">
                        <x-lucide name="pencil" class="w-4 h-4" />
                    </a>
                    <form method="POST" action="{{ route('customer.unibox.accounts.destroy', $account) }}" onsubmit="return confirm('{{ __('Remove this account?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors" title="{{ __('Remove') }}">
                            <x-lucide name="trash-2" class="w-4 h-4" />
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

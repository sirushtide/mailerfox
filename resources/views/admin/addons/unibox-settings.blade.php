@extends('layouts.admin')
@section('title', __('UniBox Settings'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('UniBox Settings') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Global configuration for the UniBox unified inbox addon.') }}</p>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-500/30 text-sm text-green-700 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.unibox.settings.update') }}">
        @csrf @method('PUT')

        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-100 dark:border-white/10 rounded-xl p-5 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Sync Interval (minutes)') }}</label>
                <input type="number" name="unibox_sync_interval" value="{{ $settings['unibox_sync_interval'] }}" min="1" max="60"
                    class="w-full max-w-xs px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">{{ __('How often to poll IMAP accounts for new emails.') }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Max Accounts Per Customer') }}</label>
                <input type="number" name="unibox_max_accounts_per_customer" value="{{ $settings['unibox_max_accounts_per_customer'] }}" min="1" max="50"
                    class="w-full max-w-xs px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Default SLA (hours)') }}</label>
                <input type="number" name="unibox_sla_hours" value="{{ $settings['unibox_sla_hours'] }}" min="1" max="72"
                    class="w-full max-w-xs px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">{{ __('Default SLA deadline set on new inbound conversations.') }}</p>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="unibox_ai_enabled" value="0">
                <input type="checkbox" name="unibox_ai_enabled" id="unibox_ai_enabled" value="1" {{ $settings['unibox_ai_enabled'] === '1' ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-gray-300 text-[#1E5FEA] focus:ring-[#1E5FEA]">
                <label for="unibox_ai_enabled" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Enable AI Assist (uses platform AI credits)') }}</label>
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="px-5 py-2 bg-[#1E5FEA] hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                {{ __('Save Settings') }}
            </button>
        </div>
    </form>
</div>
@endsection

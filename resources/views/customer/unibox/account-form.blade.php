@extends('layouts.customer')
@section('title', isset($account) ? __('Edit Account') : __('Connect Account'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('customer.unibox.accounts.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ isset($account) ? __('Edit Account') : __('Connect Email Account') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('IMAP for receiving, SMTP for sending.') }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-500/30 text-sm text-red-700 dark:text-red-400">
            <ul class="space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('success'))
        <div class="mb-5 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-500/30 text-sm text-green-700 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ isset($account) ? route('customer.unibox.accounts.update', $account) : route('customer.unibox.accounts.store') }}" class="space-y-6">
        @csrf
        @if(isset($account)) @method('PUT') @endif

        {{-- Basic Info --}}
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-100 dark:border-white/10 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-white">{{ __('Account Info') }}</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Display Name') }} *</label>
                    <input type="text" name="name" value="{{ old('name', $account->name ?? '') }}" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Email Address') }} *</label>
                    <input type="email" name="email" value="{{ old('email', $account->email ?? '') }}" {{ isset($account) ? 'readonly' : 'required' }}
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent {{ isset($account) ? 'opacity-60 cursor-not-allowed' : '' }}">
                </div>
            </div>
            @if(!isset($account))
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Account Type') }}</label>
                <select name="type" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
                    <option value="imap" {{ old('type','imap') === 'imap' ? 'selected' : '' }}>IMAP / SMTP (Generic)</option>
                    <option value="gmail" {{ old('type') === 'gmail' ? 'selected' : '' }}>Gmail</option>
                    <option value="outlook" {{ old('type') === 'outlook' ? 'selected' : '' }}>Outlook / Office 365</option>
                </select>
            </div>
            @endif
        </div>

        {{-- IMAP --}}
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-100 dark:border-white/10 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-white">{{ __('Incoming Mail (IMAP)') }}</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('IMAP Host') }} *</label>
                    <input type="text" name="imap_host" value="{{ old('imap_host', $account->imap_host ?? '') }}" placeholder="imap.example.com" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Port') }} *</label>
                    <input type="number" name="imap_port" value="{{ old('imap_port', $account->imap_port ?? 993) }}" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Encryption') }} *</label>
                    <select name="imap_encryption" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
                        <option value="ssl" {{ old('imap_encryption', $account->imap_encryption ?? 'ssl') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="tls" {{ old('imap_encryption', $account->imap_encryption ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="none" {{ old('imap_encryption', $account->imap_encryption ?? '') === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Username') }} *</label>
                    <input type="text" name="imap_username" value="{{ old('imap_username', $account->imap_username ?? '') }}" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Password') }} {{ isset($account) ? __('(leave blank to keep)') : '*' }}</label>
                    <input type="password" name="imap_password" {{ !isset($account) ? 'required' : '' }} autocomplete="new-password"
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 -my-2">
            <input type="checkbox" id="same-as-imap" class="w-4 h-4 rounded border-gray-300 text-[#1E5FEA] focus:ring-[#1E5FEA]"
                @if(!isset($account)) checked @endif>
            <label for="same-as-imap" class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer select-none">{{ __('SMTP uses same credentials as IMAP') }}</label>
        </div>

        {{-- SMTP --}}
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-100 dark:border-white/10 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-white">{{ __('Outgoing Mail (SMTP)') }}</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('SMTP Host') }} *</label>
                    <input type="text" name="smtp_host" value="{{ old('smtp_host', $account->smtp_host ?? '') }}" placeholder="smtp.example.com" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Port') }} *</label>
                    <input type="number" name="smtp_port" value="{{ old('smtp_port', $account->smtp_port ?? 587) }}" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Encryption') }} *</label>
                    <select name="smtp_encryption" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
                        <option value="tls" {{ old('smtp_encryption', $account->smtp_encryption ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ old('smtp_encryption', $account->smtp_encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="none" {{ old('smtp_encryption', $account->smtp_encryption ?? '') === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Username') }} *</label>
                    <input type="text" name="smtp_username" value="{{ old('smtp_username', $account->smtp_username ?? '') }}" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Password') }} {{ isset($account) ? __('(leave blank to keep)') : '*' }}</label>
                    <input type="password" name="smtp_password" {{ !isset($account) ? 'required' : '' }} autocomplete="new-password"
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            @if(!isset($account))
            <button type="submit" name="test_connection" value="1" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 6 0m-6 0H2.25m11.25 0a3 3 0 0 0 3-3m-3 3a3 3 0 1 1 6 0m-6 0h6.75m-11.25-3a3 3 0 0 1 3-3m0 0a3 3 0 0 1 3 3m-3-3V2.25"/></svg>
                {{ __('Test IMAP Connection') }}
            </button>
            @else
            <span></span>
            @endif
            <button type="submit" class="px-5 py-2 bg-[#1E5FEA] hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                {{ isset($account) ? __('Save Changes') : __('Connect Account') }}
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const checkbox = document.getElementById('same-as-imap');
    if (!checkbox) return;

    const imapHost = document.querySelector('input[name="imap_host"]');
    const imapPort = document.querySelector('input[name="imap_port"]');
    const imapEnc = document.querySelector('select[name="imap_encryption"]');
    const imapUser = document.querySelector('input[name="imap_username"]');
    const imapPass = document.querySelector('input[name="imap_password"]');

    const smtpHost = document.querySelector('input[name="smtp_host"]');
    const smtpPort = document.querySelector('input[name="smtp_port"]');
    const smtpEnc = document.querySelector('select[name="smtp_encryption"]');
    const smtpUser = document.querySelector('input[name="smtp_username"]');
    const smtpPass = document.querySelector('input[name="smtp_password"]');

    function deriveSmtpHost(host) {
        host = (host || '').trim();
        if (!host) return '';
        // Common provider swaps
        if (host === 'imap.gmail.com') return 'smtp.gmail.com';
        if (host === 'outlook.office365.com') return 'smtp.office365.com';
        // Generic imap. -> smtp.
        if (host.startsWith('imap.')) return 'smtp.' + host.substring(5);
        return host;
    }

    function deriveSmtpPort(enc) {
        // If IMAP uses SSL (993), SMTP usually uses 465 for SSL or 587 for TLS.
        // Default to 587 for TLS/STARTTLS, 465 for SSL.
        return enc === 'ssl' ? 465 : 587;
    }

    function copyFromImap() {
        if (!checkbox.checked) return;
        if (smtpHost) {
            const derived = deriveSmtpHost(imapHost?.value);
            if (derived) smtpHost.value = derived;
        }
        if (smtpPort) {
            smtpPort.value = deriveSmtpPort(imapEnc?.value);
        }
        if (smtpEnc && imapEnc) smtpEnc.value = imapEnc.value;
        if (smtpUser && imapUser) smtpUser.value = imapUser.value;
        if (smtpPass && imapPass) smtpPass.value = imapPass.value;
    }

    checkbox.addEventListener('change', copyFromImap);

    [imapHost, imapPort, imapEnc, imapUser, imapPass].forEach(el => {
        if (el) el.addEventListener('input', copyFromImap);
    });

    // Initialize on load
    copyFromImap();
})();
</script>
@endsection

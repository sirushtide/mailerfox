<template x-teleport="body">
<div x-cloak x-show="addInboxDrawerOpen" class="fixed inset-0 z-[100]" @keydown.window.escape="addInboxDrawerOpen = false">
<div x-show="addInboxDrawerOpen" x-transition.opacity class="absolute inset-0 bg-black/30 backdrop-blur-[1px]" @click="addInboxDrawerOpen = false"></div>
<aside x-show="addInboxDrawerOpen" x-transition:enter="transform transition ease-out duration-300" x-transition:enter-start="translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100" x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="translate-x-full opacity-0" class="absolute top-0 right-0 h-[100dvh] w-full sm:w-[520px] xl:w-[640px] overflow-hidden bg-white dark:bg-[#1a1a1a] shadow-2xl flex flex-col border-l border-gray-200 dark:border-white/10" @click.stop>

<div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100 dark:border-white/10">
<div><h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Connect Inbox') }}</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Choose a provider to connect your email account.') }}</p></div>
<button type="button" @click="addInboxDrawerOpen = false" class="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-white/5 dark:hover:text-white transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
</div>

<div class="shrink-0 px-6 py-4 border-b border-gray-100 dark:border-white/10">
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Provider') }}</label>
<select x-model="addInboxProvider" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
<option value="gmail">Gmail (Google OAuth)</option>
<option value="outlook">Outlook / Office 365</option>
<option value="custom">{{ __('Custom SMTP / IMAP') }}</option>
</select>
</div>
</div>

<div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-6 space-y-6" style="-webkit-overflow-scrolling: touch;">

<div x-show="addInboxProvider === 'gmail'" x-transition class="space-y-4">
<div class="pt-5">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center">
<svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
<path fill="#4285F4" d="M22.5 18V7.227l-7.56 5.887V18z"/>
<path fill="#34A853" d="M16.5 18H22.5l-7.56-5.886-2.94 2.288L9.06 12.114 1.5 18h15z"/>
<path fill="#FBBC04" d="M1.5 18V7.227l7.56 5.887V18z"/>
<path fill="#EA4335" d="M1.5 6.75V7.227l7.56 5.887L12 15.402l2.94-2.288 7.56-5.887V6.75A1.75 1.75 0 0 0 20.75 5h-17.5A1.75 1.75 0 0 0 1.5 6.75z"/>
</svg>
</div>
<div><h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Connect Gmail') }}</h3><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Authenticate with Google to connect your Gmail inbox.') }}</p></div>
</div>
<div class="space-y-3">
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Account Name') }}</label>
<input id="unibox-gmail-account-name" x-model="addInboxGmailName" type="text" name="name" placeholder="{{ __('e.g. My Gmail') }}" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<button type="button" @click="connectGmailOAuth()" class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#1E5FEA] px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
{{ __('Connect with Google') }}
</button>
</div>
</div>
</div>

<div x-show="addInboxProvider === 'outlook'" x-transition class="space-y-4">
<div>
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M21.17 3.25Q21.5 3.25 21.76 3.5 22 3.74 22 4.08V19.92Q22 20.26 21.76 20.5 21.5 20.75 21.17 20.75H7.83Q7.5 20.75 7.24 20.5 7 20.26 7 19.92V17H2.83Q2.5 17 2.24 16.76 2 16.5 2 16.17V7.83Q2 7.5 2.24 7.24 2.5 7 2.83 7H7V4.08Q7 3.74 7.24 3.5 7.5 3.25 7.83 3.25M7 13.06L8.18 15.28H9.97L8 12.06L9.93 8.89H8.22L7.13 10.9L7.09 10.96L7.06 11.03Q6.8 10.5 6.03 9.23Q5.03 7.63 4.7 7.13H2.97V15.28H4.7V10.57L6.14 13.28H6.72M13.88 19.5V17H8.25V19.5M13.88 15.75V12.63H12V15.75M13.88 11.38V8.25H12V11.38M13.88 7V4.5H8.25V7M20.75 19.5V17H15.13V19.5M20.75 15.75V12.63H15.13V15.75M20.75 11.38V8.25H15.13V11.38M20.75 7V4.5H15.13V7Z"/></svg>
</div>
<div><h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Connect Outlook') }}</h3><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Enter your Outlook credentials. IMAP settings are pre-filled.') }}</p></div>
</div>
<form method="POST" action="{{ route('customer.unibox.accounts.store') }}" class="space-y-3" onsubmit="this.smtp_username.value = this.imap_username.value; this.smtp_password.value = this.imap_password.value;">
@csrf
<input type="hidden" name="type" value="outlook">
<input type="hidden" name="imap_host" value="outlook.office365.com">
<input type="hidden" name="imap_port" value="993">
<input type="hidden" name="imap_encryption" value="tls">
<input type="hidden" name="smtp_host" value="smtp.office365.com">
<input type="hidden" name="smtp_port" value="587">
<input type="hidden" name="smtp_encryption" value="tls">
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Display Name') }} *</label>
<input type="text" name="name" placeholder="{{ __('e.g. Work Outlook') }}" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Email Address') }} *</label>
<input type="email" name="email" placeholder="you@outlook.com" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Username') }} *</label>
<input type="text" name="imap_username" placeholder="you@outlook.com" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('App Password') }} *</label>
<input type="password" name="imap_password" placeholder="{{ __('Your Outlook app password') }}" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<input type="hidden" name="smtp_username" value="">
<input type="hidden" name="smtp_password" value="">
</div>
<p class="text-xs text-gray-500 dark:text-gray-400"><a href="https://support.microsoft.com/en-us/account-billing/using-app-passwords-with-apps-that-don-t-support-two-step-verification-5896ed9b-4263-e681-128a-a6f2979a7944" target="_blank" class="underline text-[#1E5FEA]">{{ __('How to generate an Outlook app password') }}</a></p>
<button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#1E5FEA] px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
{{ __('Connect Account') }}
</button>
</form>
</div>
</div>

<div x-show="addInboxProvider === 'custom'" x-transition class="space-y-4 overflow-y-scroll">
<div class="p-5">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 flex items-center justify-center text-gray-600 dark:text-gray-300">
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.04a2.25 2.25 0 0 1 2.134 0l7.5 4.04a2.25 2.25 0 0 1 1.183 1.98V19.5Z"/></svg>
</div>
<div><h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Custom SMTP / IMAP') }}</h3><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Enter your full IMAP and SMTP server credentials.') }}</p></div>
</div>
<form method="POST" action="{{ route('customer.unibox.accounts.store') }}" class="space-y-3">
@csrf
<input type="hidden" name="type" value="imap">
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Display Name') }} *</label>
<input type="text" name="name" placeholder="{{ __('e.g. My Server') }}" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Email Address') }} *</label>
<input type="email" name="email" placeholder="you@example.com" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<div class="border-t border-gray-200 dark:border-white/10 pt-4 space-y-3">
<p class="text-xs font-semibold text-gray-700 dark:text-gray-300">IMAP (Incoming)</p>
<div class="grid grid-cols-3 gap-3">
<div class="col-span-2">
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Host') }} *</label>
<input type="text" name="imap_host" placeholder="imap.example.com" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Port') }} *</label>
<input type="number" name="imap_port" placeholder="993" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
</div>
<div class="grid grid-cols-2 gap-3">
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Username') }} *</label>
<input type="text" name="imap_username" placeholder="you@example.com" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Password') }} *</label>
<input type="password" name="imap_password" placeholder="{{ __('Password') }}" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Encryption') }}</label>
<select name="imap_encryption" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
<option value="ssl">SSL</option>
<option value="tls">TLS</option>
<option value="none">None</option>
</select>
</div>
</div>
<div class="border-t border-gray-200 dark:border-white/10 pt-4 space-y-3">
<p class="text-xs font-semibold text-gray-700 dark:text-gray-300">SMTP (Outgoing)</p>
<div class="grid grid-cols-3 gap-3">
<div class="col-span-2">
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Host') }} *</label>
<input type="text" name="smtp_host" placeholder="smtp.example.com" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Port') }} *</label>
<input type="number" name="smtp_port" placeholder="587" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
</div>
<div class="grid grid-cols-2 gap-3">
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Username') }} *</label>
<input type="text" name="smtp_username" placeholder="you@example.com" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Password') }} *</label>
<input type="password" name="smtp_password" placeholder="{{ __('Password') }}" required class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
</div>
</div>
<div>
<label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">{{ __('Encryption') }}</label>
<select name="smtp_encryption" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-white/5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
<option value="ssl">SSL</option>
<option value="tls">TLS</option>
<option value="none">None</option>
</select>
</div>
</div>
<button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#1E5FEA] px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
{{ __('Add Account') }}
</button>
</form>
</div>
</div>
</div>
</aside>
</div>
</template>

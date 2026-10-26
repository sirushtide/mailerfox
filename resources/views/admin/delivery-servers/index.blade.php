@extends('layouts.admin')

@section('title', __('Delivery Servers'))
@section('page-title', __('Delivery Servers'))

@section('content')
<div class="space-y-6" x-data="{ adminDeliveryDrawerOpen: false }" @open-admin-delivery-server-drawer.window="adminDeliveryDrawerOpen = true">
    <!-- Header Actions -->
    <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="w-full lg:flex-1 lg:max-w-lg">
            <form method="GET" action="{{ route('admin.delivery-servers.index') }}" class="flex flex-col gap-2 lg:flex-row">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="{{ __('Search servers...') }}"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
                <select
                    name="type"
                    class="w-full lg:w-auto rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
                    <option value="">{{ __('All Types') }}</option>
                    <option value="smtp" {{ request('type') === 'smtp' ? 'selected' : '' }}>{{ __('SMTP') }}</option>
                    <option value="sendmail" {{ request('type') === 'sendmail' ? 'selected' : '' }}>{{ __('Sendmail') }}</option>
                    <option value="zeptomail" {{ request('type') === 'zeptomail' ? 'selected' : '' }}>{{ __('ZeptoMail') }}</option>
                    <option value="amazon-ses" {{ request('type') === 'amazon-ses' ? 'selected' : '' }}>{{ __('Amazon SES') }}</option>
                    <option value="mailgun" {{ request('type') === 'mailgun' ? 'selected' : '' }}>{{ __('Mailgun') }}</option>
                    <option value="sendgrid" {{ request('type') === 'sendgrid' ? 'selected' : '' }}>{{ __('SendGrid') }}</option>
                    <option value="postmark" {{ request('type') === 'postmark' ? 'selected' : '' }}>{{ __('Postmark') }}</option>
                    <option value="sparkpost" {{ request('type') === 'sparkpost' ? 'selected' : '' }}>{{ __('SparkPost') }}</option>
                    <option value="resend" {{ request('type') === 'resend' ? 'selected' : '' }}>{{ __('Resend') }}</option>
                </select>
                <select
                    name="status"
                    class="w-full lg:w-auto rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                </select>
                <x-button type="submit" variant="primary" class="w-full lg:w-auto">{{ __('Filter') }}</x-button>
            </form>
        </div>
        <x-button type="button" variant="primary" class="w-full lg:w-auto" @click="adminDeliveryDrawerOpen = true">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('Add Server') }}
        </x-button>
    </div>

    <template x-teleport="body">
        <div x-cloak x-show="adminDeliveryDrawerOpen" class="fixed inset-0 z-[100]" @keydown.window.escape="adminDeliveryDrawerOpen = false">
            <div
                x-show="adminDeliveryDrawerOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"
                @click="adminDeliveryDrawerOpen = false"
            ></div>

            <div
                x-show="adminDeliveryDrawerOpen"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="absolute inset-y-0 right-0 w-full max-w-lg bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 shadow-2xl will-change-transform"
            >
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Add Delivery Server') }}</div>
                    <button type="button" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800" @click="adminDeliveryDrawerOpen = false" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="admin-delivery-server-form" method="POST" action="{{ route('admin.delivery-servers.store') }}" class="flex h-[calc(100%-64px)] flex-col">
                @csrf

                <div class="flex-1 overflow-y-auto px-8 py-8 space-y-8">
                    <div>
                        <label for="admin-provider-select" class="block text-base font-medium text-gray-600 dark:text-gray-300">{{ __('Select Provider') }}</label>
                        <select id="admin-provider-select" class="mt-3 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-base">
                            <option value="">{{ __('Choose a provider...') }}</option>
                            <optgroup label="{{ __('Standard SMTP') }}">
                                <option value="gmail" data-flow="smtp" data-provider-name="Gmail">Gmail (SMTP with App Password)</option>
                                <option value="outlook" data-flow="smtp" data-provider-name="Outlook">Outlook (SMTP with App Password)</option>
                                <option value="smtp" data-flow="smtp" data-provider-name="{{ __('Other SMTP') }}">Other SMTP (Custom SMTP server)</option>
                            </optgroup>
                            <optgroup label="{{ __('API Integrations') }}">
                                <option value="mailgun" data-flow="api" data-provider-name="Mailgun">Mailgun (API Key & Domain)</option>
                                <option value="sendgrid" data-flow="api" data-provider-name="SendGrid">SendGrid (API Key)</option>
                                <option value="sparkpost" data-flow="api" data-provider-name="SparkPost">SparkPost (API Key)</option>
                                <option value="resend" data-flow="api" data-provider-name="Resend">Resend (API Key)</option>
                                <option value="amazon-ses" data-flow="api" data-provider-name="Amazon SES">Amazon SES (Access Key & Secret)</option>
                                <option value="postmark" data-flow="api" data-provider-name="Postmark">Postmark (API Token)</option>
                                <option value="zeptomail-api" data-flow="api" data-provider-name="ZeptoMail API">ZeptoMail API (Send Mail Token)</option>
                            </optgroup>
                        </select>
                    </div>

                <div id="admin-config-section" class="hidden border-t border-gray-200 pt-8 dark:border-gray-700">
                    <h3 class="mb-6 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('Configure') }} <span id="admin-selected-provider-name"></span> {{ __('Server') }}</h3>

                    <input type="hidden" name="type" id="admin-selected-type">
                    <input type="hidden" name="flow" id="admin-selected-flow">

                    <div id="admin-gmail-help" class="hidden mb-6 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900 dark:border-blue-900/40 dark:bg-blue-900/10 dark:text-blue-100">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-blue-600 shadow-sm dark:bg-blue-950/60 dark:text-blue-300">
                                <x-lucide name="info" class="h-4 w-4" />
                            </div>
                            <div class="space-y-2">
                                <div class="font-semibold">{{ __('Gmail setup instructions') }}</div>
                                <ol class="list-decimal space-y-1 pl-5">
                                    <li>{{ __('Sign in to your Google account and enable 2-Step Verification.') }}</li>
                                    <li>{{ __('Open Google Account settings and go to Security.') }}</li>
                                    <li>{{ __('Under App passwords, create a new app password for Mail.') }}</li>
                                    <li>{{ __('Use your full Gmail address as Username.') }}</li>
                                    <li>{{ __('Paste the generated app password into the Password field here.') }}</li>
                                </ol>
                                <a href="https://support.google.com/accounts/answer/185833" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-medium text-blue-700 underline dark:text-blue-300">
                                    <span>{{ __('Open Google App Password instructions') }}</span>
                                    <x-lucide name="external-link" class="h-4 w-4" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label for="admin-server-name" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Server Name') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="admin-server-name" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" required>
                        </div>

                        <div id="admin-mailgun-domain-wrap" class="hidden">
                            <label for="admin-mailgun-domain" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Sending Domain') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="settings[domain]" id="admin-mailgun-domain" placeholder="e.g. mg.yourdomain.com" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-mailgun-secret-wrap" class="hidden">
                            <label for="admin-mailgun-secret" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('API Key') }} <span class="text-red-500">*</span></label>
                            <input type="password" name="settings[secret]" id="admin-mailgun-secret" placeholder="key-xxxxxxxxxxxxxxxxxxxx" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <a href="https://app.mailgun.com/settings/api_security" target="_blank" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline">{{ __('Get API key here') }}</a>
                            </p>
                        </div>

                        <div id="admin-sendgrid-wrap" class="hidden md:col-span-2">
                            <label for="admin-sendgrid-api-key" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('API Key') }} <span class="text-red-500">*</span></label>
                            <input type="password" name="settings[api_key]" id="admin-sendgrid-api-key" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>
                        <div id="admin-sendgrid-domain-wrap" class="hidden md:col-span-2">
                            <label for="admin-sendgrid-domain" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Sending Domain') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="settings[sending_domain]" id="admin-sendgrid-domain" placeholder="yourdomain.com" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>
                        <div id="admin-sendgrid-from-email-wrap" class="hidden md:col-span-2">
                            <label for="admin-sendgrid-from-prefix" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('From Email') }} <span class="text-red-500">*</span></label>
                            <div class="mt-2 flex rounded-md shadow-sm">
                                <input type="text" name="from_email_prefix" id="admin-sendgrid-from-prefix" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-lg border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">@</span>
                                <input type="text" id="admin-sendgrid-domain-display" readonly placeholder="yourdomain.com" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm cursor-not-allowed">
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Full from email:') }} <span id="admin-sendgrid-full-from-email" class="font-medium">hello@domain.com</span></p>
                            <input type="hidden" name="from_email" id="admin-sendgrid-from-email">
                        </div>

                        <div id="admin-resend-wrap" class="hidden md:col-span-2">
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <label for="admin-resend-api-key" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('API Key') }} <span class="text-red-500">*</span></label>
                                    <input type="password" name="settings[api_key]" id="admin-resend-api-key" placeholder="re_..." class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                </div>
                                <div>
                                    <label for="admin-resend-endpoint" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Endpoint') }}</label>
                                    <input type="text" name="settings[endpoint]" id="admin-resend-endpoint" value="https://api.resend.com" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                </div>
                            </div>
                        </div>

                        <div id="admin-postmark-wrap" class="hidden md:col-span-2">
                            <label for="admin-postmark-token" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('API Token') }} <span class="text-red-500">*</span></label>
                            <input type="password" name="settings[token]" id="admin-postmark-token" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-sparkpost-wrap" class="hidden md:col-span-2">
                            <label for="admin-sparkpost-secret" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('API Key') }} <span class="text-red-500">*</span></label>
                            <input type="password" name="settings[secret]" id="admin-sparkpost-secret" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-smtp-hostname-wrap" class="hidden">
                            <label for="admin-smtp-hostname" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Hostname') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="hostname" id="admin-smtp-hostname" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-smtp-port-wrap" class="hidden">
                            <label for="admin-smtp-port" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Port') }} <span class="text-red-500">*</span></label>
                            <input type="number" name="port" id="admin-smtp-port" min="1" max="65535" value="587" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-smtp-username-wrap" class="hidden">
                            <label for="admin-smtp-username" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Username') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="username" id="admin-smtp-username" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-smtp-password-wrap" class="hidden">
                            <label for="admin-smtp-password" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Password') }} <span class="text-red-500">*</span></label>
                            <input type="password" name="password" id="admin-smtp-password" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-smtp-encryption-wrap" class="hidden">
                            <label for="admin-smtp-encryption" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Encryption') }}</label>
                            <select name="encryption" id="admin-smtp-encryption" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>

                        <div id="admin-ses-key-wrap" class="hidden">
                            <label for="admin-ses-key" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Access Key ID') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="settings[key]" id="admin-ses-key" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-ses-secret-wrap" class="hidden">
                            <label for="admin-ses-secret" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Secret Key') }} <span class="text-red-500">*</span></label>
                            <input type="password" name="settings[secret]" id="admin-ses-secret" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-ses-region-wrap" class="hidden">
                            <label for="admin-ses-region" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Region') }} <span class="text-red-500">*</span></label>
                            <select name="settings[region]" id="admin-ses-region" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <option value="us-east-2">US East (Ohio) -> us-east-2</option>
                                <option value="us-east-1">US East (N. Virginia) -> us-east-1</option>
                                <option value="us-west-2">US West (Oregon) -> us-west-2</option>
                                <option value="ap-south-1">Asia Pacific (Mumbai) -> ap-south-1</option>
                                <option value="ap-northeast-2">Asia Pacific (Seoul) -> ap-northeast-2</option>
                                <option value="ap-southeast-1">Asia Pacific (Singapore) -> ap-southeast-1</option>
                                <option value="ap-southeast-2">Asia Pacific (Sydney) -> ap-southeast-2</option>
                                <option value="ap-northeast-1">Asia Pacific (Tokyo) -> ap-northeast-1</option>
                                <option value="ca-central-1">Canada (Central) -> ca-central-1</option>
                                <option value="eu-central-1">Europe (Frankfurt) -> eu-central-1</option>
                                <option value="eu-west-1">Europe (Ireland) -> eu-west-1</option>
                                <option value="eu-west-2">Europe (London) -> eu-west-2</option>
                                <option value="eu-south-1">Europe (Milan) -> eu-south-1</option>
                                <option value="eu-north-1">Europe (Stockholm) -> eu-north-1</option>
                                <option value="sa-east-1">South America (Sao Paulo) -> sa-east-1</option>
                                <option value="us-gov-west-1">AWS GovCloud (US West) -> us-gov-west-1</option>
                                <option value="us-gov-east-1">AWS GovCloud (US East) -> us-gov-east-1</option>
                            </select>
                        </div>

                        <div id="admin-from-email-wrap" class="hidden">
                            <label for="admin-from-email" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('From Email') }} <span class="text-red-500">*</span></label>
                            <input type="email" name="from_email" id="admin-from-email" placeholder="hello@yourdomain.com" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div id="admin-zepto-token-wrap" class="hidden md:col-span-2">
                            <label for="admin-zepto-token" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Send Mail Token') }} <span class="text-red-500">*</span></label>
                            <input type="password" name="settings[send_mail_token]" id="admin-zepto-token" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div>
                            <label for="admin-status" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Status') }}</label>
                            <select name="status" id="admin-status" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="active">{{ __('Active') }}</option>
                                <option value="inactive">{{ __('Inactive') }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="admin-from-name" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('From Name') }}</label>
                            <input type="text" name="from_name" id="admin-from-name" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div>
                            <label for="admin-daily-quota" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Daily Quota (0 = unlimited)') }}</label>
                            <input type="number" name="daily_quota" id="admin-daily-quota" value="0" min="0" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label for="admin-notes" class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Notes') }}</label>
                            <textarea name="notes" id="admin-notes" rows="4" class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <div class="flex flex-wrap items-center gap-6">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="use_for" value="1" checked class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Use for campaigns') }}</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="use_for_transactional" value="1" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Use for transactional') }}</span>
                                </label>
                            </div>
                        </div>

                        <div id="admin-additional-server-types-wrap" class="hidden md:col-span-2 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Also use this server as') }}</div>
                            <div class="mt-3 flex flex-wrap items-center gap-6">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="use_as_reply_server" value="1" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Reply server') }}</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="use_as_bounce_server" value="1" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Bounce server') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <div class="px-5 py-4 flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" class="px-6 py-3 rounded-xl border border-gray-300 text-gray-800 dark:text-gray-200 dark:border-gray-600" @click="adminDeliveryDrawerOpen = false">{{ __('Cancel') }}</button>
                    <button type="submit" id="admin-save-server-btn" class="px-6 py-3 rounded-xl bg-primary-600 text-white font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled>{{ __('Save Server') }}</button>
                </div>
            </form>
        </div>
        </div>
    </template>

    <x-modal name="pro-feature-extended-mailbox-providers-admin" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Extended License Required</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Gmail and Outlook mailbox providers are available only with the Extended license.
            </p>
            <div class="mt-6 flex justify-end">
                <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'pro-feature-extended-mailbox-providers-admin')">Close</x-button>
            </div>
        </div>
    </x-modal>

    <!-- Servers Table -->
    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Hostname') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Quota') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @if(isset($systemSmtpServer) && $systemSmtpServer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $systemSmtpServer->name }}</div>
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('System') }}</span>
                                </div>
                                @if(!empty($systemSmtpServer->from_email))
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $systemSmtpServer->from_email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ strtoupper(str_replace('-', ' ', $systemSmtpServer->type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ !empty($systemSmtpServer->hostname) ? $systemSmtpServer->hostname . ':' . ($systemSmtpServer->port ?? 587) : __('N/A') }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('Active') }}
                                </span>
                                <span class="ml-2 px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">{{ __('Locked') }}</span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Unlimited') }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    @if(!empty($systemSmtpServer->id))
                                        <x-button href="{{ route('admin.delivery-servers.edit', $systemSmtpServer->id) }}" variant="table" size="action" :pill="true" class="p-2" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"><x-lucide name="pencil" class="h-4 w-4" /><span class="sr-only">{{ __('Edit') }}</span></x-button>
                                    @endif
                                    <x-button href="{{ route('admin.delivery-servers.test') }}" variant="table" size="action" :pill="true">{{ __('Test') }}</x-button>
                                </div>
                            </td>
                        </tr>
                    @endif

                    @forelse($deliveryServers as $server)
                        @if(isset($systemSmtpServer) && $systemSmtpServer && !empty($systemSmtpServer->id) && (int) $server->id === (int) $systemSmtpServer->id)
                            @continue
                        @endif
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $server->name }}</div>
                                    @if($server->is_primary)
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ __('Primary') }}</span>
                                    @endif
                                </div>
                                @if($server->from_email)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $server->from_email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ strtoupper(str_replace('-', ' ', $server->type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $server->hostname ? $server->hostname . ':' . $server->port : __('N/A') }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $server->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($server->status === 'inactive' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                    {{ __(ucfirst($server->status)) }}
                                </span>
                                @if($server->locked)
                                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">{{ __('Locked') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($server->monthly_quota > 0)
                                    {{ __(':count/month', ['count' => number_format($server->monthly_quota)]) }}
                                @else
                                    {{ __('Unlimited') }}
                                @endif
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <x-button href="{{ route('admin.delivery-servers.show', $server) }}" variant="table" size="action" :pill="true" class="p-2" title="{{ __('View') }}" aria-label="{{ __('View') }}"><x-lucide name="eye" class="h-4 w-4" /><span class="sr-only">{{ __('View') }}</span></x-button>
                                    @admincan('admin.delivery_servers.make_primary')
                                        @if(!$server->is_primary)
                                            <form method="POST" action="{{ route('admin.delivery-servers.make-primary', $server) }}" class="inline">
                                                @csrf
                                                <x-button type="submit" variant="table-info" size="action" :pill="true">{{ __('Make Primary') }}</x-button>
                                            </form>
                                        @endif
                                    @endadmincan
                                    <x-button href="{{ route('admin.delivery-servers.edit', $server) }}" variant="table" size="action" :pill="true" class="p-2" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"><x-lucide name="pencil" class="h-4 w-4" /><span class="sr-only">{{ __('Edit') }}</span></x-button>
                                    <form method="POST" action="{{ route('admin.delivery-servers.destroy', $server) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="table-danger" size="action" :pill="true" class="p-2" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}"><x-lucide name="trash-2" class="h-4 w-4" /><span class="sr-only">{{ __('Delete') }}</span></x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No delivery servers found.') }}
                                <a href="{{ route('admin.delivery-servers.create') }}" class="text-primary-600">{{ __('Add your first server') }}</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deliveryServers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $deliveryServers->links() }}
            </div>
        @endif
    </x-card>
</div>

@push('scripts')
<script>
const initAdminDeliveryServerIndex = () => {
    const hasExtendedLicense = @json((bool) ($hasExtendedLicense ?? false));
    const form = document.getElementById('admin-delivery-server-form');
    if (!form) return;

    const providerSelect = document.getElementById('admin-provider-select');
    const configSection = document.getElementById('admin-config-section');
    const selectedType = document.getElementById('admin-selected-type');
    const selectedFlow = document.getElementById('admin-selected-flow');
    const selectedName = document.getElementById('admin-selected-provider-name');
    const saveButton = document.getElementById('admin-save-server-btn');
    const gmailHelp = document.getElementById('admin-gmail-help');

    const fieldIds = [
        'admin-mailgun-domain-wrap',
        'admin-mailgun-secret-wrap',
        'admin-sendgrid-wrap',
        'admin-sendgrid-domain-wrap',
        'admin-sendgrid-from-email-wrap',
        'admin-resend-wrap',
        'admin-postmark-wrap',
        'admin-sparkpost-wrap',
        'admin-smtp-hostname-wrap',
        'admin-smtp-port-wrap',
        'admin-smtp-username-wrap',
        'admin-smtp-password-wrap',
        'admin-smtp-encryption-wrap',
        'admin-ses-key-wrap',
        'admin-ses-secret-wrap',
        'admin-ses-region-wrap',
        'admin-from-email-wrap',
        'admin-zepto-token-wrap',
        'admin-additional-server-types-wrap'
    ];

    const defaults = {
        gmail: { hostname: 'smtp.gmail.com', port: '587', encryption: 'tls' },
        outlook: { hostname: 'smtp.office365.com', port: '587', encryption: 'tls' },
        smtp: { hostname: '', port: '587', encryption: 'tls' }
    };

    function hideAllFieldGroups() {
        fieldIds.forEach((id) => {
            const element = document.getElementById(id);
            if (element) element.classList.add('hidden');
        });
    }

    function showFieldsFor(type, flow) {
        hideAllFieldGroups();
        if (gmailHelp) {
            gmailHelp.classList.toggle('hidden', type !== 'gmail');
        }

        if (flow === 'smtp') {
            ['admin-smtp-hostname-wrap', 'admin-smtp-port-wrap', 'admin-smtp-username-wrap', 'admin-smtp-password-wrap', 'admin-smtp-encryption-wrap', 'admin-from-email-wrap', 'admin-additional-server-types-wrap'].forEach((id) => {
                document.getElementById(id)?.classList.remove('hidden');
            });

            const config = defaults[type] || defaults.smtp;
            document.getElementById('admin-smtp-hostname').value = config.hostname;
            document.getElementById('admin-smtp-port').value = config.port;
            document.getElementById('admin-smtp-encryption').value = config.encryption;
            return;
        }

        if (type === 'mailgun') {
            document.getElementById('admin-mailgun-domain-wrap')?.classList.remove('hidden');
            document.getElementById('admin-mailgun-secret-wrap')?.classList.remove('hidden');
            document.getElementById('admin-from-email-wrap')?.classList.remove('hidden');
        }

        if (type === 'sendgrid') {
            document.getElementById('admin-sendgrid-wrap')?.classList.remove('hidden');
            document.getElementById('admin-sendgrid-domain-wrap')?.classList.remove('hidden');
            document.getElementById('admin-sendgrid-from-email-wrap')?.classList.remove('hidden');
        }

        if (type === 'resend') {
            document.getElementById('admin-resend-wrap')?.classList.remove('hidden');
            document.getElementById('admin-from-email-wrap')?.classList.remove('hidden');
        }

        if (type === 'postmark') {
            document.getElementById('admin-postmark-wrap')?.classList.remove('hidden');
            document.getElementById('admin-from-email-wrap')?.classList.remove('hidden');
        }

        if (type === 'sparkpost') {
            document.getElementById('admin-sparkpost-wrap')?.classList.remove('hidden');
            document.getElementById('admin-from-email-wrap')?.classList.remove('hidden');
        }

        if (type === 'amazon-ses') {
            ['admin-ses-key-wrap', 'admin-ses-secret-wrap', 'admin-ses-region-wrap', 'admin-from-email-wrap'].forEach((id) => {
                document.getElementById(id)?.classList.remove('hidden');
            });
        }

        if (type === 'zeptomail-api') {
            document.getElementById('admin-zepto-token-wrap')?.classList.remove('hidden');
            document.getElementById('admin-from-email-wrap')?.classList.remove('hidden');
        }
    }

    function scrollConfigSectionIntoView() {
        requestAnimationFrame(() => {
            const modalViewport = configSection.closest('.relative.mx-auto')?.parentElement?.parentElement;

            if (modalViewport instanceof HTMLElement) {
                const viewportRect = modalViewport.getBoundingClientRect();
                const sectionRect = configSection.getBoundingClientRect();
                const nextTop = sectionRect.top - viewportRect.top + modalViewport.scrollTop - 24;

                modalViewport.scrollTo({
                    top: Math.max(nextTop, 0),
                    behavior: 'smooth',
                });

                return;
            }

            configSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (providerSelect && providerSelect.dataset.deliveryProviderBound !== '1') {
        providerSelect.dataset.deliveryProviderBound = '1';

        providerSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const type = this.value;
            const flow = selectedOption?.dataset.flow || '';
            const providerName = selectedOption?.dataset.providerName || '';

            if (!type) {
                hideAllFieldGroups();
                configSection.classList.add('hidden');
                saveButton.disabled = true;
                selectedType.value = '';
                selectedFlow.value = '';
                selectedName.textContent = '';
                return;
            }

            if ((type === 'gmail' || type === 'outlook') && !hasExtendedLicense) {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pro-feature-extended-mailbox-providers-admin' }));
                this.value = '';
                return;
            }

            selectedType.value = type;
            selectedFlow.value = flow;
            selectedName.textContent = providerName;

            showFieldsFor(type, flow);
            configSection.classList.remove('hidden');
            scrollConfigSectionIntoView();
            saveButton.disabled = false;
        });
    }

    hideAllFieldGroups();

    // SendGrid from email builder for admin drawer
    const sendgridPrefixInput = document.getElementById('admin-sendgrid-from-prefix');
    const sendgridDomainInput = document.getElementById('admin-sendgrid-domain');
    const sendgridDomainDisplay = document.getElementById('admin-sendgrid-domain-display');
    const sendgridFromEmail = document.getElementById('admin-sendgrid-from-email');
    const sendgridFullFromEmail = document.getElementById('admin-sendgrid-full-from-email');

    function updateAdminSendgridFromEmail() {
        const prefix = ((sendgridPrefixInput && sendgridPrefixInput.value) || 'hello').trim();
        const domain = ((sendgridDomainInput && sendgridDomainInput.value) || '').trim();
        if (sendgridDomainDisplay) sendgridDomainDisplay.value = domain;
        if (sendgridFullFromEmail) sendgridFullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (sendgridFromEmail) sendgridFromEmail.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (sendgridPrefixInput) sendgridPrefixInput.addEventListener('input', updateAdminSendgridFromEmail);
    if (sendgridDomainInput) sendgridDomainInput.addEventListener('input', updateAdminSendgridFromEmail);

    updateAdminSendgridFromEmail();
};

document.addEventListener('DOMContentLoaded', initAdminDeliveryServerIndex);
document.addEventListener('turbo:load', initAdminDeliveryServerIndex);

if (document.readyState !== 'loading') {
    initAdminDeliveryServerIndex();
}
</script>
@endpush
@endsection


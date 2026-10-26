@extends('layouts.customer')

@section('title', 'Edit Delivery Server')
@section('page-title', 'Edit Delivery Server')

@section('content')
@php
    $currentType = old('type', $deliveryServer->type);
@endphp
<div class="fixed inset-0 z-[100]">
    <div class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"></div>

    <div class="absolute inset-y-0 right-0 w-full max-w-lg bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 shadow-2xl will-change-transform">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="text-base font-semibold text-gray-900 dark:text-gray-100">Edit Delivery Server</div>
            <a href="{{ route('customer.delivery-servers.index') }}" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800" aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>

        <div class="h-[calc(100%-64px)] overflow-y-auto p-5">
            <form method="POST" action="{{ route('customer.delivery-servers.update', $deliveryServer) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $deliveryServer->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server Type <span class="text-red-500">*</span></label>
                    <select name="type" id="type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <option value="smtp" {{ old('type', $deliveryServer->type) == 'smtp' ? 'selected' : '' }}>SMTP</option>
                        <option value="sendmail" {{ old('type', $deliveryServer->type) == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                        <option value="zeptomail" {{ old('type', $deliveryServer->type) == 'zeptomail' ? 'selected' : '' }}>ZeptoMail</option>
                        <option value="gmail" data-extended="1" {{ old('type', $deliveryServer->type) == 'gmail' ? 'selected' : '' }}>Gmail (App Password)</option>
                        <option value="gmail-api" {{ old('type', $deliveryServer->type) == 'gmail-api' ? 'selected' : '' }}>Gmail API (OAuth2)</option>
                        <option value="outlook" data-extended="1" {{ old('type', $deliveryServer->type) == 'outlook' ? 'selected' : '' }}>Outlook (App Password)</option>
                        <option value="amazon-ses" {{ old('type', $deliveryServer->type) == 'amazon-ses' ? 'selected' : '' }}>Amazon SES</option>
                        <option value="mailgun" {{ old('type', $deliveryServer->type) == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                        <option value="sendgrid" {{ old('type', $deliveryServer->type) == 'sendgrid' ? 'selected' : '' }}>SendGrid</option>
                        <option value="postmark" {{ old('type', $deliveryServer->type) == 'postmark' ? 'selected' : '' }}>Postmark</option>
                        <option value="sparkpost" {{ old('type', $deliveryServer->type) == 'sparkpost' ? 'selected' : '' }}>SparkPost</option>
                        <option value="resend" {{ old('type', $deliveryServer->type) == 'resend' ? 'selected' : '' }}>Resend</option>
                        <option value="zeptomail-api" {{ old('type', $deliveryServer->type) == 'zeptomail-api' ? 'selected' : '' }}>ZeptoMail API</option>
                        <option value="emailit" {{ old('type', $deliveryServer->type) == 'emailit' ? 'selected' : '' }}>Emailit</option>
                    </select>
                    <p id="extended-mailbox-note" class="mt-1 text-xs text-gray-500 dark:text-gray-400 hidden">
                        Gmail/Outlook currently support <strong>SMTP + App Password</strong>. OAuth is coming soon.
                    </p>
                </div>

                <input type="hidden" name="settings[auth_method]" id="settings_auth_method" value="{{ old('settings.auth_method', data_get($deliveryServer->settings, 'auth_method')) }}">

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <option value="pending" {{ old('status', $deliveryServer->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="active" {{ old('status', $deliveryServer->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $deliveryServer->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div id="smtp-fields" class="contents" style="display: none;">
                    <div>
                        <label for="hostname" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hostname <span class="text-red-500">*</span></label>
                        <input type="text" name="hostname" id="hostname" value="{{ old('hostname', $deliveryServer->hostname) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Port <span class="text-red-500">*</span></label>
                        <input type="number" name="port" id="port" value="{{ old('port', $deliveryServer->port) }}" min="1" max="65535" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="username" value="{{ old('username', $deliveryServer->username) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="password" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Encryption</label>
                        <select name="encryption" id="encryption" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <option value="tls" {{ old('encryption', $deliveryServer->encryption) == 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('encryption', $deliveryServer->encryption) == 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="none" {{ old('encryption', $deliveryServer->encryption) == 'none' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>

                    <div>
                        <label for="from_email_smtp" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <input type="email" name="from_email" id="from_email_smtp" value="{{ old('from_email', $deliveryServer->from_email) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div id="extended-mailbox-card" class="sm:col-span-2 rounded-lg border border-blue-200 dark:border-blue-800 p-4 bg-blue-50 dark:bg-blue-900/20" style="display: none;">
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Note:</strong> For <span id="extended-mailbox-provider-label">Gmail</span>, you need to use an App Password.
                            <a id="extended-mailbox-provider-link" href="https://support.google.com/accounts/answer/185833" target="_blank" class="underline">Learn how to generate one</a>.
                        </div>
                    </div>
                </div>

                <div id="mailgun-fields" class="contents" style="display: none;">
                    <div>
                        <label for="settings_domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mailgun Domain <span class="text-red-500">*</span></label>
                        <input type="text" name="settings[domain]" id="settings_domain" value="{{ old('settings.domain', data_get($deliveryServer->settings, 'domain')) }}" placeholder="mg.yourdomain.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_secret_mailgun" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mailgun API Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[secret]" id="settings_secret_mailgun" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <a href="https://app.mailgun.com/settings/api_security" target="_blank" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline">Get API key here</a>
                        </p>
                    </div>

                    <div>
                        <label for="mailgun_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="mailgun_from_email_prefix" value="{{ old('from_email_prefix') }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <input type="text" id="mailgun_from_domain_display" readonly placeholder="yourdomain.com" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm cursor-not-allowed">
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email: <span id="mailgun_full_from_email" class="font-medium">hello@domain.com</span></p>
                        <input type="hidden" name="from_email" id="mailgun_from_email" value="{{ old('from_email', $deliveryServer->from_email) }}">
                    </div>
                </div>

                <div id="sendgrid-fields" class="contents" style="display: none;">
                    <div class="sm:col-span-2">
                        <label for="settings_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SendGrid API Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[api_key]" id="settings_api_key" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="sendgrid_sending_domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sending Domain <span class="text-red-500">*</span></label>
                        <input type="text" name="settings[sending_domain]" id="sendgrid_sending_domain" list="sendgrid_domains_list" value="{{ old('settings.sending_domain', data_get($deliveryServer->settings, 'sending_domain')) }}" placeholder="yourdomain.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <datalist id="sendgrid_domains_list"></datalist>
                        <p id="sendgrid_fetch_status" class="mt-1 text-xs text-gray-500 dark:text-gray-400 hidden"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="sendgrid_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="sendgrid_from_email_prefix" value="{{ old('from_email_prefix') }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <input type="text" id="sendgrid_from_domain_display" readonly placeholder="yourdomain.com" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm cursor-not-allowed">
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email: <span id="sendgrid_full_from_email" class="font-medium">hello@domain.com</span></p>
                        <input type="hidden" name="from_email" id="sendgrid_from_email" value="{{ old('from_email', $deliveryServer->from_email) }}">
                    </div>
                </div>

                <div id="resend-fields" class="contents" style="display: none;">
                    <div class="sm:col-span-2">
                        <label for="settings_api_key_resend" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resend API Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[api_key]" id="settings_api_key_resend" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="settings_endpoint_resend" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resend Endpoint</label>
                        <input type="text" name="settings[endpoint]" id="settings_endpoint_resend" value="{{ old('settings.endpoint', data_get($deliveryServer->settings, 'endpoint', 'https://api.resend.com')) }}" placeholder="https://api.resend.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="resend_from_email_prefix_edit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="resend_from_email_prefix_edit" value="{{ old('from_email_prefix',
                                str_contains((string) old('from_email', $deliveryServer->from_email), '@')
                                    ? explode('@', (string) old('from_email', $deliveryServer->from_email))[0]
                                    : 'hello'
                            ) }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <input type="text" name="settings[sending_domain]" id="resend_sending_domain_edit" list="resend_domains_list_edit" value="{{ old('settings.sending_domain', data_get($deliveryServer->settings, 'sending_domain')) }}" placeholder="yourdomain.com" autocomplete="off" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <datalist id="resend_domains_list_edit"></datalist>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email: <span id="resend_full_from_email_edit" class="font-medium">{{ old('from_email', $deliveryServer->from_email ?? 'hello@domain.com') }}</span></p>
                        <input type="hidden" name="from_email" id="resend_from_email_edit" value="{{ old('from_email', $deliveryServer->from_email) }}">
                    </div>

                    @if(data_get($deliveryServer->settings, 'resend_webhook_id'))
                        <div class="sm:col-span-2 rounded-md bg-green-50 dark:bg-green-900/20 p-3">
                            <p class="text-xs font-medium text-green-800 dark:text-green-200 mb-1">Webhook Configured</p>
                            <p class="text-xs text-green-700 dark:text-green-300">Webhook ID: <span class="font-mono">{{ data_get($deliveryServer->settings, 'resend_webhook_id') }}</span></p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">Webhook is managed automatically via the Resend API.</p>
                        </div>
                    @else
                        <div class="sm:col-span-2 rounded-md bg-green-50 dark:bg-green-900/20 p-3">
                            <p class="text-xs font-medium text-green-800 dark:text-green-200 mb-1">Webhook Auto-Configuration</p>
                            <p class="text-xs text-green-700 dark:text-green-300">Webhook will be created automatically at Resend using your API key on save.</p>
                        </div>
                    @endif
                </div>

                <div id="gmail-api-fields" class="contents" style="display: none;">
                    <div class="sm:col-span-2 rounded-lg border border-yellow-200 dark:border-yellow-700 p-4 bg-yellow-50 dark:bg-yellow-900/20">
                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-1">Gmail API (OAuth2) — No SMTP required</p>
                        <p class="text-xs text-yellow-700 dark:text-yellow-300">
                            Credentials from <a href="https://console.cloud.google.com/" target="_blank" class="underline">Google Cloud Console</a>.
                            Leave fields blank to keep existing values.
                        </p>
                    </div>

                    <div>
                        <label for="gmail_api_from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gmail Address (From Email)</label>
                        <input type="email" name="from_email" id="gmail_api_from_email" value="{{ old('from_email', $deliveryServer->from_email) }}" placeholder="you@gmail.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">OAuth2 Client ID</label>
                        <input type="text" name="settings[client_id]" id="settings_client_id" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_client_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">OAuth2 Client Secret</label>
                        <input type="password" name="settings[client_secret]" id="settings_client_secret" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="settings_refresh_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">OAuth2 Refresh Token</label>
                        <input type="password" name="settings[refresh_token]" id="settings_refresh_token" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>
                </div>

                <div id="postmark-fields" class="contents" style="display: none;">
                    <div class="sm:col-span-2">
                        <label for="settings_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Postmark API Token <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[token]" id="settings_token" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>
                </div>

                <div id="sparkpost-fields" class="contents" style="display: none;">
                    <div class="sm:col-span-2">
                        <label for="settings_api_key_sparkpost" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SparkPost API Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[api_key]" id="settings_api_key_sparkpost" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="from_email_prefix_edit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="from_email_prefix_edit" value="{{ old('from_email_prefix', 
                                str_contains((string) old('from_email', $deliveryServer->from_email), '@')
                                    ? explode('@', (string) old('from_email', $deliveryServer->from_email))[0]
                                    : 'hello'
                            ) }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <select name="settings[sending_domain]" id="settings_sending_domain_edit" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <option value="">Select domain</option>
                                @if(old('settings.sending_domain', data_get($deliveryServer->settings, 'sending_domain')))
                                    <option value="{{ old('settings.sending_domain', data_get($deliveryServer->settings, 'sending_domain')) }}" selected>{{ old('settings.sending_domain', data_get($deliveryServer->settings, 'sending_domain')) }}</option>
                                @endif
                            </select>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email will be: <span id="full_from_email_edit" class="font-medium">{{ old('from_email', $deliveryServer->from_email ?? 'hello@domain.com') }}</span></p>
                        <input type="hidden" name="from_email" id="from_email_edit" value="{{ old('from_email', $deliveryServer->from_email) }}">
                    </div>
                </div>

                <div id="amazon-ses-fields" class="contents" style="display: none;">
                    <div>
                        <label for="settings_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Access Key ID <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[key]" id="settings_key" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_secret_ses" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Secret Access Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[secret]" id="settings_secret_ses" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Region</label>
                        <select name="settings[region]" id="settings_region" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            @php $sesRegion = old('settings.region', data_get($deliveryServer->settings, 'region', 'us-east-1')); @endphp
                            <option value="us-east-2" {{ $sesRegion == 'us-east-2' ? 'selected' : '' }}>US East (Ohio) -> us-east-2</option>
                            <option value="us-east-1" {{ $sesRegion == 'us-east-1' ? 'selected' : '' }}>US East (N. Virginia) -> us-east-1</option>
                            <option value="us-west-2" {{ $sesRegion == 'us-west-2' ? 'selected' : '' }}>US West (Oregon) -> us-west-2</option>
                            <option value="ap-south-1" {{ $sesRegion == 'ap-south-1' ? 'selected' : '' }}>Asia Pacific (Mumbai) -> ap-south-1</option>
                            <option value="ap-northeast-2" {{ $sesRegion == 'ap-northeast-2' ? 'selected' : '' }}>Asia Pacific (Seoul) -> ap-northeast-2</option>
                            <option value="ap-southeast-1" {{ $sesRegion == 'ap-southeast-1' ? 'selected' : '' }}>Asia Pacific (Singapore) -> ap-southeast-1</option>
                            <option value="ap-southeast-2" {{ $sesRegion == 'ap-southeast-2' ? 'selected' : '' }}>Asia Pacific (Sydney) -> ap-southeast-2</option>
                            <option value="ap-northeast-1" {{ $sesRegion == 'ap-northeast-1' ? 'selected' : '' }}>Asia Pacific (Tokyo) -> ap-northeast-1</option>
                            <option value="ca-central-1" {{ $sesRegion == 'ca-central-1' ? 'selected' : '' }}>Canada (Central) -> ca-central-1</option>
                            <option value="eu-central-1" {{ $sesRegion == 'eu-central-1' ? 'selected' : '' }}>Europe (Frankfurt) -> eu-central-1</option>
                            <option value="eu-west-1" {{ $sesRegion == 'eu-west-1' ? 'selected' : '' }}>Europe (Ireland) -> eu-west-1</option>
                            <option value="eu-west-2" {{ $sesRegion == 'eu-west-2' ? 'selected' : '' }}>Europe (London) -> eu-west-2</option>
                            <option value="eu-south-1" {{ $sesRegion == 'eu-south-1' ? 'selected' : '' }}>Europe (Milan) -> eu-south-1</option>
                            <option value="eu-north-1" {{ $sesRegion == 'eu-north-1' ? 'selected' : '' }}>Europe (Stockholm) -> eu-north-1</option>
                            <option value="sa-east-1" {{ $sesRegion == 'sa-east-1' ? 'selected' : '' }}>South America (Sao Paulo) -> sa-east-1</option>
                            <option value="us-gov-west-1" {{ $sesRegion == 'us-gov-west-1' ? 'selected' : '' }}>AWS GovCloud (US West) -> us-gov-west-1</option>
                            <option value="us-gov-east-1" {{ $sesRegion == 'us-gov-east-1' ? 'selected' : '' }}>AWS GovCloud (US East) -> us-gov-east-1</option>
                        </select>
                    </div>

                    <div>
                        <label for="from_email_ses" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email <span class="text-red-500">*</span></label>
                        <input type="email" name="from_email" id="from_email_ses" value="{{ old('from_email', $deliveryServer->from_email) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>
                </div>

                <div id="zeptomail-api-fields" class="contents" style="display: none;">
                    <div class="sm:col-span-2">
                        <label for="settings_send_mail_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Send Mail Token <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[send_mail_token]" id="settings_send_mail_token" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mode</label>
                        <select name="settings[mode]" id="settings_mode" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            @php $mode = old('settings.mode', data_get($deliveryServer->settings, 'mode', 'raw')); @endphp
                            <option value="raw" {{ $mode === 'raw' ? 'selected' : '' }}>Raw HTML/Text</option>
                            <option value="template" {{ $mode === 'template' ? 'selected' : '' }}>Template</option>
                        </select>
                    </div>

                    <div>
                        <label for="settings_template_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Key</label>
                        <input type="text" name="settings[template_key]" id="settings_template_key" value="{{ old('settings.template_key', data_get($deliveryServer->settings, 'template_key')) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_template_alias" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Alias</label>
                        <input type="text" name="settings[template_alias]" id="settings_template_alias" value="{{ old('settings.template_alias', data_get($deliveryServer->settings, 'template_alias')) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_bounce_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bounce Address</label>
                        <input type="email" name="settings[bounce_address]" id="settings_bounce_address" value="{{ old('settings.bounce_address', data_get($deliveryServer->settings, 'bounce_address')) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>
                </div>

                <div id="emailit-fields" class="contents" style="display: none;">
                    <div class="sm:col-span-2">
                        <label for="settings_api_key_emailit_edit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Emailit API Key</label>
                        <input type="password" name="settings[api_key]" id="settings_api_key_emailit_edit" value="" placeholder="Leave blank to keep current" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="emailit_from_email_prefix_edit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="emailit_from_email_prefix_edit" value="{{ old('from_email_prefix',
                                str_contains((string) old('from_email', $deliveryServer->from_email), '@')
                                    ? explode('@', (string) old('from_email', $deliveryServer->from_email))[0]
                                    : 'hello'
                            ) }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <select name="settings[sending_domain]" id="emailit_sending_domain_edit" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <option value="">Select domain</option>
                                @if(old('settings.sending_domain', data_get($deliveryServer->settings, 'sending_domain')))
                                    <option value="{{ old('settings.sending_domain', data_get($deliveryServer->settings, 'sending_domain')) }}" selected>{{ old('settings.sending_domain', data_get($deliveryServer->settings, 'sending_domain')) }}</option>
                                @endif
                            </select>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email will be: <span id="emailit_full_from_email_edit" class="font-medium">{{ old('from_email', $deliveryServer->from_email ?? 'hello@domain.com') }}</span></p>
                        <input type="hidden" name="from_email" id="emailit_from_email_edit" value="{{ old('from_email', $deliveryServer->from_email) }}">
                    </div>
                </div>

                <div id="common-from-email-section" style="display: none;">
                    <label for="from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                    <input type="email" name="from_email" id="from_email" value="{{ old('from_email', $deliveryServer->from_email) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Name</label>
                    <input type="text" name="from_name" id="from_name" value="{{ old('from_name', $deliveryServer->from_name) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="bounce_server_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bounce Server</label>
                    <select name="bounce_server_id" id="bounce_server_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <option value="">None</option>
                        @foreach($bounceServers as $bounceServer)
                            <option value="{{ $bounceServer->id }}" {{ (string) old('bounce_server_id', $deliveryServer->bounce_server_id) === (string) $bounceServer->id ? 'selected' : '' }}>
                                {{ $bounceServer->name }} ({{ $bounceServer->username }})
                            </option>
                        @endforeach
                    </select>
                    @error('bounce_server_id')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>


                <div>
                    <label for="second_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Second Quota (0 = unlimited)</label>
                    <input type="number" name="second_quota" id="second_quota" value="{{ old('second_quota', $deliveryServer->second_quota ?? 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="minute_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minute Quota (0 = unlimited)</label>
                    <input type="number" name="minute_quota" id="minute_quota" value="{{ old('minute_quota', $deliveryServer->minute_quota ?? 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="hourly_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hourly Quota (0 = unlimited)</label>
                    <input type="number" name="hourly_quota" id="hourly_quota" value="{{ old('hourly_quota', $deliveryServer->hourly_quota) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="daily_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Daily Quota (0 = unlimited)</label>
                    <input type="number" name="daily_quota" id="daily_quota" value="{{ old('daily_quota', $deliveryServer->daily_quota) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="monthly_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monthly Quota (0 = unlimited)</label>
                    <input type="number" name="monthly_quota" id="monthly_quota" value="{{ old('monthly_quota', $deliveryServer->monthly_quota) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Timeout (seconds)</label>
                    <input type="number" name="timeout" id="timeout" value="{{ old('timeout', $deliveryServer->timeout ?? 30) }}" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="max_connection_messages" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Connection Messages</label>
                    <input type="number" name="max_connection_messages" id="max_connection_messages" value="{{ old('max_connection_messages', $deliveryServer->max_connection_messages ?? 100) }}" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="pause_after_send" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pause After Send (seconds)</label>
                    <input type="number" name="pause_after_send" id="pause_after_send" value="{{ old('pause_after_send', $deliveryServer->pause_after_send ?? 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">{{ old('notes', $deliveryServer->notes) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="use_for" value="1" {{ old('use_for', $deliveryServer->use_for) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use for campaigns</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="use_for_email_to_list" value="1" {{ old('use_for_email_to_list', $deliveryServer->use_for_email_to_list) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use for email to list</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="use_for_transactional" value="1" {{ old('use_for_transactional', $deliveryServer->use_for_transactional) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use for transactional</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="locked" value="1" {{ old('locked', $deliveryServer->locked) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Locked</span>
                        </label>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Also use this server as</div>
                        <div class="mt-3 flex items-center space-x-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="use_as_reply_server" value="1" {{ old('use_as_reply_server') ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use as reply server</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="use_as_bounce_server" value="1" {{ old('use_as_bounce_server') ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use as bounce server</span>
                            </label>
                        </div>
                    </div>
                </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <x-button href="{{ route('customer.delivery-servers.index') }}" variant="secondary">Cancel</x-button>
                    @customercan('servers.permissions.can_edit_delivery_servers')
                        <x-button type="submit" variant="primary">Update Server</x-button>
                    @endcustomercan
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currentType = @json($currentType);
    const canUseExtendedMailboxProviders = @json((bool) ($canUseExtendedMailboxProviders ?? false));
    const typeEl = document.getElementById('type');
    const hostEl = document.getElementById('hostname');
    const portEl = document.getElementById('port');
    const encEl = document.getElementById('encryption');
    const authMethodEl = document.getElementById('settings_auth_method');
    const extendedMailboxNoteEl = document.getElementById('extended-mailbox-note');
    const smtpFields = document.getElementById('smtp-fields');
    const extendedMailboxCardEl = document.getElementById('extended-mailbox-card');
    const extendedMailboxProviderLabelEl = document.getElementById('extended-mailbox-provider-label');
    const extendedMailboxProviderLinkEl = document.getElementById('extended-mailbox-provider-link');
    const commonFromEmailSection = document.getElementById('common-from-email-section');
    const providerSections = {
        'mailgun': document.getElementById('mailgun-fields'),
        'sendgrid': document.getElementById('sendgrid-fields'),
        'postmark': document.getElementById('postmark-fields'),
        'sparkpost': document.getElementById('sparkpost-fields'),
        'resend': document.getElementById('resend-fields'),
        'amazon-ses': document.getElementById('amazon-ses-fields'),
        'zeptomail-api': document.getElementById('zeptomail-api-fields'),
        'gmail-api': document.getElementById('gmail-api-fields'),
        'emailit': document.getElementById('emailit-fields'),
    };

    if (!typeEl || !hostEl || !portEl || !encEl) {
        return;
    }

    const defaults = {
        'smtp': { host: '', port: 587, enc: 'tls' },
        'sendmail': { host: '', port: '', enc: 'none' },
        'zeptomail': { host: 'smtp.zeptomail.com', port: 587, enc: 'tls' },
        'gmail': { host: 'smtp.gmail.com', port: 587, enc: 'tls' },
        'outlook': { host: 'smtp.office365.com', port: 587, enc: 'tls' },
        'amazon-ses': { host: 'email-smtp.us-east-1.amazonaws.com', port: 587, enc: 'tls' },
        'mailgun': { host: 'smtp.mailgun.org', port: 587, enc: 'tls' },
        'sendgrid': { host: 'smtp.sendgrid.net', port: 587, enc: 'tls' },
        'postmark': { host: 'smtp.postmarkapp.com', port: 587, enc: 'tls' },
        'sparkpost': { host: 'smtp.sparkpostmail.com', port: 587, enc: 'tls' },
        'zeptomail-api': { host: '', port: 587, enc: 'tls' },
        'emailit': { host: '', port: 587, enc: 'tls' },
    };

    function getDef(type) {
        return defaults[type] || { host: '', port: 587, enc: 'tls' };
    }

    function toggleSection(section, show) {
        if (!section) {
            return;
        }

        section.style.display = show ? '' : 'none';
        section.querySelectorAll('input, select, textarea, button').forEach((field) => {
            if (field.type === 'hidden') {
                field.disabled = !show;
                return;
            }

            field.disabled = !show;
        });
    }

    function applyDefaults(previousType) {
        const type = typeEl.value;
        const isExtendedMailboxType = ['gmail', 'outlook'].includes(type);
        const isSmtpType = ['smtp', 'gmail', 'outlook'].includes(type);

        if (isExtendedMailboxType && !canUseExtendedMailboxProviders) {
            typeEl.value = 'smtp';
            alert('This is an Extended License feature. Please upgrade to use Gmail/Outlook mailbox providers.');
            return applyDefaults(previousType);
        }

        const def = getDef(type);
        const prev = previousType ? getDef(previousType) : null;

        const hostVal = (hostEl.value || '').trim();
        if (hostVal === '' || (prev && hostVal === (prev.host || ''))) {
            hostEl.value = def.host;
        }

        const portVal = (portEl.value || '').toString().trim();
        const prevPort = prev ? (prev.port || '').toString() : null;
        if (portVal === '' || (prevPort !== null && portVal === prevPort)) {
            portEl.value = def.port;
        }

        const encVal = (encEl.value || '').trim();
        if (encVal === '' || (prev && encVal === (prev.enc || ''))) {
            encEl.value = def.enc;
        }

        if (authMethodEl) {
            authMethodEl.value = isExtendedMailboxType ? 'app_password' : '';
        }

        if (extendedMailboxNoteEl) {
            extendedMailboxNoteEl.classList.toggle('hidden', !isExtendedMailboxType);
        }

        toggleSection(smtpFields, isSmtpType);

        Object.entries(providerSections).forEach(([providerType, section]) => {
            toggleSection(section, providerType === type);
        });

        toggleSection(commonFromEmailSection, !['smtp', 'gmail', 'outlook', 'amazon-ses', 'sparkpost', 'gmail-api', 'mailgun', 'sendgrid', 'emailit'].includes(type));

        if (extendedMailboxCardEl) {
            extendedMailboxCardEl.style.display = isExtendedMailboxType ? '' : 'none';
        }

        if (extendedMailboxProviderLabelEl) {
            extendedMailboxProviderLabelEl.textContent = type === 'outlook' ? 'Outlook' : 'Gmail';
        }

        if (extendedMailboxProviderLinkEl) {
            extendedMailboxProviderLinkEl.href = type === 'outlook'
                ? 'https://support.microsoft.com/en-us/account-billing/using-app-passwords-with-apps-that-don-t-support-two-step-verification-5896ed9b-4263-e681-128a-a6f2979a7944'
                : 'https://support.google.com/accounts/answer/185833';
        }

    }

    let lastType = currentType || typeEl.value;

    typeEl.addEventListener('change', function () {
        applyDefaults(lastType);
        lastType = typeEl.value;
    });

    applyDefaults(lastType);

    // SparkPost sending domain auto-fetch for edit form
    const apiKeyInputEdit = document.getElementById('settings_api_key_sparkpost');
    const sendingDomainSelectEdit = document.getElementById('settings_sending_domain_edit');
    const fromEmailPrefixEdit = document.getElementById('from_email_prefix_edit');
    const fromEmailInputEdit = document.getElementById('from_email_edit');
    const fullFromEmailEdit = document.getElementById('full_from_email_edit');

    function updateFromEmailEdit() {
        const prefix = fromEmailPrefixEdit.value || 'hello';
        const domain = sendingDomainSelectEdit.value || 'domain.com';
        fullFromEmailEdit.textContent = prefix + '@' + domain;
        fromEmailInputEdit.value = prefix + '@' + domain;
    }

    if (fromEmailPrefixEdit) {
        fromEmailPrefixEdit.addEventListener('input', updateFromEmailEdit);
    }
    if (sendingDomainSelectEdit) {
        sendingDomainSelectEdit.addEventListener('change', updateFromEmailEdit);
    }

    // Initialize with default values
    updateFromEmailEdit();

    function fetchDomainsEdit() {
        const apiKey = apiKeyInputEdit.value;
        if (!apiKey || apiKey.length < 10) return;

        const currentValue = sendingDomainSelectEdit.value;
        sendingDomainSelectEdit.innerHTML = '<option value="">Loading...</option>';

        fetch('{{ route('customer.delivery-servers.fetch-sparkpost-domains') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => response.json())
        .then(data => {
            if (data.domains && data.domains.length > 0) {
                sendingDomainSelectEdit.innerHTML = '<option value="">Select domain</option>';
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    option.textContent = domain.domain + (domain.status ? ' (' + domain.status + ')' : '');
                    if (domain.domain === currentValue) {
                        option.selected = true;
                    }
                    sendingDomainSelectEdit.appendChild(option);
                });
                updateFromEmailEdit();
            } else {
                sendingDomainSelectEdit.innerHTML = '<option value="">No domains found</option>';
            }
        })
        .catch(error => {
            sendingDomainSelectEdit.innerHTML = '<option value="">Failed to load</option>';
        });
    }

    let sparkPostDebounceTimer;
    if (apiKeyInputEdit) {
        apiKeyInputEdit.addEventListener('blur', fetchDomainsEdit);
        apiKeyInputEdit.addEventListener('change', fetchDomainsEdit);
        apiKeyInputEdit.addEventListener('input', function () {
            clearTimeout(sparkPostDebounceTimer);
            sparkPostDebounceTimer = setTimeout(fetchDomainsEdit, 400);
        });
        apiKeyInputEdit.addEventListener('paste', function () {
            clearTimeout(sparkPostDebounceTimer);
            setTimeout(fetchDomainsEdit, 50);
        });
    }

    // Mailgun from email builder for edit form
    const mailgunDomainInputEdit = document.getElementById('settings_domain');
    const mailgunPrefixEdit = document.getElementById('mailgun_from_email_prefix');
    const mailgunFromEmailEdit = document.getElementById('mailgun_from_email');
    const mailgunDomainDisplayEdit = document.getElementById('mailgun_from_domain_display');
    const mailgunFullFromEmailEdit = document.getElementById('mailgun_full_from_email');

    function updateMailgunFromEmailEdit() {
        const prefix = ((mailgunPrefixEdit && mailgunPrefixEdit.value) || 'hello').trim();
        const domain = ((mailgunDomainInputEdit && mailgunDomainInputEdit.value) || '').trim();
        if (mailgunDomainDisplayEdit) mailgunDomainDisplayEdit.value = domain;
        if (mailgunFullFromEmailEdit) mailgunFullFromEmailEdit.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (mailgunFromEmailEdit) mailgunFromEmailEdit.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (mailgunPrefixEdit) mailgunPrefixEdit.addEventListener('input', updateMailgunFromEmailEdit);
    if (mailgunDomainInputEdit) mailgunDomainInputEdit.addEventListener('input', updateMailgunFromEmailEdit);

    updateMailgunFromEmailEdit();

    // SendGrid from email builder for edit form
    const sendgridDomainInputEdit = document.getElementById('sendgrid_sending_domain');
    const sendgridPrefixEdit = document.getElementById('sendgrid_from_email_prefix');
    const sendgridFromEmailEdit = document.getElementById('sendgrid_from_email');
    const sendgridDomainDisplayEdit = document.getElementById('sendgrid_from_domain_display');
    const sendgridFullFromEmailEdit = document.getElementById('sendgrid_full_from_email');
    const sendgridApiKeyEdit = document.getElementById('settings_api_key');
    const sendgridDomainsListEdit = document.getElementById('sendgrid_domains_list');
    const sendgridFetchStatusEdit = document.getElementById('sendgrid_fetch_status');

    function updateSendgridFromEmailEdit() {
        const prefix = ((sendgridPrefixEdit && sendgridPrefixEdit.value) || 'hello').trim();
        const domain = ((sendgridDomainInputEdit && sendgridDomainInputEdit.value) || '').trim();
        if (sendgridDomainDisplayEdit) sendgridDomainDisplayEdit.value = domain;
        if (sendgridFullFromEmailEdit) sendgridFullFromEmailEdit.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (sendgridFromEmailEdit) sendgridFromEmailEdit.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (sendgridPrefixEdit) sendgridPrefixEdit.addEventListener('input', updateSendgridFromEmailEdit);
    if (sendgridDomainInputEdit) sendgridDomainInputEdit.addEventListener('input', updateSendgridFromEmailEdit);

    updateSendgridFromEmailEdit();

    function fetchSendGridDomainsEdit() {
        const apiKey = sendgridApiKeyEdit ? sendgridApiKeyEdit.value : '';
        if (!apiKey || apiKey.length < 10 || !sendgridDomainsListEdit) return;

        if (sendgridFetchStatusEdit) {
            sendgridFetchStatusEdit.textContent = 'Fetching domains...';
            sendgridFetchStatusEdit.classList.remove('hidden');
        }

        fetch('{{ route('customer.delivery-servers.fetch-sendgrid-domains') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => response.json())
        .then(data => {
            if (sendgridFetchStatusEdit) sendgridFetchStatusEdit.classList.add('hidden');
            sendgridDomainsListEdit.innerHTML = '';
            if (data.domains && data.domains.length > 0) {
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    option.label = domain.status ? domain.status : '';
                    sendgridDomainsListEdit.appendChild(option);
                });
                if (sendgridDomainInputEdit && !sendgridDomainInputEdit.value.trim()) {
                    sendgridDomainInputEdit.value = data.domains[0].domain;
                    updateSendgridFromEmailEdit();
                }
                if (sendgridFetchStatusEdit) {
                    sendgridFetchStatusEdit.textContent = data.domains.length + ' domain(s) found.';
                    sendgridFetchStatusEdit.classList.remove('hidden', 'text-red-600', 'dark:text-red-400');
                    sendgridFetchStatusEdit.classList.add('text-gray-500', 'dark:text-gray-400');
                }
            } else {
                if (sendgridFetchStatusEdit) {
                    sendgridFetchStatusEdit.textContent = 'No domains found in this SendGrid account.';
                    sendgridFetchStatusEdit.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                    sendgridFetchStatusEdit.classList.add('text-red-600', 'dark:text-red-400');
                }
            }
        })
        .catch(error => {
            if (sendgridFetchStatusEdit) {
                sendgridFetchStatusEdit.textContent = 'Failed to fetch domains: ' + (error.message || 'Unknown error');
                sendgridFetchStatusEdit.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                sendgridFetchStatusEdit.classList.add('text-red-600', 'dark:text-red-400');
            }
        });
    }

    let sendgridDebounceTimerEdit;
    if (sendgridApiKeyEdit) {
        sendgridApiKeyEdit.addEventListener('blur', fetchSendGridDomainsEdit);
        sendgridApiKeyEdit.addEventListener('input', function () {
            clearTimeout(sendgridDebounceTimerEdit);
            sendgridDebounceTimerEdit = setTimeout(fetchSendGridDomainsEdit, 400);
        });
        sendgridApiKeyEdit.addEventListener('paste', function () {
            clearTimeout(sendgridDebounceTimerEdit);
            setTimeout(fetchSendGridDomainsEdit, 50);
        });
    }

    // Resend domain fetch and from-email builder for edit form
    const resendApiKeyEdit = document.getElementById('settings_api_key_resend');
    const resendDomainInputEdit = document.getElementById('resend_sending_domain_edit');
    const resendDomainsListEdit = document.getElementById('resend_domains_list_edit');
    const resendPrefixEdit = document.getElementById('resend_from_email_prefix_edit');
    const resendFromEmailEdit = document.getElementById('resend_from_email_edit');
    const resendFullFromEmailEdit = document.getElementById('resend_full_from_email_edit');

    function updateResendFromEmailEdit() {
        const prefix = ((resendPrefixEdit && resendPrefixEdit.value) || 'hello').trim();
        const domain = ((resendDomainInputEdit && resendDomainInputEdit.value) || '').trim();
        if (resendFullFromEmailEdit) resendFullFromEmailEdit.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (resendFromEmailEdit) resendFromEmailEdit.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (resendPrefixEdit) resendPrefixEdit.addEventListener('input', updateResendFromEmailEdit);
    if (resendDomainInputEdit) resendDomainInputEdit.addEventListener('input', updateResendFromEmailEdit);

    updateResendFromEmailEdit();

    function fetchResendDomainsEdit() {
        const apiKey = resendApiKeyEdit ? resendApiKeyEdit.value : '';
        if (!apiKey || apiKey.length < 10 || !resendDomainsListEdit) return;

        fetch('{{ route('customer.delivery-servers.fetch-resend-domains') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => response.json())
        .then(data => {
            resendDomainsListEdit.innerHTML = '';
            if (data.domains && data.domains.length > 0) {
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    if (domain.status) option.label = domain.status;
                    resendDomainsListEdit.appendChild(option);
                });
                if (resendDomainInputEdit && !resendDomainInputEdit.value.trim()) {
                    resendDomainInputEdit.value = data.domains[0].domain;
                    updateResendFromEmailEdit();
                }
            }
        })
        .catch(error => {});
    }

    let resendDebounceTimer;
    if (resendApiKeyEdit) {
        resendApiKeyEdit.addEventListener('blur', fetchResendDomainsEdit);
        resendApiKeyEdit.addEventListener('input', function () {
            clearTimeout(resendDebounceTimer);
            resendDebounceTimer = setTimeout(fetchResendDomainsEdit, 400);
        });
        resendApiKeyEdit.addEventListener('paste', function () {
            clearTimeout(resendDebounceTimer);
            setTimeout(fetchResendDomainsEdit, 50);
        });
    }

    // Emailit sending domain auto-fetch for edit form
    const emailitApiKeyEdit = document.getElementById('settings_api_key_emailit_edit');
    const emailitDomainSelectEdit = document.getElementById('emailit_sending_domain_edit');
    const emailitPrefixEdit = document.getElementById('emailit_from_email_prefix_edit');
    const emailitFromEmailEdit = document.getElementById('emailit_from_email_edit');
    const emailitFullFromEmailEdit = document.getElementById('emailit_full_from_email_edit');

    function updateEmailitFromEmailEdit() {
        const prefix = emailitPrefixEdit.value || 'hello';
        const domain = emailitDomainSelectEdit.value || 'domain.com';
        emailitFullFromEmailEdit.textContent = prefix + '@' + domain;
        emailitFromEmailEdit.value = prefix + '@' + domain;
    }

    if (emailitPrefixEdit) {
        emailitPrefixEdit.addEventListener('input', updateEmailitFromEmailEdit);
    }
    if (emailitDomainSelectEdit) {
        emailitDomainSelectEdit.addEventListener('change', updateEmailitFromEmailEdit);
    }

    updateEmailitFromEmailEdit();

    function fetchEmailitDomainsEdit() {
        const apiKey = emailitApiKeyEdit.value;
        if (!apiKey || apiKey.length < 10) return;

        const currentValue = emailitDomainSelectEdit.value;
        emailitDomainSelectEdit.innerHTML = '<option value="">Loading...</option>';

        fetch('{{ route('customer.delivery-servers.fetch-emailit-domains') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => response.json())
        .then(data => {
            if (data.domains && data.domains.length > 0) {
                emailitDomainSelectEdit.innerHTML = '<option value="">Select domain</option>';
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    option.textContent = domain.domain + (domain.status ? ' (' + domain.status + ')' : '');
                    if (domain.domain === currentValue) {
                        option.selected = true;
                    }
                    emailitDomainSelectEdit.appendChild(option);
                });
                updateEmailitFromEmailEdit();
            } else {
                emailitDomainSelectEdit.innerHTML = '<option value="">No domains found</option>';
            }
        })
        .catch(error => {
            emailitDomainSelectEdit.innerHTML = '<option value="">Failed to load</option>';
        });
    }

    let emailitDebounceTimer;
    if (emailitApiKeyEdit) {
        emailitApiKeyEdit.addEventListener('blur', fetchEmailitDomainsEdit);
        emailitApiKeyEdit.addEventListener('change', fetchEmailitDomainsEdit);
        emailitApiKeyEdit.addEventListener('input', function () {
            clearTimeout(emailitDebounceTimer);
            emailitDebounceTimer = setTimeout(fetchEmailitDomainsEdit, 400);
        });
        emailitApiKeyEdit.addEventListener('paste', function () {
            clearTimeout(emailitDebounceTimer);
            setTimeout(fetchEmailitDomainsEdit, 50);
        });
    }
});
</script>
@endpush
@endsection

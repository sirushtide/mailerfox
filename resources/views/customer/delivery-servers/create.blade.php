@extends('layouts.customer')

@section('title', 'Add Delivery Server')
@section('page-title', 'Add Delivery Server')

@section('content')
<div class="max-w-4xl">
    <x-card title="Add Delivery Server">
        <form method="POST" action="{{ route('customer.delivery-servers.store') }}" class="space-y-6">
            @csrf

            <input type="hidden" name="flow" value="{{ $flow }}">
            <input type="hidden" name="type" value="{{ $type }}">

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server Type</label>
                    <div class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                        {{ $type ? ucfirst(str_replace('-', ' ', $type)) : 'Select a type' }}
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- SMTP Fields (for Gmail, Outlook, Generic SMTP) -->
                @if(in_array($type, ['smtp', 'gmail', 'outlook']))
                    <div>
                        <label for="hostname" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hostname <span class="text-red-500">*</span></label>
                        <input type="text" name="hostname" id="hostname" value="{{ old('hostname', $type == 'gmail' ? 'smtp.gmail.com' : ($type == 'outlook' ? 'smtp.office365.com' : '')) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('hostname')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Port <span class="text-red-500">*</span></label>
                        <input type="number" name="port" id="port" value="{{ old('port', 587) }}" min="1" max="65535" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('port')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('username')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="password" value="{{ old('password') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('password')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Encryption</label>
                        <select name="encryption" id="encryption" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <option value="tls" {{ old('encryption', 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="none" {{ old('encryption') == 'none' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>

                    @if(in_array($type, ['gmail', 'outlook']))
                        <div class="sm:col-span-2 rounded-lg border border-blue-200 dark:border-blue-800 p-4 bg-blue-50 dark:bg-blue-900/20">
                            <div class="text-sm text-blue-800 dark:text-blue-200">
                                <strong>Note:</strong> For {{ $type == 'gmail' ? 'Gmail' : 'Outlook' }}, you need to use an App Password. 
                                <a href="{{ $type == 'gmail' ? 'https://support.google.com/accounts/answer/185833' : 'https://support.microsoft.com/en-us/account-billing/using-app-passwords-with-apps-that-don-t-support-two-step-verification-5896ed9b-4263-e681-128a-a6f2979a7944' }}" target="_blank" class="underline">Learn how to generate one</a>.
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Gmail API Fields -->
                @if($type === 'gmail-api')
                    <div class="sm:col-span-2 rounded-lg border border-yellow-200 dark:border-yellow-700 p-4 bg-yellow-50 dark:bg-yellow-900/20">
                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-1">Gmail API (OAuth2) — No SMTP required</p>
                        <p class="text-xs text-yellow-700 dark:text-yellow-300">
                            You need OAuth2 credentials from <a href="https://console.cloud.google.com/" target="_blank" class="underline">Google Cloud Console</a>.
                            Enable the <strong>Gmail API</strong>, create an OAuth2 Client (Desktop app), then use
                            <a href="https://developers.google.com/oauthplayground/" target="_blank" class="underline">OAuth Playground</a>
                            to generate a Refresh Token with scope <code>https://mail.google.com/</code>.
                        </p>
                    </div>

                    <div>
                        <label for="from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gmail Address (From Email) <span class="text-red-500">*</span></label>
                        <input type="email" name="from_email" id="from_email" value="{{ old('from_email') }}" placeholder="you@gmail.com" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('from_email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="settings_client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">OAuth2 Client ID <span class="text-red-500">*</span></label>
                        <input type="text" name="settings[client_id]" id="settings_client_id" value="{{ old('settings.client_id') }}" placeholder="xxxx.apps.googleusercontent.com" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_client_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">OAuth2 Client Secret <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[client_secret]" id="settings_client_secret" value="{{ old('settings.client_secret') }}" placeholder="GOCSPX-xxxx" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="settings_refresh_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">OAuth2 Refresh Token <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[refresh_token]" id="settings_refresh_token" value="{{ old('settings.refresh_token') }}" placeholder="1//xxxx" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>
                @endif

                <!-- API Provider Fields -->
                @if($type == 'mailgun')
                    <div>
                        <label for="settings_domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mailgun Domain <span class="text-red-500">*</span></label>
                        <input type="text" name="settings[domain]" id="settings_domain" value="{{ old('settings.domain') }}" placeholder="mg.yourdomain.com" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('settings.domain')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="settings_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mailgun API Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[secret]" id="settings_secret" value="{{ old('settings.secret') }}" placeholder="key-xxxxxxxxxxxxxxxxxxxx" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <a href="https://app.mailgun.com/settings/api_security" target="_blank" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline">Get API key here</a>
                        </p>
                        @error('settings.secret')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="mailgun_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email <span class="text-red-500">*</span></label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="mailgun_from_email_prefix" value="{{ old('from_email_prefix') }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <input type="text" id="mailgun_from_domain_display" readonly placeholder="yourdomain.com" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm cursor-not-allowed">
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email: <span id="mailgun_full_from_email" class="font-medium">hello@domain.com</span></p>
                        <input type="hidden" name="from_email" id="mailgun_from_email" value="{{ old('from_email') }}">
                        @error('from_email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endif

                @if($type == 'sendgrid')
                    <div class="sm:col-span-2">
                        <label for="settings_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SendGrid API Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[api_key]" id="settings_api_key" value="{{ old('settings.api_key') }}" placeholder="SG.xxxxx" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('settings.api_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="sendgrid_sending_domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sending Domain <span class="text-red-500">*</span></label>
                        <input type="text" name="settings[sending_domain]" id="sendgrid_sending_domain" list="sendgrid_domains_list" value="{{ old('settings.sending_domain') }}" placeholder="yourdomain.com" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <datalist id="sendgrid_domains_list"></datalist>
                        <p id="sendgrid_fetch_status" class="mt-1 text-xs text-gray-500 dark:text-gray-400 hidden"></p>
                        @error('settings.sending_domain')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="sendgrid_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email <span class="text-red-500">*</span></label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="sendgrid_from_email_prefix" value="{{ old('from_email_prefix') }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <input type="text" id="sendgrid_from_domain_display" readonly placeholder="yourdomain.com" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm cursor-not-allowed">
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email: <span id="sendgrid_full_from_email" class="font-medium">hello@domain.com</span></p>
                        <input type="hidden" name="from_email" id="sendgrid_from_email" value="{{ old('from_email') }}">
                        @error('from_email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endif

                @if($type == 'resend')
                    <div class="sm:col-span-2">
                        <label for="settings_api_key_resend" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resend API Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[api_key]" id="settings_api_key_resend" value="{{ old('settings.api_key') }}" placeholder="re_..." required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('settings.api_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="settings_endpoint_resend" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resend Endpoint</label>
                        <input type="text" name="settings[endpoint]" id="settings_endpoint_resend" value="{{ old('settings.endpoint', 'https://api.resend.com') }}" placeholder="https://api.resend.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div class="sm:col-span-2 rounded-md bg-green-50 dark:bg-green-900/20 p-3">
                        <p class="text-xs font-medium text-green-800 dark:text-green-200 mb-1">Webhook Auto-Configuration</p>
                        <p class="text-xs text-green-700 dark:text-green-300">Webhook will be created automatically at Resend using your API key on save.</p>
                        <code class="text-xs text-green-700 dark:text-green-300 break-all mt-1 block">{{ url('/webhooks/resend') }}</code>
                    </div>

                    <div class="sm:col-span-6">
                        <label for="resend_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email <span class="text-red-500">*</span></label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="resend_from_email_prefix" value="{{ old('from_email_prefix') }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <input type="text" name="settings[sending_domain]" id="resend_sending_domain" list="resend_domains_list" value="{{ old('settings.sending_domain') }}" placeholder="yourdomain.com" autocomplete="off" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <datalist id="resend_domains_list"></datalist>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use a domain verified in your Resend account. Full from email: <span id="resend_full_from_email" class="font-medium">hello@domain.com</span></p>
                        <input type="hidden" name="from_email" id="resend_from_email" value="{{ old('from_email') }}">
                        @error('settings.sending_domain')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endif

                @if($type == 'postmark')
                    <div class="sm:col-span-2">
                        <label for="settings_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Postmark API Token <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[token]" id="settings_token" value="{{ old('settings.token') }}" placeholder="pm-xxxxxxxxxxxxxxxxxxxx" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('settings.token')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endif

                @if($type == 'sparkpost')
                    <div class="sm:col-span-2">
                        <label for="settings_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SparkPost API Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[api_key]" id="settings_api_key" value="{{ old('settings.api_key') }}" placeholder="xxxxxxxxxxxxxxxxxxxx" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('settings.api_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="from_email_prefix" value="{{ old('from_email_prefix') }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <select name="settings[sending_domain]" id="settings_sending_domain" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <option value="">Select domain</option>
                            </select>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email will be: <span id="full_from_email" class="font-medium">hello@domain.com</span></p>
                        <input type="hidden" name="from_email" id="from_email" value="{{ old('from_email') }}">
                        @error('settings.sending_domain')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endif

                @if($type == 'amazon-ses')
                    <div>
                        <label for="settings_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Access Key ID <span class="text-red-500">*</span></label>
                        <input type="text" name="settings[key]" id="settings_key" value="{{ old('settings.key') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('settings.key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="settings_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Secret Access Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[secret]" id="settings_secret" value="{{ old('settings.secret') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('settings.secret')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="settings_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Region</label>
                        <select name="settings[region]" id="settings_region" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            @php $sesRegion = old('settings.region', 'us-east-1'); @endphp
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
                        <label for="from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email <span class="text-red-500">*</span></label>
                        <input type="email" name="from_email" id="from_email" value="{{ old('from_email') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('from_email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endif

                @if($type == 'zeptomail-api')
                    <div class="sm:col-span-2">
                        <label for="settings_send_mail_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Send Mail Token <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[send_mail_token]" id="settings_send_mail_token" value="{{ old('settings.send_mail_token') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('settings.send_mail_token')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="settings_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mode</label>
                        <select name="settings[mode]" id="settings_mode" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <option value="raw" {{ old('settings.mode', 'raw') == 'raw' ? 'selected' : '' }}>Raw HTML/Text</option>
                            <option value="template" {{ old('settings.mode') == 'template' ? 'selected' : '' }}>Template</option>
                        </select>
                    </div>

                    <div>
                        <label for="settings_template_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Key</label>
                        <input type="text" name="settings[template_key]" id="settings_template_key" value="{{ old('settings.template_key') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_template_alias" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Alias</label>
                        <input type="text" name="settings[template_alias]" id="settings_template_alias" value="{{ old('settings.template_alias') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="settings_bounce_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bounce Address</label>
                        <input type="email" name="settings[bounce_address]" id="settings_bounce_address" value="{{ old('settings.bounce_address') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>
                @endif

                @if($type == 'emailit')
                    <div class="sm:col-span-2">
                        <label for="settings_api_key_emailit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Emailit API Key <span class="text-red-500">*</span></label>
                        <input type="password" name="settings[api_key]" id="settings_api_key_emailit" value="{{ old('settings.api_key') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('settings.api_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="emailit_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="from_email_prefix" id="emailit_from_email_prefix" value="{{ old('from_email_prefix') }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                @
                            </span>
                            <select name="settings[sending_domain]" id="emailit_sending_domain" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <option value="">Select domain</option>
                            </select>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email will be: <span id="emailit_full_from_email" class="font-medium">hello@domain.com</span></p>
                        <input type="hidden" name="from_email" id="emailit_from_email" value="{{ old('from_email') }}">
                        @error('settings.sending_domain')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endif

                <!-- Common fields for all types -->
                @if(!in_array($type, ['amazon-ses', 'sparkpost', 'resend', 'mailgun', 'sendgrid', 'emailit']))
                    <div>
                        <label for="from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <input type="email" name="from_email" id="from_email" value="{{ old('from_email') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @error('from_email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div>
                    <label for="bounce_server_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bounce Server</label>
                    <select name="bounce_server_id" id="bounce_server_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <option value="">None</option>
                        @foreach($bounceServers as $bounceServer)
                            <option value="{{ $bounceServer->id }}" {{ (string) old('bounce_server_id') === (string) $bounceServer->id ? 'selected' : '' }}>
                                {{ $bounceServer->name }} ({{ $bounceServer->username }})
                            </option>
                        @endforeach
                    </select>
                    @error('bounce_server_id')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Name</label>
                    <input type="text" name="from_name" id="from_name" value="{{ old('from_name') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('from_name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="second_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Second Quota (0 = unlimited)</label>
                    <input type="number" name="second_quota" id="second_quota" value="{{ old('second_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('second_quota')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="minute_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minute Quota (0 = unlimited)</label>
                    <input type="number" name="minute_quota" id="minute_quota" value="{{ old('minute_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('minute_quota')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="hourly_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hourly Quota (0 = unlimited)</label>
                    <input type="number" name="hourly_quota" id="hourly_quota" value="{{ old('hourly_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('hourly_quota')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="daily_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Daily Quota (0 = unlimited)</label>
                    <input type="number" name="daily_quota" id="daily_quota" value="{{ old('daily_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('daily_quota')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="monthly_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monthly Quota (0 = unlimited)</label>
                    <input type="number" name="monthly_quota" id="monthly_quota" value="{{ old('monthly_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('monthly_quota')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="pause_after_send" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pause After Send (seconds, 0 = inherit default)</label>
                    <input type="number" name="pause_after_send" id="pause_after_send" value="{{ old('pause_after_send', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('pause_after_send')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="use_for" value="1" {{ old('use_for', true) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use for campaigns</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="use_for_transactional" value="1" {{ old('use_for_transactional') ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use for transactional</span>
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

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-button href="{{ route('customer.delivery-servers.index') }}" variant="secondary">Cancel</x-button>
                @customercan('servers.permissions.can_create_delivery_servers')
                    <x-button type="submit" variant="primary">Create Server</x-button>
                @endcustomercan
            </div>
        </form>
    </x-card>
</div>

@if($type == 'sparkpost')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiKeyInput = document.getElementById('settings_api_key');
    const sendingDomainSelect = document.getElementById('settings_sending_domain');
    const fromEmailPrefix = document.getElementById('from_email_prefix');
    const fromEmailInput = document.getElementById('from_email');
    const fullFromEmail = document.getElementById('full_from_email');

    function updateFromEmail() {
        const prefix = fromEmailPrefix.value || 'hello';
        const domain = sendingDomainSelect.value || 'domain.com';
        fullFromEmail.textContent = prefix + '@' + domain;
        fromEmailInput.value = prefix + '@' + domain;
    }

    fromEmailPrefix.addEventListener('input', updateFromEmail);
    sendingDomainSelect.addEventListener('change', updateFromEmail);

    // Initialize with default values
    updateFromEmail();

    function fetchDomains() {
        const apiKey = apiKeyInput.value;
        if (!apiKey || apiKey.length < 10) return;

        sendingDomainSelect.innerHTML = '<option value="">Loading...</option>';

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
                sendingDomainSelect.innerHTML = '<option value="">Select domain</option>';
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    option.textContent = domain.domain + (domain.status ? ' (' + domain.status + ')' : '');
                    sendingDomainSelect.appendChild(option);
                });
                if (!sendingDomainSelect.value) {
                    sendingDomainSelect.value = data.domains[0].domain;
                }
                updateFromEmail();
            } else {
                sendingDomainSelect.innerHTML = '<option value="">No domains found</option>';
            }
        })
        .catch(error => {
            sendingDomainSelect.innerHTML = '<option value="">Failed to load</option>';
        });
    }

    let debounceTimer;
    apiKeyInput.addEventListener('blur', fetchDomains);
    apiKeyInput.addEventListener('change', fetchDomains);
    apiKeyInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchDomains, 400);
    });
    apiKeyInput.addEventListener('paste', function () {
        clearTimeout(debounceTimer);
        setTimeout(fetchDomains, 50);
    });
});
</script>
@endif

@if($type == 'resend')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiKeyInput = document.getElementById('settings_api_key_resend');
    const domainInput = document.getElementById('resend_sending_domain');
    const domainsList = document.getElementById('resend_domains_list');
    const fromEmailPrefix = document.getElementById('resend_from_email_prefix');
    const fromEmailInput = document.getElementById('resend_from_email');
    const fullFromEmail = document.getElementById('resend_full_from_email');

    function updateFromEmail() {
        const prefix = (fromEmailPrefix.value || 'hello').trim();
        const domain = (domainInput.value || '').trim();
        fullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        fromEmailInput.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    fromEmailPrefix.addEventListener('input', updateFromEmail);
    domainInput.addEventListener('input', updateFromEmail);

    updateFromEmail();

    function fetchDomains() {
        const apiKey = apiKeyInput.value;
        if (!apiKey || apiKey.length < 10 || !domainsList) return;

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
            domainsList.innerHTML = '';
            if (data.domains && data.domains.length > 0) {
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    if (domain.status) option.label = domain.status;
                    domainsList.appendChild(option);
                });
                if (domainInput && !domainInput.value.trim()) {
                    domainInput.value = data.domains[0].domain;
                    updateFromEmail();
                }
            }
        })
        .catch(error => {});
    }

    let debounceTimer;
    apiKeyInput.addEventListener('blur', fetchDomains);
    apiKeyInput.addEventListener('change', fetchDomains);
    apiKeyInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchDomains, 400);
    });
    apiKeyInput.addEventListener('paste', function () {
        clearTimeout(debounceTimer);
        setTimeout(fetchDomains, 50);
    });
});
</script>
@endif

@if($type == 'mailgun')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const domainInput = document.getElementById('settings_domain');
    const fromEmailPrefix = document.getElementById('mailgun_from_email_prefix');
    const fromEmailInput = document.getElementById('mailgun_from_email');
    const domainDisplay = document.getElementById('mailgun_from_domain_display');
    const fullFromEmail = document.getElementById('mailgun_full_from_email');

    function updateFromEmail() {
        const prefix = (fromEmailPrefix.value || 'hello').trim();
        const domain = (domainInput.value || '').trim();
        if (domainDisplay) domainDisplay.value = domain;
        if (fullFromEmail) fullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (fromEmailInput) fromEmailInput.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (fromEmailPrefix) fromEmailPrefix.addEventListener('input', updateFromEmail);
    if (domainInput) domainInput.addEventListener('input', updateFromEmail);

    updateFromEmail();
});
</script>
@endif

@if($type == 'emailit')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiKeyInput = document.getElementById('settings_api_key_emailit');
    const sendingDomainSelect = document.getElementById('emailit_sending_domain');
    const fromEmailPrefix = document.getElementById('emailit_from_email_prefix');
    const fromEmailInput = document.getElementById('emailit_from_email');
    const fullFromEmail = document.getElementById('emailit_full_from_email');

    function updateFromEmail() {
        const prefix = fromEmailPrefix.value || 'hello';
        const domain = sendingDomainSelect.value || 'domain.com';
        fullFromEmail.textContent = prefix + '@' + domain;
        fromEmailInput.value = prefix + '@' + domain;
    }

    fromEmailPrefix.addEventListener('input', updateFromEmail);
    sendingDomainSelect.addEventListener('change', updateFromEmail);

    updateFromEmail();

    function fetchDomains() {
        const apiKey = apiKeyInput.value;
        if (!apiKey || apiKey.length < 10) return;

        sendingDomainSelect.innerHTML = '<option value="">Loading...</option>';

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
                sendingDomainSelect.innerHTML = '<option value="">Select domain</option>';
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    option.textContent = domain.domain + (domain.status ? ' (' + domain.status + ')' : '');
                    sendingDomainSelect.appendChild(option);
                });
                if (!sendingDomainSelect.value) {
                    sendingDomainSelect.value = data.domains[0].domain;
                }
                updateFromEmail();
            } else {
                sendingDomainSelect.innerHTML = '<option value="">No domains found</option>';
            }
        })
        .catch(error => {
            sendingDomainSelect.innerHTML = '<option value="">Failed to load</option>';
        });
    }

    let debounceTimer;
    apiKeyInput.addEventListener('blur', fetchDomains);
    apiKeyInput.addEventListener('change', fetchDomains);
    apiKeyInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchDomains, 400);
    });
    apiKeyInput.addEventListener('paste', function () {
        clearTimeout(debounceTimer);
        setTimeout(fetchDomains, 50);
    });
});
</script>
@endif

@if($type == 'sendgrid')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const domainInput = document.getElementById('sendgrid_sending_domain');
    const fromEmailPrefix = document.getElementById('sendgrid_from_email_prefix');
    const fromEmailInput = document.getElementById('sendgrid_from_email');
    const domainDisplay = document.getElementById('sendgrid_from_domain_display');
    const fullFromEmail = document.getElementById('sendgrid_full_from_email');
    const apiKeyInput = document.getElementById('settings_api_key');
    const domainsList = document.getElementById('sendgrid_domains_list');
    const fetchStatus = document.getElementById('sendgrid_fetch_status');

    function updateFromEmail() {
        const prefix = (fromEmailPrefix.value || 'hello').trim();
        const domain = (domainInput.value || '').trim();
        if (domainDisplay) domainDisplay.value = domain;
        if (fullFromEmail) fullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (fromEmailInput) fromEmailInput.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (fromEmailPrefix) fromEmailPrefix.addEventListener('input', updateFromEmail);
    if (domainInput) domainInput.addEventListener('input', updateFromEmail);

    updateFromEmail();

    function fetchSendGridDomains() {
        const apiKey = apiKeyInput ? apiKeyInput.value : '';
        if (!apiKey || apiKey.length < 10 || !domainsList) return;

        if (fetchStatus) {
            fetchStatus.textContent = 'Fetching domains...';
            fetchStatus.classList.remove('hidden');
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
            if (fetchStatus) fetchStatus.classList.add('hidden');
            domainsList.innerHTML = '';
            if (data.domains && data.domains.length > 0) {
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    option.label = domain.status ? domain.status : '';
                    domainsList.appendChild(option);
                });
                if (domainInput && !domainInput.value.trim()) {
                    domainInput.value = data.domains[0].domain;
                    updateFromEmail();
                }
                if (fetchStatus) {
                    fetchStatus.textContent = data.domains.length + ' domain(s) found.';
                    fetchStatus.classList.remove('hidden', 'text-red-600', 'dark:text-red-400');
                    fetchStatus.classList.add('text-gray-500', 'dark:text-gray-400');
                }
            } else {
                if (fetchStatus) {
                    fetchStatus.textContent = 'No domains found in this SendGrid account.';
                    fetchStatus.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                    fetchStatus.classList.add('text-red-600', 'dark:text-red-400');
                }
            }
        })
        .catch(error => {
            if (fetchStatus) {
                fetchStatus.textContent = 'Failed to fetch domains: ' + (error.message || 'Unknown error');
                fetchStatus.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                fetchStatus.classList.add('text-red-600', 'dark:text-red-400');
            }
        });
    }

    let sendgridDebounceTimer;
    if (apiKeyInput) {
        apiKeyInput.addEventListener('blur', fetchSendGridDomains);
        apiKeyInput.addEventListener('input', function () {
            clearTimeout(sendgridDebounceTimer);
            sendgridDebounceTimer = setTimeout(fetchSendGridDomains, 400);
        });
        apiKeyInput.addEventListener('paste', function () {
            clearTimeout(sendgridDebounceTimer);
            setTimeout(fetchSendGridDomains, 50);
        });
    }
});
</script>
@endif
@endsection

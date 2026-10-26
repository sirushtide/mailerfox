@extends('layouts.admin')

@section('title', __('Add Delivery Server'))
@section('page-title', __('Add Delivery Server'))

@section('content')
<div class="max-w-4xl">
    <x-card title="{{ __('Delivery Server Configuration') }}">
        <form method="POST" action="{{ route('admin.delivery-servers.store') }}" class="space-y-6">
            @csrf

            @if(($flow ?? null) !== null)
                <input type="hidden" name="flow" value="{{ $flow }}">
            @endif

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                @if(($flow ?? null) === 'api')
                    <div class="sm:col-span-2 rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            API credentials are managed from the Integrations page. Configure the provider there and we will use that configuration for this server.
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('admin.integrations.index', ['tab' => 'delivery-servers']) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                {{ __('Go to Integrations') }}
                            </a>
                        </div>
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Server Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Server Type') }} <span class="text-red-500">*</span></label>
                    <select name="type" id="type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @if(($flow ?? null) === 'smtp')
                            <option value="smtp" {{ old('type') == 'smtp' ? 'selected' : '' }}>{{ __('SMTP') }}</option>
                            <option value="sendmail" {{ old('type') == 'sendmail' ? 'selected' : '' }}>{{ __('Sendmail') }}</option>
                            <option value="zeptomail" {{ old('type') == 'zeptomail' ? 'selected' : '' }}>{{ __('ZeptoMail') }}</option>
                            <option value="gmail" {{ old('type') == 'gmail' ? 'selected' : '' }}>{{ __('Gmail (App Password)') }}</option>
                            <option value="outlook" {{ old('type') == 'outlook' ? 'selected' : '' }}>{{ __('Outlook (App Password)') }}</option>
                        @elseif(($flow ?? null) === 'api')
                            <option value="amazon-ses" {{ old('type') == 'amazon-ses' ? 'selected' : '' }}>{{ __('Amazon SES') }}</option>
                            <option value="mailgun" {{ old('type') == 'mailgun' ? 'selected' : '' }}>{{ __('Mailgun') }}</option>
                            <option value="sendgrid" {{ old('type') == 'sendgrid' ? 'selected' : '' }}>{{ __('SendGrid') }}</option>
                            <option value="postmark" {{ old('type') == 'postmark' ? 'selected' : '' }}>{{ __('Postmark') }}</option>
                            <option value="sparkpost" {{ old('type') == 'sparkpost' ? 'selected' : '' }}>{{ __('SparkPost') }}</option>
                            <option value="resend" {{ old('type') == 'resend' ? 'selected' : '' }}>{{ __('Resend') }}</option>
                            <option value="zeptomail-api" {{ old('type') == 'zeptomail-api' ? 'selected' : '' }}>{{ __('ZeptoMail API') }}</option>
                            <option value="emailit" {{ old('type') == 'emailit' ? 'selected' : '' }}>{{ __('Emailit') }}</option>
                        @else
                            <option value="smtp" {{ old('type') == 'smtp' ? 'selected' : '' }}>{{ __('SMTP') }}</option>
                            <option value="sendmail" {{ old('type') == 'sendmail' ? 'selected' : '' }}>{{ __('Sendmail') }}</option>
                            <option value="zeptomail" {{ old('type') == 'zeptomail' ? 'selected' : '' }}>{{ __('ZeptoMail') }}</option>
                            <option value="gmail" {{ old('type') == 'gmail' ? 'selected' : '' }}>{{ __('Gmail (App Password)') }}</option>
                            <option value="outlook" {{ old('type') == 'outlook' ? 'selected' : '' }}>{{ __('Outlook (App Password)') }}</option>
                            <option value="amazon-ses" {{ old('type') == 'amazon-ses' ? 'selected' : '' }}>{{ __('Amazon SES') }}</option>
                            <option value="mailgun" {{ old('type') == 'mailgun' ? 'selected' : '' }}>{{ __('Mailgun') }}</option>
                            <option value="sendgrid" {{ old('type') == 'sendgrid' ? 'selected' : '' }}>{{ __('SendGrid') }}</option>
                            <option value="postmark" {{ old('type') == 'postmark' ? 'selected' : '' }}>{{ __('Postmark') }}</option>
                            <option value="sparkpost" {{ old('type') == 'sparkpost' ? 'selected' : '' }}>{{ __('SparkPost') }}</option>
                            <option value="resend" {{ old('type') == 'resend' ? 'selected' : '' }}>{{ __('Resend') }}</option>
                            <option value="zeptomail-api" {{ old('type') == 'zeptomail-api' ? 'selected' : '' }}>{{ __('ZeptoMail API') }}</option>
                            <option value="emailit" {{ old('type') == 'emailit' ? 'selected' : '' }}>{{ __('Emailit') }}</option>
                        @endif
                    </select>
                    @error('type')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }}</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                </div>

                <div id="smtp-fields" class="contents" style="display: none;">
                    <div>
                        <label for="hostname" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Hostname') }}</label>
                        <input type="text" name="hostname" id="hostname" value="{{ old('hostname') }}" placeholder="{{ __('smtp.example.com') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Port') }}</label>
                        <input type="number" name="port" id="port" value="{{ old('port', 587) }}" min="1" max="65535" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Username') }}</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Password') }}</label>
                        <input type="password" name="password" id="password" value="{{ old('password') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    </div>

                    <div>
                        <label for="encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Encryption') }}</label>
                        <select name="encryption" id="encryption" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <option value="tls" {{ old('encryption', 'tls') == 'tls' ? 'selected' : '' }}>{{ __('TLS') }}</option>
                            <option value="ssl" {{ old('encryption') == 'ssl' ? 'selected' : '' }}>{{ __('SSL') }}</option>
                            <option value="none" {{ old('encryption') == 'none' ? 'selected' : '' }}>{{ __('None') }}</option>
                        </select>
                    </div>
                </div>

                <div class="sm:col-span-2" id="provider-settings" style="display: none;">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('Provider API Settings') }}</div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div id="mailgun-settings" style="display: none;">
                                    <label for="settings_domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Mailgun Domain') }}</label>
                                    <input type="text" name="settings[domain]" id="settings_domain" value="{{ old('settings.domain') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                </div>
                                <div id="mailgun-secret-settings" style="display: none;">
                                    <label for="settings_secret_mailgun" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Mailgun API Key') }}</label>
                                    <input type="password" name="settings[secret]" id="settings_secret_mailgun" value="" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <a href="https://app.mailgun.com/settings/api_security" target="_blank" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline">{{ __('Get API key here') }}</a>
                                    </p>
                                </div>
 
                            <div id="sendgrid-settings" style="display: none;">
                                <label for="settings_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SendGrid API Key') }}</label>
                                <input type="password" name="settings[api_key]" id="settings_api_key" value="" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div id="sendgrid-domain-settings" style="display: none;">
                                <label for="settings_sending_domain_sendgrid" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Sending Domain') }}</label>
                                <input type="text" name="settings[sending_domain]" id="settings_sending_domain_sendgrid" list="sendgrid_domains_list" value="{{ old('settings.sending_domain') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <datalist id="sendgrid_domains_list"></datalist>
                                <p id="sendgrid_fetch_status" class="mt-1 text-xs text-gray-500 dark:text-gray-400 hidden"></p>
                            </div>
                            <div id="sendgrid-from-email-settings" style="display: none;" class="sm:col-span-2">
                                <label for="sendgrid_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From Email') }}</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input type="text" name="from_email_prefix" id="sendgrid_from_email_prefix" value="{{ old('from_email_prefix') }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">@</span>
                                    <input type="text" id="sendgrid_from_domain_display" readonly placeholder="yourdomain.com" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm cursor-not-allowed">
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Full from email:') }} <span id="sendgrid_full_from_email" class="font-medium">hello@domain.com</span></p>
                                <input type="hidden" name="from_email" id="sendgrid_from_email" value="{{ old('from_email') }}">
                            </div>

                            <div id="resend-settings" style="display: none;">
                                <label for="settings_api_key_resend" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Resend API Key') }}</label>
                                <input type="password" name="settings[api_key]" id="settings_api_key_resend" value="" placeholder="re_..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <label for="settings_endpoint_resend" class="mt-3 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Resend Endpoint') }}</label>
                                <input type="text" name="settings[endpoint]" id="settings_endpoint_resend" value="https://api.resend.com" placeholder="https://api.resend.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <div class="mt-3 rounded-md bg-green-50 dark:bg-green-900/20 p-3">
                                    <p class="text-xs font-medium text-green-800 dark:text-green-200 mb-1">{{ __('Webhook Auto-Configuration') }}</p>
                                    <p class="text-xs text-green-700 dark:text-green-300">{{ __('Webhook will be created automatically at Resend using your API key on save.') }}</p>
                                    <code class="text-xs text-green-700 dark:text-green-300 break-all mt-1 block">{{ url('/webhooks/resend') }}</code>
                                </div>
                            </div>

                            <div id="postmark-settings" style="display: none;">
                                <label for="settings_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Postmark Token') }}</label>
                                <input type="password" name="settings[token]" id="settings_token" value="" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>

                            <div id="sparkpost-settings" style="display: none;">
                                <label for="settings_secret_sparkpost" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SparkPost API Key') }}</label>
                                <input type="password" name="settings[secret]" id="settings_secret_sparkpost" value="" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>

                            <div id="ses-key-settings" style="display: none;">
                                <label for="settings_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SES Key') }}</label>
                                <input type="password" name="settings[key]" id="settings_key" value="" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div id="ses-secret-settings" style="display: none;">
                                <label for="settings_secret_ses" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SES Secret') }}</label>
                                <input type="password" name="settings[secret]" id="settings_secret_ses" value="" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div id="ses-region-settings" style="display: none;">
                                <label for="settings_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SES Region') }}</label>
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

                            <div id="zeptomail-api-settings" style="display: none;">
                                <label for="settings_send_mail_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('ZeptoMail Send Mail Token') }}</label>
                                <input type="password" name="settings[send_mail_token]" id="settings_send_mail_token" value="" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div id="zeptomail-api-mode-settings" style="display: none;">
                                <label for="settings_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('ZeptoMail Mode') }}</label>
                                <select name="settings[mode]" id="settings_mode" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    <option value="raw" {{ old('settings.mode', 'raw') == 'raw' ? 'selected' : '' }}>{{ __('Raw HTML/Text') }}</option>
                                    <option value="template" {{ old('settings.mode') == 'template' ? 'selected' : '' }}>{{ __('Template') }}</option>
                                </select>
                            </div>
                            <div id="zeptomail-api-template-key-settings" style="display: none;">
                                <label for="settings_template_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Template Key') }}</label>
                                <input type="text" name="settings[template_key]" id="settings_template_key" value="{{ old('settings.template_key') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div id="zeptomail-api-template-alias-settings" style="display: none;">
                                <label for="settings_template_alias" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Template Alias') }}</label>
                                <input type="text" name="settings[template_alias]" id="settings_template_alias" value="{{ old('settings.template_alias') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div id="zeptomail-api-bounce-address-settings" style="display: none;">
                                <label for="settings_bounce_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bounce Address') }}</label>
                                <input type="email" name="settings[bounce_address]" id="settings_bounce_address" value="{{ old('settings.bounce_address') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div id="emailit-settings" style="display: none;">
                                <label for="settings_api_key_emailit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Emailit API Key') }}</label>
                                <input type="password" name="settings[api_key]" id="settings_api_key_emailit" value="" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div id="emailit-domain-settings" style="display: none;">
                                <label for="settings_sending_domain_emailit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Sending Domain') }}</label>
                                <input type="text" name="settings[sending_domain]" id="settings_sending_domain_emailit" list="emailit_domains_list" value="{{ old('settings.sending_domain') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                <datalist id="emailit_domains_list"></datalist>
                                <p id="emailit_fetch_status" class="mt-1 text-xs text-gray-500 dark:text-gray-400 hidden"></p>
                            </div>
                            <div id="emailit-from-email-settings" style="display: none;" class="sm:col-span-2">
                                <label for="emailit_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From Email') }}</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input type="text" name="from_email_prefix" id="emailit_from_email_prefix" value="{{ old('from_email_prefix') }}" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">@</span>
                                    <input type="text" id="emailit_from_domain_display" readonly placeholder="yourdomain.com" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm cursor-not-allowed">
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Full from email:') }} <span id="emailit_full_from_email" class="font-medium">hello@domain.com</span></p>
                                <input type="hidden" name="from_email" id="emailit_from_email" value="{{ old('from_email') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="common-from-email-wrap">
                    <label for="from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From Email') }}</label>
                    <input type="email" name="from_email" id="from_email" value="{{ old('from_email') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From Name') }}</label>
                    <input type="text" name="from_name" id="from_name" value="{{ old('from_name') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>


                <div>
                    <label for="second_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Second Quota (0 = unlimited)') }}</label>
                    <input type="number" name="second_quota" id="second_quota" value="{{ old('second_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="minute_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Minute Quota (0 = unlimited)') }}</label>
                    <input type="number" name="minute_quota" id="minute_quota" value="{{ old('minute_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="hourly_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Hourly Quota (0 = unlimited)') }}</label>
                    <input type="number" name="hourly_quota" id="hourly_quota" value="{{ old('hourly_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="daily_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Daily Quota (0 = unlimited)') }}</label>
                    <input type="number" name="daily_quota" id="daily_quota" value="{{ old('daily_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label for="monthly_quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Monthly Quota (0 = unlimited)') }}</label>
                    <input type="number" name="monthly_quota" id="monthly_quota" value="{{ old('monthly_quota', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Notes') }}</label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">{{ old('notes') }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="use_for" value="1" {{ old('use_for', true) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Use for campaigns') }}</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="use_for_transactional" value="1" {{ old('use_for_transactional') ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Use for transactional') }}</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="locked" value="1" {{ old('locked') ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Locked') }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-button href="{{ route('admin.delivery-servers.index') }}" variant="secondary">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="primary">{{ __('Create Server') }}</x-button>
            </div>
        </form>
    </x-card>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeEl = document.getElementById('type');
    const hostEl = document.getElementById('hostname');
    const portEl = document.getElementById('port');
    const encEl = document.getElementById('encryption');
    const smtpFields = document.getElementById('smtp-fields');
    const providerSettings = document.getElementById('provider-settings');
    const mailgunSettings = document.getElementById('mailgun-settings');
    const mailgunSecretSettings = document.getElementById('mailgun-secret-settings');
    const sendgridSettings = document.getElementById('sendgrid-settings');
    const sendgridDomainSettings = document.getElementById('sendgrid-domain-settings');
    const sendgridFromEmailSettings = document.getElementById('sendgrid-from-email-settings');
    const resendSettings = document.getElementById('resend-settings');
    const postmarkSettings = document.getElementById('postmark-settings');
    const sparkpostSettings = document.getElementById('sparkpost-settings');
    const sesKeySettings = document.getElementById('ses-key-settings');
    const sesSecretSettings = document.getElementById('ses-secret-settings');
    const sesRegionSettings = document.getElementById('ses-region-settings');
    const zeptoMailApiSettings = document.getElementById('zeptomail-api-settings');
    const zeptoMailApiModeSettings = document.getElementById('zeptomail-api-mode-settings');
    const zeptoMailApiTemplateKeySettings = document.getElementById('zeptomail-api-template-key-settings');
    const zeptoMailApiTemplateAliasSettings = document.getElementById('zeptomail-api-template-alias-settings');
    const zeptoMailApiBounceAddressSettings = document.getElementById('zeptomail-api-bounce-address-settings');
    const emailitSettings = document.getElementById('emailit-settings');

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
        'resend': { host: '', port: 587, enc: 'tls' },
        'zeptomail-api': { host: '', port: 587, enc: 'tls' },
        'emailit': { host: '', port: 587, enc: 'tls' },
    };

    function getDef(type) {
        return defaults[type] || { host: '', port: 587, enc: 'tls' };
    }

    function applyDefaults(previousType) {
        const type = typeEl.value;
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

        const commonFromEmailWrap = document.getElementById('common-from-email-wrap');
        if (commonFromEmailWrap) {
            commonFromEmailWrap.style.display = ['sendgrid', 'mailgun', 'sparkpost', 'resend', 'amazon-ses', 'emailit'].includes(type) ? 'none' : 'block';
        }

        if (providerSettings) {
            const typeNeedsSettings = ['mailgun', 'sendgrid', 'postmark', 'sparkpost', 'resend', 'amazon-ses', 'zeptomail-api', 'emailit'].includes(type);
            const typeNeedsSmtp = ['smtp', 'gmail', 'outlook', 'zeptomail'].includes(type);
            if (smtpFields) smtpFields.style.display = typeNeedsSmtp ? 'contents' : 'none';
            providerSettings.style.display = typeNeedsSettings ? 'block' : 'none';
            if (mailgunSettings) mailgunSettings.style.display = type === 'mailgun' ? 'block' : 'none';
            if (mailgunSecretSettings) mailgunSecretSettings.style.display = type === 'mailgun' ? 'block' : 'none';
            if (sendgridSettings) sendgridSettings.style.display = type === 'sendgrid' ? 'block' : 'none';
            if (sendgridDomainSettings) sendgridDomainSettings.style.display = type === 'sendgrid' ? 'block' : 'none';
            if (sendgridFromEmailSettings) sendgridFromEmailSettings.style.display = type === 'sendgrid' ? 'block' : 'none';
            if (resendSettings) resendSettings.style.display = type === 'resend' ? 'block' : 'none';
            if (postmarkSettings) postmarkSettings.style.display = type === 'postmark' ? 'block' : 'none';
            if (sparkpostSettings) sparkpostSettings.style.display = type === 'sparkpost' ? 'block' : 'none';
            if (sesKeySettings) sesKeySettings.style.display = type === 'amazon-ses' ? 'block' : 'none';
            if (sesSecretSettings) sesSecretSettings.style.display = type === 'amazon-ses' ? 'block' : 'none';
            if (sesRegionSettings) sesRegionSettings.style.display = type === 'amazon-ses' ? 'block' : 'none';
            if (zeptoMailApiSettings) zeptoMailApiSettings.style.display = type === 'zeptomail-api' ? 'block' : 'none';
            if (zeptoMailApiModeSettings) zeptoMailApiModeSettings.style.display = type === 'zeptomail-api' ? 'block' : 'none';
            if (zeptoMailApiTemplateKeySettings) zeptoMailApiTemplateKeySettings.style.display = type === 'zeptomail-api' ? 'block' : 'none';
            if (zeptoMailApiTemplateAliasSettings) zeptoMailApiTemplateAliasSettings.style.display = type === 'zeptomail-api' ? 'block' : 'none';
            if (zeptoMailApiBounceAddressSettings) zeptoMailApiBounceAddressSettings.style.display = type === 'zeptomail-api' ? 'block' : 'none';
            if (emailitSettings) emailitSettings.style.display = type === 'emailit' ? 'block' : 'none';
            const emailitDomainSettings = document.getElementById('emailit-domain-settings');
            const emailitFromEmailSettings = document.getElementById('emailit-from-email-settings');
            if (emailitDomainSettings) emailitDomainSettings.style.display = type === 'emailit' ? 'block' : 'none';
            if (emailitFromEmailSettings) emailitFromEmailSettings.style.display = type === 'emailit' ? 'block' : 'none';
        }

    }

    let lastType = typeEl.value;

    typeEl.addEventListener('change', function () {
        applyDefaults(lastType);
        lastType = typeEl.value;
    });

    applyDefaults(lastType);

    // SendGrid from email builder
    const sendgridPrefixInput = document.getElementById('sendgrid_from_email_prefix');
    const sendgridDomainInput = document.getElementById('settings_sending_domain_sendgrid');
    const sendgridDomainDisplay = document.getElementById('sendgrid_from_domain_display');
    const sendgridFromEmail = document.getElementById('sendgrid_from_email');
    const sendgridFullFromEmail = document.getElementById('sendgrid_full_from_email');
    const sendgridApiKey = document.getElementById('settings_api_key');
    const sendgridDomainsList = document.getElementById('sendgrid_domains_list');
    const sendgridFetchStatus = document.getElementById('sendgrid_fetch_status');

    function updateSendgridFromEmail() {
        const prefix = ((sendgridPrefixInput && sendgridPrefixInput.value) || 'hello').trim();
        const domain = ((sendgridDomainInput && sendgridDomainInput.value) || '').trim();
        if (sendgridDomainDisplay) sendgridDomainDisplay.value = domain;
        if (sendgridFullFromEmail) sendgridFullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (sendgridFromEmail) sendgridFromEmail.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (sendgridPrefixInput) sendgridPrefixInput.addEventListener('input', updateSendgridFromEmail);
    if (sendgridDomainInput) sendgridDomainInput.addEventListener('input', updateSendgridFromEmail);

    updateSendgridFromEmail();

    function fetchSendGridDomains() {
        const apiKey = sendgridApiKey ? sendgridApiKey.value : '';
        if (!apiKey || apiKey.length < 10 || !sendgridDomainsList) return;

        if (sendgridFetchStatus) {
            sendgridFetchStatus.textContent = 'Fetching domains...';
            sendgridFetchStatus.classList.remove('hidden');
        }

        fetch('{{ route('admin.delivery-servers.fetch-sendgrid-domains') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => response.json())
        .then(data => {
            if (sendgridFetchStatus) sendgridFetchStatus.classList.add('hidden');
            sendgridDomainsList.innerHTML = '';
            if (data.domains && data.domains.length > 0) {
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    option.label = domain.status ? domain.status : '';
                    sendgridDomainsList.appendChild(option);
                });
                if (sendgridDomainInput && !sendgridDomainInput.value.trim()) {
                    sendgridDomainInput.value = data.domains[0].domain;
                    updateSendgridFromEmail();
                }
                if (sendgridFetchStatus) {
                    sendgridFetchStatus.textContent = data.domains.length + ' domain(s) found.';
                    sendgridFetchStatus.classList.remove('hidden', 'text-red-600', 'dark:text-red-400');
                    sendgridFetchStatus.classList.add('text-gray-500', 'dark:text-gray-400');
                }
            } else {
                if (sendgridFetchStatus) {
                    sendgridFetchStatus.textContent = 'No domains found in this SendGrid account.';
                    sendgridFetchStatus.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                    sendgridFetchStatus.classList.add('text-red-600', 'dark:text-red-400');
                }
            }
        })
        .catch(error => {
            if (sendgridFetchStatus) {
                sendgridFetchStatus.textContent = 'Failed to fetch domains: ' + (error.message || 'Unknown error');
                sendgridFetchStatus.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                sendgridFetchStatus.classList.add('text-red-600', 'dark:text-red-400');
            }
        });
    }

    if (sendgridApiKey) {
        sendgridApiKey.addEventListener('blur', fetchSendGridDomains);
    }

    // Emailit from email builder
    const emailitPrefixInput = document.getElementById('emailit_from_email_prefix');
    const emailitDomainInput = document.getElementById('settings_sending_domain_emailit');
    const emailitDomainDisplay = document.getElementById('emailit_from_domain_display');
    const emailitFromEmail = document.getElementById('emailit_from_email');
    const emailitFullFromEmail = document.getElementById('emailit_full_from_email');

    function updateEmailitFromEmail() {
        const prefix = ((emailitPrefixInput && emailitPrefixInput.value) || 'hello').trim();
        const domain = ((emailitDomainInput && emailitDomainInput.value) || '').trim();
        if (emailitDomainDisplay) emailitDomainDisplay.value = domain;
        if (emailitFullFromEmail) emailitFullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (emailitFromEmail) emailitFromEmail.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (emailitPrefixInput) emailitPrefixInput.addEventListener('input', updateEmailitFromEmail);
    if (emailitDomainInput) emailitDomainInput.addEventListener('input', updateEmailitFromEmail);

    updateEmailitFromEmail();

    // Emailit domain fetch
    const emailitApiKey = document.getElementById('settings_api_key_emailit');
    const emailitDomainsList = document.getElementById('emailit_domains_list');
    const emailitFetchStatus = document.getElementById('emailit_fetch_status');

    function fetchEmailitDomains() {
        const apiKey = emailitApiKey ? emailitApiKey.value : '';
        if (!apiKey || apiKey.length < 10 || !emailitDomainsList) return;

        if (emailitFetchStatus) {
            emailitFetchStatus.textContent = 'Fetching domains...';
            emailitFetchStatus.classList.remove('hidden');
        }

        fetch('{{ route('admin.delivery-servers.fetch-emailit-domains') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => response.json())
        .then(data => {
            emailitDomainsList.innerHTML = '';
            if (data.domains && data.domains.length > 0) {
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    option.label = domain.status ? domain.status : '';
                    emailitDomainsList.appendChild(option);
                });
                if (emailitDomainInput && !emailitDomainInput.value.trim()) {
                    emailitDomainInput.value = data.domains[0].domain;
                    updateEmailitFromEmail();
                }
                if (emailitFetchStatus) {
                    emailitFetchStatus.textContent = data.domains.length + ' domain(s) found.';
                    emailitFetchStatus.classList.remove('hidden', 'text-red-600', 'dark:text-red-400');
                    emailitFetchStatus.classList.add('text-gray-500', 'dark:text-gray-400');
                }
            } else {
                if (emailitFetchStatus) {
                    emailitFetchStatus.textContent = 'No domains found in this Emailit account.';
                    emailitFetchStatus.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                    emailitFetchStatus.classList.add('text-red-600', 'dark:text-red-400');
                }
            }
        })
        .catch(error => {
            if (emailitFetchStatus) {
                emailitFetchStatus.textContent = 'Failed to fetch domains: ' + (error.message || 'Unknown error');
                emailitFetchStatus.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                emailitFetchStatus.classList.add('text-red-600', 'dark:text-red-400');
            }
        });
    }

    if (emailitApiKey) {
        emailitApiKey.addEventListener('blur', fetchEmailitDomains);
    }
});
</script>
@endpush
@endsection


<div class="px-5 py-4 md:px-6">
    @php
        $settingLabel = match ((string) $setting->key) {
            'brand_color' => 'Brand Color',
            'home_redirect_enabled' => 'Home Redirect',
            'storage_url_prefix' => 'Storage URL Prefix',
            'toast_position' => 'Toast Position',
            'public_meta_image' => 'Meta / OG Image',
            'new_registered_customer_plan_id' => 'New Registered Customer Plan',
            'campaign_send_gap_ms' => 'Campaign Gap Between Emails (ms)',
            'campaign_rate_limit_per_minute' => 'Campaign Rate Limit (emails/min)',
            'campaign_chunk_size' => 'Campaign Chunk Size',
            'rate_limit_error_pause_minutes' => 'Rate-Limit Error Pause (minutes)',
            'queue_worker_continuous' => 'Continuous Queue Worker',
            'campaign_schedule_enforced' => 'Enforce Campaign Schedule',
            'campaign_auto_pause_enabled' => 'Auto Pause-Resume Stalled Campaigns',
            default => ucwords(str_replace(['_', '-'], ' ', $setting->key)),
        };

        $settingDescription = match ((string) $setting->key) {
            default => $setting->description,
        };
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%] md:gap-y-6">
        <div class="space-y-1">
            <label for="setting_{{ $setting->key }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ $settingLabel }}
            </label>

            @if($settingDescription)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $settingDescription }}@if($setting->key === 'storage_url_prefix') Example: set to <span class="font-mono">public</span> to make URLs <span class="font-mono">/public/storage/...</span>.@endif</p>
            @endif

            @if(in_array($setting->key, ['google_client_id', 'google_client_secret'], true))
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <a
                        href="https://console.cloud.google.com/apis/credentials"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline"
                    >
                        Get your Google OAuth Client ID/Secret (Google Cloud Console → APIs & Services → Credentials)
                    </a>
                </p>
            @endif
        </div>

        <div class="flex w-full md:justify-end">
            <div class="w-full md:max-w-[420px] md:ml-auto">
    @if($setting->key === 'home_redirect_url')
        @php
            // Rendered inside the home_redirect_enabled control.
        @endphp
    @else
    <div class="space-y-4">
        @if($setting->key === 'brand_color')
            @php
                $brandColorValue = is_string($setting->value) ? trim($setting->value) : '';
                if ($brandColorValue === '' || !preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $brandColorValue)) {
                    $brandColorValue = '#3b82f6';
                }
            @endphp
            <div class="flex items-center">
                <input
                    type="color"
                    name="{{ $setting->key }}"
                    id="setting_{{ $setting->key }}"
                    value="{{ $brandColorValue }}"
                    class="admin-color-picker"
                    aria-label="Brand color"
                >
            </div>
        @elseif($setting->key === 'billing_default_provider')
            @php
                $billingDefaultProviderValue = is_string($setting->value) ? trim($setting->value) : 'stripe';
                $billingProviders = [
                    'stripe' => 'Stripe',
                    'uddoktapay' => 'UddoktaPay',
                    'paypal' => 'PayPal',
                    'razorpay' => 'Razorpay',
                    'paystack' => 'Paystack',
                    'flutterwave' => 'Flutterwave',
                    'manual' => 'Manual',
                ];
            @endphp
            <div class="space-y-3">
                <select
                    name="{{ $setting->key }}"
                    id="setting_{{ $setting->key }}"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
                    @foreach($billingProviders as $providerValue => $providerLabel)
                        <option value="{{ $providerValue }}" {{ (string) old($setting->key, $billingDefaultProviderValue) === (string) $providerValue ? 'selected' : '' }}>
                            {{ $providerLabel }}
                        </option>
                    @endforeach
                </select>
                <a
                    href="{{ route('admin.payment-methods.index') }}"
                    class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    {{ __('Manage payment methods') }}
                </a>
            </div>
        @elseif($setting->key === 'home_redirect_enabled')
            @php
                $homeRedirectEnabledValue = (bool) ($setting->value ?? false);
                try {
                    $homeRedirectUrlValue = (string) \App\Models\Setting::get('home_redirect_url', '');
                } catch (\Throwable $e) {
                    $homeRedirectUrlValue = '';
                }
            @endphp
            <div x-data="{ enabled: {{ $homeRedirectEnabledValue ? 'true' : 'false' }} }" class="space-y-4">
                <div class="flex items-center md:justify-end">
                    <input type="hidden" name="{{ $setting->key }}" value="0">
                    <label class="inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="{{ $setting->key }}"
                            id="setting_{{ $setting->key }}"
                            value="1"
                            {{ $homeRedirectEnabledValue ? 'checked' : '' }}
                            @change="enabled = $event.target.checked"
                            class="sr-only peer"
                        >
                        <div class="relative w-11 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 dark:bg-gray-700 peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                    </label>
                </div>

                <div x-show="enabled" x-cloak>
                    <label for="setting_home_redirect_url" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Redirect URL</label>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use a full URL (https://...) or a path starting with /</p>
                    <div class="mt-2">
                        <input
                            type="text"
                            name="home_redirect_url"
                            id="setting_home_redirect_url"
                            value="{{ old('home_redirect_url', $homeRedirectUrlValue) }}"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        >
                    </div>
                    @error('home_redirect_url')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @elseif($setting->key === 'toast_position')
            @php
                $toastPositionValue = is_string($setting->value) ? trim($setting->value) : '';
                $toastPositionValue = $toastPositionValue !== '' ? $toastPositionValue : 'top_right';
                $toastPositions = [
                    'top_left' => 'Top Left',
                    'top_center' => 'Top Center',
                    'top_right' => 'Top Right',
                    'bottom_left' => 'Bottom Left',
                    'bottom_center' => 'Bottom Center',
                    'bottom_right' => 'Bottom Right',
                ];
            @endphp
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                @foreach($toastPositions as $posValue => $posLabel)
                    <option value="{{ $posValue }}" {{ (string) old($setting->key, $toastPositionValue) === (string) $posValue ? 'selected' : '' }}>
                        {{ $posLabel }}
                    </option>
                @endforeach
            </select>
        @elseif(in_array($setting->key, ['app_logo', 'app_logo_dark', 'dashboard_logo', 'dashboard_logo_dark', 'site_favicon', 'public_meta_image'], true))
            @php
                $logoDisk = (string) config('filesystems.branding_disk', 'public');
                $logoUrl = is_string($setting->value) && trim($setting->value) !== ''
                    ? ($logoDisk === 'public'
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim((string) $setting->value, '/'))
                        : \Illuminate\Support\Facades\Storage::disk($logoDisk)->url($setting->value))
                    : null;

                $imageMeta = match ((string) $setting->key) {
                    'app_logo' => [
                        'alt' => 'App Logo',
                        'accept' => 'image/*',
                    ],
                    'app_logo_dark' => [
                        'alt' => 'App Logo Dark',
                        'accept' => 'image/*',
                    ],
                    'dashboard_logo' => [
                        'alt' => 'Dashboard Logo',
                        'accept' => 'image/*',
                    ],
                    'dashboard_logo_dark' => [
                        'alt' => 'Dashboard Logo Dark',
                        'accept' => 'image/*',
                    ],
                    'site_favicon' => [
                        'alt' => 'Site Favicon',
                        'accept' => 'image/*,.ico',
                    ],
                    'public_meta_image' => [
                        'alt' => 'Public Meta Image',
                        'accept' => 'image/*',
                    ],
                    default => [
                        'alt' => $settingLabel,
                        'accept' => 'image/*',
                    ],
                };
            @endphp

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center" data-image-upload-wrapper data-image-setting="{{ $setting->key }}">
                <div class="{{ $logoUrl ? '' : 'hidden' }}" data-image-preview-wrapper>
                    <div class="relative flex h-28 w-28 items-center justify-center overflow-visible rounded-2xl border border-gray-200 bg-transparent p-3 dark:border-gray-700">
                        @if($logoUrl)
                            <img
                                src="{{ $logoUrl }}"
                                alt="{{ $imageMeta['alt'] }}"
                                class="h-full w-full object-contain"
                                data-image-preview-image
                            />
                        @endif

                        <input type="hidden" name="remove_{{ $setting->key }}" value="0" data-image-remove-input>

                        <button
                            type="button"
                            class="absolute right-2 top-2 z-20 inline-flex h-7 w-7 cursor-pointer items-center justify-center rounded-full bg-gray-900/80 text-white shadow-lg ring-2 ring-white transition hover:bg-gray-900 dark:ring-gray-800"
                            aria-label="Remove {{ $imageMeta['alt'] }}"
                            data-image-remove-button
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <label
                    for="setting_{{ $setting->key }}"
                    class="{{ $logoUrl ? 'hidden' : '' }} flex min-h-[140px] w-full cursor-pointer flex-col items-center justify-center rounded-2xl border border-gray-300 bg-white px-6 py-5 text-center transition hover:border-primary-400 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-950/40 dark:hover:border-primary-400 dark:hover:bg-gray-900/60"
                    data-image-upload-dropzone
                >
                    <input
                        type="file"
                        name="{{ $setting->key }}"
                        id="setting_{{ $setting->key }}"
                        accept="{{ $imageMeta['accept'] }}"
                        class="sr-only"
                        data-image-upload-input
                    >
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-upload-icon lucide-cloud-upload"><path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/></svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">Click to upload or drag and drop</p>
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">SVG, PNG, JPG or GIF (max. 800x400px)</p>
                </label>
            </div>
        @elseif($setting->key === 'billing_currency')
            @php
                $popularCurrencies = [
                    'USD' => 'USD - US Dollar',
                    'EUR' => 'EUR - Euro',
                    'GBP' => 'GBP - British Pound',
                    'CAD' => 'CAD - Canadian Dollar',
                    'AUD' => 'AUD - Australian Dollar',
                    'NZD' => 'NZD - New Zealand Dollar',
                    'JPY' => 'JPY - Japanese Yen',
                    'CNY' => 'CNY - Chinese Yuan',
                    'INR' => 'INR - Indian Rupee',
                    'SGD' => 'SGD - Singapore Dollar',
                ];

                $billingCurrencyValue = is_string($setting->value) ? strtoupper(trim((string) $setting->value)) : '';
            @endphp
            <input
                type="text"
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                value="{{ old($setting->key, $billingCurrencyValue) }}"
                maxlength="3"
                list="billing_currency_options"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
            <datalist id="billing_currency_options">
                @foreach($popularCurrencies as $currencyCode => $currencyLabel)
                    <option value="{{ $currencyCode }}" label="{{ $currencyLabel }}"></option>
                @endforeach
            </datalist>
        @elseif($setting->key === 'site_language')
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                @foreach(($activeLocales ?? []) as $loc)
                    <option value="{{ $loc->code }}" {{ (string) $setting->value === (string) $loc->code ? 'selected' : '' }}>
                        {{ $loc->code }} — {{ $loc->name }}
                    </option>
                @endforeach
            </select>
        @elseif($setting->key === 'admin_font_family')
            @php
                $adminFontFamilies = [];
                $cfgFamilies = config('magicbuilds.fonts.supported_google_families', []);
                if (is_array($cfgFamilies)) {
                    foreach ($cfgFamilies as $fontName) {
                        if (is_string($fontName) && trim($fontName) !== '') {
                            $adminFontFamilies[trim((string) $fontName)] = trim((string) $fontName);
                        }
                    }
                }

                $adminFontFamilyValue = is_string($setting->value) ? trim($setting->value) : '';
                $adminFontFamilyValue = $adminFontFamilyValue !== '' ? $adminFontFamilyValue : 'Inter';
                $adminFontFamilyIsCustom = !array_key_exists($adminFontFamilyValue, $adminFontFamilies);
            @endphp
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                @if($adminFontFamilyIsCustom)
                    <option value="{{ $adminFontFamilyValue }}" selected>
                        {{ $adminFontFamilyValue }}
                    </option>
                @endif
                @foreach($adminFontFamilies as $fontValue => $fontLabel)
                    <option value="{{ $fontValue }}" {{ (string) $adminFontFamilyValue === (string) $fontValue ? 'selected' : '' }}>
                        {{ $fontLabel }}
                    </option>
                @endforeach
            </select>
        @elseif($setting->key === 'default_storage_driver')
            @php
                $storageDrivers = [
                    'local' => 'Local',
                    's3' => 'Amazon S3',
                    'wasabi' => 'Wasabi (S3 Compatible)',
                    'gcs' => 'Google Cloud Storage',
                ];
            @endphp
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                @foreach($storageDrivers as $driverKey => $driverLabel)
                    <option value="{{ $driverKey }}" {{ (string) $setting->value === (string) $driverKey ? 'selected' : '' }}>
                        {{ $driverLabel }}
                    </option>
                @endforeach
            </select>
        @elseif(in_array($setting->key, ['default_customer_group_id', 'new_registered_customer_group_id'], true))
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                <option value="" {{ empty($setting->value) ? 'selected' : '' }}>None</option>
                @foreach(($customerGroups ?? []) as $groupId => $groupName)
                    <option value="{{ $groupId }}" {{ (string) $setting->value === (string) $groupId ? 'selected' : '' }}>
                        {{ $groupName }}
                    </option>
                @endforeach
            </select>
            @if(empty($customerGroups ?? []))
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('No customer groups exist. New registrations are handled by the plan selected below.') }}</p>
            @endif
        @elseif($setting->key === 'new_registered_customer_plan_id')
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                <option value="" {{ empty($setting->value) ? 'selected' : '' }}>None</option>
                @foreach(($plans ?? []) as $planId => $planName)
                    <option value="{{ $planId }}" {{ (string) $setting->value === (string) $planId ? 'selected' : '' }}>
                        {{ $planName }}
                    </option>
                @endforeach
            </select>
        @elseif(in_array($setting->key, ['transactional_delivery_server_id', 'verification_delivery_server_id', 'password_reset_delivery_server_id', 'project_invitation_delivery_server_id'], true))
            @php
                $deliveryServerSelectOptions = $deliveryServerOptions ?? [];
                if ($setting->key !== 'transactional_delivery_server_id') {
                    $deliveryServerSelectOptions = ['inherit' => 'Inherit (Transactional Default)'] + $deliveryServerSelectOptions;
                }

                $deliveryServerSelectValue = is_string($setting->value) ? trim($setting->value) : '';
            @endphp
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                @foreach(($deliveryServerSelectOptions ?? []) as $optValue => $optLabel)
                    <option value="{{ $optValue }}" {{ (string) $deliveryServerSelectValue === (string) $optValue ? 'selected' : '' }}>
                        {{ $optLabel }}
                    </option>
                @endforeach
            </select>
        @elseif($setting->key === 'from_email')
            <input
                type="email"
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                value="{{ $setting->value }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
        @elseif($setting->key === 'google_client_secret')
            <div class="relative" data-secret-wrapper>
                <input
                    type="password"
                    name="{{ $setting->key }}"
                    id="setting_{{ $setting->key }}"
                    value="{{ !empty($setting->value) ? '********' : '' }}"
                    autocomplete="new-password"
                    data-secret-url="{{ route('admin.settings.secret', ['key' => $setting->key]) }}"
                    data-secret-input
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm pr-10"
                >

                <button
                    type="button"
                    data-toggle-secret
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 dark:text-gray-300"
                    aria-label="{{ __('Toggle secret visibility') }}"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
        @elseif(in_array($setting->key, ['openai_api_key', 'gemini_api_key', 'claude_api_key', 'anthropic_api_key', 'deepseek_api_key', 'kimi_api_key', 'glm_api_key', 'grok_api_key', 'openrouter_api_key', 'qwen_api_key', 'minimax_api_key', 'xiaomi_api_key'], true))
            <div class="relative" data-secret-wrapper>
                <input
                    type="password"
                    name="{{ $setting->key }}"
                    id="setting_{{ $setting->key }}"
                    value="{{ !empty($setting->value) ? '********' : '' }}"
                    autocomplete="new-password"
                    placeholder="Leave blank to keep current key"
                    data-secret-url="{{ route('admin.settings.secret', ['key' => $setting->key]) }}"
                    data-secret-input
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm pr-10"
                >

                <button
                    type="button"
                    data-toggle-secret
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 dark:text-gray-300"
                    aria-label="{{ __('Toggle secret visibility') }}"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
        @elseif($setting->key === 'google_redirect_uri')
            <input
                type="url"
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                value="{{ $setting->value }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >

            @php
                $googleRedirectUris = array_values(array_unique(array_filter([
                    route('customer.auth.google.callback'),
                    route('admin.auth.google.callback'),
                ], fn ($v) => is_string($v) && trim($v) !== '')));
            @endphp

            <div class="mt-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Authorized redirect URIs (add all in Google Cloud Console)') }}</div>
                <div class="mt-1 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 font-mono text-xs whitespace-pre-wrap break-all">{{ implode("\n", $googleRedirectUris) }}</div>
            </div>
        @elseif($setting->key === 'email_verification_message' || $setting->key === 'password_reset_message' || $setting->key === 'site_meta')
            <textarea
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                rows="4"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >{{ $setting->value }}</textarea>
        @elseif($setting->key === 'gdpr_notice_description')
            <textarea
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                rows="4"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >{{ $setting->value }}</textarea>
        @elseif($setting->key === 'gdpr_notice_position')
            @php
                $gdprPositionValue = is_string($setting->value) ? trim($setting->value) : '';
                $gdprPositions = [
                    'bottom_left' => 'Bottom Left',
                    'bottom_right' => 'Bottom Right',
                    'bottom_full_width' => 'Bottom Full Width',
                ];
                if (!array_key_exists($gdprPositionValue, $gdprPositions)) {
                    $gdprPositionValue = 'bottom_full_width';
                }
            @endphp
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                @foreach($gdprPositions as $posValue => $posLabel)
                    <option value="{{ $posValue }}" {{ (string) $gdprPositionValue === (string) $posValue ? 'selected' : '' }}>
                        {{ $posLabel }}
                    </option>
                @endforeach
            </select>
        @elseif($setting->type === 'boolean')
            <div class="flex w-full justify-end">
                <input type="hidden" name="{{ $setting->key }}" value="0">
                <label class="inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        name="{{ $setting->key }}"
                        id="setting_{{ $setting->key }}"
                        value="1"
                        {{ $setting->value ? 'checked' : '' }}
                        class="sr-only peer"
                    >
                    <div class="relative w-11 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 dark:bg-gray-700 peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                </label>
            </div>
        @elseif($setting->key === 'storage_driver')
            @php
                $storageDrivers = [
                    'local' => 'Local',
                    's3' => 'Amazon S3',
                    'wasabi' => 'Wasabi (S3 Compatible)',
                    'gcs' => 'Google Cloud Storage',
                ];
            @endphp
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                @foreach($storageDrivers as $driverKey => $driverLabel)
                    <option value="{{ $driverKey }}" {{ (string) $setting->value === (string) $driverKey ? 'selected' : '' }}>
                        {{ $driverLabel }}
                    </option>
                @endforeach
            </select>
        @elseif(in_array($setting->key, ['google_client_secret', 's3_secret', 'wasabi_secret', 'openai_api_key', 'gemini_api_key', 'claude_api_key', 'anthropic_api_key', 'deepseek_api_key', 'kimi_api_key', 'glm_api_key', 'grok_api_key', 'openrouter_api_key', 'qwen_api_key', 'minimax_api_key', 'xiaomi_api_key'], true))
            <div class="relative" data-secret-wrapper>
                <input
                    type="password"
                    name="{{ $setting->key }}"
                    id="setting_{{ $setting->key }}"
                    value="{{ !empty($setting->value) ? '********' : '' }}"
                    autocomplete="new-password"
                    data-secret-url="{{ route('admin.settings.secret', ['key' => $setting->key]) }}"
                    data-secret-input
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm pr-10"
                >

                <button
                    type="button"
                    data-toggle-secret
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 dark:text-gray-300"
                    aria-label="{{ __('Toggle secret visibility') }}"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
        @elseif($setting->type === 'json' || $setting->type === 'array')
            <textarea
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                rows="4"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >{{ is_array($setting->value) ? json_encode($setting->value, JSON_PRETTY_PRINT) : $setting->value }}</textarea>
        @elseif(in_array($setting->key, ['homepage_page_id', 'login_page_id', 'register_page_id'], true))
            @php
                $pageIdValue = is_numeric($setting->value) ? (int) $setting->value : null;
            @endphp
            <select
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
                <option value="" {{ is_null($pageIdValue) ? 'selected' : '' }}>Default (built-in)</option>
                @foreach(($pages ?? []) as $pid => $ptitle)
                    <option value="{{ $pid }}" {{ $pageIdValue === (int) $pid ? 'selected' : '' }}>{{ $ptitle }}</option>
                @endforeach
            </select>
        @elseif($setting->type === 'integer')
            <input
                type="number"
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                value="{{ $setting->value }}"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
        @else
            <input
                type="text"
                name="{{ $setting->key }}"
                id="setting_{{ $setting->key }}"
                value="{{ $setting->value }}"
                @if($setting->key === 'storage_url_prefix') placeholder="public" @endif
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
        @endif
    </div>
    @endif

    @error($setting->key)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    @once
        <script>
            const boundImageUploadRoots = new WeakSet();

            const initImageUploadFields = () => {
                document.querySelectorAll('[data-image-upload-wrapper]').forEach((root) => {
                    if (boundImageUploadRoots.has(root)) {
                        return;
                    }

                    boundImageUploadRoots.add(root);

                    const input = root.querySelector('[data-image-upload-input]');
                    const dropzone = root.querySelector('[data-image-upload-dropzone]');
                    const previewWrapper = root.querySelector('[data-image-preview-wrapper]');
                    const previewImage = root.querySelector('[data-image-preview-image]');
                    const removeButton = root.querySelector('[data-image-remove-button]');
                    const removeInput = root.querySelector('[data-image-remove-input]');

                    let objectUrl = null;

                    const revokeObjectUrl = () => {
                        if (!objectUrl) {
                            return;
                        }

                        URL.revokeObjectURL(objectUrl);
                        objectUrl = null;
                    };

                    const clearFileInput = () => {
                        try {
                            if (window.DataTransfer && input) {
                                const dataTransfer = new DataTransfer();
                                input.files = dataTransfer.files;
                            }
                        } catch (error) {
                            // Fallback below.
                        }

                        if (input) {
                            input.value = '';
                        }
                    };

                    const showPreview = (file) => {
                        if (!file) {
                            return;
                        }

                        if (!previewWrapper || !dropzone || !input) {
                            return;
                        }

                        revokeObjectUrl();
                        objectUrl = URL.createObjectURL(file);

                        if (removeInput) {
                            removeInput.value = '0';
                        }

                        if (previewImage) {
                            previewImage.src = objectUrl;
                        } else {
                            const image = document.createElement('img');
                            image.src = objectUrl;
                            image.alt = root.dataset.imageSetting ?? 'Uploaded image';
                            image.className = 'h-full w-full object-contain';
                            image.setAttribute('data-image-preview-image', '');

                            const previewShell = previewWrapper.querySelector('.relative');
                            if (previewShell) {
                                previewShell.prepend(image);
                            }
                        }

                        previewWrapper.style.display = '';
                        previewWrapper.classList.remove('hidden');
                        dropzone.style.display = 'none';
                        dropzone.classList.add('hidden');
                    };

                    if (dropzone && input) {
                        const setDragging = (isDragging) => {
                            dropzone.classList.toggle('ring-2', isDragging);
                            dropzone.classList.toggle('ring-primary-500', isDragging);
                            dropzone.classList.toggle('border-primary-400', isDragging);
                        };

                        ['dragenter', 'dragover'].forEach((eventName) => {
                            dropzone.addEventListener(eventName, (event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                setDragging(true);
                            });
                        });

                        ['dragleave', 'drop'].forEach((eventName) => {
                            dropzone.addEventListener(eventName, (event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                setDragging(false);
                            });
                        });

                        input.addEventListener('change', () => {
                            if (removeInput) {
                                removeInput.value = '0';
                            }

                            const file = input.files && input.files.length ? input.files[0] : null;
                            showPreview(file);
                        });

                        dropzone.addEventListener('drop', (event) => {
                            const files = event.dataTransfer?.files;
                            if (!files || !files.length) {
                                return;
                            }

                            input.files = files;
                            if (removeInput) {
                                removeInput.value = '0';
                            }
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    }

                    if (removeButton && removeInput && previewWrapper && dropzone && input) {
                        removeButton.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            revokeObjectUrl();
                            removeInput.value = '1';
                            clearFileInput();
                            if (previewImage) {
                                previewImage.removeAttribute('src');
                            }
                            previewWrapper.style.display = 'none';
                            previewWrapper.classList.add('hidden');
                            dropzone.style.display = 'flex';
                            dropzone.classList.remove('hidden');
                        });
                    }

                    window.addEventListener('beforeunload', revokeObjectUrl, { once: true });
                });
            };

            document.addEventListener('DOMContentLoaded', initImageUploadFields);
            document.addEventListener('turbo:load', initImageUploadFields);

            if (document.readyState !== 'loading') {
                initImageUploadFields();
            }
        </script>
    @endonce
            </div>
        </div>
    </div>
</div>

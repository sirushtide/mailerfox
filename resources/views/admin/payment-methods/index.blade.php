@extends('layouts.admin')

@section('title', __('Payment Methods'))
@section('page-title', __('Payment Methods'))

@php
    $paymentProviders = [
        'stripe' => [
            'name' => 'Stripe',
            'description' => 'Accept payments via credit cards and other payment methods worldwide.',
            'logo' => 'M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.489 0-.858.68-1.497 1.917-1.497 1.59 0 3.24.7 4.22 1.33l1.415-2.22C16.97 3.57 15.17 2.75 13.03 2.75c-3.3 0-5.4 1.92-5.4 4.64 0 2.61 2.05 3.88 4.6 4.87 2.36.91 3.15 1.59 3.15 2.66 0 1.05-.87 1.66-2.19 1.66-1.64 0-3.55-.88-4.7-1.77l-1.55 2.31c1.5 1.24 3.8 2.13 6.1 2.13 3.57 0 5.68-1.82 5.68-4.78 0-2.72-2.12-3.93-4.75-4.97zM6.5 2.75C2.91 2.75.75 4.88.75 8.25c0 3.37 2.16 5.5 5.75 5.5 3.59 0 5.75-2.13 5.75-5.5 0-3.37-2.16-5.5-5.75-5.5zm0 8.5c-1.77 0-2.83-1.22-2.83-3 0-1.78 1.06-3 2.83-3 1.77 0 2.83 1.22 2.83 3 0 1.78-1.06 3-2.83 3z',
            'hasLiveSandbox' => true,
        ],
        'paypal' => [
            'name' => 'PayPal',
            'description' => 'Global payment platform for secure online transactions.',
            'logo' => 'M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.794-8.647 6.794h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a2.35 2.35 0 0 0-.327-.448c-.025-.027-.05-.054-.076-.08a3.865 3.865 0 0 0-.535-.532c-.033-.027-.067-.052-.1-.077a6.032 6.032 0 0 0-.628-.445c-.037-.023-.075-.045-.113-.067a8.78 8.78 0 0 0-.705-.378c-.04-.02-.082-.038-.123-.057a10.974 10.974 0 0 0-.774-.317c-.043-.016-.087-.03-.13-.045a13.037 13.037 0 0 0-.836-.265c-.046-.013-.092-.025-.138-.037a15.68 15.68 0 0 0-.89-.22c-.048-.01-.097-.02-.145-.03a18.314 18.314 0 0 0-.938-.175c-.05-.008-.1-.016-.15-.023a20.813 20.813 0 0 0-.977-.13c-.052-.006-.104-.011-.156-.016a23.423 23.423 0 0 0-1.01-.087c-.053-.004-.106-.007-.16-.01a26.527 26.527 0 0 0-1.034-.045c-.054-.002-.108-.003-.162-.004a29.768 29.768 0 0 0-1.058-.018h-5.72a.641.641 0 0 0-.633.74L.751 20.597a.641.641 0 0 0 .633.74h3.844c.524 0 .968-.382 1.05-.9l.26-1.637c.082-.518.526-.9 1.05-.9h2.42c5.033 0 8.957-1.937 10.09-7.58.03-.15.056-.3.08-.447.27-1.87-.02-3.14-1.03-4.29z',
            'hasLiveSandbox' => true,
        ],
        'uddoktapay' => [
            'name' => 'UddoktaPay',
            'description' => 'Bangladeshi payment gateway for local businesses.',
            'logo' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z',
            'hasLiveSandbox' => true,
        ],
        'razorpay' => [
            'name' => 'Razorpay',
            'description' => 'Indian payment gateway for businesses of all sizes.',
            'logo' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.24 16L12 15.45 7.76 18l1.18-4.68-3.52-2.93 4.62-.38L12 5.5l1.96 4.51 4.62.38-3.52 2.93L16.24 18z',
            'hasLiveSandbox' => true,
        ],
        'paystack' => [
            'name' => 'Paystack',
            'description' => 'Nigerian payment gateway for seamless transactions.',
            'logo' => 'M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 8.5l-2.5 1.25L12 11zm0 2.5l-5-2.5-5 2.5L12 22l10-8.5-5-2.5-5 2.5z',
            'hasLiveSandbox' => true,
        ],
        'flutterwave' => [
            'name' => 'Flutterwave',
            'description' => 'African payment infrastructure for global businesses.',
            'logo' => 'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
            'hasLiveSandbox' => true,
        ],
        'dodopayments' => [
            'name' => 'Dodo Payments',
            'description' => 'Global merchant-of-record checkout, billing, and tax compliance.',
            'logo' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z',
            'hasLiveSandbox' => true,
        ],
        'paddle' => [
            'name' => 'Paddle',
            'description' => 'Paddle Billing for subscriptions, tax, and payments.',
            'logo' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z',
            'hasLiveSandbox' => true,
        ],
        'lemonsqueezy' => [
            'name' => 'LemonSqueezy',
            'description' => 'Payment processing and software licensing for digital products.',
            'logo' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z',
            'hasLiveSandbox' => true,
        ],
        'manual' => [
            'name' => 'Manual',
            'description' => 'Accept manual payments via bank transfer or other offline methods.',
            'logo' => 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z',
            'hasLiveSandbox' => false,
        ],
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="{
    openDrawer: false,
    selectedProvider: null,
    drawerMode: 'live',
    providers: @js($providers),
    paymentProviders: @js($paymentProviders),
    liveSandboxProviders: ['stripe', 'paypal', 'uddoktapay', 'razorpay', 'paystack', 'flutterwave', 'dodopayments', 'paddle', 'lemonsqueezy'],
    openConfiguration(provider) {
        this.selectedProvider = provider;
        this.drawerMode = this.providers[provider].mode || 'live';
        this.openDrawer = true;
    },
    closeDrawer() {
        this.openDrawer = false;
        this.selectedProvider = null;
    },
    saveConfiguration() {
        this.$refs.form.requestSubmit ? this.$refs.form.requestSubmit() : this.$refs.form.submit();
    }
}">
    <form method="POST" action="{{ route('admin.payment-methods.update') }}" enctype="multipart/form-data" x-ref="form">
        @csrf
        <input type="hidden" name="default_provider" value="{{ $defaultProvider }}">
        <template x-for="providerKey in liveSandboxProviders" :key="providerKey">
            <div>
                <input type="hidden" :name="'providers[' + providerKey + '][enabled]'" :value="providers[providerKey]?.enabled ? 1 : 0">
                <input type="hidden" :name="'providers[' + providerKey + '][mode]'" :value="selectedProvider === providerKey ? drawerMode : (providers[providerKey]?.mode || 'live')">
            </div>
        </template>
        <input type="hidden" name="providers[manual][enabled]" :value="providers.manual?.enabled ? 1 : 0">
        <input type="hidden" name="providers[manual][bank_name]" :value="providers.manual?.bank_name || ''">
        <input type="hidden" name="providers[manual][account_name]" :value="providers.manual?.account_name || ''">
        <input type="hidden" name="providers[manual][account_number]" :value="providers.manual?.account_number || ''">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($paymentProviders as $key => $provider)
                <div class="rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:border-gray-900 transition-border">
                    <div class="flex items-start gap-4 mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                {{ $provider['name'] }}
                                @if(data_get($providers, $key . '.enabled'))
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $provider['description'] }}</p>
                        </div>
                    </div>

                    <button type="button" 
                            @click="openConfiguration('{{ $key }}')"
                            class="flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ __('Configure') }}
                    </button>
                </div>
            @endforeach
        </div>

    <!-- Side Drawer -->
    <div x-show="openDrawer" 
         x-transition:enter="transition ease-in-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in-out duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-hidden !mt-0" style="display: none;">
        <div class="absolute inset-0 bg-black/50" @click="closeDrawer()"></div>
        
        <div x-show="openDrawer"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="absolute right-0 inset-y-0 w-full max-w-lg bg-white dark:bg-gray-800 shadow-xl">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                            {{ __('Configure') }} <span x-text="selectedProvider && paymentProviders[selectedProvider] ? (paymentProviders[selectedProvider].name === 'PayPal' ? 'Paypal' : paymentProviders[selectedProvider].name) : ''"></span>
                        </h2>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   :checked="selectedProvider ? providers[selectedProvider].enabled : false"
                                   @change="if (selectedProvider) providers[selectedProvider].enabled = $event.target.checked"
                                   class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 dark:bg-gray-700 peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                        </label>
                    </div>
                    <button type="button" @click="closeDrawer()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <div x-show="selectedProvider && paymentProviders[selectedProvider]?.hasLiveSandbox" class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Environment') }}</label>
                        <div class="flex gap-2">
                            <button type="button" 
                                    @click="drawerMode = 'live'"
                                    :class="drawerMode === 'live' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                {{ __('Live') }}
                            </button>
                            <button type="button" 
                                    @click="drawerMode = 'sandbox'"
                                    :class="drawerMode === 'sandbox' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                {{ __('Sandbox') }}
                            </button>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'stripe'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Public Key') }}</label>
                                <input type="text" 
                                       :name="'providers[stripe][' + drawerMode + '][public_key]'"
                                       :value="providers.stripe[drawerMode]?.public_key || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Secret Key') }}</label>
                                <input type="password" 
                                       :name="'providers[stripe][' + drawerMode + '][secret]'"
                                       :value="providers.stripe[drawerMode]?.secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Webhook Secret') }}</label>
                                <input type="password" 
                                       :name="'providers[stripe][' + drawerMode + '][webhook_secret]'"
                                       :value="providers.stripe[drawerMode]?.webhook_secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'paypal'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Client ID') }}</label>
                                <input type="text" 
                                       :name="'providers[paypal][' + drawerMode + '][client_id]'"
                                       :value="providers.paypal[drawerMode]?.client_id || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Client Secret') }}</label>
                                <input type="password" 
                                       :name="'providers[paypal][' + drawerMode + '][client_secret]'"
                                       :value="providers.paypal[drawerMode]?.client_secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'uddoktapay'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('API Key') }}</label>
                                <input type="password" 
                                       :name="'providers[uddoktapay][' + drawerMode + '][api_key]'"
                                       :value="providers.uddoktapay[drawerMode]?.api_key || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Base URL') }}</label>
                                <input type="text" 
                                       :name="'providers[uddoktapay][' + drawerMode + '][base_url]'"
                                       :value="providers.uddoktapay[drawerMode]?.base_url || (drawerMode === 'live' ? 'https://pay.uddoktapay.com' : 'https://sandbox.uddoktapay.com')"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'razorpay'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Key ID') }}</label>
                                <input type="text" 
                                       :name="'providers[razorpay][' + drawerMode + '][key_id]'"
                                       :value="providers.razorpay[drawerMode]?.key_id || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Key Secret') }}</label>
                                <input type="password" 
                                       :name="'providers[razorpay][' + drawerMode + '][key_secret]'"
                                       :value="providers.razorpay[drawerMode]?.key_secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Webhook Secret') }}</label>
                                <input type="password" 
                                       :name="'providers[razorpay][' + drawerMode + '][webhook_secret]'"
                                       :value="providers.razorpay[drawerMode]?.webhook_secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'paystack'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Public Key') }}</label>
                                <input type="text" 
                                       :name="'providers[paystack][' + drawerMode + '][public_key]'"
                                       :value="providers.paystack[drawerMode]?.public_key || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Secret Key') }}</label>
                                <input type="password" 
                                       :name="'providers[paystack][' + drawerMode + '][secret]'"
                                       :value="providers.paystack[drawerMode]?.secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'flutterwave'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Public Key') }}</label>
                                <input type="text" 
                                       :name="'providers[flutterwave][' + drawerMode + '][public_key]'"
                                       :value="providers.flutterwave[drawerMode]?.public_key || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Secret Key') }}</label>
                                <input type="password" 
                                       :name="'providers[flutterwave][' + drawerMode + '][secret]'"
                                       :value="providers.flutterwave[drawerMode]?.secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Encryption Key') }}</label>
                                <input type="password" 
                                       :name="'providers[flutterwave][' + drawerMode + '][encryption_key]'"
                                       :value="providers.flutterwave[drawerMode]?.encryption_key || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Webhook Secret') }}</label>
                                <input type="password" 
                                       :name="'providers[flutterwave][' + drawerMode + '][webhook_secret]'"
                                       :value="providers.flutterwave[drawerMode]?.webhook_secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'dodopayments'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('API Key') }}</label>
                                <input type="password" 
                                       :name="'providers[dodopayments][' + drawerMode + '][api_key]'"
                                       :value="providers.dodopayments[drawerMode]?.api_key || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Webhook Secret') }}</label>
                                <input type="password" 
                                       :name="'providers[dodopayments][' + drawerMode + '][webhook_secret]'"
                                       :value="providers.dodopayments[drawerMode]?.webhook_secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'paddle'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('API Key') }}</label>
                                <input type="password" 
                                       :name="'providers[paddle][' + drawerMode + '][api_key]'"
                                       :value="providers.paddle[drawerMode]?.api_key || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Webhook Secret') }}</label>
                                <input type="password" 
                                       :name="'providers[paddle][' + drawerMode + '][webhook_secret]'"
                                       :value="providers.paddle[drawerMode]?.webhook_secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'lemonsqueezy'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('API Key') }}</label>
                                <input type="password" 
                                       :name="'providers[lemonsqueezy][' + drawerMode + '][api_key]'"
                                       :value="providers.lemonsqueezy[drawerMode]?.api_key || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Store ID') }}</label>
                                <input type="text" 
                                       :name="'providers[lemonsqueezy][' + drawerMode + '][store_id]'"
                                       :value="providers.lemonsqueezy[drawerMode]?.store_id || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Webhook Secret') }}</label>
                                <input type="password" 
                                       :name="'providers[lemonsqueezy][' + drawerMode + '][webhook_secret]'"
                                       :value="providers.lemonsqueezy[drawerMode]?.webhook_secret || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedProvider === 'manual'" x-cloak>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Bank Name') }}</label>
                                <input type="text" 
                                       name="providers[manual][bank_name]"
                                       :value="providers.manual?.bank_name || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Account Name') }}</label>
                                <input type="text" 
                                       name="providers[manual][account_name]"
                                       :value="providers.manual?.account_name || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Account Number') }}</label>
                                <input type="text" 
                                       name="providers[manual][account_number]"
                                       :value="providers.manual?.account_number || ''"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Instructions') }}</label>
                                <textarea name="providers[manual][instructions]" 
                                          rows="3"
                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">{{ old('providers.manual.instructions', data_get($providers, 'manual.instructions')) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('QR Code Image') }}</label>
                                <input type="file" 
                                       name="providers[manual][qr_image]"
                                       accept="image/*"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                @if(data_get($providers, 'manual.qr_image_path'))
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . data_get($providers, 'manual.qr_image_path')) }}" alt="QR Code" class="h-20 w-auto">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex gap-3">
                        <button type="button" 
                                @click="closeDrawer()"
                                class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" 
                                @click="saveConfiguration()"
                                class="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium">
                            {{ __('Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>
</div>
@endsection

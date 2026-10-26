<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $legacyProvider = Setting::get('billing_provider', config('billing.provider', 'stripe'));
        $defaultProvider = Setting::get('billing_default_provider', $legacyProvider);

        $billingProviders = Setting::get('billing_providers');

        if (!is_array($billingProviders)) {
            $billingProviders = [
                'stripe' => [
                    'enabled' => $legacyProvider === 'stripe',
                    'mode' => 'live',
                    'live' => [
                        'public_key' => Setting::get('stripe_public_key', config('billing.stripe.public_key')),
                        'secret' => Setting::get('stripe_secret', config('billing.stripe.secret')),
                        'webhook_secret' => Setting::get('stripe_webhook_secret', config('billing.stripe.webhook_secret')),
                    ],
                    'sandbox' => [
                        'public_key' => null,
                        'secret' => null,
                        'webhook_secret' => null,
                    ],
                ],
                'uddoktapay' => [
                    'enabled' => $legacyProvider === 'uddoktapay',
                    'mode' => 'live',
                    'live' => [
                        'api_key' => Setting::get('uddoktapay_api_key', config('billing.uddoktapay.api_key')),
                        'base_url' => Setting::get('uddoktapay_base_url', config('billing.uddoktapay.base_url')),
                    ],
                    'sandbox' => [
                        'api_key' => null,
                        'base_url' => 'https://sandbox.uddoktapay.com',
                    ],
                ],
                'paypal' => [
                    'enabled' => $legacyProvider === 'paypal',
                    'mode' => 'live',
                    'live' => [
                        'client_id' => Setting::get('paypal_client_id', config('billing.paypal.client_id')),
                        'client_secret' => Setting::get('paypal_client_secret', config('billing.paypal.client_secret')),
                    ],
                    'sandbox' => [
                        'client_id' => null,
                        'client_secret' => null,
                    ],
                ],
                'razorpay' => [
                    'enabled' => $legacyProvider === 'razorpay',
                    'mode' => 'live',
                    'live' => [
                        'key_id' => Setting::get('razorpay_key_id', config('billing.razorpay.key_id')),
                        'key_secret' => Setting::get('razorpay_key_secret', config('billing.razorpay.key_secret')),
                        'webhook_secret' => Setting::get('razorpay_webhook_secret', config('billing.razorpay.webhook_secret')),
                    ],
                    'sandbox' => [
                        'key_id' => null,
                        'key_secret' => null,
                        'webhook_secret' => null,
                    ],
                ],
                'paystack' => [
                    'enabled' => false,
                    'mode' => 'live',
                    'live' => [
                        'public_key' => Setting::get('paystack_public_key', config('billing.paystack.public_key')),
                        'secret' => Setting::get('paystack_secret', config('billing.paystack.secret')),
                    ],
                    'sandbox' => [
                        'public_key' => null,
                        'secret' => null,
                    ],
                ],
                'flutterwave' => [
                    'enabled' => false,
                    'mode' => 'live',
                    'live' => [
                        'public_key' => Setting::get('flutterwave_public_key', config('billing.flutterwave.public_key')),
                        'secret' => Setting::get('flutterwave_secret', config('billing.flutterwave.secret')),
                        'encryption_key' => Setting::get('flutterwave_encryption_key', config('billing.flutterwave.encryption_key')),
                        'webhook_secret' => Setting::get('flutterwave_webhook_secret', config('billing.flutterwave.webhook_secret')),
                    ],
                    'sandbox' => [
                        'public_key' => null,
                        'secret' => null,
                        'encryption_key' => null,
                        'webhook_secret' => null,
                    ],
                ],
                'dodopayments' => [
                    'enabled' => $legacyProvider === 'dodopayments',
                    'mode' => 'live',
                    'live' => [
                        'api_key' => Setting::get('dodopayments_api_key', config('billing.dodopayments.api_key')),
                        'webhook_secret' => Setting::get('dodopayments_webhook_secret', config('billing.dodopayments.webhook_secret')),
                    ],
                    'sandbox' => [
                        'api_key' => null,
                        'webhook_secret' => null,
                    ],
                ],
                'paddle' => [
                    'enabled' => $legacyProvider === 'paddle',
                    'mode' => 'live',
                    'live' => [
                        'api_key' => Setting::get('paddle_api_key', config('billing.paddle.api_key')),
                        'webhook_secret' => Setting::get('paddle_webhook_secret', config('billing.paddle.webhook_secret')),
                    ],
                    'sandbox' => [
                        'api_key' => null,
                        'webhook_secret' => null,
                    ],
                ],
                'lemonsqueezy' => [
                    'enabled' => $legacyProvider === 'lemonsqueezy',
                    'mode' => 'live',
                    'live' => [
                        'api_key' => Setting::get('lemonsqueezy_api_key', config('billing.lemonsqueezy.api_key')),
                        'store_id' => Setting::get('lemonsqueezy_store_id', config('billing.lemonsqueezy.store_id')),
                        'webhook_secret' => Setting::get('lemonsqueezy_webhook_secret', config('billing.lemonsqueezy.webhook_secret')),
                    ],
                    'sandbox' => [
                        'api_key' => null,
                        'store_id' => null,
                        'webhook_secret' => null,
                    ],
                ],
                'manual' => [
                    'enabled' => $legacyProvider === 'manual',
                    'account_name' => Setting::get('manual_account_name'),
                    'account_number' => Setting::get('manual_account_number'),
                    'bank_name' => Setting::get('manual_bank_name'),
                    'instructions' => Setting::get('manual_instructions'),
                    'qr_image_path' => Setting::get('manual_qr_image_path'),
                ],
            ];
        } else {
            foreach (['stripe', 'uddoktapay', 'paypal', 'razorpay', 'paystack', 'flutterwave', 'dodopayments', 'paddle', 'lemonsqueezy'] as $providerKey) {
                $billingProviders[$providerKey] ??= [];
                $billingProviders[$providerKey]['mode'] ??= 'live';

                if (!in_array($billingProviders[$providerKey]['mode'], ['live', 'sandbox'], true)) {
                    $billingProviders[$providerKey]['mode'] = 'live';
                }
            }

            // Normalize older flat structures into mode-based structures for the UI.
            if (isset($billingProviders['stripe']['public_key']) || isset($billingProviders['stripe']['secret']) || isset($billingProviders['stripe']['webhook_secret'])) {
                $billingProviders['stripe']['live'] = [
                    'public_key' => $billingProviders['stripe']['public_key'] ?? null,
                    'secret' => $billingProviders['stripe']['secret'] ?? null,
                    'webhook_secret' => $billingProviders['stripe']['webhook_secret'] ?? null,
                ];
                $billingProviders['stripe']['sandbox'] ??= [
                    'public_key' => null,
                    'secret' => null,
                    'webhook_secret' => null,
                ];
            }

            if (isset($billingProviders['uddoktapay']['api_key']) || isset($billingProviders['uddoktapay']['base_url'])) {
                $billingProviders['uddoktapay']['live'] = [
                    'api_key' => $billingProviders['uddoktapay']['api_key'] ?? null,
                    'base_url' => $billingProviders['uddoktapay']['base_url'] ?? null,
                ];
                $billingProviders['uddoktapay']['sandbox'] ??= [
                    'api_key' => null,
                    'base_url' => 'https://sandbox.uddoktapay.com',
                ];
            }

            $billingProviders['uddoktapay']['live'] ??= [
                'api_key' => Setting::get('uddoktapay_api_key', config('billing.uddoktapay.api_key')),
                'base_url' => Setting::get('uddoktapay_base_url', config('billing.uddoktapay.base_url')),
            ];
            $billingProviders['uddoktapay']['sandbox'] ??= [
                'api_key' => null,
                'base_url' => 'https://sandbox.uddoktapay.com',
            ];

            if (isset($billingProviders['paypal']['client_id']) || isset($billingProviders['paypal']['client_secret'])) {
                $billingProviders['paypal']['live'] = [
                    'client_id' => $billingProviders['paypal']['client_id'] ?? null,
                    'client_secret' => $billingProviders['paypal']['client_secret'] ?? null,
                ];
                $billingProviders['paypal']['sandbox'] ??= [
                    'client_id' => null,
                    'client_secret' => null,
                ];
            }

            if (
                isset($billingProviders['razorpay']['key_id'])
                || isset($billingProviders['razorpay']['key_secret'])
                || isset($billingProviders['razorpay']['webhook_secret'])
            ) {
                $billingProviders['razorpay']['live'] = [
                    'key_id' => $billingProviders['razorpay']['key_id'] ?? null,
                    'key_secret' => $billingProviders['razorpay']['key_secret'] ?? null,
                    'webhook_secret' => $billingProviders['razorpay']['webhook_secret'] ?? null,
                ];
                $billingProviders['razorpay']['sandbox'] ??= [
                    'key_id' => null,
                    'key_secret' => null,
                    'webhook_secret' => null,
                ];
            }

            if (isset($billingProviders['paystack']['public_key']) || isset($billingProviders['paystack']['secret'])) {
                $billingProviders['paystack']['live'] = [
                    'public_key' => $billingProviders['paystack']['public_key'] ?? null,
                    'secret' => $billingProviders['paystack']['secret'] ?? null,
                ];
                $billingProviders['paystack']['sandbox'] ??= [
                    'public_key' => null,
                    'secret' => null,
                ];
            }

            if (
                isset($billingProviders['flutterwave']['public_key'])
                || isset($billingProviders['flutterwave']['secret'])
                || isset($billingProviders['flutterwave']['encryption_key'])
                || isset($billingProviders['flutterwave']['webhook_secret'])
            ) {
                $billingProviders['flutterwave']['live'] = [
                    'public_key' => $billingProviders['flutterwave']['public_key'] ?? null,
                    'secret' => $billingProviders['flutterwave']['secret'] ?? null,
                    'encryption_key' => $billingProviders['flutterwave']['encryption_key'] ?? null,
                    'webhook_secret' => $billingProviders['flutterwave']['webhook_secret'] ?? null,
                ];
                $billingProviders['flutterwave']['sandbox'] ??= [
                    'public_key' => null,
                    'secret' => null,
                    'encryption_key' => null,
                    'webhook_secret' => null,
                ];
            }

            if (isset($billingProviders['dodopayments']['api_key']) || isset($billingProviders['dodopayments']['webhook_secret'])) {
                $billingProviders['dodopayments']['live'] = [
                    'api_key' => $billingProviders['dodopayments']['api_key'] ?? null,
                    'webhook_secret' => $billingProviders['dodopayments']['webhook_secret'] ?? null,
                ];
                $billingProviders['dodopayments']['sandbox'] ??= [
                    'api_key' => null,
                    'webhook_secret' => null,
                ];
            }

            $billingProviders['dodopayments']['live'] ??= [
                'api_key' => Setting::get('dodopayments_api_key', config('billing.dodopayments.api_key')),
                'webhook_secret' => Setting::get('dodopayments_webhook_secret', config('billing.dodopayments.webhook_secret')),
            ];
            $billingProviders['dodopayments']['sandbox'] ??= [
                'api_key' => null,
                'webhook_secret' => null,
            ];

            if (isset($billingProviders['paddle']['api_key']) || isset($billingProviders['paddle']['webhook_secret'])) {
                $billingProviders['paddle']['live'] = [
                    'api_key' => $billingProviders['paddle']['api_key'] ?? null,
                    'webhook_secret' => $billingProviders['paddle']['webhook_secret'] ?? null,
                ];
                $billingProviders['paddle']['sandbox'] ??= [
                    'api_key' => null,
                    'webhook_secret' => null,
                ];
            }

            $billingProviders['paddle']['live'] ??= [
                'api_key' => Setting::get('paddle_api_key', config('billing.paddle.api_key')),
                'webhook_secret' => Setting::get('paddle_webhook_secret', config('billing.paddle.webhook_secret')),
            ];
            $billingProviders['paddle']['sandbox'] ??= [
                'api_key' => null,
                'webhook_secret' => null,
            ];

            if (isset($billingProviders['lemonsqueezy']['api_key']) || isset($billingProviders['lemonsqueezy']['store_id']) || isset($billingProviders['lemonsqueezy']['webhook_secret'])) {
                $billingProviders['lemonsqueezy']['live'] = [
                    'api_key' => $billingProviders['lemonsqueezy']['api_key'] ?? null,
                    'store_id' => $billingProviders['lemonsqueezy']['store_id'] ?? null,
                    'webhook_secret' => $billingProviders['lemonsqueezy']['webhook_secret'] ?? null,
                ];
                $billingProviders['lemonsqueezy']['sandbox'] ??= [
                    'api_key' => null,
                    'store_id' => null,
                    'webhook_secret' => null,
                ];
            }

            $billingProviders['lemonsqueezy']['live'] ??= [
                'api_key' => Setting::get('lemonsqueezy_api_key', config('billing.lemonsqueezy.api_key')),
                'store_id' => Setting::get('lemonsqueezy_store_id', config('billing.lemonsqueezy.store_id')),
                'webhook_secret' => Setting::get('lemonsqueezy_webhook_secret', config('billing.lemonsqueezy.webhook_secret')),
            ];
            $billingProviders['lemonsqueezy']['sandbox'] ??= [
                'api_key' => null,
                'store_id' => null,
                'webhook_secret' => null,
            ];

            $billingProviders['manual'] ??= [];
            $billingProviders['manual']['enabled'] ??= false;
            $billingProviders['manual']['account_name'] ??= null;
            $billingProviders['manual']['account_number'] ??= null;
            $billingProviders['manual']['bank_name'] ??= null;
            $billingProviders['manual']['instructions'] ??= null;

            $billingProviders['manual']['qr_image_path'] ??= ($billingProviders['manual']['qr_image'] ?? null);
        }

        return view('admin.payment-methods.index', [
            'defaultProvider' => $defaultProvider,
            'providers' => $billingProviders,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_provider' => ['required', 'in:stripe,uddoktapay,paypal,razorpay,paystack,flutterwave,dodopayments,paddle,lemonsqueezy,manual'],
            'providers' => ['required', 'array'],
            'providers.stripe.enabled' => ['nullable', 'boolean'],
            'providers.stripe.mode' => ['required', 'in:live,sandbox'],
            'providers.stripe.live.public_key' => ['nullable', 'string'],
            'providers.stripe.live.secret' => ['nullable', 'string'],
            'providers.stripe.live.webhook_secret' => ['nullable', 'string'],
            'providers.stripe.sandbox.public_key' => ['nullable', 'string'],
            'providers.stripe.sandbox.secret' => ['nullable', 'string'],
            'providers.stripe.sandbox.webhook_secret' => ['nullable', 'string'],
            'providers.uddoktapay.enabled' => ['nullable', 'boolean'],
            'providers.uddoktapay.mode' => ['required', 'in:live,sandbox'],
            'providers.uddoktapay.live.api_key' => ['nullable', 'string'],
            'providers.uddoktapay.live.base_url' => ['nullable', 'string', 'max:255'],
            'providers.uddoktapay.sandbox.api_key' => ['nullable', 'string'],
            'providers.uddoktapay.sandbox.base_url' => ['nullable', 'string', 'max:255'],
            'providers.paypal.enabled' => ['nullable', 'boolean'],
            'providers.paypal.mode' => ['required', 'in:live,sandbox'],
            'providers.paypal.live.client_id' => ['nullable', 'string'],
            'providers.paypal.live.client_secret' => ['nullable', 'string'],
            'providers.paypal.sandbox.client_id' => ['nullable', 'string'],
            'providers.paypal.sandbox.client_secret' => ['nullable', 'string'],
            'providers.razorpay.enabled' => ['nullable', 'boolean'],
            'providers.razorpay.mode' => ['required', 'in:live,sandbox'],
            'providers.razorpay.live.key_id' => ['nullable', 'string'],
            'providers.razorpay.live.key_secret' => ['nullable', 'string'],
            'providers.razorpay.live.webhook_secret' => ['nullable', 'string'],
            'providers.razorpay.sandbox.key_id' => ['nullable', 'string'],
            'providers.razorpay.sandbox.key_secret' => ['nullable', 'string'],
            'providers.razorpay.sandbox.webhook_secret' => ['nullable', 'string'],
            'providers.paystack.enabled' => ['nullable', 'boolean'],
            'providers.paystack.mode' => ['required', 'in:live,sandbox'],
            'providers.paystack.live.public_key' => ['nullable', 'string'],
            'providers.paystack.live.secret' => ['nullable', 'string'],
            'providers.paystack.sandbox.public_key' => ['nullable', 'string'],
            'providers.paystack.sandbox.secret' => ['nullable', 'string'],
            'providers.flutterwave.enabled' => ['nullable', 'boolean'],
            'providers.flutterwave.mode' => ['required', 'in:live,sandbox'],
            'providers.flutterwave.live.public_key' => ['nullable', 'string'],
            'providers.flutterwave.live.secret' => ['nullable', 'string'],
            'providers.flutterwave.live.encryption_key' => ['nullable', 'string'],
            'providers.flutterwave.live.webhook_secret' => ['nullable', 'string'],
            'providers.flutterwave.sandbox.public_key' => ['nullable', 'string'],
            'providers.flutterwave.sandbox.secret' => ['nullable', 'string'],
            'providers.flutterwave.sandbox.encryption_key' => ['nullable', 'string'],
            'providers.flutterwave.sandbox.webhook_secret' => ['nullable', 'string'],

            'providers.dodopayments.enabled' => ['nullable', 'boolean'],
            'providers.dodopayments.mode' => ['required', 'in:live,sandbox'],
            'providers.dodopayments.live.api_key' => ['nullable', 'string'],
            'providers.dodopayments.live.webhook_secret' => ['nullable', 'string'],
            'providers.dodopayments.sandbox.api_key' => ['nullable', 'string'],
            'providers.dodopayments.sandbox.webhook_secret' => ['nullable', 'string'],

            'providers.paddle.enabled' => ['nullable', 'boolean'],
            'providers.paddle.mode' => ['required', 'in:live,sandbox'],
            'providers.paddle.live.api_key' => ['nullable', 'string'],
            'providers.paddle.live.webhook_secret' => ['nullable', 'string'],
            'providers.paddle.sandbox.api_key' => ['nullable', 'string'],
            'providers.paddle.sandbox.webhook_secret' => ['nullable', 'string'],

            'providers.lemonsqueezy.enabled' => ['nullable', 'boolean'],
            'providers.lemonsqueezy.mode' => ['required', 'in:live,sandbox'],
            'providers.lemonsqueezy.live.api_key' => ['nullable', 'string'],
            'providers.lemonsqueezy.live.store_id' => ['nullable', 'string'],
            'providers.lemonsqueezy.live.webhook_secret' => ['nullable', 'string'],
            'providers.lemonsqueezy.sandbox.api_key' => ['nullable', 'string'],
            'providers.lemonsqueezy.sandbox.store_id' => ['nullable', 'string'],
            'providers.lemonsqueezy.sandbox.webhook_secret' => ['nullable', 'string'],

            'providers.manual.enabled' => ['nullable', 'boolean'],
            'providers.manual.bank_name' => ['nullable', 'string', 'max:255'],
            'providers.manual.account_name' => ['nullable', 'string', 'max:255'],
            'providers.manual.account_number' => ['nullable', 'string', 'max:255'],
            'providers.manual.instructions' => ['nullable', 'string', 'max:5000'],
            'providers.manual.qr_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $existingProviders = Setting::get('billing_providers');
        if (!is_array($existingProviders)) {
            $existingProviders = [];
        }

        $providers = $validated['providers'];

        foreach (['stripe', 'uddoktapay', 'paypal', 'razorpay', 'paystack', 'flutterwave', 'dodopayments', 'paddle', 'lemonsqueezy'] as $providerKey) {
            $providers[$providerKey]['enabled'] = (bool) ($providers[$providerKey]['enabled'] ?? false);
            $providers[$providerKey]['mode'] = $providers[$providerKey]['mode'] ?? 'live';
            if (!in_array($providers[$providerKey]['mode'], ['live', 'sandbox'], true)) {
                $providers[$providerKey]['mode'] = 'live';
            }
        }

        $providers['manual'] ??= [];
        $providers['manual']['enabled'] = (bool) ($providers['manual']['enabled'] ?? false);

        $existingManualQr = data_get($existingProviders, 'manual.qr_image_path')
            ?: data_get($existingProviders, 'manual.qr_image');
        if (is_string($existingManualQr)) {
            $existingManualQr = trim($existingManualQr);
        }

        $qrUpload = $request->file('providers.manual.qr_image');
        if ($qrUpload instanceof UploadedFile && $qrUpload->isValid()) {
            $path = $qrUpload->store('billing/manual', 'public');
            $providers['manual']['qr_image_path'] = $path;
        } else {
            $providers['manual']['qr_image_path'] = is_string($existingManualQr) && $existingManualQr !== ''
                ? $existingManualQr
                : ($providers['manual']['qr_image_path'] ?? null);
        }

        unset($providers['manual']['qr_image']);

        Setting::set('billing_providers', $providers, 'billing', 'array');
        Setting::set('billing_default_provider', $validated['default_provider'], 'billing', 'string');

        // Backward compatibility (keep legacy key in sync)
        Setting::set('billing_provider', $validated['default_provider'], 'billing', 'string');

        Setting::set('manual_bank_name', data_get($providers, 'manual.bank_name'), 'billing', 'string');
        Setting::set('manual_account_name', data_get($providers, 'manual.account_name'), 'billing', 'string');
        Setting::set('manual_account_number', data_get($providers, 'manual.account_number'), 'billing', 'string');
        Setting::set('manual_instructions', data_get($providers, 'manual.instructions'), 'billing', 'string');
        Setting::set('manual_qr_image_path', data_get($providers, 'manual.qr_image_path'), 'billing', 'string');

        // Keep existing per-key settings for other app parts
        $stripeMode = $providers['stripe']['mode'] ?? 'live';
        $stripePublicKey = data_get($providers, 'stripe.' . $stripeMode . '.public_key');
        $stripeSecret = data_get($providers, 'stripe.' . $stripeMode . '.secret');
        $stripeWebhookSecret = data_get($providers, 'stripe.' . $stripeMode . '.webhook_secret');

        $stripePublicKey = is_string($stripePublicKey) ? trim($stripePublicKey) : $stripePublicKey;
        $stripeSecret = is_string($stripeSecret) ? trim($stripeSecret) : $stripeSecret;
        $stripeWebhookSecret = is_string($stripeWebhookSecret) ? trim($stripeWebhookSecret) : $stripeWebhookSecret;

        Setting::set('stripe_public_key', $stripePublicKey !== '' ? $stripePublicKey : null, 'billing', 'string');
        Setting::set('stripe_secret', $stripeSecret !== '' ? $stripeSecret : null, 'billing', 'string');
        Setting::set('stripe_webhook_secret', $stripeWebhookSecret !== '' ? $stripeWebhookSecret : null, 'billing', 'string');

        $uddoktaPayMode = $providers['uddoktapay']['mode'] ?? 'live';
        $uddoktaPayApiKey = data_get($providers, 'uddoktapay.' . $uddoktaPayMode . '.api_key');
        $uddoktaPayBaseUrl = data_get($providers, 'uddoktapay.' . $uddoktaPayMode . '.base_url');

        $uddoktaPayApiKey = is_string($uddoktaPayApiKey) ? trim($uddoktaPayApiKey) : $uddoktaPayApiKey;
        $uddoktaPayBaseUrl = is_string($uddoktaPayBaseUrl) ? trim($uddoktaPayBaseUrl) : $uddoktaPayBaseUrl;

        Setting::set('uddoktapay_api_key', $uddoktaPayApiKey !== '' ? $uddoktaPayApiKey : null, 'billing', 'string');
        Setting::set('uddoktapay_base_url', $uddoktaPayBaseUrl !== '' ? $uddoktaPayBaseUrl : null, 'billing', 'string');

        $paypalMode = $providers['paypal']['mode'] ?? 'live';
        $paypalClientId = data_get($providers, 'paypal.' . $paypalMode . '.client_id');
        $paypalClientSecret = data_get($providers, 'paypal.' . $paypalMode . '.client_secret');

        $paypalClientId = is_string($paypalClientId) ? trim($paypalClientId) : $paypalClientId;
        $paypalClientSecret = is_string($paypalClientSecret) ? trim($paypalClientSecret) : $paypalClientSecret;

        Setting::set('paypal_client_id', $paypalClientId !== '' ? $paypalClientId : null, 'billing', 'string');
        Setting::set('paypal_client_secret', $paypalClientSecret !== '' ? $paypalClientSecret : null, 'billing', 'string');

        $razorpayMode = $providers['razorpay']['mode'] ?? 'live';
        $razorpayKeyId = data_get($providers, 'razorpay.' . $razorpayMode . '.key_id');
        $razorpayKeySecret = data_get($providers, 'razorpay.' . $razorpayMode . '.key_secret');
        $razorpayWebhookSecret = data_get($providers, 'razorpay.' . $razorpayMode . '.webhook_secret');

        $razorpayKeyId = is_string($razorpayKeyId) ? trim($razorpayKeyId) : $razorpayKeyId;
        $razorpayKeySecret = is_string($razorpayKeySecret) ? trim($razorpayKeySecret) : $razorpayKeySecret;
        $razorpayWebhookSecret = is_string($razorpayWebhookSecret) ? trim($razorpayWebhookSecret) : $razorpayWebhookSecret;

        Setting::set('razorpay_key_id', $razorpayKeyId !== '' ? $razorpayKeyId : null, 'billing', 'string');
        Setting::set('razorpay_key_secret', $razorpayKeySecret !== '' ? $razorpayKeySecret : null, 'billing', 'string');
        Setting::set('razorpay_webhook_secret', $razorpayWebhookSecret !== '' ? $razorpayWebhookSecret : null, 'billing', 'string');

        $paystackMode = $providers['paystack']['mode'] ?? 'live';
        $paystackPublicKey = data_get($providers, 'paystack.' . $paystackMode . '.public_key');
        $paystackSecret = data_get($providers, 'paystack.' . $paystackMode . '.secret');

        $paystackPublicKey = is_string($paystackPublicKey) ? trim($paystackPublicKey) : $paystackPublicKey;
        $paystackSecret = is_string($paystackSecret) ? trim($paystackSecret) : $paystackSecret;

        Setting::set('paystack_public_key', $paystackPublicKey !== '' ? $paystackPublicKey : null, 'billing', 'string');
        Setting::set('paystack_secret', $paystackSecret !== '' ? $paystackSecret : null, 'billing', 'string');

        $flutterwaveMode = $providers['flutterwave']['mode'] ?? 'live';
        $flutterwavePublicKey = data_get($providers, 'flutterwave.' . $flutterwaveMode . '.public_key');
        $flutterwaveSecret = data_get($providers, 'flutterwave.' . $flutterwaveMode . '.secret');
        $flutterwaveEncryptionKey = data_get($providers, 'flutterwave.' . $flutterwaveMode . '.encryption_key');
        $flutterwaveWebhookSecret = data_get($providers, 'flutterwave.' . $flutterwaveMode . '.webhook_secret');

        $flutterwavePublicKey = is_string($flutterwavePublicKey) ? trim($flutterwavePublicKey) : $flutterwavePublicKey;
        $flutterwaveSecret = is_string($flutterwaveSecret) ? trim($flutterwaveSecret) : $flutterwaveSecret;
        $flutterwaveEncryptionKey = is_string($flutterwaveEncryptionKey) ? trim($flutterwaveEncryptionKey) : $flutterwaveEncryptionKey;
        $flutterwaveWebhookSecret = is_string($flutterwaveWebhookSecret) ? trim($flutterwaveWebhookSecret) : $flutterwaveWebhookSecret;

        Setting::set('flutterwave_public_key', $flutterwavePublicKey !== '' ? $flutterwavePublicKey : null, 'billing', 'string');
        Setting::set('flutterwave_secret', $flutterwaveSecret !== '' ? $flutterwaveSecret : null, 'billing', 'string');
        Setting::set('flutterwave_encryption_key', $flutterwaveEncryptionKey !== '' ? $flutterwaveEncryptionKey : null, 'billing', 'string');
        Setting::set('flutterwave_webhook_secret', $flutterwaveWebhookSecret !== '' ? $flutterwaveWebhookSecret : null, 'billing', 'string');

        $dodoMode = $providers['dodopayments']['mode'] ?? 'live';
        $dodoApiKey = data_get($providers, 'dodopayments.' . $dodoMode . '.api_key');
        $dodoWebhookSecret = data_get($providers, 'dodopayments.' . $dodoMode . '.webhook_secret');

        $dodoApiKey = is_string($dodoApiKey) ? trim($dodoApiKey) : $dodoApiKey;
        $dodoWebhookSecret = is_string($dodoWebhookSecret) ? trim($dodoWebhookSecret) : $dodoWebhookSecret;

        Setting::set('dodopayments_api_key', $dodoApiKey !== '' ? $dodoApiKey : null, 'billing', 'string');
        Setting::set('dodopayments_webhook_secret', $dodoWebhookSecret !== '' ? $dodoWebhookSecret : null, 'billing', 'string');

        $paddleMode = $providers['paddle']['mode'] ?? 'live';
        $paddleApiKey = data_get($providers, 'paddle.' . $paddleMode . '.api_key');
        $paddleWebhookSecret = data_get($providers, 'paddle.' . $paddleMode . '.webhook_secret');

        $paddleApiKey = is_string($paddleApiKey) ? trim($paddleApiKey) : $paddleApiKey;
        $paddleWebhookSecret = is_string($paddleWebhookSecret) ? trim($paddleWebhookSecret) : $paddleWebhookSecret;

        Setting::set('paddle_api_key', $paddleApiKey !== '' ? $paddleApiKey : null, 'billing', 'string');
        Setting::set('paddle_webhook_secret', $paddleWebhookSecret !== '' ? $paddleWebhookSecret : null, 'billing', 'string');

        $lemonsqueezyMode = $providers['lemonsqueezy']['mode'] ?? 'live';
        $lemonsqueezyApiKey = data_get($providers, 'lemonsqueezy.' . $lemonsqueezyMode . '.api_key');
        $lemonsqueezyStoreId = data_get($providers, 'lemonsqueezy.' . $lemonsqueezyMode . '.store_id');
        $lemonsqueezyWebhookSecret = data_get($providers, 'lemonsqueezy.' . $lemonsqueezyMode . '.webhook_secret');

        $lemonsqueezyApiKey = is_string($lemonsqueezyApiKey) ? trim($lemonsqueezyApiKey) : $lemonsqueezyApiKey;
        $lemonsqueezyStoreId = is_string($lemonsqueezyStoreId) ? trim($lemonsqueezyStoreId) : $lemonsqueezyStoreId;
        $lemonsqueezyWebhookSecret = is_string($lemonsqueezyWebhookSecret) ? trim($lemonsqueezyWebhookSecret) : $lemonsqueezyWebhookSecret;

        Setting::set('lemonsqueezy_api_key', $lemonsqueezyApiKey !== '' ? $lemonsqueezyApiKey : null, 'billing', 'string');
        Setting::set('lemonsqueezy_store_id', $lemonsqueezyStoreId !== '' ? $lemonsqueezyStoreId : null, 'billing', 'string');
        Setting::set('lemonsqueezy_webhook_secret', $lemonsqueezyWebhookSecret !== '' ? $lemonsqueezyWebhookSecret : null, 'billing', 'string');

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', __('Payment methods updated.'));
    }
}

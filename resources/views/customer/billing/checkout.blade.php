@extends('layouts.customer')

@section('title', 'Checkout')
@section('page-title', 'Checkout')

@section('content')
@php
    $providerLabels = [
        'stripe' => 'Stripe',
        'uddoktapay' => 'UddoktaPay',
        'paypal' => 'PayPal',
        'razorpay' => 'Razorpay',
        'flutterwave' => 'Flutterwave',
        'dodopayments' => 'Dodo Payments',
        'paddle' => 'Paddle',
        'lemonsqueezy' => 'LemonSqueezy',
        'manual' => 'Bank Transfer',
    ];

    $enabledProviders = isset($enabledProviders) && is_array($enabledProviders) ? $enabledProviders : [];
    $selectedProvider = isset($selectedProvider) && is_string($selectedProvider) ? $selectedProvider : 'stripe';
    $couponCode = isset($couponCode) && is_string($couponCode) ? $couponCode : '';

    $planCurrency = strtoupper((string) ($plan->currency ?? 'USD'));
    if (!preg_match('/^[A-Z]{3}$/', $planCurrency)) {
        $planCurrency = 'USD';
    }

    $planValue = is_numeric($plan->price ?? null) ? (float) $plan->price : 0.0;
    $billingLabel = $plan->billing_cycle === 'lifetime'
        ? 'one-time'
        : ($plan->billing_cycle === 'yearly' ? 'yearly' : 'monthly');
@endphp

<div class="max-w-6xl mx-auto space-y-6">
    <div class="rounded-xl border border-admin-border overflow-hidden bg-gradient-to-r from-admin-button-from/15 via-transparent to-admin-button-to/10">
        <div class="px-6 py-6">
            <h1 class="text-3xl font-bold tracking-tight">Checkout</h1>
            <p class="mt-2 text-sm text-admin-text-secondary max-w-2xl">
                Choose a payment method to subscribe to <span class="font-semibold">{{ $plan->name }}</span>.
            </p>
        </div>
    </div>

    <x-card>
        <form
            method="POST"
            action="{{ route('customer.billing.checkout', $plan) }}"
            data-turbo="false"
            x-data="{ provider: '{{ $selectedProvider }}' }"
            x-on:submit="if (typeof window.mailpurseMetaTrackCustom === 'function') { window.mailpurseMetaTrackCustom('AddPaymentInfo', { value: {{ json_encode($planValue) }}, currency: {{ json_encode($planCurrency) }}, content_name: {{ json_encode((string) $plan->name) }}, payment_method: provider || 'unknown', content_category: 'subscription' }); }"
        >
            @csrf
            <input type="hidden" name="provider" x-bind:value="provider">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-7 space-y-6">
                    <div>
                        <div class="text-sm font-semibold">Billing information</div>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-admin-text-secondary">Name</label>
                                <input value="{{ $customer->full_name }}" class="mt-1 block w-full rounded-md border-admin-border bg-transparent" disabled>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-admin-text-secondary">Email</label>
                                <input value="{{ $customer->email }}" class="mt-1 block w-full rounded-md border-admin-border bg-transparent" disabled>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-semibold">Payment method</div>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            @foreach($enabledProviders as $providerKey)
                                @php
                                    $label = $providerLabels[$providerKey] ?? ucfirst($providerKey);
                                @endphp

                                <button
                                    type="button"
                                    x-on:click="provider = '{{ $providerKey }}'"
                                    class="inline-flex items-center justify-center rounded-lg border px-5 py-3 text-sm font-medium transition-colors cursor-pointer hover:border-primary-500 hover:bg-primary-500/10"
                                    x-bind:class="provider === '{{ $providerKey }}' ? 'border-primary-500 bg-primary-500/10' : 'border-admin-border'"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <div class="mt-3 text-xs text-admin-text-secondary">
                            Card details are collected securely on the payment provider page.
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="lg:sticky lg:top-6 space-y-3">
                        <div class="text-sm font-semibold">Order summary</div>
                        <div class="rounded-lg border border-admin-border bg-white/5 p-5 space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-sm text-admin-text-secondary">Plan</div>
                                    <div class="font-semibold">{{ $plan->name }}</div>
                                    <div class="text-xs text-admin-text-secondary mt-1">Billed {{ $billingLabel }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-admin-text-secondary">Price</div>
                                    <div class="text-2xl font-bold">{{ $plan->currency }} {{ number_format((float) $plan->price, 2) }}</div>
                                </div>
                            </div>

                            <div class="border-t border-admin-border pt-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-medium">Total</div>
                                    <div class="text-sm font-semibold">{{ $plan->currency }} {{ number_format((float) $plan->price, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-admin-text-secondary">Coupon code (optional)</label>
                                <input name="coupon_code" value="{{ old('coupon_code', $couponCode) }}" class="mt-1 block w-full rounded-md border-admin-border bg-transparent" placeholder="WELCOME10">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <x-button href="{{ route('customer.billing.index') }}" variant="secondary">Back</x-button>
                                <x-button type="submit" variant="primary">Continue to Payment</x-button>
                            </div>
                        </div>

                        <div class="text-xs text-admin-text-secondary">
                            By continuing, you agree to be charged according to your selected plan.
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection

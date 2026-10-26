@extends('layouts.admin')

@section('title', $plan->name)
@section('page-title', $plan->name)

@section('breadcrumbs')
    <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
        <li>
            <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                {{ __('Dashboard') }}
            </a>
        </li>
        <li class="text-gray-400">/</li>
        <li>
            <a href="{{ route('admin.plans.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                {{ __('Plans') }}
            </a>
        </li>
        <li class="text-gray-400">/</li>
        <li class="font-medium text-admin-text-primary">
            {{ $plan->name }}
        </li>
    </ol>
@endsection

@section('content')
@php
    $features = $plan->features ?? [];
    $pros = [];
    $cons = [];

    if (is_array($features) && (array_key_exists('pros', $features) || array_key_exists('cons', $features))) {
        $pros = is_array($features['pros'] ?? null) ? $features['pros'] : [];
        $cons = is_array($features['cons'] ?? null) ? $features['cons'] : [];
    } elseif (is_array($features)) {
        $pros = $features;
    }

    $pros = array_values(array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : '', $pros), fn ($value) => $value !== ''));
    $cons = array_values(array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : '', $cons), fn ($value) => $value !== ''));
    $paymentUrl = route('pricing.checkout', ['slug' => $plan->slug]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $plan->name }}</h2>
                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $plan->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ $plan->is_active ? __('Active') : __('Disabled') }}
                </span>
                @if($plan->is_popular)
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ __('Popular') }}</span>
                @endif
                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $plan->is_public ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' }}">
                    {{ $plan->is_public ? __('Public') : __('Private') }}
                </span>
            </div>
            @if($plan->description)
                <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-400">{{ $plan->description }}</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @admincan('admin.plans.edit')
                <x-button href="{{ route('admin.plans.edit', $plan) }}" variant="primary">{{ __('Edit Plan') }}</x-button>
            @endadmincan
            <x-button href="{{ route('admin.plans.index') }}" variant="secondary">{{ __('Back') }}</x-button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-6">
            <x-card title="{{ __('Plan Overview') }}">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Slug') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->slug ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Customer Group') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->customerGroup?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('CTA Button Text') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->cta_text ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Created At') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->created_at?->format('M d, Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Updated At') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->updated_at?->format('M d, Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="{{ __('Pricing') }}">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Price') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $plan->currency }} {{ number_format((float) $plan->price, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Billing Cycle') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ __(ucfirst($plan->billing_cycle)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Trial Days') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->trial_days ?? 0 }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Subscriptions') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $plan->subscriptions->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Stripe Price ID') }}</dt>
                        <dd class="mt-1 break-all text-sm text-gray-900 dark:text-gray-100">{{ $plan->stripe_price_id ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Stripe Product ID') }}</dt>
                        <dd class="mt-1 break-all text-sm text-gray-900 dark:text-gray-100">{{ $plan->stripe_product_id ?: '—' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="{{ __('Features') }}">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Pros') }}</h3>
                        @if(count($pros) > 0)
                            <ul class="mt-3 space-y-2">
                                @foreach($pros as $feature)
                                    <li class="flex items-start gap-2 rounded-md bg-green-50 px-3 py-2 text-sm text-gray-700 dark:bg-green-900/20 dark:text-gray-200">
                                        <span class="mt-0.5 text-green-600 dark:text-green-400">✓</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('No pros added.') }}</p>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Cons') }}</h3>
                        @if(count($cons) > 0)
                            <ul class="mt-3 space-y-2">
                                @foreach($cons as $feature)
                                    <li class="flex items-start gap-2 rounded-md bg-red-50 px-3 py-2 text-sm text-gray-700 dark:bg-red-900/20 dark:text-gray-200">
                                        <span class="mt-0.5 text-red-500 dark:text-red-400">×</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('No cons added.') }}</p>
                        @endif
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="{{ __('Checkout Link') }}">
                <div class="space-y-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Use this direct URL to send customers to checkout for this plan.') }}</p>
                    <div x-data="{ copied: false }" class="space-y-3">
                        <input type="text" readonly value="{{ $paymentUrl }}" class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-700 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                        <button @click="navigator.clipboard.writeText('{{ $paymentUrl }}'); copied = true; setTimeout(() => copied = false, 2000)" class="w-full justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy Link') }}'"></span>
                        </button>
                    </div>
                </div>
            </x-card>

            <x-card title="{{ __('Status') }}">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Active') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $plan->is_active ? __('Yes') : __('No') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Popular') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $plan->is_popular ? __('Yes') : __('No') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Visibility') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $plan->is_public ? __('Public') : __('Private') }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</div>
@endsection

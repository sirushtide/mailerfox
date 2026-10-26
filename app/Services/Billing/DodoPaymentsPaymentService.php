<?php

namespace App\Services\Billing;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Services\AffiliateCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DodoPaymentsPaymentService implements PaymentProviderInterface
{
    public function createCheckoutSession(Customer $customer, Plan $plan, array $options = []): string
    {
        $apiKey = $this->getConfigValue('api_key');
        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('Dodo Payments is not configured. Set DODOPAYMENTS_API_KEY (or configure it via Payment Methods).');
        }

        $productId = $plan->dodo_product_id;
        if (!is_string($productId) || trim($productId) === '') {
            throw new \RuntimeException('Dodo Payments product ID is not configured for this plan.');
        }

        $localSubscriptionId = (int) ($options['local_subscription_id'] ?? 0);
        $referenceId = sprintf(
            'sub_%d_%s',
            $localSubscriptionId,
            Str::lower(Str::random(12))
        );

        if ($localSubscriptionId > 0) {
            Subscription::whereKey($localSubscriptionId)->update([
                'payment_reference' => $referenceId,
            ]);
        }

        $payload = [
            'payment_link' => true,
            'customer' => [
                'email' => (string) $customer->email,
                'name' => (string) ($customer->full_name ?: $customer->email),
            ],
            'product_cart' => [
                [
                    'product_id' => $productId,
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'local_subscription_id' => (string) $localSubscriptionId,
                'plan_id' => (string) $plan->id,
                'plan_slug' => (string) ($plan->slug ?? ''),
                'payment_reference' => $referenceId,
                'customer_id' => (string) $customer->id,
            ],
            'success_url' => (string) ($options['success_url'] ?? route('billing.dodopayments.callback')),
            'cancel_url' => (string) ($options['cancel_url'] ?? route('customer.billing.index')),
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->post($this->getBaseUrl() . '/v1/payments', $payload);

        if (!$response->ok()) {
            Log::warning('Dodo Payments checkout creation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('Dodo Payments checkout failed.');
        }

        $paymentLink = $response->json('payment_link');
        if (!is_string($paymentLink) || trim($paymentLink) === '') {
            throw new \RuntimeException('Dodo Payments checkout failed: missing payment link.');
        }

        return trim($paymentLink);
    }

    public function createCustomerPortal(Customer $customer, array $options = []): string
    {
        return (string) ($options['return_url'] ?? route('customer.billing.index'));
    }

    public function cancelAtPeriodEnd(Subscription $subscription): void
    {
        return;
    }

    public function resume(Subscription $subscription): void
    {
        return;
    }

    public function handleWebhook(Request $request): void
    {
        $payload = $request->json()->all();
        if (!is_array($payload)) {
            abort(400, 'Invalid payload');
        }

        $this->verifyWebhook($request);

        $eventId = data_get($payload, 'data.id');
        if (!is_string($eventId) || trim($eventId) === '') {
            $eventId = (string) Str::uuid();
        }

        $eventType = data_get($payload, 'type');
        if (!is_string($eventType) || trim($eventType) === '') {
            abort(400, 'Invalid payload');
        }

        if (WebhookEvent::where('provider', 'dodopayments')->where('event_id', $eventId)->exists()) {
            return;
        }

        WebhookEvent::create([
            'provider' => 'dodopayments',
            'event_id' => $eventId,
            'type' => $eventType,
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        if (in_array($eventType, ['payment.succeeded', 'subscription.payment_succeeded'], true)) {
            $this->applyPaymentSucceeded($payload);
        }

        if (in_array($eventType, ['subscription.created', 'subscription.updated'], true)) {
            $this->applySubscriptionEvent($payload);
        }
    }

    private function verifyWebhook(Request $request): void
    {
        $secret = $this->getConfigValue('webhook_secret');
        $secret = is_string($secret) ? trim($secret) : null;

        if (!is_string($secret) || $secret === '') {
            if (!app()->environment(['local', 'development', 'testing'])) {
                abort(401, 'Webhook secret is not configured');
            }
            return;
        }

        $signature = $request->header('Dodo-Signature');
        if (!is_string($signature) || trim($signature) === '') {
            if (!app()->environment(['local', 'development', 'testing'])) {
                abort(400, 'Missing signature');
            }
            return;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if (!hash_equals($expected, (string) $signature)) {
            abort(400, 'Invalid signature');
        }
    }

    private function applyPaymentSucceeded(array $payload): ?Subscription
    {
        $metadata = data_get($payload, 'data.metadata');
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $localSubscriptionId = $metadata['local_subscription_id'] ?? null;
        $subscription = $this->resolveSubscription($localSubscriptionId, $payload, $metadata);

        if (!$subscription) {
            return null;
        }

        $plan = $subscription->plan;
        $planDbId = $subscription->plan_db_id;
        if (isset($metadata['plan_id']) && is_numeric($metadata['plan_id'])) {
            $planDbId = (int) $metadata['plan_id'];
        }

        $billingCycle = $plan?->billing_cycle ?? $subscription->billing_cycle;
        $periodStart = now();
        $periodEnd = match ($billingCycle) {
            'yearly' => now()->addYear(),
            'lifetime' => null,
            default => now()->addMonth(),
        };

        $paymentId = data_get($payload, 'data.id');
        $paymentId = is_string($paymentId) ? trim($paymentId) : null;

        $subscription->update([
            'provider' => 'dodopayments',
            'payment_gateway' => 'dodopayments',
            'status' => $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture() ? 'trialing' : 'active',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'cancel_at_period_end' => false,
            'auto_renew' => $billingCycle !== 'lifetime',
            'plan_db_id' => $planDbId,
            'last_payment_status' => 'succeeded',
            'stripe_checkout_session_id' => $paymentId ?: $subscription->stripe_checkout_session_id,
        ]);

        $this->syncPlanFeatures($subscription, $planDbId);

        $amount = data_get($payload, 'data.amount');
        $amount = is_numeric($amount) ? (float) $amount : null;
        $currency = data_get($payload, 'data.currency');
        $currency = is_string($currency) ? strtoupper(trim($currency)) : null;

        app(AffiliateCommissionService::class)->createCommissionForSubscriptionPayment(
            $subscription,
            'dodopayments',
            'dodopayments:' . ($paymentId ?: (string) Str::uuid()),
            $amount,
            $currency
        );

        return $subscription;
    }

    private function applySubscriptionEvent(array $payload): ?Subscription
    {
        $metadata = data_get($payload, 'data.metadata');
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $localSubscriptionId = $metadata['local_subscription_id'] ?? null;
        $subscription = $this->resolveSubscription($localSubscriptionId, $payload, $metadata);

        if (!$subscription) {
            return null;
        }

        $status = data_get($payload, 'data.status');
        $status = is_string($status) ? Str::lower(trim($status)) : null;

        $subscriptionId = data_get($payload, 'data.id');
        $subscriptionId = is_string($subscriptionId) ? trim($subscriptionId) : null;

        $subscription->update([
            'provider' => 'dodopayments',
            'payment_gateway' => 'dodopayments',
            'stripe_subscription_id' => $subscriptionId ?: $subscription->stripe_subscription_id,
        ]);

        if ($status && in_array($status, ['active', 'trialing', 'past_due', 'paused', 'cancelled'], true)) {
            $subscription->update([
                'status' => $status === 'active' && $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture() ? 'trialing' : $status,
                'cancel_at_period_end' => $status === 'cancelled',
                'auto_renew' => !in_array($status, ['cancelled', 'paused'], true),
            ]);
        }

        return $subscription;
    }

    private function resolveSubscription(mixed $localSubscriptionId, array $payload, array $metadata): ?Subscription
    {
        $subscription = null;

        if (is_string($localSubscriptionId) && is_numeric($localSubscriptionId)) {
            $subscription = Subscription::query()->whereKey((int) $localSubscriptionId)->first();
        }

        if (!$subscription && is_numeric($localSubscriptionId)) {
            $subscription = Subscription::query()->whereKey((int) $localSubscriptionId)->first();
        }

        if (!$subscription) {
            $paymentReference = $metadata['payment_reference'] ?? null;
            if (is_string($paymentReference) && trim($paymentReference) !== '') {
                $subscription = Subscription::query()
                    ->where('provider', 'dodopayments')
                    ->where('payment_reference', trim($paymentReference))
                    ->latest()
                    ->first();
            }
        }

        if (!$subscription) {
            $customerId = data_get($payload, 'data.customer_id');
            $customerId = is_string($customerId) ? trim($customerId) : null;
            if ($customerId) {
                $subscription = Subscription::query()
                    ->where('provider', 'dodopayments')
                    ->where('stripe_customer_id', $customerId)
                    ->latest()
                    ->first();
            }
        }

        return $subscription;
    }

    private function syncPlanFeatures(Subscription $subscription, int $planDbId): void
    {
        $plan = $subscription->plan;
        if (!$plan || (int) $plan->id !== $planDbId) {
            $plan = Plan::query()->with('customerGroup')->find($planDbId);
        }

        if (!$plan || !$plan->customerGroup) {
            return;
        }

        $limits = [
            'emails_sent_this_month' => $plan->customerGroup?->limit('sending_quota.monthly_quota', 0),
            'subscribers_count' => $plan->customerGroup?->limit('lists.limits.max_subscribers', 0),
            'campaigns_count' => $plan->customerGroup?->limit('campaigns.limits.max_campaigns', 0),
        ];

        $subscription->update([
            'features' => $plan->customerGroup?->permissions,
            'limits' => array_filter($limits, fn ($v) => $v !== null),
        ]);

        $customer = $subscription->customer;
        if ($customer && $plan->customer_group_id) {
            $customer->customerGroups()->syncWithoutDetaching([(int) $plan->customer_group_id]);
        }
    }

    private function getMode(): string
    {
        $billingProviders = Setting::get('billing_providers');
        $mode = is_array($billingProviders) ? data_get($billingProviders, 'dodopayments.mode') : null;

        if (!is_string($mode) || !in_array($mode, ['live', 'sandbox'], true)) {
            $mode = 'live';
        }

        return $mode;
    }

    private function getBaseUrl(): string
    {
        $configured = $this->getConfigValue('base_url');
        if (is_string($configured) && trim($configured) !== '') {
            return rtrim(trim($configured), '/');
        }

        return $this->getMode() === 'sandbox'
            ? 'https://test.payments.dodopayments.com'
            : 'https://payments.dodopayments.com';
    }

    private function getConfigValue(string $key): ?string
    {
        $billingProviders = Setting::get('billing_providers');
        $mode = $this->getMode();

        $value = null;
        if (is_array($billingProviders)) {
            $value = data_get($billingProviders, 'dodopayments.' . $mode . '.' . $key);
        }

        if (!is_string($value) || trim($value) === '') {
            $fallbackKey = 'dodopayments_' . $key;
            $value = Setting::get($fallbackKey, config('billing.dodopayments.' . $key));
        }

        return is_string($value) ? trim($value) : null;
    }
}

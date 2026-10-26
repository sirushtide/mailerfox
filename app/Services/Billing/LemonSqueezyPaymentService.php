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

class LemonSqueezyPaymentService implements PaymentProviderInterface
{
    private const BASE_URL = 'https://api.lemonsqueezy.com/v1';

    public function createCheckoutSession(Customer $customer, Plan $plan, array $options = []): string
    {
        $apiKey = $this->getConfigValue('api_key');
        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('LemonSqueezy is not configured. Set LEMONSQUEEZY_API_KEY (or configure it via Payment Methods).');
        }

        $storeId = $this->getConfigValue('store_id');
        if (!is_string($storeId) || trim($storeId) === '') {
            throw new \RuntimeException('LemonSqueezy store ID is not configured.');
        }

        $variantId = $plan->lemonsqueezy_variant_id;
        if (!is_string($variantId) || trim($variantId) === '') {
            throw new \RuntimeException('LemonSqueezy variant ID is not configured for this plan.');
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

        $successUrl = (string) ($options['success_url'] ?? route('billing.lemonsqueezy.callback'));
        $cancelUrl = (string) ($options['cancel_url'] ?? route('customer.billing.index'));

        $payload = [
            'data' => [
                'type' => 'checkouts',
                'attributes' => [
                    'product_options' => [
                        'redirect_url' => $successUrl,
                    ],
                    'checkout_data' => [
                        'email' => (string) $customer->email,
                        'custom' => [
                            'local_subscription_id' => (string) $localSubscriptionId,
                            'plan_id' => (string) $plan->id,
                            'plan_slug' => (string) ($plan->slug ?? ''),
                            'payment_reference' => $referenceId,
                            'customer_id' => (string) $customer->id,
                        ],
                    ],
                ],
                'relationships' => [
                    'store' => [
                        'data' => [
                            'type' => 'stores',
                            'id' => (string) $storeId,
                        ],
                    ],
                    'variant' => [
                        'data' => [
                            'type' => 'variants',
                            'id' => (string) $variantId,
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($apiKey)
            ->withHeaders([
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ])
            ->post(self::BASE_URL . '/checkouts', $payload);

        if (!$response->ok()) {
            Log::warning('LemonSqueezy checkout creation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('LemonSqueezy checkout failed.');
        }

        $checkoutUrl = $response->json('data.attributes.url');
        if (!is_string($checkoutUrl) || trim($checkoutUrl) === '') {
            throw new \RuntimeException('LemonSqueezy checkout failed: missing checkout URL.');
        }

        $checkoutId = $response->json('data.id');
        if (is_string($checkoutId) && $localSubscriptionId > 0) {
            Subscription::whereKey($localSubscriptionId)->update([
                'stripe_checkout_session_id' => trim($checkoutId),
            ]);
        }

        return trim($checkoutUrl) . (str_contains($checkoutUrl, '?') ? '&' : '?') . 'cancel_url=' . urlencode($cancelUrl);
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

        $eventType = $request->header('X-Event-Name');
        if (!is_string($eventType) || trim($eventType) === '') {
            $eventType = data_get($payload, 'meta.event_name');
        }
        if (!is_string($eventType) || trim($eventType) === '') {
            abort(400, 'Invalid payload');
        }

        if (WebhookEvent::where('provider', 'lemonsqueezy')->where('event_id', $eventId)->where('type', $eventType)->exists()) {
            return;
        }

        WebhookEvent::create([
            'provider' => 'lemonsqueezy',
            'event_id' => $eventId,
            'type' => $eventType,
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        match ($eventType) {
            'order_created' => $this->applyOrderCreated($payload),
            'subscription_created', 'subscription_updated' => $this->applySubscriptionEvent($payload),
            'subscription_expired' => $this->applySubscriptionExpired($payload),
            default => null,
        };
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

        $signature = $request->header('X-Signature');
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

    private function applyOrderCreated(array $payload): ?Subscription
    {
        $data = data_get($payload, 'data');
        if (!is_array($data)) {
            return null;
        }

        $customData = data_get($data, 'attributes.first_order_item.attributes.custom');
        if (!is_array($customData)) {
            $customData = data_get($data, 'attributes.custom');
        }
        if (!is_array($customData)) {
            $customData = [];
        }

        $localSubscriptionId = $customData['local_subscription_id'] ?? null;
        $subscription = $this->resolveSubscription($localSubscriptionId, $data, $customData);

        if (!$subscription) {
            return null;
        }

        $orderId = data_get($data, 'id');
        $orderId = is_string($orderId) ? trim($orderId) : null;

        $subscription->update([
            'provider' => 'lemonsqueezy',
            'payment_gateway' => 'lemonsqueezy',
            'status' => $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture() ? 'trialing' : 'active',
            'last_payment_status' => 'succeeded',
            'stripe_checkout_session_id' => $orderId ?: $subscription->stripe_checkout_session_id,
        ]);

        $planDbId = $subscription->plan_db_id;
        if (isset($customData['plan_id']) && is_numeric($customData['plan_id'])) {
            $planDbId = (int) $customData['plan_id'];
        }

        $this->syncPlanFeatures($subscription, $planDbId);

        $total = data_get($data, 'attributes.total');
        $total = is_numeric($total) ? ((float) $total) / 100 : null;
        $currency = data_get($data, 'attributes.currency');
        $currency = is_string($currency) ? strtoupper(trim($currency)) : null;

        app(AffiliateCommissionService::class)->createCommissionForSubscriptionPayment(
            $subscription,
            'lemonsqueezy',
            'lemonsqueezy:' . ($orderId ?: (string) Str::uuid()),
            $total,
            $currency
        );

        return $subscription;
    }

    private function applySubscriptionEvent(array $payload): ?Subscription
    {
        $data = data_get($payload, 'data');
        if (!is_array($data)) {
            return null;
        }

        $customData = data_get($data, 'attributes.custom');
        if (!is_array($customData)) {
            $customData = [];
        }

        $localSubscriptionId = $customData['local_subscription_id'] ?? null;
        $subscription = $this->resolveSubscription($localSubscriptionId, $data, $customData);

        if (!$subscription) {
            $subscriptionId = data_get($data, 'id');
            $subscriptionId = is_string($subscriptionId) ? trim($subscriptionId) : null;
            if ($subscriptionId) {
                $subscription = Subscription::query()
                    ->where('provider', 'lemonsqueezy')
                    ->where('stripe_subscription_id', $subscriptionId)
                    ->latest()
                    ->first();
            }
        }

        if (!$subscription) {
            return null;
        }

        $status = data_get($data, 'attributes.status');
        $status = is_string($status) ? Str::lower(trim($status)) : null;

        $subscriptionId = data_get($data, 'id');
        $subscriptionId = is_string($subscriptionId) ? trim($subscriptionId) : null;

        $trialEndsAt = null;
        $trialEndsAtRaw = data_get($data, 'attributes.trial_ends_at');
        if (is_string($trialEndsAtRaw) && $trialEndsAtRaw !== '') {
            $trialEndsAt = \Carbon\Carbon::parse($trialEndsAtRaw);
        }

        $renewsAt = data_get($data, 'attributes.renews_at');
        $periodEnd = null;
        if (is_string($renewsAt) && $renewsAt !== '') {
            $periodEnd = \Carbon\Carbon::parse($renewsAt);
        }

        $planDbId = $subscription->plan_db_id;
        if (isset($customData['plan_id']) && is_numeric($customData['plan_id'])) {
            $planDbId = (int) $customData['plan_id'];
        }
        if (!$planDbId && isset($data['attributes']['variant_id'])) {
            $variantId = $data['attributes']['variant_id'];
            $variantId = is_string($variantId) ? trim($variantId) : null;
            if ($variantId) {
                $foundPlan = Plan::query()->where('lemonsqueezy_variant_id', $variantId)->first();
                if ($foundPlan) {
                    $planDbId = $foundPlan->id;
                }
            }
        }

        $mappedStatus = match ($status) {
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'past_due',
            'paused' => 'suspended',
            'expired' => 'cancelled',
            'unpaid' => 'suspended',
            'cancelled' => 'cancelled',
            default => $subscription->status,
        };

        $updateData = [
            'provider' => 'lemonsqueezy',
            'payment_gateway' => 'lemonsqueezy',
            'status' => $mappedStatus,
            'stripe_subscription_id' => $subscriptionId ?: $subscription->stripe_subscription_id,
            'plan_db_id' => $planDbId ?: $subscription->plan_db_id,
            'cancel_at_period_end' => $status === 'cancelled' || $status === 'expired',
            'auto_renew' => !in_array($status, ['cancelled', 'expired', 'paused'], true),
        ];

        if ($trialEndsAt) {
            $updateData['trial_ends_at'] = $trialEndsAt;
        }
        if ($periodEnd) {
            $updateData['period_end'] = $periodEnd;
        }

        $subscription->update($updateData);

        $this->syncPlanFeatures($subscription, $planDbId ?: $subscription->plan_db_id);

        return $subscription;
    }

    private function applySubscriptionExpired(array $payload): ?Subscription
    {
        $data = data_get($payload, 'data');
        if (!is_array($data)) {
            return null;
        }

        $subscriptionId = data_get($data, 'id');
        $subscriptionId = is_string($subscriptionId) ? trim($subscriptionId) : null;

        if (!$subscriptionId) {
            return null;
        }

        $subscription = Subscription::query()
            ->where('provider', 'lemonsqueezy')
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if (!$subscription) {
            return null;
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew' => false,
            'cancel_at_period_end' => true,
        ]);

        return $subscription;
    }

    private function resolveSubscription(mixed $localSubscriptionId, array $data, array $customData): ?Subscription
    {
        $subscription = null;

        if (is_string($localSubscriptionId) && is_numeric($localSubscriptionId)) {
            $subscription = Subscription::query()->whereKey((int) $localSubscriptionId)->first();
        }

        if (!$subscription && is_numeric($localSubscriptionId)) {
            $subscription = Subscription::query()->whereKey((int) $localSubscriptionId)->first();
        }

        if (!$subscription) {
            $paymentReference = $customData['payment_reference'] ?? null;
            if (is_string($paymentReference) && trim($paymentReference) !== '') {
                $subscription = Subscription::query()
                    ->where('payment_reference', trim($paymentReference))
                    ->latest()
                    ->first();
            }
        }

        if (!$subscription) {
            $orderId = data_get($data, 'id');
            $orderId = is_string($orderId) ? trim($orderId) : null;
            if ($orderId) {
                $subscription = Subscription::query()
                    ->where('provider', 'lemonsqueezy')
                    ->where('stripe_checkout_session_id', $orderId)
                    ->latest()
                    ->first();
            }
        }

        return $subscription;
    }

    private function syncPlanFeatures(Subscription $subscription, ?int $planDbId): void
    {
        if (!$planDbId) {
            return;
        }

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
        $mode = is_array($billingProviders) ? data_get($billingProviders, 'lemonsqueezy.mode') : null;

        if (!is_string($mode) || !in_array($mode, ['live', 'sandbox'], true)) {
            $mode = 'live';
        }

        return $mode;
    }

    private function getConfigValue(string $key): ?string
    {
        $billingProviders = Setting::get('billing_providers');
        $mode = $this->getMode();

        $value = null;
        if (is_array($billingProviders)) {
            $value = data_get($billingProviders, 'lemonsqueezy.' . $mode . '.' . $key);
        }

        if (!is_string($value) || trim($value) === '') {
            $fallbackKey = 'lemonsqueezy_' . $key;
            $value = Setting::get($fallbackKey, config('billing.lemonsqueezy.' . $key));
        }

        return is_string($value) ? trim($value) : null;
    }
}

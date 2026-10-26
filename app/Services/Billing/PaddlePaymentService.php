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

class PaddlePaymentService implements PaymentProviderInterface
{
    private const LIVE_URL = 'https://api.paddle.com';
    private const SANDBOX_URL = 'https://sandbox-api.paddle.com';

    public function createCheckoutSession(Customer $customer, Plan $plan, array $options = []): string
    {
        $apiKey = $this->getConfigValue('api_key');
        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('Paddle is not configured. Set PADDLE_API_KEY (or configure it via Payment Methods).');
        }

        $priceId = $plan->paddle_price_id;
        if (!is_string($priceId) || trim($priceId) === '') {
            throw new \RuntimeException('Paddle price ID is not configured for this plan.');
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

        $paddleCustomerId = $customer->paddle_customer_id ?? null;
        if (!is_string($paddleCustomerId) || trim($paddleCustomerId) === '') {
            $paddleCustomerId = $this->createPaddleCustomer($customer, $apiKey);
        }

        $payload = [
            'collection_mode' => 'automatic',
            'currency_code' => strtoupper((string) ($plan->currency ?: 'USD')),
            'customer_id' => $paddleCustomerId,
            'items' => [
                [
                    'price_id' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'custom_data' => [
                'local_subscription_id' => (string) $localSubscriptionId,
                'plan_id' => (string) $plan->id,
                'plan_slug' => (string) ($plan->slug ?? ''),
                'payment_reference' => $referenceId,
                'customer_id' => (string) $customer->id,
            ],
        ];

        $successUrl = $options['success_url'] ?? route('billing.paddle.callback');
        $cancelUrl = $options['cancel_url'] ?? route('customer.billing.index');

        $payload['checkout'] = [
            'url' => (string) $successUrl,
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->post($this->getBaseUrl() . '/v2/transactions', $payload);

        if (!$response->ok()) {
            Log::warning('Paddle transaction creation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('Paddle checkout failed.');
        }

        $checkoutUrl = $response->json('data.checkout.url');
        if (!is_string($checkoutUrl) || trim($checkoutUrl) === '') {
            throw new \RuntimeException('Paddle checkout failed: missing checkout URL.');
        }

        $transactionId = $response->json('data.id');
        if (is_string($transactionId) && $localSubscriptionId > 0) {
            Subscription::whereKey($localSubscriptionId)->update([
                'stripe_checkout_session_id' => trim($transactionId),
            ]);
        }

        return trim($checkoutUrl) . (str_contains($checkoutUrl, '?') ? '&' : '?') . 'cancel_url=' . urlencode((string) $cancelUrl);
    }

    public function createCustomerPortal(Customer $customer, array $options = []): string
    {
        return (string) ($options['return_url'] ?? route('customer.billing.index'));
    }

    public function cancelAtPeriodEnd(Subscription $subscription): void
    {
        $apiKey = $this->getConfigValue('api_key');
        $subscriptionId = $subscription->stripe_subscription_id;
        if (!is_string($apiKey) || trim($apiKey) === '' || !is_string($subscriptionId) || trim($subscriptionId) === '') {
            return;
        }

        try {
            Http::withToken($apiKey)
                ->acceptJson()
                ->patch($this->getBaseUrl() . '/v2/subscriptions/' . urlencode($subscriptionId), [
                    'scheduled_change' => null,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Paddle subscription cancel call failed', [
                'subscription_id' => $subscription->id,
                'paddle_subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
        }
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

        $eventId = data_get($payload, 'event_id');
        if (!is_string($eventId) || trim($eventId) === '') {
            $eventId = (string) Str::uuid();
        }

        $eventType = data_get($payload, 'event_type');
        if (!is_string($eventType) || trim($eventType) === '') {
            abort(400, 'Invalid payload');
        }

        if (WebhookEvent::where('provider', 'paddle')->where('event_id', $eventId)->exists()) {
            return;
        }

        WebhookEvent::create([
            'provider' => 'paddle',
            'event_id' => $eventId,
            'type' => $eventType,
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        match ($eventType) {
            'subscription.created', 'subscription.updated' => $this->applySubscriptionEvent($payload),
            'transaction.completed' => $this->applyTransactionCompleted($payload),
            'subscription.cancelled' => $this->applySubscriptionCancelled($payload),
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

        $signatureHeader = $request->header('Paddle-Signature');
        if (!is_string($signatureHeader) || trim($signatureHeader) === '') {
            if (!app()->environment(['local', 'development', 'testing'])) {
                abort(400, 'Missing signature');
            }
            return;
        }

        $parts = [];
        foreach (explode(';', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key] = $value;
        }

        $timestamp = $parts['ts'] ?? '';
        $signature = $parts['h1'] ?? '';

        if (!is_string($timestamp) || $timestamp === '' || !is_string($signature) || $signature === '') {
            abort(400, 'Invalid signature');
        }

        $signedPayload = $timestamp . ':' . $request->getContent();
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        if (!hash_equals($expected, $signature)) {
            abort(400, 'Invalid signature');
        }
    }

    private function applySubscriptionEvent(array $payload): ?Subscription
    {
        $data = data_get($payload, 'data');
        if (!is_array($data)) {
            return null;
        }

        $customData = data_get($data, 'custom_data');
        if (!is_array($customData)) {
            $customData = [];
        }

        $localSubscriptionId = $customData['local_subscription_id'] ?? null;
        $subscription = $this->resolveSubscription($localSubscriptionId, $data, $customData);

        if (!$subscription) {
            return null;
        }

        $status = data_get($data, 'status');
        $status = is_string($status) ? Str::lower(trim($status)) : null;

        $subscriptionId = data_get($data, 'id');
        $subscriptionId = is_string($subscriptionId) ? trim($subscriptionId) : null;

        $customerId = data_get($data, 'customer_id');
        $customerId = is_string($customerId) ? trim($customerId) : null;

        $periodStart = null;
        $periodEnd = null;
        $currentBillingPeriod = data_get($data, 'current_billing_period');
        if (is_array($currentBillingPeriod)) {
            $periodStart = is_string($currentBillingPeriod['starts_at'] ?? null) ? \Carbon\Carbon::parse($currentBillingPeriod['starts_at']) : null;
            $periodEnd = is_string($currentBillingPeriod['ends_at'] ?? null) ? \Carbon\Carbon::parse($currentBillingPeriod['ends_at']) : null;
        }

        $planDbId = $subscription->plan_db_id;
        $items = data_get($data, 'items');
        if (is_array($items) && isset($items[0]['price']['id'])) {
            $priceId = $items[0]['price']['id'];
            $foundPlan = Plan::query()->where('paddle_price_id', $priceId)->first();
            if ($foundPlan) {
                $planDbId = $foundPlan->id;
            }
        }
        if (isset($customData['plan_id']) && is_numeric($customData['plan_id'])) {
            $planDbId = (int) $customData['plan_id'];
        }

        $mappedStatus = match ($status) {
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'past_due',
            'paused' => 'suspended',
            'canceled' => 'cancelled',
            default => $subscription->status,
        };

        $subscription->update([
            'provider' => 'paddle',
            'payment_gateway' => 'paddle',
            'status' => $mappedStatus,
            'stripe_subscription_id' => $subscriptionId ?: $subscription->stripe_subscription_id,
            'stripe_customer_id' => $customerId ?: $subscription->stripe_customer_id,
            'period_start' => $periodStart ?: $subscription->period_start,
            'period_end' => $periodEnd ?: $subscription->period_end,
            'plan_db_id' => $planDbId ?: $subscription->plan_db_id,
            'cancel_at_period_end' => $status === 'canceled',
            'auto_renew' => !in_array($status, ['canceled', 'paused'], true),
        ]);

        $this->syncPlanFeatures($subscription, $planDbId ?: $subscription->plan_db_id);

        return $subscription;
    }

    private function applyTransactionCompleted(array $payload): ?Subscription
    {
        $data = data_get($payload, 'data');
        if (!is_array($data)) {
            return null;
        }

        $customData = data_get($data, 'custom_data');
        if (!is_array($customData)) {
            $customData = [];
        }

        $subscriptionId = data_get($data, 'subscription_id');
        $subscriptionId = is_string($subscriptionId) ? trim($subscriptionId) : null;

        $subscription = $this->resolveSubscription($customData['local_subscription_id'] ?? null, $data, $customData);

        if (!$subscription && $subscriptionId) {
            $subscription = Subscription::query()
                ->where('provider', 'paddle')
                ->where('stripe_subscription_id', $subscriptionId)
                ->latest()
                ->first();
        }

        if (!$subscription) {
            return null;
        }

        if ($subscriptionId && $subscription->stripe_subscription_id !== $subscriptionId) {
            $subscription->update(['stripe_subscription_id' => $subscriptionId]);
        }

        $subscription->update([
            'status' => $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture() ? 'trialing' : 'active',
            'last_payment_status' => 'succeeded',
        ]);

        $this->syncPlanFeatures($subscription, $subscription->plan_db_id);

        $transactionId = data_get($data, 'id');
        $transactionId = is_string($transactionId) ? trim($transactionId) : null;

        $amount = null;
        $totals = data_get($data, 'details.totals');
        if (is_array($totals) && isset($totals['grand_total']) && is_string($totals['grand_total'])) {
            $amount = ((float) $totals['grand_total']) / 100;
        }
        $currency = data_get($data, 'currency_code');
        $currency = is_string($currency) ? strtoupper(trim($currency)) : null;

        app(AffiliateCommissionService::class)->createCommissionForSubscriptionPayment(
            $subscription,
            'paddle',
            'paddle:' . ($transactionId ?: (string) Str::uuid()),
            $amount,
            $currency
        );

        return $subscription;
    }

    private function applySubscriptionCancelled(array $payload): ?Subscription
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
            ->where('provider', 'paddle')
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
                    ->where('provider', 'paddle')
                    ->where('payment_reference', trim($paymentReference))
                    ->latest()
                    ->first();
            }
        }

        if (!$subscription) {
            $transactionId = data_get($data, 'id');
            $transactionId = is_string($transactionId) ? trim($transactionId) : null;
            if ($transactionId) {
                $subscription = Subscription::query()
                    ->where('provider', 'paddle')
                    ->where('stripe_checkout_session_id', $transactionId)
                    ->latest()
                    ->first();
            }
        }

        if (!$subscription) {
            $subscriptionId = data_get($data, 'subscription_id');
            $subscriptionId = is_string($subscriptionId) ? trim($subscriptionId) : null;
            if ($subscriptionId) {
                $subscription = Subscription::query()
                    ->where('provider', 'paddle')
                    ->where('stripe_subscription_id', $subscriptionId)
                    ->latest()
                    ->first();
            }
        }

        return $subscription;
    }

    private function createPaddleCustomer(Customer $customer, string $apiKey): string
    {
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->post($this->getBaseUrl() . '/v2/customers', [
                'email' => (string) $customer->email,
                'name' => (string) ($customer->full_name ?: $customer->email),
            ]);

        if (!$response->ok()) {
            throw new \RuntimeException('Paddle customer creation failed.');
        }

        $customerId = $response->json('data.id');
        if (!is_string($customerId) || trim($customerId) === '') {
            throw new \RuntimeException('Paddle customer creation failed: missing customer ID.');
        }

        $customer->update(['paddle_customer_id' => $customerId]);

        return $customerId;
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
        $mode = is_array($billingProviders) ? data_get($billingProviders, 'paddle.mode') : null;

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

        return $this->getMode() === 'sandbox' ? self::SANDBOX_URL : self::LIVE_URL;
    }

    private function getConfigValue(string $key): ?string
    {
        $billingProviders = Setting::get('billing_providers');
        $mode = $this->getMode();

        $value = null;
        if (is_array($billingProviders)) {
            $value = data_get($billingProviders, 'paddle.' . $mode . '.' . $key);
        }

        if (!is_string($value) || trim($value) === '') {
            $fallbackKey = 'paddle_' . $key;
            $value = Setting::get($fallbackKey, config('billing.paddle.' . $key));
        }

        return is_string($value) ? trim($value) : null;
    }
}

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

class UddoktaPayPaymentService implements PaymentProviderInterface
{
    public function createCheckoutSession(Customer $customer, Plan $plan, array $options = []): string
    {
        $apiKey = $this->getConfigValue('api_key');
        $baseUrl = $this->getBaseUrl();

        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('UddoktaPay is not configured. Set UDDOKTAPAY_API_KEY (or configure it via Payment Methods).');
        }

        $amount = (float) $plan->price;
        if ($amount < 0) {
            $amount = 0;
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

        $redirectUrl = $options['redirect_url'] ?? ($options['success_url'] ?? route('billing.uddoktapay.callback'));
        $cancelUrl = $options['cancel_url'] ?? route('customer.billing.index');
        $webhookUrl = $options['webhook_url'] ?? route('webhooks.uddoktapay');

        $payload = [
            'full_name' => (string) ($customer->full_name ?: $customer->email),
            'email' => (string) $customer->email,
            'amount' => number_format($amount, 2, '.', ''),
            'metadata' => [
                'local_subscription_id' => (string) $localSubscriptionId,
                'plan_id' => (string) $plan->id,
                'plan_slug' => (string) ($plan->slug ?? ''),
                'payment_reference' => $referenceId,
                'success_url' => (string) ($options['success_url'] ?? ''),
                'cancel_url' => (string) ($options['cancel_url'] ?? ''),
            ],
            'redirect_url' => (string) $redirectUrl,
            'return_type' => 'GET',
            'cancel_url' => (string) $cancelUrl,
            'webhook_url' => (string) $webhookUrl,
        ];

        $response = Http::acceptJson()
            ->withHeaders([
                'RT-UDDOKTAPAY-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($baseUrl . '/api/checkout-v2', $payload);

        if (!$response->ok()) {
            Log::warning('UddoktaPay checkout creation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('UddoktaPay checkout failed.');
        }

        $paymentUrl = $response->json('payment_url');
        $status = $response->json('status');

        if (!$status || !is_string($paymentUrl) || trim($paymentUrl) === '') {
            throw new \RuntimeException((string) ($response->json('message') ?: 'UddoktaPay checkout failed: missing payment URL.'));
        }

        return trim($paymentUrl);
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

        $apiKey = $this->getConfigValue('api_key');
        $headerApiKey = $request->header('RT-UDDOKTAPAY-API-KEY');

        $apiKey = is_string($apiKey) ? trim($apiKey) : null;
        $headerApiKey = is_string($headerApiKey) ? trim($headerApiKey) : null;

        if ((!$apiKey || !$headerApiKey || !hash_equals($apiKey, $headerApiKey)) && !app()->environment(['local', 'development', 'testing'])) {
            abort(401, 'Unauthorized Action');
        }

        $invoiceId = data_get($payload, 'invoice_id');
        $eventId = is_string($invoiceId) && trim($invoiceId) !== '' ? trim($invoiceId) : (string) Str::uuid();

        if (WebhookEvent::where('provider', 'uddoktapay')->where('event_id', $eventId)->exists()) {
            return;
        }

        $status = data_get($payload, 'status');
        $eventType = 'payment.' . Str::lower(is_string($status) && trim($status) !== '' ? trim($status) : 'unknown');

        WebhookEvent::create([
            'provider' => 'uddoktapay',
            'event_id' => $eventId,
            'type' => $eventType,
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        $this->applyVerifiedPaymentData($payload);
    }

    public function verifyPaymentAndSync(string $invoiceId): array
    {
        $apiKey = $this->getConfigValue('api_key');
        $baseUrl = $this->getBaseUrl();

        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('UddoktaPay is not configured.');
        }

        $response = Http::acceptJson()
            ->withHeaders([
                'RT-UDDOKTAPAY-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($baseUrl . '/api/verify-payment', [
                'invoice_id' => $invoiceId,
            ]);

        if (!$response->ok()) {
            Log::warning('UddoktaPay payment verification failed', [
                'status' => $response->status(),
                'body' => $response->json(),
                'invoice_id' => $invoiceId,
            ]);
            throw new \RuntimeException('Unable to verify UddoktaPay payment.');
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid UddoktaPay verification response.');
        }

        return $data;
    }

    public function applyVerifiedPaymentByInvoice(string $invoiceId): ?Subscription
    {
        return $this->applyVerifiedPaymentData($this->verifyPaymentAndSync($invoiceId));
    }

    public function applyVerifiedPaymentData(array $data): ?Subscription
    {
        $invoiceId = $data['invoice_id'] ?? null;
        $metadata = $data['metadata'] ?? [];
        $metadata = is_array($metadata) ? $metadata : [];

        $subscription = null;
        $localSubscriptionId = $metadata['local_subscription_id'] ?? null;
        if (is_string($localSubscriptionId) && is_numeric($localSubscriptionId)) {
            $subscription = Subscription::query()->whereKey((int) $localSubscriptionId)->first();
        }

        if (!$subscription && is_numeric($localSubscriptionId)) {
            $subscription = Subscription::query()->whereKey((int) $localSubscriptionId)->first();
        }

        if (!$subscription && is_string($invoiceId) && trim($invoiceId) !== '') {
            $subscription = Subscription::query()
                ->where('provider', 'uddoktapay')
                ->where('stripe_checkout_session_id', trim($invoiceId))
                ->latest()
                ->first();
        }

        if (!$subscription) {
            $paymentReference = $metadata['payment_reference'] ?? null;
            if (is_string($paymentReference) && trim($paymentReference) !== '') {
                $subscription = Subscription::query()
                    ->where('payment_reference', trim($paymentReference))
                    ->latest()
                    ->first();
            }
        }

        if (!$subscription) {
            return null;
        }

        $status = strtoupper((string) ($data['status'] ?? ''));
        if ($status !== 'COMPLETED') {
            $subscription->update([
                'status' => 'pending',
                'last_payment_status' => Str::lower($status !== '' ? $status : 'pending'),
                'stripe_checkout_session_id' => is_string($invoiceId) && trim($invoiceId) !== '' ? trim($invoiceId) : $subscription->stripe_checkout_session_id,
            ]);

            return $subscription;
        }

        $plan = $subscription->plan;
        $planDbId = $subscription->plan_db_id;
        if (isset($metadata['plan_id']) && is_numeric($metadata['plan_id'])) {
            $planDbId = (int) $metadata['plan_id'];
        }

        if ($planDbId && (!$plan || (int) $plan->id !== (int) $planDbId)) {
            $plan = Plan::query()->with('customerGroup')->find($planDbId);
        }

        $billingCycle = $plan?->billing_cycle ?? $subscription->billing_cycle;
        $periodStart = now();
        $periodEnd = match ($billingCycle) {
            'yearly' => now()->addYear(),
            'lifetime' => null,
            default => now()->addMonth(),
        };

        $transactionId = $data['transaction_id'] ?? null;
        $paymentMethod = $data['payment_method'] ?? null;

        $subscription->update([
            'provider' => 'uddoktapay',
            'payment_gateway' => 'uddoktapay',
            'status' => $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture() ? 'trialing' : 'active',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'cancel_at_period_end' => false,
            'auto_renew' => $billingCycle !== 'lifetime',
            'plan_db_id' => $planDbId,
            'payment_method' => is_string($paymentMethod) && trim($paymentMethod) !== '' ? trim($paymentMethod) : $subscription->payment_method,
            'payment_reference' => is_string($transactionId) && trim($transactionId) !== '' ? trim($transactionId) : $subscription->payment_reference,
            'last_payment_status' => 'succeeded',
            'stripe_checkout_session_id' => is_string($invoiceId) && trim($invoiceId) !== '' ? trim($invoiceId) : $subscription->stripe_checkout_session_id,
        ]);

        if ($plan && $plan->customerGroup) {
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

        $amount = $data['amount'] ?? null;
        $amount = is_numeric($amount) ? (float) $amount : null;

        app(AffiliateCommissionService::class)->createCommissionForSubscriptionPayment(
            $subscription,
            'uddoktapay',
            'uddoktapay:' . (is_string($invoiceId) && trim($invoiceId) !== '' ? trim($invoiceId) : (string) Str::uuid()),
            $amount,
            'BDT'
        );

        return $subscription;
    }

    private function getMode(): string
    {
        $billingProviders = Setting::get('billing_providers');
        $mode = is_array($billingProviders) ? data_get($billingProviders, 'uddoktapay.mode') : null;

        if (!is_string($mode) || !in_array($mode, ['live', 'sandbox'], true)) {
            $mode = 'live';
        }

        return $mode;
    }

    private function getBaseUrl(): string
    {
        $configured = $this->getConfigValue('base_url');

        if (is_string($configured) && trim($configured) !== '') {
            $url = rtrim(trim($configured), '/');
            // Strip trailing /api so users can paste the URL exactly as UddoktaPay provides it
            if (str_ends_with($url, '/api')) {
                $url = substr($url, 0, -4);
            }
            return $url;
        }

        return $this->getMode() === 'sandbox'
            ? 'https://sandbox.uddoktapay.com'
            : 'https://pay.uddoktapay.com';
    }

    private function getConfigValue(string $key): ?string
    {
        $billingProviders = Setting::get('billing_providers');
        $mode = $this->getMode();

        $value = null;
        if (is_array($billingProviders)) {
            $value = data_get($billingProviders, 'uddoktapay.' . $mode . '.' . $key);
        }

        if (!is_string($value) || trim($value) === '') {
            $fallbackKey = 'uddoktapay_' . $key;
            $value = Setting::get($fallbackKey, config('billing.uddoktapay.' . $key));
        }

        return is_string($value) ? trim($value) : null;
    }
}

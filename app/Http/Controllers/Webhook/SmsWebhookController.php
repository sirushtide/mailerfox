<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SmsWebhookController extends Controller
{
    public function twilio(Request $request, SmsWebhookService $service, SmsManager $manager): Response
    {
        $payload = $request->all();

        $account = $this->resolveAccount($payload);

        if ($account) {
            $url = (string) config('services.twilio.webhook_url', $request->fullUrl());
            $signature = $request->header('X-Twilio-Signature');
            $valid = $manager->resolve($account)->verifyWebhook($account, $url, $payload, $signature);

            if (!$valid) {
                return response('Invalid signature', 403);
            }

            if (!empty($payload['Body'])) {
                $service->handleInbound($account, $payload);
            } else {
                $service->handleTwilio($request);
            }

            return response('OK', 200);
        }

        // No matching account: still record the event for diagnostics.
        $service->handleTwilio($request);

        return response('OK', 200);
    }

    protected function resolveAccount(array $payload): ?ChannelAccount
    {
        $to = $payload['To'] ?? null;
        $from = $payload['From'] ?? null;

        foreach (array_filter([$to, $from]) as $address) {
            $account = ChannelAccount::forChannel('sms')
                ->where('provider', 'twilio')
                ->where('from_address', $address)
                ->first();

            if ($account) {
                return $account;
            }
        }

        return null;
    }
}

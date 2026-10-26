<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\ChannelWebhookEvent;
use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    /**
     * Meta webhook verification (GET).
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $expected = (string) (Setting::get('whatsapp_verify_token', '') ?: config('services.whatsapp.verify_token', ''));

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, (string) $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Meta webhook events (POST).
     */
    public function handle(Request $request, WhatsAppWebhookService $service): Response
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256');

        $appSecret = (string) (Setting::get('whatsapp_app_secret', '') ?: config('services.whatsapp.app_secret', ''));

        if ($appSecret !== '') {
            $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
            if (!is_string($signature) || !hash_equals($expected, $signature)) {
                return response('Invalid signature', 403);
            }
        }

        $payload = $request->json()->all() ?: $request->all();

        $event = ChannelWebhookEvent::create([
            'channel'    => 'whatsapp',
            'provider'   => 'meta',
            'event_type' => $payload['object'] ?? 'events',
            'payload'    => $payload,
        ]);

        try {
            foreach (($payload['entry'] ?? []) as $entry) {
                $service->handleEntry($entry);
            }

            $event->update(['processed' => true, 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $event->update(['error' => $e->getMessage()]);
        }

        return response('OK', 200);
    }
}

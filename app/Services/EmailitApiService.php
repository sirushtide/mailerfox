<?php

namespace App\Services;

use App\Models\DeliveryServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailitApiService
{
    private const BASE_URL = 'https://emailit.com/api/v1';

    public function send(DeliveryServer $server, array $message): array
    {
        $apiKey = $this->getApiKey($server);

        $payload = $this->buildPayload($message);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->post(self::BASE_URL . '/emails', $payload);

        if (!$response->successful()) {
            Log::warning('Emailit API request failed', [
                'server_id' => $server->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $errorBody = $response->json();
            $errorMessage = is_array($errorBody) && isset($errorBody['message'])
                ? $errorBody['message']
                : $response->body();

            throw new \RuntimeException('Emailit API error: ' . $errorMessage);
        }

        return $response->json() ?? [];
    }

    public function sendBatch(DeliveryServer $server, array $message, array $recipients): array
    {
        $apiKey = $this->getApiKey($server);

        $toEmails = array_values(array_filter(array_map(fn ($r) => $r['email'] ?? ($r['email_address']['address'] ?? null), $recipients)));

        if (empty($toEmails)) {
            throw new \RuntimeException('No valid recipients for Emailit batch send.');
        }

        $payload = $this->buildPayload($message);
        $payload['to'] = $toEmails;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->timeout(60)
            ->post(self::BASE_URL . '/emails', $payload);

        if (!$response->successful()) {
            Log::warning('Emailit API batch request failed', [
                'server_id' => $server->id,
                'recipient_count' => count($toEmails),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $errorBody = $response->json();
            $errorMessage = is_array($errorBody) && isset($errorBody['message'])
                ? $errorBody['message']
                : $response->body();

            throw new \RuntimeException('Emailit API error: ' . $errorMessage);
        }

        return $response->json() ?? [];
    }

    public function fetchDomains(DeliveryServer $server): array
    {
        $apiKey = $this->getApiKey($server);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->get(self::BASE_URL . '/domains');

        if (!$response->successful()) {
            Log::warning('Emailit domains fetch failed', [
                'server_id' => $server->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $json = $response->json();
        $domains = $json['data'] ?? $json['domains'] ?? $json ?? [];

        return is_array($domains) ? $domains : [];
    }

    private function buildPayload(array $message): array
    {
        $payload = [
            'from' => (string) ($message['from_email'] ?? ''),
            'subject' => (string) ($message['subject'] ?? ''),
        ];

        if (!empty($message['from_name'])) {
            $payload['from_name'] = (string) $message['from_name'];
        }

        if (!empty($message['htmlbody']) || !empty($message['html'])) {
            $payload['html'] = (string) ($message['htmlbody'] ?? $message['html'] ?? '');
        }

        if (!empty($message['textbody']) || !empty($message['text'])) {
            $payload['text'] = (string) ($message['textbody'] ?? $message['text'] ?? '');
        }

        if (!empty($message['reply_to'])) {
            $payload['reply_to'] = (string) $message['reply_to'];
        }

        if (!empty($message['headers']) && is_array($message['headers'])) {
            $payload['headers'] = $message['headers'];
        }

        return $payload;
    }

    private function getApiKey(DeliveryServer $server): string
    {
        $settings = $server->settings ?? [];
        $apiKey = $settings['api_key'] ?? null;

        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new \InvalidArgumentException('Emailit API key is missing.');
        }

        return trim($apiKey);
    }
}

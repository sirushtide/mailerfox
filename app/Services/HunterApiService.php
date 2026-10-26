<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HunterApiService
{
    public function __construct(
        private EnrichmentKeyResolver $keyResolver
    ) {}

    public function domainSearch(string $domain): ?array
    {
        $apiKey = $this->keyResolver->resolve('hunter');
        if (!$apiKey) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.hunter.io/v2/domain-search', [
                'domain' => $domain,
            ]);

            if (!$response->successful()) {
                Log::warning('Hunter API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            $emails = $data['data']['emails'] ?? [];

            if (empty($emails)) {
                return null;
            }

            $best = $emails[0];
            foreach ($emails as $email) {
                if (($email['confidence'] ?? 0) > ($best['confidence'] ?? 0)) {
                    $best = $email;
                }
            }

            return [
                'email' => $best['value'] ?? null,
                'title' => $best['position'] ?? null,
                'confidence' => $best['confidence'] ?? null,
                'linkedin_url' => $best['linkedin'] ?? null,
                'phone' => $best['phone_number'] ?? null,
                'company' => $data['data']['organization'] ?? null,
                'company_size' => null,
                'industry' => null,
                'location' => null,
                'technologies' => [],
                'funding' => null,
                'employee_count' => null,
                'social_profiles' => [
                    'linkedin' => $best['linkedin'] ?? null,
                    'twitter' => $best['twitter'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Hunter API exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function verifyEmail(string $email): ?array
    {
        $apiKey = $this->keyResolver->resolve('hunter');
        if (!$apiKey) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.hunter.io/v2/email-verifier', [
                'email' => $email,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return [
                'status' => $data['data']['status'] ?? null,
                'score' => $data['data']['score'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Hunter verify exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

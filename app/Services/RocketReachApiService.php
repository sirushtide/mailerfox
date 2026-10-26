<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RocketReachApiService
{
    public function __construct(
        private EnrichmentKeyResolver $keyResolver
    ) {}

    public function searchPerson(string $name, ?string $company = null): ?array
    {
        $apiKey = $this->keyResolver->resolve('rocketreach');
        if (!$apiKey) {
            return null;
        }

        try {
            $parts = $this->splitName($name);

            $query = [
                'api_key' => $apiKey,
                'name' => $name,
            ];

            if ($company) {
                $query['current_employer'] = $company;
            }

            $response = Http::get('https://api.rocketreach.co/v1/person/lookup', $query);

            if (!$response->successful()) {
                Log::warning('RocketReach API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            $profile = $data['profile'] ?? $data;

            if (empty($profile) || !is_array($profile)) {
                return null;
            }

            return [
                'email' => $profile['current_work_email'] ?? $profile['emails'][0]['email'] ?? null,
                'title' => $profile['current_title'] ?? null,
                'linkedin_url' => $profile['links']['linkedin'] ?? null,
                'phone' => $profile['phones'][0] ?? null,
                'company' => $profile['current_employer_name'] ?? null,
                'company_size' => $profile['current_employer_size'] ?? null,
                'industry' => $profile['current_employer_industry'] ?? null,
                'location' => $profile['location'] ?? null,
                'technologies' => [],
                'funding' => null,
                'employee_count' => $profile['current_employer_size'] ?? null,
                'social_profiles' => [
                    'linkedin' => $profile['links']['linkedin'] ?? null,
                    'twitter' => $profile['links']['twitter'] ?? null,
                    'facebook' => $profile['links']['facebook'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('RocketReach API exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function splitName(string $name): array
    {
        $parts = explode(' ', trim($name), 2);
        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
        ];
    }
}

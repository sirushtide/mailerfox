<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApolloApiService
{
    public function __construct(
        private EnrichmentKeyResolver $keyResolver
    ) {}

    public function searchPerson(string $name, ?string $domain = null): ?array
    {
        $apiKey = $this->keyResolver->resolve('apollo');
        if (!$apiKey) {
            return null;
        }

        try {
            $parts = $this->splitName($name);

            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.apollo.io/v1/people/match', [
                'first_name' => $parts['first_name'],
                'last_name' => $parts['last_name'],
                'organization_name' => $domain,
            ]);

            if (!$response->successful()) {
                Log::warning('Apollo API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            $person = $data['person'] ?? null;

            if (!$person) {
                return null;
            }

            return [
                'email' => $person['email'] ?? null,
                'title' => $person['title'] ?? null,
                'linkedin_url' => $person['linkedin_url'] ?? null,
                'phone' => $person['phone_numbers'][0]['number'] ?? null,
                'company' => $person['organization']['name'] ?? null,
                'company_size' => $person['organization']['estimated_num_employees'] ?? null,
                'industry' => $person['organization']['industry'] ?? null,
                'location' => $person['organization']['location'] ?? null,
                'technologies' => $person['organization']['technologies'] ?? [],
                'funding' => $person['organization']['funding'] ?? null,
                'employee_count' => $person['organization']['estimated_num_employees'] ?? null,
                'social_profiles' => [
                    'linkedin' => $person['linkedin_url'] ?? null,
                    'twitter' => $person['twitter_url'] ?? null,
                    'facebook' => $person['facebook_url'] ?? null,
                    'github' => $person['github_url'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Apollo API exception', ['error' => $e->getMessage()]);
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

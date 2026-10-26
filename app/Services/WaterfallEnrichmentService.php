<?php

namespace App\Services;

use App\Models\ScraperLead;
use Illuminate\Support\Facades\Log;

class WaterfallEnrichmentService
{
    public function __construct(
        private ApolloApiService $apollo,
        private HunterApiService $hunter,
        private RocketReachApiService $rocketReach,
    ) {}

    public function enrich(ScraperLead $lead): bool
    {
        $lead->update(['enrichment_status' => 'enriching']);

        try {
            $domain = $this->extractDomain($lead->website) ?: $this->extractDomainFromEmail($lead->email);
            $name = $lead->name ?? '';

            $merged = [];
            $sources = [];

            // Step 1: Apollo
            $apolloData = $this->apollo->searchPerson($name, $domain);
            if ($apolloData) {
                $merged = $apolloData;
                $sources[] = 'apollo';
            }

            // Step 2: Hunter (if missing key fields)
            if (!$this->hasKeyFields($merged) && $domain) {
                $hunterData = $this->hunter->domainSearch($domain);
                if ($hunterData) {
                    $merged = $this->mergeData($merged, $hunterData);
                    $sources[] = 'hunter';
                }
            }

            // Step 3: RocketReach (if still missing key fields)
            if (!$this->hasKeyFields($merged)) {
                $company = $merged['company'] ?? $domain ?? null;
                $rocketData = $this->rocketReach->searchPerson($name, $company);
                if ($rocketData) {
                    $merged = $this->mergeData($merged, $rocketData);
                    $sources[] = 'rocketreach';
                }
            }

            if (empty($merged)) {
                $lead->update(['enrichment_status' => 'failed']);
                return false;
            }

            $confidence = $this->calculateConfidence($merged, $sources);

            $lead->update([
                'enrichment_status' => 'enriched',
                'enriched_at' => now(),
                'enrichment_data' => $merged,
                'enrichment_sources' => $sources,
                'confidence_score' => $confidence,
                'linkedin_url' => $merged['linkedin_url'] ?? null,
                'job_title' => $merged['title'] ?? null,
                'company_size' => $merged['company_size'] ? (string) $merged['company_size'] : null,
                'industry' => $merged['industry'] ?? null,
                'location' => $merged['location'] ?? null,
                'technologies' => $merged['technologies'] ?? null,
                'social_profiles' => $merged['social_profiles'] ?? null,
                'funding' => $merged['funding'] ?? null,
                'employee_count' => $merged['employee_count'] ? (string) $merged['employee_count'] : null,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Waterfall enrichment failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            $lead->update(['enrichment_status' => 'failed']);
            return false;
        }
    }

    private function hasKeyFields(array $data): bool
    {
        return filled($data['email'] ?? null)
            && filled($data['title'] ?? null)
            && filled($data['linkedin_url'] ?? null);
    }

    private function mergeData(array $existing, array $new): array
    {
        foreach ($new as $key => $value) {
            if (!isset($existing[$key]) || empty($existing[$key])) {
                $existing[$key] = $value;
            }
        }
        return $existing;
    }

    private function calculateConfidence(array $data, array $sources): int
    {
        $score = 0;
        $fields = ['email', 'title', 'linkedin_url', 'company', 'phone', 'location'];
        $filled = 0;

        foreach ($fields as $field) {
            if (filled($data[$field] ?? null)) {
                $filled++;
            }
        }

        $score += min(60, (int) round(($filled / count($fields)) * 60));

        // Source reliability bonus
        if (in_array('apollo', $sources)) {
            $score += 20;
        }
        if (in_array('hunter', $sources)) {
            $score += 10;
        }
        if (in_array('rocketreach', $sources)) {
            $score += 10;
        }

        return min(100, $score);
    }

    private function extractDomain(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $url = trim($url);
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        return $host ?: null;
    }

    private function extractDomainFromEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return null;
        }

        return substr($email, strrpos($email, '@') + 1);
    }
}

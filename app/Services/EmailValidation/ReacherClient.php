<?php

namespace App\Services\EmailValidation;

use Illuminate\Support\Facades\Http;

/**
 * Client for the self-hosted Reacher verification service (emailverify app).
 *
 * Talks to the Express wrapper's POST /api/verify endpoint, which proxies a
 * self-hosted reacherhq/check-if-email-exists backend. Returns the same
 * verification array shape as SnapvalidClient::verify() so the chunk job can
 * treat providers interchangeably.
 */
class ReacherClient
{
    /**
     * @return array{success: bool, result: ?string, message: ?string, flags: ?array, raw: ?array}
     */
    public function verify(string $baseUrl, string $apiKey, string $email): array
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '') {
            return [
                'success' => false,
                'result' => null,
                'message' => 'Reacher endpoint URL is not configured.',
                'flags' => null,
                'raw' => null,
            ];
        }

        try {
            $request = Http::timeout((int) config('mailpurse.email_validation.reacher_timeout_seconds', 35))
                ->acceptJson()
                ->asJson();

            if ($apiKey !== '') {
                $request = $request->withHeaders(['x-api-key' => $apiKey]);
            }

            $response = $request->post($baseUrl . '/api/verify', [
                'emails' => [trim($email)],
                'smtp_port' => 25,
                'check_gravatar' => true,
            ]);

            if (!$response->successful()) {
                $json = $response->json();
                $apiMessage = is_array($json) && is_string($json['error'] ?? null) ? $json['error'] : null;

                return [
                    'success' => false,
                    'result' => null,
                    'message' => $apiMessage
                        ? "Reacher request failed ({$response->status()}): {$apiMessage}"
                        : "Reacher request failed ({$response->status()}).",
                    'flags' => null,
                    'raw' => [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ],
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'result' => null,
                    'message' => 'Reacher returned invalid JSON.',
                    'flags' => null,
                    'raw' => ['body' => $response->body()],
                ];
            }

            $result = $this->pickResult($data, $email);

            if ($result === null) {
                $rejected = $data['rejected'] ?? null;
                $message = is_array($rejected) && is_string($rejected[0]['error'] ?? null)
                    ? (string) $rejected[0]['error']
                    : 'Reacher returned no result for email.';

                return [
                    'success' => false,
                    'result' => is_array($rejected) ? 'undeliverable' : null,
                    'message' => $message,
                    'flags' => null,
                    'raw' => $data,
                ];
            }

            return $this->mapOutput($result, $data);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $isTimeout = is_string($message)
                && (str_contains($message, 'cURL error 28') || str_contains($message, 'timed out'));

            return [
                'success' => false,
                'result' => $isTimeout ? 'unknown' : null,
                'message' => $isTimeout ? "Reacher request timed out: {$message}" : $message,
                'flags' => null,
                'raw' => ['exception' => $message],
            ];
        }
    }

    private function pickResult(array $data, string $email): ?array
    {
        $results = $data['results'] ?? null;
        if (!is_array($results)) {
            return null;
        }

        $email = strtolower(trim($email));
        foreach ($results as $item) {
            if (!is_array($item)) {
                continue;
            }
            $input = strtolower(trim((string) ($item['input'] ?? '')));
            if ($input === $email) {
                return $item;
            }
        }

        return is_array($results[0] ?? null) ? $results[0] : null;
    }

    /**
     * Maps a Reacher CheckEmailOutput to the normalized verification shape.
     * is_reachable: safe -> deliverable, invalid -> undeliverable,
     * risky -> accept_all, unknown -> unknown.
     */
    private function mapOutput(array $output, array $envelope): array
    {
        $isReachable = is_string($output['is_reachable'] ?? null)
            ? strtolower(trim($output['is_reachable']))
            : null;

        $result = match ($isReachable) {
            'safe' => 'deliverable',
            'invalid' => 'undeliverable',
            'risky' => 'accept_all',
            'unknown' => 'unknown',
            default => 'unknown',
        };

        $mx = is_array($output['mx'] ?? null) ? $output['mx'] : [];
        $smtp = is_array($output['smtp'] ?? null) ? $output['smtp'] : [];
        $syntax = is_array($output['syntax'] ?? null) ? $output['syntax'] : [];
        $misc = is_array($output['misc'] ?? null) ? $output['misc'] : [];

        $message = $this->firstErrorMessage([$smtp, $mx, $syntax, $misc])
            ?? (is_string($output['error'] ?? null) ? $output['error'] : null);

        if (($result === 'deliverable' || $result === 'accept_all') && ($smtp['is_catch_all'] ?? false) === true) {
            $result = 'accept_all';
        }

        if (array_key_exists('is_deliverable', $smtp) && $smtp['is_deliverable'] === false && $result === 'deliverable') {
            $result = 'undeliverable';
        }

        if (($mx['accepts_mail'] ?? true) === false && $result === 'deliverable') {
            $result = 'undeliverable';
        }

        $flags = array_filter([
            'disposable' => ($misc['is_disposable'] ?? null) === true ? true : null,
            'role' => ($misc['is_role_account'] ?? null) === true ? true : null,
            'accept_all' => ($smtp['is_catch_all'] ?? null) === true ? true : null,
            'inbox_full' => ($smtp['has_full_inbox'] ?? null) === true ? true : null,
            'disabled' => ($smtp['is_disabled'] ?? null) === true ? true : null,
            'gravatar_url' => is_string($misc['gravatar_url'] ?? null) ? $misc['gravatar_url'] : null,
            'haveibeenpwned' => is_bool($misc['haveibeenpwned'] ?? null) ? $misc['haveibeenpwned'] : null,
        ], fn ($v) => $v !== null);

        return [
            'success' => true,
            'result' => $result,
            'message' => $message,
            'flags' => $flags === [] ? null : $flags,
            'raw' => $output,
        ];
    }

    private function firstErrorMessage(array $sections): ?string
    {
        foreach ($sections as $section) {
            $error = $section['error'] ?? null;
            if (is_string($error) && $error !== '') {
                return $error;
            }
            if (is_array($error) && is_string($error['message'] ?? null)) {
                return $error['message'];
            }
        }

        return null;
    }
}

<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $refreshToken;

    public function __construct(string $clientId, string $clientSecret, string $refreshToken)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;

        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $accessToken = $this->getAccessToken();

        // Use Symfony's own serialization so the full MIME tree is preserved
        // (HTML body, embedded images, attachments, tracking pixels, all headers).
        $rawMessage = $message->toString();
        $encoded = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $ch = curl_init('https://gmail.googleapis.com/gmail/v1/users/me/messages/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['raw' => $encoded]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new TransportException('Gmail API request failed: ' . $curlError);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $body = json_decode($response, true);
            $errMsg = $body['error']['message'] ?? $response;
            throw new TransportException(sprintf('Gmail API returned HTTP %d: %s', $statusCode, $errMsg));
        }
    }

    private function getAccessToken(): string
    {
        // Cache the token for its lifetime so campaigns don't hit the token
        // endpoint once per email.
        $cacheKey = 'gmail_api_access_token_' . md5((string) $this->refreshToken);
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new TransportException('Gmail OAuth token request failed: ' . $curlError);
        }

        $data = json_decode($response, true);

        if (empty($data['access_token'])) {
            $errMsg = $data['error_description'] ?? $data['error'] ?? $response;
            throw new TransportException('Gmail OAuth token error: ' . $errMsg);
        }

        $token = (string) $data['access_token'];
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        Cache::put($cacheKey, $token, max(60, $expiresIn - 120));

        return $token;
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}

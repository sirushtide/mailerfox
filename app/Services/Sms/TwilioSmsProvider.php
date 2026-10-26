<?php

namespace App\Services\Sms;

use App\Models\ChannelAccount;
use App\Services\Channels\ChannelResult;
use App\Services\Sms\Contracts\SmsProvider;
use Illuminate\Support\Facades\Http;

class TwilioSmsProvider implements SmsProvider
{
    public function key(): string
    {
        return 'twilio';
    }

    public function send(ChannelAccount $account, string $to, string $body, array $media = []): ChannelResult
    {
        $sid = trim((string) $account->credential('account_sid', ''));
        $token = trim((string) $account->credential('auth_token', ''));
        $from = trim((string) ($account->from_address ?? $account->credential('from', '')));
        $messagingServiceSid = trim((string) $account->credential('messaging_service_sid', ''));

        if ($sid === '' || $token === '') {
            return ChannelResult::fail('Twilio account SID or auth token is missing.');
        }

        $payload = [
            'To'   => $to,
            'Body' => (string) $body,
        ];

        if ($messagingServiceSid !== '') {
            $payload['MessagingServiceSid'] = $messagingServiceSid;
        } elseif ($from !== '') {
            $payload['From'] = $from;
        } else {
            return ChannelResult::fail('No Twilio sender configured (from number or messaging service SID).');
        }

        foreach (array_values($media) as $index => $url) {
            if (is_string($url) && $url !== '') {
                $payload['MediaUrl' . ($index === 0 ? '' : $index)] = $url;
            }
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->timeout(30)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", $payload);
        } catch (\Throwable $e) {
            return ChannelResult::fail('Twilio request error: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            return ChannelResult::fail(
                'Twilio API error (' . $response->status() . '): ' . $response->body(),
                ['body' => $response->json() ?? $response->body()]
            );
        }

        $data = $response->json() ?? [];

        return ChannelResult::ok(
            $data['sid'] ?? null,
            $this->mapStatus($data['status'] ?? 'queued'),
            ['twilio' => $data]
        );
    }

    public function verifyWebhook(ChannelAccount $account, string $url, array $params, ?string $signature): bool
    {
        if (!is_string($signature) || $signature === '') {
            return false;
        }

        $token = trim((string) $account->credential('auth_token', ''));
        if ($token === '') {
            return false;
        }

        ksort($params);
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . (is_array($value) ? implode('', $value) : $value);
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $token, true));

        return hash_equals($expected, $signature);
    }

    protected function mapStatus(string $twilioStatus): string
    {
        return match ($twilioStatus) {
            'sent'                         => 'sent',
            'delivered'                    => 'delivered',
            'read'                         => 'read',
            'failed', 'undelivered'        => 'failed',
            default                        => 'queued',
        };
    }
}

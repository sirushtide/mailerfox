<?php

namespace App\Services\WhatsApp;

use App\Models\ChannelAccount;
use App\Services\Channels\ChannelResult;
use App\Services\Channels\Contracts\ChannelProvider;
use App\Services\Channels\OutboundMessage;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Meta WhatsApp Business Cloud API client.
 * Registered with ChannelManager under the "whatsapp" channel.
 */
class WhatsAppCloudClient implements ChannelProvider
{
    public function channel(): string
    {
        return 'whatsapp';
    }

    public function send(OutboundMessage $message): ChannelResult
    {
        $account = $message->account;
        if (!$account instanceof ChannelAccount) {
            throw new InvalidArgumentException('WhatsApp send requires a channel account.');
        }

        if ($message->templateName) {
            return $this->sendTemplate(
                $account,
                $message->to,
                $message->templateName,
                $message->templateParams,
                $message->meta['language'] ?? 'en_US'
            );
        }

        if (!empty($message->media)) {
            return $this->sendMedia($account, $message->to, $message->body, $message->media[0]);
        }

        return $this->sendText($account, $message->to, (string) ($message->body ?? ''));
    }

    public function sendText(ChannelAccount $account, string $to, string $body): ChannelResult
    {
        return $this->dispatch($account, [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $body,
            ],
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $params Positional/body parameters.
     */
    public function sendTemplate(ChannelAccount $account, string $to, string $template, array $params = [], string $language = 'en_US'): ChannelResult
    {
        $components = [];
        if ($params !== []) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(static function ($value) {
                    return is_array($value) && isset($value['type'])
                        ? $value
                        : ['type' => 'text', 'text' => (string) $value];
                }, $params),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'     => $template,
                'language' => ['code' => $language],
            ],
        ];

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        return $this->dispatch($account, $payload);
    }

    public function sendMedia(ChannelAccount $account, string $to, ?string $caption, array $media): ChannelResult
    {
        $type = $media['type'] ?? 'image';
        $link = $media['url'] ?? $media['link'] ?? '';

        if (!$link) {
            return ChannelResult::fail('WhatsApp media requires a URL.');
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => $type,
            $type               => array_filter([
                'link'    => $link,
                'caption' => $caption,
            ]),
        ];

        return $this->dispatch($account, $payload);
    }

    protected function dispatch(ChannelAccount $account, array $payload): ChannelResult
    {
        $phoneNumberId = trim((string) ($account->from_address ?: $account->credential('phone_number_id', '')));
        $accessToken = trim((string) $account->credential('access_token', ''));
        $version = trim((string) $account->credential('api_version', 'v21.0')) ?: 'v21.0';

        if ($phoneNumberId === '' || $accessToken === '') {
            return ChannelResult::fail('WhatsApp phone number ID or access token is missing.');
        }

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(30)
                ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", $payload);
        } catch (\Throwable $e) {
            return ChannelResult::fail('WhatsApp request error: ' . $e->getMessage());
        }

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            $error = $data['error']['message'] ?? $response->body();

            return ChannelResult::fail('WhatsApp API error (' . $response->status() . '): ' . $error, ['body' => $data]);
        }

        return ChannelResult::ok(
            $data['messages'][0]['id'] ?? null,
            'sent',
            ['meta' => $data]
        );
    }

    public function verifyWebhookSignature(ChannelAccount $account, string $rawBody, ?string $signature): bool
    {
        if (!is_string($signature) || $signature === '') {
            return false;
        }

        $appSecret = trim((string) $account->credential('app_secret', ''));
        if ($appSecret === '') {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $signature);
    }
}

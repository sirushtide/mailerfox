<?php

namespace App\Services\WhatsApp;

use App\Models\ChannelAccount;
use Illuminate\Support\Facades\Http;

class WhatsAppTemplateService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(ChannelAccount $account): array
    {
        $wabaId = trim((string) ($account->external_id ?: $account->credential('waba_id', '')));
        $token = trim((string) $account->credential('access_token', ''));
        $version = trim((string) $account->credential('api_version', 'v21.0')) ?: 'v21.0';

        if ($wabaId === '' || $token === '') {
            return [];
        }

        $response = Http::withToken($token)
            ->timeout(30)
            ->get("https://graph.facebook.com/{$version}/{$wabaId}/message_templates", [
                'limit'  => 200,
                'fields' => 'id,name,status,category,language,components',
            ]);

        if (!$response->successful()) {
            return [];
        }

        return $response->json('data') ?? [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(ChannelAccount $account, array $data): array
    {
        $wabaId = trim((string) ($account->external_id ?: $account->credential('waba_id', '')));
        $token = trim((string) $account->credential('access_token', ''));
        $version = trim((string) $account->credential('api_version', 'v21.0')) ?: 'v21.0';

        $response = Http::withToken($token)
            ->timeout(30)
            ->post("https://graph.facebook.com/{$version}/{$wabaId}/message_templates", $data);

        return [
            'success' => $response->successful(),
            'data'    => $response->json() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(ChannelAccount $account, string $name): array
    {
        $wabaId = trim((string) ($account->external_id ?: $account->credential('waba_id', '')));
        $token = trim((string) $account->credential('access_token', ''));
        $version = trim((string) $account->credential('api_version', 'v21.0')) ?: 'v21.0';

        $response = Http::withToken($token)
            ->timeout(30)
            ->delete("https://graph.facebook.com/{$version}/{$wabaId}/message_templates", [
                'name' => $name,
            ]);

        return [
            'success' => $response->successful(),
            'data'    => $response->json() ?? [],
        ];
    }
}

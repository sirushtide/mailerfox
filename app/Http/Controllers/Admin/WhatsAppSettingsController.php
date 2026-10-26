<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.addons.whatsapp-settings', [
            'platformEnabled' => filter_var(Setting::get('whatsapp_platform_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'phoneNumberId'   => (string) Setting::get('whatsapp_phone_number_id', ''),
            'wabaId'          => (string) Setting::get('whatsapp_waba_id', ''),
            'accessToken'     => (string) Setting::get('whatsapp_access_token', ''),
            'appSecret'       => (string) Setting::get('whatsapp_app_secret', ''),
            'verifyToken'     => (string) Setting::get('whatsapp_verify_token', ''),
            'apiVersion'      => (string) (Setting::get('whatsapp_api_version', 'v21.0') ?: 'v21.0'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_platform_enabled' => ['nullable', 'boolean'],
            'whatsapp_phone_number_id'  => ['nullable', 'string', 'max:191'],
            'whatsapp_waba_id'          => ['nullable', 'string', 'max:191'],
            'whatsapp_access_token'     => ['nullable', 'string', 'max:1000'],
            'whatsapp_app_secret'       => ['nullable', 'string', 'max:191'],
            'whatsapp_verify_token'     => ['nullable', 'string', 'max:191'],
            'whatsapp_api_version'      => ['nullable', 'string', 'max:16'],
        ]);

        Setting::set('whatsapp_platform_enabled', $request->boolean('whatsapp_platform_enabled') ? '1' : '0', 'addon', 'boolean');
        foreach ([
            'whatsapp_phone_number_id',
            'whatsapp_waba_id',
            'whatsapp_access_token',
            'whatsapp_app_secret',
            'whatsapp_verify_token',
            'whatsapp_api_version',
        ] as $key) {
            Setting::set($key, trim((string) ($validated[$key] ?? '')), 'addon', 'string');
        }

        $this->syncPlatformAccount();

        return redirect()->route('admin.whatsapp.settings')->with('success', __('WhatsApp settings saved.'));
    }

    protected function syncPlatformAccount(): void
    {
        $phoneNumberId = trim((string) Setting::get('whatsapp_phone_number_id', ''));
        $accessToken = trim((string) Setting::get('whatsapp_access_token', ''));
        $wabaId = trim((string) Setting::get('whatsapp_waba_id', ''));
        $appSecret = trim((string) Setting::get('whatsapp_app_secret', ''));
        $apiVersion = trim((string) Setting::get('whatsapp_api_version', 'v21.0'));

        if ($phoneNumberId === '' || $accessToken === '') {
            return;
        }

        ChannelAccount::updateOrCreate(
            [
                'customer_id' => null,
                'channel'     => 'whatsapp',
                'provider'    => 'meta',
                'is_platform' => true,
            ],
            [
                'name'         => 'Platform WhatsApp',
                'status'       => 'active',
                'from_address' => $phoneNumberId,
                'external_id'  => $wabaId ?: null,
                'credentials'  => [
                    'phone_number_id' => $phoneNumberId,
                    'waba_id'         => $wabaId,
                    'access_token'    => $accessToken,
                    'app_secret'      => $appSecret,
                    'api_version'     => $apiVersion ?: 'v21.0',
                ],
            ]
        );
    }
}

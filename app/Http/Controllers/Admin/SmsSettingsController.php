<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.addons.sms-settings', [
            'platformEnabled' => filter_var(Setting::get('sms_platform_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'accountSid'      => (string) Setting::get('sms_twilio_account_sid', ''),
            'authToken'       => (string) Setting::get('sms_twilio_auth_token', ''),
            'fromAddress'     => (string) Setting::get('sms_twilio_from', ''),
            'messagingSid'    => (string) Setting::get('sms_twilio_messaging_service_sid', ''),
            'pricePerSegment' => (string) Setting::get('sms_price_per_segment', '0'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sms_platform_enabled'              => ['nullable', 'boolean'],
            'sms_twilio_account_sid'            => ['nullable', 'string', 'max:191'],
            'sms_twilio_auth_token'             => ['nullable', 'string', 'max:191'],
            'sms_twilio_from'                   => ['nullable', 'string', 'max:64'],
            'sms_twilio_messaging_service_sid'  => ['nullable', 'string', 'max:191'],
            'sms_price_per_segment'             => ['nullable', 'numeric', 'min:0'],
        ]);

        Setting::set('sms_platform_enabled', $request->boolean('sms_platform_enabled') ? '1' : '0', 'addon', 'boolean');
        Setting::set('sms_twilio_account_sid', trim((string) ($validated['sms_twilio_account_sid'] ?? '')), 'addon', 'string');
        Setting::set('sms_twilio_auth_token', trim((string) ($validated['sms_twilio_auth_token'] ?? '')), 'addon', 'string');
        Setting::set('sms_twilio_from', trim((string) ($validated['sms_twilio_from'] ?? '')), 'addon', 'string');
        Setting::set('sms_twilio_messaging_service_sid', trim((string) ($validated['sms_twilio_messaging_service_sid'] ?? '')), 'addon', 'string');
        Setting::set('sms_price_per_segment', (string) ($validated['sms_price_per_segment'] ?? '0'), 'addon', 'string');

        $this->syncPlatformAccount();

        return redirect()->route('admin.sms.settings')->with('success', __('SMS settings saved.'));
    }

    protected function syncPlatformAccount(): void
    {
        $sid = trim((string) Setting::get('sms_twilio_account_sid', ''));
        $token = trim((string) Setting::get('sms_twilio_auth_token', ''));
        $from = trim((string) Setting::get('sms_twilio_from', ''));
        $messagingSid = trim((string) Setting::get('sms_twilio_messaging_service_sid', ''));

        if ($sid === '' || $token === '') {
            return;
        }

        ChannelAccount::updateOrCreate(
            [
                'customer_id' => null,
                'channel'     => 'sms',
                'provider'    => 'twilio',
                'is_platform' => true,
            ],
            [
                'name'         => 'Platform Twilio',
                'status'       => 'active',
                'from_address' => $from ?: null,
                'external_id'  => $messagingSid ?: null,
                'credentials'  => [
                    'account_sid'           => $sid,
                    'auth_token'            => $token,
                    'from'                  => $from,
                    'messaging_service_sid' => $messagingSid,
                ],
            ]
        );
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\ChannelAccount;
use App\Models\Setting;
use App\Services\Sms\SmsSendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsAccountController extends Controller
{
    protected function guard(): void
    {
        abort_unless(Addon::isActive('sms'), 404);
    }

    protected function customerId(): int
    {
        return (int) auth('customer')->id();
    }

    public function index(): View
    {
        $this->guard();

        $accounts = ChannelAccount::forChannel('sms')
            ->accessibleBy($this->customerId())
            ->orderBy('name')
            ->get();

        $platformEnabled = filter_var(Setting::get('sms_platform_enabled', true), FILTER_VALIDATE_BOOLEAN);

        return view('customer.sms.accounts', compact('accounts', 'platformEnabled'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();

        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:120'],
            'provider'             => ['required', 'in:twilio'],
            'mode'                 => ['required', 'in:byo,platform'],
            'from_address'         => ['nullable', 'string', 'max:64'],
            'account_sid'          => ['nullable', 'string', 'max:191'],
            'auth_token'           => ['nullable', 'string', 'max:191'],
            'messaging_service_sid' => ['nullable', 'string', 'max:191'],
        ]);

        if ($validated['mode'] === 'platform') {
            $platform = ChannelAccount::forChannel('sms')
                ->where('is_platform', true)
                ->whereNull('customer_id')
                ->where('provider', $validated['provider'])
                ->active()
                ->first();

            if (!$platform) {
                return back()->with('error', __('No platform SMS account is available. Please connect your own Twilio account.'));
            }

            ChannelAccount::create([
                'customer_id'    => $this->customerId(),
                'channel'        => 'sms',
                'provider'       => $validated['provider'],
                'name'           => $validated['name'],
                'status'         => 'active',
                'is_platform'    => false,
                'from_address'   => $validated['from_address'] ?: $platform->from_address,
                'external_id'    => $platform->external_id,
                'credentials'    => $platform->credentials,
                'settings'       => ['uses_platform_account' => $platform->id],
            ]);

            return back()->with('success', __('SMS account connected using the platform provider.'));
        }

        ChannelAccount::create([
            'customer_id'  => $this->customerId(),
            'channel'      => 'sms',
            'provider'     => $validated['provider'],
            'name'         => $validated['name'],
            'status'       => 'active',
            'is_platform'  => false,
            'from_address' => $validated['from_address'] ?? null,
            'external_id'  => $validated['messaging_service_sid'] ?? null,
            'credentials'  => [
                'account_sid'           => trim((string) ($validated['account_sid'] ?? '')),
                'auth_token'            => trim((string) ($validated['auth_token'] ?? '')),
                'messaging_service_sid' => trim((string) ($validated['messaging_service_sid'] ?? '')),
                'from'                  => trim((string) ($validated['from_address'] ?? '')),
            ],
        ]);

        return back()->with('success', __('SMS account added successfully.'));
    }

    public function destroy(ChannelAccount $smsAccount): RedirectResponse
    {
        $this->guard();

        abort_unless(
            $smsAccount->channel === 'sms' && (int) $smsAccount->customer_id === $this->customerId(),
            403
        );

        $smsAccount->delete();

        return back()->with('success', __('SMS account removed.'));
    }

    public function test(Request $request, ChannelAccount $smsAccount, SmsSendService $sender): RedirectResponse
    {
        $this->guard();

        abort_unless($smsAccount->channel === 'sms' && (int) $smsAccount->customer_id === $this->customerId(), 403);

        $validated = $request->validate([
            'to'   => ['required', 'string', 'max:32'],
            'body' => ['required', 'string', 'max:480'],
        ]);

        $message = $sender->send($smsAccount, $validated['to'], $validated['body'], [], [
            'customer_id' => $this->customerId(),
        ]);

        return $message->status === 'failed'
            ? back()->with('error', __('Test SMS failed: ') . ($message->error ?? ''))
            : back()->with('success', __('Test SMS sent.'));
    }
}

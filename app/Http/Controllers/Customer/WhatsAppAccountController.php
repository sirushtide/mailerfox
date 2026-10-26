<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\ChannelAccount;
use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppSendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppAccountController extends Controller
{
    protected function guard(): void
    {
        abort_unless(Addon::isActive('whatsapp'), 404);
    }

    protected function customerId(): int
    {
        return (int) auth('customer')->id();
    }

    public function index(): View
    {
        $this->guard();

        $accounts = ChannelAccount::forChannel('whatsapp')
            ->accessibleBy($this->customerId())
            ->orderBy('name')
            ->get();

        $platformEnabled = filter_var(Setting::get('whatsapp_platform_enabled', true), FILTER_VALIDATE_BOOLEAN);

        return view('customer.whatsapp.accounts', compact('accounts', 'platformEnabled'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:120'],
            'mode'            => ['required', 'in:byo,platform'],
            'phone_number_id' => ['nullable', 'string', 'max:191'],
            'waba_id'         => ['nullable', 'string', 'max:191'],
            'access_token'    => ['nullable', 'string', 'max:1000'],
            'app_secret'      => ['nullable', 'string', 'max:191'],
            'api_version'     => ['nullable', 'string', 'max:16'],
        ]);

        if ($validated['mode'] === 'platform') {
            $platform = ChannelAccount::forChannel('whatsapp')
                ->where('is_platform', true)
                ->whereNull('customer_id')
                ->active()
                ->first();

            if (!$platform) {
                return back()->with('error', __('No platform WhatsApp account is available. Please connect your own WhatsApp Business account.'));
            }

            ChannelAccount::create([
                'customer_id'  => $this->customerId(),
                'channel'      => 'whatsapp',
                'provider'     => 'meta',
                'name'         => $validated['name'],
                'status'       => 'active',
                'is_platform'  => false,
                'from_address' => $platform->from_address,
                'external_id'  => $platform->external_id,
                'credentials'  => $platform->credentials,
                'settings'     => ['uses_platform_account' => $platform->id],
            ]);

            return back()->with('success', __('WhatsApp account connected using the platform provider.'));
        }

        ChannelAccount::create([
            'customer_id'  => $this->customerId(),
            'channel'      => 'whatsapp',
            'provider'     => 'meta',
            'name'         => $validated['name'],
            'status'       => 'active',
            'is_platform'  => false,
            'from_address' => $validated['phone_number_id'] ?? null,
            'external_id'  => $validated['waba_id'] ?? null,
            'credentials'  => [
                'phone_number_id' => trim((string) ($validated['phone_number_id'] ?? '')),
                'waba_id'         => trim((string) ($validated['waba_id'] ?? '')),
                'access_token'    => trim((string) ($validated['access_token'] ?? '')),
                'app_secret'      => trim((string) ($validated['app_secret'] ?? '')),
                'api_version'     => trim((string) ($validated['api_version'] ?? 'v21.0')),
            ],
        ]);

        return back()->with('success', __('WhatsApp account added successfully.'));
    }

    public function destroy(ChannelAccount $whatsappAccount): RedirectResponse
    {
        $this->guard();

        abort_unless(
            $whatsappAccount->channel === 'whatsapp' && (int) $whatsappAccount->customer_id === $this->customerId(),
            403
        );

        $whatsappAccount->delete();

        return back()->with('success', __('WhatsApp account removed.'));
    }

    public function test(Request $request, ChannelAccount $whatsappAccount, WhatsAppSendService $sender): RedirectResponse
    {
        $this->guard();

        abort_unless(
            $whatsappAccount->channel === 'whatsapp' && (int) $whatsappAccount->customer_id === $this->customerId(),
            403
        );

        $validated = $request->validate([
            'to'   => ['required', 'string', 'max:32'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $message = $sender->send($whatsappAccount, $validated['to'], $validated['body'], [], [
            'customer_id' => $this->customerId(),
        ]);

        return $message->status === 'failed'
            ? back()->with('error', __('Test message failed: ') . ($message->error ?? ''))
            : back()->with('success', __('Test WhatsApp message sent.'));
    }
}

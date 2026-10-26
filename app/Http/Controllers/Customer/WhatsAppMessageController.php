<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\ChannelAccount;
use App\Models\ChannelThread;
use App\Services\WhatsApp\WhatsAppSendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppMessageController extends Controller
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

        $threads = ChannelThread::forChannel('whatsapp')
            ->where('customer_id', $this->customerId())
            ->with(['contact', 'account'])
            ->orderByDesc('last_message_at')
            ->paginate(25);

        return view('customer.whatsapp.messages.index', compact('threads'));
    }

    public function show(ChannelThread $whatsappThread): View
    {
        $this->guard();

        abort_unless(
            $whatsappThread->channel === 'whatsapp' && (int) $whatsappThread->customer_id === $this->customerId(),
            404
        );

        $whatsappThread->update(['unread_count' => 0]);

        return view('customer.whatsapp.messages.show', [
            'thread'   => $whatsappThread,
            'messages' => $whatsappThread->messages()->get(),
        ]);
    }

    public function reply(Request $request, ChannelThread $whatsappThread, WhatsAppSendService $sender): RedirectResponse
    {
        $this->guard();

        abort_unless(
            $whatsappThread->channel === 'whatsapp' && (int) $whatsappThread->customer_id === $this->customerId(),
            404
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $account = $whatsappThread->account;
        if (!$account || $account->channel !== 'whatsapp') {
            $account = ChannelAccount::forChannel('whatsapp')->accessibleBy($this->customerId())->active()->first();
        }

        if (!$account) {
            return back()->with('error', __('No active WhatsApp account available to send from.'));
        }

        $to = (string) ($whatsappThread->contact?->address ?? '');
        if ($to === '') {
            return back()->with('error', __('This conversation has no recipient.'));
        }

        $message = $sender->send($account, $to, $validated['body'], [], [
            'customer_id' => $this->customerId(),
            'thread_id'   => $whatsappThread->id,
        ]);

        return $message->status === 'failed'
            ? back()->with('error', __('Reply failed: ') . ($message->error ?? ''))
            : back()->with('success', __('Reply sent.'));
    }
}

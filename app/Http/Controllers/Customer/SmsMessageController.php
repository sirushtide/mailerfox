<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\ChannelAccount;
use App\Models\ChannelThread;
use App\Services\Sms\SmsSendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsMessageController extends Controller
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

        $threads = ChannelThread::forChannel('sms')
            ->where('customer_id', $this->customerId())
            ->with(['contact', 'account'])
            ->orderByDesc('last_message_at')
            ->paginate(25);

        return view('customer.sms.messages.index', compact('threads'));
    }

    public function show(ChannelThread $smsThread): View
    {
        $this->guard();

        abort_unless(
            $smsThread->channel === 'sms' && (int) $smsThread->customer_id === $this->customerId(),
            404
        );

        $smsThread->update(['unread_count' => 0]);

        $messages = $smsThread->messages()->get();

        return view('customer.sms.messages.show', [
            'thread'   => $smsThread,
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request, ChannelThread $smsThread, SmsSendService $sender): RedirectResponse
    {
        $this->guard();

        abort_unless(
            $smsThread->channel === 'sms' && (int) $smsThread->customer_id === $this->customerId(),
            404
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1600'],
        ]);

        $account = $smsThread->account;
        if (!$account || $account->channel !== 'sms') {
            $account = ChannelAccount::forChannel('sms')->accessibleBy($this->customerId())->active()->first();
        }

        if (!$account) {
            return back()->with('error', __('No active SMS account available to send from.'));
        }

        $to = (string) ($smsThread->contact?->address ?? '');
        if ($to === '') {
            return back()->with('error', __('This conversation has no recipient.'));
        }

        $message = $sender->send($account, $to, $validated['body'], [], [
            'customer_id' => $this->customerId(),
            'thread_id'   => $smsThread->id,
        ]);

        if ($message->status === 'failed') {
            return back()->with('error', __('Reply failed: ') . ($message->error ?? ''));
        }

        return back()->with('success', __('Reply sent.'));
    }
}

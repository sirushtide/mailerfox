<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsCampaignJob;
use App\Models\Addon;
use App\Models\Campaign;
use App\Models\ChannelAccount;
use App\Models\EmailList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsCampaignController extends Controller
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

        $campaigns = Campaign::where('customer_id', $this->customerId())
            ->where('channel', 'sms')
            ->latest()
            ->paginate(20);

        return view('customer.sms.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->guard();

        $lists = EmailList::where('customer_id', $this->customerId())->orderBy('name')->get();
        $accounts = ChannelAccount::forChannel('sms')
            ->accessibleBy($this->customerId())
            ->active()
            ->orderBy('name')
            ->get();

        return view('customer.sms.campaigns.create', compact('lists', 'accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:191'],
            'list_id'           => ['required', 'integer'],
            'channel_account_id' => ['required', 'integer'],
            'body'              => ['required', 'string', 'max:2000'],
            'scheduled_at'      => ['nullable', 'date'],
        ]);

        $list = EmailList::where('customer_id', $this->customerId())->findOrFail($validated['list_id']);

        $account = ChannelAccount::forChannel('sms')
            ->accessibleBy($this->customerId())
            ->findOrFail($validated['channel_account_id']);

        $campaign = Campaign::create([
            'customer_id'        => $this->customerId(),
            'list_id'            => $list->id,
            'name'               => $validated['name'],
            'subject'            => $validated['name'],
            'type'               => 'regular',
            'channel'            => 'sms',
            'channel_account_id' => $account->id,
            'status'             => $request->filled('scheduled_at') ? 'scheduled' : 'draft',
            'scheduled_at'       => $validated['scheduled_at'] ?? null,
            'html_content'       => $validated['body'],
            'plain_text_content' => $validated['body'],
        ]);

        return redirect()
            ->route('customer.sms.campaigns.show', $campaign)
            ->with('success', __('SMS campaign created.'));
    }

    public function show(Campaign $smsCampaign): View
    {
        $this->guard();

        abort_unless(
            $smsCampaign->channel === 'sms' && (int) $smsCampaign->customer_id === $this->customerId(),
            404
        );

        return view('customer.sms.campaigns.show', ['campaign' => $smsCampaign]);
    }

    public function send(Campaign $smsCampaign): RedirectResponse
    {
        $this->guard();

        abort_unless(
            $smsCampaign->channel === 'sms' && (int) $smsCampaign->customer_id === $this->customerId(),
            404
        );

        if (!in_array($smsCampaign->status, ['draft', 'scheduled', 'paused', 'failed'], true)) {
            return back()->with('error', __('This campaign cannot be started in its current state.'));
        }

        $smsCampaign->update([
            'status'     => 'queued',
            'send_at'    => now(),
        ]);

        SendSmsCampaignJob::dispatch($smsCampaign->id);

        return back()->with('success', __('SMS campaign queued for sending.'));
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppCampaignJob;
use App\Models\Addon;
use App\Models\Campaign;
use App\Models\ChannelAccount;
use App\Models\EmailList;
use App\Services\WhatsApp\WhatsAppTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppCampaignController extends Controller
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

        $campaigns = Campaign::where('customer_id', $this->customerId())
            ->where('channel', 'whatsapp')
            ->latest()
            ->paginate(20);

        return view('customer.whatsapp.campaigns.index', compact('campaigns'));
    }

    public function create(WhatsAppTemplateService $templates): View
    {
        $this->guard();

        $lists = EmailList::where('customer_id', $this->customerId())->orderBy('name')->get();
        $accounts = ChannelAccount::forChannel('whatsapp')
            ->accessibleBy($this->customerId())
            ->active()
            ->orderBy('name')
            ->get();

        $account = $accounts->first();
        $waTemplates = $account ? $templates->list($account) : [];

        return view('customer.whatsapp.campaigns.create', compact('lists', 'accounts', 'waTemplates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:191'],
            'list_id'            => ['required', 'integer'],
            'channel_account_id' => ['required', 'integer'],
            'mode'               => ['required', 'in:text,template'],
            'body'               => ['nullable', 'string', 'max:4000'],
            'template'           => ['nullable', 'string', 'max:191'],
            'language'           => ['nullable', 'string', 'max:16'],
            'scheduled_at'       => ['nullable', 'date'],
        ]);

        $list = EmailList::where('customer_id', $this->customerId())->findOrFail($validated['list_id']);
        $account = ChannelAccount::forChannel('whatsapp')
            ->accessibleBy($this->customerId())
            ->findOrFail($validated['channel_account_id']);

        $campaign = Campaign::create([
            'customer_id'        => $this->customerId(),
            'list_id'            => $list->id,
            'name'               => $validated['name'],
            'subject'            => $validated['name'],
            'type'               => 'regular',
            'channel'            => 'whatsapp',
            'channel_account_id' => $account->id,
            'channel_config'     => [
                'mode'     => $validated['mode'],
                'template' => $validated['template'] ?? null,
                'language' => $validated['language'] ?? 'en_US',
            ],
            'status'             => $request->filled('scheduled_at') ? 'scheduled' : 'draft',
            'scheduled_at'       => $validated['scheduled_at'] ?? null,
            'html_content'       => $validated['body'] ?? '',
            'plain_text_content' => $validated['body'] ?? '',
        ]);

        return redirect()
            ->route('customer.whatsapp.campaigns.show', $campaign)
            ->with('success', __('WhatsApp campaign created.'));
    }

    public function show(Campaign $whatsappCampaign): View
    {
        $this->guard();

        abort_unless(
            $whatsappCampaign->channel === 'whatsapp' && (int) $whatsappCampaign->customer_id === $this->customerId(),
            404
        );

        return view('customer.whatsapp.campaigns.show', ['campaign' => $whatsappCampaign]);
    }

    public function send(Campaign $whatsappCampaign): RedirectResponse
    {
        $this->guard();

        abort_unless(
            $whatsappCampaign->channel === 'whatsapp' && (int) $whatsappCampaign->customer_id === $this->customerId(),
            404
        );

        if (!in_array($whatsappCampaign->status, ['draft', 'scheduled', 'paused', 'failed'], true)) {
            return back()->with('error', __('This campaign cannot be started in its current state.'));
        }

        $whatsappCampaign->update(['status' => 'queued', 'send_at' => now()]);

        SendWhatsAppCampaignJob::dispatch($whatsappCampaign->id);

        return back()->with('success', __('WhatsApp campaign queued for sending.'));
    }
}

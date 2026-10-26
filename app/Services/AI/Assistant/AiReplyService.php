<?php

namespace App\Services\AI\Assistant;

use App\Models\AiAgent;
use App\Models\AiReplyDraft;
use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Models\Customer;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\OutboundMessage;
use Illuminate\Support\Facades\Log;

/**
 * Omnichannel reply assistant: drafts (and optionally auto-sends) replies to
 * inbound Email / SMS / WhatsApp messages using the customer's AI agent.
 */
class AiReplyService
{
    public function __construct(
        protected AiAssistantOrchestrator $orchestrator,
        protected ChannelManager $channels,
    ) {
    }

    public function resolveAgent(int $customerId, string $channel): ?AiAgent
    {
        $agents = AiAgent::forCustomer($customerId)->active()->get();

        return $agents->first(function (AiAgent $agent) use ($channel) {
            $channels = is_array($agent->channels) ? $agent->channels : [];

            return in_array($channel, $channels, true) || $agent->is_default;
        });
    }

    public function draft(ChannelMessage $message, ?ChannelAccount $account): ?AiReplyDraft
    {
        $customerId = (int) $message->customer_id;
        if ($customerId <= 0) {
            return null;
        }

        $agent = $this->resolveAgent($customerId, (string) $message->channel);
        if (!$agent) {
            return null;
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return null;
        }

        $history = $message->thread?->messages()
            ->where('id', '!=', $message->id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn (ChannelMessage $m) => [
                'role'    => $m->direction === 'in' ? 'user' : 'assistant',
                'content' => (string) ($m->body ?? ''),
            ])
            ->values()
            ->all() ?? [];

        $response = $this->orchestrator->respond(
            $customer,
            $agent->model,
            $history,
            'Draft a helpful reply to this inbound ' . $message->channel . ' message. Do not call any tools. Message: ' . (string) ($message->body ?? ''),
            ['channel' => $message->channel, 'thread_id' => $message->thread_id],
            $agent
        );

        if (!$response['success'] || trim($response['reply']) === '') {
            return null;
        }

        return AiReplyDraft::create([
            'customer_id' => $customerId,
            'agent_id'    => $agent->id,
            'channel'     => (string) $message->channel,
            'thread_id'   => $message->thread_id,
            'message_id'  => $message->id,
            'draft'       => $response['reply'],
            'status'      => 'pending',
        ]);
    }

    /**
     * Called from the "channel_inbound_message" hook.
     */
    public function handleInbound(ChannelMessage $message, ChannelAccount $account): void
    {
        try {
            $draft = $this->draft($message, $account);
            if (!$draft) {
                return;
            }

            $agent = $draft->agent_id ? AiAgent::find($draft->agent_id) : null;
            if ($agent && $agent->autonomy === 'auto') {
                $this->send($draft, $account);
            }
        } catch (\Throwable $e) {
            Log::warning('AI reply handling failed: ' . $e->getMessage());
        }
    }

    public function send(AiReplyDraft $draft, ?ChannelAccount $account = null): bool
    {
        $to = (string) ($draft->thread?->contact?->address ?? '');
        if ($to === '' || !$draft->draft) {
            return false;
        }

        try {
            $this->channels->send(new OutboundMessage(
                channel: (string) $draft->channel,
                account: $account,
                to: $to,
                body: $draft->draft,
                customerId: (int) $draft->customer_id,
                threadId: $draft->thread_id,
            ));

            $draft->update(['status' => 'sent']);

            return true;
        } catch (\Throwable $e) {
            Log::warning('AI draft send failed: ' . $e->getMessage());

            return false;
        }
    }
}

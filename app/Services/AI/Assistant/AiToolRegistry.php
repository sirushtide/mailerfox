<?php

namespace App\Services\AI\Assistant;

use App\Models\ChannelAccount;
use App\Models\ChannelContact;
use App\Models\ChannelMessage;
use App\Models\ChannelThread;
use App\Models\Customer;
use App\Models\EmailList;
use App\Models\ListSubscriber;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\OutboundMessage;
use App\Services\Sms\SmsSendService;
use App\Services\WhatsApp\WhatsAppSendService;
use Illuminate\Support\Facades\Mail;

/**
 * Registry of tools the AI Assistant can invoke. Tools are keyed by name and
 * expose a short description, parameter hints and a handler. Additional tools
 * can be registered by addons via the "ai_assistant_tools" filter.
 */
class AiToolRegistry
{
    /**
     * @return array<string, array{description: string, parameters: array<string,string>, handler: callable}>
     */
    public function all(): array
    {
        $tools = [
            'send_email' => [
                'description' => 'Send a transactional email to a recipient.',
                'parameters'  => ['to' => 'email address', 'subject' => 'string', 'body' => 'string'],
                'handler'     => fn (array $args, Customer $customer) => $this->sendEmail($args),
            ],
            'send_sms' => [
                'description' => 'Send an SMS message to a phone number.',
                'parameters'  => ['to' => 'phone number', 'body' => 'string'],
                'handler'     => fn (array $args, Customer $customer) => $this->sendSms($args, $customer),
            ],
            'send_whatsapp' => [
                'description' => 'Send a WhatsApp message to a phone number.',
                'parameters'  => ['to' => 'phone number', 'body' => 'string'],
                'handler'     => fn (array $args, Customer $customer) => $this->sendWhatsApp($args, $customer),
            ],
            'list_lists' => [
                'description' => 'List the customer email/contact lists.',
                'parameters'  => [],
                'handler'     => fn (array $args, Customer $customer) => $this->listLists($customer),
            ],
            'list_stats' => [
                'description' => 'Get subscriber statistics for a list.',
                'parameters'  => ['list_id' => 'integer'],
                'handler'     => fn (array $args, Customer $customer) => $this->listStats($args, $customer),
            ],
            'channel_stats' => [
                'description' => 'Get recent message counts for a channel (sms or whatsapp).',
                'parameters'  => ['channel' => 'sms|whatsapp'],
                'handler'     => fn (array $args, Customer $customer) => $this->channelStats($args, $customer),
            ],
            'recent_threads' => [
                'description' => 'List recent conversations for a channel.',
                'parameters'  => ['channel' => 'sms|whatsapp'],
                'handler'     => fn (array $args, Customer $customer) => $this->recentThreads($args, $customer),
            ],
            'search_contacts' => [
                'description' => 'Search channel contacts by name or address.',
                'parameters'  => ['channel' => 'sms|whatsapp', 'query' => 'string'],
                'handler'     => fn (array $args, Customer $customer) => $this->searchContacts($args, $customer),
            ],
        ];

        $tools = apply_filters('ai_assistant_tools', $tools);

        return is_array($tools) ? $tools : [];
    }

    public function has(string $name): bool
    {
        return isset($this->all()[$name]);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function run(string $name, array $args, Customer $customer): array
    {
        $tools = $this->all();
        if (!isset($tools[$name])) {
            return ['success' => false, 'error' => "Unknown tool [{$name}]."];
        }

        try {
            $result = ($tools[$name]['handler'])($args, $customer);

            return is_array($result) ? $result : ['success' => true, 'result' => $result];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function describe(): string
    {
        $lines = [];
        foreach ($this->all() as $name => $tool) {
            $params = empty($tool['parameters'])
                ? '{}'
                : json_encode($tool['parameters'], JSON_UNESCAPED_SLASHES);
            $lines[] = "- {$name}: {$tool['description']} params={$params}";
        }

        return implode("\n", $lines);
    }

    protected function sendEmail(array $args): array
    {
        $to = trim((string) ($args['to'] ?? ''));
        if ($to === '') {
            return ['success' => false, 'error' => 'Recipient is required.'];
        }

        Mail::raw((string) ($args['body'] ?? ''), function ($mail) use ($to, $args) {
            $mail->to($to)->subject((string) ($args['subject'] ?? 'Message'));
        });

        return ['success' => true, 'message' => 'Email sent.'];
    }

    protected function sendSms(array $args, Customer $customer): array
    {
        $account = ChannelAccount::forChannel('sms')->accessibleBy($customer->id)->active()->first();
        if (!$account) {
            return ['success' => false, 'error' => 'No active SMS account available.'];
        }

        $message = app(SmsSendService::class)->send(
            $account,
            (string) ($args['to'] ?? ''),
            (string) ($args['body'] ?? ''),
            [],
            ['customer_id' => $customer->id]
        );

        return [
            'success' => $message->status !== 'failed',
            'message_id' => $message->id,
            'error' => $message->error,
        ];
    }

    protected function sendWhatsApp(array $args, Customer $customer): array
    {
        $account = ChannelAccount::forChannel('whatsapp')->accessibleBy($customer->id)->active()->first();
        if (!$account) {
            return ['success' => false, 'error' => 'No active WhatsApp account available.'];
        }

        $message = app(WhatsAppSendService::class)->send(
            $account,
            (string) ($args['to'] ?? ''),
            (string) ($args['body'] ?? ''),
            [],
            ['customer_id' => $customer->id]
        );

        return [
            'success' => $message->status !== 'failed',
            'message_id' => $message->id,
            'error' => $message->error,
        ];
    }

    protected function listLists(Customer $customer): array
    {
        return [
            'success' => true,
            'lists' => EmailList::where('customer_id', $customer->id)
                ->select('id', 'name')
                ->limit(50)
                ->get()
                ->toArray(),
        ];
    }

    protected function listStats(array $args, Customer $customer): array
    {
        $listId = (int) ($args['list_id'] ?? 0);
        $list = EmailList::where('customer_id', $customer->id)->find($listId);
        if (!$list) {
            return ['success' => false, 'error' => 'List not found.'];
        }

        return [
            'success' => true,
            'list' => $list->name,
            'total' => ListSubscriber::where('list_id', $listId)->count(),
            'confirmed' => ListSubscriber::where('list_id', $listId)->where('status', 'confirmed')->count(),
        ];
    }

    protected function channelStats(array $args, Customer $customer): array
    {
        $channel = (string) ($args['channel'] ?? '');
        if (!in_array($channel, ['sms', 'whatsapp'], true)) {
            return ['success' => false, 'error' => 'Channel must be sms or whatsapp.'];
        }

        return [
            'success' => true,
            'channel' => $channel,
            'messages_30d' => ChannelMessage::where('customer_id', $customer->id)
                ->where('channel', $channel)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'inbound_30d' => ChannelMessage::where('customer_id', $customer->id)
                ->where('channel', $channel)
                ->where('direction', 'in')
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'contacts' => ChannelContact::where('customer_id', $customer->id)->where('channel', $channel)->count(),
        ];
    }

    protected function recentThreads(array $args, Customer $customer): array
    {
        $channel = (string) ($args['channel'] ?? '');
        if (!in_array($channel, ['sms', 'whatsapp'], true)) {
            return ['success' => false, 'error' => 'Channel must be sms or whatsapp.'];
        }

        return [
            'success' => true,
            'threads' => ChannelThread::where('customer_id', $customer->id)
                ->where('channel', $channel)
                ->with('contact:id,address,name')
                ->orderByDesc('last_message_at')
                ->limit(20)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'contact' => $t->contact?->name ?: $t->contact?->address,
                    'unread' => $t->unread_count,
                    'last_message_at' => optional($t->last_message_at)->toDateTimeString(),
                ])
                ->toArray(),
        ];
    }

    protected function searchContacts(array $args, Customer $customer): array
    {
        $channel = (string) ($args['channel'] ?? '');
        $query = trim((string) ($args['query'] ?? ''));

        $contacts = ChannelContact::where('customer_id', $customer->id)
            ->when($channel !== '', fn ($q) => $q->where('channel', $channel))
            ->when($query !== '', fn ($q) => $q->where(function ($sub) use ($query) {
                $sub->where('address', 'like', "%{$query}%")->orWhere('name', 'like', "%{$query}%");
            }))
            ->limit(25)
            ->get(['id', 'channel', 'address', 'name'])
            ->toArray();

        return ['success' => true, 'contacts' => $contacts];
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\KnowledgeBase;
use App\Models\RagConversation;
use App\Services\AI\AiProviderRegistry;
use App\Services\AI\AiProviderRouter;
use App\Services\AI\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AiAgentController extends Controller
{
    public function __construct(
        protected RagService $ragService,
        protected AiProviderRouter $router,
    ) {
    }

    protected function customer()
    {
        return auth('customer')->user();
    }

    public function index(Request $request)
    {
        $customer = $this->customer();

        $agents = AiAgent::forCustomer($customer->id)
            ->with('knowledgeBases')
            ->orderBy('name')
            ->get();

        return view('customer.ai-agents.index', compact('agents'));
    }

    public function chat(Request $request, AiAgent $aiAgent)
    {
        $customer = $this->customer();
        $this->authorizeOwn($aiAgent, $customer);

        if ($aiAgent->status !== 'active') {
            return redirect()
                ->route('customer.ai-agents.index')
                ->with('error', __('This agent is currently inactive.'));
        }

        $agents = AiAgent::forCustomer($customer->id)
            ->active()
            ->orderBy('name')
            ->get();

        // Slim conversation list: only metadata + lightweight preview (no full messages JSON)
        $conversations = $this->conversationsQuery($customer->id, $aiAgent->id, 21)->get();
        $hasMoreConversations = $conversations->count() > 20;
        $conversations = $conversations->take(20);

        $activeConversation = null;
        if ($request->filled('conversation')) {
            $activeConversation = RagConversation::query()
                ->where('customer_id', $customer->id)
                ->where('agent_id', $aiAgent->id)
                ->where('mode', 'chat')
                ->select('id', 'title')
                ->find($request->integer('conversation'));
        }

        $providerModels = $this->providerModels();

        return view('customer.ai-agents.chat', compact(
            'agents',
            'aiAgent',
            'conversations',
            'activeConversation',
            'providerModels',
            'hasMoreConversations'
        ));
    }

    public function conversationsList(Request $request, AiAgent $aiAgent)
    {
        $customer = $this->customer();
        $this->authorizeOwn($aiAgent, $customer);

        $perPage = max(1, min(50, $request->integer('per_page', 20)));
        $page = max(1, $request->integer('page', 1));
        $exclude = $request->input('exclude');
        $excludeIds = is_array($exclude)
            ? array_filter(array_map('intval', $exclude))
            : array_filter(array_map('intval', explode(',', (string) $exclude)));

        $query = $this->conversationsQuery($customer->id, $aiAgent->id, $perPage + 1, $excludeIds);
        $items = $query->get();
        $hasMore = $items->count() > $perPage;
        $items = $items->take($perPage);

        return response()->json([
            'success' => true,
            'conversations' => $items->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title ?: __('Untitled'),
                'preview' => $c->preview ? \Illuminate\Support\Str::limit(strip_tags($c->preview), 60) : '',
                'updated_at' => $c->updated_at?->diffForHumans(),
            ])->values(),
            'has_more' => $hasMore,
        ]);
    }

    protected function conversationsQuery(int $customerId, int $agentId, int $limit, array $excludeIds = [])
    {
        $query = RagConversation::query()
            ->where('customer_id', $customerId)
            ->where('agent_id', $agentId)
            ->where('mode', 'chat')
            ->orderByDesc('updated_at')
            ->limit($limit);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        $driver = RagConversation::query()->getConnection()->getDriverName();
        if ($driver === 'mysql') {
            $query->select('id', 'title', 'updated_at');
            $query->addSelect(\DB::raw(
                'CASE WHEN JSON_LENGTH(messages) > 0 '
                . 'THEN JSON_UNQUOTE(JSON_EXTRACT(messages, CONCAT(\'$[\', JSON_LENGTH(messages) - 1, \'].content\'))) '
                . 'ELSE NULL END as preview'
            ));
        } else {
            // Fallback: select messages too for non-MySQL (SQLite/Postgres)
            $query->select('id', 'title', 'updated_at', 'messages');
            $query->addSelect(\DB::raw("NULL as preview"));
        }

        return $query;
    }

    public function create()
    {
        $customer = $this->customer();
        $agent = null;
        $knowledgeBases = KnowledgeBase::forCustomer($customer->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $providerModels = $this->providerModels();

        return view('customer.ai-agents.form', compact('agent', 'knowledgeBases', 'providerModels'));
    }

    public function store(Request $request)
    {
        $customer = $this->customer();

        $validated = $request->validate($this->rules($customer));

        $provider = $validated['provider'] ?? 'chatgpt';
        $agent = AiAgent::create([
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'provider' => $provider,
            'model' => $validated['model'] ?: AiProviderRegistry::defaultModel($provider),
            'avatar_color' => $validated['avatar_color'] ?? '#1E5FEA',
            'status' => 'active',
            'image' => $this->storeAgentImage($request),
        ]);

        $this->syncKnowledgeBases($agent, $validated['knowledge_base_ids'] ?? [], $customer);

        return redirect()
            ->route('customer.ai-agents.chat', $agent)
            ->with('success', __('AI agent created successfully.'));
    }

    public function edit(AiAgent $aiAgent)
    {
        $customer = $this->customer();
        $this->authorizeOwn($aiAgent, $customer);

        $knowledgeBases = KnowledgeBase::forCustomer($customer->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $providerModels = $this->providerModels();
        $aiAgent->load('knowledgeBases');
        $agent = $aiAgent;

        return view('customer.ai-agents.form', compact('agent', 'knowledgeBases', 'providerModels'));
    }

    public function update(Request $request, AiAgent $aiAgent)
    {
        $customer = $this->customer();
        $this->authorizeOwn($aiAgent, $customer);

        $validated = $request->validate($this->rules($customer, $aiAgent));

        $provider = $validated['provider'] ?? 'chatgpt';
        $aiAgent->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'provider' => $provider,
            'model' => $validated['model'] ?: AiProviderRegistry::defaultModel($provider),
            'avatar_color' => $validated['avatar_color'] ?? '#1E5FEA',
            'status' => $validated['status'] ?? 'active',
            'image' => $this->storeAgentImage($request, $aiAgent),
        ]);

        $this->syncKnowledgeBases($aiAgent, $validated['knowledge_base_ids'] ?? [], $customer);

        return redirect()
            ->route('customer.ai-agents.chat', $aiAgent)
            ->with('success', __('AI agent updated successfully.'));
    }

    public function destroy(AiAgent $aiAgent)
    {
        $customer = $this->customer();
        $this->authorizeOwn($aiAgent, $customer);

        $aiAgent->delete();

        return redirect()
            ->route('customer.ai-agents.index')
            ->with('success', __('AI agent deleted.'));
    }

    public function ask(Request $request)
    {
        $customer = $this->customer();

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:5000'],
            'agent_id' => ['required', 'integer'],
            'provider' => ['nullable', 'string', 'in:' . implode(',', AiProviderRegistry::providerKeys())],
            'model' => ['nullable', 'string', 'max:100'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $agent = AiAgent::forCustomer($customer->id)
            ->active()
            ->find($validated['agent_id']);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'AI agent not found.',
            ], 404);
        }

        $conversation = null;
        if (!empty($validated['conversation_id'])) {
            $conversation = RagConversation::query()
                ->where('customer_id', $customer->id)
                ->where('agent_id', $agent->id)
                ->where('mode', 'chat')
                ->find($validated['conversation_id']);
        }

        $result = $this->ragService->chatWithAgent(
            $customer,
            $agent,
            $validated['query'],
            $conversation,
            $validated['provider'] ?? null,
            $validated['model'] ?? null
        );

        return response()->json($result, $result['status'] ?? 200);
    }

    public function askStream(Request $request)
    {
        $customer = $this->customer();

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:5000'],
            'agent_id' => ['required', 'integer'],
            'provider' => ['nullable', 'string', 'in:' . implode(',', AiProviderRegistry::providerKeys())],
            'model' => ['nullable', 'string', 'max:100'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $agent = AiAgent::forCustomer($customer->id)
            ->active()
            ->find($validated['agent_id']);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'AI agent not found.',
            ], 404);
        }

        $conversation = null;
        if (!empty($validated['conversation_id'])) {
            $conversation = RagConversation::query()
                ->where('customer_id', $customer->id)
                ->where('agent_id', $agent->id)
                ->where('mode', 'chat')
                ->find($validated['conversation_id']);
        }

        return response()->stream(function () use ($customer, $agent, $validated, $conversation) {
            $sendEvent = function (string $event, array $data = []) {
                echo 'event: ' . $event . "\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            $result = $this->ragService->chatWithAgentStream(
                $customer,
                $agent,
                $validated['query'],
                $conversation,
                $validated['provider'] ?? null,
                $validated['model'] ?? null,
                function (string $delta) use ($sendEvent) {
                    $sendEvent('delta', ['text' => $delta]);
                }
            );

            if (!($result['success'] ?? false)) {
                $sendEvent('error', [
                    'message' => $result['message'] ?? 'AI generation failed.',
                    'status' => $result['status'] ?? 502,
                ]);
            } else {
                $sendEvent('done', [
                    'answer' => $result['answer'] ?? '',
                    'conversation_id' => $result['conversation_id'] ?? null,
                    'tokens' => $result['tokens'] ?? null,
                    'sources' => $result['sources'] ?? [],
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function chatFeedback(Request $request, AiAgent $aiAgent)
    {
        $customer = $this->customer();
        $this->authorizeOwn($aiAgent, $customer);

        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'message_index' => ['required', 'integer', 'min:0'],
            'feedback' => ['required', 'string', 'in:like,dislike'],
        ]);

        $conversation = RagConversation::query()
            ->where('customer_id', $customer->id)
            ->where('agent_id', $aiAgent->id)
            ->where('mode', 'chat')
            ->find($validated['conversation_id']);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        $conversation->setMessageFeedback(
            (int) $validated['message_index'],
            $validated['feedback']
        );

        return response()->json(['success' => true]);
    }

    public function conversation(AiAgent $aiAgent, RagConversation $conversation)
    {
        $customer = $this->customer();

        if ($conversation->customer_id !== $customer->id || $conversation->agent_id !== $aiAgent->id) {
            abort(403);
        }

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $conversation->messages ?? [],
        ]);
    }

    protected function providerModels(): array
    {
        $models = [];
        foreach (AiProviderRegistry::providerKeys() as $key) {
            if (! $this->router->isProviderEnabled($key)) {
                continue;
            }
            $models[$key] = AiProviderRegistry::models($key);
        }
        return $models;
    }

    protected function rules($customer, ?AiAgent $agent = null): array
    {
        $providers = AiProviderRegistry::providerKeys();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'provider' => ['required', 'string', 'in:' . implode(',', $providers)],
            'model' => ['nullable', 'string', 'max:100'],
            'avatar_color' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'knowledge_base_ids' => ['nullable', 'array'],
            'knowledge_base_ids.*' => [
                'integer',
                Rule::exists('knowledge_bases', 'id')->where(function ($query) use ($customer) {
                    $query->where(function ($q) use ($customer) {
                        $q->where('customer_id', $customer->id)->orWhereNull('customer_id');
                    })->where('status', 'active');
                }),
            ],
        ];
    }

    protected function syncKnowledgeBases(AiAgent $agent, array $ids, $customer): void
    {
        $ids = array_map('intval', array_filter($ids));

        $allowed = KnowledgeBase::forCustomer($customer->id)
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        $agent->knowledgeBases()->sync($allowed);
    }

    protected function authorizeOwn(AiAgent $agent, $customer): void
    {
        if ($agent->customer_id !== $customer->id) {
            abort(403);
        }
    }

    protected function storeAgentImage(Request $request, ?AiAgent $agent = null): ?string
    {
        if (!$request->hasFile('image')) {
            return $agent?->image;
        }

        $file = $request->file('image');
        if (!$file || !$file->isValid()) {
            return $agent?->image;
        }

        $disk = (string) config('filesystems.branding_disk', 'public');

        if ($agent && is_string($agent->image) && $agent->image !== '') {
            Storage::disk($disk)->delete($agent->image);
        }

        return $file->storePublicly('agents', $disk);
    }
}

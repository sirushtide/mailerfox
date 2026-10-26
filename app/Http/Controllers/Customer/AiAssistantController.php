<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\AiAgent;
use App\Models\AiAssistantConversation;
use App\Models\AiAssistantMessage;
use App\Services\AI\Assistant\AiAssistantOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    protected function guard(): void
    {
        abort_unless(Addon::isActive('ai-assistant'), 404);
    }

    protected function customer()
    {
        return auth('customer')->user();
    }

    public function index(Request $request): View
    {
        $this->guard();

        $customer = $this->customer();

        $conversations = AiAssistantConversation::where('customer_id', $customer->id)
            ->latest()
            ->limit(30)
            ->get();

        $active = null;
        if ($request->filled('conversation')) {
            $active = AiAssistantConversation::where('customer_id', $customer->id)
                ->find($request->integer('conversation'));
        }

        $agents = AiAgent::forCustomer($customer->id)->active()->orderBy('name')->get();

        return view('customer.ai-assistant.index', compact('conversations', 'active', 'agents'));
    }

    public function ask(Request $request, AiAssistantOrchestrator $orchestrator): JsonResponse
    {
        $this->guard();

        $customer = $this->customer();

        $validated = $request->validate([
            'message'         => ['required', 'string', 'max:8000'],
            'conversation_id' => ['nullable', 'integer'],
            'agent_id'        => ['nullable', 'integer'],
            'model'           => ['nullable', 'string', 'max:191'],
            'context'         => ['nullable', 'array'],
        ]);

        $conversation = null;
        if (!empty($validated['conversation_id'])) {
            $conversation = AiAssistantConversation::where('customer_id', $customer->id)
                ->find($validated['conversation_id']);
        }

        if (!$conversation) {
            $conversation = AiAssistantConversation::create([
                'customer_id' => $customer->id,
                'agent_id'    => $validated['agent_id'] ?? null,
                'title'       => mb_substr($validated['message'], 0, 80),
                'context'     => $validated['context'] ?? null,
            ]);
        }

        AiAssistantMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $validated['message'],
        ]);

        $history = $conversation->messages()
            ->latest('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->map(fn (AiAssistantMessage $m) => ['role' => $m->role, 'content' => (string) $m->content])
            ->values()
            ->all();

        $agent = !empty($validated['agent_id']) ? AiAgent::forCustomer($customer->id)->find($validated['agent_id']) : null;

        $result = $orchestrator->respond(
            $customer,
            $validated['model'] ?? null,
            array_slice($history, 0, -1),
            $validated['message'],
            $validated['context'] ?? [],
            $agent
        );

        AiAssistantMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $result['reply'] ?: (string) ($result['error'] ?? ''),
        ]);

        foreach ($result['tool_runs'] as $run) {
            AiAssistantMessage::create([
                'conversation_id' => $conversation->id,
                'role'            => 'tool',
                'tool_name'       => $run['tool'],
                'tool_result'     => $run['result'],
            ]);
        }

        return response()->json([
            'success'         => $result['success'],
            'reply'           => $result['reply'],
            'tool_runs'       => $result['tool_runs'],
            'model'           => $result['model'],
            'conversation_id' => $conversation->id,
            'error'           => $result['error'],
        ]);
    }

    public function destroy(AiAssistantConversation $conversation): JsonResponse
    {
        $this->guard();

        abort_unless((int) $conversation->customer_id === (int) $this->customer()->id, 403);

        $conversation->delete();

        return response()->json(['success' => true]);
    }
}

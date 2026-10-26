<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use App\Models\RagConversation;
use App\Services\AI\AiProviderRegistry;
use App\Services\AI\AiProviderRouter;
use App\Services\AI\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RagController extends Controller
{
    public function __construct(
        protected RagService $ragService,
        protected AiProviderRouter $router,
    ) {
    }

    public function knowledgeIndex()
    {
        $knowledgeBases = KnowledgeBase::query()
            ->whereNull('customer_id')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.rag.knowledge-index', compact('knowledgeBases'));
    }

    public function knowledgeCreate()
    {
        return view('admin.rag.knowledge-create');
    }

    public function knowledgeStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:general,brand,product,faq,campaign'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source_type' => ['required', 'string', 'in:text,file'],
            'text_content' => ['nullable', 'string', 'max:500000'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:txt,md,csv,json,html,htm,pdf'],
        ]);

        $kb = KnowledgeBase::create([
            'customer_id' => null,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'source_type' => $validated['source_type'],
            'status' => 'processing',
        ]);

        try {
            if ($validated['source_type'] === 'text') {
                $text = trim((string) ($validated['text_content'] ?? ''));
                if ($text === '') {
                    $kb->markFailed('Text content is required.');
                    return redirect()->back()->withInput()->with('error', 'Text content is required.');
                }

                $this->ragService->ingestText($kb, $text);
            } elseif ($validated['source_type'] === 'file' && $request->hasFile('file')) {
                $file = $request->file('file');
                $content = $this->extractFileContent($file);
                if ($content === '') {
                    $kb->markFailed('Could not extract text from the uploaded file.');
                    return redirect()->back()->withInput()->with('error', 'Could not extract text from the uploaded file.');
                }

                $kb->file_path = $file->store('knowledge-bases', 'local');
                $kb->save();

                $this->ragService->ingestText($kb, $content);
            } else {
                $kb->markFailed('No content provided.');
                return redirect()->back()->withInput()->with('error', 'No content provided.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admin knowledge base ingestion failed', [
                'knowledge_base_id' => $kb->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $kb->markFailed('Indexing failed: ' . $e->getMessage());

            return redirect()
                ->route('admin.rag.knowledge.index')
                ->with('error', 'Knowledge base creation failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.rag.knowledge.index')
            ->with('success', 'Knowledge base created with ' . $kb->total_chunks . ' chunks.');
    }

    public function knowledgeDestroy(KnowledgeBase $knowledgeBase)
    {
        $this->ragService->deleteKnowledgeBase($knowledgeBase);

        return redirect()
            ->route('admin.rag.knowledge.index')
            ->with('success', 'Knowledge base deleted.');
    }

    public function chatIndex(Request $request)
    {
        $conversations = RagConversation::query()
            ->whereNull('customer_id')
            ->where('mode', 'chat')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $activeConversation = null;
        if ($request->has('conversation')) {
            $activeConversation = RagConversation::query()
                ->whereNull('customer_id')
                ->where('mode', 'chat')
                ->find($request->integer('conversation'));
        }

        $providers = AiProviderRegistry::providerLabels();
        $providerModels = [];
        foreach (AiProviderRegistry::providerKeys() as $key) {
            if (! $this->router->isProviderEnabled($key)) {
                continue;
            }
            $providerModels[$key] = AiProviderRegistry::allModels($key);
        }

        return view('admin.rag.chat', compact('conversations', 'activeConversation', 'providers', 'providerModels'));
    }

    public function chatAsk(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:5000'],
            'provider' => ['nullable', 'string', 'in:' . implode(',', AiProviderRegistry::providerKeys())],
            'model' => ['nullable', 'string', 'max:100'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $apiKey = \App\Models\Setting::get(AiProviderRegistry::settingKey($validated['provider'] ?? 'chatgpt'));

        if (!is_string($apiKey) || trim($apiKey) === '') {
            return response()->json([
                'success' => false,
                'message' => 'Admin AI API key is not configured for this provider.',
            ], 422);
        }

        $provider = $validated['provider'] ?? 'chatgpt';
        $model = $validated['model'] ?? AiProviderRegistry::defaultModel($provider);
        $query = $validated['query'];

        $chunks = $this->ragService->retrieve($query, null);
        $context = $this->ragService->buildContext($chunks);

        $systemPrompt = "You are a helpful AI assistant with access to a knowledge base. "
            . "Answer the user's question using ONLY the provided context. "
            . "If the context does not contain the answer, say you do not have that information. "
            . "Do not make up facts, do not answer from general knowledge, and do not output any internal reasoning or meta-commentary. "
            . "Use markdown formatting (headings, bullet lists, numbered lists, bold, code fences) for a clear, readable answer.\n\n"
            . "Context:\n" . ($context !== '' ? $context : '(No relevant context found)');

        $conversation = null;
        if (!empty($validated['conversation_id'])) {
            $conversation = RagConversation::query()
                ->whereNull('customer_id')
                ->where('mode', 'chat')
                ->find($validated['conversation_id']);
        }

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        if ($conversation && $conversation->messages) {
            $history = array_slice($conversation->messages, -6);
            foreach ($history as $msg) {
                if (isset($msg['role']) && in_array($msg['role'], ['user', 'assistant'], true)) {
                    $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
                }
            }
        }

        $messages[] = ['role' => 'user', 'content' => $query];

        $systemPrompt = null;
        $chatMessages = [];
        foreach ($messages as $m) {
            if (($m['role'] ?? null) === 'system') {
                $systemPrompt = $m['content'];
                continue;
            }
            $chatMessages[] = $m;
        }

        $result = $this->router->generateChat($model, $chatMessages, 1500, $systemPrompt);

        if (!is_array($result) || !($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => is_array($result) && is_string($result['message'] ?? null) ? $result['message'] : 'AI generation failed.',
                'sources' => $chunks,
            ], 502);
        }

        $text = is_string($result['text'] ?? null) ? $this->stripMarkdown(trim($result['text'])) : '';
        $tokens = is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : null;

        if (!$conversation) {
            $conversation = RagConversation::create([
                'customer_id' => null,
                'mode' => 'chat',
                'title' => mb_substr($query, 0, 60),
                'provider' => $provider,
                'model' => $model,
                'messages' => [],
            ]);
        }

        $conversation->addMessage([
            'role' => 'user',
            'content' => $query,
            'timestamp' => now()->toIso8601String(),
        ]);

        $conversation->addMessage([
            'role' => 'assistant',
            'content' => $text,
            'sources' => array_map(fn($c) => [
                'title' => $c['title'],
                'chunk_index' => $c['chunk_index'],
                'score' => $c['score'],
            ], $chunks),
            'tokens' => $tokens,
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'answer' => $text,
            'sources' => $chunks,
            'tokens' => $tokens,
            'conversation_id' => $conversation->id,
        ]);
    }

    public function chatFeedback(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'message_index' => ['required', 'integer', 'min:0'],
            'feedback' => ['required', 'string', 'in:like,dislike'],
        ]);

        $conversation = RagConversation::query()
            ->whereNull('customer_id')
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

    protected function extractFileContent($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['txt', 'md', 'csv', 'json', 'html', 'htm'], true)) {
            $content = file_get_contents($file->getRealPath());
            if ($extension === 'html' || $extension === 'htm') {
                $content = strip_tags($content);
            }
            return trim($content ?: '');
        }

        if ($extension === 'pdf') {
            $content = '';
            $path = $file->getRealPath();

            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($path);
                    $content = $pdf->getText();
                } catch (\Throwable $e) {
                    // Fall through
                }
            }

            if ($content === '' && function_exists('shell_exec')) {
                $escaped = escapeshellarg($path);
                $output = @shell_exec("pdftotext -q {$escaped} - 2>/dev/null");
                if (is_string($output) && trim($output) !== '') {
                    $content = $output;
                }
            }

            return trim($content);
        }

        return '';
    }

    private function stripMarkdown(string $text): string
    {
        return Str::markdown(trim($text), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}

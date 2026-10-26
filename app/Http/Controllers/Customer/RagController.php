<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use App\Models\RagConversation;
use App\Services\AI\AiProviderRegistry;
use App\Services\AI\AiProviderRouter;
use App\Services\AI\CampaignRagService;
use App\Services\AI\InsightsRagService;
use App\Services\AI\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RagController extends Controller
{
    public function __construct(
        protected RagService $ragService,
        protected CampaignRagService $campaignRagService,
        protected InsightsRagService $insightsRagService,
        protected AiProviderRouter $router,
    ) {
    }

    public function knowledgeIndex(Request $request)
    {
        $customer = auth('customer')->user();

        $knowledgeBases = KnowledgeBase::query()
            ->forCustomer($customer->id)
            ->orderByDesc('created_at')
            ->get();

        return view('customer.rag.knowledge-index', compact('knowledgeBases'));
    }

    public function knowledgeCreate()
    {
        return view('customer.rag.knowledge-create');
    }

    public function knowledgeStore(Request $request)
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:general,document,brand,product,faq,campaign,url,image'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source_type' => ['required', 'string', 'in:text,file,url'],
            'text_content' => ['nullable', 'string', 'max:500000'],
            'source_url' => ['nullable', 'url', 'max:2000'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:txt,md,csv,json,html,htm,pdf,jpg,jpeg,png,gif,webp'],
        ]);

        $kb = KnowledgeBase::create([
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'source_type' => $validated['source_type'],
            'source_url' => $validated['source_url'] ?? null,
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
            } elseif ($validated['source_type'] === 'url') {
                $url = trim((string) ($validated['source_url'] ?? ''));
                if ($url === '') {
                    $kb->markFailed('URL is required.');
                    return redirect()->back()->withInput()->with('error', 'URL is required.');
                }

                $content = $this->fetchUrlContent($url);
                if ($content === '') {
                    $kb->markFailed('Could not fetch content from the provided URL. The page may be JavaScript-rendered or unreachable.');
                    return redirect()->back()->withInput()->with('error', 'Could not fetch content from the provided URL.');
                }

                $kb->source_url = $url;
                $kb->save();

                $this->ragService->ingestText($kb, $content);
            } else {
                $kb->markFailed('No content provided.');
                return redirect()->back()->withInput()->with('error', 'No content provided.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Knowledge base ingestion failed', [
                'knowledge_base_id' => $kb->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $kb->markFailed('Indexing failed: ' . $e->getMessage());

            return redirect()
                ->route('customer.rag.knowledge.index')
                ->with('error', 'Knowledge base creation failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('customer.rag.knowledge.index')
            ->with('success', 'Knowledge base created successfully with ' . $kb->total_chunks . ' chunks.');
    }

    public function knowledgeDestroy(KnowledgeBase $knowledgeBase)
    {
        $customer = auth('customer')->user();
        if ($knowledgeBase->customer_id !== $customer->id) {
            abort(403);
        }

        $this->ragService->deleteKnowledgeBase($knowledgeBase);

        return redirect()
            ->route('customer.rag.knowledge.index')
            ->with('success', 'Knowledge base deleted.');
    }

    public function chatIndex(Request $request)
    {
        $customer = auth('customer')->user();

        $conversations = RagConversation::query()
            ->where('customer_id', $customer->id)
            ->where('mode', 'chat')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $activeConversation = null;
        if ($request->has('conversation')) {
            $activeConversation = RagConversation::query()
                ->where('customer_id', $customer->id)
                ->where('mode', 'chat')
                ->find($request->integer('conversation'));
        }

        $providers = AiProviderRegistry::providerLabels();
        $providerModels = [];
        foreach (AiProviderRegistry::providerKeys() as $key) {
            if (! $this->router->isProviderEnabled($key)) {
                continue;
            }
            $providerModels[$key] = AiProviderRegistry::models($key);
        }

        return view('customer.rag.chat', compact('conversations', 'activeConversation', 'providers', 'providerModels'));
    }

    public function chatAsk(Request $request)
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:5000'],
            'provider' => ['nullable', 'string', 'in:' . implode(',', AiProviderRegistry::providerKeys())],
            'model' => ['nullable', 'string', 'max:100'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $conversation = null;
        if (!empty($validated['conversation_id'])) {
            $conversation = RagConversation::query()
                ->where('customer_id', $customer->id)
                ->where('mode', 'chat')
                ->find($validated['conversation_id']);
        }

        $result = $this->ragService->chat(
            $customer,
            $validated['query'],
            $conversation,
            $validated['provider'] ?? null,
            $validated['model'] ?? null
        );

        if ($request->expectsJson()) {
            return response()->json($result, $result['status'] ?? 200);
        }

        if (!($result['success'] ?? false)) {
            return redirect()->back()->withInput()->with('error', $result['message'] ?? 'Failed to get response.');
        }

        return redirect()->route('customer.rag.chat.index', ['conversation' => $result['conversation_id'] ?? null])
            ->with('success', 'Response generated.');
    }

    public function chatFeedback(Request $request)
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'message_index' => ['required', 'integer', 'min:0'],
            'feedback' => ['required', 'string', 'in:like,dislike'],
        ]);

        $conversation = RagConversation::query()
            ->where('customer_id', $customer->id)
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

    public function campaignIndex()
    {
        $providers = AiProviderRegistry::providerLabels();

        return view('customer.rag.campaign', compact('providers'));
    }

    public function campaignGenerate(Request $request)
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:5000'],
            'provider' => ['nullable', 'string', 'in:' . implode(',', AiProviderRegistry::providerKeys())],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->campaignRagService->generateCampaignContent(
            $customer,
            $validated['prompt'],
            $validated['provider'] ?? 'chatgpt',
            $validated['model'] ?? null
        );

        if ($request->expectsJson()) {
            return response()->json($result, $result['status'] ?? 200);
        }

        if (!($result['success'] ?? false)) {
            return redirect()->back()->withInput()->with('error', $result['message'] ?? 'Generation failed.');
        }

        return redirect()->back()->with('success', 'Content generated.')->with('generated_text', $result['text']);
    }

    public function insightsIndex()
    {
        $providers = AiProviderRegistry::providerLabels();

        return view('customer.rag.insights', compact('providers'));
    }

    public function insightsAsk(Request $request)
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:5000'],
            'provider' => ['nullable', 'string', 'in:' . implode(',', AiProviderRegistry::providerKeys())],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->insightsRagService->answer(
            $customer,
            $validated['query'],
            $validated['provider'] ?? 'chatgpt',
            $validated['model'] ?? null
        );

        if ($request->expectsJson()) {
            return response()->json($result, $result['status'] ?? 200);
        }

        if (!($result['success'] ?? false)) {
            return redirect()->back()->withInput()->with('error', $result['message'] ?? 'Failed to get insights.');
        }

        return redirect()->back()->with('success', 'Insights generated.')->with('generated_text', $result['answer']);
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

    protected function fetchUrlContent(string $url): string
    {
        try {
            $response = Http::timeout(20)->get($url);
            if (!$response->successful()) {
                return '';
            }

            $html = (string) $response->body();
            $text = strip_tags($html);
            $text = preg_replace('/\s+/', ' ', $text) ?: '';
            return trim($text);
        } catch (\Throwable $e) {
            return '';
        }
    }
}

<?php

namespace App\Services\AI;

use App\Models\AiAgent;
use App\Models\Customer;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Models\RagConversation;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RagService
{
    protected int $defaultChunkSize = 500;
    protected int $defaultChunkOverlap = 50;
    protected int $defaultTopK = 5;
    protected int $maxContextTokens = 3000;

    public function __construct(
        protected AiUsageService $aiUsage,
        protected ?AiProviderRouter $router = null,
    ) {
        $this->router = $router ?? app(AiProviderRouter::class);
    }

    public function chunkText(string $text, int $chunkSize = 0, int $overlap = 0): array
    {
        $chunkSize = $chunkSize > 0 ? $chunkSize : (int) Setting::get('rag_chunk_size', $this->defaultChunkSize);
        $overlap = $overlap > 0 ? $overlap : (int) Setting::get('rag_chunk_overlap', $this->defaultChunkOverlap);

        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [$text];

        $chunks = [];
        $currentChunk = '';
        $currentWords = 0;

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }

            $sentenceWords = str_word_count($sentence);

            if ($currentWords + $sentenceWords > $chunkSize && $currentChunk !== '') {
                $chunks[] = trim($currentChunk);

                if ($overlap > 0 && $currentWords > $overlap) {
                    $words = preg_split('/\s+/', $currentChunk) ?: [];
                    $overlapText = implode(' ', array_slice($words, -$overlap));
                    $currentChunk = $overlapText . ' ' . $sentence;
                    $currentWords = str_word_count($currentChunk);
                } else {
                    $currentChunk = $sentence;
                    $currentWords = $sentenceWords;
                }
            } else {
                $currentChunk = $currentChunk === '' ? $sentence : $currentChunk . ' ' . $sentence;
                $currentWords += $sentenceWords;
            }
        }

        if (trim($currentChunk) !== '') {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    public function ingestText(KnowledgeBase $kb, string $text): int
    {
        $chunks = $this->chunkText($text);
        $totalWords = 0;

        foreach ($chunks as $index => $chunk) {
            $wordCount = str_word_count($chunk);
            $totalWords += $wordCount;

            KnowledgeChunk::create([
                'knowledge_base_id' => $kb->id,
                'chunk_index' => $index,
                'content' => $chunk,
                'word_count' => $wordCount,
                'title' => $kb->name . ' — Chunk ' . ($index + 1),
            ]);
        }

        $kb->update([
            'total_chunks' => count($chunks),
            'total_words' => $totalWords,
            'status' => 'active',
        ]);

        return count($chunks);
    }

    public function retrieve(string $query, ?int $customerId = null, int $topK = 0): array
    {
        $topK = $topK > 0 ? $topK : (int) Setting::get('rag_top_k', $this->defaultTopK);
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $kbQuery = KnowledgeBase::query()
            ->where('status', 'active')
            ->forCustomer($customerId);

        $kbIds = $kbQuery->pluck('id');

        if ($kbIds->isEmpty()) {
            return [];
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver", 'mysql');

        if ($driver === 'mysql') {
            $results = DB::table('knowledge_chunks')
                ->select('knowledge_chunks.id', 'knowledge_chunks.knowledge_base_id', 'knowledge_chunks.content', 'knowledge_chunks.title', 'knowledge_chunks.chunk_index')
                ->selectRaw('MATCH(knowledge_chunks.content) AGAINST (?) AS relevance_score', [$query])
                ->join('knowledge_bases', 'knowledge_chunks.knowledge_base_id', '=', 'knowledge_bases.id')
                ->whereIn('knowledge_chunks.knowledge_base_id', $kbIds)
                ->where('knowledge_bases.status', 'active')
                ->whereRaw('MATCH(knowledge_chunks.content) AGAINST (?)', [$query])
                ->orderByDesc('relevance_score')
                ->limit($topK)
                ->get();
        } else {
            $results = $this->fallbackRetrieve(
                DB::table('knowledge_chunks')
                    ->select('knowledge_chunks.id', 'knowledge_chunks.knowledge_base_id', 'knowledge_chunks.content', 'knowledge_chunks.title', 'knowledge_chunks.chunk_index')
                    ->join('knowledge_bases', 'knowledge_chunks.knowledge_base_id', '=', 'knowledge_bases.id')
                    ->whereIn('knowledge_chunks.knowledge_base_id', $kbIds)
                    ->where('knowledge_bases.status', 'active'),
                $query,
                $topK
            );
        }

        return $results->map(function ($row) {
            return [
                'id' => $row->id,
                'knowledge_base_id' => $row->knowledge_base_id,
                'content' => $row->content,
                'title' => $row->title,
                'chunk_index' => $row->chunk_index,
                'score' => isset($row->relevance_score) ? (float) $row->relevance_score : 0.0,
            ];
        })->toArray();
    }

    public function retrieveForAgent(string $query, array $kbIds, int $topK = 0): array
    {
        $topK = $topK > 0 ? $topK : (int) Setting::get('rag_top_k', $this->defaultTopK);
        $query = trim($query);

        if ($query === '' || empty($kbIds)) {
            return [];
        }

        $kbIds = array_map('intval', array_filter($kbIds));

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver", 'mysql');

        if ($driver === 'mysql') {
            $results = DB::table('knowledge_chunks')
                ->select('knowledge_chunks.id', 'knowledge_chunks.knowledge_base_id', 'knowledge_chunks.content', 'knowledge_chunks.title', 'knowledge_chunks.chunk_index')
                ->selectRaw('MATCH(knowledge_chunks.content) AGAINST (?) AS relevance_score', [$query])
                ->join('knowledge_bases', 'knowledge_chunks.knowledge_base_id', '=', 'knowledge_bases.id')
                ->whereIn('knowledge_chunks.knowledge_base_id', $kbIds)
                ->where('knowledge_bases.status', 'active')
                ->whereRaw('MATCH(knowledge_chunks.content) AGAINST (?)', [$query])
                ->orderByDesc('relevance_score')
                ->limit($topK)
                ->get();
        } else {
            $results = $this->fallbackRetrieve(
                DB::table('knowledge_chunks')
                    ->select('knowledge_chunks.id', 'knowledge_chunks.knowledge_base_id', 'knowledge_chunks.content', 'knowledge_chunks.title', 'knowledge_chunks.chunk_index')
                    ->join('knowledge_bases', 'knowledge_chunks.knowledge_base_id', '=', 'knowledge_bases.id')
                    ->whereIn('knowledge_chunks.knowledge_base_id', $kbIds)
                    ->where('knowledge_bases.status', 'active'),
                $query,
                $topK
            );
        }

        return $results->map(function ($row) {
            return [
                'id' => $row->id,
                'knowledge_base_id' => $row->knowledge_base_id,
                'content' => $row->content,
                'title' => $row->title,
                'chunk_index' => $row->chunk_index,
                'score' => isset($row->relevance_score) ? (float) $row->relevance_score : 0.0,
            ];
        })->toArray();
    }

    private function fallbackRetrieve($queryBuilder, string $query, int $topK)
    {
        $keywords = $this->extractKeywords($query);

        if (empty($keywords)) {
            $keywords = [$query];
        }

        $queryBuilder->where(function ($q) use ($keywords) {
            foreach ($keywords as $index => $keyword) {
                $pattern = '%' . $keyword . '%';
                if ($index === 0) {
                    $q->where('knowledge_chunks.content', 'LIKE', $pattern);
                } else {
                    $q->orWhere('knowledge_chunks.content', 'LIKE', $pattern);
                }
            }
        });

        $candidates = $queryBuilder->get();

        if ($candidates->isEmpty()) {
            return $candidates;
        }

        // Compute document frequency for each keyword across candidates
        $documentFrequency = [];
        foreach ($keywords as $keyword) {
            $documentFrequency[$keyword] = 0;
            foreach ($candidates as $row) {
                $haystack = strtolower($row->content . ' ' . $row->title);
                if (strpos($haystack, $keyword) !== false) {
                    $documentFrequency[$keyword]++;
                }
            }
        }

        // Score each chunk by sum of inverse document frequencies
        $scored = $candidates->map(function ($row) use ($keywords, $documentFrequency) {
            $haystack = strtolower($row->content . ' ' . $row->title);
            $score = 0.0;
            foreach ($keywords as $keyword) {
                if (strpos($haystack, $keyword) !== false) {
                    $df = $documentFrequency[$keyword] ?? 1;
                    $score += $df > 0 ? (1 / $df) : 0;
                }
            }
            $row->relevance_score = $score;
            return $row;
        });

        $scored = $scored->sortByDesc('relevance_score')->values();

        return $scored->take($topK);
    }

    private function extractKeywords(string $query): array
    {
        $words = preg_split('/\s+/', strtolower($query)) ?: [];
        $stopWords = ['a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'how', 'what', 'when', 'where', 'why', 'who', 'which', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them', 'my', 'your', 'his', 'its', 'our', 'their', 'on', 'in', 'at', 'to', 'for', 'of', 'with', 'about', 'as', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'from', 'up', 'down', 'out', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'and', 'but', 'or', 'yet', 'so', 'if', 'because', 'although', 'though', 'while', 'whereas', 'unless', 'until', 'since', 'than', 'too', 'very', 'just', 'now', 'only', 'also', 'do', 'does', 'run'];

        $keywords = [];
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z0-9]/', '', $word);
            if ($word === '' || in_array($word, $stopWords, true) || strlen($word) < 2) {
                continue;
            }
            $keywords[] = $word;
            foreach ($this->expandWord($word) as $expanded) {
                $keywords[] = $expanded;
            }
        }

        return array_values(array_unique($keywords));
    }

    private function expandWord(string $word): array
    {
        $expanded = [];
        $dictionary = [
            'cron', 'job', 'jobs', 'cronjob', 'cronjobs', 'queue', 'worker', 'supervisor', 'systemd', 'schedule', 'task',
            'mailpurse', 'mailserver', 'server', 'install', 'installation', 'setup', 'configure',
            'config', 'env', 'database', 'mysql', 'sqlite', 'redis', 'php', 'artisan', 'command',
            'webhook', 'hook', 'api', 'key', 'campaign', 'list', 'subscriber', 'email', 'bounce',
            'delivery', 'sender', 'domain', 'dns', 'dkim', 'spf', 'dmarc', 'smtp', 'imap',
        ];

        // Add known subwords found inside the word
        foreach ($dictionary as $dictWord) {
            if ($dictWord !== $word && strpos($word, $dictWord) !== false && strlen($dictWord) >= 3) {
                $expanded[] = $dictWord;
            }
        }

        // Try simple suffix stemming for plurals
        if (str_ends_with($word, 's') && strlen($word) > 3) {
            $expanded[] = substr($word, 0, -1);
        }
        if (str_ends_with($word, 'es') && strlen($word) > 4) {
            $expanded[] = substr($word, 0, -2);
        }
        if (str_ends_with($word, 'ing') && strlen($word) > 5) {
            $expanded[] = substr($word, 0, -3);
        }

        return array_values(array_unique($expanded));
    }

    public function buildContext(array $chunks): string
    {
        $parts = [];
        $totalTokens = 0;
        $maxTokens = (int) Setting::get('rag_max_context_tokens', $this->maxContextTokens);

        foreach ($chunks as $chunk) {
            $estimatedTokens = (int) ceil(strlen($chunk['content']) / 4);
            if ($totalTokens + $estimatedTokens > $maxTokens) {
                break;
            }

            $parts[] = "[Source: {$chunk['title']}]\n{$chunk['content']}";
            $totalTokens += $estimatedTokens;
        }

        return implode("\n\n---\n\n", $parts);
    }

    private function stripMarkdown(string $text): string
    {
        return Str::markdown(trim($text), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function chat(
        Customer $customer,
        string $query,
        ?RagConversation $conversation = null,
        ?string $provider = null,
        ?string $model = null
    ): array {
        $provider = $provider ?? (string) Setting::get('rag_default_provider', 'chatgpt');
        $model = $model ?? AiProviderRegistry::defaultModel($provider);

        if (!AiProviderRegistry::exists($provider)) {
            return [
                'success' => false,
                'message' => 'Invalid AI provider.',
                'status' => 422,
            ];
        }

        $customerField = AiProviderRegistry::customerField($provider);
        $settingKey = AiProviderRegistry::settingKey($provider);

        $ownApiKey = is_string($customer->{$customerField} ?? null) ? trim((string) $customer->{$customerField}) : '';
        $adminApiKey = is_string(Setting::get($settingKey)) ? trim((string) Setting::get($settingKey)) : '';
        $usingAdminKeys = false;

        if ($ownApiKey !== '') {
            $apiKey = $ownApiKey;
        } else {
            $apiKey = $adminApiKey;
            $usingAdminKeys = true;
        }

        if (!is_string($apiKey) || trim($apiKey) === '') {
            return [
                'success' => false,
                'message' => 'No AI API key configured. Please add your API key in settings or ask the admin to configure one.',
                'status' => 422,
            ];
        }

        $chunks = $this->retrieve($query, $customer->id);
        $context = $this->buildContext($chunks);

        $systemPrompt = "You are a helpful AI support assistant with access to a knowledge base. "
            . "Answer the user's question using ONLY the provided context. "
            . "If the context does not contain the answer, say you do not have that information. "
            . "Do not make up facts, do not answer from general knowledge, and do not output any internal reasoning or meta-commentary. "
            . "Use markdown formatting (headings, bullet lists, numbered lists, bold, code fences) for a clear, readable answer.\n\n"
            . "Context from knowledge base:\n"
            . ($context !== '' ? $context : '(No relevant context found in knowledge base)');

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

        $maxOutputTokens = 1500;
        $inputEstimate = max(1, (int) ceil(strlen(implode("\n", array_column($messages, 'content'))) / 4));

        if ($usingAdminKeys) {
            $tokenLimit = (int) $customer->groupSetting('ai.token_limit', 0);
            $current = (int) ($customer->ai_token_usage ?? 0);
            $budget = null;

            if ($tokenLimit > 0) {
                $budget = $tokenLimit - $current;
            }

            $monthlyLimit = $this->aiUsage->getAdminMonthlyLimit($provider);
            if ($monthlyLimit > 0) {
                $usedMonth = $this->aiUsage->getAdminTokensUsedThisMonth($provider);
                $monthlyBudget = $monthlyLimit - $usedMonth;
                $budget = $budget === null ? $monthlyBudget : min($budget, $monthlyBudget);
            }

            if ($budget !== null && $budget <= 0) {
                return [
                    'success' => false,
                    'message' => 'AI token limit reached.',
                    'status' => 429,
                ];
            }

            if ($budget !== null) {
                $maxOutputTokens = min($maxOutputTokens, max(1, $budget - $inputEstimate));
            }
        }

        $systemPrompt = null;
        $chatMessages = [];
        foreach ($messages as $m) {
            if (($m['role'] ?? null) === 'system') {
                $systemPrompt = $m['content'];
                continue;
            }
            $chatMessages[] = $m;
        }

        $result = $this->router->generateChat($model, $chatMessages, $maxOutputTokens, $systemPrompt, $customer);

        if (!is_array($result) || !($result['success'] ?? false)) {
            return [
                'success' => false,
                'message' => is_array($result) && is_string($result['message'] ?? null) ? $result['message'] : 'AI generation failed.',
                'status' => 502,
                'sources' => $chunks,
            ];
        }

        $text = is_string($result['text'] ?? null) ? $this->stripMarkdown(trim($result['text'])) : '';
        $tokens = is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : ($inputEstimate + max(1, (int) ceil(strlen($text) / 4)));

        if ($usingAdminKeys) {
            $customer->ai_token_usage = (int) ($customer->ai_token_usage ?? 0) + $tokens;
            $customer->save();
        }

        if (!$conversation) {
            $conversation = RagConversation::create([
                'customer_id' => $customer->id,
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

        return [
            'success' => true,
            'answer' => $text,
            'sources' => $chunks,
            'tokens' => $tokens,
            'conversation_id' => $conversation->id,
            'used_admin_keys' => $usingAdminKeys,
        ];
    }

    public function chatWithAgent(
        Customer $customer,
        AiAgent $agent,
        string $query,
        ?RagConversation $conversation = null,
        ?string $provider = null,
        ?string $model = null
    ): array {
        return $this->doChatWithAgent($customer, $agent, $query, $conversation, $provider, $model, null);
    }

    public function chatWithAgentStream(
        Customer $customer,
        AiAgent $agent,
        string $query,
        ?RagConversation $conversation = null,
        ?string $provider = null,
        ?string $model = null,
        ?callable $onDelta = null
    ): array {
        return $this->doChatWithAgent($customer, $agent, $query, $conversation, $provider, $model, $onDelta);
    }

    protected function doChatWithAgent(
        Customer $customer,
        AiAgent $agent,
        string $query,
        ?RagConversation $conversation = null,
        ?string $provider = null,
        ?string $model = null,
        ?callable $onDelta = null
    ): array {
        $provider = $provider ?? $agent->provider ?? (string) Setting::get('rag_default_provider', 'chatgpt');
        $model = $model ?? $agent->model ?? AiProviderRegistry::defaultModel($provider);

        if (!AiProviderRegistry::exists($provider)) {
            return [
                'success' => false,
                'message' => 'Invalid AI provider.',
                'status' => 422,
            ];
        }

        $customerField = AiProviderRegistry::customerField($provider);
        $settingKey = AiProviderRegistry::settingKey($provider);

        $ownApiKey = is_string($customer->{$customerField} ?? null) ? trim((string) $customer->{$customerField}) : '';
        $adminApiKey = is_string(Setting::get($settingKey)) ? trim((string) Setting::get($settingKey)) : '';
        $usingAdminKeys = false;

        if ($ownApiKey !== '') {
            $apiKey = $ownApiKey;
        } else {
            $apiKey = $adminApiKey;
            $usingAdminKeys = true;
        }

        if (!is_string($apiKey) || trim($apiKey) === '') {
            return [
                'success' => false,
                'message' => 'No AI API key configured. Please add your API key in settings or ask the admin to configure one.',
                'status' => 422,
            ];
        }

        $kbIds = $agent->knowledgeBases()->where('status', 'active')->pluck('knowledge_bases.id')->all();
        $chunks = $this->retrieveForAgent($query, $kbIds);
        $context = $this->buildContext($chunks);

        $identity = $agent->name ? "You are {$agent->name}." : 'You are a helpful AI assistant.';
        $description = $agent->description ? "{$agent->description}." : '';
        $instructions = $agent->instructions ? "{$agent->instructions}" : '';

        $systemPrompt = "{$identity}\n{$description}\n{$instructions}\n\n"
            . "You have access to the following knowledge base context. Answer the user's question using ONLY this context. "
            . "If the context does not contain the answer, say you do not have that information. "
            . "Do not make up facts, do not answer from general knowledge, and do not output any internal reasoning or meta-commentary. "
            . "Use markdown formatting (headings, bullet lists, numbered lists, bold, code fences) for a clear, readable answer.\n\n"
            . "Context from knowledge base:\n"
            . ($context !== '' ? $context : '(No relevant context found in knowledge base)');

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

        $maxOutputTokens = 1500;
        $inputEstimate = max(1, (int) ceil(strlen(implode("\n", array_column($messages, 'content'))) / 4));

        if ($usingAdminKeys) {
            $tokenLimit = (int) $customer->groupSetting('ai.token_limit', 0);
            $current = (int) ($customer->ai_token_usage ?? 0);
            $budget = null;

            if ($tokenLimit > 0) {
                $budget = $tokenLimit - $current;
            }

            $monthlyLimit = $this->aiUsage->getAdminMonthlyLimit($provider);
            if ($monthlyLimit > 0) {
                $usedMonth = $this->aiUsage->getAdminTokensUsedThisMonth($provider);
                $monthlyBudget = $monthlyLimit - $usedMonth;
                $budget = $budget === null ? $monthlyBudget : min($budget, $monthlyBudget);
            }

            if ($budget !== null && $budget <= 0) {
                return [
                    'success' => false,
                    'message' => 'AI token limit reached.',
                    'status' => 429,
                ];
            }

            if ($budget !== null) {
                $maxOutputTokens = min($maxOutputTokens, max(1, $budget - $inputEstimate));
            }
        }

        $systemPrompt = null;
        $chatMessages = [];
        foreach ($messages as $m) {
            if (($m['role'] ?? null) === 'system') {
                $systemPrompt = $m['content'];
                continue;
            }
            $chatMessages[] = $m;
        }

        if ($onDelta !== null) {
            $result = $this->router->streamChat($model, $chatMessages, $maxOutputTokens, $systemPrompt, $onDelta, $customer);
        } else {
            $result = $this->router->generateChat($model, $chatMessages, $maxOutputTokens, $systemPrompt, $customer);
        }

        if (!is_array($result) || !($result['success'] ?? false)) {
            return [
                'success' => false,
                'message' => is_array($result) && is_string($result['message'] ?? null) ? $result['message'] : 'AI generation failed.',
                'status' => 502,
                'sources' => $chunks,
            ];
        }

        $text = is_string($result['text'] ?? null) ? $this->stripMarkdown(trim($result['text'])) : '';
        $tokens = is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : ($inputEstimate + max(1, (int) ceil(strlen($text) / 4)));

        if ($usingAdminKeys) {
            $customer->ai_token_usage = (int) ($customer->ai_token_usage ?? 0) + $tokens;
            $customer->save();
        }

        if (!$conversation) {
            $conversation = RagConversation::create([
                'customer_id' => $customer->id,
                'agent_id' => $agent->id,
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

        return [
            'success' => true,
            'answer' => $text,
            'sources' => $chunks,
            'tokens' => $tokens,
            'conversation_id' => $conversation->id,
            'used_admin_keys' => $usingAdminKeys,
        ];
    }

    public function deleteKnowledgeBase(KnowledgeBase $kb): void
    {
        $kb->chunks()->delete();
        $kb->delete();
    }
}

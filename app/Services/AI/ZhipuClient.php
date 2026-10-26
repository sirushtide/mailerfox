<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class ZhipuClient implements AiProviderInterface
{
    use StreamChatOpenAICompatibleTrait;

    private const DEFAULT_API_BASE = 'https://open.bigmodel.cn/api/paas/v4/chat/completions';
    private const DEFAULT_MODEL = 'glm-5.2';

    protected function getStreamApiBase(): string
    {
        return $this->getApiBase();
    }

    private function getApiBase(): string
    {
        $base = (string) Setting::get('glm_api_base_url', '');
        $base = trim($base);
        if ($base === '') {
            return self::DEFAULT_API_BASE;
        }
        // Ensure it ends with /chat/completions
        if (!str_ends_with($base, '/chat/completions')) {
            $base = rtrim($base, '/') . '/chat/completions';
        }
        return $base;
    }

    private function normalizeModel(?string $model): string
    {
        $model = is_string($model) ? trim($model) : '';
        return $model !== '' ? $model : self::DEFAULT_MODEL;
    }

    private function describeFailure($response): string
    {
        $status = null;
        $body = null;

        try {
            $status = $response ? $response->status() : null;
            $body = $response ? $response->body() : null;
        } catch (\Throwable $e) {
        }

        $message = null;
        try {
            $data = $response ? $response->json() : null;
            if (is_array($data) && is_array($data['error'] ?? null) && is_string($data['error']['message'] ?? null)) {
                $message = trim((string) $data['error']['message']);
            }
        } catch (\Throwable $e) {
        }

        $prefix = 'Zhipu (GLM) request failed.';
        if (is_numeric($status)) {
            $prefix .= ' (HTTP ' . ((int) $status) . ')';
        }

        if (is_string($message) && trim($message) !== '') {
            return $prefix . ' ' . $message;
        }

        if (is_string($body) && trim($body) !== '') {
            return $prefix . ' ' . substr(trim(str_replace(["\r", "\n"], ' ', $body)), 0, 500);
        }

        return $prefix;
    }

    private function extractChoiceText(array $data): ?string
    {
        $choice = $data['choices'][0] ?? null;
        if (!is_array($choice)) {
            return null;
        }

        $message = $choice['message'] ?? null;
        if (!is_array($message)) {
            return null;
        }

        $content = $message['content'] ?? null;
        if (is_string($content)) {
            $text = trim($content);
            if ($text !== '') {
                return $text;
            }
        }

        // Some Zhipu models (e.g. glm-4.5-air) put reasoning in reasoning_content
        // and may leave content empty when max_tokens is small.
        $reasoning = $message['reasoning_content'] ?? null;
        if (is_string($reasoning)) {
            $text = trim($reasoning);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function extractTokens(array $data): ?int
    {
        $usage = $data['usage'] ?? null;
        if (!is_array($usage)) {
            return null;
        }

        if (is_numeric($usage['total_tokens'] ?? null)) {
            return (int) $usage['total_tokens'];
        }

        $prompt = is_numeric($usage['prompt_tokens'] ?? null) ? (int) $usage['prompt_tokens'] : 0;
        $completion = is_numeric($usage['completion_tokens'] ?? null) ? (int) $usage['completion_tokens'] : 0;

        if ($prompt > 0 || $completion > 0) {
            return $prompt + $completion;
        }

        return null;
    }

    /**
     * Extract a JSON object from LLM text that may be wrapped in markdown fences,
     * preceded by explanatory text, or contain trailing commas.
     */
    private function extractJsonObject(string $text): ?array
    {
        $text = $this->sanitizeJsonText($text);
        if ($text === '') {
            return null;
        }

        // Direct decode
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Remove markdown code fences and decode again
        $fenced = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $fenced = preg_replace('/\s*```\s*$/', '', $fenced) ?? $fenced;
        $fenced = trim($fenced);
        $decoded = json_decode($fenced, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Extract first JSON object (handles text before/after)
        if (preg_match('/(\{[\s\S]*\})/', $text, $matches)) {
            $decoded = json_decode($this->sanitizeJsonText($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Fix trailing commas
        $fixed = preg_replace('/,\s*}/', '}', $text) ?? $text;
        $fixed = preg_replace('/,\s*]/', ']', $fixed) ?? $fixed;
        $decoded = json_decode($this->sanitizeJsonText($fixed), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Combine fence removal with trailing comma fix
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $inner = $this->sanitizeJsonText($matches[1]);
            $innerFixed = preg_replace('/,\s*}/', '}', $inner) ?? $inner;
            $innerFixed = preg_replace('/,\s*]/', ']', $innerFixed) ?? $innerFixed;
            $decoded = json_decode($innerFixed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Extract JSON object with trailing comma fix
        if (preg_match('/(\{[\s\S]*\})/', $text, $matches)) {
            $inner = $this->sanitizeJsonText($matches[1]);
            $innerFixed = preg_replace('/,\s*}/', '}', $inner) ?? $inner;
            $innerFixed = preg_replace('/,\s*]/', ']', $innerFixed) ?? $innerFixed;
            $decoded = json_decode($innerFixed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function sanitizeJsonText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Strip UTF-8 BOM and zero-width characters that can break JSON parsing
        $text = preg_replace('/^[\xEF\xBB\xBF]+/', '', $text) ?? $text;
        $text = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $text) ?? $text;

        // Strip line comments and block comments.
        // IMPORTANT: only strip "//" comments that begin a line, never mid-string,
        // otherwise URLs like "https://..." inside JSON string values get destroyed
        // and the payload becomes invalid JSON.
        $text = preg_replace('/\/\*[\s\S]*?\*\//', '', $text) ?? $text;
        $text = preg_replace('/^\s*\/\/[^\r\n]*/m', '', $text) ?? $text;

        // Strip reasoning/thinking tags
        $text = preg_replace('/<think(?:ing)?[^>]*>[\s\S]*?<\/think(?:ing)?>/i', '', $text) ?? $text;
        $text = preg_replace('/<reason(?:ing)?[^>]*>[\s\S]*?<\/reason(?:ing)?>/i', '', $text) ?? $text;

        return trim($text);
    }

    public function generateText(string $apiKey, string $prompt, int $maxTokens = 1200, ?string $model = null): array
    {
        return $this->generateChat(
            $apiKey,
            [['role' => 'user', 'content' => trim($prompt)]],
            $maxTokens,
            $model,
            'You write email copy. Output ONLY plain text (no markdown, no code fences).'
        );
    }

    public function generateChat(string $apiKey, array $messages, int $maxTokens = 1200, ?string $model = null, ?string $systemPrompt = null): array
    {
        $model = $this->normalizeModel($model);

        $apiMessages = [];
        if (is_string($systemPrompt) && trim($systemPrompt) !== '') {
            $apiMessages[] = ['role' => 'system', 'content' => trim($systemPrompt)];
        }

        foreach ($messages as $message) {
            if (!is_array($message) || !is_string($message['role'] ?? null) || !is_string($message['content'] ?? null)) {
                continue;
            }
            $apiMessages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        if (empty($apiMessages)) {
            return [
                'success' => false,
                'text' => null,
                'tokens' => null,
                'message' => 'No messages provided.',
                'raw' => null,
            ];
        }

        $payload = [
            'model' => $model,
            'messages' => $apiMessages,
            'max_tokens' => max(1, $maxTokens),
            'temperature' => 0.7,
        ];

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }

            $url = $this->getApiBase();
            \Illuminate\Support\Facades\Log::debug('ZhipuClient request', ['url' => $url, 'model' => $model, 'api_key_prefix' => substr($apiKey, 0, 8), 'payload' => $payload]);

            $response = Http::timeout(120)
                ->retry(1, 200)
                ->withToken($apiKey)
                ->post($url, $payload);

            \Illuminate\Support\Facades\Log::debug('ZhipuClient response', ['status' => $response->status(), 'body' => $response->body()]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'text' => null,
                    'tokens' => null,
                    'message' => $this->describeFailure($response),
                    'raw' => ['status' => $response->status(), 'body' => $response->body()],
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'text' => null,
                    'tokens' => null,
                    'message' => 'Zhipu returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            $text = $this->extractChoiceText($data);
            if (!is_string($text) || $text === '') {
                return [
                    'success' => false,
                    'text' => null,
                    'tokens' => null,
                    'message' => 'Zhipu returned an empty response.',
                    'raw' => $data,
                ];
            }

            $text = preg_replace('/^```\s*/i', '', $text) ?? $text;
            $text = preg_replace('/\s*```\s*$/', '', $text) ?? $text;
            $text = trim($text);

            return [
                'success' => true,
                'text' => $text,
                'tokens' => $this->extractTokens($data),
                'message' => null,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'text' => null,
                'tokens' => null,
                'message' => $e->getMessage(),
                'raw' => null,
            ];
        }
    }

    public function generateJsonObject(string $apiKey, string $systemPrompt, string $userPrompt, int $maxTokens = 1200, ?string $model = null): array
    {
        $model = $this->normalizeModel($model);

        $messages = [];
        if (is_string($systemPrompt) && trim($systemPrompt) !== '') {
            $messages[] = ['role' => 'system', 'content' => trim($systemPrompt)];
        }
        $messages[] = ['role' => 'user', 'content' => trim($userPrompt)];

        if (empty($messages)) {
            return [
                'success' => false,
                'json' => null,
                'tokens' => null,
                'message' => 'No messages provided.',
                'raw' => null,
            ];
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => max(1, $maxTokens),
            'temperature' => 0.5,
            'response_format' => ['type' => 'json_object'],
        ];

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(300);
            }

            $url = $this->getApiBase();

            $sendRequest = function (array $requestPayload) use ($url, $apiKey) {
                $response = Http::timeout(180)
                    ->retry(1, 200)
                    ->withToken($apiKey)
                    ->post($url, $requestPayload);

                return $response;
            };

            $response = $sendRequest($payload);

            $shouldRetryWithoutFormat = false;

            if (!$response->successful()) {
                $maybe = $response->json();
                $errMessage = is_array($maybe) && is_array($maybe['error'] ?? null) && is_string($maybe['error']['message'] ?? null)
                    ? strtolower((string) $maybe['error']['message'])
                    : '';
                if ($errMessage !== '' && (str_contains($errMessage, 'response_format') || str_contains($errMessage, 'json'))) {
                    $shouldRetryWithoutFormat = true;
                }
            }

            $data = $response->json();
            if (!is_array($data)) {
                $shouldRetryWithoutFormat = true;
            } elseif (is_array($data['error'] ?? null) && !is_array($data['choices'] ?? null)) {
                // Some providers return errors in a 200 body with no choices.
                $errMessage = is_string($data['error']['message'] ?? null) ? strtolower((string) $data['error']['message']) : '';
                if ($errMessage !== '' && (str_contains($errMessage, 'response_format') || str_contains($errMessage, 'json'))) {
                    $shouldRetryWithoutFormat = true;
                }
            }

            // Retry without response_format if the model rejects it or the body is not JSON.
            if ($shouldRetryWithoutFormat) {
                $fallbackPayload = $payload;
                unset($fallbackPayload['response_format']);
                $response = $sendRequest($fallbackPayload);
            }

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => null,
                    'message' => $this->describeFailure($response),
                    'raw' => ['status' => $response->status(), 'body' => $response->body()],
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                \Illuminate\Support\Facades\Log::warning('ZhipuClient generateJsonObject: non-JSON response body', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => substr((string) $response->body(), 0, 1000),
                ]);

                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => null,
                    'message' => 'Zhipu returned a non-JSON response.',
                    'raw' => ['status' => $response->status(), 'body' => $response->body()],
                ];
            }

            $text = $this->extractChoiceText($data);
            if (!is_string($text) || $text === '') {
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => null,
                    'message' => 'Zhipu returned an empty response.',
                    'raw' => $data,
                ];
            }

            $decoded = $this->extractJsonObject($text);
            if (!is_array($decoded)) {
                \Illuminate\Support\Facades\Log::warning('ZhipuClient generateJsonObject: content is not valid JSON', [
                    'model' => $model,
                    'content_preview' => substr($text, 0, 1000),
                ]);

                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => $this->extractTokens($data),
                    'message' => 'Zhipu returned invalid JSON.',
                    'raw' => $data,
                ];
            }

            return [
                'success' => true,
                'json' => $decoded,
                'tokens' => $this->extractTokens($data),
                'message' => null,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'json' => null,
                'tokens' => null,
                'message' => $e->getMessage(),
                'raw' => null,
            ];
        }
    }

    public function generateTemplateHtml(string $apiKey, string $prompt, int $maxTokens = 1200, ?string $model = null): array
    {
        $prompt = trim($prompt);
        $model = $this->normalizeModel($model);

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You generate email template HTML. Output ONLY valid HTML (no markdown, no code fences). Do NOT use inline base64 images (data:image/...). Use only publicly accessible image URLs.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => max(1, $maxTokens),
            'temperature' => 0.7,
        ];

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }

            $response = Http::timeout(120)
                ->retry(1, 200)
                ->withToken($apiKey)
                ->post($this->getApiBase(), $payload);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'html' => null,
                    'tokens' => null,
                    'message' => $this->describeFailure($response),
                    'raw' => ['status' => $response->status(), 'body' => $response->body()],
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'html' => null,
                    'tokens' => null,
                    'message' => 'Zhipu returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            $html = $this->extractChoiceText($data);
            if (!is_string($html) || $html === '') {
                return [
                    'success' => false,
                    'html' => null,
                    'tokens' => null,
                    'message' => 'Zhipu returned an empty response.',
                    'raw' => $data,
                ];
            }

            $html = preg_replace('/^```(?:html)?\s*/i', '', $html) ?? $html;
            $html = preg_replace('/\s*```\s*$/', '', $html) ?? $html;
            $html = trim($html);

            return [
                'success' => true,
                'html' => $html,
                'tokens' => $this->extractTokens($data),
                'message' => null,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'html' => null,
                'tokens' => null,
                'message' => $e->getMessage(),
                'raw' => null,
            ];
        }
    }

    public function generateTemplateUnlayerDesign(string $apiKey, string $prompt, int $maxTokens = 1200, ?string $model = null): array
    {
        $prompt = trim($prompt);
        $model = $this->normalizeModel($model);

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You generate email templates as Unlayer design JSON. Output ONLY valid JSON (no markdown, no code fences). The JSON must be a design object with keys: counters and body. The body must contain rows/columns/contents with proper Unlayer block types like heading, text, image, button, divider, spacer. Do NOT use inline base64 images (data:image/...). Use only publicly accessible image URLs.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => max(1, $maxTokens),
            'temperature' => 0.4,
        ];

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }

            $response = Http::timeout(120)
                ->retry(1, 200)
                ->withToken($apiKey)
                ->post($this->getApiBase(), $payload);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => null,
                    'message' => $this->describeFailure($response),
                    'raw' => ['status' => $response->status(), 'body' => $response->body()],
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => null,
                    'message' => 'Zhipu returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            $content = $this->extractChoiceText($data);
            if (!is_string($content) || $content === '') {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => null,
                    'message' => 'Zhipu returned an empty response.',
                    'raw' => $data,
                ];
            }

            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```\s*$/', '', $content) ?? $content;
            $content = trim($content);

            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => $this->extractTokens($data),
                    'message' => 'Zhipu returned invalid Unlayer design JSON.',
                    'raw' => $data,
                ];
            }

            $design = $decoded;
            if (!isset($design['body']) && (isset($design['rows']) || isset($design['values']))) {
                $design = ['counters' => [], 'body' => $decoded];
            }

            if (!is_array($design['body'] ?? null)) {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => $this->extractTokens($data),
                    'message' => 'Zhipu returned an invalid Unlayer design structure.',
                    'raw' => $data,
                ];
            }

            if (!isset($design['counters']) || (!is_array($design['counters']) && !is_object($design['counters']))) {
                $design['counters'] = [];
            }

            return [
                'success' => true,
                'design' => $design,
                'tokens' => $this->extractTokens($data),
                'message' => null,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'design' => null,
                'tokens' => null,
                'message' => $e->getMessage(),
                'raw' => null,
            ];
        }
    }
}

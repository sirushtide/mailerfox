<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class OpenRouterClient implements AiProviderInterface
{
    use StreamChatOpenAICompatibleTrait;
    private const API_BASE = 'https://openrouter.ai/api/v1/chat/completions';
    private const DEFAULT_MODEL = 'openai/gpt-4o-mini';

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

        $prefix = 'OpenRouter request failed.';
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
            return $text !== '' ? $text : null;
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

    private function usageFields(array $data): array
    {
        $usage = $data['usage'] ?? null;
        $input = 0;
        $output = 0;
        if (is_array($usage)) {
            $input = is_numeric($usage['prompt_tokens'] ?? null) ? (int) $usage['prompt_tokens'] : 0;
            $output = is_numeric($usage['completion_tokens'] ?? null) ? (int) $usage['completion_tokens'] : 0;
        }
        return [
            'tokens' => $this->extractTokens($data),
            'input_tokens' => $input,
            'output_tokens' => $output,
        ];
    }

    private function buildHeaders(string $apiKey): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        $siteUrl = config('app.url', '');
        if (is_string($siteUrl) && trim($siteUrl) !== '') {
            $headers['HTTP-Referer'] = $siteUrl;
        }

        $siteName = config('app.name', 'MailPurse');
        if (is_string($siteName) && trim($siteName) !== '') {
            $headers['X-Title'] = $siteName;
        }

        return $headers;
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

            $response = Http::timeout(120)
                ->retry(1, 200)
                ->withHeaders($this->buildHeaders($apiKey))
                ->post(self::API_BASE, $payload);

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
                    'message' => 'OpenRouter returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            $text = $this->extractChoiceText($data);
            if (!is_string($text) || $text === '') {
                return [
                    'success' => false,
                    'text' => null,
                    'tokens' => null,
                    'message' => 'OpenRouter returned an empty response.',
                    'raw' => $data,
                ];
            }

            $text = preg_replace('/^```\s*/i', '', $text) ?? $text;
            $text = preg_replace('/\s*```\s*$/', '', $text) ?? $text;
            $text = trim($text);

            return [
                'success' => true,
                'text' => $text,
                ...$this->usageFields($data),
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
                ->withHeaders($this->buildHeaders($apiKey))
                ->post(self::API_BASE, $payload);

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
                    'message' => 'OpenRouter returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            $html = $this->extractChoiceText($data);
            if (!is_string($html) || $html === '') {
                return [
                    'success' => false,
                    'html' => null,
                    'tokens' => null,
                    'message' => 'OpenRouter returned an empty response.',
                    'raw' => $data,
                ];
            }

            $html = preg_replace('/^```(?:html)?\s*/i', '', $html) ?? $html;
            $html = preg_replace('/\s*```\s*$/', '', $html) ?? $html;
            $html = trim($html);

            return [
                'success' => true,
                'html' => $html,
                ...$this->usageFields($data),
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
                ->withHeaders($this->buildHeaders($apiKey))
                ->post(self::API_BASE, $payload);

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
                    'message' => 'OpenRouter returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            $content = $this->extractChoiceText($data);
            if (!is_string($content) || $content === '') {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => null,
                    'message' => 'OpenRouter returned an empty response.',
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
                    ...$this->usageFields($data),
                    'message' => 'OpenRouter returned invalid Unlayer design JSON.',
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
                    ...$this->usageFields($data),
                    'message' => 'OpenRouter returned an invalid Unlayer design structure.',
                    'raw' => $data,
                ];
            }

            if (!isset($design['counters']) || (!is_array($design['counters']) && !is_object($design['counters']))) {
                $design['counters'] = [];
            }

            return [
                'success' => true,
                'design' => $design,
                ...$this->usageFields($data),
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

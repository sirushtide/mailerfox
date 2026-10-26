<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class AnthropicClient implements AiProviderInterface
{
    private const API_BASE = 'https://api.anthropic.com/v1/messages';
    private const DEFAULT_MODEL = 'claude-sonnet-5';

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

        $prefix = 'Anthropic request failed.';
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

    private function extractText(array $data): ?string
    {
        $content = $data['content'] ?? null;
        if (!is_array($content)) {
            return null;
        }

        $parts = [];
        foreach ($content as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                $parts[] = $block['text'];
            }
        }

        $text = trim(implode("\n", $parts));
        return $text !== '' ? $text : null;
    }

    private function extractTokens(array $data): ?int
    {
        $usage = $data['usage'] ?? null;
        if (!is_array($usage)) {
            return null;
        }

        $input = is_numeric($usage['input_tokens'] ?? null) ? (int) $usage['input_tokens'] : 0;
        $output = is_numeric($usage['output_tokens'] ?? null) ? (int) $usage['output_tokens'] : 0;

        if ($input > 0 || $output > 0) {
            return $input + $output;
        }

        return null;
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
            'max_tokens' => max(1, $maxTokens),
            'messages' => $apiMessages,
        ];

        if (is_string($systemPrompt) && trim($systemPrompt) !== '') {
            $payload['system'] = trim($systemPrompt);
        }

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $response = Http::timeout(120)
                ->retry(1, 200)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
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
                    'message' => 'Anthropic returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            if (is_array($data['error'] ?? null) && is_string($data['error']['message'] ?? null)) {
                return [
                    'success' => false,
                    'text' => null,
                    'tokens' => null,
                    'message' => (string) $data['error']['message'],
                    'raw' => $data,
                ];
            }

            $text = $this->extractText($data);
            if (!is_string($text) || $text === '') {
                return [
                    'success' => false,
                    'text' => null,
                    'tokens' => null,
                    'message' => 'Anthropic returned an empty response.',
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

    public function streamChat(string $apiKey, array $messages, int $maxTokens, ?string $model, ?string $systemPrompt, callable $onDelta): array
    {
        $model = $this->normalizeModel($model);

        $apiMessages = [];
        foreach ($messages as $message) {
            if (!is_array($message) || !is_string($message['role'] ?? null) || !is_string($message['content'] ?? null)) {
                continue;
            }
            $apiMessages[] = ['role' => $message['role'], 'content' => $message['content']];
        }

        if (empty($apiMessages)) {
            return ['success' => false, 'text' => null, 'tokens' => null, 'message' => 'No messages provided.', 'raw' => null];
        }

        $payload = [
            'model' => $model,
            'max_tokens' => max(1, $maxTokens),
            'messages' => $apiMessages,
            'stream' => true,
        ];

        if (is_string($systemPrompt) && trim($systemPrompt) !== '') {
            $payload['system'] = trim($systemPrompt);
        }

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $body = json_encode($payload);

            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'x-api-key: ' . $apiKey,
                        'anthropic-version: 2023-06-01',
                        'Content-Type: application/json',
                        'Accept: text/event-stream',
                    ],
                    'content' => $body,
                    'timeout' => 120,
                    'ignore_errors' => true,
                ],
            ]);

            $fp = @fopen(self::API_BASE, 'r', false, $ctx);
            if ($fp === false) {
                return ['success' => false, 'text' => null, 'tokens' => null, 'message' => 'Failed to open stream connection.', 'raw' => null];
            }

            $fullText = '';
            $tokens = null;
            $buffer = '';
            $currentEvent = '';

            while (!feof($fp)) {
                $chunk = fread($fp, 4096);
                if ($chunk === false || $chunk === '') {
                    continue;
                }
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    $rawLine = rtrim($line, "\r");

                    if ($rawLine === '') {
                        $currentEvent = '';
                        continue;
                    }

                    if (str_starts_with($rawLine, 'event: ')) {
                        $currentEvent = substr($rawLine, 7);
                        continue;
                    }

                    if (!str_starts_with($rawLine, 'data: ')) {
                        continue;
                    }

                    $data = substr($rawLine, 6);
                    $json = json_decode($data, true);
                    if (!is_array($json)) {
                        continue;
                    }

                    $type = $json['type'] ?? $currentEvent;

                    if ($type === 'error') {
                        fclose($fp);
                        $msg = is_array($json['error'] ?? null) && is_string($json['error']['message'] ?? null)
                            ? (string) $json['error']['message']
                            : 'Anthropic stream error.';
                        return ['success' => false, 'text' => null, 'tokens' => null, 'message' => $msg, 'raw' => $json];
                    }

                    if ($type === 'content_block_delta') {
                        $delta = $json['delta'] ?? null;
                        if (is_array($delta) && ($delta['type'] ?? null) === 'text_delta' && is_string($delta['text'] ?? null)) {
                            $text = $delta['text'];
                            $fullText .= $text;
                            $onDelta($text);
                        }
                    }

                    if ($type === 'message_delta') {
                        $usage = $json['usage'] ?? null;
                        if (is_array($usage) && isset($usage['output_tokens'])) {
                            $tokens = (int) ($tokens ?? 0) + (int) $usage['output_tokens'];
                        }
                    }

                    if ($type === 'message_start') {
                        $usage = $json['message']['usage'] ?? null;
                        if (is_array($usage) && isset($usage['input_tokens'])) {
                            $tokens = (int) $usage['input_tokens'];
                        }
                    }
                }
            }

            fclose($fp);

            $fullText = preg_replace('/^```\s*/i', '', $fullText) ?? $fullText;
            $fullText = preg_replace('/\s*```\s*$/', '', $fullText) ?? $fullText;
            $fullText = trim($fullText);

            if ($fullText === '') {
                return ['success' => false, 'text' => null, 'tokens' => $tokens, 'message' => 'Anthropic returned an empty response.', 'raw' => null];
            }

            return ['success' => true, 'text' => $fullText, 'tokens' => $tokens, 'message' => null, 'raw' => null];
        } catch (\Throwable $e) {
            return ['success' => false, 'text' => null, 'tokens' => null, 'message' => $e->getMessage(), 'raw' => null];
        }
    }

    public function generateTemplateHtml(string $apiKey, string $prompt, int $maxTokens = 1200, ?string $model = null): array
    {
        $prompt = trim($prompt);
        $model = $this->normalizeModel($model);

        $payload = [
            'model' => $model,
            'max_tokens' => max(1, $maxTokens),
            'system' => 'You generate email template HTML. Output ONLY valid HTML (no markdown, no code fences). Do NOT use inline base64 images (data:image/...). Use only publicly accessible image URLs.',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $response = Http::timeout(120)
                ->retry(1, 200)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
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
                    'message' => 'Anthropic returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            $html = $this->extractText($data);
            if (!is_string($html) || $html === '') {
                return [
                    'success' => false,
                    'html' => null,
                    'tokens' => null,
                    'message' => 'Anthropic returned an empty response.',
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
            'max_tokens' => max(1, $maxTokens),
            'system' => 'You generate email templates as Unlayer design JSON. Output ONLY valid JSON (no markdown, no code fences). The JSON must be a design object with keys: counters and body. The body must contain rows/columns/contents with proper Unlayer block types like heading, text, image, button, divider, spacer. Do NOT use inline base64 images (data:image/...). Use only publicly accessible image URLs.',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $response = Http::timeout(120)
                ->retry(1, 200)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
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
                    'message' => 'Anthropic returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            $content = $this->extractText($data);
            if (!is_string($content) || $content === '') {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => null,
                    'message' => 'Anthropic returned an empty response.',
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
                    'message' => 'Anthropic returned invalid Unlayer design JSON.',
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
                    'message' => 'Anthropic returned an invalid Unlayer design structure.',
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

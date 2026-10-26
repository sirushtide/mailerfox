<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIClient implements AiProviderInterface
{
    private function removeTrailingCommas(string $json): string
    {
        $out = $json;
        // Common model mistake: trailing commas before closing braces/brackets.
        $out = preg_replace('/,\s*}/', '}', $out) ?? $out;
        $out = preg_replace('/,\s*]/', ']', $out) ?? $out;
        return $out;
    }

    private function extractJsonObject(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        if (str_starts_with($text, '{') && str_ends_with($text, '}')) {
            return $text;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        return substr($text, $start, ($end - $start) + 1);
    }

    private function decodeJsonObject(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $fixed = $this->removeTrailingCommas($text);
        $decoded = json_decode($fixed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $extracted = $this->extractJsonObject($text);
        if (!is_string($extracted) || trim($extracted) === '') {
            return null;
        }

        $decoded = json_decode($extracted, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $extractedFixed = $this->removeTrailingCommas($extracted);
        $decoded = json_decode($extractedFixed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    private function extractStringValue(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);
            return $value !== '' ? $value : null;
        }

        if (!is_array($value)) {
            return null;
        }

        foreach (['value', 'text', 'output_text', 'content'] as $k) {
            if (is_string($value[$k] ?? null)) {
                $v = trim((string) $value[$k]);
                return $v !== '' ? $v : null;
            }
        }

        return null;
    }

    private function extractChoiceText(array $choice): array
    {
        $message = is_array($choice['message'] ?? null) ? $choice['message'] : [];
        $refusal = is_string($message['refusal'] ?? null) ? trim((string) $message['refusal']) : null;

        $content = $message['content'] ?? null;
        if (is_string($content)) {
            $text = trim($content);
            return ['text' => $text !== '' ? $text : null, 'refusal' => $refusal];
        }

        // Some models may return an array of content parts.
        if (is_array($content)) {
            $parts = [];
            foreach ($content as $part) {
                if (!is_array($part)) {
                    continue;
                }

                $type = is_string($part['type'] ?? null) ? $part['type'] : null;
                if ($type === 'text' || $type === 'output_text' || $type === null) {
                    foreach (['text', 'output_text', 'content', 'value'] as $k) {
                        $maybe = $this->extractStringValue($part[$k] ?? null);
                        if ($maybe !== null) {
                            $parts[] = $maybe;
                            continue 2;
                        }
                    }
                }

                // Some payloads may omit type.
                if ($type === null && is_string($part['text'] ?? null)) {
                    $parts[] = $part['text'];
                }
            }

            $text = trim(implode("\n", $parts));
            return ['text' => $text !== '' ? $text : null, 'refusal' => $refusal];
        }

        return ['text' => null, 'refusal' => $refusal];
    }

    private function extractTokenCounts(array $data): array
    {
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : null;
        if (!is_array($usage)) {
            return ['total' => null, 'input' => null, 'output' => null];
        }

        $prompt = is_numeric($usage['prompt_tokens'] ?? null) ? (int) $usage['prompt_tokens'] : null;
        $completion = is_numeric($usage['completion_tokens'] ?? null) ? (int) $usage['completion_tokens'] : null;
        $total = is_numeric($usage['total_tokens'] ?? null) ? (int) $usage['total_tokens'] : null;

        return [
            'total' => $total ?? (($prompt !== null || $completion !== null) ? (($prompt ?? 0) + ($completion ?? 0)) : null),
            'input' => $prompt,
            'output' => $completion,
        ];
    }

    private function extractTotalTokens(array $data): ?int
    {
        return $this->extractTokenCounts($data)['total'];
    }

    private function usesMaxCompletionTokens(string $model): bool
    {
        $model = strtolower(trim($model));
        if ($model === '') {
            return false;
        }

        return str_starts_with($model, 'gpt-5')
            || str_starts_with($model, 'o1')
            || str_starts_with($model, 'o3');
    }

    private function supportsReasoningEffort(string $model): bool
    {
        $model = strtolower(trim($model));
        if ($model === '') {
            return false;
        }

        return str_starts_with($model, 'gpt-5')
            || str_starts_with($model, 'o1')
            || str_starts_with($model, 'o3');
    }

    private function shouldOmitTemperature(string $model): bool
    {
        $model = strtolower(trim($model));
        if ($model === '') {
            return false;
        }

        return str_starts_with($model, 'gpt-5')
            || str_starts_with($model, 'o1')
            || str_starts_with($model, 'o3');
    }

    public function generateTemplateHtml(string $apiKey, string $prompt, int $maxTokens = 1200, ?string $model = null): array
    {
        $prompt = trim($prompt);
        $model = is_string($model) && trim($model) !== '' ? trim($model) : 'gpt-5-mini';

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You generate email template HTML. Output ONLY valid HTML (no markdown, no code fences). Do NOT use inline base64 images (data:image/...). Use only publicly accessible image URLs.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        if (!$this->shouldOmitTemperature($model)) {
            $payload['temperature'] = 0.7;
        }

        if ($this->usesMaxCompletionTokens($model)) {
            $payload['max_completion_tokens'] = max(1, $maxTokens);
        } else {
            $payload['max_tokens'] = max(1, $maxTokens);
        }

        if ($this->supportsReasoningEffort($model)) {
            $payload['reasoning_effort'] = 'low';
        }

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $response = Http::timeout(120)
                ->retry(1, 200)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorMessage = 'OpenAI request failed.';
                if (is_string($errorBody) && $errorBody !== '') {
                    $decodedError = json_decode($errorBody, true);
                    if (is_array($decodedError) && is_array($decodedError['error'] ?? null) && is_string($decodedError['error']['message'] ?? null)) {
                        $errorMessage = (string) $decodedError['error']['message'];
                    }
                }
                return [
                    'success' => false,
                    'html' => null,
                    'tokens' => null,
                    'message' => $errorMessage,
                    'raw' => [
                        'status' => $response->status(),
                        'body' => $errorBody,
                    ],
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'html' => null,
                    'tokens' => null,
                    'message' => 'OpenAI returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            if (is_array($data['error'] ?? null) && is_string($data['error']['message'] ?? null)) {
                return [
                    'success' => false,
                    'html' => null,
                    'tokens' => null,
                    'message' => (string) $data['error']['message'],
                    'raw' => $data,
                ];
            }

            $choice = is_array($data['choices'][0] ?? null) ? $data['choices'][0] : [];
            $extracted = $this->extractChoiceText($choice);
            $content = is_string($extracted['text'] ?? null) ? (string) $extracted['text'] : null;
            $refusal = is_string($extracted['refusal'] ?? null) ? (string) $extracted['refusal'] : null;

            $finishReason = is_string($choice['finish_reason'] ?? null) ? (string) $choice['finish_reason'] : null;

            if (!is_string($content) || $content === '') {
                Log::warning('OpenAI returned empty content', [
                    'model' => $model,
                    'finish_reason' => $finishReason,
                    'choice' => $choice,
                ]);
                return [
                    'success' => false,
                    'html' => null,
                    'tokens' => null,
                    'message' => $refusal !== null && $refusal !== ''
                        ? ('OpenAI refused the request: ' . $refusal)
                        : (
                            $finishReason === 'length' && $this->supportsReasoningEffort($model)
                                ? 'OpenAI hit the token limit before producing any visible output for this model. Please shorten your prompt, increase the token budget, or use gpt-4.1.'
                                : ('OpenAI returned an empty response.' . ($finishReason ? (' finish_reason=' . $finishReason) : ''))
                        ),
                    'raw' => $data,
                ];
            }

            $original = $content;
            $content = preg_replace('/^```(?:html)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```\s*$/', '', $content) ?? $content;
            $content = trim($content);

            if ($content === '') {
                $fallback = trim((string) $original);
                $looksLikeFenceOnly = preg_match('/^```(?:html)?\s*$/i', $fallback) === 1;

                if ($looksLikeFenceOnly) {
                    return [
                        'success' => false,
                        'html' => null,
                        'tokens' => null,
                        'message' => $finishReason === 'length'
                            ? 'OpenAI response was truncated due to token limit. Please shorten your prompt or increase the token budget.'
                            : 'OpenAI returned an empty response.',
                        'raw' => $data,
                    ];
                }

                $content = $fallback;
            }

            $tokens = $this->extractTotalTokens($data);

            return [
                'success' => true,
                'html' => $content,
                'tokens' => $tokens,
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

    public function generateJsonObject(string $apiKey, string $systemPrompt, string $userPrompt, int $maxTokens = 1200, ?string $model = null): array
    {
        $systemPrompt = trim($systemPrompt);
        $userPrompt = trim($userPrompt);
        $model = is_string($model) && trim($model) !== '' ? trim($model) : 'gpt-5-mini';

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt !== '' ? $systemPrompt : 'Output valid JSON only (no markdown, no code fences).',
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
        ];

        if (!$this->shouldOmitTemperature($model)) {
            $payload['temperature'] = 0.8;
        }

        if ($this->usesMaxCompletionTokens($model)) {
            $payload['max_completion_tokens'] = max(1, $maxTokens);
        } else {
            $payload['max_tokens'] = max(1, $maxTokens);
        }

        if ($this->supportsReasoningEffort($model)) {
            $payload['reasoning_effort'] = 'low';
        }

        $payloadWithJsonFormat = $payload;
        $payloadWithJsonFormat['response_format'] = ['type' => 'json_object'];

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $attempts = [$payloadWithJsonFormat, $payload];
            $response = null;
            foreach ($attempts as $i => $p) {
                $response = Http::timeout(120)
                    ->retry(1, 200)
                    ->withToken($apiKey)
                    ->post('https://api.openai.com/v1/chat/completions', $p);

                if ($response->successful()) {
                    break;
                }

                if ($i === 0 && $response->status() === 400) {
                    $maybe = $response->json();
                    $errMessage = is_array($maybe) && is_array($maybe['error'] ?? null) && is_string($maybe['error']['message'] ?? null)
                        ? strtolower((string) $maybe['error']['message'])
                        : '';
                    if ($errMessage !== '' && (str_contains($errMessage, 'response_format') || str_contains($errMessage, 'json') || str_contains($errMessage, 'schema'))) {
                        continue;
                    }
                }
            }

            if (!$response || !$response->successful()) {
                $tc = $response ? $this->extractTokenCounts($response->json() ?? []) : ['total' => null, 'input' => null, 'output' => null];
                $errorBody = $response ? $response->body() : null;
                $errorMessage = 'OpenAI request failed.';
                if (is_string($errorBody) && $errorBody !== '') {
                    $decodedError = json_decode($errorBody, true);
                    if (is_array($decodedError) && is_array($decodedError['error'] ?? null) && is_string($decodedError['error']['message'] ?? null)) {
                        $errorMessage = (string) $decodedError['error']['message'];
                    }
                }
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => $tc['total'],
                    'input_tokens' => $tc['input'],
                    'output_tokens' => $tc['output'],
                    'message' => $errorMessage,
                    'raw' => [
                        'status' => $response ? $response->status() : null,
                        'body' => $errorBody,
                    ],
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => null,
                    'input_tokens' => null,
                    'output_tokens' => null,
                    'message' => 'OpenAI returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            if (is_array($data['error'] ?? null) && is_string($data['error']['message'] ?? null)) {
                $tc = $this->extractTokenCounts($data);
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => $tc['total'],
                    'input_tokens' => $tc['input'],
                    'output_tokens' => $tc['output'],
                    'message' => (string) $data['error']['message'],
                    'raw' => $data,
                ];
            }

            $choice = is_array($data['choices'][0] ?? null) ? $data['choices'][0] : [];
            $extracted = $this->extractChoiceText($choice);
            $content = is_string($extracted['text'] ?? null) ? (string) $extracted['text'] : null;
            $refusal = is_string($extracted['refusal'] ?? null) ? (string) $extracted['refusal'] : null;

            $finishReason = is_string($choice['finish_reason'] ?? null) ? (string) $choice['finish_reason'] : null;
            $tc = $this->extractTokenCounts($data);

            if (!is_string($content) || $content === '') {
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => $tc['total'],
                    'input_tokens' => $tc['input'],
                    'output_tokens' => $tc['output'],
                    'message' => $refusal !== null && $refusal !== ''
                        ? ('OpenAI refused the request: ' . $refusal)
                        : (
                            $finishReason === 'length' && $this->supportsReasoningEffort($model)
                                ? 'OpenAI hit the token limit before producing any visible output for this model. Please shorten your prompt, increase the token budget, or use gpt-4.1.'
                                : ('OpenAI returned an empty response.' . ($finishReason ? (' finish_reason=' . $finishReason) : ''))
                        ),
                    'raw' => $data,
                ];
            }

            $original = $content;
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```\s*$/', '', $content) ?? $content;
            $content = trim($content);
            if ($content === '') {
                $content = trim((string) $original);
            }

            $decoded = $this->decodeJsonObject($content);
            if (!is_array($decoded)) {
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => $tc['total'],
                    'input_tokens' => $tc['input'],
                    'output_tokens' => $tc['output'],
                    'message' => $finishReason === 'length'
                        ? 'OpenAI response was truncated due to token limit. Please shorten your prompt or increase the token budget.'
                        : 'OpenAI returned invalid JSON.',
                    'raw' => $data,
                ];
            }

            return [
                'success' => true,
                'json' => $decoded,
                'tokens' => $tc['total'],
                'input_tokens' => $tc['input'],
                'output_tokens' => $tc['output'],
                'message' => null,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'json' => null,
                'tokens' => null,
                'input_tokens' => null,
                'output_tokens' => null,
                'message' => $e->getMessage(),
                'raw' => null,
            ];
        }
    }

    public function generateTemplateUnlayerDesign(string $apiKey, string $prompt, int $maxTokens = 1200, ?string $model = null): array
    {
        $prompt = trim($prompt);
        $model = is_string($model) && trim($model) !== '' ? trim($model) : 'gpt-5-mini';

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You generate email templates as Unlayer design JSON. Output ONLY valid JSON (no markdown, no code fences). The JSON must be a design object with keys: counters and body. The body must contain rows/columns/contents with proper Unlayer block types like heading, text, image, button, divider, spacer. Do NOT use inline base64 images (data:image/...). Use only publicly accessible image URLs.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        if (!$this->shouldOmitTemperature($model)) {
            $payload['temperature'] = 0.4;
        }

        if ($this->usesMaxCompletionTokens($model)) {
            $payload['max_completion_tokens'] = max(1, $maxTokens);
        } else {
            $payload['max_tokens'] = max(1, $maxTokens);
        }

        if ($this->supportsReasoningEffort($model)) {
            $payload['reasoning_effort'] = 'low';
        }

        $payloadWithJsonFormat = $payload;
        $payloadWithJsonFormat['response_format'] = ['type' => 'json_object'];

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }

            $attempts = [$payloadWithJsonFormat, $payload];
            $response = null;
            foreach ($attempts as $i => $p) {
                $response = Http::timeout(120)
                    ->retry(1, 200)
                    ->withToken($apiKey)
                    ->post('https://api.openai.com/v1/chat/completions', $p);

                if ($response->successful()) {
                    break;
                }

                if ($i === 0 && $response->status() === 400) {
                    $maybe = $response->json();
                    $errMessage = is_array($maybe) && is_array($maybe['error'] ?? null) && is_string($maybe['error']['message'] ?? null)
                        ? strtolower((string) $maybe['error']['message'])
                        : '';
                    if ($errMessage !== '' && (str_contains($errMessage, 'response_format') || str_contains($errMessage, 'json') || str_contains($errMessage, 'schema'))) {
                        continue;
                    }
                }
            }

            if (!$response || !$response->successful()) {
                $errorBody = $response ? $response->body() : null;
                $errorMessage = 'OpenAI request failed.';
                if (is_string($errorBody) && $errorBody !== '') {
                    $decodedError = json_decode($errorBody, true);
                    if (is_array($decodedError) && is_array($decodedError['error'] ?? null) && is_string($decodedError['error']['message'] ?? null)) {
                        $errorMessage = (string) $decodedError['error']['message'];
                    }
                }
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => null,
                    'message' => $errorMessage,
                    'raw' => [
                        'status' => $response ? $response->status() : null,
                        'body' => $errorBody,
                    ],
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => null,
                    'message' => 'OpenAI returned invalid JSON.',
                    'raw' => ['body' => $response->body()],
                ];
            }

            if (is_array($data['error'] ?? null) && is_string($data['error']['message'] ?? null)) {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => null,
                    'message' => (string) $data['error']['message'],
                    'raw' => $data,
                ];
            }

            $choice = is_array($data['choices'][0] ?? null) ? $data['choices'][0] : [];
            $extracted = $this->extractChoiceText($choice);
            $content = is_string($extracted['text'] ?? null) ? (string) $extracted['text'] : null;
            $refusal = is_string($extracted['refusal'] ?? null) ? (string) $extracted['refusal'] : null;

            $finishReason = is_string($choice['finish_reason'] ?? null) ? (string) $choice['finish_reason'] : null;

            if (!is_string($content) || $content === '') {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => null,
                    'message' => $refusal !== null && $refusal !== ''
                        ? ('OpenAI refused the request: ' . $refusal)
                        : ('OpenAI returned an empty response.' . ($finishReason ? (' finish_reason=' . $finishReason) : '')),
                    'raw' => $data,
                ];
            }

            $original = $content;
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```\s*$/', '', $content) ?? $content;
            $content = trim($content);
            if ($content === '') {
                $content = trim((string) $original);
            }

            $decoded = $this->decodeJsonObject($content);
            if (!is_array($decoded)) {
                $debugExcerpt = null;
                if (config('app.debug') && is_string($content) && $content !== '') {
                    $debugExcerpt = substr(str_replace(["\r", "\n"], [' ', ' '], $content), 0, 900);
                }

                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => $this->extractTotalTokens($data),
                    'message' => $finishReason === 'length'
                        ? 'OpenAI response was truncated due to token limit. Please shorten your prompt or increase the token budget.'
                        : ('OpenAI returned invalid Unlayer design JSON.' . ($debugExcerpt ? (' Output excerpt: ' . $debugExcerpt) : '')),
                    'raw' => $data,
                ];
            }

            $design = $decoded;
            if (!isset($design['body']) && (isset($design['rows']) || isset($design['values']))) {
                $design = [
                    'counters' => [],
                    'body' => $decoded,
                ];
            }

            if (!is_array($design['body'] ?? null)) {
                return [
                    'success' => false,
                    'design' => null,
                    'tokens' => $this->extractTotalTokens($data),
                    'message' => 'OpenAI returned an invalid Unlayer design structure.',
                    'raw' => $data,
                ];
            }

            if (!isset($design['counters']) || (!is_array($design['counters']) && !is_object($design['counters']))) {
                $design['counters'] = [];
            }

            return [
                'success' => true,
                'design' => $design,
                'tokens' => $this->extractTotalTokens($data),
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

    public function generateChat(string $apiKey, array $messages, int $maxTokens = 1200, ?string $model = null, ?string $systemPrompt = null): array
    {
        $model = is_string($model) && trim($model) !== '' ? trim($model) : 'gpt-5-mini';

        $apiMessages = [];
        if (is_string($systemPrompt) && trim($systemPrompt) !== '') {
            $apiMessages[] = ['role' => 'system', 'content' => trim($systemPrompt)];
        }
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
            'messages' => $apiMessages,
        ];

        if (!$this->shouldOmitTemperature($model)) {
            $payload['temperature'] = 0.7;
        }

        if ($this->usesMaxCompletionTokens($model)) {
            $payload['max_completion_tokens'] = max(1, $maxTokens);
        } else {
            $payload['max_tokens'] = max(1, $maxTokens);
        }

        if ($this->supportsReasoningEffort($model)) {
            $payload['reasoning_effort'] = 'low';
        }

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $response = Http::timeout(120)
                ->retry(1, 200)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorMessage = 'OpenAI request failed.';
                if (is_string($errorBody) && $errorBody !== '') {
                    $decodedError = json_decode($errorBody, true);
                    if (is_array($decodedError) && is_array($decodedError['error'] ?? null) && is_string($decodedError['error']['message'] ?? null)) {
                        $errorMessage = (string) $decodedError['error']['message'];
                    }
                }
                return [
                    'success' => false,
                    'text' => null,
                    'tokens' => null,
                    'message' => $errorMessage,
                    'raw' => [
                        'status' => $response->status(),
                        'body' => $errorBody,
                    ],
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'text' => null,
                    'tokens' => null,
                    'message' => 'OpenAI returned invalid JSON.',
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

            $choice = is_array($data['choices'][0] ?? null) ? $data['choices'][0] : [];
            $extracted = $this->extractChoiceText($choice);
            $content = is_string($extracted['text'] ?? null) ? (string) $extracted['text'] : null;
            $refusal = is_string($extracted['refusal'] ?? null) ? (string) $extracted['refusal'] : null;

            $finishReason = is_string($choice['finish_reason'] ?? null) ? (string) $choice['finish_reason'] : null;

            if (!is_string($content) || $content === '') {
                Log::warning('OpenAI returned empty content', [
                    'model' => $model,
                    'finish_reason' => $finishReason,
                    'choice' => $choice,
                ]);
                return [
                    'success' => false,
                    'text' => null,
                    'tokens' => null,
                    'message' => $refusal !== null && $refusal !== ''
                        ? ('OpenAI refused the request: ' . $refusal)
                        : (
                            $finishReason === 'length' && $this->supportsReasoningEffort($model)
                                ? 'OpenAI hit the token limit before producing any visible output for this model. Please shorten your prompt, increase the token budget, or use gpt-4.1.'
                                : ('OpenAI returned an empty response.' . ($finishReason ? (' finish_reason=' . $finishReason) : ''))
                        ),
                    'raw' => $data,
                ];
            }

            $content = preg_replace('/^```\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```\s*$/', '', $content) ?? $content;
            $content = trim($content);

            $tokens = $this->extractTotalTokens($data);

            return [
                'success' => true,
                'text' => $content,
                'tokens' => $tokens,
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

    public function streamChat(string $apiKey, array $messages, int $maxTokens, ?string $model, ?string $systemPrompt, callable $onDelta): array
    {
        $model = is_string($model) && trim($model) !== '' ? trim($model) : 'gpt-5-mini';

        $apiMessages = [];
        if (is_string($systemPrompt) && trim($systemPrompt) !== '') {
            $apiMessages[] = ['role' => 'system', 'content' => trim($systemPrompt)];
        }
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
            'messages' => $apiMessages,
            'stream' => true,
            'stream_options' => ['include_usage' => true],
        ];

        if (!$this->shouldOmitTemperature($model)) {
            $payload['temperature'] = 0.7;
        }

        if ($this->usesMaxCompletionTokens($model)) {
            $payload['max_completion_tokens'] = max(1, $maxTokens);
        } else {
            $payload['max_tokens'] = max(1, $maxTokens);
        }

        if ($this->supportsReasoningEffort($model)) {
            $payload['reasoning_effort'] = 'low';
        }

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $url = 'https://api.openai.com/v1/chat/completions';
            $body = json_encode($payload);

            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json',
                        'Accept: text/event-stream',
                    ],
                    'content' => $body,
                    'timeout' => 120,
                    'ignore_errors' => true,
                ],
            ]);

            $fp = @fopen($url, 'r', false, $ctx);
            if ($fp === false) {
                return ['success' => false, 'text' => null, 'tokens' => null, 'message' => 'Failed to open stream connection.', 'raw' => null];
            }

            $fullText = '';
            $tokens = null;
            $buffer = '';

            while (!feof($fp)) {
                $chunk = fread($fp, 4096);
                if ($chunk === false || $chunk === '') {
                    continue;
                }
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    $line = trim($line);

                    if ($line === '' || !str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $data = substr($line, 6);
                    if ($data === '[DONE]') {
                        continue 2;
                    }

                    $json = json_decode($data, true);
                    if (!is_array($json)) {
                        continue;
                    }

                    if (isset($json['error']['message'])) {
                        fclose($fp);
                        return ['success' => false, 'text' => null, 'tokens' => null, 'message' => (string) $json['error']['message'], 'raw' => $json];
                    }

                    $choice = $json['choices'][0] ?? null;
                    if (is_array($choice)) {
                        $delta = $choice['delta']['content'] ?? null;
                        if (is_string($delta) && $delta !== '') {
                            $fullText .= $delta;
                            $onDelta($delta);
                        }
                    }

                    $usage = $json['usage'] ?? null;
                    if (is_array($usage) && isset($usage['total_tokens'])) {
                        $tokens = (int) $usage['total_tokens'];
                    }
                }
            }

            fclose($fp);

            $fullText = preg_replace('/^```\s*/i', '', $fullText) ?? $fullText;
            $fullText = preg_replace('/\s*```\s*$/', '', $fullText) ?? $fullText;
            $fullText = trim($fullText);

            if ($fullText === '') {
                return ['success' => false, 'text' => null, 'tokens' => $tokens, 'message' => 'OpenAI returned an empty response.', 'raw' => null];
            }

            return ['success' => true, 'text' => $fullText, 'tokens' => $tokens, 'message' => null, 'raw' => null];
        } catch (\Throwable $e) {
            return ['success' => false, 'text' => null, 'tokens' => null, 'message' => $e->getMessage(), 'raw' => null];
        }
    }
}

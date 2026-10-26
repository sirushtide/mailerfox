<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class ClaudeClient
{
    private const DEFAULT_MODEL = 'claude-haiku-4-5-20251001';

    /**
     * Maps internal model aliases to actual Anthropic API model names.
     *
     * Per Anthropic docs (https://platform.claude.com/docs/en/about-claude/models/model-ids-and-versions):
     * - 4.6+ uses dateless IDs: claude-{name}-{major}-{minor}
     * - Pre-4.6 uses dated IDs: claude-{name}-{major}-{minor}-{YYYYMMDD}
     * - Short aliases (e.g. claude-sonnet-4-5) point to the latest dated snapshot.
     */
    private const MODEL_MAP = [
        'claude-haiku-4-5-20251001' => 'claude-haiku-4-5-20251001',
        'claude-sonnet-4-5' => 'claude-sonnet-4-5',
        'claude-sonnet-4-6' => 'claude-sonnet-4-6',
        'claude-opus-4-5' => 'claude-opus-4-6',
        'claude-opus-4-6' => 'claude-opus-4-6',
        'claude-opus-4-7' => 'claude-opus-4-7',
        'claude-opus-4-8' => 'claude-opus-4-8',
    ];

    private function resolveModel(string $model): string
    {
        $model = trim($model);
        if ($model === '') {
            return self::MODEL_MAP[self::DEFAULT_MODEL] ?? self::DEFAULT_MODEL;
        }
        return self::MODEL_MAP[$model] ?? $model;
    }

    private function removeTrailingCommas(string $json): string
    {
        $out = $json;
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

    private function normalizeModel(?string $model): string
    {
        $model = is_string($model) ? trim($model) : '';
        if ($model === '') {
            return $this->resolveModel(self::DEFAULT_MODEL);
        }

        return $this->resolveModel($model);
    }

    private function extractTokenCounts(array $data): array
    {
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : null;
        if (!is_array($usage)) {
            return ['total' => null, 'input' => null, 'output' => null];
        }

        $input = is_numeric($usage['input_tokens'] ?? null) ? (int) $usage['input_tokens'] : null;
        $output = is_numeric($usage['output_tokens'] ?? null) ? (int) $usage['output_tokens'] : null;

        return [
            'total' => ($input !== null && $output !== null) ? $input + $output : null,
            'input' => $input,
            'output' => $output,
        ];
    }

    public function generateJsonObject(string $apiKey, string $systemPrompt, string $userPrompt, int $maxTokens = 4096, ?string $model = null): array
    {
        $systemPrompt = trim($systemPrompt);
        $userPrompt = trim($userPrompt);
        $model = $this->normalizeModel($model);

        $system = $systemPrompt !== '' ? $systemPrompt : 'Output valid JSON only (no markdown, no code fences).';

        $payloads = [];
        $payloads[] = [
            'model' => $model,
            'max_tokens' => max(1, $maxTokens),
            'temperature' => 0.8,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
            'system' => $system,
        ];
        $payloads[] = [
            'model' => $model,
            'max_tokens' => max(1, $maxTokens),
            'temperature' => 0.8,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $system . "\n\n" . $userPrompt,
                ],
            ],
        ];

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $lastResponse = null;
            $lastPayload = null;
            foreach ($payloads as $payload) {
                $response = Http::timeout(120)
                    ->connectTimeout(15)
                    ->retry(1, 200)
                    ->withHeaders([
                        'x-api-key' => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.anthropic.com/v1/messages', $payload);

                $lastResponse = $response;
                $lastPayload = $payload;

                if ($response->successful()) {
                    break;
                }

                $status = $response->status();
                if ($status === 401 || $status === 403) {
                    break;
                }
            }

            if (!$lastResponse || !$lastResponse->successful()) {
                $status = $lastResponse ? $lastResponse->status() : null;
                $body = $lastResponse ? $lastResponse->body() : null;
                $message = 'Claude request failed' . ($status ? ' (HTTP ' . $status . ').' : '.');

                if ($lastResponse) {
                    $decoded = $lastResponse->json();
                    if (is_array($decoded)) {
                        if (is_array($decoded['error'] ?? null) && is_string($decoded['error']['message'] ?? null)) {
                            $message .= ' ' . trim((string) $decoded['error']['message']);
                        } elseif (is_string($decoded['message'] ?? null)) {
                            $message .= ' ' . trim((string) $decoded['message']);
                        } elseif (is_string($decoded['detail'] ?? null)) {
                            $message .= ' ' . trim((string) $decoded['detail']);
                        }
                    }
                    if ($body !== null && $message === 'Claude request failed (HTTP ' . $status . ').') {
                        $compact = trim(str_replace(["\r", "\n"], ' ', $body));
                        if ($compact !== '' && strlen($compact) < 500) {
                            $message .= ' ' . $compact;
                        }
                    }
                }

                $tc = $lastResponse ? $this->extractTokenCounts($lastResponse->json() ?? []) : ['total' => null, 'input' => null, 'output' => null];
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => $tc['total'],
                    'input_tokens' => $tc['input'],
                    'output_tokens' => $tc['output'],
                    'message' => $message,
                    'raw' => [
                        'status' => $status,
                        'body' => $body,
                    ],
                ];
            }

            $data = $lastResponse->json();
            $stopReason = is_string($data['stop_reason'] ?? null) ? (string) $data['stop_reason'] : null;

            // Retry with higher max_tokens if truncated (up to 16384)
            if ($stopReason === 'max_tokens' && $maxTokens < 16384 && $lastPayload !== null) {
                $retryMaxTokens = min($maxTokens * 2, 16384);
                $retryPayload = $lastPayload;
                $retryPayload['max_tokens'] = $retryMaxTokens;

                $retryResponse = Http::timeout(120)
                    ->connectTimeout(15)
                    ->retry(1, 200)
                    ->withHeaders([
                        'x-api-key' => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.anthropic.com/v1/messages', $retryPayload);

                if ($retryResponse->successful()) {
                    $retryData = $retryResponse->json();
                    $retryStopReason = is_string($retryData['stop_reason'] ?? null) ? (string) $retryData['stop_reason'] : null;
                    if ($retryStopReason !== 'max_tokens') {
                        $lastResponse = $retryResponse;
                        $data = $retryData;
                        $stopReason = $retryStopReason;
                    }
                }
            }
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => null,
                    'input_tokens' => null,
                    'output_tokens' => null,
                    'message' => 'Claude returned invalid JSON.',
                    'raw' => ['body' => $lastResponse->body()],
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

            $contentBlocks = $data['content'] ?? null;
            $text = null;
            if (is_array($contentBlocks)) {
                $texts = [];
                foreach ($contentBlocks as $block) {
                    if (is_array($block) && ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                        $texts[] = $block['text'];
                    }
                }
                $text = trim(implode("\n", $texts));
            }

            $stopReason = is_string($data['stop_reason'] ?? null) ? (string) $data['stop_reason'] : null;

            $tc = $this->extractTokenCounts($data);
            if (!is_string($text) || $text === '') {
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => $tc['total'],
                    'input_tokens' => $tc['input'],
                    'output_tokens' => $tc['output'],
                    'message' => $stopReason === 'max_tokens'
                        ? 'Claude response was truncated due to token limit. Please shorten your prompt or increase the token budget.'
                        : 'Claude returned an empty response.',
                    'raw' => $data,
                ];
            }

            $original = $text;
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
            $text = preg_replace('/\s*```\s*$/', '', $text) ?? $text;
            $text = trim($text);
            if ($text === '') {
                $text = trim((string) $original);
            }

            $decoded = $this->decodeJsonObject($text);
            if (!is_array($decoded)) {
                return [
                    'success' => false,
                    'json' => null,
                    'tokens' => $tc['total'],
                    'input_tokens' => $tc['input'],
                    'output_tokens' => $tc['output'],
                    'message' => $stopReason === 'max_tokens'
                        ? 'Claude response was truncated due to token limit. Please shorten your prompt or increase the token budget.'
                        : 'Claude returned invalid JSON.',
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
            $message = $e->getMessage();
            if (str_contains($message, 'cURL error 28') || str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                $message = 'The request to Claude timed out. This can happen with complex prompts or during high API load. Please try again or use a lighter model (e.g., Haiku).';
            }
            return [
                'success' => false,
                'json' => null,
                'tokens' => null,
                'input_tokens' => null,
                'output_tokens' => null,
                'message' => $message,
                'raw' => null,
            ];
        }
    }
}

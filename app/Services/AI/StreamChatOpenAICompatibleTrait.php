<?php

namespace App\Services\AI;

trait StreamChatOpenAICompatibleTrait
{
    protected function getStreamApiBase(): string
    {
        return defined('self::API_BASE') ? self::API_BASE : 'https://api.openai.com/v1/chat/completions';
    }

    public function streamChat(string $apiKey, array $messages, int $maxTokens, ?string $model, ?string $systemPrompt, callable $onDelta): array
    {
        $model = is_string($model) && trim($model) !== '' ? trim($model) : (defined('self::DEFAULT_MODEL') ? self::DEFAULT_MODEL : 'gpt-4o-mini');

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
            'max_tokens' => max(1, $maxTokens),
            'temperature' => 0.7,
        ];

        $apiBase = $this->getStreamApiBase();

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(180);
            }

            $body = json_encode($payload);

            $headers = [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: text/event-stream',
            ];

            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => $headers,
                    'content' => $body,
                    'timeout' => 120,
                    'ignore_errors' => true,
                ],
            ]);

            $fp = @fopen($apiBase, 'r', false, $ctx);
            if ($fp === false) {
                return ['success' => false, 'text' => null, 'tokens' => null, 'message' => 'Failed to open stream connection.', 'raw' => null];
            }

            $fullText = '';
            $tokens = null;
            $inputTokens = 0;
            $outputTokens = 0;
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
                    if (is_array($usage)) {
                        $inputTokens = is_numeric($usage['prompt_tokens'] ?? null) ? (int) $usage['prompt_tokens'] : $inputTokens;
                        $outputTokens = is_numeric($usage['completion_tokens'] ?? null) ? (int) $usage['completion_tokens'] : $outputTokens;
                    }
                }
            }

            fclose($fp);

            $fullText = preg_replace('/^```\s*/i', '', $fullText) ?? $fullText;
            $fullText = preg_replace('/\s*```\s*$/', '', $fullText) ?? $fullText;
            $fullText = trim($fullText);

            if ($fullText === '') {
                return ['success' => false, 'text' => null, 'tokens' => $tokens, 'message' => 'Empty response from provider.', 'raw' => null];
            }

            return ['success' => true, 'text' => $fullText, 'tokens' => $tokens, 'input_tokens' => $inputTokens, 'output_tokens' => $outputTokens, 'message' => null, 'raw' => null];
        } catch (\Throwable $e) {
            return ['success' => false, 'text' => null, 'tokens' => null, 'message' => $e->getMessage(), 'raw' => null];
        }
    }
}

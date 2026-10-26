<?php

namespace App\Services\AI;

interface AiProviderInterface
{
    /**
     * Generate plain text (email copy).
     *
     * @return array{success: bool, text: ?string, tokens: ?int, message: ?string, raw: mixed}
     */
    public function generateText(string $apiKey, string $prompt, int $maxTokens = 1200, ?string $model = null): array;

    /**
     * Generate a chat response from a message array.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @return array{success: bool, text: ?string, tokens: ?int, message: ?string, raw: mixed}
     */
    public function generateChat(string $apiKey, array $messages, int $maxTokens = 1200, ?string $model = null, ?string $systemPrompt = null): array;

    /**
     * Stream a chat response token-by-token via a callback.
     *
     * The callback receives each text delta as it arrives.
     * Returns the full text and token count when done.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param callable(string $delta): void $onDelta
     * @return array{success: bool, text: ?string, tokens: ?int, message: ?string, raw: mixed}
     */
    public function streamChat(string $apiKey, array $messages, int $maxTokens, ?string $model, ?string $systemPrompt, callable $onDelta): array;

    /**
     * Generate email template HTML.
     *
     * @return array{success: bool, html: ?string, tokens: ?int, message: ?string, raw: mixed}
     */
    public function generateTemplateHtml(string $apiKey, string $prompt, int $maxTokens = 1200, ?string $model = null): array;

    /**
     * Generate Unlayer design JSON.
     *
     * @return array{success: bool, design: ?array, tokens: ?int, message: ?string, raw: mixed}
     */
    public function generateTemplateUnlayerDesign(string $apiKey, string $prompt, int $maxTokens = 1200, ?string $model = null): array;
}

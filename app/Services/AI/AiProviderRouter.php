<?php

namespace App\Services\AI;

use App\Models\Customer;
use App\Models\Setting;

class AiProviderRouter
{
    private ?\Closure $stepCallback = null;

    public function onStep(\Closure $callback): void
    {
        $this->stepCallback = $callback;
    }

    public function clearStepCallback(): void
    {
        $this->stepCallback = null;
    }

    public function resolveProvider(string $model): string
    {
        return AiProviderRegistry::detectProvider($model);
    }

    public function resolveApiKey(string $provider, ?Customer $customer = null): ?string
    {
        $details = $this->resolveKeyDetails($provider, $customer);

        return $details['key'];
    }

    public function resolveKeyDetails(string $provider, ?Customer $customer = null): array
    {
        $provider = strtolower(trim($provider));
        if (! AiProviderRegistry::exists($provider)) {
            return ['key' => null, 'uses_admin_keys' => false];
        }

        if ($customer !== null) {
            $customerField = AiProviderRegistry::customerField($provider);
            $ownKey = is_string($customer->{$customerField} ?? null) ? trim((string) $customer->{$customerField}) : '';
            if ($ownKey !== '') {
                return ['key' => $ownKey, 'uses_admin_keys' => false];
            }
        }

        $settingKey = AiProviderRegistry::settingKey($provider);
        $adminKey = is_string(Setting::get($settingKey)) ? trim((string) Setting::get($settingKey)) : '';

        return ['key' => $adminKey !== '' ? $adminKey : null, 'uses_admin_keys' => true];
    }

    public function isProviderEnabled(string $provider): bool
    {
        $provider = strtolower(trim($provider));

        if (! AiProviderRegistry::exists($provider)) {
            return false;
        }

        return filter_var(Setting::get('ai_provider_enabled_' . $provider, 1), FILTER_VALIDATE_BOOLEAN);
    }

    public function isModelEnabled(string $provider, ?string $model = null): bool
    {
        $provider = strtolower(trim($provider));

        if (! $this->isProviderEnabled($provider)) {
            return false;
        }

        $model = is_string($model) ? trim($model) : '';
        if ($model === '') {
            return true;
        }

        return filter_var(Setting::get($this->modelEnabledKey($provider, $model), 1), FILTER_VALIDATE_BOOLEAN);
    }

    public function availabilityMessage(string $model): ?string
    {
        $provider = $this->resolveProvider($model);

        if (! $this->isProviderEnabled($provider)) {
            return AiProviderRegistry::label($provider) . ' is disabled in AI settings.';
        }

        if (! $this->isModelEnabled($provider, $model)) {
            return trim($model) . ' is disabled in AI settings.';
        }

        return null;
    }

    public function hasCredentials(string $model): bool
    {
        $provider = $this->resolveProvider($model);

        if ($this->availabilityMessage($model) !== null) {
            return false;
        }

        return $this->resolveApiKey($provider) !== null;
    }

    public function generateText(string $model, string $prompt, int $maxTokens = 1200, ?Customer $customer = null): array
    {
        return $this->callClient('generateText', $model, $customer, $prompt, $maxTokens, $model);
    }

    public function generateChat(string $model, array $messages, int $maxTokens = 1200, ?string $systemPrompt = null, ?Customer $customer = null): array
    {
        return $this->callClient('generateChat', $model, $customer, $messages, $maxTokens, $model, $systemPrompt);
    }

    public function streamChat(string $model, array $messages, int $maxTokens, ?string $systemPrompt, callable $onDelta, ?Customer $customer = null): array
    {
        return $this->callClient('streamChat', $model, $customer, $messages, $maxTokens, $model, $systemPrompt, $onDelta);
    }

    public function generateJsonObject(string $model, string $systemPrompt, string $userPrompt, int $maxTokens = 1200, ?string $stepName = null): array
    {
        $provider = $this->resolveProvider($model);

        $availabilityMessage = $this->availabilityMessage($model);
        if ($availabilityMessage !== null) {
            return [
                'success' => false,
                'json' => null,
                'tokens' => null,
                'message' => $availabilityMessage,
                'raw' => null,
            ];
        }

        $apiKey = $this->resolveApiKey($provider);

        if ($apiKey === null) {
            return [
                'success' => false,
                'json' => null,
                'tokens' => null,
                'message' => ucfirst($provider) . ' API key is not configured.',
                'raw' => null,
            ];
        }

        $client = AiProviderFactory::make($provider);

        if (method_exists($client, 'generateJsonObject')) {
            $result = $client->generateJsonObject($apiKey, $systemPrompt, $userPrompt, $maxTokens, $model);
        } else {
            $result = $this->generateJsonObjectViaChat($client, $apiKey, $systemPrompt, $userPrompt, $maxTokens, $model);
        }

        if ($this->stepCallback !== null) {
            ($this->stepCallback)([
                'step_name' => $stepName,
                'model' => $model,
                'provider' => $provider,
                'success' => $result['success'] ?? false,
                'tokens' => is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : 0,
                'input_tokens' => is_numeric($result['input_tokens'] ?? null) ? (int) $result['input_tokens'] : 0,
                'output_tokens' => is_numeric($result['output_tokens'] ?? null) ? (int) $result['output_tokens'] : 0,
                'error_message' => ($result['success'] ?? false) ? null : ($result['message'] ?? null),
            ]);
        }

        return $result;
    }

    public function generateTemplateHtml(string $model, string $prompt, int $maxTokens = 1200, ?Customer $customer = null): array
    {
        return $this->callClient('generateTemplateHtml', $model, $customer, $prompt, $maxTokens, $model);
    }

    public function generateTemplateUnlayerDesign(string $model, string $prompt, int $maxTokens = 1200, ?Customer $customer = null): array
    {
        return $this->callClient('generateTemplateUnlayerDesign', $model, $customer, $prompt, $maxTokens, $model);
    }

    private function callClient(string $method, string $model, ?Customer $customer, ...$args): array
    {
        $provider = $this->resolveProvider($model);

        $availabilityMessage = $this->availabilityMessage($model);
        if ($availabilityMessage !== null) {
            return [
                'success' => false,
                'message' => $availabilityMessage,
            ];
        }

        $apiKey = $this->resolveApiKey($provider, $customer);

        if ($apiKey === null) {
            return [
                'success' => false,
                'message' => ucfirst($provider) . ' API key is not configured.',
            ];
        }

        $client = AiProviderFactory::make($provider);
        if (! method_exists($client, $method)) {
            return [
                'success' => false,
                'message' => "Provider {$provider} does not support {$method}.",
            ];
        }

        $result = $client->{$method}($apiKey, ...$args);

        if ($this->stepCallback !== null && is_array($result)) {
            ($this->stepCallback)([
                'step_name' => 'AI Chat',
                'model' => $model,
                'provider' => $provider,
                'success' => $result['success'] ?? false,
                'tokens' => is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : 0,
                'input_tokens' => is_numeric($result['input_tokens'] ?? null) ? (int) $result['input_tokens'] : 0,
                'output_tokens' => is_numeric($result['output_tokens'] ?? null) ? (int) $result['output_tokens'] : 0,
                'error_message' => ($result['success'] ?? false) ? null : ($result['message'] ?? null),
            ]);
        }

        return $result;
    }

    private function modelEnabledKey(string $provider, string $model): string
    {
        return 'ai_model_enabled_' . $provider . '_' . $this->modelKeySlug($model);
    }

    private function modelKeySlug(string $model): string
    {
        $model = strtolower(trim($model));
        $model = preg_replace('/[^a-z0-9]+/', '_', $model) ?? $model;

        return trim($model, '_');
    }

    private function generateJsonObjectViaChat(object $client, string $apiKey, string $systemPrompt, string $userPrompt, int $maxTokens, string $model): array
    {
        $systemPrompt = trim($systemPrompt);
        $jsonInstruction = 'CRITICAL JSON RULE: You must respond with exactly one valid JSON object and nothing else. Do not wrap it in markdown code fences (```). Do not add comments, explanations, or text before or after the JSON. Make sure every opening brace, bracket, quote, and colon is properly matched and closed.';

        $system = $systemPrompt !== '' ? $jsonInstruction . "\n\n" . $systemPrompt : $jsonInstruction;

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $result = $client->generateChat($apiKey, $messages, $maxTokens, $model, null);

        if (! is_array($result) || ! ($result['success'] ?? false)) {
            return [
                'success' => false,
                'json' => null,
                'tokens' => $result['tokens'] ?? null,
                'message' => is_array($result) && is_string($result['message'] ?? null) ? $result['message'] : 'AI generation failed.',
                'raw' => $result['raw'] ?? null,
            ];
        }

        $text = is_string($result['text'] ?? null) ? trim($result['text']) : '';
        $json = $this->extractJsonObject($text);

        if ($json !== null) {
            return [
                'success' => true,
                'json' => $json,
                'tokens' => $result['tokens'] ?? null,
                'message' => null,
                'raw' => $text,
            ];
        }

        // Retry once with a stricter, simpler prompt that omits the original system context
        // so the model focuses only on producing valid JSON.
        $retryMessages = [
            ['role' => 'system', 'content' => $jsonInstruction . "\n\nYour previous response was not valid JSON. Respond with ONLY a valid JSON object. No markdown, no explanation, no extra text."],
            ['role' => 'user', 'content' => "Return a valid JSON object for this request:\n\n" . $userPrompt],
        ];

        $retryResult = $client->generateChat($apiKey, $retryMessages, $maxTokens, $model, null);

        if (! is_array($retryResult) || ! ($retryResult['success'] ?? false)) {
            return [
                'success' => false,
                'json' => null,
                'tokens' => $result['tokens'] ?? null,
                'message' => 'AI response did not contain a valid JSON object.',
                'raw' => $text,
            ];
        }

        $retryText = is_string($retryResult['text'] ?? null) ? trim($retryResult['text']) : '';
        $retryJson = $this->extractJsonObject($retryText);

        if ($retryJson === null) {
            return [
                'success' => false,
                'json' => null,
                'tokens' => $result['tokens'] ?? null,
                'message' => 'AI response did not contain a valid JSON object after retry.',
                'raw' => $text,
            ];
        }

        return [
            'success' => true,
            'json' => $retryJson,
            'tokens' => $retryResult['tokens'] ?? null,
            'message' => null,
            'raw' => $retryText,
        ];
    }

    private function extractJsonObject(string $text): ?array
    {
        $text = $this->sanitizeJsonText($text);
        if ($text === '') {
            return null;
        }

        // Try direct decode first
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try removing markdown code fences
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $decoded = json_decode($this->sanitizeJsonText($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Try extracting first JSON object (handles text before/after)
        if (preg_match('/(\{[\s\S]*\})/', $text, $matches)) {
            $decoded = json_decode($this->sanitizeJsonText($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Try fixing common JSON errors: trailing commas
        $fixed = $this->sanitizeJsonText(preg_replace('/,\s*}/', '}', $text) ?? $text);
        $fixed = preg_replace('/,\s*]/', ']', $fixed) ?? $fixed;
        $decoded = json_decode($fixed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try combining fence removal with trailing comma fix
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $inner = $this->sanitizeJsonText($matches[1]);
            $innerFixed = preg_replace('/,\s*}/', '}', $inner) ?? $inner;
            $innerFixed = preg_replace('/,\s*]/', ']', $innerFixed) ?? $innerFixed;
            $decoded = json_decode($innerFixed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Try extracting JSON object with trailing comma fix
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

        // Strip line comments (// ...) and block comments (/* ... */).
        // IMPORTANT: only strip "//" comments that begin a line, never mid-string,
        // otherwise URLs like "https://..." inside JSON string values get destroyed
        // and the payload becomes invalid JSON.
        $text = preg_replace('/\/\*[\s\S]*?\*\//', '', $text) ?? $text;
        $text = preg_replace('/^\s*\/\/[^\r\n]*/m', '', $text) ?? $text;

        // Strip common LLM reasoning/thinking tags so JSON can be found afterwards
        $text = preg_replace('/<think(?:ing)?[^>]*>[\s\S]*?<\/think(?:ing)?>/i', '', $text) ?? $text;
        $text = preg_replace('/<reason(?:ing)?[^>]*>[\s\S]*?<\/reason(?:ing)?>/i', '', $text) ?? $text;

        return trim($text);
    }
}

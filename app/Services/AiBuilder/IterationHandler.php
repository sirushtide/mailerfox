<?php

namespace App\Services\AiBuilder;

use App\Services\AI\AiProviderRouter;

class IterationHandler
{
    public function __construct(
        protected AiProviderRouter $router,
    ) {
    }

    public function iterate(array $currentPage, string $changeRequest, string $mode = 'page'): array
    {
        $model = ModelRouter::resolve('iteration');
        if (!$this->router->hasCredentials($model)) {
            throw new \RuntimeException('AI API key is not configured for the selected provider.');
        }

        $system = implode("\n", [
            'You are a page iteration assistant. Given a current builder JSON and a user change request, output the minimal updated builder JSON.',
            'Output ONLY valid JSON (no markdown, no code fences).',
            'Preserve all untouched sections verbatim. Only modify sections that need to change.',
            'Schema: {"builder": "mailpurse", "version": 1, "meta": object, "style": object, "sections": [section objects]}',
        ]);

        $user = "Current page:\n" . json_encode($currentPage, JSON_PRETTY_PRINT) . "\n\nChange request: {$changeRequest}";

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $user,
            4000,
            'Iteration Handler'
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            $msg = is_string($result['message'] ?? null) ? (string) $result['message'] : 'Iteration failed.';
            throw new \RuntimeException($msg);
        }

        $decoded = is_array($result['json'] ?? null) ? $result['json'] : null;
        if (!is_array($decoded)) {
            throw new \RuntimeException('Iteration handler returned invalid JSON.');
        }

        $decoded['builder'] = 'mailpurse';
        $decoded['version'] = 1;

        $validator = new Validator();
        return $validator->validatePage($decoded, $mode);
    }
}

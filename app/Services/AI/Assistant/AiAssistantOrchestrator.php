<?php

namespace App\Services\AI\Assistant;

use App\Models\AiAgent;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\AI\AiProviderRouter;

/**
 * Orchestrates the AI Assistant. Uses the existing provider router and a
 * JSON "plan" protocol (reply + optional tool calls) so it works across every
 * configured provider, then produces a final answer.
 */
class AiAssistantOrchestrator
{
    public function __construct(
        protected AiProviderRouter $router,
        protected AiToolRegistry $tools,
    ) {
    }

    /**
     * @param array<int, array{role: string, content: string}> $history
     * @param array<string, mixed> $context
     * @return array{reply: string, tool_runs: array, model: string, success: bool, error: ?string}
     */
    public function respond(
        Customer $customer,
        ?string $model,
        array $history,
        string $userMessage,
        array $context = [],
        ?AiAgent $agent = null
    ): array {
        $model = $this->resolveModel($model, $agent);

        $system = $this->buildSystemPrompt($agent, $context);

        $plan = $this->router->generateJsonObject(
            $model,
            $system,
            $this->transcript($history, $userMessage),
            1500,
            'AI Assistant Plan'
        );

        $toolRuns = [];
        $reply = '';

        if ($plan['success'] ?? false) {
            $json = $plan['json'] ?? [];
            $reply = trim((string) ($json['reply'] ?? ''));
            $toolCalls = is_array($json['tool_calls'] ?? null) ? $json['tool_calls'] : [];

            foreach ($toolCalls as $call) {
                $name = is_string($call['name'] ?? null) ? $call['name'] : '';
                if ($name === '' || !$this->tools->has($name)) {
                    continue;
                }

                $args = is_array($call['arguments'] ?? null) ? $call['arguments'] : [];
                $result = $this->tools->run($name, $args, $customer);
                $toolRuns[] = ['tool' => $name, 'arguments' => $args, 'result' => $result];
            }

            if ($toolRuns !== []) {
                $reply = $this->finalAnswer($model, $history, $userMessage, $toolRuns, $reply, $system);
            }

            if ($reply === '') {
                $reply = __('I completed the requested action.');
            }

            return [
                'reply' => $reply,
                'tool_runs' => $toolRuns,
                'model' => $model,
                'success' => true,
                'error' => null,
            ];
        }

        return [
            'reply' => '',
            'tool_runs' => [],
            'model' => $model,
            'success' => false,
            'error' => $plan['message'] ?? __('AI generation failed.'),
        ];
    }

    protected function resolveModel(?string $model, ?AiAgent $agent): string
    {
        $model = trim((string) $model);
        if ($model !== '') {
            return $model;
        }

        if ($agent && is_string($agent->model) && trim($agent->model) !== '') {
            return trim($agent->model);
        }

        $setting = trim((string) Setting::get('ai_assistant_default_model', ''));

        return $setting !== '' ? $setting : 'gpt-4o-mini';
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function buildSystemPrompt(?AiAgent $agent, array $context): string
    {
        $instructions = $agent && is_string($agent->instructions) && trim($agent->instructions) !== ''
            ? trim($agent->instructions)
            : 'You are the MailPurse AI Assistant, an expert email marketing and omnichannel messaging copilot.';

        $format = <<<'TXT'
Respond with a single valid JSON object and nothing else:
{
  "reply": "your answer to the user (plain text)",
  "tool_calls": [ { "name": "tool_name", "arguments": { } } ]
}
Only include tool_calls when an action is genuinely required. If no action is needed, use an empty array.
TXT;

        $contextJson = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return implode("\n\n", [
            $instructions,
            "Available tools:\n" . $this->tools->describe(),
            $format,
            'Current context: ' . ($contextJson ?: '{}'),
        ]);
    }

    /**
     * @param array<int, array{role: string, content: string}> $history
     */
    protected function transcript(array $history, string $userMessage): string
    {
        $lines = [];
        foreach ($history as $message) {
            $role = ucfirst((string) ($message['role'] ?? 'user'));
            $lines[] = "{$role}: " . (string) ($message['content'] ?? '');
        }
        $lines[] = 'User: ' . $userMessage;

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array{role: string, content: string}> $history
     * @param array<int, array<string, mixed>> $toolRuns
     */
    protected function finalAnswer(string $model, array $history, string $userMessage, array $toolRuns, string $draft, string $system): string
    {
        $messages = [['role' => 'system', 'content' => $system]];
        foreach ($history as $message) {
            $messages[] = ['role' => (string) ($message['role'] ?? 'user'), 'content' => (string) ($message['content'] ?? '')];
        }

        $results = json_encode(array_map(static fn ($run) => [
            'tool' => $run['tool'],
            'result' => $run['result'],
        ], $toolRuns), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $messages[] = ['role' => 'user', 'content' => $userMessage];
        $messages[] = ['role' => 'assistant', 'content' => $draft !== '' ? $draft : 'Executing requested tools.'];
        $messages[] = ['role' => 'user', 'content' => 'Tool results: ' . $results . "\n\nNow give the final plain-text answer for the user. Do not output JSON."];

        $final = $this->router->generateChat($model, $messages, 1200, null, null);

        if (($final['success'] ?? false) && is_string($final['text'] ?? null) && trim($final['text']) !== '') {
            return trim($final['text']);
        }

        return $draft;
    }
}

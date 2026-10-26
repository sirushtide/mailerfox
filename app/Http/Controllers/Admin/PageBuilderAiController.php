<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBuilderMessage;
use App\Services\PageBuilderAi\PageBuilderAiModelStrategy;
use App\Services\PageBuilderAi\PageBuilderAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PageBuilderAiController extends Controller
{
    public function models(PageBuilderAiModelStrategy $strategies): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $strategies->catalog()]);
    }

    public function message(Request $request, int $page, PageBuilderAiService $service): Response
    {
        $validated = $request->validate([
            'request_id' => ['nullable', 'uuid'],
            'operation' => ['required', 'string', 'in:page,section,iteration'],
            'prompt' => ['nullable', 'string', 'max:12000'],
            'change_request' => ['nullable', 'string', 'max:12000'],
            'model' => ['nullable', 'string', 'max:160'],
            'section_index' => ['nullable', 'integer', 'min:0'],
            'page' => ['nullable', 'array'],
            'selected_section' => ['nullable', 'array'],
            'strategy' => ['nullable', 'string', 'in:auto,fast,quality,balanced'],
            'stream' => ['nullable', 'boolean'],
            'mode' => ['nullable', 'string', 'in:page,email'],
        ]);

        $validated['request_id'] = $validated['request_id'] ?? (string) Str::uuid();
        $validated['page_id'] = $page;

        if (!empty($validated['stream'])) {
            return $this->streamResponse($page, $service, $validated);
        }

        try {
            $data = $service->message($validated);
            $this->persist($page, $validated, $data);
        } catch (\Throwable $e) {
            $this->persistError($page, $validated, $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    private function streamResponse(int $page, PageBuilderAiService $service, array $validated): StreamedResponse
    {
        return new StreamedResponse(function () use ($page, $service, $validated) {
            $errorSent = false;

            $this->sendSse([
                'event' => 'connected',
                'data' => ['request_id' => $validated['request_id']],
            ]);

            try {
                $data = $service->message($validated, function (array $event) use (&$errorSent) {
                    if (($event['event'] ?? '') === 'error') {
                        $errorSent = true;
                    }
                    $this->sendSse($event);
                    if (connection_aborted()) {
                        throw new \RuntimeException('Client disconnected.');
                    }
                });

                $this->persist($page, $validated, $data);
            } catch (\Throwable $e) {
                $this->persistError($page, $validated, $e->getMessage());
                if ($e->getMessage() === 'Client disconnected.') {
                    return;
                }
                if (!$errorSent) {
                    $this->sendSse([
                        'event' => 'error',
                        'data' => ['message' => $e->getMessage()],
                    ]);
                }
            }
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function sendSse(array $payload): void
    {
        echo 'data: ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function persist(int $page, array $validated, array $data): void
    {
        $scope = (string) ($validated['operation'] ?? 'section');
        $model = is_string($data['model'] ?? null) ? $data['model'] : null;
        $resultType = is_array($data['result'] ?? null) ? ($data['result']['type'] ?? null) : null;
        $result = is_array($data['result'] ?? null) ? $data['result'] : [];
        $prompt = trim((string) ($validated['prompt'] ?? $validated['change_request'] ?? ''));

        if ($prompt !== '') {
            AiBuilderMessage::create([
                'page_id' => $page,
                'role' => 'user',
                'content' => $prompt,
                'scope' => $scope,
                'model' => $model,
                'status' => 'ok',
            ]);
        }

        $sectionData = match ($resultType) {
            'section' => $result['section'] ?? null,
            'page', 'pageset' => $result['page'] ?? null,
            default => null,
        };

        $name = '';
        if (is_array($sectionData)) {
            $name = trim((string) ($sectionData['name'] ?? ''));
        }
        $finalContent = match ($resultType) {
            'section' => 'Finished section' . ($name !== '' ? ' "' . $name . '"' : '') . '.',
            'page', 'pageset' => 'Finished the page layout' . ($name !== '' ? ' "' . $name . '"' : '') . '.',
            'clarification' => is_string($result['question'] ?? null) ? $result['question'] : 'I need a bit more detail to keep going.',
            default => 'Done.',
        };

        // Persist each streamed chat event in order so the reloaded rail matches the
        // live build: narrations, steps and the plan checklist first, then the final summary.
        foreach ($this->chatBlocks($data) as $block) {
            AiBuilderMessage::create(array_merge([
                'page_id' => $page,
                'role' => 'assistant',
                'scope' => $scope,
                'model' => $model,
                'status' => 'ok',
            ], $block));
        }

        AiBuilderMessage::create([
            'page_id' => $page,
            'role' => 'assistant',
            'type' => 'summary',
            'content' => $finalContent,
            'section_data' => $sectionData,
            'scope' => $scope,
            'model' => $model,
            'status' => 'ok',
            'tokens_used' => (int) ($data['tokens'] ?? 0),
        ]);
    }

    private function chatBlocks(array $data): array
    {
        $blocks = [];
        foreach (is_array($data['events'] ?? null) ? $data['events'] : [] as $event) {
            $name = $event['event'] ?? '';
            $payload = is_array($event['data'] ?? null) ? $event['data'] : [];

            if ($name === 'think') {
                $text = trim((string) ($payload['narration'] ?? ''));
                if ($text !== '') {
                    $blocks[] = ['type' => 'narration', 'content' => $text];
                }
            } elseif ($name === 'narrate') {
                $text = trim((string) ($payload['narration'] ?? ''));
                if ($text !== '') {
                    $blocks[] = ['type' => 'narration', 'content' => $text];
                }
            } elseif ($name === 'tool_call') {
                $blocks[] = [
                    'content' => $this->toolStepLabel(trim((string) ($payload['tool'] ?? ''))),
                ];
            } elseif ($name === 'plan') {
                $items = is_array($payload['items'] ?? null) ? array_values($payload['items']) : [];
                if (count($items) > 0) {
                    $blocks[] = [
                        'type' => 'plan',
                        'content' => (string) count($items),
                        'plan_data' => $items,
                    ];
                }
            } elseif ($name === 'clarification') {
                $question = trim((string) ($payload['question'] ?? ''));
                if ($question !== '') {
                    $blocks[] = ['type' => 'summary', 'content' => $question, 'status' => 'ok'];
                }
            }
        }

        return $blocks;
    }

    private function toolStepLabel(string $tool): string
    {
        $labels = [
            'find_element' => 'Finding best matching elements',
            'create_section' => 'Building section…',
            'create_page' => 'Assembling the page…',
            'update_section' => 'Updating section…',
            'insert_column' => 'Adding a column…',
            'update_column' => 'Updating column…',
            'remove_column' => 'Removing column…',
            'insert_element' => 'Adding an element…',
            'update_element' => 'Updating element…',
            'remove_element' => 'Removing an element…',
            'get_style_presets' => 'Loading style presets…',
        ];

        if (isset($labels[$tool]) && $labels[$tool] !== '') {
            return $labels[$tool];
        }

        $pretty = str_replace('_', ' ', $tool);

        return $pretty !== '' ? ucfirst($pretty) : 'Step';
    }

    private function persistError(int $page, array $validated, string $message): void
    {
        AiBuilderMessage::create([
            'page_id' => $page,
            'role' => 'assistant',
            'type' => 'error',
            'content' => $message,
            'scope' => (string) ($validated['operation'] ?? 'section'),
            'model' => is_string($validated['model'] ?? null) ? $validated['model'] : null,
            'status' => 'error',
        ]);
    }
}

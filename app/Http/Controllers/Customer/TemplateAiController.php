<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\PageBuilderAi\PageBuilderAiModelStrategy;
use App\Services\PageBuilderAi\PageBuilderAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TemplateAiController extends Controller
{
    public function __construct()
    {
        $this->middleware('customer.access:templates.permissions.can_use_ai_builder');
    }

    public function models(PageBuilderAiModelStrategy $strategies): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $strategies->catalog()]);
    }

    public function message(Request $request, PageBuilderAiService $service): Response
    {
        $validated = $request->validate([
            'request_id' => ['nullable', 'uuid'],
            'operation' => ['required', 'string', 'in:page,section,iteration'],
            'prompt' => ['nullable', 'string', 'max:12000'],
            'model' => ['nullable', 'string', 'max:160'],
            'section_index' => ['nullable', 'integer', 'min:0'],
            'page' => ['nullable', 'array'],
            'selected_section' => ['nullable', 'array'],
            'strategy' => ['nullable', 'string', 'in:auto,fast,quality,balanced'],
            'stream' => ['nullable', 'boolean'],
        ]);
        $validated['request_id'] = $validated['request_id'] ?? (string) Str::uuid();
        $validated['mode'] = 'email';

        // Customers cannot pick a model: always use the plan's selected default model.
        $planModel = (string) auth('customer')->user()?->groupSetting('templates.ai_builder.default_model', '');
        $validated['model'] = $planModel !== '' ? $planModel : null;
        $validated['strategy'] = 'auto';

        if (!$request->boolean('stream')) {
            return response()->json(['success' => true, 'data' => $service->message($validated)]);
        }

        return new StreamedResponse(function () use ($service, $validated) {
            echo 'data: ' . json_encode(['event' => 'connected', 'data' => ['request_id' => $validated['request_id']]]) . "\n\n";
            flush();
            try {
                $data = $service->message($validated, function (array $event): void {
                    echo 'data: ' . json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                });
                echo 'data: ' . json_encode(['event' => 'done', 'data' => ['result' => $data['result'] ?? null]]) . "\n\n";
                flush();
            } catch (\Throwable $e) {
                echo 'data: ' . json_encode(['event' => 'error', 'data' => ['message' => $e->getMessage()]]) . "\n\n";
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}

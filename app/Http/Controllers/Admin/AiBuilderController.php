<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\AiBuildPageJob;
use App\Models\AiGeneration;
use App\Models\AiGenerationStep;
use App\Models\Page;
use App\Services\AiBuilder\AiBuilderPipeline;
use App\Services\AiBuilder\AiProgressTracker;
use App\Services\AiBuilder\IntentDetector;
use App\Services\AiBuilder\ModelRouter;
use App\Services\AI\AiProviderRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiBuilderController extends Controller
{
    public function section(Request $request, Page $page, AiProviderRouter $router)
    {
        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:8000'],
            'screenshot' => ['nullable', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
            'model' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', 'string', 'in:section,full_page'],
        ]);

        $prompt = is_string($validated['prompt'] ?? null) ? trim((string) $validated['prompt']) : '';
        $modelParam = is_string($validated['model'] ?? null) ? trim((string) $validated['model']) : 'auto';
        $scope = is_string($validated['scope'] ?? null) ? (string) $validated['scope'] : 'section';

        // Intent gate: greetings, questions, and guidance requests get a conversational
        // reply instead of being forced through the section-building pipeline.
        $intentDetector = new IntentDetector($router);
        $intent = $intentDetector->detect($prompt, $modelParam);
        if ($intent['intent'] === 'chat') {
            return response()->json([
                'success' => true,
                'type' => 'chat',
                'message' => $intent['message'] ?? $intentDetector->chatReply($prompt),
            ]);
        }

        $screenshotUrl = null;
        if ($request->hasFile('screenshot')) {
            $screenshot = $request->file('screenshot');
            $path = $screenshot->store('pages/ai', 'public');
            $screenshotUrl = asset('storage/' . ltrim($path, '/'));
        }

        $admin = auth('admin')->user();
        $generation = AiGeneration::create([
            'admin_user_id' => $admin?->id,
            'tool' => 'ai_builder',
            'type' => $scope,
            'provider' => $router->resolveProvider($modelParam !== 'auto' ? $modelParam : 'gpt-4o-mini'),
            'model' => $modelParam !== 'auto' ? $modelParam : null,
            'used_admin_keys' => true,
            'prompt' => $prompt !== '' ? $prompt : '(no prompt)',
            'success' => false,
        ]);

        $totalTokens = 0;
        $router->onStep(function ($meta) use ($generation, &$totalTokens) {
            $tokens = $meta['tokens'] ?? 0;
            $inputTokens = $meta['input_tokens'] ?? 0;
            $outputTokens = $meta['output_tokens'] ?? 0;
            $totalTokens += $tokens;

            $costCents = ModelRouter::estimateCostCents($meta['model'], $inputTokens, $outputTokens, $tokens);

            AiGenerationStep::create([
                'ai_generation_id' => $generation->id,
                'step_name' => $meta['step_name'] ?? 'AI Generation',
                'provider' => $meta['provider'],
                'model' => $meta['model'],
                'tokens_used' => $tokens,
                'cost_cents' => $costCents,
                'status' => $meta['success'] ? 'completed' : 'failed',
                'error_message' => $meta['error_message'],
            ]);
        });

        $pipeline = new AiBuilderPipeline($router);

        try {
            if ($scope === 'full_page') {
                $jobId = Str::uuid()->toString();
                AiBuildPageJob::dispatch($jobId, $generation->id, $prompt, 'page', $screenshotUrl, $modelParam);

                return response()->json([
                    'success' => true,
                    'job_id' => $jobId,
                    'status' => 'queued',
                    'message' => 'Planning your design…',
                ]);
            }

            $result = $pipeline->generateSection($prompt, 'page', $screenshotUrl, $modelParam);
            $section = $result['section'];

            $generation->update([
                'success' => true,
                'tokens_used' => $totalTokens,
            ]);

            return response()->json([
                'success' => true,
                'section' => $section,
            ]);
        } catch (\Throwable $e) {
            $generation->update([
                'success' => false,
                'tokens_used' => $totalTokens,
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        } finally {
            $router->clearStepCallback();
        }
    }

    public function status(Request $request, string $jobId)
    {
        $data = AiProgressTracker::get($jobId);

        return response()->json([
            'success' => true,
            'status' => $data['status'] ?? 'unknown',
            'message' => $data['message'] ?? '',
            'result' => $data['result'] ?? null,
        ]);
    }

    public function iterate(Request $request, Page $page, AiProviderRouter $router)
    {
        $validated = $request->validate([
            'builder_data' => ['required', 'array'],
            'change_request' => ['required', 'string', 'max:2000'],
        ]);

        $builderData = $validated['builder_data'];
        $changeRequest = trim((string) $validated['change_request']);

        $admin = auth('admin')->user();
        $generation = AiGeneration::create([
            'admin_user_id' => $admin?->id,
            'tool' => 'ai_builder',
            'type' => 'iteration',
            'provider' => $router->resolveProvider('gpt-4o'),
            'model' => 'gpt-4o',
            'used_admin_keys' => true,
            'prompt' => $changeRequest,
            'success' => false,
        ]);

        $totalTokens = 0;
        $router->onStep(function ($meta) use ($generation, &$totalTokens) {
            $tokens = $meta['tokens'] ?? 0;
            $inputTokens = $meta['input_tokens'] ?? 0;
            $outputTokens = $meta['output_tokens'] ?? 0;
            $totalTokens += $tokens;

            $costCents = ModelRouter::estimateCostCents($meta['model'], $inputTokens, $outputTokens, $tokens);

            AiGenerationStep::create([
                'ai_generation_id' => $generation->id,
                'step_name' => $meta['step_name'] ?? 'AI Generation',
                'provider' => $meta['provider'],
                'model' => $meta['model'],
                'tokens_used' => $tokens,
                'cost_cents' => $costCents,
                'status' => $meta['success'] ? 'completed' : 'failed',
                'error_message' => $meta['error_message'],
            ]);
        });

        $pipeline = new AiBuilderPipeline($router);

        try {
            $updated = $pipeline->iterate($builderData, $changeRequest, 'page');

            $generation->update([
                'success' => true,
                'tokens_used' => $totalTokens,
            ]);

            return response()->json([
                'success' => true,
                'builder_data' => $updated,
            ]);
        } catch (\Throwable $e) {
            $generation->update([
                'success' => false,
                'tokens_used' => $totalTokens,
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        } finally {
            $router->clearStepCallback();
        }
    }
}

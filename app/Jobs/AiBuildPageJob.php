<?php

namespace App\Jobs;

use App\Models\AiGeneration;
use App\Models\AiGenerationStep;
use App\Services\AiBuilder\AiBuilderPipeline;
use App\Services\AiBuilder\AiProgressTracker;
use App\Services\AiBuilder\ModelRouter;
use App\Services\AI\AiProviderRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AiBuildPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $jobId,
        public int $generationId,
        public string $prompt,
        public string $mode = 'page',
        public ?string $screenshotUrl = null,
        public ?string $modelOverride = null,
    ) {
    }

    public function handle(AiProviderRouter $router): void
    {
        AiProgressTracker::setStatus($this->jobId, 'queued', 'Planning your design…');

        $generation = AiGeneration::find($this->generationId);
        $totalTokens = 0;

        $router->onStep(function ($meta) use ($generation, &$totalTokens) {
            if (!$generation) {
                return;
            }
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

        try {
            $pipeline = new AiBuilderPipeline($router);
            $pipeline->generatePageWithProgress(
                $this->jobId,
                $this->prompt,
                $this->mode,
                $this->screenshotUrl,
                $this->modelOverride
            );

            if ($generation) {
                $generation->update([
                    'success' => true,
                    'tokens_used' => $totalTokens,
                ]);
            }
        } catch (\Throwable $e) {
            if ($generation) {
                $generation->update([
                    'success' => false,
                    'tokens_used' => $totalTokens,
                    'error_message' => $e->getMessage(),
                ]);
            }
            throw $e;
        } finally {
            $router->clearStepCallback();
        }
    }

    public function failed(\Throwable $exception): void
    {
        AiProgressTracker::setError($this->jobId, $exception->getMessage());

        $generation = AiGeneration::find($this->generationId);
        if ($generation) {
            $generation->update([
                'success' => false,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}

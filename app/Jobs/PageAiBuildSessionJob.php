<?php

namespace App\Jobs;

use App\Models\AiBuilderSession;
use App\Models\AiGeneration;
use App\Models\AiGenerationStep;
use App\Models\Customer;
use App\Services\AiBuilder\AiBuilderCreditService;
use App\Services\AiBuilder\AiProgressTracker;
use App\Services\AiBuilder\ModelRouter;
use App\Services\AiBuilder\PageHtmlGenerator;
use App\Services\AiBuilder\ViteSandboxCompiler;
use App\Services\AI\AiProviderRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PageAiBuildSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $sessionId,
        public int $generationId,
        public string $prompt,
        public ?string $screenshotUrl = null,
        public string $modelParam = 'auto',
        public string $resolvedModel = 'gpt-4o-mini',
        public ?string $themeJson = null,
        public ?string $rawPrompt = null,
    ) {
    }

    public function handle(AiProviderRouter $router, ?ViteSandboxCompiler $sandboxCompiler = null): void
    {
        $sandboxCompiler ??= app(ViteSandboxCompiler::class);
        $generation = AiGeneration::find($this->generationId);
        $sessionModel = AiBuilderSession::where('session_id', $this->sessionId)->first();
        $totalTokens = 0;

        $router->onStep(function ($meta) use ($generation, &$totalTokens) {
            if (! $generation) {
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
            $frontendTheme = null;
            if (is_string($this->themeJson) && $this->themeJson !== '') {
                $decoded = json_decode($this->themeJson, true);
                if (is_array($decoded) && !empty($decoded['id'])) {
                    $frontendTheme = $decoded;
                }
            }

            // Functional auth in generated apps requires a connected Supabase
            // integration for this session's customer (or page). Without one,
            // the generator surfaces a clear error instead of shipping a login
            // gate nobody can get through.
            $supabaseConnected = false;
            $authProviders = null;
            if ($sessionModel) {
                $integration = $sessionModel->page_id && $sessionModel->page
                    ? $sessionModel->page->supabaseIntegration
                    : null;
                if (! $integration || $integration->status !== 'connected') {
                    $integration = $sessionModel->customer_id
                        ? \App\Models\SupabaseIntegration::where('customer_id', $sessionModel->customer_id)
                            ->where('status', 'connected')
                            ->latest()
                            ->first()
                        : null;
                }
                $supabaseConnected = $integration && $integration->status === 'connected';
                $authProviders = $integration?->auth_providers;
            }

            $pageHtmlGenerator = new PageHtmlGenerator($router);
            $result = $pageHtmlGenerator->generate($this->prompt, $this->screenshotUrl, $this->resolvedModel, $this->sessionId, $frontendTheme, $this->rawPrompt, $supabaseConnected, $authProviders);

            if ($generation) {
                $generation->update([
                    'success' => true,
                    'tokens_used' => $totalTokens,
                ]);
            }

            if (($result['type'] ?? 'page') === 'chat') {
                $payload = [
                    'success' => true,
                    'type' => 'chat',
                    'message' => $result['message'] ?? '',
                    'model_used' => $this->resolvedModel,
                    'tokens' => $totalTokens > 0 ? $totalTokens : null,
                ];

                if ($sessionModel) {
                    $messages = is_array($sessionModel->messages) ? $sessionModel->messages : [];
                    $messages[] = [
                        'id' => 'chat-' . now()->timestamp,
                        'role' => 'assistant',
                        'content' => $payload['message'],
                        'model' => $this->resolvedModel,
                        'status' => 'ok',
                        'created_at' => now()->toISOString(),
                    ];
                    $sessionModel->update([
                        'messages' => $messages,
                        'model' => $this->resolvedModel,
                    ]);
                }

                if ($sessionModel && $sessionModel->customer_id) {
                    $customer = Customer::find($sessionModel->customer_id);
                    if ($customer) {
                        app(AiBuilderCreditService::class)->deduct($customer, 'generate', $totalTokens);
                    }
                }

                AiProgressTracker::setDone($this->sessionId, $payload);
                return;
            }

            $projectFiles = is_array($result['project_files'] ?? null) ? $result['project_files'] : null;
            $compiledReact = null;

            if (($result['preview_mode'] ?? 'html') === 'react' && !empty($projectFiles)) {
                $compiledReact = $sandboxCompiler->compileWithAutoFix(
                    $this->sessionId,
                    $projectFiles,
                    $pageHtmlGenerator,
                    $this->resolvedModel,
                    true
                );
                if ($compiledReact['success']) {
                    $projectFiles = $compiledReact['project_files'];
                }
            }

            $isCompiledReact = is_array($compiledReact) && ($compiledReact['success'] ?? false) === true;
            $previewUrl = null;
            if ($isCompiledReact) {
                $routeName = $sessionModel?->customer_id
                    ? 'customer.ai-projects.builder.sandbox.preview'
                    : 'admin.ai-projects.page-ai-builder.sandbox.preview';
                $previewUrl = route($routeName, [
                    'session' => $this->sessionId,
                    'path' => 'index.html',
                ]);
            }

            $compiledPreviewHtml = $compiledReact['dist_index_html'] ?? '';
            if ($isCompiledReact && is_string($compiledPreviewHtml) && $compiledPreviewHtml !== '') {
                $sandboxBaseUrl = rtrim(str_replace('/index.html', '', $previewUrl), '/');
                $compiledPreviewHtml = preg_replace(
                    '/(src|href)="\.\/assets\//i',
                    '$1="' . $sandboxBaseUrl . '/assets/',
                    $compiledPreviewHtml
                );
            }

            $payload = [
                'success' => true,
                'type' => 'page',
                'html' => $result['html'],
                'preview_html' => $isCompiledReact
                    ? $compiledPreviewHtml
                    : ($result['preview_html'] ?? $result['html'] ?? ''),
                'react_code' => $result['react_code'] ?? null,
                'preview_mode' => $result['preview_mode'] ?? 'html',
                'title' => $result['title'] ?? 'Generated Page',
                'description' => $result['description'] ?? '',
                'model_used' => $this->resolvedModel,
                'tokens' => $totalTokens > 0 ? $totalTokens : null,
                'project_files' => $projectFiles,
                'preview_url' => $previewUrl,
                'build_log' => $isCompiledReact ? ($compiledReact['build_log'] ?? null) : null,
                'sandbox_session_id' => $this->sessionId,
            ];

            if ($sessionModel) {
                $messages = is_array($sessionModel->messages) ? $sessionModel->messages : [];
                $messages[] = [
                    'id' => 'ok-' . now()->timestamp,
                    'role' => 'assistant',
                    'content' => 'Generated "' . ($payload['title'] ?? 'Page') . '". Preview it on the right and save when ready.',
                    'html' => $payload['preview_html'] ?? '',
                    'model' => $this->resolvedModel,
                    'status' => 'ok',
                    'created_at' => now()->toISOString(),
                ];
                $sessionModel->update([
                    'messages' => $messages,
                    'preview_html' => $payload['preview_html'],
                    'project_files' => $payload['project_files'],
                    'react_code' => $payload['react_code'],
                    'preview_mode' => $payload['preview_mode'],
                    'preview_title' => $payload['title'],
                    'page_title' => $sessionModel->page_title ?: $payload['title'],
                    'model' => $this->resolvedModel,
                ]);
            }

            if ($sessionModel && $sessionModel->customer_id) {
                $customer = Customer::find($sessionModel->customer_id);
                if ($customer) {
                    app(AiBuilderCreditService::class)->deduct($customer, 'generate', $totalTokens);
                }
            }

            AiProgressTracker::setDone($this->sessionId, $payload);
        } catch (\Throwable $e) {
            if ($generation) {
                $generation->update([
                    'success' => false,
                    'tokens_used' => $totalTokens,
                    'error_message' => $e->getMessage(),
                ]);
            }

            if (str_contains($e->getMessage(), 'Generation cancelled by user')) {
                AiProgressTracker::set($this->sessionId, [
                    'status' => 'cancelled',
                    'message' => 'Generation cancelled.',
                ]);
            } else {
                AiProgressTracker::setError($this->sessionId, $e->getMessage());
            }

            if (!str_contains($e->getMessage(), 'Generation cancelled by user')) {
                throw $e;
            }
        } finally {
            $router->clearStepCallback();
        }
    }

    public function failed(\Throwable $exception): void
    {
        AiProgressTracker::setError($this->sessionId, $exception->getMessage());

        $generation = AiGeneration::find($this->generationId);
        if ($generation) {
            $generation->update([
                'success' => false,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}

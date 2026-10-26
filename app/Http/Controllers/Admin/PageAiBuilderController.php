<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PageAiBuildSessionJob;
use App\Models\AiBuilderSession;
use App\Models\AiGeneration;
use App\Models\AiGenerationStep;
use App\Models\Page;
use App\Models\Setting;
use App\Services\AiBuilder\AiProgressTracker;
use App\Services\AiBuilder\DefaultCodebase;
use App\Services\AiBuilder\IntentDetector;
use App\Services\AiBuilder\ModelRouter;
use App\Services\AiBuilder\PageHtmlGenerator;
use App\Services\AiBuilder\ViteSandboxCompiler;
use App\Services\AI\AiProviderRegistry;
use App\Services\AI\AiProviderRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PageAiBuilderController extends Controller
{
    public function index(AiProviderRouter $router)
    {
        $providerModels = [];
        foreach (AiProviderRegistry::providerKeys() as $key) {
            if (! $router->isProviderEnabled($key)) {
                continue;
            }
            $providerModels[$key] = AiProviderRegistry::allModels($key);
        }

        return view('admin.ai-projects.page-ai-builder', [
            'sessionData' => null,
            'providerModels' => $providerModels,
            'aiBuilderCapabilities' => $this->adminCapabilities(),
            'aiBuilderCreditStatus' => [],
        ]);
    }

    private function adminCapabilities(): array
    {
        return [
            'enabledModels' => [],
            'permissions' => [
                'canDiscuss' => true,
                'canShareProject' => true,
                'canCodePreview' => true,
                'canCodeEdit' => true,
                'canLivePreviewEdit' => true,
                'canCustomDomain' => true,
                'canByos' => true,
            ],
            'platformBadge' => ['enabled' => false, 'text' => ''],
        ];
    }

    public function show(string $session, AiProviderRouter $router)
    {
        $sessionModel = AiBuilderSession::where('session_id', $session)->first();

        if (! $sessionModel) {
            return redirect()->route('admin.ai-projects.page-ai-builder');
        }

        $providerModels = [];
        foreach (AiProviderRegistry::providerKeys() as $key) {
            if (! $router->isProviderEnabled($key)) {
                continue;
            }
            $providerModels[$key] = AiProviderRegistry::allModels($key);
        }

        return view('admin.ai-projects.page-ai-builder', [
            'sessionData' => $sessionModel->toArray(),
            'providerModels' => $providerModels,
            'aiBuilderCapabilities' => $this->adminCapabilities(),
            'aiBuilderCreditStatus' => [],
        ]);
    }

    public function storeSession(Request $request)
    {
        $validated = $request->validate([
            'messages' => ['nullable', 'array'],
            'preview_html' => ['nullable', 'string'],
            'project_files' => ['nullable', 'array'],
            'react_code' => ['nullable', 'string'],
            'preview_mode' => ['nullable', 'string', 'in:html,react'],
            'preview_title' => ['nullable', 'string', 'max:255'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'page_slug' => ['nullable', 'string', 'max:255'],
            'page_status' => ['nullable', 'string', 'in:draft,publish'],
            'model' => ['nullable', 'string', 'max:100'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'app_meta' => ['nullable', 'array'],
        ]);

        $admin = auth('admin')->user();

        $providedSessionId = $validated['session_id'] ?? null;
        $sessionId = null;
        if ($providedSessionId && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $providedSessionId)) {
            $existing = AiBuilderSession::where('session_id', $providedSessionId)->first();
            if (! $existing) {
                $sessionId = $providedSessionId;
            }
        }
        if (! $sessionId) {
            $sessionId = Str::uuid()->toString();
        }

        $session = AiBuilderSession::create([
            'admin_user_id' => $admin?->id,
            'session_id' => $sessionId,
            'messages' => $validated['messages'] ?? [],
            'preview_html' => $validated['preview_html'] ?? null,
            'project_files' => $validated['project_files'] ?? null,
            'react_code' => $validated['react_code'] ?? null,
            'preview_mode' => $validated['preview_mode'] ?? 'html',
            'preview_title' => $validated['preview_title'] ?? null,
            'page_title' => $validated['page_title'] ?? null,
            'page_slug' => $validated['page_slug'] ?? null,
            'page_status' => $validated['page_status'] ?? 'draft',
            'model' => $validated['model'] ?? null,
            'app_meta' => $validated['app_meta'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->session_id,
            'url' => route('admin.ai-projects.page-ai-builder.show', $session->session_id),
            'update_url' => route('admin.ai-projects.page-ai-builder.session.update', $session->session_id),
        ]);
    }

    public function updateSession(Request $request, string $session)
    {
        $sessionModel = AiBuilderSession::where('session_id', $session)->firstOrFail();

        $validated = $request->validate([
            'messages' => ['nullable', 'array'],
            'preview_html' => ['nullable', 'string'],
            'project_files' => ['nullable', 'array'],
            'react_code' => ['nullable', 'string'],
            'preview_mode' => ['nullable', 'string', 'in:html,react'],
            'preview_title' => ['nullable', 'string', 'max:255'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'page_slug' => ['nullable', 'string', 'max:255'],
            'page_status' => ['nullable', 'string', 'in:draft,publish'],
            'model' => ['nullable', 'string', 'max:100'],
            'app_meta' => ['nullable', 'array'],
        ]);

        $update = [];
        if (array_key_exists('messages', $validated)) {
            $update['messages'] = $validated['messages'];
        }
        if (array_key_exists('preview_html', $validated)) {
            $update['preview_html'] = $validated['preview_html'];
        }
        if (array_key_exists('project_files', $validated)) {
            $update['project_files'] = $validated['project_files'];
        }
        if (array_key_exists('react_code', $validated)) {
            $update['react_code'] = $validated['react_code'];
        }
        if (array_key_exists('preview_mode', $validated)) {
            $update['preview_mode'] = $validated['preview_mode'];
        }
        if (array_key_exists('preview_title', $validated)) {
            $update['preview_title'] = $validated['preview_title'];
        }
        if (array_key_exists('page_title', $validated)) {
            $update['page_title'] = $validated['page_title'];
        }
        if (array_key_exists('page_slug', $validated)) {
            $update['page_slug'] = $validated['page_slug'];
        }
        if (array_key_exists('page_status', $validated)) {
            $update['page_status'] = $validated['page_status'];
        }
        if (array_key_exists('model', $validated)) {
            $update['model'] = $validated['model'];
        }
        if (array_key_exists('app_meta', $validated)) {
            $update['app_meta'] = $validated['app_meta'];
        }

        $sessionModel->update($update);

        return response()->json([
            'success' => true,
            'session_id' => $sessionModel->session_id,
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:8000'],
            'screenshot' => ['nullable', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
            'model' => ['nullable', 'string', 'max:100'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'string', 'max:4000'],
            'raw_prompt' => ['nullable', 'string', 'max:8000'],
        ]);

        $prompt = is_string($validated['prompt'] ?? null) ? trim((string) $validated['prompt']) : '';
        $rawPrompt = is_string($validated['raw_prompt'] ?? null) ? trim((string) $validated['raw_prompt']) : $prompt;
        $modelParam = is_string($validated['model'] ?? null) ? trim((string) $validated['model']) : 'auto';
        $sessionId = is_string($validated['session_id'] ?? null) ? trim((string) $validated['session_id']) : '';
        $sandboxSessionId = $sessionId !== '' ? $sessionId : Str::uuid()->toString();

        $screenshotUrl = null;
        if ($request->hasFile('screenshot')) {
            $screenshot = $request->file('screenshot');
            if ($screenshot && $screenshot->getMimeType() === 'image/svg+xml') {
                $svg = @file_get_contents($screenshot->getRealPath());
                if (!is_string($svg) || $svg === '' || preg_match('~<\s*script\b|\bon\w+\s*=|javascript\s*:|<\s*foreignObject\b~i', $svg)) {
                    throw ValidationException::withMessages([
                        'screenshot' => 'The uploaded SVG contains unsupported content.',
                    ]);
                }
            }
            $path = $screenshot->store('pages/ai', 'public');
            $screenshotUrl = asset('storage/' . ltrim($path, '/'));
        }

        $resolvedModel = ModelRouter::resolve('worker', $modelParam !== 'auto' ? $modelParam : null);
        $provider = ModelRouter::detectProvider($resolvedModel);
        $themeJson = is_string($validated['theme'] ?? null) ? trim((string) $validated['theme']) : '';

        $admin = auth('admin')->user();
        $customer = auth('customer')->user();
        $sessionModel = AiBuilderSession::firstOrCreate(
            ['session_id' => $sandboxSessionId],
            [
                'admin_user_id' => $admin?->id,
                'customer_id' => $customer?->id,
                'messages' => [],
                'preview_mode' => 'html',
                'page_status' => 'draft',
                'model' => $resolvedModel,
            ]
        );

        if ($sessionModel->admin_user_id === null && $admin?->id) {
            $sessionModel->update(['admin_user_id' => $admin->id]);
        }

        if ($sessionModel->customer_id === null && $customer?->id) {
            $sessionModel->update(['customer_id' => $customer->id]);
        }

        $generation = AiGeneration::create([
            'admin_user_id' => $admin?->id,
            'customer_id' => $customer?->id,
            'ai_builder_session_id' => $sessionModel->id,
            'tool' => 'page_ai_builder',
            'type' => 'full_page',
            'provider' => $provider,
            'model' => $resolvedModel,
            'used_admin_keys' => true,
            'prompt' => $rawPrompt !== '' ? $rawPrompt : '(no prompt)',
            'success' => false,
        ]);

        AiProgressTracker::set($sandboxSessionId, [
            'status' => 'queued',
            'step' => 0,
            'total' => 0,
            'message' => 'Working...',
            'file' => null,
            'result' => null,
        ]);

        try {
            $phpBinary = PHP_BINARY;
            $artisanPath = base_path('artisan');
            $cmd = sprintf(
                '%s %s ai:build-page %s %d %s --screenshot-url=%s --model-param=%s --resolved-model=%s --theme-json=%s --raw-prompt=%s > /dev/null 2>&1 &',
                escapeshellarg($phpBinary),
                escapeshellarg($artisanPath),
                escapeshellarg($sandboxSessionId),
                $generation->id,
                escapeshellarg($prompt),
                escapeshellarg($screenshotUrl ?? ''),
                escapeshellarg($modelParam),
                escapeshellarg($resolvedModel),
                escapeshellarg($themeJson),
                escapeshellarg($rawPrompt)
            );

            proc_open(
                $cmd,
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes
            );

            if (isset($pipes)) {
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'queued' => true,
                'session_id' => $sandboxSessionId,
                'sandbox_session_id' => $sandboxSessionId,
                'generation_id' => $generation->id,
                'type' => 'page',
                'status' => 'queued',
                'message' => 'Working...',
                'model_used' => $resolvedModel,
            ]);
        } catch (\Throwable $e) {
            $generation->update([
                'success' => false,
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    public function progress(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);

        // Release session lock immediately — we only need cache
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $data = AiProgressTracker::get($validated['session_id']);

        $steps = $data['steps'] ?? [];
        $blocks = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];

        return response()->json([
            'success' => true,
            'status' => $data['status'] ?? 'unknown',
            'step' => $data['step'] ?? 0,
            'total' => $data['total'] ?? 0,
            'message' => $data['message'] ?? '',
            'file' => $data['file'] ?? null,
            'action' => $data['action'] ?? 'working',
            'description' => $data['description'] ?? null,
            'steps' => is_array($steps) ? $steps : [],
            'blocks' => $blocks,
            'result' => is_array($data['result'] ?? null) ? $data['result'] : null,
        ]);
    }

    public function stream(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);

        $sessionId = $validated['session_id'];

        return response()->stream(function () use ($sessionId) {
            ignore_user_abort(true);
            set_time_limit(300);

            $sendEvent = function (string $event, array $data = []) use ($sessionId): void {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            $lastBlockCount = 0;
            $lastStatus = null;
            $lastStatusSnapshot = null;
            $startTime = microtime(true);

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                if ((microtime(true) - $startTime) > 280) {
                    $sendEvent('error', ['message' => 'Stream timeout.']);
                    break;
                }

                $data = AiProgressTracker::getSince($sessionId, $lastBlockCount);
                $newBlocks = $data['blocks'] ?? [];
                $blockCount = $data['block_count'] ?? 0;
                $status = $data['status'] ?? 'unknown';

                if (!empty($newBlocks)) {
                    foreach ($newBlocks as $block) {
                        $sendEvent('block', $block);
                    }
                    $lastBlockCount = $blockCount;
                }

                $statusSnapshot = implode('|', [
                    $status,
                    (string) ($data['message'] ?? ''),
                    (string) ($data['file'] ?? ''),
                    (string) ($data['action'] ?? ''),
                ]);
                if ($status !== $lastStatus || $statusSnapshot !== $lastStatusSnapshot) {
                    $sendEvent('status', [
                        'status' => $status,
                        'step' => $data['step'] ?? 0,
                        'total' => $data['total'] ?? 0,
                        'message' => $data['message'] ?? '',
                        'file' => $data['file'] ?? null,
                        'action' => $data['action'] ?? 'working',
                        'description' => $data['description'] ?? null,
                    ]);
                    $lastStatus = $status;
                    $lastStatusSnapshot = $statusSnapshot;
                }

                if (in_array($status, ['done', 'error', 'cancelled'], true)) {
                    $result = $data['result'] ?? null;
                    // A late progress block can race the final cache write. The
                    // session row is the durable source for completed page data.
                    if ($status === 'done' && !is_array($result)) {
                        $session = AiBuilderSession::where('session_id', $sessionId)->first();
                        if ($session) {
                            $result = [
                                'success' => true,
                                'type' => 'page',
                                'html' => $session->preview_html ?? '',
                                'preview_html' => $session->preview_html ?? '',
                                'react_code' => $session->react_code,
                                'preview_mode' => $session->preview_mode ?? 'html',
                                'title' => $session->preview_title ?? 'Generated Page',
                                'project_files' => $session->project_files ?? [],
                                'sandbox_session_id' => $sessionId,
                            ];
                        }
                    }
                    $sendEvent('done', [
                        'status' => $status,
                        'result' => $result,
                        'message' => $data['message'] ?? '',
                    ]);
                    break;
                }

                usleep(200000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);

        AiProgressTracker::cancel($validated['session_id']);

        return response()->json(['success' => true]);
    }

    public function compileSandbox(Request $request, string $session, ViteSandboxCompiler $sandboxCompiler, PageHtmlGenerator $pageHtmlGenerator)
    {
        set_time_limit(600);

        $sessionModel = AiBuilderSession::where('session_id', $session)->firstOrFail();

        $validated = $request->validate([
            'project_files' => ['required', 'array', 'min:1'],
            'react_code' => ['nullable', 'string'],
            'preview_title' => ['nullable', 'string', 'max:255'],
            'allow_fix' => ['nullable', 'boolean'],
        ]);

        $model = is_string($validated['model'] ?? null) ? $validated['model'] : $sessionModel->model;
        $allowFix = filter_var($validated['allow_fix'] ?? false, FILTER_VALIDATE_BOOL);

        $compiled = $sandboxCompiler->compileWithAutoFix($sessionModel->session_id, $validated['project_files'], $pageHtmlGenerator, $model, $allowFix);

        if (! $compiled['success']) {
            return response()->json([
                'success' => false,
                'error' => $compiled['error'] ?? 'Compilation failed',
                'build_log' => $compiled['build_log'] !== '' ? $compiled['build_log'] : null,
                'project_files' => $compiled['project_files'] ?? null,
            ], 422);
        }

        $distHtml = $compiled['dist_index_html'];
        $previewUrl = route('admin.ai-projects.page-ai-builder.sandbox.preview', ['session' => $sessionModel->session_id, 'path' => 'index.html']);
        $sandboxBaseUrl = rtrim(str_replace('/index.html', '', $previewUrl), '/');

        if (is_string($distHtml) && $distHtml !== '') {
            $distHtml = $this->rewriteSandboxAssetUrls($distHtml, $sandboxBaseUrl);
        }

        $sessionModel->update([
            'project_files' => $compiled['project_files'],
            'react_code' => $validated['react_code'] ?? $sessionModel->react_code,
            'preview_title' => $validated['preview_title'] ?? $sessionModel->preview_title,
            'preview_mode' => 'react',
            'preview_html' => $distHtml,
        ]);

        return response()->json([
            'success' => true,
            'project_files' => $compiled['project_files'],
            'preview_html' => $distHtml,
            'preview_url' => $previewUrl,
            'build_log' => $compiled['build_log'] !== '' ? $compiled['build_log'] : null,
        ]);
    }

    public function starterFiles(PageHtmlGenerator $pageHtmlGenerator)
    {
        $codebase = new DefaultCodebase();

        return response()->json([
            'success' => true,
            'project_files' => array_merge($codebase->starterFiles(), $pageHtmlGenerator->latestTemplateFiles()),
        ]);
    }

    public function iterate(Request $request, string $session, PageHtmlGenerator $pageHtmlGenerator, AiProviderRouter $aiRouter)
    {
        $sessionModel = AiBuilderSession::where('session_id', $session)->firstOrFail();

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:8000'],
            'model' => ['nullable', 'string', 'max:100'],
            'mode' => ['nullable', 'string', 'max:50'],
            'theme' => ['nullable', 'array'],
            'project_files' => ['required', 'array', 'min:1'],
            'messages' => ['nullable', 'array'],
            'raw_prompt' => ['nullable', 'string', 'max:8000'],
            'segment_plan' => ['nullable', 'array'],
        ]);

        $prompt = trim((string) $validated['prompt']);
        $rawPrompt = is_string($validated['raw_prompt'] ?? null) ? trim((string) $validated['raw_prompt']) : $prompt;
        $modelParam = is_string($validated['model'] ?? null) ? trim((string) $validated['model']) : null;
        $requestFiles = is_array($validated['project_files']) ? $validated['project_files'] : [];
        $sessionFiles = is_array($sessionModel->project_files) ? $sessionModel->project_files : [];
        $currentFiles = $this->mergeProjectFilesForIteration($sessionFiles, $requestFiles);
        $messages = is_array($validated['messages'] ?? null) ? $validated['messages'] : (is_array($sessionModel->messages) ? $sessionModel->messages : []);

        $resolvedModel = $modelParam !== null && $modelParam !== '' && strtolower($modelParam) !== 'auto'
            ? $modelParam
            : $this->resolveSessionModel($sessionModel);

        $frontendTheme = is_array($validated['theme'] ?? null) ? $validated['theme'] : null;
        $mode = is_string($validated['mode'] ?? null) ? $validated['mode'] : null;

        // Track AI token usage and cost for iterations (intent detection, chat
        // replies, and the build itself) so admin dashboard totals include
        // follow-up work, not just the initial generation.
        $admin = auth('admin')->user();
        $customer = auth('customer')->user();
        $generation = AiGeneration::create([
            'admin_user_id' => $admin?->id,
            'customer_id' => $customer?->id,
            'ai_builder_session_id' => $sessionModel->id,
            'tool' => 'page_ai_builder',
            'type' => 'iteration',
            'provider' => ModelRouter::detectProvider($resolvedModel),
            'model' => $resolvedModel,
            'used_admin_keys' => true,
            'prompt' => $rawPrompt !== '' ? $rawPrompt : '(no prompt)',
            'success' => false,
        ]);

        $totalTokens = 0;
        $onStep = function ($meta) use ($generation, &$totalTokens) {
            $tokens = is_numeric($meta['tokens'] ?? null) ? (int) $meta['tokens'] : 0;
            $inputTokens = is_numeric($meta['input_tokens'] ?? null) ? (int) $meta['input_tokens'] : 0;
            $outputTokens = is_numeric($meta['output_tokens'] ?? null) ? (int) $meta['output_tokens'] : 0;
            $totalTokens += $tokens;

            $costCents = ModelRouter::estimateCostCents((string) ($meta['model'] ?? ''), $inputTokens, $outputTokens, $tokens);

            AiGenerationStep::create([
                'ai_generation_id' => $generation->id,
                'step_name' => is_string($meta['step_name'] ?? null) ? $meta['step_name'] : 'AI Generation',
                'provider' => $meta['provider'] ?? null,
                'model' => $meta['model'] ?? null,
                'tokens_used' => $tokens,
                'cost_cents' => $costCents,
                'status' => ($meta['success'] ?? false) ? 'completed' : 'failed',
                'error_message' => $meta['error_message'] ?? null,
            ]);
        };
        $aiRouter->onStep($onStep);

        $finalizeGeneration = function (bool $success, string $type = 'iteration') use ($generation, &$totalTokens, $aiRouter) {
            $aiRouter->clearStepCallback();
            $generation->update([
                'success' => $success,
                'type' => $type,
                'tokens_used' => $totalTokens,
            ]);
        };

        // Intent gate: answer conversational requests instead of editing the project.
        // Explicit theme-only iterations are always build/edit work, never chat.
        try {
            $intentDetector = new IntentDetector($aiRouter);
            $intent = ($mode ?? '') === 'theme'
                ? ['intent' => 'build', 'message' => null]
                : $intentDetector->detect($rawPrompt, $modelParam, $resolvedModel);
        } catch (\Throwable $e) {
            $finalizeGeneration(false);
            throw $e;
        }
        if ($intent['intent'] === 'chat') {
            try {
                $supabaseConnected = $this->sessionHasConnectedSupabase($sessionModel);
                $reply = $this->chatReplyForProject($aiRouter, $resolvedModel, $currentFiles, $messages, $rawPrompt, $supabaseConnected)
                    ?? $intentDetector->chatReply($rawPrompt, $resolvedModel, $supabaseConnected);
            } catch (\Throwable $e) {
                $finalizeGeneration(false);
                throw $e;
            }

            $sessionMessages = is_array($sessionModel->messages) ? $sessionModel->messages : [];
            $now = now()->toIso8601String();
            $sessionMessages[] = [
                'id' => 'chat-user-' . time() . '-' . random_int(100, 999),
                'role' => 'user',
                'content' => $rawPrompt,
                'status' => 'ok',
                'created_at' => $now,
            ];
            $sessionMessages[] = [
                'id' => 'chat-assistant-' . time() . '-' . random_int(100, 999),
                'role' => 'assistant',
                'content' => $reply,
                'status' => 'ok',
                'created_at' => $now,
            ];
            $sessionModel->update(['messages' => $sessionMessages]);

            $finalizeGeneration(true, 'chat');

            return response()->json([
                'success' => true,
                'type' => 'chat',
                'message' => $reply,
                'model_used' => $resolvedModel,
            ]);
        }

        try {
            $iterationResult = $pageHtmlGenerator->iterate(
                $currentFiles,
                $prompt,
                $resolvedModel,
                $messages,
                $frontendTheme,
                $mode,
                $onStep,
                is_array($validated['segment_plan'] ?? null) ? $validated['segment_plan'] : null
            );
        } catch (\Throwable $e) {
            $finalizeGeneration(false);
            throw $e;
        }

        if (!($iterationResult['success'] ?? false)) {
            $finalizeGeneration(false);
            return response()->json([
                'success' => false,
                'message' => $iterationResult['error'] ?? 'AI iteration failed or returned no changes.',
            ], 422);
        }

        $updatedFiles = $iterationResult['project_files'] ?? [];

        $sessionUpdate = [
            'project_files' => $updatedFiles,
            'preview_mode' => 'react',
        ];
        if (!empty($messages)) {
            $sessionUpdate['messages'] = $messages;
        }
        $sessionModel->update($sessionUpdate);

        $finalizeGeneration(true);

            return response()->json([
                'success' => true,
                'project_files' => $updatedFiles,
                'message' => 'Updated files successfully.',
                'remaining_plan' => is_array($iterationResult['remaining_plan'] ?? null)
                    ? $iterationResult['remaining_plan']
                    : [],
            ]);
    }

    public function discuss(Request $request, string $session, AiProviderRouter $aiRouter)
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:8000'],
            'model' => ['nullable', 'string', 'max:100'],
            'messages' => ['nullable', 'array'],
        ]);

        $sessionModel = AiBuilderSession::where('session_id', $session)->firstOrFail();

        $prompt = trim((string) $validated['prompt']);
        $modelParam = is_string($validated['model'] ?? null) ? trim((string) $validated['model']) : null;
        $resolvedModel = $modelParam !== null && $modelParam !== '' && strtolower($modelParam) !== 'auto'
            ? $modelParam
            : $this->resolveSessionModel($sessionModel);

        if (! $aiRouter->hasCredentials($resolvedModel)) {
            return response()->json([
                'success' => false,
                'message' => 'AI API key is not configured.',
            ], 422);
        }

        // Track AI token usage and cost for discussions too, so admin dashboard
        // totals include chat replies.
        $admin = auth('admin')->user();
        $customer = auth('customer')->user();
        $generation = AiGeneration::create([
            'admin_user_id' => $admin?->id,
            'customer_id' => $customer?->id,
            'tool' => 'page_ai_builder',
            'type' => 'chat',
            'provider' => ModelRouter::detectProvider($resolvedModel),
            'model' => $resolvedModel,
            'used_admin_keys' => true,
            'prompt' => $prompt !== '' ? $prompt : '(no prompt)',
            'success' => false,
        ]);

        $totalTokens = 0;
        $onStep = function ($meta) use ($generation, &$totalTokens) {
            $tokens = is_numeric($meta['tokens'] ?? null) ? (int) $meta['tokens'] : 0;
            $inputTokens = is_numeric($meta['input_tokens'] ?? null) ? (int) $meta['input_tokens'] : 0;
            $outputTokens = is_numeric($meta['output_tokens'] ?? null) ? (int) $meta['output_tokens'] : 0;
            $totalTokens += $tokens;

            $costCents = ModelRouter::estimateCostCents((string) ($meta['model'] ?? ''), $inputTokens, $outputTokens, $tokens);

            AiGenerationStep::create([
                'ai_generation_id' => $generation->id,
                'step_name' => is_string($meta['step_name'] ?? null) ? $meta['step_name'] : 'AI Generation',
                'provider' => $meta['provider'] ?? null,
                'model' => $meta['model'] ?? null,
                'tokens_used' => $tokens,
                'cost_cents' => $costCents,
                'status' => ($meta['success'] ?? false) ? 'completed' : 'failed',
                'error_message' => $meta['error_message'] ?? null,
            ]);
        };
        $aiRouter->onStep($onStep);

        $projectFiles = is_array($sessionModel->project_files) ? $sessionModel->project_files : [];

        try {
            $responseText = $this->chatReplyForProject(
                $aiRouter,
                $resolvedModel,
                $projectFiles,
                $validated['messages'] ?? [],
                $prompt,
                $this->sessionHasConnectedSupabase($sessionModel),
            );
        } catch (\Throwable $e) {
            $aiRouter->clearStepCallback();
            $generation->update(['success' => false, 'tokens_used' => $totalTokens]);
            throw $e;
        }

        if ($responseText === null) {
            $aiRouter->clearStepCallback();
            $generation->update(['success' => false, 'tokens_used' => $totalTokens]);
            return response()->json([
                'success' => false,
                'message' => 'AI discussion failed.',
            ], 502);
        }

        $aiRouter->clearStepCallback();
        $generation->update(['success' => true, 'tokens_used' => $totalTokens]);

        $now = now()->toIso8601String();
        $userMessage = [
            'id' => 'discuss-user-' . time() . '-' . random_int(100, 999),
            'role' => 'user',
            'content' => $prompt,
            'model' => $resolvedModel,
            'status' => 'ok',
            'created_at' => $now,
        ];
        $assistantMessage = [
            'id' => 'discuss-assistant-' . time() . '-' . random_int(100, 999),
            'role' => 'assistant',
            'content' => $responseText,
            'model' => $resolvedModel,
            'status' => 'ok',
            'created_at' => $now,
        ];

        $sessionMessages = is_array($sessionModel->messages) ? $sessionModel->messages : [];
        $sessionModel->update(['messages' => array_merge($sessionMessages, [$userMessage, $assistantMessage])]);

        return response()->json([
            'success' => true,
            'type' => 'chat',
            'messages' => [$userMessage, $assistantMessage],
            'model_used' => $resolvedModel,
        ]);
    }

    /**
     * Run a context-aware conversational reply against the current project files.
     * Returns null when the AI call fails, so callers can fall back to a generic reply.
     */
    protected function chatReplyForProject(
        AiProviderRouter $router,
        string $resolvedModel,
        array $projectFiles,
        array $history,
        string $prompt,
        bool $supabaseConnected = false,
    ): ?string
    {
        if (! $router->hasCredentials($resolvedModel)) {
            return null;
        }

        $fileContext = [];
        foreach (array_slice($projectFiles, 0, 20) as $file) {
            if (! is_array($file) || ! is_string($file['path'] ?? null)) {
                continue;
            }
            $content = is_string($file['content'] ?? null) ? $file['content'] : '';
            $fileContext[] = 'File: ' . $file['path'] . "\n" . substr($content, 0, 2000);
        }

        $systemPrompt = "You are a helpful AI assistant discussing a web project. Help the user understand, plan, and improve their generated project. Answer questions, explain code, suggest improvements, and help debug. Do not generate new files or modify the project unless explicitly asked. Keep responses concise and actionable.";
        $systemPrompt .= $supabaseConnected
            ? "\n\nSupabase is already connected for this app. If the user asks to connect Supabase, do not give installation, .env, or manual code instructions. Clearly say Supabase is already connected and ask: 'Do you want to integrate your app data with Supabase?'"
            : "\n\nSupabase is not connected for this app. If the user asks to connect it, direct them to the app dashboard's Settings → Supabase integration instead of suggesting npm installation or .env setup.";

        if (! empty($fileContext)) {
            $systemPrompt .= "\n\nCurrent project files:\n" . implode("\n\n", $fileContext);
        }

        // Build conversation messages: system + prior history + current user prompt.
        // Without prior history the AI has no context and treats each message as standalone.
        $conversationHistory = [];
        foreach ($history as $msg) {
            if (! is_array($msg) || ! is_string($msg['role'] ?? null)) {
                continue;
            }
            $role = $msg['role'];
            $content = is_string($msg['content'] ?? null) ? trim((string) $msg['content']) : '';
            if ($content === '' || ! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }
            $conversationHistory[] = ['role' => $role, 'content' => $content];
        }

        // Limit to last 20 messages to stay within token budget.
        $conversationHistory = array_slice($conversationHistory, -20);

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $conversationHistory,
            [['role' => 'user', 'content' => $prompt]],
        );

        try {
            $result = $router->generateChat($resolvedModel, $messages, 2500, null);
        } catch (\Throwable $e) {
            return null;
        }

        if (! is_array($result) || ! ($result['success'] ?? false)) {
            return null;
        }

        $responseText = is_string($result['text'] ?? null) ? trim($result['text']) : '';

        return $responseText !== '' ? $responseText : null;
    }

    private function sessionHasConnectedSupabase(AiBuilderSession $session): bool
    {
        $integration = $session->page_id && $session->page
            ? $session->page->supabaseIntegration
            : null;

        if (! $integration || $integration->status !== 'connected') {
            $integration = $session->customer_id
                ? \App\Models\SupabaseIntegration::where('customer_id', $session->customer_id)
                    ->where('status', 'connected')
                    ->latest()
                    ->first()
                : null;
        }

        return (bool) $integration;
    }

    public function serveSandbox(string $session, ViteSandboxCompiler $sandboxCompiler, ?string $path = null)
    {
        $resolvedPath = $path ?: 'index.html';
        $filePath = $sandboxCompiler->sandboxDistFilePath($session, $resolvedPath);

        if (! File::exists($filePath)) {
            $currentUrl = htmlspecialchars((string) request()->fullUrl(), ENT_QUOTES, 'UTF-8');
            $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Preview Not Ready</title>
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .box { text-align: center; max-width: 400px; padding: 2rem; }
        .icon { width: 48px; height: 48px; background: #fee2e2; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem; color: #ef4444; font-size: 24px; }
        h1 { margin: 0 0 0.5rem; font-size: 1.125rem; color: #1f2937; }
        p { margin: 0; font-size: 0.875rem; color: #6b7280; line-height: 1.5; }
        code { background: #f3f4f6; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">&#x26A0;</div>
        <h1>Preview not ready</h1>
        <p>Build is still in progress or the build failed.<br>This page will reload automatically once the preview is ready.</p>
    </div>
    <script>
        (function () {
            let attempts = 0;
            const maxAttempts = 24; // 2 minutes
            const check = function () {
                if (attempts >= maxAttempts) {
                    document.querySelector('p').innerHTML = 'Still waiting? Check the Code tab for build errors and click <strong>Compile</strong> to retry.';
                    return;
                }
                attempts++;
                fetch('{$currentUrl}', { method: 'HEAD', cache: 'no-store' })
                    .then(function (r) {
                        if (r.ok || r.status === 200) {
                            window.location.reload();
                        }
                    })
                    .catch(function () {})
                    .finally(function () {
                        setTimeout(check, 5000);
                    });
            };
            setTimeout(check, 3000);
        })();
    </script>
</body>
</html>
HTML;
            return response($html, 404, [
                'Content-Type' => 'text/html',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        $mime = $this->detectMimeType($filePath);
        $contents = File::get($filePath);

        // Inject Supabase credentials and rewrite asset URLs for sandbox preview
        if (str_ends_with($resolvedPath, 'index.html')) {
            $contents = $this->injectSandboxSupabaseCredentials($session, $contents);
            $sandboxBaseUrl = rtrim(preg_replace('#/index\.html(?:\?.*)?$#', '', (string) request()->fullUrl()) ?: (string) request()->url(), '/');
            $contents = $this->rewriteSandboxAssetUrls($contents, $sandboxBaseUrl);
        }

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => str_ends_with($resolvedPath, 'index.html') ? 'no-store, no-cache, must-revalidate' : 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * Inject Supabase credentials into the sandbox preview HTML so auth works.
     * Looks up the session's linked page and its Supabase integration.
     */
    private function injectSandboxSupabaseCredentials(string $sessionId, string $html): string
    {
        $session = AiBuilderSession::where('session_id', $sessionId)->first();
        if (!$session) {
            return $html;
        }

         $supabaseUrl = '';
         $supabaseKey = '';
         $authProviders = null;
         $appId = '';

         // Try to get credentials from the linked page's Supabase integration
         if ($session->page_id) {
             $page = Page::find($session->page_id);
             if ($page) {
                 $integration = $page->supabaseIntegration;
                 if ($integration && $integration->status === 'connected') {
                     $supabaseUrl = $integration->project_url ?? '';
                     $supabaseKey = $integration->publishable_key ?? '';
                     $authProviders = $integration->auth_providers;
                     $appId = (string) $integration->getKey();
                 }
             }
         }

         // Also try customer's Supabase integrations if page didn't have one
         if ($supabaseUrl === '' && $session->customer_id) {
             $integration = \App\Models\SupabaseIntegration::where('customer_id', $session->customer_id)
                 ->where('status', 'connected')
                 ->latest()
                 ->first();
             if ($integration) {
                 $supabaseUrl = $integration->project_url ?? '';
                 $supabaseKey = $integration->publishable_key ?? '';
                 $authProviders = $integration->auth_providers;
                 $appId = (string) $integration->getKey();
             }
         }

         $urlJson = json_encode($supabaseUrl);
         $keyJson = json_encode($supabaseKey);
         $providersJson = json_encode($authProviders);
         $appIdJson = json_encode($appId);

         $script = <<<SCRIPT
 <script>
     window.APP_SUPABASE_URL = {$urlJson};
     window.APP_SUPABASE_PUBLISHABLE_KEY = {$keyJson};
     window.APP_AUTH_PROVIDERS = {$providersJson};
     window.APP_ID = {$appIdJson};
 </script>
 SCRIPT;

        // Inject before </head>
        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $script . '</head>', $html);
        }

        // Fallback: prepend
        return $script . $html;
    }

    private function rewriteSandboxAssetUrls(string $html, string $sandboxBaseUrl): string
    {
        $base = rtrim($sandboxBaseUrl, '/');

        // Rewrite relative Vite asset references (e.g. ./assets/index-xxx.js)
        $html = preg_replace(
            '/(src|href)="\.\/assets\//i',
            '$1="' . $base . '/assets/',
            $html
        );

        // Rewrite bare assets/ references (without ./ prefix)
        $html = preg_replace(
            '/(src|href)="assets\//i',
            '$1="' . $base . '/assets/',
            $html
        );

        // Rewrite absolute favicon reference so it resolves through the sandbox route
        $html = preg_replace(
            '/href="\/vite\.svg"/i',
            'href="' . $base . '/vite.svg"',
            $html
        );

        // Rewrite any remaining absolute /assets/ paths
        $html = preg_replace(
            '/(src|href)="\/assets\//i',
            '$1="' . $base . '/assets/',
            $html
        );

        return $html;
    }

    private function injectAppBrandingIntoHtml(string $html, array $appMeta, string $previewMode): string
    {
        if ($previewMode !== 'react' || empty($appMeta)) {
            return $html;
        }

        $json = json_encode($appMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $script = <<<SCRIPT
<script>
    window.APP_META = {$json};
    (function () {
        const meta = window.APP_META || {};
        function applyAppMeta() {
            if (meta.app_title) {
                document.querySelectorAll('[data-app-title]').forEach((el) => {
                    el.textContent = meta.app_title;
                });
                document.title = meta.app_title;
            }
            if (meta.app_logo) {
                document.querySelectorAll('[data-app-logo]').forEach((el) => {
                    if (el.tagName.toLowerCase() === 'img') {
                        el.src = meta.app_logo;
                        el.classList.remove('hidden');
                    } else {
                        el.style.backgroundImage = 'url(' + JSON.stringify(meta.app_logo).slice(1, -1) + ')';
                        el.style.backgroundSize = 'contain';
                        el.style.backgroundRepeat = 'no-repeat';
                        el.style.backgroundPosition = 'center';
                    }
                });
            }
            if (meta.app_icon) {
                let link = document.querySelector('link[rel="icon"]');
                if (!link) {
                    link = document.createElement('link');
                    link.rel = 'icon';
                    document.head.appendChild(link);
                }
                link.href = meta.app_icon;
            }
        }
        function startObserver() {
            if (!window.MutationObserver || !document.body) {
                return;
            }
            const observer = new MutationObserver((mutations) => {
                let shouldApply = false;
                for (const mutation of mutations) {
                    if (mutation.type !== 'childList') {
                        continue;
                    }
                    for (const node of mutation.addedNodes) {
                        if (node.nodeType !== 1) {
                            continue;
                        }
                        if (
                            (node.matches && node.matches('[data-app-title], [data-app-logo]')) ||
                            (node.querySelector && node.querySelector('[data-app-title], [data-app-logo]'))
                        ) {
                            shouldApply = true;
                            break;
                        }
                    }
                    if (shouldApply) {
                        break;
                    }
                }
                if (shouldApply) {
                    applyAppMeta();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }

        applyAppMeta();
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                applyAppMeta();
                startObserver();
            });
        } else {
            applyAppMeta();
            startObserver();
        }
    })();
</script>
SCRIPT;

        // Insert before </head> so it runs before React mounts
        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $script . '</head>', $html, $count);
        }

        // Fallback: prepend at the start of the document
        return $script . $html;
    }

    public function export(Request $request, string $session)
    {
        $format = $request->input('format', 'zip');
        $sessionModel = AiBuilderSession::where('session_id', $session)->firstOrFail();

        $sandboxPath = storage_path('app/ai-builder-sandboxes/' . $session);
        $distPath = $sandboxPath . DIRECTORY_SEPARATOR . 'dist';

        // If dist/index.html doesn't exist, compile on the fly from stored project files
        if (! File::exists($distPath . DIRECTORY_SEPARATOR . 'index.html')) {
            $projectFiles = is_array($sessionModel->project_files) ? $sessionModel->project_files : [];
            if (empty($projectFiles)) {
                return response()->json(['message' => 'No project files available. Generate a page first.'], 422);
            }

            $compiler = app(ViteSandboxCompiler::class);
            $compiled = $compiler->compile($session, $projectFiles);
            if (! $compiled['success']) {
                return response()->json(['message' => 'Failed to compile project: ' . ($compiled['error'] ?? 'Unknown error')], 422);
            }
        }

        $indexHtmlPath = $distPath . DIRECTORY_SEPARATOR . 'index.html';
        if (! File::exists($indexHtmlPath)) {
            return response()->json(['message' => 'No compiled output found.'], 422);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $sessionModel->page_slug ?: $sessionModel->preview_title ?: 'project');

        if ($format === 'html') {
            $html = $this->buildSelfContainedHtml($distPath);
            $filename = $safeName . '.html';
            return response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        if ($format === 'zip') {
            $zipPath = tempnam(sys_get_temp_dir(), 'ai-project-') . '.zip';
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return response()->json(['message' => 'Could not create ZIP archive.'], 500);
            }

            $this->addDirectoryToZip($zip, $distPath, '');
            $zip->close();

            $filename = $safeName . '.zip';
            return response()->download($zipPath, $filename, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
        }

        return response()->json(['message' => 'Unsupported export format.'], 422);
    }

    /**
     * Recursively add all files from a directory into a ZipArchive, preserving relative paths.
     */
    private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $zipPrefix): void
    {
        $entries = File::allFiles($dir);
        foreach ($entries as $file) {
            if ($file->isDir()) {
                continue;
            }
            $relative = $file->getRelativePathname();
            $relative = str_replace('\\', '/', $relative);
            $zip->addFile($file->getRealPath(), $zipPrefix !== '' ? $zipPrefix . '/' . $relative : $relative);
        }
    }

    /**
     * Build a self-contained HTML string by inlining all CSS and JS assets
     * referenced in the dist/index.html with relative paths.
     */
    private function buildSelfContainedHtml(string $distPath): string
    {
        $html = File::get($distPath . DIRECTORY_SEPARATOR . 'index.html');

        // Inline <link rel="stylesheet" href="./assets/xxx.css">
        $html = preg_replace_callback(
            '/<link\b[^>]*\bhref=["\'](\.\/assets\/[^"\']+\.css)["\'][^>]*>/i',
            function ($m) use ($distPath) {
                $assetPath = $distPath . DIRECTORY_SEPARATOR . ltrim($m[1], './');
                if (! File::exists($assetPath)) {
                    return $m[0];
                }
                $css = File::get($assetPath);
                // Inline url() references in CSS as base64 data URIs
                $css = $this->inlineCssUrlAssets($css, $distPath);
                return '<style>' . $css . '</style>';
            },
            $html
        );

        // Inline <script type="module" src="./assets/xxx.js">
        $html = preg_replace_callback(
            '/<script\b([^>]*?)\bsrc=["\'](\.\/assets\/[^"\']+\.js)["\']([^>]*?)><\/script>/i',
            function ($m) use ($distPath) {
                $attrs = trim(($m[1] ?? '') . ' ' . ($m[3] ?? ''));
                $assetPath = $distPath . DIRECTORY_SEPARATOR . ltrim($m[2], './');
                if (! File::exists($assetPath)) {
                    return $m[0];
                }
                $js = File::get($assetPath);
                return '<script ' . $attrs . '>' . $js . '</script>';
            },
            $html
        );

        // Inline other asset references in <img src="./assets/...">, <link href="./assets/..."> etc.
        $html = preg_replace_callback(
            '/\b(src|href)=["\'](\.\/assets\/[^"\']+)["\']/i',
            function ($m) use ($distPath) {
                $assetPath = $distPath . DIRECTORY_SEPARATOR . ltrim($m[2], './');
                if (! File::exists($assetPath)) {
                    return $m[0];
                }
                $mime = $this->detectMimeType($assetPath);
                $data = base64_encode(File::get($assetPath));
                return $m[1] . '="data:' . $mime . ';base64,' . $data . '"';
            },
            $html
        );

        return $html;
    }

    /**
     * Replace url() references in CSS with base64 data URIs for local assets.
     */
    private function inlineCssUrlAssets(string $css, string $distPath): string
    {
        return preg_replace_callback(
            '/url\(\s*["\']?(\.\/[^"\')]+)["\']?\s*\)/i',
            function ($m) use ($distPath) {
                $assetPath = $distPath . DIRECTORY_SEPARATOR . ltrim($m[1], './');
                if (! File::exists($assetPath)) {
                    return $m[0];
                }
                $mime = $this->detectMimeType($assetPath);
                $data = base64_encode(File::get($assetPath));
                return 'url(data:' . $mime . ';base64,' . $data . ')';
            },
            $css
        );
    }

    private function detectMimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $map = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'mjs' => 'application/javascript',
            'json' => 'application/json',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'html' => 'text/html',
            'htm' => 'text/html',
            'txt' => 'text/plain',
            'xml' => 'application/xml',
            'pdf' => 'application/pdf',
        ];

        if (isset($map[$ext])) {
            return $map[$ext];
        }

        return File::mimeType($filePath) ?: 'application/octet-stream';
    }

    public function analyze(Request $request, AiProviderRouter $router)
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:8000'],
            'raw_prompt' => ['nullable', 'string', 'max:8000'],
        ]);

        $prompt = trim((string) $validated['prompt']);
        $rawPrompt = is_string($validated['raw_prompt'] ?? null) ? trim((string) $validated['raw_prompt']) : $prompt;
        $model = ModelRouter::resolve('worker');

        if (!$router->hasCredentials($model)) {
            return response()->json([
                'success' => false,
                'message' => 'AI API key is not configured.',
            ], 422);
        }

        // Chat intents should not trigger a fake build-progress stream at all.
        $intent = (new IntentDetector($router))->detect($rawPrompt);
        if ($intent['intent'] === 'chat') {
            return response()->json([
                'success' => true,
                'intent' => 'chat',
                'events' => [],
            ]);
        }

        $system = implode("\n", [
            'You are a website building agent. Analyze the user\'s request and generate a realistic sequence of progress events that would occur while building their website.',
            '',
            'Rules:',
            '- Generate 15-30 unique progress events.',
            '- Each event must be specific to the website type (SaaS, restaurant, marketing agency, portfolio, etc.).',
            '- Never repeat events.',
            '- Events should feel like real work being performed by an AI agent.',
            '- Include some unexpected events for realism.',
            '',
            'Output ONLY a JSON array of objects with this shape (no markdown, no code fences):',
            '[{"title": "Analyzing restaurant concept", "description": "Understanding cuisine style, ambiance, and target diners.", "type": "analysis"}, ...]',
            '',
            'Event types: analysis, planning, content, design, components, forms, integrations, optimization, testing, deployment.',
        ]);

        $user = 'User request: ' . $prompt;

        try {
            $result = $router->generateJsonObject(
                $model,
                $system,
                $user,
                2000,
                'Progress Event Analyzer'
            );

            if (!is_array($result) || ($result['success'] ?? false) !== true) {
                return response()->json([
                    'success' => true,
                    'events' => $this->fallbackEvents($prompt),
                ]);
            }

            $events = is_array($result['json'] ?? null) ? $result['json'] : null;
            if (!is_array($events) || count($events) === 0) {
                return response()->json([
                    'success' => true,
                    'events' => $this->fallbackEvents($prompt),
                ]);
            }

            // Normalize and sanitize
            $normalized = [];
            foreach ($events as $ev) {
                if (!is_array($ev)) continue;
                $title = is_string($ev['title'] ?? null) ? trim($ev['title']) : '';
                if ($title === '') continue;
                $normalized[] = [
                    'title' => $title,
                    'description' => is_string($ev['description'] ?? null) ? trim($ev['description']) : '',
                    'type' => is_string($ev['type'] ?? null) ? trim($ev['type']) : 'design',
                ];
            }

            if (count($normalized) === 0) {
                $normalized = $this->fallbackEvents($prompt);
            }

            return response()->json([
                'success' => true,
                'events' => $normalized,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'events' => $this->fallbackEvents($prompt),
            ]);
        }
    }

    private function fallbackEvents(string $prompt): array
    {
        $p = strtolower($prompt);
        if (str_contains($p, 'restaurant') || str_contains($p, 'cafe') || str_contains($p, 'food')) {
            return [
                ['title' => 'Understanding restaurant concept', 'description' => 'Analyzing cuisine, ambiance, and dining style.', 'type' => 'analysis'],
                ['title' => 'Designing menu showcase', 'description' => 'Creating appetizing menu layouts with pricing.', 'type' => 'design'],
                ['title' => 'Building reservation system', 'description' => 'Designing booking flow and availability display.', 'type' => 'components'],
                ['title' => 'Creating gallery section', 'description' => 'Showcasing dishes and interior atmosphere.', 'type' => 'content'],
                ['title' => 'Optimizing mobile ordering', 'description' => 'Ensuring smooth takeout and delivery UX.', 'type' => 'optimization'],
                ['title' => 'Preparing contact section', 'description' => 'Adding hours, location, and map integration.', 'type' => 'forms'],
                ['title' => 'Adding social proof', 'description' => 'Embedding reviews and testimonials.', 'type' => 'content'],
                ['title' => 'Finalizing responsive design', 'description' => 'Testing across all screen sizes.', 'type' => 'testing'],
            ];
        }
        if (str_contains($p, 'saas') || str_contains($p, 'app') || str_contains($p, 'software')) {
            return [
                ['title' => 'Mapping product positioning', 'description' => 'Defining value proposition and competitive angle.', 'type' => 'analysis'],
                ['title' => 'Designing pricing comparison', 'description' => 'Building clear tiered pricing tables.', 'type' => 'components'],
                ['title' => 'Creating onboarding flow', 'description' => 'Designing sign-up and first-use experience.', 'type' => 'design'],
                ['title' => 'Building feature showcase', 'description' => 'Highlighting key product capabilities.', 'type' => 'content'],
                ['title' => 'Adding customer testimonials', 'description' => 'Integrating social proof and case studies.', 'type' => 'content'],
                ['title' => 'Designing CTA sections', 'description' => 'Optimizing conversion points throughout.', 'type' => 'optimization'],
                ['title' => 'Preparing demo request form', 'description' => 'Building lead capture workflow.', 'type' => 'forms'],
                ['title' => 'Finalizing performance checks', 'description' => 'Ensuring fast load times and accessibility.', 'type' => 'testing'],
            ];
        }
        return [
            ['title' => 'Analyzing your request', 'description' => 'Understanding goals, audience, and style preferences.', 'type' => 'analysis'],
            ['title' => 'Planning page structure', 'description' => 'Mapping sections, navigation, and user flow.', 'type' => 'planning'],
            ['title' => 'Designing visual identity', 'description' => 'Crafting color palette, typography, and spacing.', 'type' => 'design'],
            ['title' => 'Building hero section', 'description' => 'Creating the above-the-fold first impression.', 'type' => 'components'],
            ['title' => 'Writing page content', 'description' => 'Generating headlines, copy, and CTAs.', 'type' => 'content'],
            ['title' => 'Creating feature blocks', 'description' => 'Designing content cards and grid layouts.', 'type' => 'components'],
            ['title' => 'Adding contact elements', 'description' => 'Building forms and contact information.', 'type' => 'forms'],
            ['title' => 'Optimizing responsiveness', 'description' => 'Testing mobile, tablet, and desktop layouts.', 'type' => 'optimization'],
            ['title' => 'Running quality checks', 'description' => 'Reviewing accessibility and visual consistency.', 'type' => 'testing'],
            ['title' => 'Preparing final output', 'description' => 'Assembling and polishing the complete page.', 'type' => 'deployment'],
        ];
    }

    public function suggestions(Request $request, AiProviderRouter $router)
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:blank,improve'],
            'preview_title' => ['nullable', 'string', 'max:255'],
            'preview_html' => ['nullable', 'string'],
            'project_files' => ['nullable', 'array'],
            'prompt_context' => ['nullable', 'string', 'max:8000'],
        ]);

        $mode = $validated['mode'];

        $projectFiles = is_array($validated['project_files'] ?? null) ? $validated['project_files'] : [];
        $previewTitle = trim((string) ($validated['preview_title'] ?? ''));
        $previewHtml = trim((string) ($validated['preview_html'] ?? ''));
        $promptContext = trim((string) ($validated['prompt_context'] ?? ''));
        $existingSections = $this->detectExistingProjectSections($projectFiles, $previewHtml, $previewTitle, $promptContext);

        $hasContext = count($projectFiles) > 0 || $previewHtml !== '' || $previewTitle !== '';

        $model = ModelRouter::resolve('worker');
        if (!$router->hasCredentials($model)) {
            return response()->json([
                'success' => true,
                'suggestions' => $this->contextualSuggestions($mode, $projectFiles, $previewHtml, $previewTitle, $promptContext, $existingSections),
            ]);
        }

        if ($mode === 'blank' || !$hasContext) {
            // Blank mode: use the admin-configured initial suggestions when available,
            // otherwise ask AI to generate diverse, creative starter ideas.
            $configured = $this->initialSuggestions();
            if (count($configured) > 0) {
                return response()->json([
                    'success' => true,
                    'suggestions' => $this->filterSuggestionsForExistingSections($configured, $existingSections),
                ]);
            }

            $system = implode("\n", [
                'You are a creative product strategist for an AI website/app builder.',
                'Generate 6 diverse, creative starter ideas that users might want to build.',
                'Mix landing pages, dashboards, apps, and tools. Be specific and inspiring.',
                'Vary the types: some SaaS, some e-commerce, some personal, some productivity, some creative.',
                'Output ONLY a JSON array of objects with this shape (no markdown, no code fences):',
                '[{"label": "AI content generator", "icon": "🤖", "prompt": "Build an AI-powered content generation tool with a dashboard showing generated content history, a prompt input area, and settings for tone and style."}]',
                'Keep labels concise (2-5 words). Keep icons as single emoji or null. Keep prompts detailed enough for an AI builder to act on.',
                'Each suggestion must be unique and different from typical defaults. Avoid generic ideas like "SaaS landing page" or "portfolio website".',
            ]);

            try {
                $result = $router->generateJsonObject(
                    $model,
                    $system,
                    'Generate 6 diverse, creative starter ideas for an AI website/app builder. Make them varied and interesting.',
                    2000,
                    'AI Builder Blank Suggestions'
                );

                $suggestions = is_array($result['json'] ?? null) ? $result['json'] : null;
                if (is_array($suggestions) && count($suggestions) > 0) {
                    $normalized = [];
                    foreach ($suggestions as $s) {
                        if (!is_array($s)) continue;
                        $label = is_string($s['label'] ?? null) ? trim($s['label']) : '';
                        $prompt = is_string($s['prompt'] ?? null) ? trim($s['prompt']) : '';
                        if ($label === '' || $prompt === '') continue;
                        $normalized[] = [
                            'label' => $label,
                            'prompt' => $prompt,
                            'icon' => is_string($s['icon'] ?? null) ? trim($s['icon']) : null,
                        ];
                    }
                    if (count($normalized) > 0) {
                        return response()->json([
                            'success' => true,
                            'suggestions' => $this->filterSuggestionsForExistingSections($normalized, $existingSections),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Fall through to fallback
            }

            return response()->json([
                'success' => true,
                'suggestions' => $this->contextualSuggestions($mode, $projectFiles, $previewHtml, $previewTitle, $promptContext, $existingSections),
            ]);
        }

        // Improve mode: generate dynamic suggestions from AI based on the current project
        $fileNames = [];
        foreach ($projectFiles as $file) {
            $path = is_array($file) && isset($file['path']) ? (string) $file['path'] : (is_object($file) ? ($file->path ?? '') : '');
            if ($path !== '') {
                $fileNames[] = $path;
            }
        }
        $fileNames = array_slice($fileNames, 0, 30);

        $htmlSnippet = $previewHtml !== '' ? substr(strip_tags($previewHtml), 0, 1200) : '';

        // Collect file content snippets for richer context
        $fileContentSnippets = [];
        foreach (array_slice($projectFiles, 0, 15) as $file) {
            if (!is_array($file)) continue;
            $path = is_string($file['path'] ?? null) ? (string) $file['path'] : '';
            $content = is_string($file['content'] ?? null) ? (string) $file['content'] : '';
            if ($path !== '' && $content !== '' && str_starts_with($path, 'src/pages/')) {
                $fileContentSnippets[] = substr($content, 0, 800);
            }
        }
        $contentContext = implode("\n---\n", array_slice($fileContentSnippets, 0, 5));

        $system = implode("\n", [
            'You are a senior product engineer helping improve a generated website or app.',
            'Analyze the actual page/app context below and generate exactly 4-5 specific, high-value next-step suggestions.',
            'Each suggestion must be tightly relevant to what was actually built. Reference concrete elements that exist in the page (for example a contact form, product cards, a chart, a dashboard table, a navbar, empty states).',
            'Prefer actionable follow-ups such as connecting a form to a backend (Supabase, Firebase, an API), adding authentication, persisting data, adding real interactions, wiring buttons, or making a static section functional.',
            'For example, if the page has a contact form, suggest "Connect contact form to Supabase" with a prompt that adds form handling and data persistence.',
            'Do NOT suggest generic marketing blocks (testimonials, pricing, FAQ) unless the page is clearly missing them and they fit.',
            'Output ONLY a JSON array of objects with this shape (no markdown, no code fences):',
            '[{"label": "Connect contact form to Supabase", "icon": "🔌", "prompt": "Wire up the contact form so submissions are saved to a Supabase table, with loading, success, and error states."}]',
            'Keep labels concise (2-6 words). Keep icons as single emoji or null. Keep prompts detailed enough for an AI to act on them.',
        ]);

        $userParts = [];
        if ($previewTitle !== '') {
            $userParts[] = 'Page/app title: ' . $previewTitle;
        }
        if ($promptContext !== '') {
            $userParts[] = 'Original request: ' . $promptContext;
        }
        if (count($fileNames) > 0) {
            $userParts[] = 'Project files: ' . implode(', ', $fileNames);
        }
        if ($htmlSnippet !== '') {
            $userParts[] = 'Current page text preview: ' . $htmlSnippet;
        }
        if ($contentContext !== '') {
            $userParts[] = 'Page component code snippets:\n' . $contentContext;
        }
        $user = implode("\n", $userParts);

        try {
            $result = $router->generateJsonObject(
                $model,
                $system,
                $user,
                2000,
                'AI Builder Suggestions'
            );

            $suggestions = is_array($result['json'] ?? null) ? $result['json'] : null;
            if (!is_array($suggestions) || count($suggestions) === 0) {
                return response()->json([
                    'success' => true,
                    'suggestions' => [],
                ]);
            }

            $normalized = [];
            foreach ($suggestions as $s) {
                if (!is_array($s)) continue;
                $label = is_string($s['label'] ?? null) ? trim($s['label']) : '';
                $prompt = is_string($s['prompt'] ?? null) ? trim($s['prompt']) : '';
                if ($label === '' || $prompt === '') continue;
                $normalized[] = [
                    'label' => $label,
                    'prompt' => $prompt,
                    'icon' => is_string($s['icon'] ?? null) ? trim($s['icon']) : null,
                ];
            }

            // Improve mode: keep all AI suggestions. The section-duplicate filter is NOT
            // applied here because relevant suggestions reference existing page elements
            // (e.g. "Connect contact form to Supabase"), which would be wrongly dropped.
            $normalized = array_slice($normalized, 0, 5);

            return response()->json([
                'success' => true,
                'suggestions' => $normalized,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'suggestions' => [],
            ]);
        }
    }

    private function blankSuggestions(): array
    {
        return $this->contextualSuggestions('blank');
    }

    private function initialSuggestions(): array
    {
        $raw = Setting::get('ai_builder_initial_suggestions', []);
        if (!is_array($raw)) {
            return [];
        }

        $suggestions = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = is_string($item['title'] ?? null) ? trim($item['title']) : '';
            $description = is_string($item['description'] ?? null) ? trim($item['description']) : '';
            if ($title === '' || $description === '') {
                continue;
            }
            $suggestions[] = [
                'label' => $title,
                'prompt' => $description,
                'icon' => is_string($item['icon'] ?? null) && trim($item['icon']) !== '' ? trim($item['icon']) : null,
            ];
        }

        return array_slice($suggestions, 0, 6);
    }

    private function fallbackSuggestions(array $existingSections = []): array
    {
        return $this->contextualSuggestions('improve', [], '', '', '', $existingSections);
    }

    private function contextualSuggestions(string $mode, array $projectFiles = [], string $previewHtml = '', string $previewTitle = '', string $promptContext = '', array $existingSections = []): array
    {
        $projectText = $this->collectProjectSuggestionText($projectFiles, $previewHtml, $previewTitle, $promptContext);
        $isAppLike = preg_match('/\b(app|dashboard|admin|login|signup|auth|task|todo|crm|workspace|settings|users|table)\b/i', $projectText) === 1;
        $isLandingLike = preg_match('/\b(landing|website|homepage|home page|hero|marketing|product page|portfolio|pricing)\b/i', $projectText) === 1;
        $isEcommerceLike = preg_match('/\b(shop|store|product|catalog|cart|checkout|order)\b/i', $projectText) === 1;

        if ($mode === 'blank') {
            if ($isAppLike) {
                $suggestions = [
                    ['label' => 'Add search and filters', 'icon' => '🔎', 'prompt' => 'Add searchable filters and quick sort controls so users can find records, tasks, or entries faster inside the app.'],
                    ['label' => 'Add summary cards', 'icon' => '📊', 'prompt' => 'Add a top summary strip with the most important counts, trends, or status totals for the app homepage.'],
                    ['label' => 'Add activity feed', 'icon' => '🕒', 'prompt' => 'Add a recent activity feed that shows the latest actions, updates, or changes in a compact timeline.'],
                    ['label' => 'Add settings panel', 'icon' => '⚙️', 'prompt' => 'Add a dedicated settings panel for preferences, profile options, and workspace configuration.'],
                    ['label' => 'Improve mobile layout', 'icon' => '📱', 'prompt' => 'Improve the mobile layout with tighter spacing, stacked cards, and touch-friendly controls.'],
                    ['label' => 'Add empty states', 'icon' => '📭', 'prompt' => 'Add helpful empty states that explain what the user should do when there is no data yet.'],
                ];
            } elseif ($isEcommerceLike) {
                $suggestions = [
                    ['label' => 'Add product filters', 'icon' => '🧭', 'prompt' => 'Add category, price, and rating filters so shoppers can browse products more easily.'],
                    ['label' => 'Add cart summary', 'icon' => '🛒', 'prompt' => 'Add a sticky cart summary with item count, subtotal, and a clear checkout action.'],
                    ['label' => 'Add product details', 'icon' => '🪪', 'prompt' => 'Add a product details panel or modal with larger imagery, description, variants, and related items.'],
                    ['label' => 'Add trust badges', 'icon' => '✅', 'prompt' => 'Add shipping, returns, and secure checkout trust badges near the purchase area.'],
                    ['label' => 'Improve checkout flow', 'icon' => '🧾', 'prompt' => 'Simplify the checkout flow with fewer steps, clearer form grouping, and progress indicators.'],
                    ['label' => 'Improve mobile shopping', 'icon' => '📱', 'prompt' => 'Improve the mobile shopping experience with larger touch targets and stacked product cards.'],
                ];
            } elseif ($isLandingLike) {
                $suggestions = [
                    ['label' => 'Add testimonials', 'icon' => '💬', 'prompt' => 'Add a testimonial section with short quotes, names, and trust-building social proof.'],
                    ['label' => 'Add pricing section', 'icon' => '💳', 'prompt' => 'Add a pricing section with clear feature comparison and a highlighted recommended plan.'],
                    ['label' => 'Add FAQ block', 'icon' => '❓', 'prompt' => 'Add an FAQ accordion to answer common questions and reduce friction before conversion.'],
                    ['label' => 'Strengthen CTA', 'icon' => '🚀', 'prompt' => 'Strengthen the main call-to-action with clearer copy, contrast, and repeated conversion points.'],
                    ['label' => 'Add contact form', 'icon' => '✉️', 'prompt' => 'Add a concise contact form with name, email, message, and validation feedback.'],
                    ['label' => 'Add trust signals', 'icon' => '🛡️', 'prompt' => 'Add trust signals such as logos, stats, guarantees, or security badges near the hero or CTA.'],
                ];
            } else {
                $suggestions = [
                    ['label' => 'Add stronger CTA', 'icon' => '🚀', 'prompt' => 'Add a clearer primary call-to-action so the page has a stronger conversion focus.'],
                    ['label' => 'Improve mobile layout', 'icon' => '📱', 'prompt' => 'Improve spacing and stacking on smaller screens so the page feels polished on mobile.'],
                    ['label' => 'Add loading states', 'icon' => '⏳', 'prompt' => 'Add skeleton loading states and lightweight transitions for asynchronous content.'],
                    ['label' => 'Add empty states', 'icon' => '📭', 'prompt' => 'Add friendly empty states that guide the user when no content is available yet.'],
                    ['label' => 'Add social proof', 'icon' => '⭐', 'prompt' => 'Add testimonials, reviews, or client logos to make the page feel more trustworthy.'],
                    ['label' => 'Tighten hierarchy', 'icon' => '🧱', 'prompt' => 'Improve visual hierarchy with stronger headings, spacing, and clearer section grouping.'],
                ];
            }

            return $this->filterSuggestionsForExistingSections($suggestions, $existingSections);
        }

        if ($isAppLike) {
            $suggestions = [
                ['label' => 'Add role-based access', 'icon' => '🔐', 'prompt' => 'Add role-based access or permission-aware navigation so different users see the right screens.'],
                ['label' => 'Add search and filters', 'icon' => '🔎', 'prompt' => 'Add search, filters, and sorting so users can find records more quickly.'],
                ['label' => 'Add analytics cards', 'icon' => '📈', 'prompt' => 'Add compact analytics cards with key totals, trends, and status summaries.'],
                ['label' => 'Add export actions', 'icon' => '📤', 'prompt' => 'Add CSV or PDF export actions for tables and reports so users can reuse the data.'],
                ['label' => 'Add notifications', 'icon' => '🔔', 'prompt' => 'Add notification states for success, warnings, and important system updates.'],
                ['label' => 'Refine empty states', 'icon' => '📭', 'prompt' => 'Refine empty states for dashboards and tables so the app feels complete from the first load.'],
            ];
        } elseif ($isEcommerceLike) {
            $suggestions = [
                ['label' => 'Add category filtering', 'icon' => '🧭', 'prompt' => 'Add category and price filtering to make browsing the catalog faster and more relevant.'],
                ['label' => 'Add cart drawer', 'icon' => '🛒', 'prompt' => 'Add a cart drawer or sidebar that stays visible while users continue shopping.'],
                ['label' => 'Add product reviews', 'icon' => '⭐', 'prompt' => 'Add product review summaries and rating highlights to support purchase decisions.'],
                ['label' => 'Improve checkout flow', 'icon' => '🧾', 'prompt' => 'Reduce checkout friction with clearer steps, validation, and a stronger order summary.'],
                ['label' => 'Add inventory status', 'icon' => '📦', 'prompt' => 'Show stock status and availability cues so shoppers know what can be purchased now.'],
                ['label' => 'Improve mobile browsing', 'icon' => '📱', 'prompt' => 'Improve the mobile browsing and checkout experience with larger tap targets and compact layouts.'],
            ];
        } elseif ($isLandingLike) {
            $suggestions = [
                ['label' => 'Add testimonials', 'icon' => '💬', 'prompt' => 'Add a testimonial section to strengthen trust and social proof on the page.'],
                ['label' => 'Add pricing tier', 'icon' => '💳', 'prompt' => 'Add a pricing or package section to help users compare options more easily.'],
                ['label' => 'Add FAQ section', 'icon' => '❓', 'prompt' => 'Add an FAQ section to answer objections and reduce pre-signup friction.'],
                ['label' => 'Strengthen CTA', 'icon' => '🚀', 'prompt' => 'Improve the primary CTA with clearer copy and higher contrast so conversions stand out.'],
                ['label' => 'Add contact block', 'icon' => '✉️', 'prompt' => 'Add a compact contact block or lead form for visitors who need a quick way to reach out.'],
                ['label' => 'Add trust logos', 'icon' => '🛡️', 'prompt' => 'Add customer logos or trust signals near the hero and CTA sections.'],
            ];
        } else {
            $suggestions = [
                ['label' => 'Improve mobile layout', 'icon' => '📱', 'prompt' => 'Improve the spacing, stacking, and touch targets so the experience feels polished on mobile.'],
                ['label' => 'Add loading states', 'icon' => '⏳', 'prompt' => 'Add skeletons, placeholders, and smooth loading feedback for async content.'],
                ['label' => 'Add empty states', 'icon' => '📭', 'prompt' => 'Add helpful empty states that explain what users should do when the page has no content yet.'],
                ['label' => 'Tighten hierarchy', 'icon' => '🧱', 'prompt' => 'Strengthen visual hierarchy with better spacing, heading contrast, and section grouping.'],
                ['label' => 'Add stronger CTA', 'icon' => '🚀', 'prompt' => 'Add a clearer primary call-to-action so the page has a more obvious next step.'],
                ['label' => 'Add social proof', 'icon' => '⭐', 'prompt' => 'Add testimonials, metrics, or logos to make the page feel more credible.'],
            ];
        }

        return $this->filterSuggestionsForExistingSections($suggestions, $existingSections);
    }

    private function normalizeSuggestionText(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? ''));
    }

    private function collectProjectSuggestionText(array $projectFiles = [], string $previewHtml = '', string $previewTitle = '', string $promptContext = ''): string
    {
        $parts = [];

        if ($previewTitle !== '') {
            $parts[] = $previewTitle;
        }
        if ($promptContext !== '') {
            $parts[] = $promptContext;
        }
        if ($previewHtml !== '') {
            $parts[] = strip_tags($previewHtml);
        }

        foreach (array_slice($projectFiles, 0, 30) as $file) {
            if (!is_array($file)) {
                continue;
            }

            if (is_string($file['path'] ?? null) && trim((string) $file['path']) !== '') {
                $parts[] = (string) $file['path'];
            }

            if (is_string($file['content'] ?? null) && trim((string) $file['content']) !== '') {
                $parts[] = substr((string) $file['content'], 0, 1200);
            }
        }

        return strtolower(implode("\n", $parts));
    }

    private function detectExistingProjectSections(array $projectFiles = [], string $previewHtml = '', string $previewTitle = '', string $promptContext = ''): array
    {
        $text = $this->collectProjectSuggestionText($projectFiles, $previewHtml, $previewTitle, $promptContext);
        if ($text === '') {
            return [];
        }

        $keywords = [
            'hero' => ['hero', 'masthead', 'banner'],
            'testimonials' => ['testimonial', 'testimonials', 'review', 'reviews', 'social proof', 'quotes', 'quote'],
            'pricing' => ['pricing', 'price', 'plans', 'plan', 'tiers', 'subscription'],
            'faq' => ['faq', 'questions', 'accordion'],
            'contact' => ['contact', 'reach out', 'message us', 'form'],
            'features' => ['feature', 'features', 'benefit', 'benefits', 'highlights'],
            'navigation' => ['navbar', 'navigation', 'header', 'menu', 'nav'],
            'footer' => ['footer'],
            'about' => ['about', 'mission', 'story'],
            'team' => ['team', 'members', 'people'],
            'gallery' => ['gallery', 'portfolio', 'showcase'],
            'blog' => ['blog', 'articles', 'posts'],
            'cta' => ['call to action', 'cta', 'get started', 'subscribe now', 'start free'],
            'newsletter' => ['newsletter', 'subscribe', 'mailing list'],
            'dashboard' => ['dashboard', 'analytics', 'metrics', 'charts', 'overview'],
            'mobile' => ['mobile', 'responsive', 'breakpoint'],
            'social_proof' => ['social proof', 'trust badge', 'trust badges', 'client logo', 'client logos'],
            'case_studies' => ['case study', 'case studies', 'customer story', 'customer stories'],
        ];

        $sections = [];
        foreach ($keywords as $section => $terms) {
            foreach ($terms as $term) {
                if (str_contains($text, $term)) {
                    $sections[] = $section;
                    break;
                }
            }
        }

        return array_values(array_unique($sections));
    }

    private function suggestionLooksLikeDuplicateSection(array $suggestion, array $existingSections): bool
    {
        if (count($existingSections) === 0) {
            return false;
        }

        $label = $this->normalizeSuggestionText((string) ($suggestion['label'] ?? ''));
        $prompt = $this->normalizeSuggestionText((string) ($suggestion['prompt'] ?? ''));
        $text = trim($label . ' ' . $prompt);
        if ($text === '') {
            return false;
        }

        if (!preg_match('/\b(add|create|include|insert|build|introduce|feature)\b/i', $text)) {
            return false;
        }

        $keywords = [
            'hero' => ['hero', 'masthead', 'banner'],
            'testimonials' => ['testimonial', 'testimonials', 'review', 'reviews', 'social proof', 'quotes', 'quote'],
            'pricing' => ['pricing', 'price', 'plans', 'plan', 'tiers', 'subscription'],
            'faq' => ['faq', 'questions', 'accordion'],
            'contact' => ['contact', 'reach out', 'message us', 'form'],
            'features' => ['feature', 'features', 'benefit', 'benefits', 'highlights'],
            'navigation' => ['navbar', 'navigation', 'header', 'menu', 'nav'],
            'footer' => ['footer'],
            'about' => ['about', 'mission', 'story'],
            'team' => ['team', 'members', 'people'],
            'gallery' => ['gallery', 'portfolio', 'showcase'],
            'blog' => ['blog', 'articles', 'posts'],
            'cta' => ['call to action', 'cta', 'get started', 'subscribe now', 'start free'],
            'newsletter' => ['newsletter', 'subscribe', 'mailing list'],
            'dashboard' => ['dashboard', 'analytics', 'metrics', 'charts', 'overview'],
            'mobile' => ['mobile', 'responsive', 'breakpoint'],
            'social_proof' => ['social proof', 'trust badge', 'trust badges', 'client logo', 'client logos'],
            'case_studies' => ['case study', 'case studies', 'customer story', 'customer stories'],
        ];

        foreach ($existingSections as $section) {
            $terms = $keywords[$section] ?? [];
            foreach ($terms as $term) {
                if ($term !== '' && str_contains($text, $term)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function filterSuggestionsForExistingSections(array $suggestions, array $existingSections): array
    {
        if (count($existingSections) === 0) {
            return $suggestions;
        }

        return array_values(array_filter($suggestions, function ($suggestion) use ($existingSections) {
            if (!is_array($suggestion)) {
                return false;
            }

            return !$this->suggestionLooksLikeDuplicateSection($suggestion, $existingSections);
        }));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'page_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'html' => ['required', 'string'],
            'project_files' => ['nullable', 'array'],
            'react_code' => ['nullable', 'string'],
            'preview_mode' => ['nullable', 'string', 'in:html,react'],
            'description' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'string', 'in:draft,publish'],
            'publish_visibility' => ['nullable', 'string', 'in:public,private,password'],
            'publish_password' => ['nullable', 'string', 'max:255'],
            'app_title' => ['nullable', 'string', 'max:255'],
            'app_logo' => ['nullable', 'string', 'max:65535'],
            'app_icon' => ['nullable', 'string', 'max:65535'],
            'social_image' => ['nullable', 'string', 'max:65535'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ]);

        $pageId = filter_var($validated['page_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $title = trim((string) $validated['title']);
        $slug = trim((string) ($validated['slug'] ?? ''));
        $html = (string) $validated['html'];
        $projectFiles = is_array($validated['project_files'] ?? null) ? $validated['project_files'] : null;
        $reactCode = is_string($validated['react_code'] ?? null) ? trim((string) $validated['react_code']) : null;
        $previewMode = is_string($validated['preview_mode'] ?? null) ? trim((string) $validated['preview_mode']) : 'html';
        $description = is_string($validated['description'] ?? null) ? trim((string) $validated['description']) : '';
        $status = (string) ($validated['status'] ?? 'draft');
        $publishVisibility = is_string($validated['publish_visibility'] ?? null) ? trim((string) $validated['publish_visibility']) : 'public';
        $publishPassword = is_string($validated['publish_password'] ?? null) ? trim((string) $validated['publish_password']) : '';
        $appTitle = is_string($validated['app_title'] ?? null) ? trim((string) $validated['app_title']) : '';
        $appLogo = is_string($validated['app_logo'] ?? null) ? trim((string) $validated['app_logo']) : '';
        $appIcon = is_string($validated['app_icon'] ?? null) ? trim((string) $validated['app_icon']) : '';
        $socialImage = is_string($validated['social_image'] ?? null) ? trim((string) $validated['social_image']) : '';

        $page = null;
        if ($pageId) {
            $page = Page::find($pageId);
        }

        $isUpdate = $page instanceof Page;
        $customerId = filter_var($validated['customer_id'] ?? null, FILTER_VALIDATE_INT) ?: ($page?->customer_id ?: null);

        if ($slug === '') {
            $slug = Str::slug($title);
        }

        // Ensure unique slug only when creating or when the slug actually changed
        $originalSlug = $slug;
        $counter = 1;
        $existingQuery = Page::where('slug', $slug);
        if ($isUpdate) {
            $existingQuery->where('id', '!=', $page->id);
        }
        while ($existingQuery->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            $existingQuery = Page::where('slug', $slug);
            if ($isUpdate) {
                $existingQuery->where('id', '!=', $page->id);
            }
        }

        $appMeta = array_filter([
            'app_title' => $appTitle !== '' ? $appTitle : null,
            'app_logo' => $appLogo !== '' ? $appLogo : null,
            'app_icon' => $appIcon !== '' ? $appIcon : null,
            'meta_description' => $description !== '' ? $description : null,
            'social_image' => $socialImage !== '' ? $socialImage : null,
        ], fn ($value) => $value !== null && $value !== '');

        $html = $this->injectAppBrandingIntoHtml($html, $appMeta, $previewMode);

        $builderData = array_filter([
            'source' => 'page_ai_builder',
            'preview_mode' => $previewMode,
            'project_files' => $projectFiles,
            'react_code' => $reactCode,
            'meta_description' => $description !== '' ? $description : null,
            'meta_image' => $socialImage !== '' ? $socialImage : null,
            'app_meta' => !empty($appMeta) ? $appMeta : null,
            'publish_meta' => [
                'visibility' => $publishVisibility,
                'password_hash' => $publishVisibility === 'password' && $publishPassword !== '' ? Hash::make($publishPassword) : null,
            ],
        ], fn ($value) => $value !== null && $value !== '');

        $pageData = [
            'customer_id' => $customerId,
            'type' => 'page',
            'title' => $title,
            'slug' => $slug,
            'status' => $status,
            'html_content' => $html,
            'builder_data' => $builderData,
        ];

        if ($isUpdate) {
            $page->update($pageData);
        } else {
            $page = Page::create($pageData);
        }

        $sessionId = $validated['session_id'] ?? null;
        if ($sessionId) {
            $session = AiBuilderSession::where('session_id', $sessionId)->first();
            if ($session) {
                $session->update(['page_id' => $page->id]);
            }
        }

        return response()->json([
            'success' => true,
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'url' => route('admin.pages.builder', $page),
                'public_url' => $this->buildPublicUrl($request, $page->slug),
                'publish_visibility' => $publishVisibility,
                'custom_domain' => $page->custom_domain,
                'app_meta' => $appMeta,
            ],
        ]);
    }

    public function updateCustomDomain(Request $request)
    {
        $validated = $request->validate([
            'page_id' => ['required', 'integer'],
            'custom_domain' => ['nullable', 'string', 'max:255'],
        ]);

        $page = Page::findOrFail($validated['page_id']);
        $domain = $validated['custom_domain'] ?? null;
        $domain = is_string($domain) ? strtolower(trim($domain)) : null;
        $domain = $domain === '' ? null : $domain;

        // Basic domain format validation
        if ($domain !== null && !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $domain)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid domain format.',
            ], 422);
        }

        $page->update(['custom_domain' => $domain]);

        return response()->json([
            'success' => true,
            'page' => [
                'id' => $page->id,
                'custom_domain' => $page->custom_domain,
            ],
        ]);
    }

    private function buildPublicUrl(Request $request, string $slug): string
    {
        $host = $request->getHttpHost();
        $hostWithoutPort = preg_replace('/:\d+$/', '', $host);

        if ($hostWithoutPort === 'localhost' || filter_var($hostWithoutPort, FILTER_VALIDATE_IP)) {
            return $request->getSchemeAndHttpHost() . '/pages/' . $slug;
        }

        return $slug . '.' . $host;
    }

    private function buildSandboxPayload(ViteSandboxCompiler $sandboxCompiler, PageHtmlGenerator $pageHtmlGenerator, string $model, string $sessionId, array $result): array
    {
        if (($result['preview_mode'] ?? 'html') !== 'react' || !is_string($result['react_code'] ?? null) || trim((string) $result['react_code']) === '') {
            return [
                'project_files' => null,
                'preview_url' => null,
                'build_log' => null,
                'sandbox_session_id' => $sessionId,
            ];
        }

        // Get the default template (boilerplate: package.json, index.html, main.jsx, styles.css, etc.)
        $defaultFiles = $sandboxCompiler->defaultProjectFiles(
            '',
            is_string($result['title'] ?? null) ? (string) $result['title'] : 'Generated Page',
            is_string($result['preview_html'] ?? null) ? (string) $result['preview_html'] : null
        );

        $mergedFiles = [];

        // Index default template files by path
        foreach ($defaultFiles as $file) {
            $mergedFiles[$file['path']] = $file;
        }

        // If the AI generated multi-file artifacts, merge them (they override defaults)
        $aiFiles = is_array($result['project_files'] ?? null) ? $result['project_files'] : null;
        if (!empty($aiFiles)) {
            foreach ($aiFiles as $file) {
                if (!is_array($file) || !is_string($file['path'] ?? null)) {
                    continue;
                }
                $path = trim((string) $file['path']);
                if ($path === '') {
                    continue;
                }
                $mergedFiles[$path] = [
                    'path' => $path,
                    'content' => is_string($file['content'] ?? null) ? $file['content'] : '',
                ];
            }
        } else {
            // Single-file fallback: place react_code into src/pages/{Component}.jsx
            $componentName = $sandboxCompiler->detectComponentName((string) $result['react_code']) ?: 'LandingPage';
            $pagePath = "src/pages/{$componentName}.jsx";
            $pageSource = trim((string) $result['react_code']);
            if (!preg_match('/\bexport\s+default\b/m', $pageSource)) {
                $pageSource .= "\n\nexport default {$componentName};\n";
            }
            $mergedFiles[$pagePath] = [
                'path' => $pagePath,
                'content' => $pageSource,
            ];
        }

        // Track which paths came from AI so we can prefer them over placeholder defaults
        $aiFilePaths = [];
        $aiHasExplicitApp = false;
        if (!empty($aiFiles)) {
            foreach ($aiFiles as $file) {
                if (!is_array($file) || !is_string($file['path'] ?? null)) {
                    continue;
                }
                $path = trim((string) $file['path']);
                $aiFilePaths[] = $path;
                if (preg_match('#^src/App\.(jsx|tsx)$#i', $path)) {
                    $aiHasExplicitApp = true;
                }
            }
        }

        // If AI generated a page, remove the default placeholder page to avoid confusion
        $aiHasPage = false;
        foreach ($aiFilePaths as $path) {
            if (preg_match('#^src/pages/.*\.(jsx|tsx)$#i', $path)) {
                $aiHasPage = true;
                break;
            }
        }
        if ($aiHasPage && isset($mergedFiles['src/pages/LandingPage.jsx'])) {
            unset($mergedFiles['src/pages/LandingPage.jsx']);
        }

        // Find the main page component - prefer AI pages, then defaults
        $pageComponentPath = null;
        $defaultPagePath = null;
        foreach ($mergedFiles as $path => $file) {
            if (preg_match('#^src/pages/.*\.(jsx|tsx)$#i', $path)) {
                if (in_array($path, $aiFilePaths, true)) {
                    $pageComponentPath = $path;
                    break;
                }
                if ($defaultPagePath === null) {
                    $defaultPagePath = $path;
                }
            }
        }
        if ($pageComponentPath === null) {
            $pageComponentPath = $defaultPagePath;
        }

        if ($pageComponentPath !== null && !$aiHasExplicitApp) {
            $componentName = $sandboxCompiler->detectComponentName($mergedFiles[$pageComponentPath]['content']) ?: 'LandingPage';
            $importPath = './pages/' . basename($pageComponentPath);
            $mergedFiles['src/App.jsx'] = [
                'path' => 'src/App.jsx',
                'content' => "import Page from '{$importPath}';\n\nexport default function App() {\n    return <Page />;\n}\n",
            ];
        }

        // Iterative compile-fix loop (max 3 attempts)
        $attempt = 0;
        $maxAttempts = 3;
        $lastError = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            $compiled = $sandboxCompiler->compile($sessionId, array_values($mergedFiles));

            if ($compiled['success']) {
                $previewUrl = route('admin.ai-projects.page-ai-builder.sandbox.preview', ['session' => $sessionId, 'path' => 'index.html']);
                $sandboxBaseUrl = rtrim(str_replace('/index.html', '', $previewUrl), '/');
                $distHtml = $compiled['dist_index_html'];
                if (is_string($distHtml) && $distHtml !== '') {
                    $distHtml = $this->rewriteSandboxAssetUrls($distHtml, $sandboxBaseUrl);
                }
                return [
                    'project_files' => $compiled['project_files'],
                    'preview_url' => $previewUrl,
                    'build_log' => $compiled['build_log'] !== '' ? $compiled['build_log'] : null,
                    'sandbox_session_id' => $sessionId,
                    'preview_html' => $distHtml,
                ];
            }

            $lastError = $compiled['error'] ?? 'Unknown build error';

            if ($attempt < $maxAttempts) {
                // Extract only the source files (not boilerplate) for the AI fix
                $sourceFiles = [];
                foreach ($mergedFiles as $path => $file) {
                    if (str_starts_with($path, 'src/')) {
                        $sourceFiles[] = $file;
                    }
                }

                $fixedFiles = $pageHtmlGenerator->fixReactBuildErrors($sourceFiles, $lastError, $model);

                if ($fixedFiles !== null) {
                    // Merge fixed files back
                    foreach ($fixedFiles as $file) {
                        $mergedFiles[$file['path']] = $file;
                    }
                } else {
                    // AI fix failed, break out
                    break;
                }
            }
        }

        // All attempts failed — return error info so the frontend can show it
        return [
            'project_files' => array_values($mergedFiles),
            'preview_url' => null,
            'build_log' => $lastError,
            'sandbox_session_id' => $sessionId,
            'preview_html' => null,
        ];
    }

    private function mergeProjectFilesForIteration(array $sessionFiles, array $requestFiles): array
    {
        $map = [];
        foreach ($sessionFiles as $file) {
            if (is_array($file) && is_string($file['path'] ?? null)) {
                $map[$file['path']] = $file;
            }
        }
        foreach ($requestFiles as $file) {
            if (is_array($file) && is_string($file['path'] ?? null)) {
                $map[$file['path']] = $file;
            }
        }
        return array_values($map);
    }

    public function getShares(Request $request, string $session)
    {
        $sessionModel = AiBuilderSession::where('session_id', $session)->firstOrFail();
        $this->authorizeAccess($sessionModel);

        $owner = $sessionModel->adminUser;

        $shares = \App\Models\AiBuilderProjectShare::forSession($session)
            ->get()
            ->map(function ($share) {
                return [
                    'id' => $share->id,
                    'email' => $share->email,
                    'role' => $share->role,
                    'invite_token' => $share->invite_token,
                    'accepted_at' => $share->accepted_at?->toISOString(),
                ];
            });

        $admin = auth('admin')->user();
        $isOwner = $admin && $sessionModel->admin_user_id === $admin->id;

        $currentUserRole = null;
        if ($isOwner) {
            $currentUserRole = 'owner';
        } elseif ($admin) {
            $userShare = \App\Models\AiBuilderProjectShare::forSession($session)
                ->where('email', $admin->email)
                ->whereNotNull('accepted_at')
                ->first();
            $currentUserRole = $userShare?->role;
        }

        return response()->json([
            'success' => true,
            'shares' => $shares,
            'owner' => $owner ? [
                'name' => $owner->name ?? null,
                'email' => $owner->email ?? null,
            ] : null,
            'is_owner' => $isOwner,
            'current_user_role' => $currentUserRole,
            'invite_link' => ($firstShare = $shares->first(fn ($share) => !empty($share['invite_token'] ?? null)))
                ? route('admin.ai-projects.page-ai-builder.accept-invite', ['token' => data_get($firstShare, 'invite_token')])
                : null,
        ]);
    }

    public function inviteCollaborator(Request $request, string $session)
    {
        $sessionModel = AiBuilderSession::where('session_id', $session)->firstOrFail();
        $this->authorizeAccess($sessionModel);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:editor,viewer'],
        ]);

        $email = trim((string) $validated['email']);
        $role = (string) $validated['role'];

        // Check if already shared
        $existing = \App\Models\AiBuilderProjectShare::forSession($session)
            ->where('email', $email)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already shared on this project.',
            ], 422);
        }

        $inviteToken = bin2hex(random_bytes(32));

        $share = \App\Models\AiBuilderProjectShare::create([
            'session_id' => $session,
            'email' => $email,
            'role' => $role,
            'invite_token' => $inviteToken,
            'accepted_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'share' => [
                'id' => $share->id,
                'email' => $share->email,
                'role' => $share->role,
                'invite_token' => $share->invite_token,
                'accepted_at' => $share->accepted_at?->toISOString(),
            ],
            'invite_link' => route('admin.ai-projects.page-ai-builder.accept-invite', ['token' => $inviteToken]),
        ]);
    }

    public function updateCollaboratorRole(Request $request, string $session, int $shareId)
    {
        $sessionModel = AiBuilderSession::where('session_id', $session)->firstOrFail();
        $this->authorizeAccess($sessionModel);

        $validated = $request->validate([
            'role' => ['required', 'in:editor,viewer'],
        ]);

        $share = \App\Models\AiBuilderProjectShare::forSession($session)
            ->where('id', $shareId)
            ->firstOrFail();

        $share->update(['role' => (string) $validated['role']]);

        return response()->json([
            'success' => true,
            'share' => [
                'id' => $share->id,
                'email' => $share->email,
                'role' => $share->role,
                'invite_token' => $share->invite_token,
                'accepted_at' => $share->accepted_at?->toISOString(),
            ],
        ]);
    }

    public function removeCollaborator(Request $request, string $session, int $shareId)
    {
        $sessionModel = AiBuilderSession::where('session_id', $session)->firstOrFail();
        $this->authorizeAccess($sessionModel);

        $share = \App\Models\AiBuilderProjectShare::forSession($session)
            ->where('id', $shareId)
            ->firstOrFail();

        $share->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function acceptInvite(Request $request, string $token)
    {
        $share = \App\Models\AiBuilderProjectShare::where('invite_token', $token)->first();

        if (!$share) {
            abort(404, 'Invalid or expired invite link.');
        }

        $sessionModel = AiBuilderSession::where('session_id', $share->session_id)->firstOrFail();

        $admin = auth('admin')->user();

        if (!$admin) {
            return redirect()->route('admin.login')->with('error', 'Please log in to accept the invitation.');
        }

        if ($share->accepted_at) {
            return redirect()->route('admin.ai-projects.page-ai-builder.show', ['session' => $share->session_id])
                ->with('error', 'This invite has already been accepted.');
        }

        if (strtolower($admin->email) !== strtolower($share->email)) {
            return redirect()->route('admin.ai-projects.page-ai-builder.show', ['session' => $share->session_id])
                ->with('error', 'This invite was sent to ' . $share->email . '. Please log in with that email.');
        }

        $share->update(['accepted_at' => now()]);

        return redirect()->route('admin.ai-projects.page-ai-builder.show', ['session' => $share->session_id])
            ->with('success', 'You now have access to this project.');
    }

    private function resolveSessionModel(?AiBuilderSession $sessionModel): string
    {
        $stored = is_string($sessionModel->model ?? null) ? trim((string) $sessionModel->model) : '';
        if ($stored !== '' && strtolower($stored) !== 'auto') {
            return $stored;
        }

        return ModelRouter::resolve('worker');
    }

    private function authorizeAccess(AiBuilderSession $session)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        // Allow access if user is the owner
        if ($session->admin_user_id === $admin->id) {
            return;
        }

        // Check if user is a collaborator
        $share = \App\Models\AiBuilderProjectShare::forSession($session->session_id)
            ->where('email', $admin->email)
            ->whereNotNull('accepted_at')
            ->first();

        if (!$share) {
            abort(403, 'Unauthorized');
        }
    }
}

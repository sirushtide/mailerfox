<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AiBuilderSession;
use App\Services\AiBuilder\AiBuilderAccessService;
use App\Services\AiBuilder\AiBuilderCreditService;
use App\Services\AiBuilder\DefaultCodebase;
use App\Services\AiBuilder\ModelRouter;
use App\Services\AiBuilder\PageHtmlGenerator;
use App\Services\AiBuilder\ViteSandboxCompiler;
use App\Services\AI\AiProviderRegistry;
use App\Services\AI\AiProviderRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class PageAiBuilderController extends Controller
{
    public function index(AiBuilderAccessService $accessService, AiBuilderCreditService $creditService, AiProviderRouter $router)
    {
        $customer = auth('customer')->user();

        $providerModels = [];
        foreach (AiProviderRegistry::providerKeys() as $key) {
            if (! $router->isProviderEnabled($key)) {
                continue;
            }
            $models = AiProviderRegistry::models($key);
            foreach ($models as $model) {
                if ($router->isModelEnabled($key, $model)) {
                    $providerModels[$model] = $key;
                }
            }
        }

        return view('customer.ai-projects.page-ai-builder', [
            'sessionData' => [],
            'providerModels' => $providerModels,
            'aiBuilderCapabilities' => $accessService->capabilities($customer),
            'aiBuilderCreditStatus' => $creditService->status($customer),
        ]);
    }

    public function projects(Request $request)
    {
        $customer = auth('customer')->user();

        $sessions = AiBuilderSession::query()
            ->where('customer_id', $customer->id)
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        $sessions->getCollection()->transform(function ($session) {
            $session->thumbnail_url = $session->preview_mode === 'react'
                ? route('customer.ai-projects.builder.sandbox.preview', ['session' => $session->session_id, 'path' => 'index.html'])
                : null;
            return $session;
        });

        return view('customer.ai-projects.index', [
            'sessions' => $sessions,
        ]);
    }

    public function show(string $session, AiBuilderAccessService $accessService, AiBuilderCreditService $creditService, AiProviderRouter $router)
    {
        $customer = auth('customer')->user();
        $sessionModel = AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $sessionModel) {
            return redirect()->route('customer.ai-projects.index');
        }

        $providerModels = [];
        foreach (AiProviderRegistry::providerKeys() as $key) {
            if (! $router->isProviderEnabled($key)) {
                continue;
            }
            $models = AiProviderRegistry::models($key);
            foreach ($models as $model) {
                if ($router->isModelEnabled($key, $model)) {
                    $providerModels[$model] = $key;
                }
            }
        }

        $sessionData = [
            'session_id' => $sessionModel->session_id,
            'messages' => $sessionModel->messages ?? [],
            'preview_html' => $sessionModel->preview_html ?? '',
            'project_files' => $sessionModel->project_files ?? [],
            'react_code' => $sessionModel->react_code ?? '',
            'preview_mode' => $sessionModel->preview_mode ?? 'html',
            'preview_title' => $sessionModel->preview_title ?? '',
            'page_title' => $sessionModel->page_title ?? '',
            'page_slug' => $sessionModel->page_slug ?? '',
            'page_status' => $sessionModel->page_status ?? 'draft',
            'page_id' => $sessionModel->page_id,
            'model' => $sessionModel->model ?? '',
            'app_meta' => $sessionModel->app_meta ?? [],
        ];

        return view('customer.ai-projects.page-ai-builder', [
            'sessionData' => $sessionData,
            'providerModels' => $providerModels,
            'aiBuilderCapabilities' => $accessService->capabilities($customer),
            'aiBuilderCreditStatus' => $creditService->status($customer),
        ]);
    }

    public function storeSession(Request $request)
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'messages' => ['nullable', 'array'],
            'preview_html' => ['nullable', 'string'],
            'project_files' => ['nullable', 'array'],
            'react_code' => ['nullable', 'string'],
            'preview_mode' => ['nullable', 'string', 'in:html,react'],
            'preview_title' => ['nullable', 'string', 'max:255'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'page_slug' => ['nullable', 'string', 'max:255'],
            'page_status' => ['nullable', 'string', 'in:draft,published'],
            'model' => ['nullable', 'string', 'max:100'],
            'app_meta' => ['nullable', 'array'],
            'session_id' => ['nullable', 'string', 'max:64'],
        ]);

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
            'customer_id' => $customer->id,
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
            'url' => route('customer.ai-projects.builder.show', $session->session_id),
            'update_url' => route('customer.ai-projects.builder.session.update', $session->session_id),
        ]);
    }

    public function updateSession(Request $request, string $session)
    {
        $customer = auth('customer')->user();
        $sessionModel = AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $validated = $request->validate([
            'messages' => ['nullable', 'array'],
            'preview_html' => ['nullable', 'string'],
            'project_files' => ['nullable', 'array'],
            'react_code' => ['nullable', 'string'],
            'preview_mode' => ['nullable', 'string', 'in:html,react'],
            'preview_title' => ['nullable', 'string', 'max:255'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'page_slug' => ['nullable', 'string', 'max:255'],
            'page_status' => ['nullable', 'string', 'in:draft,published'],
            'model' => ['nullable', 'string', 'max:100'],
            'app_meta' => ['nullable', 'array'],
        ]);

        $sessionModel->update(array_filter($validated, fn ($v) => $v !== null));

        return response()->json([
            'success' => true,
            'session_id' => $sessionModel->session_id,
        ]);
    }

    public function generate(Request $request, AiBuilderAccessService $accessService, AiBuilderCreditService $creditService)
    {
        // Delegate to admin controller generate logic but scoped to customer
        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);

        // Temporarily bind customer context for session creation
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:8000'],
            'model' => ['nullable', 'string', 'max:100'],
            'session_id' => ['nullable', 'string', 'max:64'],
        ]);

        // Model access check
        $selectedModel = $validated['model'] ?? null;
        if ($selectedModel !== null && strtolower($selectedModel) !== 'auto') {
            if (! $accessService->canUseModel($customer, $selectedModel)) {
                return response()->json(['success' => false, 'message' => 'The selected model is not available on your plan.'], 403);
            }
            $request->merge(['model' => $selectedModel]);
        }

        // Credit check (raw prompt only — the theme-composed prompt inflates the estimate)
        $rawPrompt = is_string($request->input('raw_prompt')) ? trim((string) $request->input('raw_prompt')) : '';
        $estimate = $creditService->estimateGenerationTokens($rawPrompt !== '' ? $rawPrompt : ($validated['prompt'] ?? ''));
        if (! $creditService->canAfford($customer, 'generate', $estimate)) {
            return response()->json(['success' => false, 'message' => 'Not enough AI Builder credits for this generation.'], 403);
        }

        // If a session_id is provided and belongs to this customer, allow it
        if (! empty($validated['session_id'])) {
            $existingSession = AiBuilderSession::where('session_id', $validated['session_id'])->first();
            if ($existingSession && $existingSession->customer_id !== $customer->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
        }

        // Call admin generate and intercept the session creation to tag customer_id
        $response = $adminController->generate($request);

        // After generation, tag the session and generation with customer_id if it was created
        if ($response->isSuccessful()) {
            $data = $response->getData(true);
            if (! empty($data['session_id'])) {
                AiBuilderSession::where('session_id', $data['session_id'])
                    ->whereNull('customer_id')
                    ->update(['customer_id' => $customer->id]);
            }
            if (! empty($data['generation_id'])) {
                \App\Models\AiGeneration::where('id', $data['generation_id'])
                    ->whereNull('customer_id')
                    ->update(['customer_id' => $customer->id]);
            }
        }

        return $response;
    }

    public function progress(Request $request)
    {
        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);
        return $adminController->progress($request);
    }

    public function stream(Request $request)
    {
        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);
        return $adminController->stream($request);
    }

    public function cancel(Request $request)
    {
        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);
        return $adminController->cancel($request);
    }

    public function analyze(Request $request, AiProviderRouter $aiRouter)
    {
        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);
        return $adminController->analyze($request, $aiRouter);
    }

    public function suggestions(Request $request, AiProviderRouter $aiRouter)
    {
        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);
        return $adminController->suggestions($request, $aiRouter);
    }

    public function starterFiles(PageHtmlGenerator $pageHtmlGenerator)
    {
        $codebase = new DefaultCodebase();
        return response()->json([
            'success' => true,
            'project_files' => array_merge($codebase->starterFiles(), $pageHtmlGenerator->latestTemplateFiles()),
        ]);
    }

    public function iterate(Request $request, string $session, PageHtmlGenerator $pageHtmlGenerator, AiProviderRouter $aiRouter, AiBuilderAccessService $accessService, AiBuilderCreditService $creditService)
    {
        $customer = auth('customer')->user();
        $sessionModel = AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:8000'],
            'model' => ['nullable', 'string', 'max:100'],
            'mode' => ['nullable', 'string', 'in:iteration,selection,theme'],
            'segment_plan' => ['nullable', 'array'],
        ]);

        // Model access check
        $selectedModel = $validated['model'] ?? null;
        if ($selectedModel === null || strtolower($selectedModel) === 'auto') {
            $request->merge(['model' => 'auto']);
        } else {
            if (! $accessService->canUseModel($customer, $selectedModel)) {
                return response()->json(['success' => false, 'message' => 'The selected model is not available on your plan.'], 403);
            }
            $request->merge(['model' => $selectedModel]);
        }

        // Credit check (theme-only iterations don't use AI)
        $mode = $validated['mode'] ?? 'iteration';
        $operation = $mode === 'selection' ? 'selection_iteration' : ($mode === 'theme' ? null : 'iterate');
        $isContinuation = ! empty($validated['segment_plan']);
        if ($operation !== null && ! $isContinuation && ! $creditService->canAfford($customer, $operation)) {
            return response()->json(['success' => false, 'message' => 'Not enough AI Builder credits for this iteration.'], 403);
        }

        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);
        $response = $adminController->iterate($request, $session, $pageHtmlGenerator, $aiRouter);

        if ($response->isSuccessful()) {
            $data = $response->getData(true);

            // A chat reply answers a question; it is not a paid iteration.
            if (($data['type'] ?? null) !== 'chat' && $operation !== null && ! $isContinuation) {
                $creditService->deduct($customer, $operation);
            }

            // Inject updated credit status into the response
            $data['credit_status'] = $creditService->status($customer);
            $response->setData($data);
        }

        return $response;
    }

    public function discuss(Request $request, string $session, AiProviderRouter $aiRouter, AiBuilderAccessService $accessService, AiBuilderCreditService $creditService)
    {
        $customer = auth('customer')->user();
        $sessionModel = AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        if (! $accessService->canDiscuss($customer)) {
            return response()->json(['success' => false, 'message' => 'The Discuss feature is not available on your plan.'], 403);
        }

        $validated = $request->validate([
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        // Model access check
        $selectedModel = $validated['model'] ?? null;
        if ($selectedModel === null || strtolower($selectedModel) === 'auto') {
            $request->merge(['model' => 'auto']);
        } else {
            if (! $accessService->canUseModel($customer, $selectedModel)) {
                return response()->json(['success' => false, 'message' => 'The selected model is not available on your plan.'], 403);
            }
            $request->merge(['model' => $selectedModel]);
        }

        if (! $creditService->canAfford($customer, 'discuss')) {
            return response()->json(['success' => false, 'message' => 'Not enough AI Builder credits for this discussion.'], 403);
        }

        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);
        $response = $adminController->discuss($request, $session, $aiRouter);

        if ($response->isSuccessful()) {
            $creditService->deduct($customer, 'discuss');

            // Inject updated credit status into the response
            $data = $response->getData(true);
            $data['credit_status'] = $creditService->status($customer);
            $response->setData($data);
        }

        return $response;
    }

    public function customDomain(Request $request, string $session, AiBuilderAccessService $accessService)
    {
        $customer = auth('customer')->user();

        if (! $accessService->canCustomDomain($customer)) {
            return response()->json(['success' => false, 'message' => 'Custom domains are not available on your plan.'], 403);
        }

        $sessionModel = AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $validated = $request->validate([
            'custom_domain' => ['nullable', 'string', 'max:255'],
        ]);

        $appMeta = $sessionModel->app_meta ?? [];
        $appMeta['custom_domain'] = $validated['custom_domain'] ?? '';
        $sessionModel->update(['app_meta' => $appMeta]);

        return response()->json([
            'success' => true,
            'page' => ['custom_domain' => $validated['custom_domain'] ?? ''],
        ]);
    }

    public function shares(Request $request, string $session, AiBuilderAccessService $accessService)
    {
        $customer = auth('customer')->user();

        if (! $accessService->canShareProject($customer)) {
            return response()->json(['success' => false, 'message' => 'Project sharing is not available on your plan.'], 403);
        }

        $sessionModel = AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'shares' => [],
            'owner' => [
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
            ],
            'is_owner' => true,
            'current_user_role' => 'owner',
            'invite_link' => '',
        ]);
    }

    public function inviteShare(Request $request, string $session, AiBuilderAccessService $accessService)
    {
        $customer = auth('customer')->user();

        if (! $accessService->canShareProject($customer)) {
            return response()->json(['success' => false, 'message' => 'Project sharing is not available on your plan.'], 403);
        }

        AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'in:viewer,editor'],
        ]);

        return response()->json(['success' => true, 'message' => 'Collaborator invited.']);
    }

    public function updateShare(Request $request, string $session, string $share, AiBuilderAccessService $accessService)
    {
        $customer = auth('customer')->user();

        if (! $accessService->canShareProject($customer)) {
            return response()->json(['success' => false, 'message' => 'Project sharing is not available on your plan.'], 403);
        }

        AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $request->validate(['role' => ['required', 'string', 'in:viewer,editor']]);

        return response()->json(['success' => true, 'message' => 'Role updated.']);
    }

    public function removeShare(Request $request, string $session, string $share, AiBuilderAccessService $accessService)
    {
        $customer = auth('customer')->user();

        if (! $accessService->canShareProject($customer)) {
            return response()->json(['success' => false, 'message' => 'Project sharing is not available on your plan.'], 403);
        }

        AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        return response()->json(['success' => true, 'message' => 'Collaborator removed.']);
    }

    public function creditStatus(AiBuilderCreditService $creditService)
    {
        $customer = auth('customer')->user();
        return response()->json([
            'success' => true,
            'credit_status' => $creditService->status($customer),
        ]);
    }

    public function compileSandbox(Request $request, string $session, ViteSandboxCompiler $sandboxCompiler, PageHtmlGenerator $pageHtmlGenerator)
    {
        set_time_limit(600);

        $customer = auth('customer')->user();
        $sessionModel = AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $validated = $request->validate([
            'project_files' => ['required', 'array', 'min:1'],
            'allow_fix' => ['nullable', 'boolean'],
        ]);

        $model = is_string($validated['model'] ?? null) ? $validated['model'] : $sessionModel->model;
        $allowFix = filter_var($validated['allow_fix'] ?? false, FILTER_VALIDATE_BOOL);

        try {
            $compiled = $sandboxCompiler->compileWithAutoFix($session, $validated['project_files'], $pageHtmlGenerator, $model, $allowFix);
            $sessionModel->update(['project_files' => $compiled['project_files']]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Build failed: ' . $e->getMessage(),
            ], 500);
        }

        if (! $compiled['success']) {
            return response()->json([
                'success' => false,
                'message' => $compiled['error'] ?? 'Build failed.',
                'build_log' => $compiled['build_log'] !== '' ? $compiled['build_log'] : null,
                'project_files' => $compiled['project_files'] ?? null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'project_files' => $compiled['project_files'],
            'preview_html' => $compiled['dist_index_html'],
            'preview_url' => route('customer.ai-projects.builder.sandbox.preview', ['session' => $sessionModel->session_id, 'path' => 'index.html']),
            'build_log' => $compiled['build_log'] !== '' ? $compiled['build_log'] : null,
        ]);
    }

    public function serveSandbox(string $session, ViteSandboxCompiler $sandboxCompiler, ?string $path = null)
    {
        $customer = auth('customer')->user();
        // Ownership check: session must belong to this customer
        $sessionModel = AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);
        return $adminController->serveSandbox($session, $sandboxCompiler, $path);
    }

    public function save(Request $request)
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'session_id' => ['nullable', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,published'],
            'html_content' => ['nullable', 'string'],
            'react_code' => ['nullable', 'string'],
            'preview_mode' => ['nullable', 'string', 'in:html,react'],
            'publish_visibility' => ['nullable', 'string', 'in:public,private,password'],
            'publish_password' => ['nullable', 'string', 'max:255'],
            'app_title' => ['nullable', 'string', 'max:255'],
            'app_logo' => ['nullable', 'string', 'max:1000'],
            'app_icon' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'social_image' => ['nullable', 'string', 'max:1000'],
            'app_meta' => ['nullable', 'array'],
        ]);

        $appMeta = array_merge($validated['app_meta'] ?? [], [
            'app_title' => $validated['app_title'] ?? ($validated['app_meta']['app_title'] ?? ''),
            'app_logo' => $validated['app_logo'] ?? ($validated['app_meta']['app_logo'] ?? ''),
            'app_icon' => $validated['app_icon'] ?? ($validated['app_meta']['app_icon'] ?? ''),
            'description' => $validated['description'] ?? ($validated['app_meta']['description'] ?? ''),
            'social_image' => $validated['social_image'] ?? ($validated['app_meta']['social_image'] ?? ''),
            'publish_visibility' => $validated['publish_visibility'] ?? ($validated['app_meta']['publish_visibility'] ?? 'public'),
            'publish_password' => $validated['publish_visibility'] === 'password' ? ($validated['publish_password'] ?? '') : '',
            'custom_domain' => $validated['app_meta']['custom_domain'] ?? '',
        ]);

        // Update session record
        if (! empty($validated['session_id'])) {
            AiBuilderSession::where('session_id', $validated['session_id'])
                ->where('customer_id', $customer->id)
                ->update([
                    'page_title' => $validated['title'],
                    'page_slug' => $validated['slug'],
                    'page_status' => $validated['status'],
                    'preview_html' => $validated['html_content'] ?? null,
                    'react_code' => $validated['react_code'] ?? null,
                    'preview_mode' => $validated['preview_mode'] ?? 'html',
                    'app_meta' => $appMeta,
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project saved.',
            'public_url' => $validated['slug'] . '.' . request()->getHost(),
            'page' => [
                'id' => $validated['session_id'] ?? null,
                'session_id' => $validated['session_id'] ?? null,
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'status' => $validated['status'],
                'publish_visibility' => $appMeta['publish_visibility'] ?? 'public',
                'custom_domain' => $appMeta['custom_domain'] ?? '',
            ],
        ]);
    }

    public function export(Request $request, string $session)
    {
        $customer = auth('customer')->user();
        // Ownership check: session must belong to this customer
        AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $adminController = app(\App\Http\Controllers\Admin\PageAiBuilderController::class);
        return $adminController->export($request, $session);
    }

    public function destroy(string $session)
    {
        $customer = auth('customer')->user();
        $sessionModel = AiBuilderSession::where('session_id', $session)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $sandboxPath = storage_path('app/ai-builder-sandboxes/' . $sessionModel->session_id);
        if (File::isDirectory($sandboxPath)) {
            File::deleteDirectory($sandboxPath);
        }

        $sessionModel->delete();

        return redirect()->route('customer.ai-projects.index')
            ->with('success', __('Project deleted.'));
    }
}

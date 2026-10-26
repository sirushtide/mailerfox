<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBuilderMessage;
use App\Models\AiGeneration;
use App\Models\AiGenerationStep;
use App\Models\EmailList;
use App\Models\Page;
use App\Models\Setting;
use App\Services\AI\AiProviderRouter;
use App\Services\AiBuilder\AiBuilderPipeline;
use App\Services\AiBuilder\IntentDetector;
use App\Services\AiBuilder\ModelRouter;
use App\Services\AiBuilder\PageBuilderContext;
use App\Services\PageBuilder\PageBuilderRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    private const RESERVED_PAGE_SLUGS = [
        'admin',
        'login',
        'logout',
        'register',
        'openapi',
        'openapi.json',
        'blog',
        'subscribe',
        'unsubscribe',
        'track',
        't',
        'webhooks',
        'storage',
        'pages',
    ];

    public function index(Request $request)
    {
        $this->ensureDefaultHomepages();

        $search = trim((string) $request->get('q', ''));

        $pages = Page::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            })
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pages.index', compact('pages', 'search'));
    }

    public function create(Request $request)
    {
        $type = trim((string) $request->query('type', 'page'));
        if (!in_array($type, ['page', 'homepage'], true)) {
            $type = 'page';
        }

        $page = Page::create([
            'type' => $type,
            'status' => 'draft',
            'title' => $type === 'homepage' ? 'New Homepage' : 'New Page',
        ]);

        return redirect()->route('admin.pages.builder', ['page' => $page, 'embed' => 1])->with('success', __('Page created.'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $data = $this->applyBuilderHtml($data);
        $page = Page::create($data);

        $action = is_string($request->input('action')) ? trim((string) $request->input('action')) : '';
        if ($action === 'save_and_edit') {
            return redirect()->route('admin.pages.builder', ['page' => $page, 'embed' => 1])->with('success', __('Page created.'));
        }

        return redirect()->route('admin.pages.builder', ['page' => $page, 'embed' => 1])->with('success', __('Page created.'));
    }

    public function edit(Page $page, Request $request)
    {
        $embed = $request->boolean('embed') ? 1 : 0;
        return redirect()->route('admin.pages.builder', ['page' => $page, 'embed' => $embed]);
    }

    public function builder(Page $page)
    {
        return view('admin.pages.builder', [
            'page' => $page,
            'pageBuilderData' => $this->defaultBuilderFromPage($page),
            'navMenus' => \App\Models\NavigationMenu::orderBy('name')->get(['id', 'name', 'key', 'items']),
            'lists' => EmailList::orderBy('name')->get(['id', 'name', 'display_name']),
        ]);
    }

    public function renderHtml(Request $request, Page $page)
    {
        $builder = $this->decodeBuilderData($request->input('builder_data'));
        if (!is_array($builder)) {
            throw ValidationException::withMessages([
                'builder_data' => __('Invalid builder data.'),
            ]);
        }

        $renderer = app(PageBuilderRenderer::class);
        if (!$renderer->isBuilderData($builder)) {
            throw ValidationException::withMessages([
                'builder_data' => __('Invalid builder data.'),
            ]);
        }

        return response()->json([
            'success' => true,
            'html' => $renderer->render($builder),
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $this->validateData($request, $page->id);

        $redirectTo = is_string($request->input('redirect_to')) ? trim((string) $request->input('redirect_to')) : '';
        if ($redirectTo === 'builder') {
            $raw = $request->input('builder_data');
            $rawLen = is_string($raw) ? strlen($raw) : null;
            $decoded = is_array($data['builder_data'] ?? null) ? $data['builder_data'] : null;

            $sectionCount = null;
            $widgetTypes = [];
            $firstHeadingProps = null;
            $firstRichTextProps = null;
            if (is_array($decoded)) {
                $sections = $decoded['sections'] ?? null;
                if (is_array($sections)) {
                    $sectionCount = count($sections);
                    foreach ($sections as $s) {
                        $cols = is_array($s['columns'] ?? null) ? $s['columns'] : [];
                        foreach ($cols as $c) {
                            $widgets = is_array($c['widgets'] ?? null) ? $c['widgets'] : [];
                            foreach ($widgets as $w) {
                                $t = is_string($w['type'] ?? null) ? (string) $w['type'] : null;
                                if ($t !== null) {
                                    $widgetTypes[] = $t;
                                }
                                if ($firstHeadingProps === null && $t === 'heading') {
                                    $firstHeadingProps = is_array($w['props'] ?? null) ? $w['props'] : null;
                                }
                                if ($firstRichTextProps === null && $t === 'rich_text') {
                                    $firstRichTextProps = is_array($w['props'] ?? null) ? $w['props'] : null;
                                }
                            }
                        }
                    }
                }
            }

            Log::info('PageBuilder save', [
                'page_id' => $page->id,
                'raw_type' => gettype($raw),
                'raw_len' => $rawLen,
                'builder' => is_array($decoded) ? ($decoded['builder'] ?? null) : null,
                'version' => is_array($decoded) ? ($decoded['version'] ?? null) : null,
                'sections' => $sectionCount,
                'widget_types' => array_values(array_unique($widgetTypes)),
                'first_heading_props' => $firstHeadingProps,
                'first_richtext_props' => $firstRichTextProps,
            ]);
        }

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $data = $this->applyBuilderHtml($data);
        $page->update($data);

        $redirectTo = is_string($request->input('redirect_to')) ? trim((string) $request->input('redirect_to')) : '';
        if ($redirectTo === 'builder') {
            return redirect()->route('admin.pages.builder', ['page' => $page, 'embed' => 1])->with('success', __('Page updated.'));
        }

        if ($redirectTo === 'index') {
            return redirect()->route('admin.pages.index')->with('success', __('Page updated.'));
        }

        return redirect()->route('admin.pages.builder', ['page' => $page, 'embed' => 1])->with('success', __('Page updated.'));
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', __('Page deleted.'));
    }

    public function uploadImage(Request $request)
    {
        // The legacy Unlayer editor posts the file as "file"; the mailpurse
        // page builder (logo/image controls) posts it as "image".
        $request->validate([
            'file' => ['required_without:image', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
            'image' => ['required_without:file', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
        ]);

        $uploaded = $request->file('file') ?: $request->file('image');
        if ($uploaded && $uploaded->getMimeType() === 'image/svg+xml') {
            $svg = @file_get_contents($uploaded->getRealPath());
            if (!is_string($svg) || $svg === '' || preg_match('~<\s*script\b|\bon\w+\s*=|javascript\s*:|<\s*foreignObject\b~i', $svg)) {
                throw ValidationException::withMessages([
                    'file' => 'The uploaded SVG contains unsupported content.',
                ]);
            }
        }

        $path = $uploaded->store('pages/uploads', 'public');

        return response()->json([
            'filelink' => asset('storage/' . ltrim($path, '/')),
        ]);
    }

    public function aiSection(Request $request, Page $page, AiProviderRouter $router)
    {
        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:8000'],
            'screenshot' => ['nullable', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
            'model' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', 'string', 'in:section,full_page'],
            'raw_prompt' => ['nullable', 'string', 'max:8000'],
            'builder_data' => ['nullable', 'string', 'max:500000'],
            'section_index' => ['nullable', 'integer', 'min:0'],
        ]);

        $prompt = is_string($validated['prompt'] ?? null) ? trim((string) $validated['prompt']) : '';
        $rawPrompt = is_string($validated['raw_prompt'] ?? null) ? trim((string) $validated['raw_prompt']) : $prompt;
        $modelParam = is_string($validated['model'] ?? null) ? trim((string) $validated['model']) : 'auto';

        $resolvedModel = $this->resolveAiBuilderModel($modelParam, 'worker');

        // Intent gate: greetings, questions, and guidance requests get a conversational
        // reply instead of being forced through the section-building pipeline.
        $intentDetector = new IntentDetector($router);
        $intent = $intentDetector->detect($rawPrompt, $modelParam, $resolvedModel);
        if ($intent['intent'] === 'chat') {
            $reply = $intent['message'] ?? $intentDetector->chatReply($rawPrompt, $resolvedModel);

            AiBuilderMessage::create([
                'page_id' => $page->id,
                'role' => 'user',
                'content' => $rawPrompt !== '' ? $rawPrompt : 'Design a clean marketing section.',
                'scope' => 'section',
                'model' => $resolvedModel,
                'status' => 'ok',
            ]);

            AiBuilderMessage::create([
                'page_id' => $page->id,
                'role' => 'assistant',
                'content' => $reply,
                'scope' => 'section',
                'model' => $resolvedModel,
                'status' => 'ok',
            ]);

            return response()->json([
                'success' => true,
                'type' => 'chat',
                'message' => $reply,
                'model_used' => $resolvedModel,
            ]);
        }

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

        if (!$router->hasCredentials($resolvedModel)) {
            $provider = $router->resolveProvider($resolvedModel);
            return response()->json([
                'success' => true,
                'section' => [
                    'name' => 'AI Section',
                    'style' => [
                        'backgroundType' => 'color',
                        'backgroundColor' => '#ffffff',
                        'paddingY' => 56,
                        'gap' => 24,
                        'maxWidth' => '6xl',
                    ],
                    'columns' => [
                        [
                            'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                            'widgets' => [
                                [
                                    'type' => 'heading',
                                    'props' => [
                                        'text' => 'AI Designer (Not configured)',
                                        'level' => 2,
                                        'align' => 'left',
                                    ],
                                ],
                                [
                                    'type' => 'rich_text',
                                    'props' => [
                                        'html' => '<p>Configure <strong>' . $provider . '_api_key</strong> in Settings to enable AI-generated sections.</p>',
                                        'align' => 'left',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'message' => 'AI key not configured. Returned placeholder section.',
            ]);
        }

        $scope = is_string($validated['scope'] ?? null) ? (string) $validated['scope'] : 'section';
        $provider = $router->resolveProvider($resolvedModel);
        $builderData = $this->decodeBuilderData($validated['builder_data'] ?? null) ?? [];
        $pageContext = app(PageBuilderContext::class)->summarize($builderData);

        $userMessage = AiBuilderMessage::create([
            'page_id' => $page->id,
            'role' => 'user',
            'content' => $prompt !== '' ? $prompt : 'Design a clean marketing section.',
            'scope' => $scope,
            'model' => $resolvedModel,
            'status' => 'ok',
        ]);

        $admin = auth('admin')->user();
        $generation = AiGeneration::create([
            'admin_user_id' => $admin?->id,
            'tool' => 'ai_builder',
            'type' => $scope,
            'provider' => $provider,
            'model' => $resolvedModel,
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

        try {
            $pipeline = new AiBuilderPipeline($router);
            $result = $pipeline->generateSection(
                $prompt,
                'page',
                $screenshotUrl,
                $modelParam,
                $pageContext,
                (int) ($validated['section_index'] ?? count($pageContext['sections'] ?? []))
            );
            $section = $result['section'];

            if (!is_array($section) || !is_array($section['columns'] ?? null)) {
                throw new \RuntimeException('AI returned an invalid section structure.');
            }

            $generation->update([
                'success' => true,
                'tokens_used' => $totalTokens,
            ]);

            $sectionName = is_string($section['name'] ?? null) && trim((string) $section['name']) !== ''
                ? trim((string) $section['name'])
                : ($scope === 'full_page' ? 'Full page' : 'Section');

            AiBuilderMessage::create([
                'page_id' => $page->id,
                'ai_generation_id' => $generation->id,
                'role' => 'assistant',
                'content' => 'Generated "' . $sectionName . '" and added it to your page.',
                'section_data' => $section,
                'scope' => $scope,
                'model' => $resolvedModel,
                'status' => 'ok',
                'tokens_used' => $totalTokens,
            ]);

            return response()->json([
                'success' => true,
                'section' => $section,
                'tokens' => $totalTokens > 0 ? $totalTokens : null,
                'model_used' => $resolvedModel,
            ]);
        } catch (\Throwable $e) {
            $generation->update([
                'success' => false,
                'tokens_used' => $totalTokens,
                'error_message' => $e->getMessage(),
            ]);

            AiBuilderMessage::create([
                'page_id' => $page->id,
                'ai_generation_id' => $generation->id,
                'role' => 'assistant',
                'content' => $e->getMessage(),
                'scope' => $scope,
                'model' => $resolvedModel,
                'status' => 'error',
                'tokens_used' => $totalTokens,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        } finally {
            $router->clearStepCallback();
        }
    }

    public function aiMessages(Request $request, Page $page)
    {
        $messages = AiBuilderMessage::query()
            ->where('page_id', $page->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->map(function (AiBuilderMessage $m) {
                return [
                    'id' => $m->id,
                    'role' => $m->role,
                    'type' => $m->type,
                    'content' => $m->content,
                    'section' => $m->section_data,
                    'plan' => $m->plan_data,
                    'scope' => $m->scope,
                    'model' => $m->model,
                    'status' => $m->status,
                    'tokens' => $m->tokens_used,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    public function aiMessagesClear(Request $request, Page $page)
    {
        AiBuilderMessage::where('page_id', $page->id)->delete();

        return response()->json(['success' => true]);
    }

    private function resolveAiBuilderModel(string $modelParam, string $stage): ?string
    {
        if ($modelParam !== 'auto' && $modelParam !== '') {
            return $modelParam;
        }

        $default = Setting::get('ai_builder_model_' . $stage);
        if (is_string($default) && trim($default) !== '') {
            return trim($default);
        }

        $fallbacks = [
            'enhancer' => 'gpt-5.4-mini',
            'orchestrator' => 'gpt-5.4-mini',
            'worker' => 'gpt-5.4-mini',
            'iteration' => 'gpt-5.4-mini',
        ];

        return $fallbacks[$stage] ?? 'gpt-5.4-mini';
    }

    private function validateData(Request $request, ?int $pageId = null): array
    {
        $redirectTo = is_string($request->input('redirect_to')) ? trim((string) $request->input('redirect_to')) : '';
        $isBuilderSave = $redirectTo === 'builder';

        $hasBuilderData = $request->has('builder_data');
        $decodedBuilder = null;
        $rawBuilderData = null;
        if ($hasBuilderData) {
            $rawBuilderData = $request->input('builder_data');
            $decodedBuilder = $this->decodeBuilderData($rawBuilderData);
            if (
                (is_string($rawBuilderData) && trim($rawBuilderData) !== '')
                && !is_array($decodedBuilder)
            ) {
                throw ValidationException::withMessages([
                    'builder_data' => __('Invalid builder data.'),
                ]);
            }
        }

        if ($isBuilderSave) {
            if (!$hasBuilderData) {
                throw ValidationException::withMessages([
                    'builder_data' => __('Missing builder data.'),
                ]);
            }

            if (is_string($rawBuilderData) && trim($rawBuilderData) === '') {
                throw ValidationException::withMessages([
                    'builder_data' => __('Builder data is empty.'),
                ]);
            }

            if (!is_array($decodedBuilder)) {
                throw ValidationException::withMessages([
                    'builder_data' => __('Invalid builder data.'),
                ]);
            }
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9\-]+$/', Rule::unique('pages', 'slug')->ignore($pageId)],
            'type' => ['required', 'string', Rule::in(['page', 'homepage'])],
            'variant_key' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', Rule::in(['draft', 'publish'])],
            'html_content' => ['nullable', 'string'],
        ]);

        if (($data['type'] ?? 'page') === 'page' && !empty($data['slug'])) {
            $slug = trim((string) $data['slug']);
            $data['slug'] = $slug;

            if (in_array($slug, self::RESERVED_PAGE_SLUGS, true)) {
                throw ValidationException::withMessages([
                    'slug' => __('This slug is reserved. Please choose another.'),
                ]);
            }
        }

        if ($data['type'] === 'homepage') {
            $data['variant_key'] = is_string($data['variant_key'] ?? null) ? trim((string) $data['variant_key']) : null;
            if (($data['variant_key'] ?? '') === '') {
                $data['variant_key'] = null;
            }
        } else {
            $data['variant_key'] = null;
        }

        if ($hasBuilderData) {
            $data['builder_data'] = $decodedBuilder;
        }

        return $data;
    }

    private function decodeBuilderData(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $candidates = [$raw];
        $decodedEntities = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (is_string($decodedEntities) && $decodedEntities !== $raw) {
            $candidates[] = $decodedEntities;
        }

        foreach ($candidates as $cand) {
            $first = json_decode($cand, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($first)) {
                    return $first;
                }
                if (is_string($first)) {
                    $second = json_decode($first, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($second)) {
                        return $second;
                    }
                }
            }

            $stripped = stripslashes($cand);
            if ($stripped !== $cand) {
                $first2 = json_decode($stripped, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (is_array($first2)) {
                        return $first2;
                    }
                    if (is_string($first2)) {
                        $second2 = json_decode($first2, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($second2)) {
                            return $second2;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function ensureDefaultHomepages(): void
    {
        $defaults = [
            ['variant_key' => '1', 'title' => 'Homepage Variant 1', 'slug' => 'home-variant-1'],
            ['variant_key' => 'dark', 'title' => 'Homepage Variant Dark', 'slug' => 'home-variant-dark'],
            ['variant_key' => '2', 'title' => 'Homepage Variant 2', 'slug' => 'home-variant-2'],
            ['variant_key' => '3', 'title' => 'Homepage Variant 3', 'slug' => 'home-variant-3'],
        ];

        foreach ($defaults as $row) {
            Page::firstOrCreate(
                ['type' => 'homepage', 'variant_key' => $row['variant_key']],
                [
                    'title' => $row['title'],
                    'slug' => $row['slug'],
                    'status' => 'draft',
                ]
            );
        }
    }

    private function applyBuilderHtml(array $data): array
    {
        $builder = $data['builder_data'] ?? null;
        if (!is_array($builder)) {
            return $data;
        }

        $renderer = app(PageBuilderRenderer::class);
        if (!$renderer->isBuilderData($builder)) {
            return $data;
        }

        $data['html_content'] = $renderer->render($builder);

        return $data;
    }

    private function defaultBuilderFromPage(Page $page): array
    {
        $builder = is_array($page->builder_data) ? $page->builder_data : [];
        $renderer = app(PageBuilderRenderer::class);

        if ($renderer->isBuilderData($builder)) {
            return $builder;
        }

        $html = is_string($page->html_content) ? (string) $page->html_content : '';

        if ($html !== '') {
            return [
                'builder' => 'mailpurse',
                'version' => 1,
                'sections' => [
                    [
                        'id' => 'sec_' . Str::random(8),
                        'style' => [
                            'backgroundType' => 'color',
                            'backgroundColor' => '#ffffff',
                            'paddingY' => 48,
                            'gap' => 24,
                            'maxWidth' => '6xl',
                        ],
                        'columns' => [
                            [
                                'id' => 'col_' . Str::random(8),
                                'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                                'widgets' => [
                                    [
                                        'id' => 'w_' . Str::random(8),
                                        'type' => 'rich_text',
                                        'props' => [
                                            'html' => $html,
                                            'align' => 'left',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return [
            'builder' => 'mailpurse',
            'version' => 1,
            'sections' => [
                [
                    'id' => 'sec_' . Str::random(8),
                    'name' => 'Section 1',
                    'style' => [
                        'backgroundType' => 'color',
                        'backgroundColor' => '#ffffff',
                        'paddingY' => 48,
                        'gap' => 24,
                        'maxWidth' => '6xl',
                    ],
                    'columns' => [
                        [
                            'id' => 'col_' . Str::random(8),
                            'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ];
    }
}

<?php

namespace App\Services\AiBuilder;

use App\Models\AiBuilderTemplate;
use App\Models\Setting;
use App\Services\AI\AiProviderRouter;

class PageHtmlGenerator
{
    /**
     * How many recent generations per category to remember when rotating
     * template selection, so a given template is not reused too soon.
     */
    private const ROTATION_HISTORY_LEN = 5;

    /**
     * How many recent generations to remember when rotating decorative
     * (hero/CTA) background presets, so a given preset is not reused too soon.
     */
    private const BACKGROUND_ROTATION_HISTORY_LEN = 5;

    /**
     * Cap for the files handed to the iteration model. Only import-reachable
     * source files are included, but these hard limits guard against a polluted
     * project (leftover scratch files, generated test sections, etc.) blowing up
     * the request with content the model does not need.
     */
    private const MAX_ITERATION_CONTEXT_FILES = 25;
    private const MAX_ITERATION_CONTEXT_CHARS = 100000;

    /**
     * Segmented iteration edits one file per request. This caps how many files a
     * single iteration may touch so the total work stays bounded and each step is
     * small enough to never hit the HTTP timeout.
     */
    private const MAX_SEGMENTED_EDIT_FILES = 3;

    /**
     * Fixed set of logo images for brand/logo fields. The generation prompts tell
     * the model to use ONLY these URLs for logo/brand content, and the image
     * repair pass swaps any other logo-context URL to a deterministic pick from
     * this set so logo strips never render broken or photo images.
     */
    private const BRAND_LOGO_URLS = [
        'https://i.ibb.co.com/chdrTb1g/logoipsum-430.png',
        'https://i.ibb.co.com/9H1D6gSJ/logoipsum-254.png',
        'https://i.ibb.co.com/39KLF61g/logoipsum-257.png',
        'https://i.ibb.co.com/dYMqC2P/logoipsum-262.png',
        'https://i.ibb.co.com/dwWm6wPp/logoipsum-264.png',
        'https://i.ibb.co.com/g5y4nXy/logoipsum-297.png',
        'https://i.ibb.co.com/zW5StSX7/logoipsum-343.png',
        'https://i.ibb.co.com/HTgK53cH/logoipsum-362.png',
        'https://i.ibb.co.com/zWWhycG0/logoipsum-369.png',
        'https://i.ibb.co.com/hF6zmyLt/logoipsum-367.png',
    ];

    public function __construct(
        protected AiProviderRouter $router,
    ) {
    }

    /**
     * Emit a typed block (thought | narration | tool | summary) into the job's
     * block stream. No-op when there is no job to stream to.
     */
    private function pushBlock(?string $jobId, string $type, string $content, ?string $action = null, ?string $file = null, ?string $detail = null, ?int $duration = null): void
    {
        if ($jobId === null || $jobId === '') {
            return;
        }

        AiProgressTracker::pushBlock($jobId, array_filter([
            'type'     => $type,
            'content'  => $content,
            'action'   => $action,
            'file'     => $file,
            'detail'   => $detail,
            'duration' => $duration,
        ], static fn ($value) => $value !== null));
    }

    /**
     * Stream a concise, user-visible planning summary rather than hidden
     * provider reasoning or internal chain-of-thought.
     */
    private function streamPlanningSummary(?string $jobId, string $model, string $prompt, string $file = 'docs/plan.md'): void
    {
        if (!$jobId) {
            return;
        }

        $buffer = '';
        try {
            $this->router->streamChat(
                $model,
                [['role' => 'user', 'content' => $prompt]],
                350,
                'You are an IDE build agent. Return concise, user-visible progress updates only. Explain the requested page structure and the next implementation step in plain language. Do not reveal private chain-of-thought, policies, or hidden reasoning. Output one or two short paragraphs.',
                function (string $delta) use (&$buffer, $jobId, $file): void {
                    $buffer .= $delta;
                    if (strlen($buffer) >= 100 || preg_match('/[.!?]\s$/', $buffer)) {
                        $message = trim($buffer);
                        AiProgressTracker::set($jobId, [
                            'message' => $message,
                            'file' => $file,
                            'action' => 'thinking',
                        ]);
                        $this->pushBlock($jobId, 'thought', $message, 'thinking', $file);
                        $buffer = '';
                    }
                }
            );
            if (trim($buffer) !== '') {
                $message = trim($buffer);
                AiProgressTracker::set($jobId, [
                    'message' => $message,
                    'file' => $file,
                    'action' => 'thinking',
                ]);
                $this->pushBlock($jobId, 'thought', $message, 'thinking', $file);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::debug('PageHtmlGenerator: live planning summary unavailable', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function pushFileWrite(?string $jobId, string $path, string $content): void
    {
        if (!$jobId || $path === '' || $content === '') {
            return;
        }

        $lines = substr_count(trim($content), "\n") + 1;
        $this->pushBlock($jobId, 'tool', 'Write', 'write', $path, '+' . $lines . ' -0');
    }

    public function generate(string $prompt, ?string $screenshotUrl = null, ?string $modelOverride = null, ?string $jobId = null, ?array $frontendTheme = null, ?string $intentPrompt = null, bool $supabaseConnected = false, ?array $authProviders = null): array
    {
        AiProgressTracker::resetBlocks($jobId);

        $sectionSource = (string) Setting::get('ai_builder_section_source', 'templates');

        $themeApplier = new ThemeApplier();
        if (!empty($frontendTheme)) {
            $themePreset = $themeApplier->presetFromFrontendTheme($frontendTheme) ?? ThemeApplier::randomPreset();
        } else {
            $themePreset = ThemeApplier::randomPreset();
        }
        $sharedStyles = $this->sharedStylesFromPreset($themePreset);

        // Templates Test mode: skip AI entirely, build a page from all templates with default props
        if ($sectionSource === 'templates_test') {

            if ($jobId) {
                AiProgressTracker::set($jobId, [
                    'status'  => 'generating',
                    'step'    => 1,
                    'total'   => 3,
                    'message' => 'Loading templates…',
                    'file'    => 'docs/plan.md',
                    'action'  => 'thinking',
                ]);
            }

            $reactPage = $this->generateTemplatesTestPage($sharedStyles, $jobId);

            if ($reactPage !== null) {
            $projectFiles = $reactPage['project_files'] ?? [];
            $projectFiles = (new ThemeApplier())->applyPreset($projectFiles, $themePreset);
            $projectFiles = $this->repairProjectFiles($projectFiles);

                if ($jobId) {
                    AiProgressTracker::set($jobId, [
                        'status'  => 'done',
                        'step'    => 3,
                        'total'   => 3,
                        'message' => 'Done',
                        'file'    => 'build/index.html',
                        'action'  => 'done',
                    ]);
                }

                return [
                    'type'           => 'page',
                    'html'           => $reactPage['html'],
                    'preview_html'   => $reactPage['html'],
                    'title'          => 'Templates Test Page',
                    'description'    => 'All templates rendered with default data for testing.',
                    'react_code'     => $reactPage['react_code'],
                    'preview_mode'   => 'react',
                    'project_files'  => $projectFiles,
                    'app_meta'       => [
                        'app_title'       => 'Templates Test Page',
                        'meta_description'=> 'All templates rendered with default data for testing.',
                    ],
                ];
            }

            // No templates found — fall through to normal generation
        }

        // Intent gate: greetings, questions, and guidance requests get a conversational
        // reply instead of being forced through the page-building pipeline. The raw user
        // text is used (not the theme-composed prompt) so theme presets never leak into
        // intent classification or chat replies.
        $intentText = is_string($intentPrompt) && trim($intentPrompt) !== '' ? trim($intentPrompt) : $prompt;
        $intentDetector = new IntentDetector($this->router);
        $intent = $intentDetector->detect($intentText, $modelOverride);
        if ($intent['intent'] === 'chat') {
            $model = ModelRouter::resolve('worker', $modelOverride);
            $message = is_string($intent['message'] ?? null) && trim($intent['message']) !== ''
                ? trim($intent['message'])
                : $intentDetector->chatReply($intentText, $model);

            $this->pushBlock($jobId, 'summary', $message);

            return [
                'type' => 'chat',
                'message' => $message,
                'html' => null,
                'preview_html' => null,
                'title' => null,
                'description' => null,
                'react_code' => null,
                'preview_mode' => 'html',
                'project_files' => [],
            ];
        }

        $model = ModelRouter::resolve('worker', $modelOverride);
        if (!$this->router->hasCredentials($model)) {
            throw new \RuntimeException('AI API key is not configured for the selected provider.');
        }

        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status' => 'generating',
                'step' => 1,
                'total' => 1,
                'message' => 'Planning…',
                'file' => 'docs/plan.md',
                'action' => 'thinking',
            ]);
        }

        $this->streamPlanningSummary($jobId, $model, $intentText);

        // Stage 1: Blueprint
        // Use the raw user prompt (without theme composition) for blueprint planning.
        // The theme block ("Theme preset: / Style: / Typography: / Colors:") confuses
        // small models into echoing a feature-parse instead of a proper blueprint,
        // which misclassifies app requests as single-section landing pages.
        $blueprint = $this->generateBlueprint($intentText, $screenshotUrl, $model);

        if ($jobId && AiProgressTracker::isCancelled($jobId)) {
            throw new \RuntimeException('Generation cancelled by user.');
        }

        if ($blueprint['type'] === 'chat') {
            if ($jobId) {
                AiProgressTracker::setDone($jobId, ['type' => 'chat']);
            }
            return [
                'type' => 'chat',
                'message' => $blueprint['message'],
                'html' => null,
                'preview_html' => null,
                'title' => null,
                'description' => null,
                'react_code' => null,
                'preview_mode' => 'html',
            ];
        }

        $designSystem = is_array($blueprint['design_system'] ?? null) ? $blueprint['design_system'] : [];
        if (empty($frontendTheme)) {
            if (!empty($designSystem['color_palette'] ?? null)) {
                $themePreset = $this->aiDesignSystemToPreset($designSystem);
                $sharedStyles = $this->designSystemSharedStyle($designSystem);
            } else {
                $themePreset = ThemeApplier::randomPreset();
                $sharedStyles = $this->sharedStylesFromPreset($themePreset);
            }
        }

        // Rotate the decorative (hero/CTA) background preset per generation so
        // backgrounds differ even when a fixed frontend theme or a deterministic
        // model would otherwise reuse the same one.
        $sharedStyles = $this->pickDecorativeBackground($sharedStyles);

        // Stage 1b: Prompt Enhancement — get detailed design spec (industry, tone,
        // features, widget types, design requirements) to enrich section generation.
        $enhancedSpec = [];
        $blueprintType = $blueprint['type'] ?? 'page';
        try {
            if ($jobId) {
                AiProgressTracker::set($jobId, [
                    'status'  => 'generating',
                    'step'    => 1,
                    'total'   => max(3, count($blueprint['sections'] ?? []) + count($blueprint['pages'] ?? []) + 3),
                    'message' => 'Enhancing design spec…',
                    'file'    => 'docs/plan.md',
                    'action'  => 'thinking',
                ]);
            }
            $this->streamPlanningSummary(
                $jobId,
                $model,
                'The initial blueprint is ready. Explain which design details should be refined for this request, including content tone, visual hierarchy, and useful interaction patterns. Keep it concise and observable.',
                'docs/plan.md'
            );
            $enhancer = new PromptEnhancer($this->router);
            $enhancedSpec = $enhancer->enhance($intentText, $blueprintType, $screenshotUrl);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator: prompt enhancer failed, continuing with blueprint only', [
                'error' => $e->getMessage(),
            ]);
        }

        // Merge enhanced spec into sharedStyles for richer section context
        if (!empty($enhancedSpec)) {
            $sharedStyles['industry'] = $enhancedSpec['industry'] ?? 'general';
            $sharedStyles['features'] = $enhancedSpec['features'] ?? [];
            $sharedStyles['widget_types'] = $enhancedSpec['widget_types'] ?? [];
            // If blueprint didn't return a design system, use enhancer's color palette
            if (empty($designSystem['color_palette'] ?? null) && !empty($enhancedSpec['color_palette'] ?? null)) {
                $sharedStyles['primary'] = $enhancedSpec['color_palette']['primary'] ?? $sharedStyles['primary'];
                $sharedStyles['secondary'] = $enhancedSpec['color_palette']['secondary'] ?? $sharedStyles['secondary'];
                $sharedStyles['background'] = $enhancedSpec['color_palette']['background'] ?? $sharedStyles['background'];
                $sharedStyles['text'] = $enhancedSpec['color_palette']['text'] ?? $sharedStyles['text'];
            }
            // Merge enhanced section descriptions into blueprint sections
            $enhancedSections = $enhancedSpec['sections'] ?? [];
            if (!empty($enhancedSections) && !empty($blueprint['sections'])) {
                $blueprint['sections'] = $this->mergeEnhancedSections($blueprint['sections'], $enhancedSections);
            }
        }

        $sections = $blueprint['sections'] ?? [];
        $pages = $blueprint['pages'] ?? [];
        $totalSteps = max(3, count($sections) + count($pages) + 2);
        $title = is_string($blueprint['title'] ?? null) && trim((string) $blueprint['title']) !== ''
            ? trim((string) $blueprint['title'])
            : 'your page';
        $planningDescription = is_string($blueprint['description'] ?? null) ? trim((string) $blueprint['description']) : '';
        $plannedItems = array_values(array_filter(array_map(static function ($item) {
            return is_string($item['name'] ?? null) ? trim((string) $item['name']) : '';
        }, !empty($sections) ? $sections : $pages)));
        $structureDescription = !empty($plannedItems)
            ? 'Planned flow: ' . implode(', ', array_slice($plannedItems, 0, 6)) . '.'
            : null;

        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status' => 'generating',
                'step' => 1,
                'total' => $totalSteps,
                'message' => 'Planning ' . $title . '…',
                'file' => 'docs/plan.md',
                'action' => 'thinking',
                'description' => $planningDescription !== '' ? $planningDescription : null,
            ]);
            AiProgressTracker::set($jobId, [
                'status' => 'generating',
                'step' => 2,
                'total' => $totalSteps,
                'message' => 'Preparing project structure…',
                'file' => 'src/App.jsx',
                'action' => 'reading',
                'description' => $structureDescription,
            ]);
        }

        $this->pushBlock($jobId, 'narration', 'I\'ll build a ' . $title . ' with ' . (count($sections) ?: count($pages)) . ' ' . ((count($sections) ?: count($pages)) === 1 ? 'section' : 'sections') . '.');
        if ($structureDescription !== null) {
            $this->pushBlock($jobId, 'narration', $structureDescription);
        }

        // Landing pages (type=page) use section-by-section generation:
        // one small AI call per section (~2 500 tokens each) assembled into a full
        // Vite project by DefaultCodebase::sectionFiles(). This avoids the single
        // 12K-token call that times out on Zhipu/GLM and other slower models.
        // Apps (type=app) use page-by-page generation: one AI call per page,
        // assembled by assembleAppProject() with infrastructure (auth, supabase,
        // protected routes, layout). Falls back to buildFallbackAppScaffold only
        // if all AI page calls fail.
        // Admin panels / dashboards are built on the prebuilt shadcn admin
        // codebase (sidebar shell + admin UI primitives). Common blueprint
        // sections map onto prebuilt admin pages; anything else is generated
        // per-page by AI. Falls back to the prebuilt scaffold if AI fails.
        if ($this->isAdminIntent($intentText, $blueprint)) {
            // Auth is strictly opt-in and requires a connected Supabase project.
            // If the user asks for login/signup/auth but none is connected, we
            // cannot build a working auth screen — surface a clear error in the
            // foundry panel instead of shipping a login page nobody can use.
            $authRequested = $this->isAdminAuthIntent($intentText);
            if ($authRequested && ! $supabaseConnected) {
                throw new \RuntimeException(
                    'You asked for login/signup, but no connected Supabase project was found for this account. '
                    . 'Connect a Supabase integration in Settings → Supabase first, then ask to enable authentication.'
                );
            }
            $authMode = $authRequested ? 'functional' : 'static';
            $reactPage = $this->generateAdminProject(
                $intentText,
                $blueprint,
                $sharedStyles,
                $model,
                $jobId,
                $authMode,
                $this->isAdminWorkspaceIntent($intentText),
                $this->isAdminProjectsIntent($intentText),
                $authProviders
            );
            if ($reactPage === null) {
                $reactPage = $this->buildFallbackAdminProject($blueprint, $authMode, $jobId);
            }
        } elseif (($blueprint['type'] ?? 'page') === 'page') {
            $reactPage = $this->generateReactPageSectionBySection($intentText, $blueprint, $sharedStyles, $model, $jobId);
        } else {
            $reactPage = $this->generateAppPageByPage($intentText, $blueprint, $sharedStyles, $model, $jobId);
            if ($reactPage === null) {
                $reactPage = $this->buildFallbackAppScaffold($blueprint, $sharedStyles, $jobId);
            }
        }

        if ($reactPage !== null) {
            $projectFiles = $reactPage['project_files'] ?? [];
            if ($themePreset !== null) {
                $projectFiles = (new ThemeApplier())->applyPreset($projectFiles, $themePreset);
            }
            $projectFiles = $this->repairProjectFiles($projectFiles);
            if ($jobId) {
                AiProgressTracker::set($jobId, [
                    'status' => 'done',
                    'step' => $totalSteps,
                    'total' => $totalSteps,
                    'message' => 'Done',
                    'file' => 'build/index.html',
                    'action' => 'done',
                ]);
            }
            return [
                'type' => 'page',
                'html' => $reactPage['html'],
                'preview_html' => $reactPage['html'],
                'title' => $blueprint['title'],
                'description' => $blueprint['description'],
                'react_code' => $this->repairImageUrls((string) ($reactPage['react_code'] ?? '')),
                'preview_mode' => 'react',
                'project_files' => $projectFiles,
                'app_meta' => [
                    'app_title' => is_string($blueprint['title'] ?? null) ? trim((string) $blueprint['title']) : '',
                    'meta_description' => is_string($blueprint['description'] ?? null) ? trim((string) $blueprint['description']) : '',
                ],
            ];
        }

        // React generation failed; fall back to a predefined, Vite-safe React page
        // instead of plain HTML so the Code tab always shows editable React code.
        $fallbackPage = $this->buildFallbackReactPage($blueprint, $sharedStyles, $jobId);
        $fallbackPage['project_files'] = $themePreset !== null
            ? (new ThemeApplier())->applyPreset($fallbackPage['project_files'] ?? [], $themePreset)
            : ($fallbackPage['project_files'] ?? []);
        $fallbackPage['project_files'] = $this->repairProjectFiles($fallbackPage['project_files']);

        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status' => 'done',
                'step' => $totalSteps,
                'total' => $totalSteps,
                'message' => 'Done',
                'file' => 'build/index.html',
                'action' => 'done',
            ]);
        }

        return [
            'type' => 'page',
            'html' => $fallbackPage['html'],
            'preview_html' => $fallbackPage['html'],
            'title' => $blueprint['title'],
            'description' => $blueprint['description'],
            'react_code' => $this->repairImageUrls((string) ($fallbackPage['react_code'] ?? '')),
            'preview_mode' => 'react',
            'project_files' => $fallbackPage['project_files'],
            'app_meta' => [
                'app_title' => is_string($blueprint['title'] ?? null) ? trim((string) $blueprint['title']) : '',
                'meta_description' => is_string($blueprint['description'] ?? null) ? trim((string) $blueprint['description']) : '',
            ],
        ];
    }

    private function buildFallbackReactPage(array $blueprint, array $sharedStyles, ?string $jobId = null): array
    {
        $title = htmlspecialchars($blueprint['title'] ?? 'Generated Page', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = htmlspecialchars($blueprint['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $sections = is_array($blueprint['sections'] ?? null) && count($blueprint['sections']) > 0
            ? $blueprint['sections']
            : [
                ['name' => 'Hero', 'type' => 'hero'],
                ['name' => 'Features', 'type' => 'features'],
                ['name' => 'Pricing', 'type' => 'pricing'],
                ['name' => 'Testimonials', 'type' => 'testimonials'],
                ['name' => 'CTA', 'type' => 'cta'],
                ['name' => 'FAQ', 'type' => 'faq'],
            ];

        // Ensure core landing-page sections exist
        $hasFooter = false;
        $hasNavbar = false;
        $hasHero = false;
        $hasCta = false;
        foreach ($sections as $s) {
            $t = strtolower((string) ($s['type'] ?? ''));
            if ($t === 'footer') $hasFooter = true;
            if ($t === 'header' || $t === 'navbar') $hasNavbar = true;
            if ($t === 'hero') $hasHero = true;
            if ($t === 'cta') $hasCta = true;
        }
        if (!$hasHero) {
            array_unshift($sections, ['name' => 'Hero', 'type' => 'hero']);
        }
        if (!$hasCta) {
            $sections[] = ['name' => 'CTA', 'type' => 'cta'];
        }
        if (!$hasFooter) {
            $sections[] = ['name' => 'Footer', 'type' => 'footer'];
        }
        if (!$hasNavbar) {
            array_unshift($sections, ['name' => 'Navbar', 'type' => 'navbar']);
        }

        $files = [];
        $files[] = ['path' => 'src/components/layout/Navbar.jsx', 'content' => $this->buildFallbackNavbar($title)];
        $files[] = ['path' => 'src/components/sections/Footer.jsx', 'content' => $this->buildFallbackFooter($title)];

        $imports = [];
        foreach ($sections as $section) {
            $type = strtolower((string) ($section['type'] ?? 'content'));
            $name = is_string($section['name'] ?? null) ? trim((string) $section['name']) : ucfirst($type);
            $component = $this->sanitizeComponentName($name);
            if ($component === '') {
                $component = ucfirst($type);
            }

            $path = "src/components/sections/{$component}.jsx";
            $content = match ($type) {
                'hero' => $this->buildFallbackHero($title, $description, $component),
                'features' => $this->buildFallbackFeatures($component),
                'pricing' => $this->buildFallbackPricing($title, $component),
                'testimonials' => $this->buildFallbackTestimonials($component),
                'cta' => $this->buildFallbackCta($title, $component),
                'faq' => $this->buildFallbackFaq($component),
                'stats' => $this->buildFallbackStats($component),
                'newsletter' => $this->buildFallbackNewsletter($component),
                'contact' => $this->buildFallbackContact($component),
                'about' => $this->buildFallbackAbout($title, $component),
                'team' => $this->buildFallbackTeam($component),
                'logos' => $this->buildFallbackLogos($component),
                'footer', 'navbar' => null,
                default => $this->buildFallbackContent($name, $component),
            };

            if ($content === null) {
                continue;
            }

            $files[] = ['path' => $path, 'content' => $content];
            $imports[] = ['name' => $component, 'path' => "../components/sections/{$component}.jsx"];
        }

        $pageCode = $this->buildFallbackLandingPage($title, $imports);
        $files[] = ['path' => 'src/pages/LandingPage.jsx', 'content' => $pageCode];

        if ($jobId) {
            foreach ($files as $file) {
                AiProgressTracker::set($jobId, [
                    'status' => 'generating',
                    'message' => 'Creating ' . $file['path'],
                    'file' => $file['path'],
                    'action' => 'creating',
                ]);
            }
        }

        $html = $this->buildReactRuntimeSnippet('LandingPage', $pageCode, $sharedStyles);

        return [
            'html' => $html,
            'react_code' => $pageCode,
            'project_files' => $files,
        ];
    }

    private function sanitizeComponentName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9]+/', '', $name);
        $name = preg_replace('/^[0-9]+/', '', $name);
        return $name === '' ? '' : $name;
    }

    private function pascalCaseCategory(string $cat): string
    {
        $parts = preg_split('/[-_\s]+/', $cat);
        $name = implode('', array_map('ucfirst', $parts));
        $name = preg_replace('/[^A-Za-z0-9]+/', '', $name);
        $name = preg_replace('/^[0-9]+/', '', $name);
        return $name === '' ? 'Section' : $name;
    }

    private function buildFallbackNavbar(string $title): string
    {
        return <<<JSX
import React from 'react';
import { Button } from '../ui/button.jsx';

export default function Navbar() {
    return (
        <nav className="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/80 backdrop-blur-md">
            <div className="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
                <a href="/" className="flex items-center gap-3">
                    <img data-app-logo src="" alt="App logo" className="h-8 w-8 rounded-lg object-contain hidden" />
                    <span data-app-title className="font-bold text-lg tracking-tight">{$title}</span>
                </a>
                <div className="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
                    <a href="#features" className="hover:text-slate-900">Features</a>
                    <a href="#pricing" className="hover:text-slate-900">Pricing</a>
                    <a href="#testimonials" className="hover:text-slate-900">Testimonials</a>
                    <a href="#faq" className="hover:text-slate-900">FAQ</a>
                </div>
                <Button size="sm">Get Started</Button>
            </div>
        </nav>
    );
}
JSX;
    }

    private function buildFallbackFooter(string $title): string
    {
        return <<<JSX
import React from 'react';

export default function Footer() {
    return (
        <footer className="w-full border-t border-slate-200 bg-slate-50 py-12">
            <div className="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
                <a href="/" className="flex items-center gap-3">
                    <img data-app-logo src="" alt="App logo" className="h-8 w-8 rounded-lg object-contain hidden" />
                    <span data-app-title className="font-semibold text-slate-900">{$title}</span>
                </a>
                <p className="text-sm text-slate-500">Built with the AI Page Builder.</p>
            </div>
        </footer>
    );
}
JSX;
    }

    private function buildFallbackHero(string $title, string $description, string $component): string
    {
        return <<<JSX
import React from 'react';
import { Button } from '../ui/button.jsx';
import { Badge } from '../ui/badge.jsx';
import SectionWrapper from './SectionWrapper.jsx';

export default function {$component}() {
    return (
        <SectionWrapper
            className="pt-24 pb-20 md:pt-32 md:pb-28"
            background={{ type: 'gradflow', config: { color1: '#d21570', color2: '#8e3ff8', color3: '#56ffc3', speed: 0.5, scale: 1.2, type: 'aurora', noise: 0.32 } }}
        >
            <div className="max-w-3xl">
                <Badge variant="outline" className="mb-6 rounded-full px-3 py-1">New launch</Badge>
                <h1 className="text-4xl md:text-6xl lg:text-7xl font-bold tracking-tight mb-6 text-slate-900">
                    {$title}
                </h1>
                <p className="text-lg md:text-xl text-slate-600 max-w-2xl mb-8 leading-relaxed">
                    {$description}
                </p>
                <div className="flex flex-wrap items-center gap-4">
                    <Button className="rounded-xl px-6 py-3 h-auto font-semibold shadow-lg shadow-blue-500/20">Get Started</Button>
                    <Button variant="outline" className="rounded-xl px-6 py-3 h-auto font-semibold">Learn More</Button>
                </div>
            </div>
        </SectionWrapper>
    );
}
JSX;
    }

    public function latestTemplateFiles(): array
    {
        // Templates are internal generation fixtures, not files belonging to every
        // user's app. The selected/generated sections are added by the build flow.
        return [];
    }

    /**
     * Render ALL active DB templates grouped by category into *Test.jsx files.
     * Multiple templates in the same category get suffixed names (FeaturesTest, FeaturesTest2, …).
     */
    private function renderAllTemplatesByCategory(array $grouped, array $sectionOrder, ?string $jobId = null): array
    {
        $generatedFiles = [];
        $usedCategories = [];

        foreach ($sectionOrder as $cat) {
            if (empty($grouped[$cat])) {
                continue;
            }

            $idx = 0;
            foreach ($grouped[$cat] as $template) {
                $suffix = $idx === 0 ? '' : (string) ($idx + 1);
                $componentName = $this->pascalCaseCategory($cat) . 'Test' . $suffix;
                $props = $template->defaults ?? [];

                $content = $this->renderTemplateJsx($template, $props, $componentName);
                $filePath = 'src/components/sections/' . $componentName . '.jsx';
                $content = $this->normalizeImports($content);
                $content = $this->normalizeImportPaths($filePath, $content);

                $generatedFiles[] = ['path' => $filePath, 'content' => $content];
                $this->pushFileWrite($jobId, $filePath, $content);

                if ($jobId) {
                    AiProgressTracker::set($jobId, [
                        'status'  => 'generating',
                        'message' => 'Loaded template: ' . $template->name,
                        'file'    => $filePath,
                        'action'  => 'creating',
                    ]);
                }
                $idx++;
            }
            $usedCategories[] = $cat;
        }

        // Add any remaining categories not in the predefined order
        foreach ($grouped as $cat => $templates) {
            if (in_array($cat, $usedCategories, true)) {
                continue;
            }

            $idx = 0;
            foreach ($templates as $template) {
                $suffix = $idx === 0 ? '' : (string) ($idx + 1);
                $componentName = $this->pascalCaseCategory($cat) . 'Test' . $suffix;
                $props = $template->defaults ?? [];

                $content = $this->renderTemplateJsx($template, $props, $componentName);
                $filePath = 'src/components/sections/' . $componentName . '.jsx';
                $content = $this->normalizeImports($content);
                $content = $this->normalizeImportPaths($filePath, $content);

                $generatedFiles[] = ['path' => $filePath, 'content' => $content];
                $this->pushFileWrite($jobId, $filePath, $content);
                $idx++;
            }
        }

        return $generatedFiles;
    }

    private function buildFallbackFeatures(string $component): string
    {
        return <<<JSX
import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../ui/card.jsx';
import { Zap, Shield, BarChart3 } from 'lucide-react';

export default function {$component}() {
    const features = [
        { icon: Zap, title: 'Fast', desc: 'Built for speed and reliability.' },
        { icon: Shield, title: 'Secure', desc: 'Enterprise-grade security for your data.' },
        { icon: BarChart3, title: 'Insightful', desc: 'Real-time analytics and reporting.' },
    ];

    return (
        <section id="features" className="py-20 md:py-28 bg-white">
            <div className="max-w-7xl mx-auto px-6">
                <div className="mb-12 max-w-2xl">
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-slate-900">Everything you need</h2>
                    <p className="text-lg text-slate-600">A complete toolkit designed for modern teams.</p>
                </div>
                <div className="grid md:grid-cols-3 gap-6">
                    {features.map((f) => (
                        <Card key={f.title} className="rounded-2xl border-slate-200 p-2 hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
                            <CardHeader>
                                <div className="flex justify-end mb-4">
                                    <f.icon size={20} className="text-slate-400" />
                                </div>
                                <CardTitle>{f.title}</CardTitle>
                                <CardDescription>{f.desc}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-slate-600">Part of the predefined Vite project structure.</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackPricing(string $title, string $component): string
    {
        return <<<JSX
import React from 'react';
import { Button } from '../ui/button.jsx';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../ui/card.jsx';
import { Check } from 'lucide-react';

export default function {$component}() {
    const plans = [
        { name: 'Starter', price: '$0', features: ['Core features', '1 project', 'Community support'] },
        { name: 'Pro', price: '$29', features: ['All features', 'Unlimited projects', 'Priority support', 'Analytics'] },
        { name: 'Enterprise', price: 'Custom', features: ['SSO', 'Custom contracts', 'Dedicated support', 'SLA'] },
    ];

    return (
        <section id="pricing" className="py-20 md:py-28 bg-slate-50">
            <div className="max-w-7xl mx-auto px-6">
                <div className="text-center max-w-2xl mx-auto mb-12">
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-slate-900">Simple pricing</h2>
                    <p className="text-lg text-slate-600">Choose the plan that works for your team.</p>
                </div>
                <div className="grid md:grid-cols-3 gap-6">
                    {plans.map((plan, i) => (
                        <Card key={plan.name} className={'rounded-2xl border-slate-200 p-2 ' + (i === 1 ? 'ring-2 ring-blue-600 scale-105' : '')}>
                            <CardHeader>
                                <CardTitle>{plan.name}</CardTitle>
                                <CardDescription><span className="text-3xl font-bold text-slate-900">{plan.price}</span>/mo</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-2 mb-6">
                                    {plan.features.map((feat) => (
                                        <li key={feat} className="flex items-center gap-2 text-sm text-slate-600">
                                            <Check size={16} className="text-blue-600" /> {feat}
                                        </li>
                                    ))}
                                </ul>
                                <Button className="w-full" variant={i === 1 ? 'default' : 'outline'}>Choose {plan.name}</Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackTestimonials(string $component): string
    {
        return <<<JSX
import React from 'react';
import { Card, CardContent } from '../ui/card.jsx';
import { Star } from 'lucide-react';

export default function {$component}() {
    const testimonials = [
        { name: 'Amina Rahman', role: 'Founder', quote: 'This platform completely transformed how we launch landing pages.' },
        { name: 'David Chen', role: 'Product Lead', quote: 'The quality and speed of the generated code is impressive.' },
        { name: 'Sarah Okafor', role: 'Marketing Director', quote: 'We shipped a full campaign site in a single afternoon.' },
    ];

    return (
        <section id="testimonials" className="py-20 md:py-28 bg-white">
            <div className="max-w-7xl mx-auto px-6">
                <div className="text-center max-w-2xl mx-auto mb-12">
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-slate-900">Loved by teams</h2>
                    <p className="text-lg text-slate-600">See what people are saying.</p>
                </div>
                <div className="grid md:grid-cols-3 gap-6">
                    {testimonials.map((t) => (
                        <Card key={t.name} className="rounded-2xl border-slate-200 p-6">
                            <CardContent className="p-0">
                                <div className="flex gap-1 mb-4">
                                    {[...Array(5)].map((_, i) => (
                                        <Star key={i} size={16} className="fill-amber-400 text-amber-400" />
                                    ))}
                                </div>
                                <p className="text-slate-700 mb-4">"{t.quote}"</p>
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-semibold text-blue-700">
                                        {t.name.split(' ').map((n) => n[0]).join('')}
                                    </div>
                                    <div>
                                        <p className="text-sm font-semibold text-slate-900">{t.name}</p>
                                        <p className="text-xs text-slate-500">{t.role}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackCta(string $title, string $component): string
    {
        return <<<JSX
import React from 'react';
import { Button } from '../ui/button.jsx';

export default function {$component}() {
    return (
        <section className="py-20 md:py-28 bg-blue-600 text-white">
            <div className="max-w-7xl mx-auto px-6 text-center">
                <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4">Ready to build with {$title}?</h2>
                <p className="text-lg text-blue-100 max-w-2xl mx-auto mb-8">Get started today and launch your next landing page in minutes.</p>
                <Button className="rounded-xl px-8 py-3 h-auto font-semibold bg-white text-blue-600 hover:bg-blue-50">Start Building</Button>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackFaq(string $component): string
    {
        return <<<JSX
import React from 'react';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '../ui/accordion.jsx';

export default function {$component}() {
    const items = [
        { q: 'How does the AI builder work?', a: 'Describe the page you want and the AI generates a complete React project.' },
        { q: 'Can I edit the generated code?', a: 'Yes. Every file is editable in the Code tab.' },
        { q: 'Is the output production-ready?', a: 'It produces modern React + Tailwind code that you can deploy after review.' },
    ];

    return (
        <section id="faq" className="py-20 md:py-28 bg-slate-50">
            <div className="max-w-3xl mx-auto px-6">
                <div className="text-center mb-12">
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-slate-900">Questions? Answered.</h2>
                    <p className="text-lg text-slate-600">Everything you need to know.</p>
                </div>
                <Accordion type="single" collapsible className="w-full">
                    {items.map((item, i) => (
                        <AccordionItem key={i} value={'item-' + i}>
                            <AccordionTrigger>{item.q}</AccordionTrigger>
                            <AccordionContent>{item.a}</AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackStats(string $component): string
    {
        return <<<JSX
import React from 'react';

export default function {$component}() {
    const stats = [
        { label: 'Users', value: '10K+' },
        { label: 'Projects', value: '50K+' },
        { label: 'Uptime', value: '99.9%' },
        { label: 'Support', value: '24/7' },
    ];

    return (
        <section className="py-16 bg-white border-y border-slate-100">
            <div className="max-w-7xl mx-auto px-6">
                <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    {stats.map((s) => (
                        <div key={s.label}>
                            <p className="text-3xl md:text-4xl font-bold text-slate-900 mb-1">{s.value}</p>
                            <p className="text-sm text-slate-500">{s.label}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackNewsletter(string $component): string
    {
        return <<<JSX
import React from 'react';
import { Button } from '../ui/button.jsx';
import { Input } from '../ui/input.jsx';

export default function {$component}() {
    return (
        <section className="py-20 md:py-28 bg-slate-50">
            <div className="max-w-7xl mx-auto px-6">
                <div className="max-w-xl mx-auto text-center">
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-slate-900">Stay in the loop</h2>
                    <p className="text-lg text-slate-600 mb-8">Get the latest updates delivered to your inbox.</p>
                    <div className="flex gap-2">
                        <Input placeholder="Enter your email" className="flex-1" />
                        <Button>Subscribe</Button>
                    </div>
                </div>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackContact(string $component): string
    {
        return <<<JSX
import React from 'react';
import { Button } from '../ui/button.jsx';
import { Input } from '../ui/input.jsx';
import { Textarea } from '../ui/textarea.jsx';

export default function {$component}() {
    return (
        <section id="contact" className="py-20 md:py-28 bg-white">
            <div className="max-w-7xl mx-auto px-6">
                <div className="max-w-xl mx-auto">
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-slate-900">Contact us</h2>
                    <p className="text-lg text-slate-600 mb-8">We will get back to you as soon as possible.</p>
                    <form className="space-y-4">
                        <Input placeholder="Name" />
                        <Input type="email" placeholder="Email" />
                        <Textarea placeholder="Message" />
                        <Button className="w-full">Send Message</Button>
                    </form>
                </div>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackAbout(string $title, string $component): string
    {
        return <<<JSX
import React from 'react';

export default function {$component}() {
    return (
        <section className="py-20 md:py-28 bg-white">
            <div className="max-w-7xl mx-auto px-6">
                <div className="max-w-3xl">
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-slate-900">About {$title}</h2>
                    <p className="text-lg text-slate-600 leading-relaxed">
                        We are building the future of AI-assisted web design. Our platform helps teams ship beautiful, conversion-focused landing pages in minutes.
                    </p>
                </div>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackTeam(string $component): string
    {
        return <<<JSX
import React from 'react';

export default function {$component}() {
    const team = [
        { name: 'Alex Rivera', role: 'CEO' },
        { name: 'Priya Sharma', role: 'Engineering' },
        { name: 'James Wilson', role: 'Design' },
    ];

    return (
        <section className="py-20 md:py-28 bg-slate-50">
            <div className="max-w-7xl mx-auto px-6">
                <div className="text-center max-w-2xl mx-auto mb-12">
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-slate-900">Meet the team</h2>
                    <p className="text-lg text-slate-600">The people behind the product.</p>
                </div>
                <div className="grid md:grid-cols-3 gap-6">
                    {team.map((member) => (
                        <div key={member.name} className="rounded-2xl border border-slate-200 bg-white p-6 text-center">
                            <div className="h-20 w-20 rounded-full bg-slate-100 mx-auto mb-4 flex items-center justify-center text-xl font-semibold text-slate-600">
                                {member.name.split(' ').map((n) => n[0]).join('')}
                            </div>
                            <h3 className="font-semibold text-slate-900">{member.name}</h3>
                            <p className="text-sm text-slate-500">{member.role}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackLogos(string $component): string
    {
        return <<<JSX
import React from 'react';

export default function {$component}() {
    return (
        <section className="py-16 bg-white border-y border-slate-100">
            <div className="max-w-7xl mx-auto px-6 text-center">
                <p className="text-sm font-medium text-slate-500 mb-8">Trusted by innovative teams</p>
                <div className="flex flex-wrap items-center justify-center gap-8 md:gap-12 opacity-60">
                    {['Acme', 'Globex', 'Hooli', 'Initech', 'Umbrella'].map((logo) => (
                        <span key={logo} className="text-xl font-bold text-slate-700">{logo}</span>
                    ))}
                </div>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackContent(string $name, string $component): string
    {
        return <<<JSX
import React from 'react';

export default function {$component}() {
    return (
        <section className="py-20 md:py-28 bg-white">
            <div className="max-w-7xl mx-auto px-6">
                <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-slate-900">{$name}</h2>
                <p className="text-lg text-slate-600 leading-relaxed">This section was added to the blueprint. Customize it in the Code tab.</p>
            </div>
        </section>
    );
}
JSX;
    }

    private function buildFallbackLandingPage(string $title, array $imports): string
    {
        $importLines = '';
        $sectionLines = '';
        $hasNavbar = false;
        $hasFooter = false;

        foreach ($imports as $imp) {
            $name = (string) ($imp['name'] ?? '');
            $path = (string) ($imp['path'] ?? '');
            $normalizedName = strtolower($name . ' ' . $path);
            if (str_contains($normalizedName, 'nav')
                || str_contains($normalizedName, 'header')
                || str_contains($normalizedName, 'menu')) {
                $hasNavbar = true;
            }
            if (str_contains($normalizedName, 'footer')) {
                $hasFooter = true;
            }
            $importLines .= "import {$name} from '{$path}';\n";
            $sectionLines .= "            <{$name} />\n";
        }

        $navbarImport = $hasNavbar ? '' : "import Navbar from '../components/layout/Navbar.jsx';\n";
        $navbarMarkup = $hasNavbar ? '' : "            <Navbar />\n";
        $footerImport = $hasFooter ? '' : "import Footer from '../components/sections/Footer.jsx';\n";
        $footerMarkup = $hasFooter ? '' : "            <Footer />\n";

        return "import React from 'react';\n{$navbarImport}{$footerImport}{$importLines}\n\nexport default function LandingPage() {\n    return (\n        <div className=\"min-h-screen bg-white text-slate-900\">\n{$navbarMarkup}            <main>\n{$sectionLines}            </main>\n{$footerMarkup}        </div>\n    );\n}\n";
    }



    /**
     * Generate a multi-page React app by making one small AI call per page
     * (mirrors the section-by-section approach used for landing pages).
     * Each call returns one JSX page component; the results are assembled
     * by assembleAppProject() into a full Vite project scaffold.
     */
    private function generateAppPageByPage(
        string  $prompt,
        array   $blueprint,
        array   $sharedStyles,
        string  $model,
        ?string $jobId = null
    ): ?array {
        $pages = is_array($blueprint['pages'] ?? null) ? $blueprint['pages'] : [];
        if (empty($pages)) {
            return null;
        }

        $title       = is_string($blueprint['title'] ?? null) ? (string) $blueprint['title'] : 'Generated App';
        $description = is_string($blueprint['description'] ?? null) ? (string) $blueprint['description'] : '';
        $totalSteps  = count($pages) + 3;

        $systemPrompt = implode("\n", [
            'You are a React page component writer for a Vite + Tailwind multi-page app.',
            'Generate ONE self-contained JSX page component.',
            'Output ONLY valid JSON (no markdown, no code fences):',
            '  {"path": "src/pages/{PageName}.jsx", "content": string}',
            '',
            'RULES:',
            '- Use Tailwind CSS utility classes for ALL styling.',
            '- Brand classes (use these, never raw hex):',
            '    bg-brand-primary, text-brand-primary, bg-brand-secondary, bg-brand-bg, text-brand-text.',
            '- Design system classes: bg-ds-card, bg-ds-background, bg-ds-muted, text-ds-muted-foreground, border-ds-border.',
            '- Import UI components (from src/pages/, use ../components/ui/):',
            '    import { Button } from "../components/ui/button.jsx";',
            '    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../components/ui/card.jsx";',
            '    import { Input } from "../components/ui/input.jsx";',
            '    import { Label } from "../components/ui/label.jsx";',
            '    import { Badge } from "../components/ui/badge.jsx";',
            '    import { Tabs, TabsList, TabsTrigger, TabsContent } from "../components/ui/tabs.jsx";',
            '    import { Progress } from "../components/ui/progress.jsx";',
            '    import { Separator } from "../components/ui/separator.jsx";',
            '    import { Avatar, AvatarFallback } from "../components/ui/avatar.jsx";',
            '    import { Select } from "../components/ui/select.jsx";',
            '    import { Checkbox } from "../components/ui/checkbox.jsx";',
            '    import { Dialog, DialogHeader, DialogTitle, DialogContent } from "../components/ui/dialog.jsx";',
            '- Icons: import { IconName } from "lucide-react"; — use generic icons only. NEVER use brand icons (Twitter, Facebook, Instagram, LinkedIn, Github, Discord, Youtube, etc.) from lucide-react. For brand/social icons, import from ../components/ui/BrandIcons.jsx: `import { Twitter, Github } from "../components/ui/BrandIcons.jsx";`.',
            '- `export default ComponentName;` is MANDATORY.',
            '- Use `import React, { useState, useEffect, useMemo } from "react";` when using hooks.',
            '- For routing: import { Link, useNavigate } from "react-router-dom";',
            '- For auth: import { useAuth } from "../contexts/AuthContext.jsx";',
            '- For Supabase: import { getSupabaseClient } from "../lib/supabase.js";',
            '- The page MUST look like a real app screen for the specified app, NOT a marketing/landing page.',
            '- Use cards, tables, forms, stats, lists appropriate to the page type:',
            '    - dashboard: stat cards, recent activity, summary charts (CSS-based bars), quick actions',
            '    - list/table: searchable data table or card grid with filter',
            '    - form: clean form with labeled inputs, selects, submit button',
            '    - settings: toggle sections, preference panels',
            '    - profile: user info card, avatar, edit form',
            '    - home: app overview with quick stats and navigation cards',
            '    - login: centered card with email + password inputs, use const { login } = useAuth(); call await login({ email, password }) on submit, show error on failure, navigate to "/" on success using useNavigate(). If you add social login buttons (Apple/Google), render each logo at exactly 20x20 px (width/height={20} or className "h-5 w-5") inside the button.',
            '    - signup: centered card with name + email + password inputs, use const { signup } = useAuth(); call await signup({ email, password }) on submit, show error on failure, navigate to "/" on success using useNavigate()',
            '- Use useState for local data. Generate realistic sample data appropriate to the app.',
            '- If Supabase client is available, use it for CRUD; otherwise use local state.',
            '- No Lorem ipsum. Generate real content based on the app title and page description.',
            '- Keep the component CONCISE. Maximum ~80 lines of JSX inside the return().',
            '- Output ONLY the JSON object. No extra text.',
        ]);

        $generatedFiles = [];
        $step = 2;
        $successCount = 0;

        foreach ($pages as $index => $page) {
            if ($jobId && AiProgressTracker::isCancelled($jobId)) {
                throw new \RuntimeException('Generation cancelled by user.');
            }

            $pageName = is_string($page['name'] ?? null) ? trim((string) $page['name']) : 'Page' . ($index + 1);
            $pageType = is_string($page['type'] ?? null) ? trim((string) $page['type']) : 'content';
            $pageDesc = is_string($page['description'] ?? null) ? trim((string) $page['description']) : '';

            $componentName = preg_replace('/[^A-Za-z0-9]/', '', ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $pageName) ?? $pageName));
            if ($componentName === '') {
                $componentName = 'Page' . ($index + 1);
            }

            if ($jobId) {
                AiProgressTracker::set($jobId, [
                    'status'  => 'generating',
                    'step'    => min($step, $totalSteps - 1),
                    'total'   => $totalSteps,
                    'message' => 'Building ' . $pageName . '…',
                    'file'    => 'src/pages/' . $componentName . '.jsx',
                    'action'  => 'creating',
                    'description' => $pageDesc !== '' ? $pageDesc : null,
                ]);
            }

            $this->streamPlanningSummary(
                $jobId,
                $model,
                'The user asked for this app page: ' . $pageName . '. Page type: ' . $pageType . '. Description: ' . ($pageDesc ?: 'No separate description.') . '. Explain the concrete UI structure and implementation choices you are about to generate. Keep it concise and observable.',
                'src/pages/' . $componentName . '.jsx'
            );

            $userPrompt = implode("\n", [
                'App title: ' . $title,
                'App description: ' . $description,
                'User request: ' . $prompt,
                'Industry: ' . ($sharedStyles['industry'] ?? 'general'),
                'Key features: ' . json_encode($sharedStyles['features'] ?? []),
                'Page ' . ($index + 1) . ' of ' . count($pages) . ': ' . $pageName . ' (type: ' . $pageType . ')',
                'Page description: ' . $pageDesc,
                'Component name: ' . $componentName,
                'Brand colors (hex reference — use brand-* classes in code): ' . json_encode($sharedStyles),
                'Design tone: ' . ($sharedStyles['tone'] ?? 'professional, modern'),
                'Typography: heading=' . ($sharedStyles['heading_font'] ?? 'Inter') . ', body=' . ($sharedStyles['body_font'] ?? 'Inter'),
                'Spacing: section padding=' . ($sharedStyles['padding_y'] ?? 80) . 'px, gap=' . ($sharedStyles['gap'] ?? 24) . 'px, border radius=' . ($sharedStyles['border_radius'] ?? 12) . 'px',
                'Button style: ' . ($sharedStyles['button_style'] ?? 'primary'),
                '',
                'Generate the ' . $componentName . ' page component now.',
            ]);

            $result = $this->router->generateJsonObject(
                $model,
                $systemPrompt,
                $userPrompt,
                4500,
                'App Page: ' . $componentName
            );

            $json = null;
            if (is_array($result) && ($result['success'] ?? false) === true) {
                $json = is_array($result['json'] ?? null) ? $result['json'] : null;
            }

            if (!is_array($json)) {
                \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator[app-page-by-page]: page AI call failed', [
                    'page'     => $pageName,
                    'model'    => $model,
                    'ai_error' => $result['message'] ?? null,
                ]);
                $step++;
                continue;
            }

            $filePath    = is_string($json['path'] ?? null)    ? trim((string) $json['path'])    : '';
            $fileContent = is_string($json['content'] ?? null) ? trim((string) $json['content']) : '';

            if ($filePath === '' || $fileContent === '') {
                $step++;
                continue;
            }

            if (!str_starts_with($filePath, 'src/pages/')) {
                $filePath = 'src/pages/' . $componentName . '.jsx';
            }

            if (!$this->isJsxComplete($fileContent)) {
                \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator[app-page-by-page]: page content appears truncated', [
                    'page'   => $pageName,
                    'length' => strlen($fileContent),
                ]);
                $step++;
                continue;
            }

            $fileContent = $this->normalizeImports($fileContent);
            $fileContent = $this->normalizeImportPaths($filePath, $fileContent);

            $generatedFiles[] = ['path' => $filePath, 'content' => $fileContent];
            $this->pushFileWrite($jobId, $filePath, $fileContent);
            $successCount++;
            $step++;
        }

        if ($successCount === 0) {
            return null;
        }

        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status'  => 'generating',
                'step'    => min($step, $totalSteps - 1),
                'total'   => $totalSteps,
                'message' => 'Assembling ' . $title . '…',
                'file'    => 'src/App.jsx',
                'action'  => 'thinking',
                'description' => 'Combining ' . count($generatedFiles) . ' generated pages into the final app.',
            ]);
        }

        $this->streamPlanningSummary(
            $jobId,
            $model,
            'The generated app pages are ready. Explain how they will be connected through the app shell and what the user will be able to do next. Mention the number of generated pages: ' . count($generatedFiles) . '. Keep it concise and observable.',
            'src/App.jsx'
        );

        return $this->assembleAppProject($blueprint, $sharedStyles, $generatedFiles, $jobId);
    }

    /**
     * Whether the prompt + blueprint describe an admin panel / dashboard that
     * should be built on the prebuilt shadcn admin codebase.
     */
    private function isAdminIntent(string $prompt, array $blueprint): bool
    {
        // Every multi-page app uses the prebuilt shadcn admin codebase
        // (sidebar shell + admin UI primitives). Apps are management tools, so
        // the generated project must always include the admin templates.
        if (strtolower((string) ($blueprint['type'] ?? '')) === 'app') {
            return true;
        }

        $lower = strtolower($prompt);
        $strong = [
            'admin panel', 'admin dashboard', 'admin app', 'admin area', 'administration',
            'back office', 'control panel', 'management dashboard', 'crm dashboard',
            'crm panel', 'crm app', 'admin console', 'admin template', 'admin interface',
            'admin site', 'admin page', 'administrator',
        ];
        foreach ($strong as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        // "dashboard" / "crm" alone: only treat as admin when the blueprint is a
        // multi-page app or explicitly lists admin-style pages.
        if (preg_match('/\b(admin|crm|back ?office|dashboard|management panel)\b/i', $prompt)) {
            if (strtolower((string) ($blueprint['type'] ?? '')) === 'app') {
                return true;
            }
            $pages = $blueprint['pages'] ?? [];
            if (is_array($pages) && ! empty($pages)) {
                foreach ($pages as $page) {
                    $type = strtolower((string) (is_array($page) ? ($page['type'] ?? '') : ''));
                    if (in_array($type, ['dashboard', 'analytics', 'admin', 'settings', 'users', 'orders', 'reports'], true)) {
                        return true;
                    }
                }
            }
        }

        // Any multi-page app whose blueprint lists management-style screens
        // (dashboard, settings, tasks, users, etc.) is treated as an admin
        // panel so it gets the prebuilt shadcn sidebar shell. This covers
        // prompts like "task value" that never mention the word "admin".
        if (strtolower((string) ($blueprint['type'] ?? '')) === 'app') {
            $pages = $blueprint['pages'] ?? [];
            if (is_array($pages) && count($pages) >= 2) {
                foreach ($pages as $page) {
                    $type = strtolower((string) (is_array($page) ? ($page['type'] ?? '') : ''));
                    if (in_array($type, [
                        'dashboard', 'analytics', 'admin', 'settings', 'users', 'orders',
                        'reports', 'tasks', 'projects', 'kanban', 'invoices', 'customers',
                        'products', 'inventory', 'transactions', 'payments', 'members', 'team',
                    ], true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Generate an admin panel project on top of the prebuilt shadcn admin
     * codebase. Blueprint pages map onto prebuilt admin pages where possible;
     * any other page is generated by AI as a self-contained admin page. The
     * sidebar nav and routes are rebuilt to match the final page set.
     */
    private function generateAdminProject(
        string  $prompt,
        array   $blueprint,
        array   $sharedStyles,
        string  $model,
        ?string $jobId = null,
        string  $authMode = 'static',
        bool    $withWorkspace = false,
        bool    $withProjects = false,
        ?array  $authProviders = null
    ): ?array {
        $admin = new AdminDefaultCodebase();
        if (! $admin->exists()) {
            return null;
        }
        $map = $admin->projectFileMap();
        if (empty($map)) {
            return null;
        }

        $title = is_string($blueprint['title'] ?? null) && trim((string) $blueprint['title']) !== ''
            ? trim((string) $blueprint['title'])
            : 'Admin Panel';

        // Auth is resolved by generate(): 'functional' (explicit request +
        // connected Supabase) or 'static' (default — non-functional demo forms,
        // never guards the app). Auth pages are never sidebar routes.
        $authPages = ['login', 'signup', 'signin', 'auth'];
        $hasAuthPages = false;

        $pages = is_array($blueprint['pages'] ?? null) && ! empty($blueprint['pages'])
            ? $blueprint['pages']
            : $this->defaultAdminPages();

        $prebuiltByType = $this->adminPrebuiltPageMap();

        $routes = [];
        foreach ($pages as $index => $page) {
            if (! is_array($page)) {
                continue;
            }
            $pageName = is_string($page['name'] ?? null) ? trim((string) $page['name']) : ('Page ' . ($index + 1));
            $pageType = strtolower(is_string($page['type'] ?? null) ? trim((string) $page['type']) : 'content');

            // Auth screens are rendered by adminAppJsx() outside the sidebar
            // shell; skip them so they don't become sidebar routes.
            if (in_array($pageType, $authPages, true)) {
                $hasAuthPages = true;
                continue;
            }

            // Generate an app-specific page first so every app gets a dashboard
            // tuned to its own topic instead of the same generic admin template.
            // The prebuilt pages are only a fallback when the AI page call fails.
            $componentName = $this->adminComponentName($pageName, $index);
            $pageComponent = $this->generateAdminPageComponent($prompt, $blueprint, $sharedStyles, $model, $page, $index, $componentName, $jobId);
            if ($pageComponent !== null) {
                $map['src/pages/' . $componentName . '.jsx'] = [
                    'path'    => 'src/pages/' . $componentName . '.jsx',
                    'content' => $pageComponent,
                ];
                $routes[] = [
                    'name'   => $componentName,
                    'file'   => $componentName . '.jsx',
                    'export' => $componentName,
                    'title'  => $pageName,
                    'type'   => $pageType,
                ];
                continue;
            }

            if (isset($prebuiltByType[$pageType])) {
                $routes[] = [
                    'name'   => $prebuiltByType[$pageType]['export'],
                    'file'   => $prebuiltByType[$pageType]['file'],
                    'export' => $prebuiltByType[$pageType]['export'],
                    'title'  => $pageName,
                    'type'   => $pageType,
                ];
            }
        }

        if (empty($routes)) {
            return null;
        }

        // Rebuild the sidebar nav and routes to match the final page set.
        $map['src/components/app-sidebar.jsx'] = [
            'path'    => 'src/components/app-sidebar.jsx',
            'content' => $this->adminSidebarJsx($routes, $withWorkspace, $withProjects),
        ];
        $map['src/App.jsx'] = [
            'path'    => 'src/App.jsx',
            'content' => $this->adminAppJsx($routes, $title, $authMode, $hasAuthPages, $authProviders),
        ];

        // Static (non-functional) auth pages: only added when the blueprint
        // listed login/signup screens. They render the form but never guard.
        if ($authMode === 'static' && $hasAuthPages) {
            $map['src/pages/static-login-page.jsx'] = [
                'path'    => 'src/pages/static-login-page.jsx',
                'content' => $this->adminStaticAuthPageJsx('login', $title),
            ];
            $map['src/pages/static-signup-page.jsx'] = [
                'path'    => 'src/pages/static-signup-page.jsx',
                'content' => $this->adminStaticAuthPageJsx('signup', $title),
            ];
        }

        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status'  => 'generating',
                'step'    => count($pages) + 2,
                'total'   => count($pages) + 2,
                'message' => 'Assembling ' . $title . '…',
                'file'    => 'src/App.jsx',
                'action'  => 'thinking',
                'description' => 'Combining ' . count($routes) . ' admin pages into the sidebar layout.',
            ]);
        }

        return [
            'html' => '<div class="flex min-h-screen items-center justify-center bg-slate-50 text-slate-600">Admin panel generated. Open the sandbox preview.</div>',
            'react_code' => $map['src/App.jsx']['content'],
            'project_files' => array_values($map),
        ];
    }

    /**
     * Fallback admin project: the prebuilt shadcn admin codebase with its
     * default dashboard/analytics/users/orders/products/settings pages.
     */
    private function buildFallbackAdminProject(array $blueprint, string $authMode = 'static', ?string $jobId = null, ?array $authProviders = null): array
    {
        $admin = new AdminDefaultCodebase();
        $map = $admin->projectFileMap();

        if ($authMode === 'functional') {
            $routes = [];
            foreach ($this->defaultAdminPages() as $page) {
                $prebuilt = $this->adminPrebuiltPageMap()[$page['type']] ?? null;
                if ($prebuilt === null) {
                    continue;
                }
                $routes[] = [
                    'name'   => $prebuilt['export'],
                    'file'   => $prebuilt['file'],
                    'export' => $prebuilt['export'],
                    'title'  => $page['name'],
                    'type'   => $page['type'],
                ];
            }
            if (! empty($routes)) {
                $map['src/App.jsx'] = [
                    'path'    => 'src/App.jsx',
                    'content' => $this->adminAppJsx($routes, 'Admin Panel', 'functional', false, $authProviders),
                ];
            }
        }

        $files = array_values($map);

        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status'  => 'done',
                'step'    => 1,
                'total'   => 1,
                'message' => 'Admin panel scaffold ready',
                'file'    => 'src/App.jsx',
                'action'  => 'done',
            ]);
        }

        return [
            'html' => '<div class="flex min-h-screen items-center justify-center bg-slate-50 text-slate-600">Admin panel scaffold generated.</div>',
            'react_code' => $map['src/App.jsx']['content'] ?? '',
            'project_files' => $files,
        ];
    }

    private function defaultAdminPages(): array
    {
        return [
            ['name' => 'Dashboard', 'type' => 'dashboard'],
            ['name' => 'Analytics', 'type' => 'analytics'],
            ['name' => 'Users', 'type' => 'users'],
            ['name' => 'Orders', 'type' => 'orders'],
            ['name' => 'Products', 'type' => 'products'],
            ['name' => 'Settings', 'type' => 'settings'],
        ];
    }

    /**
     * Map of blueprint page type => prebuilt admin page (file + default export).
     */
    private function adminPrebuiltPageMap(): array
    {
        return [
            'dashboard'     => ['file' => 'dashboard-page.jsx', 'export' => 'DashboardPage'],
            'overview'      => ['file' => 'dashboard-page.jsx', 'export' => 'DashboardPage'],
            'analytics'     => ['file' => 'analytics-page.jsx', 'export' => 'AnalyticsPage'],
            'reports'       => ['file' => 'analytics-page.jsx', 'export' => 'AnalyticsPage'],
            'insights'      => ['file' => 'analytics-page.jsx', 'export' => 'AnalyticsPage'],
            'users'         => ['file' => 'users-page.jsx',     'export' => 'UsersPage'],
            'customers'     => ['file' => 'users-page.jsx',     'export' => 'UsersPage'],
            'team'          => ['file' => 'users-page.jsx',     'export' => 'UsersPage'],
            'orders'        => ['file' => 'orders-page.jsx',    'export' => 'OrdersPage'],
            'transactions'  => ['file' => 'orders-page.jsx',    'export' => 'OrdersPage'],
            'products'      => ['file' => 'products-page.jsx',  'export' => 'ProductsPage'],
            'catalog'       => ['file' => 'products-page.jsx',  'export' => 'ProductsPage'],
            'inventory'     => ['file' => 'products-page.jsx',  'export' => 'ProductsPage'],
            'settings'      => ['file' => 'settings-page.jsx',  'export' => 'SettingsPage'],
        ];
    }

    /**
     * Whether the prompt asks for auth screens / authentication (login, signup,
     * sign in, register, supabase, etc.). Auth is opt-in for admin panels.
     */
    private function isAdminAuthIntent(string $prompt): bool
    {
        $lower = strtolower(trim((string) $prompt));
        if ($lower === '') {
            return false;
        }

        $phrases = [
            'log in', 'sign in', 'log out', 'sign out', 'sign-in', 'sign-up',
            'create an account', 'user account', 'admin login', 'auth screen',
            'auth screens', 'authentication screen',
        ];
        foreach ($phrases as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(auth|login|signin|signup|logout|register|registration|authentication|authenticate|supabase)\b/', $lower);
    }

    /**
     * Whether the prompt asks for workspace/team/org switching in the sidebar.
     * Simple apps render the brand logo instead of a workspace dropdown.
     */
    private function isAdminWorkspaceIntent(string $prompt): bool
    {
        $lower = strtolower(trim((string) $prompt));
        if ($lower === '') {
            return false;
        }

        $phrases = [
            'switch workspace', 'switch workspaces', 'switch team', 'switch teams',
            'workspace switcher', 'team switcher', 'workspace selector',
            'multiple workspaces', 'multiple teams', 'multi-workspace', 'multi-tenant',
            'organization switcher', 'org switcher', 'switch organization', 'switch organizations',
            'switch between workspaces', 'switch between teams', 'workspace dropdown',
        ];
        foreach ($phrases as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(workspace|workspaces)\b/', $lower);
    }

    /**
     * Whether the prompt asks for a grouped "Projects" section in the sidebar.
     * Off by default; only rendered when explicitly requested.
     */
    private function isAdminProjectsIntent(string $prompt): bool
    {
        $lower = strtolower(trim((string) $prompt));
        if ($lower === '') {
            return false;
        }

        $phrases = [
            'projects section', 'project section', 'sidebar projects', 'sidebar project',
            'grouped navigation', 'navigation sections', 'sidebar sections', 'sidebar groups',
            'nav projects', 'project groups', 'project categories', 'categories in the sidebar',
        ];
        foreach ($phrases as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(project sections|sidebar project|nav projects|project groups)\b/', $lower);
    }

    /**
     * Generate the App.jsx shell for the admin project: sidebar layout with a
     * breadcrumb topbar and one route per admin page.
     *
     * $authMode:
     * - 'functional': wrap in AuthProvider, add public /login + /signup, and
     *   guard every page with ProtectedRoute (only when Supabase is connected
     *   and the user explicitly asked for auth).
     * - 'static': AuthProvider is still mounted so any AI-generated page that
     *   calls useAuth() resolves to a valid context (user is null when Supabase
     *   is not connected). No guards, no real session handling. If $hasAuthPages,
     *   add public /login + /signup routes that render non-functional demo forms.
     * - 'none': no auth routes at all.
     */
    private function adminAppJsx(array $routes, string $title, string $authMode = 'static', bool $hasAuthPages = false, ?array $authProviders = null): string
    {
        $imports = [];
        $imports[] = "import React from 'react';";
        $imports[] = "import { Link, Navigate, Route, Routes } from 'react-router-dom';";
        $imports[] = "import { AppSidebar } from './components/app-sidebar.jsx';";
        $imports[] = "import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from './components/ui/breadcrumb.jsx';";
        $imports[] = "import { Separator } from './components/ui/separator.jsx';";
        $imports[] = "import { SidebarInset, SidebarProvider, SidebarTrigger } from './components/ui/sidebar.jsx';";
        $imports[] = "import { AuthProvider } from './contexts/AuthContext.jsx';";

        foreach ($routes as $route) {
            $imports[] = "import " . $route['export'] . " from './pages/" . $route['file'] . "';";
        }

        if ($authMode === 'functional') {
            $imports[] = "import ProtectedRoute from './components/layout/ProtectedRoute.jsx';";
            $imports[] = "import LoginPage from './pages/login-page.jsx';";
            $imports[] = "import SignupPage from './pages/signup-page.jsx';";
        } elseif ($authMode === 'static' && $hasAuthPages) {
            $imports[] = "import StaticLoginPage from './pages/static-login-page.jsx';";
            $imports[] = "import StaticSignupPage from './pages/static-signup-page.jsx';";
        }

        $routesJsx = [];
        if ($authMode === 'functional') {
            $routesJsx[] = '            <Route path="/login" element={<LoginPage />} />';
            $routesJsx[] = '            <Route path="/signup" element={<SignupPage />} />';
        } elseif ($authMode === 'static' && $hasAuthPages) {
            $routesJsx[] = '            <Route path="/login" element={<StaticLoginPage />} />';
            $routesJsx[] = '            <Route path="/signup" element={<StaticSignupPage />} />';
        }
        foreach ($routes as $i => $route) {
            $path = $i === 0 ? '/' : '/' . $this->adminRouteSlug($route['title']);
            $inner = '<Shell title=' . json_encode($route['title'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '><' . $route['export'] . ' /></Shell>';
            $element = $authMode === 'functional' ? '<ProtectedRoute>' . $inner . '</ProtectedRoute>' : $inner;
            $routesJsx[] = '            <Route path="' . $path . '" element={' . $element . '} />';
        }

        $shell = <<<'JS'
function Shell({ title, children }) {
    return (
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset>
                <header className="sidebar-header-trim flex shrink-0 items-center gap-2 transition-[width,height] ease-linear">
                    <div className="flex items-center gap-2 px-4">
                        <SidebarTrigger className="-ml-1" />
                        <Separator orientation="vertical" className="mr-2 h-4" />
                        <Breadcrumb>
                            <BreadcrumbList>
                                <BreadcrumbItem className="hidden md:block">
                                    <BreadcrumbLink asChild>
                                        <Link to="/">Home</Link>
                                    </BreadcrumbLink>
                                </BreadcrumbItem>
                                <BreadcrumbSeparator className="hidden md:block" />
                                <BreadcrumbItem>
                                    <BreadcrumbPage>{title}</BreadcrumbPage>
                                </BreadcrumbItem>
                            </BreadcrumbList>
                        </Breadcrumb>
                    </div>
                </header>
                <div className="flex flex-1 flex-col gap-4 p-4">
                    {children}
                </div>
            </SidebarInset>
        </SidebarProvider>
    );
}
JS;

        $appBody = implode("\n", $routesJsx) . "\n"
            . "            <Route path=\"*\" element={<Navigate to=\"/\" replace />} />\n";

        return implode("\n", $imports) . "\n\n" . $shell . "\n\nexport default function App() {\n    return (\n        <AuthProvider>\n            <Routes>\n"
            . $appBody
            . "            </Routes>\n        </AuthProvider>\n    );\n}\n";
    }

    /**
     * Generate a standalone static (non-functional) auth page. Renders the
     * login/signup form with Apple/Google buttons, but submitting shows a notice
     * instead of authenticating. Used when a blueprint lists login/signup pages
     * but the user never asked for functional auth (or Supabase isn't connected).
     */
    private function adminStaticAuthPageJsx(string $mode, string $title): string
    {
        $isLogin = $mode === 'login';
        $component = $isLogin ? 'Login' : 'Signup';
        $heading = $isLogin ? 'Welcome back' : 'Create an account';
        $subtitle = $isLogin
            ? 'Login is not configured for this demo. This form does not sign you in.'
            : 'Sign up is not configured for this demo. This form does not create an account.';
        $switchLink = $isLogin
            ? 'Don&apos;t have an account? <Link to="/signup" className="underline underline-offset-4 hover:underline">Sign up</Link>'
            : 'Already have an account? <Link to="/login" className="underline underline-offset-4 hover:underline">Login</Link>';

        return <<<JS
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { cn } from '../lib/utils.js';
import { Button } from '../components/ui/button.jsx';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '../components/ui/card.jsx';
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from '../components/ui/field.jsx';
import { Input } from '../components/ui/input.jsx';

export default function Static{$component}Page() {
    const [notice, setNotice] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setNotice(true);
    };

    return (
        <div className="flex min-h-svh items-center justify-center bg-background p-4">
            <div className="w-full max-w-sm">
                <Card>
                    <CardHeader className="text-center">
                        <CardTitle className="text-xl">{$heading}</CardTitle>
                        <CardDescription>{$subtitle}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit}>
                            <FieldGroup>
                                <Field>
                                    <Button variant="outline" type="button" className="w-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" className="size-5">
                                            <path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701" fill="currentColor" />
                                        </svg>
                                        Continue with Apple
                                    </Button>
                                    <Button variant="outline" type="button" className="w-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" className="size-5">
                                            <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z" fill="currentColor" />
                                        </svg>
                                        Continue with Google
                                    </Button>
                                </Field>
                                <Field>
                                    <FieldLabel htmlFor="email">Email</FieldLabel>
                                    <Input id="email" type="email" placeholder="m@example.com" required autoComplete="email" />
                                </Field>
                                <Field>
                                    <FieldLabel htmlFor="password">Password</FieldLabel>
                                    <Input id="password" type="password" required autoComplete="current-password" />
                                </Field>
                                {notice && (
                                    <FieldDescription className="text-muted-foreground">
                                        Authentication is not enabled for this demo. Ask to connect Supabase to enable login.
                                    </FieldDescription>
                                )}
                                <Field>
                                    <Button type="submit" className="w-full">{$heading}</Button>
                                    <FieldDescription className="text-center">
                                        {$switchLink}
                                    </FieldDescription>
                                </Field>
                            </FieldGroup>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
JS;
    }

    /**
     * Generate the app-sidebar component for the admin project, with nav items
     * matching the actual admin pages (icons picked per page type/title).
     * Simple apps show the brand logo; only when $withWorkspace is set is the
     * team/workspace switcher rendered instead. The projects section is only
     * included when $withProjects is set.
     */
    private function adminSidebarJsx(array $routes, bool $withWorkspace = false, bool $withProjects = false): string
    {
        $iconUsed = [];
        $nav = [];
        foreach ($routes as $i => $route) {
            $icon = $this->adminIconForType($route['type'], $route['title']);
            $iconUsed[$icon] = true;
            $slug = $i === 0 ? '/' : '/' . $this->adminRouteSlug($route['title']);
            $nav[] = '        { title: ' . json_encode($route['title'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ', url: ' . json_encode($slug) . ', icon: ' . $icon . ' },';
        }

        // Icons used by the workspace switcher / projects section only when those
        // features are requested. Nav item icons always come from $iconUsed.
        $extraIcons = [];
        if ($withWorkspace) {
            $extraIcons = ['FileText', 'Wallet'];
        }
        if ($withProjects) {
            $extraIcons[] = 'LayoutDashboard';
            $extraIcons[] = 'LifeBuoy';
        }
        $iconList = array_values(array_unique(array_merge($extraIcons, array_keys($iconUsed))));

        $lines = [];
        $lines[] = "import * as React from 'react';";
        $lines[] = "import { " . implode(', ', $iconList) . " } from 'lucide-react';";
        $lines[] = "import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarRail } from '@/components/ui/sidebar.jsx';";
        $lines[] = "import { Logo } from './ui/logo.jsx';";
        $lines[] = "import { NavMain } from './nav-main.jsx';";
        $lines[] = "import { NavUser } from './nav-user.jsx';";
        if ($withWorkspace) {
            $lines[] = "import { TeamSwitcher } from './team-switcher.jsx';";
        }
        if ($withProjects) {
            $lines[] = "import { NavProjects } from './nav-projects.jsx';";
        }
        $lines[] = '';
        $lines[] = 'const data = {';
        if ($withWorkspace) {
            $lines[] = '    teams: [';
            $lines[] = '        { name: "Acme Inc", logo: Wallet, plan: "Enterprise" },';
            $lines[] = '        { name: "Acme Corp.", logo: FileText, plan: "Startup" },';
            $lines[] = '    ],';
        }
        $lines[] = '    navMain: [';
        $lines = array_merge($lines, $nav);
        $lines[] = '    ],';
        if ($withProjects) {
            $lines[] = '    projects: [';
            $lines[] = '        { name: "Design Engineering", url: "/projects/design", icon: LayoutDashboard },';
            $lines[] = '        { name: "Sales & Marketing", url: "/projects/sales", icon: LifeBuoy },';
            $lines[] = '    ],';
        }
        $lines[] = '    user: { name: "Admin", email: "admin@example.com", avatar: "" },';
        $lines[] = '};';
        $lines[] = '';
        $lines[] = 'export function AppSidebar({ ...props }) {';
        $lines[] = '    return (';
        $lines[] = '        <Sidebar collapsible="icon" {...props}>';
        if ($withWorkspace) {
            $lines[] = '            <SidebarHeader><TeamSwitcher teams={data.teams} /></SidebarHeader>';
        } else {
            $lines[] = '            <SidebarHeader className="items-start px-2 py-4"><Logo /></SidebarHeader>';
        }
        $content = '<NavMain items={data.navMain} />';
        if ($withProjects) {
            $content .= '<NavProjects projects={data.projects} />';
        }
        $lines[] = '            <SidebarContent>' . $content . '</SidebarContent>';
        $lines[] = '            <SidebarFooter><NavUser user={data.user} /></SidebarFooter>';
        $lines[] = '            <SidebarRail />';
        $lines[] = '        </Sidebar>';
        $lines[] = '    );';
        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * AI-generate a single admin page component on the prebuilt codebase.
     */
    private function generateAdminPageComponent(
        string  $prompt,
        array   $blueprint,
        array   $sharedStyles,
        string  $model,
        array   $page,
        int     $index,
        string  $componentName,
        ?string $jobId = null
    ): ?string {
        $title = is_string($blueprint['title'] ?? null) ? trim((string) $blueprint['title']) : 'Admin Panel';
        $pageName = is_string($page['name'] ?? null) ? trim((string) $page['name']) : ('Page ' . ($index + 1));
        $pageType = is_string($page['type'] ?? null) ? trim((string) $page['type']) : 'content';
        $pageDesc = is_string($page['description'] ?? null) ? trim((string) $page['description']) : '';

        $systemPrompt = implode("\n", [
            'You are a React page component writer for a shadcn-style ADMIN DASHBOARD inside a Vite + Tailwind app.',
            'Generate ONE self-contained JSX admin page component.',
            'Output ONLY valid JSON (no markdown, no code fences):',
            '  {"path": "src/pages/' . $componentName . '.jsx", "content": string}',
            '',
            'RULES:',
            '- Use Tailwind CSS utility classes for ALL styling.',
            '- Use the design-system theme classes (never raw hex):',
            '    bg-background, text-foreground, bg-card, text-card-foreground, bg-muted, text-muted-foreground,',
            '    border-border, bg-primary, text-primary, text-primary-foreground, bg-secondary, bg-destructive,',
            '    bg-accent, text-accent-foreground. Opacity modifiers are fine (e.g. bg-primary/10).',
            '- The page renders inside the admin layout\'s main content area. Do NOT render a sidebar, topbar, or <main> wrapper.',
            '  Start directly with page content (a flex flex-col with gap-6).',
            '- Use PageHeader for the page title:',
            '    import { PageHeader } from "../components/ui/page-header.jsx";',
            '    <PageHeader title="..." description="..." actions={<>...</>} />',
            '- Import admin UI components from ../components/ui/:',
            '    import { Button } from "../components/ui/button.jsx";',
            '    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../components/ui/card.jsx";',
            '    import { Input } from "../components/ui/input.jsx";',
            '    import { Label } from "../components/ui/label.jsx";',
            '    import { Textarea } from "../components/ui/textarea.jsx";',
            '    import { Badge } from "../components/ui/badge.jsx";',
            '    import { Select } from "../components/ui/select.jsx";',
            '    import { Tabs, TabsList, TabsTrigger, TabsContent } from "../components/ui/tabs.jsx";',
            '    import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "../components/ui/table.jsx";',
            '    import { StatCard } from "../components/ui/stat-card.jsx";',
            '    import { AreaChartCard, BarChartCard, MiniBars } from "../components/ui/charts.jsx";',
            '    import { Avatar, AvatarFallback } from "../components/ui/avatar.jsx";',
            '- StatCard props: <StatCard title="Total Revenue" value="$24,500" change="+12.5%" trend="up" icon={DollarSign} />',
            '- charts.jsx data format: <AreaChartCard title="Revenue" description="Last 6 months" data={[{ label: "Jan", value: 4200 }]} />',
            '    MiniBars props: data (array of { label, value }), className, barClassName, height.',
            '- Icons: import { IconName } from "lucide-react"; use generic icons only.',
            '- If you need the signed-in user, import { useAuth } from "../contexts/AuthContext.jsx"; (useAuth().user).',
            '- `export default ' . $componentName . ';` is MANDATORY.',
            '- Use useState for local data. Generate realistic sample data appropriate to the admin section.',
            '- The page MUST look like a real admin screen for the section: stat cards, data tables, forms, charts, lists.',
            '- TOOL PAGES: if the page is a workspace/tool (prompt input, generator, editor, analyzer, composer), render the actual interactive tool UI (input area + action button + results/output panel) instead of admin tables.',
            '- No Lorem ipsum. Generate real content based on the app title and page description.',
            '- DESIGN CONSISTENCY (mandatory):',
            '    * Every card/panel uses border border-border, rounded-lg, bg-card, shadow-sm. NEVER mix border colors (no border-slate-200/gray-200/hex) or radii.',
            '    * Use the <Card> component for cards — it already provides border border-border rounded-lg bg-card shadow-sm. Do NOT repeat those classes on <Card>; pass className only for layout (e.g. lg:col-span-2). If you hand-roll a panel div, use exactly: border border-border rounded-lg bg-card shadow-sm.',
            '    * Controls (buttons, inputs, badges, tabs) use rounded-md — they match the prebuilt shadcn components. Radii never vary per element.',
            '    * Page content: <div className="flex flex-col gap-6">. Grids use gap-4. Stat card grids use gap-4 too.',
            '    * KPI/stat cards: always use <StatCard> (import from "../components/ui/stat-card.jsx"). Do NOT hand-roll KPI cards with raw divs.',
            '    * Tables inside cards: <CardContent className="p-0"> with the table edge-to-edge; header rows use bg-muted/50.',
            '    * Text hierarchy: titles text-foreground, body/secondary text-muted-foreground. Never hardcode text colors.',
            '- Keep the component CONCISE. Maximum ~90 lines of JSX inside the return().',
            '- Output ONLY the JSON object. No extra text.',
        ]);

        $userPrompt = implode("\n", [
            'App title: ' . $title,
            'User request: ' . $prompt,
            'Industry: ' . ($sharedStyles['industry'] ?? 'general'),
            'Key features: ' . json_encode($sharedStyles['features'] ?? []),
            'Admin page: ' . $pageName . ' (type: ' . $pageType . ')',
            'Page description: ' . $pageDesc,
            'Component name: ' . $componentName,
            '',
            'Generate the ' . $componentName . ' admin page component now.',
        ]);

        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status'  => 'generating',
                'step'    => $index + 2,
                'total'   => count($blueprint['pages'] ?? []) + 2,
                'message' => 'Building ' . $pageName . '…',
                'file'    => 'src/pages/' . $componentName . '.jsx',
                'action'  => 'creating',
                'description' => $pageDesc !== '' ? $pageDesc : null,
            ]);
        }

        $result = $this->router->generateJsonObject($model, $systemPrompt, $userPrompt, 4500, 'Admin Page: ' . $componentName);

        $json = is_array($result) && ($result['success'] ?? false) === true && is_array($result['json'] ?? null)
            ? $result['json']
            : null;

        if (! is_array($json)) {
            \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator[admin]: page AI call failed', [
                'page'     => $pageName,
                'model'    => $model,
                'ai_error' => $result['message'] ?? null,
            ]);

            return null;
        }

        $fileContent = is_string($json['content'] ?? null) ? trim((string) $json['content']) : '';
        if ($fileContent === '' || ! $this->isJsxComplete($fileContent)) {
            return null;
        }

        $fileContent = $this->normalizeImports($fileContent);
        $fileContent = $this->normalizeImportPaths('src/pages/' . $componentName . '.jsx', $fileContent);

        return $fileContent;
    }

    private function adminComponentName(string $pageName, int $index): string
    {
        $componentName = preg_replace('/[^A-Za-z0-9]/', '', ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $pageName) ?? $pageName));
        if ($componentName === '') {
            $componentName = 'Page' . ($index + 1);
        }

        return $componentName;
    }

    private function adminRouteSlug(string $name): string
    {
        $slug = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $name);
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', $slug ?? '');
        $slug = trim($slug ?? '', '-');

        return strtolower($slug);
    }

    private function adminIconForType(string $type, string $title): string
    {
        $t = strtolower(trim($title));
        $type = strtolower($type);

        if (in_array($type, ['dashboard', 'overview', 'home'], true) || $t === 'home' || str_contains($t, 'dashboard')) {
            return 'LayoutDashboard';
        }
        if (in_array($type, ['analytics', 'reports', 'insights', 'statistics'], true)) {
            return 'BarChart3';
        }
        if (in_array($type, ['users', 'customers', 'team', 'members'], true)) {
            return 'Users';
        }
        if (in_array($type, ['orders', 'transactions', 'invoices', 'payments', 'sales'], true)) {
            return 'ShoppingCart';
        }
        if (in_array($type, ['products', 'catalog', 'inventory', 'items'], true)) {
            return 'Package';
        }
        if (in_array($type, ['settings', 'preferences', 'account', 'profile'], true)) {
            return 'Settings';
        }

        return 'FileText';
    }

    /**
     * Assemble a full Vite project from AI-generated app page files.
     * Adds infrastructure (supabase, auth, protected route, layout) and
     * generates App.jsx with routes matching the actual generated pages.
     */
    private function assembleAppProject(array $blueprint, array $sharedStyles, array $pageFiles, ?string $jobId = null): array
    {
        $title       = is_string($blueprint['title'] ?? null) ? trim((string) $blueprint['title']) : 'Generated App';
        $description = is_string($blueprint['description'] ?? null) ? trim((string) $blueprint['description']) : '';
        if ($title === '') {
            $title = 'Generated App';
        }
        if ($description === '') {
            $description = 'A multi-page app generated by AI.';
        }

        $titleLiteral       = json_encode($title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $descriptionLiteral = json_encode($description, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Start with the base scaffold (UI components, config, etc.)
        $codebase = new DefaultCodebase();
        $scaffold = $codebase->defaultFiles($sharedStyles, $title, null);

        $map = [];
        foreach ($scaffold as $f) {
            $map[$f['path']] = $f['content'];
        }

        // Remove the default LandingPage since we have app pages
        unset($map['src/pages/LandingPage.jsx']);

        // Add infrastructure files (supabase, auth, protected route)
        $map['src/lib/supabase.js'] = <<<'JS'
import { createClient } from '@supabase/supabase-js';

let client = null;

export function getSupabaseClient() {
    const url = window.APP_SUPABASE_URL;
    const key = window.APP_SUPABASE_PUBLISHABLE_KEY;

    if (!url || !key) {
        return null;
    }

    if (!client) {
        client = createClient(url, key);
    }

    return client;
}
JS;

        $map['src/contexts/AuthContext.jsx'] = <<<'JS'
import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { getSupabaseClient } from '../lib/supabase.js';

const AuthContext = createContext(null);

function buildLocalUser(email) {
    return {
        id: `local-${email}`,
        email,
        user_metadata: { provider: 'local' },
    };
}

export function AuthProvider({ children }) {
    const client = getSupabaseClient();
    const [user, setUser] = useState(null);
    const [session, setSession] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!client) {
            setLoading(false);
            return undefined;
        }

        let active = true;

        client.auth
            .getSession()
            .then(({ data }) => {
                if (!active) {
                    return;
                }
                setSession(data.session ?? null);
                setUser(data.session?.user ?? null);
                setLoading(false);
            })
            .catch(() => {
                if (active) {
                    setLoading(false);
                }
            });

        const { data } = client.auth.onAuthStateChange((_event, nextSession) => {
            setSession(nextSession ?? null);
            setUser(nextSession?.user ?? null);
            setLoading(false);
        });

        return () => {
            active = false;
            data.subscription.unsubscribe();
        };
    }, [client]);

    const login = async ({ email, password }) => {
        if (client) {
            const { data, error } = await client.auth.signInWithPassword({ email, password });
            if (error) {
                throw error;
            }

            const loggedInUser = data.session?.user ?? null;

            if (loggedInUser && loggedInUser.id) {
                const appId = window.APP_ID || null;
                if (appId) {
                    const { data: existing } = await client
                        .from('app_users')
                        .select('id')
                        .eq('auth_user_id', loggedInUser.id)
                        .eq('app_id', appId)
                        .maybeSingle();

                    if (!existing) {
                        await client.from('app_users').upsert({
                            auth_user_id: loggedInUser.id,
                            app_id: appId,
                            email: loggedInUser.email || email,
                        }, { onConflict: 'auth_user_id,app_id' });
                    }
                }
            }

            setSession(data.session ?? null);
            setUser(loggedInUser);
            return loggedInUser;
        }

        const localUser = buildLocalUser(email);
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const signup = async ({ email, password }) => {
        if (client) {
            const { data, error } = await client.auth.signUp({ email, password });
            if (error) {
                throw error;
            }

            const newUser = data.user ?? data.session?.user ?? null;

            if (newUser && newUser.id) {
                const appId = window.APP_ID || null;
                if (appId) {
                    await client.from('app_users').upsert({
                        auth_user_id: newUser.id,
                        app_id: appId,
                        email: newUser.email || email,
                    }, { onConflict: 'auth_user_id,app_id' });
                }
            }

            setSession(data.session ?? null);
            setUser(newUser);
            return newUser;
        }

        const localUser = buildLocalUser(email);
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const logout = async () => {
        if (client) {
            await client.auth.signOut();
        }

        setSession(null);
        setUser(null);
    };

    const authProviders = typeof window !== 'undefined' && window.APP_AUTH_PROVIDERS
        ? window.APP_AUTH_PROVIDERS
        : { google: { enabled: true }, apple: { enabled: true } };

    const loginWithApple = async () => {
        if (client) {
            const { error } = await client.auth.signInWithOAuth({
                provider: 'apple',
                options: { redirectTo: window.location.origin },
            });
            if (error) throw error;
            return null;
        }
        const localUser = buildLocalUser('apple-user@local.dev');
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const loginWithGoogle = async () => {
        if (client) {
            const { error } = await client.auth.signInWithOAuth({
                provider: 'google',
                options: { redirectTo: window.location.origin },
            });
            if (error) throw error;
            return null;
        }
        const localUser = buildLocalUser('google-user@local.dev');
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const value = useMemo(() => ({
        user,
        session,
        loading,
        mode: client ? 'supabase' : 'local',
        authProviders,
        login,
        loginWithApple,
        loginWithGoogle,
        signup,
        logout,
    }), [user, session, loading, client]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth must be used within AuthProvider');
    }

    return context;
}
JS;

        $map['src/components/layout/ProtectedRoute.jsx'] = <<<'JS'
import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext.jsx';

export default function ProtectedRoute({ children }) {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return (
            <div className="flex min-h-[40vh] items-center justify-center text-slate-500">
                Loading…
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location }} />;
    }

    return children;
}
JS;

        // Collect page info from the generated files
        $pageInfos = [];
        foreach ($pageFiles as $file) {
            $path = $file['path'] ?? '';
            if (preg_match('#src/pages/([A-Za-z0-9_]+)\.jsx$#', $path, $m)) {
                $componentName = $m[1];
                $routePath = '/' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $componentName));
                $pageType = 'content';

                // Match against blueprint pages to get type
                foreach ($blueprint['pages'] ?? [] as $bp) {
                    $bpName = is_string($bp['name'] ?? null) ? trim((string) $bp['name']) : '';
                    $bpComponent = preg_replace('/[^A-Za-z0-9]/', '', ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $bpName) ?? $bpName));
                    if ($bpComponent === $componentName) {
                        $pageType = is_string($bp['type'] ?? null) ? trim((string) $bp['type']) : 'content';
                        break;
                    }
                }

                $pageInfos[] = [
                    'name'     => $componentName,
                    'path'     => $routePath,
                    'type'     => $pageType,
                    'isHome'   => strtolower($pageType) === 'home' || $routePath === '/home',
                    'isPublic' => in_array(strtolower($pageType), ['home', 'login', 'signup'], true),
                ];

                $map[$path] = $file['content'];
            }
        }

        // If no page was identified as home, use the first one
        $hasHome = false;
        foreach ($pageInfos as $pi) {
            if ($pi['isHome']) {
                $hasHome = true;
                break;
            }
        }
        if (!$hasHome && !empty($pageInfos)) {
            $pageInfos[0]['isHome'] = true;
            $pageInfos[0]['isPublic'] = true;
        }

        // Generate sidebar nav links for the app pages (auth/home pages are
        // public and live outside the sidebar layout, so they are excluded).
        $sidebarLinks = '';
        foreach ($pageInfos as $pi) {
            if ($pi['isPublic']) {
                continue;
            }
            $label = preg_replace('/([a-z])([A-Z])/', '$1 $2', $pi['name']);
            $toProp = $pi['isHome'] ? 'to="/" end' : 'to="' . $pi['path'] . '"';
            $iconName = match (strtolower($pi['type'])) {
                'home'      => 'LayoutDashboard',
                'dashboard' => 'LayoutDashboard',
                'login'     => 'LogIn',
                'signup'    => 'UserPlus',
                'settings'  => 'Settings',
                'profile'   => 'User',
                'admin'     => 'Shield',
                default     => 'FileText',
            };
            $sidebarLinks .= '                    <NavLink ' . $toProp . ' className={navClass}>' . "\n";
            $sidebarLinks .= '                        <' . $iconName . ' size={18} />' . "\n";
            $sidebarLinks .= '                        <span>' . $label . '</span>' . "\n";
            $sidebarLinks .= '                    </NavLink>' . "\n";
        }

        $appLayout = str_replace(
            ['__APP_TITLE__', '__SIDEBAR_LINKS__'],
            [$titleLiteral, rtrim($sidebarLinks)],
            <<<'JS'
import React, { useState } from 'react';
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { LayoutDashboard, LogIn, UserPlus, Settings, User, Shield, FileText, Menu, X, LogOut } from 'lucide-react';
import { useAuth } from '../../contexts/AuthContext.jsx';

const navClass = ({ isActive }) => `flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${isActive ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'}`;

export default function AppLayout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const handleLogout = async () => {
        await logout();
        navigate('/login', { replace: true });
    };

    return (
        <div className="flex min-h-screen bg-slate-50 text-slate-900">
            {/* Sidebar */}
            <aside className={`fixed inset-y-0 left-0 z-30 w-64 transform border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                <div className="flex h-16 items-center justify-between border-b border-slate-200 px-6">
                    <Link to="/" className="flex items-center gap-3" onClick={() => setSidebarOpen(false)}>
                        <img data-app-logo src="" alt="App logo" className="h-8 w-8 rounded-lg object-contain hidden" />
                        <span data-app-title className="truncate text-sm font-bold tracking-tight">{__APP_TITLE__}</span>
                    </Link>
                    <button className="lg:hidden" onClick={() => setSidebarOpen(false)}>
                        <X size={20} className="text-slate-500" />
                    </button>
                </div>

                <nav className="flex flex-col gap-1 overflow-y-auto p-4" style={{ maxHeight: 'calc(100vh - 4rem)' }}>
__SIDEBAR_LINKS__
                </nav>

                {user && (
                    <div className="absolute bottom-0 left-0 right-0 border-t border-slate-200 p-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">
                                {user.email?.[0]?.toUpperCase() ?? 'U'}
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-xs font-medium text-slate-900">{user.email}</p>
                                <button onClick={handleLogout} className="flex items-center gap-1 text-xs text-slate-500 hover:text-slate-900">
                                    <LogOut size={12} /> Logout
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </aside>

            {/* Mobile overlay */}
            {sidebarOpen && <div className="fixed inset-0 z-20 bg-black/40 lg:hidden" onClick={() => setSidebarOpen(false)} />}

            {/* Main area */}
            <div className="flex flex-1 flex-col lg:pl-64">
                <header className="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-6 backdrop-blur">
                    <button className="lg:hidden" onClick={() => setSidebarOpen(true)}>
                        <Menu size={22} className="text-slate-700" />
                    </button>

                    <div className="flex-1" />

                    <div className="flex items-center gap-3">
                        {user ? (
                            <button
                                type="button"
                                onClick={handleLogout}
                                className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Logout
                            </button>
                        ) : (
                            <>
                                <Link to="/login" className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    Login
                                </Link>
                                <Link to="/signup" className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                                    Sign Up
                                </Link>
                            </>
                        )}
                    </div>
                </header>

                <main className="flex-1 overflow-y-auto p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
JS
        );
        $map['src/components/layout/AppLayout.jsx'] = $appLayout;

        // Generate App.jsx with dynamic routes
        $imports = "import React from 'react';\n";
        $imports .= "import { HashRouter, Navigate, Route, Routes } from 'react-router-dom';\n";
        $imports .= "import { AuthProvider } from './contexts/AuthContext.jsx';\n";
        $imports .= "import AppLayout from './components/layout/AppLayout.jsx';\n";
        $imports .= "import ProtectedRoute from './components/layout/ProtectedRoute.jsx';\n";

        foreach ($pageInfos as $pi) {
            $imports .= "import {$pi['name']} from './pages/{$pi['name']}.jsx';\n";
        }

        $publicRoutes = '';
        $protectedRoutes = '';
        $hasProtected = false;
        foreach ($pageInfos as $pi) {
            if ($pi['isPublic']) {
                // Auth/home screens are standalone and must not render inside
                // the AppLayout shell (no sidebar on signin/signup).
                $pathAttr = $pi['isHome'] ? 'index' : 'path="' . ltrim($pi['path'], '/') . '"';
                $publicRoutes .= "                    <Route {$pathAttr} element={<{$pi['name']} />} />\n";
                continue;
            }
            $hasProtected = true;
            $protectedRoutes .= "                    <Route\n";
            $protectedRoutes .= "                        path=\"" . ltrim($pi['path'], '/') . "\"\n";
            $protectedRoutes .= "                        element={\n";
            $protectedRoutes .= "                            <ProtectedRoute>\n";
            $protectedRoutes .= "                                <{$pi['name']} />\n";
            $protectedRoutes .= "                            </ProtectedRoute>\n";
            $protectedRoutes .= "                        }\n";
            $protectedRoutes .= "                    />\n";
        }

        $appCode = $imports . "\nexport default function App() {\n";
        $appCode .= "    return (\n";
        $appCode .= "        <HashRouter>\n";
        $appCode .= "            <AuthProvider>\n";
        $appCode .= "                <Routes>\n";
        $appCode .= $publicRoutes;
        if ($hasProtected) {
            $appCode .= "                    <Route element={<AppLayout />}>\n";
            $appCode .= $protectedRoutes;
            $appCode .= "                        <Route path=\"*\" element={<Navigate to=\"/\" replace />} />\n";
            $appCode .= "                    </Route>\n";
        } else {
            $appCode .= "                    <Route path=\"*\" element={<Navigate to=\"/\" replace />} />\n";
        }
        $appCode .= "                </Routes>\n";
        $appCode .= "            </AuthProvider>\n";
        $appCode .= "        </HashRouter>\n";
        $appCode .= "    );\n";
        $appCode .= "}\n";
        $map['src/App.jsx'] = $appCode;

        // Convert map to files array
        $allFiles = array_map(
            fn($path) => ['path' => $path, 'content' => $map[$path]],
            array_keys($map)
        );

        if ($jobId) {
            foreach ($allFiles as $file) {
                AiProgressTracker::set($jobId, [
                    'status'  => 'generating',
                    'message' => 'Creating ' . $file['path'],
                    'file'    => $file['path'],
                    'action'  => 'creating',
                ]);
            }
        }

        // Build react_code from App.jsx for backward compatibility
        $reactCode = $appCode;

        return [
            'html' => '<div class="flex min-h-screen items-center justify-center bg-slate-50 text-slate-600">App generated. Open the sandbox preview.</div>',
            'react_code' => $reactCode,
            'project_files' => $allFiles,
        ];
    }

    private function buildFallbackAppScaffold(array $blueprint, array $sharedStyles, ?string $jobId = null): array
    {
        $title = is_string($blueprint['title'] ?? null) ? trim((string) $blueprint['title']) : 'TaskList App';
        if ($title === '') {
            $title = 'TaskList App';
        }

        $description = is_string($blueprint['description'] ?? null) ? trim((string) $blueprint['description']) : '';
        if ($description === '') {
            $description = 'A multi-page task workspace with authentication and dashboard views.';
        }

        $titleLiteral = json_encode($title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $descriptionLiteral = json_encode($description, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $files = [];
        $addFile = function (string $path, string $content) use (&$files): void {
            $files[] = ['path' => $path, 'content' => $content];
        };

        $addFile('src/lib/supabase.js', <<<'JS'
import { createClient } from '@supabase/supabase-js';

let client = null;

export function getSupabaseClient() {
    const url = window.APP_SUPABASE_URL;
    const key = window.APP_SUPABASE_PUBLISHABLE_KEY;

    if (!url || !key) {
        return null;
    }

    if (!client) {
        client = createClient(url, key);
    }

    return client;
}
JS);

        $addFile('src/contexts/AuthContext.jsx', <<<'JS'
import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { getSupabaseClient } from '../lib/supabase.js';

const AuthContext = createContext(null);

function buildLocalUser(email) {
    return {
        id: `local-${email}`,
        email,
        user_metadata: { provider: 'local' },
    };
}

export function AuthProvider({ children }) {
    const client = getSupabaseClient();
    const [user, setUser] = useState(null);
    const [session, setSession] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!client) {
            setLoading(false);
            return undefined;
        }

        let active = true;

        client.auth
            .getSession()
            .then(({ data }) => {
                if (!active) {
                    return;
                }
                setSession(data.session ?? null);
                setUser(data.session?.user ?? null);
                setLoading(false);
            })
            .catch(() => {
                if (active) {
                    setLoading(false);
                }
            });

        const { data } = client.auth.onAuthStateChange((_event, nextSession) => {
            setSession(nextSession ?? null);
            setUser(nextSession?.user ?? null);
            setLoading(false);
        });

        return () => {
            active = false;
            data.subscription.unsubscribe();
        };
    }, [client]);

    const login = async ({ email, password }) => {
        if (client) {
            const { data, error } = await client.auth.signInWithPassword({ email, password });
            if (error) {
                throw error;
            }

            const loggedInUser = data.session?.user ?? null;

            if (loggedInUser && loggedInUser.id) {
                const appId = window.APP_ID || null;
                if (appId) {
                    const { data: existing } = await client
                        .from('app_users')
                        .select('id')
                        .eq('auth_user_id', loggedInUser.id)
                        .eq('app_id', appId)
                        .maybeSingle();

                    if (!existing) {
                        await client.from('app_users').upsert({
                            auth_user_id: loggedInUser.id,
                            app_id: appId,
                            email: loggedInUser.email || email,
                        }, { onConflict: 'auth_user_id,app_id' });
                    }
                }
            }

            setSession(data.session ?? null);
            setUser(loggedInUser);
            return loggedInUser;
        }

        const localUser = buildLocalUser(email);
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const signup = async ({ email, password }) => {
        if (client) {
            const { data, error } = await client.auth.signUp({ email, password });
            if (error) {
                throw error;
            }

            const newUser = data.user ?? data.session?.user ?? null;

            if (newUser && newUser.id) {
                const appId = window.APP_ID || null;
                if (appId) {
                    await client.from('app_users').upsert({
                        auth_user_id: newUser.id,
                        app_id: appId,
                        email: newUser.email || email,
                    }, { onConflict: 'auth_user_id,app_id' });
                }
            }

            setSession(data.session ?? null);
            setUser(newUser);
            return newUser;
        }

        const localUser = buildLocalUser(email);
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const logout = async () => {
        if (client) {
            await client.auth.signOut();
        }

        setSession(null);
        setUser(null);
    };

    const authProviders = typeof window !== 'undefined' && window.APP_AUTH_PROVIDERS
        ? window.APP_AUTH_PROVIDERS
        : { google: { enabled: true }, apple: { enabled: true } };

    const loginWithApple = async () => {
        if (client) {
            const { error } = await client.auth.signInWithOAuth({
                provider: 'apple',
                options: { redirectTo: window.location.origin },
            });
            if (error) throw error;
            return null;
        }
        const localUser = buildLocalUser('apple-user@local.dev');
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const loginWithGoogle = async () => {
        if (client) {
            const { error } = await client.auth.signInWithOAuth({
                provider: 'google',
                options: { redirectTo: window.location.origin },
            });
            if (error) throw error;
            return null;
        }
        const localUser = buildLocalUser('google-user@local.dev');
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const value = useMemo(() => ({
        user,
        session,
        loading,
        mode: client ? 'supabase' : 'local',
        authProviders,
        login,
        loginWithApple,
        loginWithGoogle,
        signup,
        logout,
    }), [user, session, loading, client]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth must be used within AuthProvider');
    }

    return context;
}
JS);

        $addFile('src/components/layout/ProtectedRoute.jsx', <<<'JS'
import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext.jsx';

export default function ProtectedRoute({ children }) {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return (
            <div className="flex min-h-[40vh] items-center justify-center text-slate-500">
                Loading workspace…
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location }} />;
    }

    return children;
}
JS);

        $appLayout = str_replace('__APP_TITLE__', $titleLiteral, <<<'JS'
import React from 'react';
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext.jsx';

const navClass = ({ isActive }) => `rounded-full px-4 py-2 text-sm font-medium transition ${isActive ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'}`;

export default function AppLayout() {
    const { user, logout, mode } = useAuth();
    const navigate = useNavigate();

    const handleLogout = async () => {
        await logout();
        navigate('/login', { replace: true });
    };

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4">
                    <Link to="/" className="flex items-center gap-3">
                        <img data-app-logo src="" alt="App logo" className="h-10 w-10 rounded-2xl object-contain hidden" />
                        <div>
                            <div data-app-title className="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{__APP_TITLE__}</div>
                            <div className="text-sm font-semibold text-slate-900">Task dashboard</div>
                        </div>
                    </Link>

                    <nav className="hidden items-center gap-2 md:flex">
                        <NavLink to="/" end className={navClass}>Home</NavLink>
                        <NavLink to="/dashboard" className={navClass}>Dashboard</NavLink>
                        <NavLink to="/tasks" className={navClass}>Tasks</NavLink>
                    </nav>

                    <div className="flex items-center gap-2">
                        {user ? (
                            <button
                                type="button"
                                onClick={handleLogout}
                                className="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Logout
                            </button>
                        ) : (
                            <>
                                <Link to="/login" className="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    Login
                                </Link>
                                <Link to="/signup" className="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                                    Signup
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <main className="mx-auto grid max-w-7xl gap-6 px-6 py-8">
                <Outlet />
            </main>
        </div>
    );
}
JS);
        $addFile('src/components/layout/AppLayout.jsx', $appLayout);

        $homePage = str_replace(['__APP_TITLE__', '__APP_DESCRIPTION__'], [$titleLiteral, $descriptionLiteral], <<<'JS'
import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext.jsx';

const stats = [
    { label: 'Pages', value: '4' },
    { label: 'Workflow', value: 'Auth → dashboard → tasks' },
    { label: 'Mode', value: 'Dashboard app' },
];

export default function Home() {
    const { user, mode } = useAuth();

    return (
        <section className="grid gap-6 lg:grid-cols-[1.3fr_0.9fr]">
            <div className="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <span className="inline-flex rounded-full border border-brand-primary/20 bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                    Workspace home
                </span>
                <h1 className="mt-6 text-4xl font-bold tracking-tight text-slate-900 md:text-5xl">
                    {__APP_TITLE__}
                </h1>
                <p className="mt-4 max-w-2xl text-lg leading-8 text-slate-600">
                    {__APP_DESCRIPTION__}
                </p>

                <div className="mt-8 flex flex-wrap gap-3">
                    <Link to={user ? '/dashboard' : '/signup'} className="rounded-full bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-primary/20 transition hover:-translate-y-0.5">
                        Open dashboard
                    </Link>
                    <Link to="/tasks" className="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Manage tasks
                    </Link>
                </div>

                <div className="mt-8 grid gap-4 sm:grid-cols-3">
                    {stats.map((stat) => (
                        <div key={stat.label} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{stat.label}</div>
                            <div className="mt-2 text-sm font-semibold text-slate-900">{stat.value}</div>
                        </div>
                    ))}
                </div>
            </div>

            <aside className="space-y-4">
                <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-slate-900">Session</h2>
                    <div className="mt-4 space-y-3 text-sm text-slate-600">
                        <div className="flex items-center justify-between gap-4">
                            <span>Auth mode</span>
                            <span className="font-medium text-slate-900">{mode === 'supabase' ? 'Supabase' : 'Local demo'}</span>
                        </div>
                        <div className="flex items-center justify-between gap-4">
                            <span>User</span>
                            <span className="font-medium text-slate-900">{user ? user.email : 'Not signed in'}</span>
                        </div>
                    </div>
                </div>

                <div className="rounded-3xl bg-slate-900 p-6 text-white shadow-lg shadow-slate-900/10">
                    <h3 className="text-lg font-semibold">Next step</h3>
                    <p className="mt-2 text-sm leading-6 text-white/70">
                        Use the dashboard to review your progress and the tasks screen to add, complete, and remove items.
                    </p>
                </div>
            </aside>
        </section>
    );
}
JS);
        $addFile('src/pages/Home.jsx', $homePage);

        $loginPage = <<<'JS'
import React, { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext.jsx';

export default function Login() {
    const { login, user, mode } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const from = location.state?.from?.pathname || '/dashboard';
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (user) {
            navigate('/dashboard', { replace: true });
        }
    }, [user, navigate]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setError('');

        try {
            await login({ email, password });
            navigate(from, { replace: true });
        } catch (err) {
            setError(err?.message || 'Unable to sign in.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <section className="mx-auto w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Login</p>
                    <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900">Welcome back</h1>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="mt-8 space-y-4">
                <label className="block">
                    <span className="mb-2 block text-sm font-medium text-slate-700">Email</span>
                    <input
                        type="email"
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none ring-0 transition focus:border-brand-primary"
                        placeholder="you@example.com"
                        required
                    />
                </label>

                <label className="block">
                    <span className="mb-2 block text-sm font-medium text-slate-700">Password</span>
                    <input
                        type="password"
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none ring-0 transition focus:border-brand-primary"
                        placeholder="••••••••"
                        required
                    />
                </label>

                {error ? <p className="rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p> : null}

                <button type="submit" disabled={saving} className="w-full rounded-full bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-primary/20 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60">
                    {saving ? 'Signing in…' : 'Sign in'}
                </button>
            </form>

            <p className="mt-6 text-sm text-slate-600">
                Need an account?{' '}
                <Link to="/signup" className="font-semibold text-brand-primary hover:underline">
                    Create one here
                </Link>
            </p>
        </section>
    );
}
JS;
        $addFile('src/pages/Login.jsx', $loginPage);

        $signupPage = <<<'JS'
import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext.jsx';

export default function Signup() {
    const { signup, user, mode } = useAuth();
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (user) {
            navigate('/dashboard', { replace: true });
        }
    }, [user, navigate]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setSaving(true);
        setError('');

        try {
            await signup({ email, password });
            navigate('/dashboard', { replace: true });
        } catch (err) {
            setError(err?.message || 'Unable to create your account.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <section className="mx-auto w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Signup</p>
                    <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900">Create your workspace</h1>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="mt-8 space-y-4">
                <label className="block">
                    <span className="mb-2 block text-sm font-medium text-slate-700">Email</span>
                    <input
                        type="email"
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none ring-0 transition focus:border-brand-primary"
                        placeholder="you@example.com"
                        required
                    />
                </label>

                <label className="block">
                    <span className="mb-2 block text-sm font-medium text-slate-700">Password</span>
                    <input
                        type="password"
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none ring-0 transition focus:border-brand-primary"
                        placeholder="At least 6 characters"
                        required
                    />
                </label>

                {error ? <p className="rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p> : null}

                <button type="submit" disabled={saving} className="w-full rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60">
                    {saving ? 'Creating account…' : 'Create account'}
                </button>
            </form>

            <p className="mt-6 text-sm text-slate-600">
                Already have an account?{' '}
                <Link to="/login" className="font-semibold text-brand-primary hover:underline">
                    Sign in instead
                </Link>
            </p>
        </section>
    );
}
JS;
        $addFile('src/pages/Signup.jsx', $signupPage);

        $dashboardPage = <<<'JS'
import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext.jsx';

const demoTasks = [
    { id: '1', title: 'Review task intake flow', completed: true },
    { id: '2', title: 'Prepare project dashboard widgets', completed: false },
    { id: '3', title: 'Send invite to new teammate', completed: false },
    { id: '4', title: 'Publish weekly status update', completed: true },
];

export default function Dashboard() {
    const { user, mode } = useAuth();
    const completed = demoTasks.filter((task) => task.completed).length;
    const openTasks = demoTasks.length - completed;

    return (
        <section className="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
            <div className="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Dashboard</p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-slate-900">Hello{user ? `, ${user.email}` : ''}</h1>
                        <p className="mt-2 text-slate-600">A quick view of your workspace, progress, and active work.</p>
                    </div>
                    <Link to="/tasks" className="rounded-full bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-primary/20 transition hover:-translate-y-0.5">
                        Open tasks
                    </Link>
                </div>

                <div className="mt-8 grid gap-4 sm:grid-cols-3">
                    {[
                        { label: 'Tasks', value: demoTasks.length },
                        { label: 'Completed', value: completed },
                        { label: 'Open', value: openTasks },
                    ].map((item) => (
                        <div key={item.label} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{item.label}</div>
                            <div className="mt-2 text-2xl font-bold text-slate-900">{item.value}</div>
                        </div>
                    ))}
                </div>

                <div className="mt-8 rounded-2xl border border-slate-200">
                    <div className="border-b border-slate-200 px-5 py-4">
                        <h2 className="text-lg font-semibold text-slate-900">Recent tasks</h2>
                    </div>
                    <div className="divide-y divide-slate-200">
                        {demoTasks.map((task) => (
                            <div key={task.id} className="flex items-center justify-between gap-4 px-5 py-4">
                                <div>
                                    <div className="font-medium text-slate-900">{task.title}</div>
                                    <div className="text-sm text-slate-500">{task.completed ? 'Completed' : 'In progress'}</div>
                                </div>
                                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${task.completed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}`}>
                                    {task.completed ? 'Done' : 'Open'}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            <aside className="space-y-4">
                <div className="rounded-3xl bg-slate-900 p-6 text-white shadow-lg shadow-slate-900/10">
                    <p className="text-sm font-semibold uppercase tracking-[0.24em] text-white/60">Workspace mode</p>
                    <h2 className="mt-3 text-2xl font-bold tracking-tight">{mode === 'supabase' ? 'Connected to Supabase' : 'Local demo mode'}</h2>
                    <p className="mt-3 text-sm leading-6 text-white/70">
                        Use the task board to add items, mark them complete, and keep your dashboard in sync during the session.
                    </p>
                </div>

                <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 className="text-lg font-semibold text-slate-900">Suggested actions</h3>
                    <div className="mt-4 space-y-3 text-sm text-slate-600">
                        <div className="rounded-2xl bg-slate-50 px-4 py-3">Add your first task</div>
                        <div className="rounded-2xl bg-slate-50 px-4 py-3">Invite a teammate</div>
                        <div className="rounded-2xl bg-slate-50 px-4 py-3">Review completed work</div>
                    </div>
                </div>
            </aside>
        </section>
    );
}
JS;
        $addFile('src/pages/Dashboard.jsx', $dashboardPage);

        $tasksPage = <<<'JS'
import React, { useMemo, useState } from 'react';

const seedTasks = [
    { id: 'task-1', title: 'Draft onboarding checklist', completed: false },
    { id: 'task-2', title: 'Review login flow', completed: true },
    { id: 'task-3', title: 'Publish dashboard metrics', completed: false },
];

export default function Tasks() {
    const [tasks, setTasks] = useState(seedTasks);
    const [title, setTitle] = useState('');
    const [filter, setFilter] = useState('all');

    const filteredTasks = useMemo(() => {
        if (filter === 'completed') {
            return tasks.filter((task) => task.completed);
        }
        if (filter === 'open') {
            return tasks.filter((task) => !task.completed);
        }
        return tasks;
    }, [tasks, filter]);

    const addTask = (event) => {
        event.preventDefault();
        const trimmed = title.trim();
        if (!trimmed) {
            return;
        }

        setTasks((current) => [
            {
                id: `task-${Date.now()}`,
                title: trimmed,
                completed: false,
            },
            ...current,
        ]);
        setTitle('');
    };

    const toggleTask = (id) => {
        setTasks((current) => current.map((task) => (task.id === id ? { ...task, completed: !task.completed } : task)));
    };

    const removeTask = (id) => {
        setTasks((current) => current.filter((task) => task.id !== id));
    };

    const completedCount = tasks.filter((task) => task.completed).length;

    return (
        <section className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <div className="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Tasks</p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-slate-900">Task board</h1>
                        <p className="mt-2 text-slate-600">Add, complete, and remove items for your workspace.</p>
                    </div>
                    <div className="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                        {completedCount}/{tasks.length} done
                    </div>
                </div>

                <form onSubmit={addTask} className="mt-8 flex flex-col gap-3 sm:flex-row">
                    <input
                        type="text"
                        value={title}
                        onChange={(event) => setTitle(event.target.value)}
                        className="flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none transition focus:border-brand-primary"
                        placeholder="Add a new task"
                    />
                    <button type="submit" className="rounded-full bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-primary/20 transition hover:-translate-y-0.5">
                        Add task
                    </button>
                </form>

                <div className="mt-6 flex flex-wrap gap-2">
                    {['all', 'open', 'completed'].map((item) => (
                        <button
                            key={item}
                            type="button"
                            onClick={() => setFilter(item)}
                            className={`rounded-full px-4 py-2 text-sm font-medium transition ${filter === item ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}
                        >
                            {item}
                        </button>
                    ))}
                </div>

                <div className="mt-6 space-y-3">
                    {filteredTasks.map((task) => (
                        <div key={task.id} className="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <button
                                type="button"
                                onClick={() => toggleTask(task.id)}
                                className="flex flex-1 items-center gap-3 text-left"
                            >
                                <span className={`flex h-5 w-5 items-center justify-center rounded-full border ${task.completed ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300 bg-white'}`}>
                                    {task.completed ? '✓' : ''}
                                </span>
                                <span className={`${task.completed ? 'text-slate-400 line-through' : 'text-slate-900'}`}>{task.title}</span>
                            </button>
                            <button type="button" onClick={() => removeTask(task.id)} className="text-sm font-medium text-rose-600 hover:underline">
                                Remove
                            </button>
                        </div>
                    ))}

                    {filteredTasks.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                            No tasks match this filter.
                        </div>
                    ) : null}
                </div>
            </div>

            <aside className="space-y-4">
                <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-slate-900">Task summary</h2>
                    <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div className="rounded-2xl bg-slate-50 px-4 py-3">
                            <div className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Open</div>
                            <div className="mt-2 text-2xl font-bold text-slate-900">{tasks.length - completedCount}</div>
                        </div>
                        <div className="rounded-2xl bg-slate-50 px-4 py-3">
                            <div className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Completed</div>
                            <div className="mt-2 text-2xl font-bold text-slate-900">{completedCount}</div>
                        </div>
                    </div>
                </div>

                <div className="rounded-3xl bg-slate-900 p-6 text-white shadow-lg shadow-slate-900/10">
                    <h3 className="text-lg font-semibold">Why this works</h3>
                    <p className="mt-2 text-sm leading-6 text-white/70">
                        The task board updates instantly in the browser, so you can test the complete app flow without needing a backend.
                    </p>
                </div>
            </aside>
        </section>
    );
}
JS;
        $addFile('src/pages/Tasks.jsx', $tasksPage);

        $appCode = str_replace(['__APP_TITLE__', '__APP_DESCRIPTION__'], [$titleLiteral, $descriptionLiteral], <<<'JS'
import React from 'react';
import { HashRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext.jsx';
import AppLayout from './components/layout/AppLayout.jsx';
import ProtectedRoute from './components/layout/ProtectedRoute.jsx';
import Dashboard from './pages/Dashboard.jsx';
import Home from './pages/Home.jsx';
import Login from './pages/Login.jsx';
import Signup from './pages/Signup.jsx';
import Tasks from './pages/Tasks.jsx';

export default function App() {
    return (
        <HashRouter>
            <AuthProvider>
                <Routes>
                    <Route index element={<Home />} />
                    <Route path="login" element={<Login />} />
                    <Route path="signup" element={<Signup />} />
                    <Route element={<AppLayout />}>
                        <Route
                            path="dashboard"
                            element={
                                <ProtectedRoute>
                                    <Dashboard />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="tasks"
                            element={
                                <ProtectedRoute>
                                    <Tasks />
                                </ProtectedRoute>
                            }
                        />
                        <Route path="*" element={<Navigate to="/" replace />} />
                    </Route>
                </Routes>
            </AuthProvider>
        </HashRouter>
    );
}
JS);
        $addFile('src/App.jsx', $appCode);

        if ($jobId) {
            foreach ($files as $file) {
                AiProgressTracker::set($jobId, [
                    'status' => 'generating',
                    'message' => 'Creating ' . $file['path'],
                    'file' => $file['path'],
                    'action' => 'creating',
                ]);
            }
        }

        return [
            'html' => '<div class="flex min-h-screen items-center justify-center bg-slate-50 text-slate-600">App scaffold generated. Open the sandbox preview.</div>',
            'react_code' => $appCode,
            'project_files' => $files,
        ];
    }
    /**
     * Stage 1: Generate a lightweight blueprint (plan only).
     * Returns: ['type' => 'page'|'chat', 'title', 'description', 'sections', 'message']
     */
    private function generateBlueprint(string $prompt, ?string $screenshotUrl, string $model): array
    {
        $system = implode("\n", [
            'You are a website and app blueprint planner. Your job is to decide if the user wants to BUILD a webpage/app, or if they are just saying hello.',
            '',
            '=== DECISION RULE ===',
            'Decide whether the user wants to BUILD a landing page (type "page"), an interactive multi-page app (type "app"),',
            'or is just chatting (type "chat"). Use the FULL CONTEXT of the request, not individual keywords.',
            '',
            'Guidelines:',
            '- type "app": The user wants a functional, interactive application with multiple screens (login, dashboard, data tables, etc.).',
            '  The primary intent is to USE the app, not to read about it.',
            '  Examples: "build a CRM", "create a task management app with login and dashboard", "make a booking app".',
            '- type "page": The user wants a marketing/landing page, portfolio, blog, or public-facing website.',
            '  The primary intent is to PRESENT information about a product/service/topic.',
            '  Examples: "design a landing page for a task management app", "build a portfolio", "create a product page".',
            '  CRITICAL: If the user says "landing page for X app" or "website for X tool", they want a LANDING PAGE about that app/tool,',
            '  NOT the app itself. The primary intent is the PAGE, not the app. Use context to decide correctly.',
            '  DASHBOARD PAGES: A request that is ONLY about an admin dashboard, admin panel, or dashboard overview view is type "page". Plan it with dashboard section types (sidebar, topbar, dashboard-stats, dashboard-chart, data-table, activity-feed, quick-actions) so reusable dashboard templates are used.',
            '  IMPORTANT: A functional TOOL/APP that happens to include a dashboard (e.g. "a content generation tool with a dashboard", "a CRM with settings", "an analytics app with a dashboard") is type "app" — it is a working application with interactive screens, not a marketing page.',
            '  Use type "app" for any functional tool, app, software, or platform that has interactive screens (dashboard, settings, login, etc.).',
            '  CRITICAL: If the request is to BUILD a tool/app/software/platform, return type "app" even if it also has a dashboard. Only return "page" when the user explicitly asks for a landing page, website, marketing page, portfolio, or blog, or an admin dashboard view ALONE.',
            '- type "chat": The user only says a greeting with no mention of building anything.',
            'NEVER return type "chat" if the user mentions building or creating anything web-related.',
            '',
            '=== EXAMPLES ===',
            'Input: "hi there" -> type: "chat"',
            'Input: "build a portfolio for samsung tv" -> type: "page"',
            'Input: "create a landing page for students" -> type: "page"',
            'Input: "create a tasklist app with login, signup, and dashboard" -> type: "app"',
            'Input: "project management app" -> type: "app"',
            'Input: "build a CRM tool" -> type: "app"',
            'Input: "make me a pricing page" -> type: "page"',
            'Input: "how are you today" -> type: "chat"',
            'Input: "generate a blog about cooking" -> type: "page"',
            'Input: "create a project management tool with tasks and kanban" -> type: "app"',
            'Input: "build a dashboard for analytics" -> type: "page" (dashboard sections: sidebar, topbar, stat cards, charts, data table)',
            'Input: "create an admin dashboard for my CRM" -> type: "page" (dashboard sections: sidebar, stat cards, charts, data table, activity feed)',
            'Input: "make a booking app" -> type: "app"',
            'Input: "design a landing page for a task management app" -> type: "page"',
            'Input: "create a website for a CRM tool" -> type: "page"',
            'Input: "build a homepage for a scheduling app" -> type: "page",',
            'Input: "which model are you?" -> type: "chat"',
            'Input: "where can I find the button code?" -> type: "chat"',
            'Input: "how do I add a pricing section?" -> type: "chat"',
            'Input: "hello" -> type: "chat"',
            '',
            '=== CHAT OUTPUT FORMAT ===',
            '  {"type": "chat", "message": string}',
            '  message: friendly response asking what page they want to build.',
            '',
            '=== DESIGN SYSTEM (include in ALL page and app outputs) ===',
            '  "design_system": {',
            '    "tone": string (e.g. "professional, modern, trustworthy" or "playful, warm, friendly"),',
            '    "color_palette": {',
            '      "primary": "#hex (main brand color for buttons, links, accents)",',
            '      "secondary": "#hex (supporting color)",',
            '      "background": "#hex (page background)",',
            '      "foreground": "#hex (main text color — must contrast with background)",',
            '      "muted": "#hex (subtle background for cards/sections)",',
            '      "muted_foreground": "#hex (secondary text color)",',
            '      "border": "#hex (border color for cards/dividers)",',
            '      "card": "#hex (card background)",',
            '      "accent": "#hex (highlight color)"',
            '    },',
            '    "typography": {',
            '      "heading_font": "FontName (e.g. Inter, Playfair Display, Space Grotesk, Oswald, Manrope)",',
            '      "body_font": "FontName (usually Inter for readability)",',
            '      "google_fonts": ["FontName:weights"] (e.g. ["Inter:400,500,600,700,800", "Space+Grotesk:600,700"])',
            '    },',
            '    "design_requirements": {',
            '      "padding_y": int (vertical section padding in px, 64-96),',
            '      "gap": int (element gap in px, 16-32),',
            '      "border_radius": int (card/button radius in px, 0-24),',
            '      "button_style": "primary|outline|ghost",',
            '      "button_radius": "rounded-none|rounded-sm|rounded|rounded-md|rounded-lg|rounded-xl|rounded-2xl|rounded-full",',
            '      "badge_variant": "solid|outline|subtle|plain" (choose ONE for the entire page)',
            '    }',
            '  }',
            '  Choose colors that match the topic and tone. Ensure good contrast between foreground and background.',
            '  Choose typography that fits the industry (e.g. serif for editorial/luxury, sans-serif for tech/startup).',
            '  The design system MUST be applied consistently across ALL sections/pages.',
            '',
            '=== APP OUTPUT FORMAT ===',
            '  {"type": "app", "title": string, "description": string, "pages": [...], "design_system": {...}}',
            '  title: concise app title (max 60 chars). Prefer the brand, product, app, or company name from the request.',
            '  description: short meta description (max 160 chars).',
            '  pages: ordered array of app pages. Common types: home, dashboard, tasks, list, settings, profile, content. Use 4-8 pages. Do NOT exceed 10 pages.',
            '  AUTH PAGES (login, signup, auth) are OPT-IN ONLY: include them ONLY when the user explicitly asks for authentication, accounts, or login/signup (e.g. "with login", "user accounts", "sign up"). If the user does not ask, do NOT add login/signup/auth pages so the app opens directly.',
            '  Keep page descriptions brief (1 sentence each).',
            '',
            '=== PAGE OUTPUT FORMAT ===',
            '  {"type": "page", "title": string, "description": string, "sections": [...], "design_system": {...}}',
            '  title: concise page title (max 60 chars). Prefer the brand, product, app, or company name from the request.',
            '  description: short meta description (max 160 chars).',
            '  sections: ordered array of sections. Common types: hero, features, pricing, testimonials, cta, contact, footer, header, stats, integrations, about, team, faq, newsletter, logos.',
            '  For admin dashboards and admin panels use dashboard section types: sidebar, topbar, dashboard-stats, dashboard-chart, data-table, activity-feed, quick-actions (plus any content sections that fit).',
            '  CRITICAL: Each section MUST have a descriptive "name" matching its purpose (e.g. "Hero", "Features", "Testimonials", "Pricing", "Footer"). NEVER use empty names or generic names like "Section1".',
            '  Keep section descriptions brief (1 sentence each).',
            '  CRITICAL: ALWAYS include a "Header" section (type "header") as the FIRST section and a "Footer" section (type "footer") as the LAST section of every landing page.',
            '  For a typical landing page, use 8-10 sections: Header, Hero, Features, plus 4-7 more (Stats, About, Testimonials, Pricing, FAQ, CTA, Contact, Logos, Newsletter, Integrations, Team). Do NOT exceed 10 sections.',
            '',
            'Output ONLY valid JSON. No markdown, no code fences, no explanation text.',
        ]);

        $userParts = [];
        $userParts[] = 'User request: ' . $prompt;
        if ($screenshotUrl) {
            $userParts[] = 'Reference screenshot URL: ' . $screenshotUrl;
            $userParts[] = 'Use the screenshot as inspiration for layout and section structure.';
        }

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            implode("\n", $userParts),
            2000,
            'Blueprint Planner'
        );

        $json = null;
        if (is_array($result) && ($result['success'] ?? false) === true) {
            $json = is_array($result['json'] ?? null) ? $result['json'] : null;
        }

        if (!is_array($json)) {
            // Fallback: the AI could not produce a valid blueprint. Build a generic
            // landing page so the user still gets a usable result instead of an error.
            \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator: blueprint AI returned invalid JSON, using default blueprint.', [
                'model' => $model,
                'ai_error' => $result['message'] ?? null,
                'ai_raw' => is_string($result['raw'] ?? null) ? substr((string) $result['raw'], 0, 500) : null,
            ]);
            $json = [
                'type' => 'page',
                'title' => 'Generated Landing Page',
                'description' => 'A multi-section landing page generated by AI.',
                'sections' => [
                    ['name' => 'Hero', 'type' => 'hero', 'description' => 'Headline and call to action'],
                    ['name' => 'Features', 'type' => 'features', 'description' => 'Key features or benefits'],
                    ['name' => 'Pricing', 'type' => 'pricing', 'description' => 'Pricing plans'],
                    ['name' => 'Testimonials', 'type' => 'testimonials', 'description' => 'Customer quotes'],
                    ['name' => 'CTA', 'type' => 'cta', 'description' => 'Final call to action'],
                    ['name' => 'FAQ', 'type' => 'faq', 'description' => 'Frequently asked questions'],
                ],
            ];
        }

        $type = is_string($json['type'] ?? null) ? (string) $json['type'] : 'page';
        if (!in_array($type, ['page', 'app', 'chat'], true)) {
            $type = 'page';
        }

        $type = $this->correctBlueprintType($type, $prompt, $json);

        $sections = is_array($json['sections'] ?? null) ? $json['sections'] : [];
        $pages = is_array($json['pages'] ?? null) ? $json['pages'] : [];
        if ($type === 'app') {
            $pages = $this->normalizeAppPages($pages, $sections, $prompt);
        }

        if ($type === 'chat') {
            $message = is_string($json['message'] ?? null) ? trim((string) $json['message']) : '';
            if ($message === '') {
                $message = 'Hi there! Tell me what kind of page you would like to build and I will create it for you.';
            }
            return ['type' => 'chat', 'message' => $message, 'sections' => [], 'pages' => [], 'title' => '', 'description' => ''];
        }

        if ($type === 'app') {
            if (count($pages) === 0) {
                $pages = [
                    ['name' => 'Home', 'type' => 'home', 'description' => 'Landing view of the app'],
                    ['name' => 'Login', 'type' => 'login', 'description' => 'User login page'],
                    ['name' => 'Signup', 'type' => 'signup', 'description' => 'User registration page'],
                    ['name' => 'Dashboard', 'type' => 'dashboard', 'description' => 'Main app dashboard'],
                ];
            }
            return [
                'type' => 'app',
                'title' => is_string($json['title'] ?? null) ? trim((string) $json['title']) : 'Generated App',
                'description' => is_string($json['description'] ?? null) ? trim((string) $json['description']) : '',
                'sections' => [],
                'pages' => $pages,
                'design_system' => is_array($json['design_system'] ?? null) ? $json['design_system'] : [],
                'message' => '',
            ];
        }

        if (count($sections) === 0) {
            // Fallback: create a single generic section
            $sections = [
                ['name' => 'Content', 'type' => 'content', 'description' => 'Main content area'],
            ];
        }

        if ($type === 'page') {
            $sections = $this->normalizePageSections($sections);
        }

        return [
            'type' => 'page',
            'title' => is_string($json['title'] ?? null) ? trim((string) $json['title']) : 'Generated Page',
            'description' => is_string($json['description'] ?? null) ? trim((string) $json['description']) : '',
            'sections' => $sections,
            'pages' => $pages,
            'design_system' => is_array($json['design_system'] ?? null) ? $json['design_system'] : [],
            'message' => '',
        ];
    }

    /**
     * Deterministically normalize a landing page's section list:
     * - Guarantee a "Header" section is first and a "Footer" section is last.
     * - Fill in missing/empty section types from names.
     * - Pad a too-short list up to a healthy 8-10 sections so pages never
     *   render as a handful of sections.
     */
    private function normalizePageSections(array $sections): array
    {
        $normalized = [];
        $seen = [];

        foreach ($sections as $section) {
            $name = is_string($section['name'] ?? null) ? trim((string) $section['name']) : '';
            $type = is_string($section['type'] ?? null) ? trim((string) $section['type']) : '';
            $desc = is_string($section['description'] ?? null) ? trim((string) $section['description']) : '';

            if ($name === '' && $type === '') {
                continue;
            }

            if ($type === '') {
                $type = $this->normalizeSectionType($section);
            }
            if ($name === '') {
                $name = ucfirst($type);
            }

            $key = strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $normalized[] = [
                'name' => $name,
                'type' => $type,
                'description' => $desc !== '' ? $desc : ($name . ' section'),
            ];
        }

        // Normalize header/footer section names so the page assembly can detect
        // them by component name (prevents a footer named "Site Bottom" from
        // rendering an extra layout <Footer /> alongside its own footer).
        $footerIdx = null;
        $headerIdx = null;
        foreach ($normalized as $i => $s) {
            $t = $this->normalizeSectionType($s);
            if ($t === 'footer' && $footerIdx === null) {
                $footerIdx = $i;
            }
            if ($t === 'header' && $headerIdx === null) {
                $headerIdx = $i;
            }
        }
        foreach ($normalized as $i => $s) {
            $lower = strtolower((string) ($s['name'] ?? ''));
            if ($i === $footerIdx && $lower !== 'footer' && !$this->sectionNameTaken($normalized, 'Footer', $s)) {
                $normalized[$i]['name'] = 'Footer';
            }
            if ($i === $headerIdx && $lower !== 'header' && !$this->sectionNameTaken($normalized, 'Header', $s)) {
                $normalized[$i]['name'] = 'Header';
            }
        }

        // Guarantee Header first
        $hasHeader = false;
        foreach ($normalized as $s) {
            if ($this->normalizeSectionType($s) === 'header') {
                $hasHeader = true;
                break;
            }
        }
        if (!$hasHeader) {
            array_unshift($normalized, ['name' => 'Header', 'type' => 'header', 'description' => 'Top navigation bar with brand and links']);
        }

        // Guarantee Footer last
        $hasFooter = false;
        foreach ($normalized as $s) {
            if ($this->normalizeSectionType($s) === 'footer') {
                $hasFooter = true;
                break;
            }
        }
        if (!$hasFooter) {
            $normalized[] = ['name' => 'Footer', 'type' => 'footer', 'description' => 'Footer with brand, links, and copyright'];
        }

        // Pad up to 8 sections with sensible defaults (respecting existing ones).
        if (count($normalized) < 8) {
            $padPool = [
                ['name' => 'Stats', 'type' => 'stats', 'description' => 'Key numbers and achievements'],
                ['name' => 'About', 'type' => 'about', 'description' => 'About the product or brand'],
                ['name' => 'FAQ', 'type' => 'faq', 'description' => 'Frequently asked questions'],
                ['name' => 'Contact', 'type' => 'contact', 'description' => 'Contact information and form'],
                ['name' => 'Newsletter', 'type' => 'newsletter', 'description' => 'Email newsletter signup'],
                ['name' => 'Logos', 'type' => 'logos', 'description' => 'Trusted-by logos strip'],
                ['name' => 'Integrations', 'type' => 'integrations', 'description' => 'Tool integrations showcase'],
            ];
            $i = 0;
            while (count($normalized) < 8 && $i < count($padPool)) {
                $candidate = $padPool[$i];
                $name = $candidate['name'];
                $dup = false;
                foreach ($normalized as $s) {
                    if (strcasecmp((string) ($s['name'] ?? ''), $name) === 0) {
                        $dup = true;
                        break;
                    }
                    if ($this->normalizeSectionType($s) === $candidate['type']) {
                        $dup = true;
                        break;
                    }
                }
                if (!$dup) {
                    $insertBefore = count($normalized) - 1; // before Footer
                    array_splice($normalized, $insertBefore, 0, [$candidate]);
                }
                $i++;
            }
        }

        // Hard cap at 10 sections (Header..Footer) to keep generation fast and tidy.
        if (count($normalized) > 10) {
            $head = $normalized[0];
            $tail = $normalized[count($normalized) - 1];
            $middle = array_slice($normalized, 1, -1);
            $middle = array_slice($middle, 0, 8);
            $normalized = array_merge([$head], $middle, [$tail]);
        }

        return $normalized;
    }

    /**
     * Whether another (different) section in the list already uses the given name.
     */
    private function sectionNameTaken(array $sections, string $name, array $current): bool
    {
        foreach ($sections as $s) {
            if ($s !== $current && strcasecmp((string) ($s['name'] ?? ''), $name) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Deterministically correct the blueprint type when the model misclassifies
     * a clear app/tool request as a marketing page.
     */
    private function correctBlueprintType(string $type, string $prompt, array $json): string
    {
        if ($type === 'chat') {
            return $type;
        }

        $p = strtolower($prompt);

        $appSignals = [
            'build an app', 'build a tool', 'build the app', 'build the tool',
            'create an app', 'create a tool', 'create the app', 'create the tool',
            'make an app', 'make a tool', 'develop an app', 'develop a tool',
            'build a software', 'build software', 'an ai-powered', 'ai-powered tool',
            'content generation tool', 'generator tool', 'dashboard app', 'admin panel app',
            'saas', 'software application', 'a web app', 'web application', 'an application',
            'with a dashboard', 'dashboard showing', 'tool with', 'app with',
            'with settings for', 'prompt input', 'tool that', 'app that',
        ];
        $pageSignals = [
            'landing page', 'marketing page', 'homepage', 'home page', 'portfolio',
            'website for', 'a blog', 'business website', 'personal website',
            'product page', 'blog page', 'pricing page', 'about page',
        ];

        foreach ($pageSignals as $s) {
            if (str_contains($p, $s)) {
                return 'page';
            }
        }

        foreach ($appSignals as $s) {
            if (str_contains($p, $s)) {
                return 'app';
            }
        }

        $pages = is_array($json['pages'] ?? null) ? $json['pages'] : [];
        if (count($pages) >= 3) {
            return 'app';
        }

        return $type;
    }

    /**
     * Ensure every app page has a usable name/type/description.
     * When the model returned sections instead of pages (common with app
     * blueprints), convert those sections into pages so the app reflects the
     * user's actual screens rather than generic defaults. When the model
     * returns nothing usable, derive tool-specific pages from the request
     * (dashboard, content/history, settings, etc.) so the app actually matches
     * what the user asked to build.
     */
    private function normalizeAppPages(array $pages, array $sections = [], ?string $prompt = null): array
    {
        if (count($pages) === 0 && count($sections) > 0) {
            $pages = $sections;
        }

        if (count($pages) === 0 && is_string($prompt) && trim($prompt) !== '') {
            $pages = $this->pagesFromPrompt($prompt);
        }

        if (count($pages) === 0) {
            return [
                ['name' => 'Home', 'type' => 'home', 'description' => 'Landing view of the app'],
                ['name' => 'Login', 'type' => 'login', 'description' => 'User login page'],
                ['name' => 'Signup', 'type' => 'signup', 'description' => 'User registration page'],
                ['name' => 'Dashboard', 'type' => 'dashboard', 'description' => 'Main app dashboard'],
            ];
        }

        $normalized = [];
        foreach ($pages as $i => $page) {
            $name = is_string($page['name'] ?? null) ? trim((string) $page['name']) : '';
            $pageType = is_string($page['type'] ?? null) ? trim((string) $page['type']) : '';
            $desc = is_string($page['description'] ?? null) ? trim((string) $page['description']) : '';

            if ($name === '') {
                $name = $pageType !== '' ? ucfirst($pageType) : 'Page' . ($i + 1);
            }
            if ($pageType === '') {
                $lower = strtolower($name);
                if (str_contains($lower, 'dashboard')) {
                    $pageType = 'dashboard';
                } elseif (str_contains($lower, 'login')) {
                    $pageType = 'login';
                } elseif (str_contains($lower, 'signup') || str_contains($lower, 'register')) {
                    $pageType = 'signup';
                } elseif (str_contains($lower, 'setting')) {
                    $pageType = 'settings';
                } elseif (str_contains($lower, 'home') || str_contains($lower, 'landing')) {
                    $pageType = 'home';
                } else {
                    $pageType = 'content';
                }
            }
            if ($desc === '') {
                $desc = $name . ' page';
            }

            $normalized[] = ['name' => $name, 'type' => $pageType, 'description' => $desc];
        }

        return $normalized;
    }

    /**
     * Derive tool-specific app pages from the user's prompt when the model
     * failed to emit a usable page list. Returns 4-6 pages that reflect the
     * screens the user actually asked for.
     */
    private function pagesFromPrompt(string $prompt): array
    {
        $p = strtolower($prompt);

        $pages = [
            ['name' => 'Home', 'type' => 'home', 'description' => 'Landing view of the app'],
        ];

        $hasAuth = str_contains($p, 'login') || str_contains($p, 'signup')
            || str_contains($p, 'auth') || str_contains($p, 'account') || str_contains($p, 'user');
        if ($hasAuth) {
            $pages[] = ['name' => 'Login', 'type' => 'login', 'description' => 'User login page'];
            $pages[] = ['name' => 'Signup', 'type' => 'signup', 'description' => 'User registration page'];
        }

        if (str_contains($p, 'dashboard')) {
            $pages[] = ['name' => 'Dashboard', 'type' => 'dashboard', 'description' => 'Main app dashboard'];
        }

        $contentKinds = [
            'generated content history' => ['Content', 'content', 'Generated content history'],
            'history' => ['History', 'history', 'Activity and history log'],
            'content' => ['Content', 'content', 'Browse and manage content'],
            'prompt' => ['Create', 'form', 'Create new items from a prompt'],
            'generator' => ['Generator', 'form', 'Generate content from a prompt'],
            'create' => ['Create', 'form', 'Create new items'],
        ];
        foreach ($contentKinds as $key => [$name, $type, $desc]) {
            if (str_contains($p, $key) && !$this->pageListHas($pages, $name)) {
                $pages[] = ['name' => $name, 'type' => $type, 'description' => $desc];
                break;
            }
        }

        if (str_contains($p, 'setting') || str_contains($p, 'tone') || str_contains($p, 'style')
            || str_contains($p, 'preference')) {
            if (!$this->pageListHas($pages, 'Settings')) {
                $pages[] = ['name' => 'Settings', 'type' => 'settings', 'description' => 'App settings and preferences'];
            }
        }

        if (count($pages) < 4) {
            if (!$this->pageListHas($pages, 'Login')) {
                $pages[] = ['name' => 'Login', 'type' => 'login', 'description' => 'User login page'];
            }
            if (!$this->pageListHas($pages, 'Dashboard')) {
                $pages[] = ['name' => 'Dashboard', 'type' => 'dashboard', 'description' => 'Main app dashboard'];
            }
            if (!$this->pageListHas($pages, 'Settings')) {
                $pages[] = ['name' => 'Settings', 'type' => 'settings', 'description' => 'App settings and preferences'];
            }
        }

        return array_slice($pages, 0, 6);
    }

    private function pageListHas(array $pages, string $name): bool
    {
        foreach ($pages as $page) {
            if (strcasecmp((string) ($page['name'] ?? ''), $name) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Stage 2: Generate HTML for a single section.
     */
    private function generateSectionHtml(array $section, array $blueprint, array $sharedStyles, string $model, int $index): ?string
    {
        $sectionName = is_string($section['name'] ?? null) ? (string) $section['name'] : 'Section';
        $sectionType = is_string($section['type'] ?? null) ? (string) $section['type'] : 'content';
        $sectionDesc = is_string($section['description'] ?? null) ? (string) $section['description'] : '';

        $system = implode("\n", [
            'You are a frontend developer. Generate a SINGLE HTML section for a website.',
            'Output ONLY valid JSON: {"html": string}',
            '',
            'Rules:',
            '- Use Tailwind CSS for all styling.',
            '- Brand colors: bg-brand-primary (primary button bg), bg-brand-secondary, bg-brand-bg (page bg), text-brand-text (body text). Use these EXACT classes, never raw hex colors.',
            '- Neutral grays: slate-50 through slate-900.',
            '- Layout: max-w-7xl mx-auto px-6 py-16, grid grid-cols-1 md:grid-cols-2/3 gap-8, flex flex-col, space-y-4/8.',
            '- Typography: text-4xl md:text-5xl font-bold tracking-tight for headings, text-lg text-slate-600 leading-relaxed for body.',
            '- Buttons: bg-brand-primary text-white rounded-lg px-5 py-3 font-semibold. Never output a button without bg-brand-*.',
            '- Light bg -> dark text. Dark bg -> white text.',
            '- Placeholder images: https://picsum.photos/ with descriptive alt text and object-cover.',
            '- Return ONLY the section inner HTML (no <html>/<head>/<body>/<style> tags).',
            '- Wrap in a semantic container: <section id="{slug}" class="..."></section> or <header>/<footer>/<nav>.',
            '- No JavaScript. No <script> tags.',
            '- Generate unique creative content. Never use "Lorem ipsum" or "Heading".',
            '- Use inline Lucide SVGs for icons (simple popular ones: Star, Heart, Zap, Shield, Globe, Mail, Check, ArrowRight, Users, Layers).',
            '- Keep backgrounds clean: alternate between bg-white, bg-slate-50, bg-brand-bg. Use gradient blobs only for hero sections.',
            '- Every card must have hover:-translate-y-1 hover:shadow-xl transition-all duration-300.',
            '- For feature grids use a clean bento-style or horizontal inline layout. Keep it simple.',
            '- No markdown, no code fences. Output ONLY the JSON.',
        ]);

        $userParts = [];
        $userParts[] = 'Page title: ' . $blueprint['title'];
        $userParts[] = 'Section ' . ($index + 1) . ': ' . $sectionName . ' (type: ' . $sectionType . ')';
        $userParts[] = 'Section purpose: ' . $sectionDesc;
        if (!empty($sharedStyles)) {
            $userParts[] = 'Brand colors (hex for reference): ' . json_encode($sharedStyles);
        }

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            implode("\n", $userParts),
            6000,
            'Section Generator: ' . $sectionName
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator: section generation failed (AI call)', [
                'section' => $sectionName,
                'model' => $model,
                'error' => $result['message'] ?? 'Unknown AI error',
            ]);
            return null;
        }

        $json = is_array($result['json'] ?? null) ? $result['json'] : null;
        if (!is_array($json) || !is_string($json['html'] ?? null)) {
            \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator: section generation failed (no JSON/html)', [
                'section' => $sectionName,
                'model' => $model,
                'has_json' => $result['json'] !== null ? 'yes' : 'no',
            ]);
            return null;
        }

        $html = trim((string) $json['html']);
        if ($html === '') {
            \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator: section generated empty HTML', [
                'section' => $sectionName,
            ]);
            return null;
        }

        // Strip any stray inline styles or <style> tags the model might have added
        $html = $this->stripInlineStyles($html);

        return $html;
    }

    /**
     * Stage 3: Assemble sections into a complete HTML document.
     */
    private function assemblePage(array $blueprint, array $sectionHtmls, array $sharedStyles): string
    {
        $primary = $sharedStyles['primary'] ?? '#3b82f6';
        $secondary = $sharedStyles['secondary'] ?? '#10b981';
        $background = $sharedStyles['background'] ?? '#ffffff';
        $text = $sharedStyles['text'] ?? '#111827';

        $sectionsHtml = implode("\n\n", $sectionHtmls);

        return <<<HTML
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>
    window.tailwind = window.tailwind || {};
    window.tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        primary: '{$primary}',
                        secondary: '{$secondary}',
                        bg: '{$background}',
                        text: '{$text}',
                    }
                }
            }
        }
    };
</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    .mailpurse-ai-page, .mailpurse-ai-page * { box-sizing: border-box; }
    .mailpurse-ai-page { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .bg-brand-primary { background-color: {$primary} !important; }
    .text-brand-primary { color: {$primary} !important; }
    .bg-brand-secondary { background-color: {$secondary} !important; }
    .text-brand-secondary { color: {$secondary} !important; }
    .bg-brand-bg { background-color: {$background} !important; }
    .text-brand-text { color: {$text} !important; }
    .mp-gradient-blob { position:absolute; border-radius:50%; filter:blur(80px); opacity:.35; pointer-events:none; z-index:0; }
    .mp-bg-grid { background-image: linear-gradient(rgba(0,0,0,.04) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,.04) 1px, transparent 1px); background-size: 40px 40px; }
    .mp-glass-card { background: rgba(255,255,255,.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,.4); }
    .mp-card-hover { transition: all .3s ease; }
    .mp-card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,.1); }
    .mp-text-gradient { background-clip: text; -webkit-background-clip: text; color: transparent; }
    .mailpurse-ai-page section, .mailpurse-ai-page header, .mailpurse-ai-page footer, .mailpurse-ai-page nav, .mailpurse-ai-page .section { padding-top: clamp(2rem, 5vw, 4rem) !important; padding-bottom: clamp(2rem, 5vw, 4rem) !important; }
    .mailpurse-ai-page section > *, .mailpurse-ai-page header > *, .mailpurse-ai-page footer > *, .mailpurse-ai-page nav > * { margin-top: 0 !important; }
    .mailpurse-ai-page section * + *, .mailpurse-ai-page header * + *, .mailpurse-ai-page footer * + *, .mailpurse-ai-page nav * + * { margin-top: clamp(0.5rem, 1vw, 1rem) !important; }
</style>
<div class="mailpurse-ai-page bg-brand-bg text-brand-text antialiased">
{$sectionsHtml}
</div>
HTML;
    }

    /**
     * Templates Test mode: build a full page using ALL active templates with
     * their default props. No AI calls — pure template rendering for testing.
     */
    private function generateTemplatesTestPage(array $sharedStyles, ?string $jobId = null): ?array
    {
        // Define a sensible section order for the test page
        $sectionOrder = ['header', 'hero', 'logos', 'features', 'why-choose-us', 'stats', 'integrations', 'about', 'team', 'testimonials', 'pricing', 'faq', 'cta', 'contact', 'newsletter', 'footer', 'sidebar', 'topbar', 'dashboard-stats', 'dashboard-chart', 'data-table', 'activity-feed', 'quick-actions'];

        // Load all active templates grouped by category
        $allTemplates = AiBuilderTemplate::active()->orderBy('sort_order')->orderBy('name')->get();
        if ($allTemplates->isEmpty()) {
            return null;
        }

        $grouped = [];
        foreach ($allTemplates as $tpl) {
            $cat = strtolower(trim((string) $tpl->category));
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $tpl;
        }

        // Build section files — render ALL templates per category, not just the first
        $generatedFiles = $this->renderAllTemplatesByCategory($grouped, $sectionOrder, $jobId);

        if (empty($generatedFiles)) {
            return null;
        }

        // Assemble into a full Vite project
        $codebase = new DefaultCodebase();
        $projectFiles = $codebase->sectionFiles($sharedStyles, 'Templates Test Page', $generatedFiles);

        // Pick the first page file for react_code
        $reactCode = '';
        foreach ($projectFiles as $pf) {
            if (preg_match('#^src/pages/#', $pf['path'] ?? '')) {
                $reactCode = $pf['content'];
                break;
            }
        }

        $previewHtml = $this->buildReactRuntimeSnippet('LandingPage', $reactCode, $sharedStyles);

        return [
            'html'          => $previewHtml,
            'react_code'    => $reactCode,
            'project_files' => $projectFiles,
        ];
    }

    /**
     * Section-by-section React generation.
     *
     * Calls the AI once per section (small prompt ~2 500 tokens) instead of
     * one huge call that times out for Zhipu / slower models.
     * Each call returns one JSX component file; the results are assembled by
     * DefaultCodebase::sectionFiles() into a full Vite project scaffold.
     */
    private function generateReactPageSectionBySection(
        string  $prompt,
        array   $blueprint,
        array   $sharedStyles,
        string  $model,
        ?string $jobId = null
    ): ?array {
        $sections = is_array($blueprint['sections'] ?? null) ? $blueprint['sections'] : [];
        if (empty($sections)) {
            return null;
        }

        // Background budget: the HERO gets a decorative background
        // (gradflow/grid/grid-fade/dots/mesh), one CTA or pricing section may use
        // the same decorative rule, and at most ONE other section gets a dark/black
        // background. Everything else stays on the plain page background, and the
        // footer always keeps its stored template background.
        $decorativeSectionIndex = $this->pickDecorativeSectionIndex($sections);
        $darkSectionIndex = $this->pickDarkSectionIndex($sections);

        $title       = is_string($blueprint['title'] ?? null) ? (string) $blueprint['title'] : 'Generated Page';
        $totalSteps  = count($sections) + 3; // plan + N sections + assemble + done

        $systemPrompt = implode("\n", [
            'You are a React component writer for a Vite + Tailwind project.',
            'Generate ONE self-contained JSX section component.',
            'Output ONLY valid JSON (no markdown, no code fences):',
            '  {"path": "src/components/sections/{ComponentName}.jsx", "content": string}',
            '',
            'RULES:',
            '- Use Tailwind CSS utility classes for ALL styling. No inline styles.',
            '- Brand classes (use these, never raw hex):',
            '    bg-brand-primary, text-brand-primary, bg-brand-secondary, text-brand-secondary,',
            '    bg-brand-bg, text-brand-text.',
            '- Design system classes: bg-ds-card, bg-ds-background, bg-ds-muted, text-ds-muted-foreground,',
            '    border-ds-border, bg-ds-primary, text-ds-primary-foreground.',
            '- Import only from: react, lucide-react, and relative UI components already in the codebase.',
            '  Available relative paths (from src/components/sections/):',
            '    import SectionWrapper from "./SectionWrapper.jsx";',
            '    import { Button } from "../ui/button.jsx";',
            '    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../ui/card.jsx";',
            '    import { Badge } from "../ui/badge.jsx";',
            '    import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from "../ui/accordion.jsx";',
            '    import { Tabs, TabsList, TabsTrigger, TabsContent } from "../ui/tabs.jsx";',
            '    import { Avatar, AvatarImage, AvatarFallback } from "../ui/avatar.jsx";',
            '    import { Separator } from "../ui/separator.jsx";',
            '    import { Progress } from "../ui/progress.jsx";',
            '    import { Skeleton } from "../ui/skeleton.jsx";',
            '    import { cn } from "../../lib/utils.js";',
            '    import { useIsMobile } from "../../hooks/use-mobile.js";',
            '- Icons: import { IconName } from "lucide-react"; — only use generic icons (Check, Star, Zap, Shield, Globe, Mail, ArrowRight, Users, Layers, ChevronDown, Menu, X, Plus, Minus). NEVER use brand icons.',
            '- SOCIAL/BRAND ICONS: lucide-react does NOT include brand icons. A BrandIcons.jsx component is available at ../ui/BrandIcons.jsx with predefined inline SVGs for: Twitter, Facebook, Instagram, Linkedin, Github, Youtube, Tiktok, Discord, Whatsapp, Telegram, Pinterest, Reddit, Twitch, Spotify. Import from there: `import { Twitter, Github } from "../ui/BrandIcons.jsx";`. Use them like any icon component: <Twitter className="h-5 w-5" />. Do NOT import brand icons from lucide-react.',
            '- Root element: wrap the whole section in <SectionWrapper id="..." background={...}> with the inner content inside the same centered max-w-7xl container that the wrapper provides.',
            '- SectionWrapper `background` prop (optional). Choose a background that matches the section purpose and brand colors:',
            '    type: "grid" | "grid-fade" | "dots" | "mesh" | "blobs" | "image" | "gradflow"',
            '    config: depends on type',
            '      gradflow: { color1, color2, color3 } (hex or {r,g,b}), speed (0.1-3), scale (0.5-3), type: "aurora"|"mesh"|"smoke"|"wave"|"silk", noise (0-0.5)',
            '      mesh/blobs: { color1, color2, color3 } (hex or {r,g,b}) — pick from the brand palette',
            '      image: { src: "...", overlay: "rgba(0,0,0,0.4)" }',
            '  Ready-to-use presets (pick a preset_id from the catalog and copy the config, or customize colors):',
            '  Active background presets: ' . BackgroundPresetStore::catalogForPrompt(),
            '  Background selection rules (STRICT — follow exactly):',
            '    - EXACTLY ONE section gets a decorative background: the HERO. Its type must be gradflow, grid, grid-fade, dots, or mesh. NEVER use blobs or image for the hero.',
            '    - AT MOST ONE CTA or pricing section may use the SAME decorative rule as the hero (gradflow, grid, grid-fade, dots, or mesh only — never blobs or image).',
            '    - AT MOST ONE other section (a stats, testimonials or features-style accent section) may use a DARK background: render <SectionWrapper dark background={{ type: "dark" }}> or a dark gradflow preset so the section renders dark.',
            '    - ALL other sections MUST have NO background — render <SectionWrapper> without a background prop (plain page background).',
            '    - The FOOTER section MUST keep its stored template background — do NOT add or change a background prop.',
            '    - For any section with a dark background, set `dark={true}` on SectionWrapper so the text stays white.',
            '  Recommended usage by section:',
            '    Hero: use gradflow (aurora) OR image with overlay',
            '    CTA: use blobs or gradflow (mesh)',
            '    Features: use grid-fade or dots',
            '    Testimonials/Pricing: use mesh or grid',
            '    FAQ/Content: use grid-fade or dots',
            '- Headings: text-4xl md:text-5xl font-bold tracking-tight.',
            '- Cards: rounded-2xl border border-slate-200 shadow-sm hover:-translate-y-1 hover:shadow-xl transition-all duration-300.',
            '- For HERO: large headline (text-5xl md:text-7xl), two CTA buttons. Wrap in <SectionWrapper background={{ type: "gradflow" or "image" ... }}>.',
            '- For FEATURES: bento-grid layout with col-span-2 for featured card.',
            '- For PRICING: 3-tier cards, middle one has ring-2 ring-brand-primary scale-105.',
            '- For TESTIMONIALS: star rating (Star icons fill-current text-amber-400), initials avatar.',
            '- For CTA: wrap in <SectionWrapper background={{ type: "blobs" or "gradflow" ... }}> with contrast text, two CTA buttons.',
            '- For FAQ: Accordion component. For FOOTER: links + brand name + copyright. Import social icons from ../ui/BrandIcons.jsx (Twitter, Github, Linkedin, Discord, etc.).',
            '- For HEADER, NAVBAR, or FOOTER sections: include the brand name in a <span data-app-title>{brandName}</span> and an app logo image <img data-app-logo src="" alt="App logo" class="h-8 w-8 rounded-lg object-contain hidden" /> so the published app branding is injected.',
            '- No Lorem ipsum. Generate real creative content based on the page title.',
            '- Keep the component CONCISE. Maximum ~60 lines of JSX inside the return(). Do NOT add every possible variant or exhaustive lists.',
            '- NEVER generate more than 3 feature cards, 3 testimonials, or 3 pricing tiers. Quality over quantity.',
            '- CRITICAL: When rendering arrays of objects (e.g. features, plans, links, items), ALWAYS access the specific property (e.g. {feature.title}, {item.label}, {plan.name}). NEVER render the object itself as a JSX child (e.g. {feature} or {item}) — this causes a runtime error.',
            '- Output ONLY the JSON object. No extra text before or after.',
        ]);

        $generatedFiles = [];
        $successCount = 0;
        $step = 2;

        // Phase 1: AI-driven template selection — one small call with compact catalog
        $templateAssignments = [];
        $sectionSource = (string) Setting::get('ai_builder_section_source', 'templates');
        if ($sectionSource !== 'ai_free') {
            $templateIds = $this->selectTemplatesForSections($prompt, $title, $sections, $sharedStyles, $model, $jobId);
            if (!empty($templateIds)) {
                // Resolve template IDs to models in a single query
                $resolved = AiBuilderTemplate::active()
                    ->whereIn('template_id', array_values($templateIds))
                    ->get()
                    ->keyBy('template_id');
                foreach ($templateIds as $idx => $tid) {
                    if (isset($resolved[$tid])) {
                        $templateAssignments[$idx] = $resolved[$tid];
                    }
                }
            }
        }

        foreach ($sections as $index => $section) {
            if ($jobId && AiProgressTracker::isCancelled($jobId)) {
                throw new \RuntimeException('Generation cancelled by user.');
            }

            // Normalize the section type (AI blueprints often use descriptive
            // type strings like "landing" or "offerings" instead of catalog names).
            $sectionType = $this->normalizeSectionType($section);
            $sectionName = is_string($section['name'] ?? null) ? trim((string) $section['name']) : '';
            $sectionDesc = is_string($section['description'] ?? null) ? trim((string) $section['description']) : '';

            // If the AI returned an empty name, fall back to the section type
            // (e.g. "testimonials" -> "Testimonials") instead of a generic "SectionN".
            if ($sectionName === '') {
                $sectionName = ucfirst($sectionType);
            }

            // Derive a PascalCase component name from the section name
            $componentName = preg_replace('/[^A-Za-z0-9]/', '', ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $sectionName) ?? $sectionName));
            if ($componentName === '') {
                $componentName = ucfirst($sectionType) ?: 'Section' . ($index + 1);
            }

            // Determine this section's background role under the page-wide budget:
            // hero (decorative), decorative (one CTA/pricing section), dark (one
            // accent section), footer (preserve template), or plain (no background).
            $backgroundRole = 'plain';
            if ($sectionType === 'hero') {
                $backgroundRole = 'hero';
            } elseif ($index === $decorativeSectionIndex) {
                $backgroundRole = 'decorative';
            } elseif ($sectionType === 'footer') {
                $backgroundRole = 'footer';
            } elseif ($index === $darkSectionIndex) {
                $backgroundRole = 'dark';
            }

            if ($jobId) {
                AiProgressTracker::set($jobId, [
                    'status'  => 'generating',
                    'step'    => min($step, $totalSteps - 1),
                    'total'   => $totalSteps,
                    'message' => 'Designing ' . $sectionName . '…',
                    'file'    => 'src/components/sections/' . $componentName . '.jsx',
                    'action'  => 'creating',
                    'description' => $sectionDesc !== '' ? $sectionDesc : null,
                ]);
            }

            $this->streamPlanningSummary(
                $jobId,
                $model,
                'The user asked for a ' . $sectionName . ' section. Section type: ' . $sectionType . '. Description: ' . ($sectionDesc ?: 'No separate description.') . '. Explain the specific layout, content hierarchy, and interaction choices you are about to generate. Keep it concise and observable.',
                'src/components/sections/' . $componentName . '.jsx'
            );

            // Try template-based generation first if templates exist for this category,
            // unless the admin has switched to AI free generation mode.
            if ($sectionSource !== 'ai_free') {
                // Use AI-selected template if available from Phase 1
                $selectedTemplate = $templateAssignments[$index] ?? null;

                $templateResult = $this->generateSectionFromTemplate(
                    $prompt,
                    $title,
                    $sectionName,
                    $sectionType,
                    $sectionDesc,
                    $componentName,
                    $sharedStyles,
                    $model,
                    $jobId,
                    $selectedTemplate,
                    $backgroundRole
                );

                if ($templateResult !== null) {
                    $generatedFiles[] = $templateResult;
                    $this->pushFileWrite($jobId, (string) ($templateResult['path'] ?? ''), (string) ($templateResult['content'] ?? ''));
                    $successCount++;
                    $step++;
                    continue;
                }
            }

            $userPrompt = implode("\n", [
                'Page title: ' . $title,
                'User request: ' . $prompt,
                'Industry: ' . ($sharedStyles['industry'] ?? 'general'),
                'Key features to highlight: ' . json_encode($sharedStyles['features'] ?? []),
                'Section ' . ($index + 1) . ' of ' . count($sections) . ': ' . $sectionName . ' (type: ' . $sectionType . ')',
                'Section description: ' . $sectionDesc,
                'Component name: ' . $componentName,
                'Brand colors (hex reference for context — use brand-* classes in code): ' . json_encode($sharedStyles),
                'Design tone: ' . ($sharedStyles['tone'] ?? 'professional, modern'),
                'Typography: heading=' . ($sharedStyles['heading_font'] ?? 'Inter') . ', body=' . ($sharedStyles['body_font'] ?? 'Inter'),
                'Spacing: section padding=' . ($sharedStyles['padding_y'] ?? 80) . 'px, gap=' . ($sharedStyles['gap'] ?? 24) . 'px, border radius=' . ($sharedStyles['border_radius'] ?? 12) . 'px',
                'Button style: ' . ($sharedStyles['button_style'] ?? 'primary'),
                'Badge variant: ' . ($sharedStyles['badge_variant'] ?? 'subtle') . ' (use this ONE variant consistently across the entire page).',
                'Background role for THIS section (STRICT): ' . $this->backgroundRolePrompt($backgroundRole, $sharedStyles),
                '',
                'Shared design spec (apply to every section exactly):',
                '- BADGE / EYEBROW: import { Badge } from "../ui/badge.jsx" and use <Badge variant="' . ($sharedStyles['badge_variant'] ?? 'subtle') . '">Label</Badge>. Never create badges with raw <span> tags or hardcoded badge classes. Use the same page-level variant for every badge/eyebrow.',
                '- Primary CTAs: import { Button } from "../ui/button.jsx"; and use <Button variant="default">. Do not override colors with bg-* or text-white classes.',
                '- If a custom button is needed, use `bg-brand-primary text-ds-primary-foreground` (never `text-white`).',
                '- Section vertical padding: use py-' . ($sharedStyles['padding_y'] ?? 80) . 'px equivalent. Maintain consistent spacing across all sections.',
                '- Card border radius: use rounded-lg or rounded-2xl consistently based on the design system radius (' . ($sharedStyles['border_radius'] ?? 12) . 'px).',
                '',
                'Generate the ' . $componentName . ' component now.',
                'CRITICAL:',
                '- The component MUST be named exactly ' . $componentName . '.',
                '- The file MUST be saved as src/components/sections/' . $componentName . '.jsx.',
                '- Do NOT return SectionWrapper or any other helper component unless the section name is exactly SectionWrapper.',
            ]);

            $result = $this->router->generateJsonObject(
                $model,
                $systemPrompt,
                $userPrompt,
                4500,
                'Section: ' . $componentName
            );

            $json = null;
            if (is_array($result) && ($result['success'] ?? false) === true) {
                $json = is_array($result['json'] ?? null) ? $result['json'] : null;
            }

            if (!is_array($json)) {
                \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator[section-by-section]: section AI call failed', [
                    'section'  => $sectionName,
                    'model'    => $model,
                    'ai_error' => $result['message'] ?? null,
                ]);
                // Use a minimal placeholder so the page still compiles
                $generatedFiles[] = [
                    'path'    => 'src/components/sections/' . $componentName . '.jsx',
                    'content' => $this->minimalSectionFallback($componentName, $sectionName, $sectionType),
                ];
                $this->pushFileWrite($jobId, 'src/components/sections/' . $componentName . '.jsx', $this->minimalSectionFallback($componentName, $sectionName, $sectionType));
                $step++;
                continue;
            }

            // The AI should return {"path": "...", "content": "..."}
            $fileContent = is_string($json['content'] ?? null) ? trim((string) $json['content']) : '';

            // Always write to the section file we asked for, never the AI's hallucinated path.
            // This prevents files from being swapped (e.g. Hero code written to Header.jsx).
            $filePath = 'src/components/sections/' . $componentName . '.jsx';

            // Detect truncated JSX: content must have a closing brace after the return
            if ($fileContent !== '' && !$this->isJsxComplete($fileContent)) {
                \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator[section-by-section]: section content appears truncated, using fallback', [
                    'section' => $sectionName,
                    'length'  => strlen($fileContent),
                    'tail'    => substr($fileContent, -80),
                ]);
                $fileContent = $this->minimalSectionFallback($componentName, $sectionName, $sectionType);
            }

            if ($fileContent === '') {
                $generatedFiles[] = [
                    'path'    => $filePath,
                    'content' => $this->minimalSectionFallback($componentName, $sectionName, $sectionType),
                ];
                $this->pushFileWrite($jobId, $filePath, $this->minimalSectionFallback($componentName, $sectionName, $sectionType));
                $step++;
                continue;
            }

            $fileContent = $this->normalizeImports($fileContent);
            $fileContent = $this->normalizeImportPaths($filePath, $fileContent);

            // Enforce the background budget on AI-generated JSX: plain sections must
            // not carry a SectionWrapper background (or a dark theme) even if the AI
            // ignored the prompt rule.
            if ($backgroundRole === 'plain') {
                $fileContent = $this->stripSectionWrapperBackground($fileContent);
            }

            $generatedFiles[] = ['path' => $filePath, 'content' => $fileContent];
            $this->pushFileWrite($jobId, $filePath, $fileContent);
            $successCount++;
            $step++;
        }

        if ($successCount === 0) {
            return null;
        }

        // Assemble into a full Vite project using DefaultCodebase
        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status'  => 'generating',
                'step'    => min($step, $totalSteps - 1),
                'total'   => $totalSteps,
                'message' => 'Assembling ' . $title . '…',
                'file'    => 'src/pages/LandingPage.jsx',
                'action'  => 'thinking',
                'description' => 'Combining ' . count($generatedFiles) . ' generated sections into the final React page.',
            ]);
        }

        $this->streamPlanningSummary(
            $jobId,
            $model,
            'The generated sections are ready. Explain how they will be composed into the final landing page and how their visual hierarchy supports the user request. Mention the number of generated sections: ' . count($generatedFiles) . '. Keep it concise and observable.',
            'src/pages/LandingPage.jsx'
        );

        $codebase     = new DefaultCodebase();
        $projectFiles = $codebase->sectionFiles($sharedStyles, $title, $generatedFiles);

        // Pick the first page file for the react_code fallback
        $reactCode = '';
        foreach ($projectFiles as $pf) {
            if (preg_match('#^src/pages/#', $pf['path'] ?? '')) {
                $reactCode = $pf['content'];
                break;
            }
        }

        $previewHtml = $this->buildReactRuntimeSnippet('LandingPage', $reactCode, $sharedStyles);

        return [
            'html'         => $previewHtml,
            'react_code'   => $reactCode,
            'project_files'=> $projectFiles,
        ];
    }

    /**
     * Check that a JSX string looks complete (not truncated mid-generation).
     * A complete component must have: export default, a closing }, and a return ( ... ).
     */
    private function isJsxComplete(string $code): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }
        // Must have export default
        if (!preg_match('/\bexport\s+default\b/m', $code)) {
            return false;
        }
        // Last non-whitespace char should be } or ; (function body closed)
        $lastChar = substr(rtrim($code), -1);
        if (!in_array($lastChar, ['}', ';'], true)) {
            return false;
        }
        // Rough brace balance check — open { must equal close }
        $open  = substr_count($code, '{');
        $close = substr_count($code, '}');
        return abs($open - $close) <= 2; // allow 1-2 off for template literals
    }

    /**
     * Generate a minimal but valid placeholder JSX component for a section
     * that failed or was truncated, so the project still compiles.
     */
    private function minimalSectionFallback(string $componentName, string $sectionName, string $sectionType): string
    {
        $background = match($sectionType) {
            'hero'         => ' background={{ type: "gradflow", config: { color1: "#d21570", color2: "#8e3ff8", color3: "#56ffc3", speed: 0.5, scale: 1.2, type: "aurora", noise: 0.32 } }}',
            'cta'          => ' background={{ type: "blobs", config: { color1: "#d21570", color2: "#8e3ff8" } }}',
            'features'     => ' background={{ type: "grid-fade" }}',
            'pricing'      => ' background={{ type: "dots" }}',
            'testimonials' => ' background={{ type: "mesh", config: { color1: "#d21570", color2: "#8e3ff8" } }}',
            'faq'          => '',
            'footer'       => ' dark={true}',
            default        => '',
        };

        return <<<JSX
import React from 'react';
import SectionWrapper from './SectionWrapper.jsx';

export default function {$componentName}() {
    return (
        <SectionWrapper className="py-20"{$background}>
            <div className="text-center">
                <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4">{$sectionName}</h2>
                <p className="text-lg text-slate-500 max-w-2xl mx-auto">This section is being generated. Regenerate the page for full content.</p>
            </div>
        </SectionWrapper>
    );
}
JSX;
    }

    /**
     * Phase 1: Ask the AI to select the best template for each section from a
     * compact catalog (id, category, name, description, tags only — no JSX or
     * defaults). This keeps the payload tiny even with 500+ templates.
     *
     * Returns a map of [sectionIndex => templateId] or empty array on failure.
     */
    private function selectTemplatesForSections(
        string  $prompt,
        string  $pageTitle,
        array   $sections,
        array   $sharedStyles,
        string  $model,
        ?string $jobId = null
    ): array {
        // Build compact catalog: only id, category, name, description, tags
        $allTemplates = AiBuilderTemplate::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['template_id', 'category', 'name', 'description', 'tags']);

        if ($allTemplates->isEmpty()) {
            return [];
        }

        // Group by category for the catalog
        $catalog = [];
        foreach ($allTemplates as $tpl) {
            $cat = strtolower(trim((string) $tpl->category));
            $catalog[] = [
                'id'          => $tpl->template_id,
                'category'    => $cat,
                'name'        => $tpl->name,
                'description' => mb_substr((string) $tpl->description, 0, 120),
                'tags'        => is_array($tpl->tags) ? array_slice($tpl->tags, 0, 5) : [],
            ];
        }

        // Shuffle the presentation order so the AI does not anchor on the first
        // listing of every category. Selection variety is still guaranteed by
        // applyTemplateRotation() below.
        shuffle($catalog);

        // Build the section list the AI needs to assign templates to
        $sectionList = [];
        foreach ($sections as $i => $section) {
            $sectionList[] = [
                'index'    => $i,
                'type'     => $section['type'] ?? 'content',
                'name'     => $section['name'] ?? '',
                'desc'     => mb_substr((string) ($section['description'] ?? ''), 0, 100),
            ];
        }

        $system = implode("\n", [
            'You are a website and app section architect. Select the best premade template for each section of the page or dashboard.',
            'Sections can be landing-page sections (hero, features, pricing, etc.) or dashboard sections (sidebar, topbar, stat cards, charts, data tables, activity feeds, quick actions).',
            'Output ONLY valid JSON with this exact shape:',
            '  {"assignments": [{"index": number, "template_id": string}]}',
            '',
            'RULES:',
            '- Pick the template that best matches the section type and user intent.',
            '- Each section must get exactly one template.',
            '- Use the template id (not name) in the response.',
            '- Only assign templates from the provided catalog.',
            '- Never assign the same template_id to two different sections on the same page.',
            '- Vary your choices: do not always pick the same template for the same section type. When several templates in a category fit, spread your picks so the page looks hand-assembled and unique.',
            '- The request includes the templates used in recent generations per category. Prefer a template that was NOT recently used so this generation is visibly different from previous ones.',
            '- Output ONLY the JSON object. No markdown, no code fences, no explanation.',
        ]);

        // Recent template usage per category (most-recent first) so the AI steers
        // its preferred picks toward templates that have not just been used.
        $recentHistory = [];
        $historyRaw = Setting::get('ai_builder_template_rotation_history', []);
        if (is_array($historyRaw)) {
            foreach ($historyRaw as $cat => $ids) {
                if (is_array($ids) && $ids !== []) {
                    $recentHistory[$cat] = array_slice($ids, 0, 3);
                }
            }
        }

        $userPrompt = implode("\n", [
            'Page title: ' . $pageTitle,
            'User request: ' . $prompt,
            'Industry: ' . ($sharedStyles['industry'] ?? 'general'),
            'Design tone: ' . ($sharedStyles['tone'] ?? 'professional, modern'),
            '',
            'Sections to assign:',
            json_encode($sectionList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '',
            'Available templates catalog:',
            json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '',
            'Recently used templates per category (avoid re-picking these when possible):',
            json_encode($recentHistory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '',
            'Select the best template for each section now.',
        ]);

        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status'  => 'generating',
                'message' => 'Selecting templates…',
                'file'    => 'docs/plan.md',
                'action'  => 'thinking',
            ]);
        }

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $userPrompt,
            3000,
            'Template Selection Phase'
        );

        $assignments = [];
        if (is_array($result) && ($result['success'] ?? false) === true) {
            $json = is_array($result['json'] ?? null) ? $result['json'] : null;
            if (is_array($json) && is_array($json['assignments'] ?? null)) {
                foreach ($json['assignments'] as $a) {
                    $idx = is_int($a['index'] ?? null) ? $a['index'] : null;
                    $tid = is_string($a['template_id'] ?? null) ? trim($a['template_id']) : null;
                    if ($idx !== null && $tid !== null) {
                        $assignments[$idx] = $tid;
                    }
                }
            }
        }

        if (empty($assignments)) {
            \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator[template-selection]: AI template selection failed or empty', [
                'model'    => $model,
                'ai_error' => $result['message'] ?? null,
            ]);
        }

        // Guarantee mix-matching: rotate templates per category so consecutive
        // generations never reuse the same template for the same section type,
        // and no template appears twice on the same page.
        return $this->applyTemplateRotation($assignments, $sections, $allTemplates);
    }

    /**
     * Enforce template variety across generations.
     *
     * - Every section is mapped to a catalog category; sections the AI missed
     *   are auto-assigned from that category.
     * - A template already used for the same category in a recent generation
     *   (tracked in settings, most-recent first) is skipped, so selections
     *   rotate instead of repeating.
     * - A template already used on the current page is never reused.
     */
    private function applyTemplateRotation(array $assignments, array $sections, \Illuminate\Database\Eloquent\Collection $allTemplates): array
    {
        // Category -> ordered template ids (preserving sort_order/name order)
        $catalogByCategory = [];
        foreach ($allTemplates as $tpl) {
            $cat = strtolower(trim((string) $tpl->category));
            if ($cat === '') {
                continue;
            }
            $catalogByCategory[$cat][] = $tpl->template_id;
        }
        if ($catalogByCategory === []) {
            return $assignments;
        }

        // Recent history: category => [template ids, most-recent first]
        $history = Setting::get('ai_builder_template_rotation_history', []);
        if (!is_array($history)) {
            $history = [];
        }
        foreach ($history as $cat => $ids) {
            if (!is_array($ids)) {
                unset($history[$cat]);
                continue;
            }
            // Drop ids that no longer exist and prune to the window.
            $valid = isset($catalogByCategory[$cat]) ? array_values(array_intersect($ids, $catalogByCategory[$cat])) : [];
            $history[$cat] = array_values(array_unique(array_slice($valid, 0, self::ROTATION_HISTORY_LEN)));
        }

        $final = [];
        $usedThisPage = [];
        $updatedHistory = $history;

        foreach ($sections as $index => $section) {
            $category = $this->catalogCategoryForSection($section, $catalogByCategory);
            if ($category === null) {
                // No templates catalogued for this section type. Never trust an
                // AI pick here: the model sometimes assigns a template from an
                // unrelated category (e.g. a footer template to a contact section),
                // which renders a duplicate footer or a mismatched layout. Leave
                // the section unassigned so the template path falls back to the
                // category-scoped/AI-free generation instead.
                continue;
            }

            $candidates = $catalogByCategory[$category];
            $preferred = isset($assignments[$index]) ? $assignments[$index] : null;
            // Discard an AI pick that is not actually a candidate for this category.
            if ($preferred !== null && !in_array($preferred, $candidates, true)) {
                $preferred = null;
            }

            $chosen = $this->pickRotationCandidate($preferred, $candidates, $history[$category] ?? [], $usedThisPage);
            $final[$index] = $chosen;
            $usedThisPage[] = $chosen;

            // Move the chosen id to the front of this category's history.
            $recent = $history[$category] ?? [];
            $updatedHistory[$category] = array_values(array_unique(array_merge([$chosen], array_diff($recent, [$chosen]))));
        }

        Setting::set('ai_builder_template_rotation_history', $updatedHistory, 'ai_builder', 'json');

        return $final;
    }

    /**
     * Map a blueprint section to a catalog category (template category string),
     * or null when no catalogued templates exist for that section type.
     */
    private function catalogCategoryForSection(array $section, array $catalogByCategory): ?string
    {
        $type = $this->normalizeSectionType($section);
        $remap = [
            'integrations' => 'integration',
            'portfolio' => 'integration',
            'partners' => 'integration',
            'case-studies' => 'integration',
            'showcase' => 'integration',
            'gallery' => 'integration',
            'logos' => 'brands',
            'brands' => 'brands',
            'cta' => 'cta',
        ];
        if (isset($catalogByCategory[$type])) {
            return $type;
        }
        $mapped = $remap[$type] ?? null;
        if ($mapped !== null && isset($catalogByCategory[$mapped])) {
            return $mapped;
        }
        return null;
    }

    /**
     * Pick the template for one section, honoring the AI preference when it is
     * fresh, otherwise rotating to a template not used recently and not used on
     * this page. When every candidate has been used recently, round-robin to the
     * LEAST-recently-used one (oldest in the history) so the category never
     * locks onto its first catalog template generation after generation.
     */
    private function pickRotationCandidate(?string $preferred, array $candidates, array $recent, array $usedThisPage): string
    {
        $fresh = array_values(array_filter($candidates, fn ($c) => !in_array($c, $recent, true)));
        $freshUnused = array_values(array_filter($fresh, fn ($c) => !in_array($c, $usedThisPage, true)));

        if ($preferred !== null && in_array($preferred, $freshUnused, true)) {
            return $preferred;
        }
        if ($freshUnused !== []) {
            return $freshUnused[0];
        }

        // Every candidate has been used recently (history window >= category
        // size). Pick the least-recently-used candidate that is not used on this
        // page, so selections rotate instead of repeating the catalog order.
        $ranked = [];
        foreach ($candidates as $c) {
            if (in_array($c, $usedThisPage, true)) {
                continue;
            }
            $pos = array_search($c, $recent, true);
            $ranked[] = ['id' => $c, 'pos' => ($pos === false ? PHP_INT_MIN : $pos)];
        }
        if ($ranked !== []) {
            usort($ranked, fn ($a, $b) => $b['pos'] <=> $a['pos']);
            return $ranked[0]['id'];
        }

        return $candidates[0];
    }

    /**
     * Map an arbitrary blueprint section (its type + name) to a canonical
     * semantic type. Real AI blueprints often emit descriptive type strings
     * ("topbar", "landing", "offerings") instead of the standard catalog names,
     * so the background budget relies on this normalization.
     */
    private function normalizeSectionType(array $section): string
    {
        $type = strtolower(trim((string) ($section['type'] ?? '')));
        $name = strtolower(trim((string) ($section['name'] ?? '')));

        // Map non-standard but unambiguous type strings to their canonical form.
        $remap = [
            'navbar' => 'header', 'navigation' => 'header', 'topbar' => 'header', 'menu' => 'header',
            'landing' => 'hero', 'welcome' => 'hero', 'banner' => 'hero', 'introduction' => 'hero',
            'bottom' => 'footer', 'site-footer' => 'footer',
            'plans' => 'pricing', 'plan' => 'pricing', 'pricing-plans' => 'pricing',
            'statistics' => 'stats', 'counters' => 'stats', 'metrics' => 'stats',
            'reviews' => 'testimonials', 'quotes' => 'testimonials', 'social-proof' => 'testimonials',
            'benefits' => 'features', 'offerings' => 'features', 'solutions' => 'features',
            'partners' => 'integrations', 'portfolio' => 'integrations', 'case-studies' => 'integrations',
            'showcase' => 'integrations', 'gallery' => 'integrations',
            'questions' => 'faq',
            'team' => 'about', 'about-us' => 'about',
            'contact-us' => 'contact', 'enquiry' => 'contact', 'contact-form' => 'contact', 'get-in-touch' => 'contact',
        ];
        if (isset($remap[$type])) {
            return $remap[$type];
        }

        $known = [
            'hero', 'footer', 'header', 'cta', 'pricing', 'testimonials', 'stats',
            'features', 'why-choose-us', 'faq', 'integrations', 'about', 'contact',
        ];
        if (in_array($type, $known, true)) {
            return $type;
        }

        // Name-based fallbacks for descriptive / custom types. Most specific
        // keywords first so a name like "PricingHeader" still resolves correctly.
        if ($this->nameMatches($name, ['hero'])) {
            return 'hero';
        }
        if ($this->nameMatches($name, ['footer'])) {
            return 'footer';
        }
        if ($this->nameMatches($name, ['header', 'navbar', 'navigation', 'topbar', 'menu'])) {
            return 'header';
        }
        if ($this->nameMatches($name, ['pricing', 'price', 'plans'])) {
            return 'pricing';
        }
        if ($this->nameMatches($name, ['cta', 'call-to-action', 'callout'])) {
            return 'cta';
        }
        if ($this->nameMatches($name, ['testimonials', 'reviews', 'quotes'])) {
            return 'testimonials';
        }
        if ($this->nameMatches($name, ['stats', 'statistics', 'counters', 'metrics'])) {
            return 'stats';
        }
        if ($this->nameMatches($name, ['features', 'benefits', 'services', 'offerings', 'why-choose-us'])) {
            return 'features';
        }
        if ($this->nameMatches($name, ['integrations', 'partners', 'portfolio', 'case-studies', 'showcase', 'gallery', 'work'])) {
            return 'integrations';
        }
        if ($this->nameMatches($name, ['faq', 'questions', 'frequently asked'])) {
            return 'faq';
        }
        if ($this->nameMatches($name, ['about', 'team', 'mission'])) {
            return 'about';
        }
        if ($this->nameMatches($name, ['contact', 'enquiry', 'brief', 'form'])) {
            return 'contact';
        }

        return $type !== '' ? $type : 'content';
    }

    private function nameMatches(string $name, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($name !== '' && str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Choose the single CTA or pricing section (besides the hero) that is allowed
     * the same decorative background rule as the hero section.
     */
    private function pickDecorativeSectionIndex(array $sections): ?int
    {
        foreach ($sections as $index => $section) {
            if (in_array($this->normalizeSectionType($section), ['cta', 'pricing'], true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Choose the single section allowed a dark/black background. CTA and pricing
     * are excluded — they take the decorative (hero-rule) slot instead. Prefers
     * an accent-style section (stats, testimonials, etc.) and falls back to the
     * first remaining non-hero / non-footer / non-navbar section.
     */
    private function pickDarkSectionIndex(array $sections): ?int
    {
        $preferred = ['stats', 'testimonials', 'features', 'why-choose-us', 'faq', 'integrations', 'about'];
        $excluded  = ['hero', 'footer', 'header', 'navbar', 'cta', 'pricing', 'content'];

        foreach ($preferred as $type) {
            foreach ($sections as $index => $section) {
                if ($this->normalizeSectionType($section) === $type) {
                    return $index;
                }
            }
        }

        foreach ($sections as $index => $section) {
            if (!in_array($this->normalizeSectionType($section), $excluded, true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Role-specific background instruction used in the AI-free section generation prompt.
     */
    private function backgroundRolePrompt(string $role, array $sharedStyles): string
    {
        $palette = json_encode([
            'primary'   => $sharedStyles['primary'] ?? null,
            'secondary' => $sharedStyles['secondary'] ?? null,
            'background' => $sharedStyles['background'] ?? null,
        ]);

        return match ($role) {
            'hero' => 'This is the HERO section — it MUST use a decorative background of type gradflow, grid, grid-fade, dots, or mesh (never blobs or image). Wrap the section in <SectionWrapper background={{ type: "...", config: {...} }}> with brand colors (' . $palette . '). Set dark={true} only for dark gradflow colors.',
            'decorative' => 'This is a CTA or pricing section — it may use the SAME decorative background rule as the hero: a background of type gradflow, grid, grid-fade, dots, or mesh (never blobs or image), matching the hero\'s style but adapted to this section. Wrap the section in <SectionWrapper background={{ type: "...", config: {...} }}> with brand colors (' . $palette . ').',
            'dark' => 'This is the DARK accent section — allowed a dark/black background (one of the few sections that may have one). Render it with a dark/black background: <SectionWrapper dark background={{ type: "dark" }}> so it renders dark, OR use a dark gradflow preset with dark colors. All text inside must stay light.',
            'footer' => 'FOOTER section — do NOT add or change any background. Keep the stored template background exactly as it is; do not set a background or dark prop.',
            default => 'PLAIN section — do NOT add any background. Render <SectionWrapper> with no background prop and no dark prop so the section stays on the plain page background.',
        };
    }

    /**
     * Role-specific background instruction used in the template props system prompt.
     */
    private function templateBackgroundRolePrompt(string $role): string
    {
        return match ($role) {
            'hero' => 'This is the HERO section: it MUST have a background. Use one of these types: gradflow, grid, grid-fade, dots, or mesh. Do NOT use blobs or image.',
            'decorative' => 'This is a CTA or pricing section: it may use the SAME decorative background rule as the hero — types gradflow, grid, grid-fade, dots, or mesh only (never blobs or image).',
            'dark' => 'This is the DARK accent section (one of the few sections allowed a background): use a dark/black background — set the "background" prop to {"type":"dark"} and "dark" to true (or a dark gradflow preset).',
            'footer' => 'This is the FOOTER section: do NOT change the background. Keep the template\'s stored default background exactly as-is — do not output a "background" or "dark" prop.',
            default => 'This is a PLAIN section: do NOT add a background. Do not output a "background" or "dark" prop — the section stays on the plain page background.',
        };
    }

    /**
     * Pick a decorative background preset for this generation (rotation-enforced)
     * so hero/CTA sections look different every generation even when the model is
     * deterministic or a fixed frontend theme keeps the palette constant.
     *
     * The same preset is reused for the hero and its matching CTA section on this
     * page so the page stays coherent, but consecutive generations rotate to a
     * different preset (tracked in settings, most-recent first).
     *
     * Returns a copy of $sharedStyles with:
     *   decorative_background          => ['type' => ..., 'config' => ...]
     *   decorative_background_dark     => bool
     *   decorative_background_preset_id => string|null
     */
    private function pickDecorativeBackground(array $sharedStyles): array
    {
        $pool = array_values(array_filter(
            BackgroundPresetStore::active(),
            fn ($p) => in_array(strtolower((string) ($p['category'] ?? '')), [
                'gradflow', 'grid', 'grid-fade', 'dots', 'mesh',
            ], true)
        ));

        if ($pool === []) {
            return $sharedStyles;
        }

        $history = Setting::get('ai_builder_background_rotation_history', []);
        if (!is_array($history)) {
            $history = [];
        }
        $validIds = array_column($pool, 'preset_id');
        $recent = array_values(array_unique(array_filter(
            array_slice($history, 0, self::BACKGROUND_ROTATION_HISTORY_LEN),
            fn ($id) => in_array($id, $validIds, true)
        )));

        // Prefer a preset not used in recent generations; otherwise rotate within the pool.
        $fresh = array_values(array_filter($pool, fn ($p) => !in_array($p['preset_id'] ?? null, $recent, true)));
        $chosen = $fresh !== [] ? $fresh[array_rand($fresh)] : $pool[array_rand($pool)];

        $background = $chosen['config'] ?? null;
        if (!is_array($background) || !isset($background['type'])) {
            return $sharedStyles;
        }

        $dark = $this->backgroundPresetIsDark($background);

        // Record the pick at the front of the rotation history.
        $updated = [];
        foreach ([$chosen['preset_id'] ?? null, ...$recent] as $id) {
            if ($id === null || in_array($id, $updated, true)) {
                continue;
            }
            $updated[] = $id;
            if (count($updated) >= self::BACKGROUND_ROTATION_HISTORY_LEN) {
                break;
            }
        }
        Setting::set('ai_builder_background_rotation_history', $updated, 'ai_builder', 'json');

        $sharedStyles['decorative_background'] = $background;
        $sharedStyles['decorative_background_dark'] = $dark;
        $sharedStyles['decorative_background_preset_id'] = $chosen['preset_id'] ?? null;

        return $sharedStyles;
    }

    /**
     * Whether a decorative background config should render dark text. Light
     * patterns (grid/grid-fade/dots) and palettes that resolve via CSS variables
     * are treated as light; deep hex palettes derive dark from relative luminance.
     */
    private function backgroundPresetIsDark(array $background): bool
    {
        $type = (string) ($background['type'] ?? '');
        if (in_array($type, ['grid', 'grid-fade', 'dots'], true)) {
            return false;
        }

        $config = is_array($background['config'] ?? null) ? $background['config'] : [];
        if ($config === []) {
            return false;
        }

        return $this->isDarkDecorativeConfig($config);
    }

    /**
     * Enforce the page-wide background budget on merged template props.
     * Runs AFTER mergeTemplateProps so template defaults are already resolved.
     */
    private function enforceBackgroundRole(array $templateDefaults, array $props, string $role, array $sharedStyles = []): array
    {
        if ($role === 'footer') {
            // Keep the stored template background untouched — drop any AI override
            // and restore the template's own default background/dark values.
            unset($props['background'], $props['dark']);
            if (array_key_exists('background', $templateDefaults)) {
                $props['background'] = $templateDefaults['background'];
            }
            if (array_key_exists('dark', $templateDefaults)) {
                $props['dark'] = $templateDefaults['dark'];
            }
            return $props;
        }

        if ($role === 'dark') {
            // Solid dark/black accent section.
            $props['background'] = ['type' => 'dark'];
            $props['dark'] = true;
            return $props;
        }

        if ($role === 'hero' || $role === 'decorative') {
            // Dark-only templates (their stored default background is a solid
            // dark section and their JSX is designed for dark, e.g. pricing_dark
            // hardcodes white text) must stay dark even when the page-wide
            // decorative preset is light — otherwise white text renders on a
            // light background.
            $templateBg = $templateDefaults['background'] ?? null;
            $templateDarkOnly = is_array($templateBg)
                && ($templateBg['type'] ?? '') === 'dark'
                && ($templateDefaults['dark'] ?? false) === true;
            if ($templateDarkOnly) {
                $props['background'] = ['type' => 'dark'];
                $props['dark'] = true;
                return $props;
            }

            // This page's rotation-enforced decorative preset — guaranteed to
            // differ from previous generations for hero/CTA backgrounds. Keeps
            // hero and its CTA visually coherent (same preset on the page).
            $preset = $sharedStyles['decorative_background'] ?? null;
            if (is_array($preset) && isset($preset['type'])) {
                $props['background'] = $preset;
                $props['dark'] = (bool) ($sharedStyles['decorative_background_dark'] ?? false);
                return $props;
            }

            $allowed = ['gradflow', 'grid', 'grid-fade', 'dots', 'mesh'];
            $current = $props['background'] ?? null;
            $type = is_array($current) ? ($current['type'] ?? '') : '';
            if (!in_array($type, $allowed, true)) {
                $props['background'] = [
                    'type'   => 'gradflow',
                    'config' => [
                        'color1' => '#d21570',
                        'color2' => '#8e3ff8',
                        'color3' => '#56ffc3',
                        'speed'  => 0.5,
                        'scale'  => 1.2,
                        'type'   => 'aurora',
                        'noise'  => 0.32,
                    ],
                ];
                // The forced preset uses deep, saturated brand colors — keep text white.
                $props['dark'] = true;
            } elseif (in_array($type, ['grid', 'grid-fade', 'dots'], true)) {
                // These patterns are light by default; keep the text dark.
                $props['dark'] = false;
            } elseif (in_array($type, ['gradflow', 'mesh'], true)) {
                // Auto-derive the dark flag from the actual preset colors so text
                // is never dark-on-dark when the AI used a dark palette.
                $props['dark'] = $this->isDarkDecorativeConfig(is_array($current['config'] ?? null) ? $current['config'] : []);
            }
            return $props;
        }

        // Plain: no background, no dark theme — even if the template default had one.
        // EXCEPT dark-only templates whose JSX is hardcoded for a dark background
        // (e.g. pricing_dark). Forcing them light would render white text on a
        // white background, so they are kept dark instead.
        $templateBg = $templateDefaults['background'] ?? null;
        $templateDarkOnly = is_array($templateBg)
            && ($templateBg['type'] ?? '') === 'dark'
            && ($templateDefaults['dark'] ?? false) === true;
        if ($templateDarkOnly) {
            $props['background'] = ['type' => 'dark'];
            $props['dark'] = true;
            return $props;
        }
        $props['background'] = ['type' => 'default'];
        $props['dark'] = false;
        return $props;
    }

    /**
     * Decide whether a decorative (gradflow/mesh) preset should render dark text.
     * Computes the average relative luminance of the preset's color stops; dark
     * palettes get dark=true so the section renders white text.
     */
    private function isDarkDecorativeConfig(array $config): bool
    {
        $colors = [];
        foreach (['color1', 'color2', 'color3', 'color4', 'color5'] as $key) {
            $rgb = $this->normalizeDecorativeColor($config[$key] ?? null);
            if ($rgb !== null) {
                $colors[] = $rgb;
            }
        }
        if ($colors === []) {
            return false;
        }

        $sum = 0.0;
        foreach ($colors as [$r, $g, $b]) {
            // Linearize sRGB for relative luminance (WCAG).
            $linear = fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            $sum += 0.2126 * $linear($r) + 0.7152 * $linear($g) + 0.0722 * $linear($b);
        }

        // Decorative gradients contain vivid/saturated stops; render white text
        // unless the palette is clearly light (e.g. silk-light, patterns).
        return ($sum / count($colors)) < 0.4;
    }

    /**
     * Normalize a decorative gradient stop to [r,g,b] floats in 0-1 range, or
     * null when it is not a concrete color (e.g. a CSS variable reference).
     */
    private function normalizeDecorativeColor($value): ?array
    {
        if (is_string($value) && preg_match('/^#?([0-9a-fA-F]{6})$/i', trim($value), $m)) {
            $hex = $m[1];

            return [
                hexdec(substr($hex, 0, 2)) / 255,
                hexdec(substr($hex, 2, 2)) / 255,
                hexdec(substr($hex, 4, 2)) / 255,
            ];
        }

        if (is_array($value)) {
            $r = $value['r'] ?? null;
            $g = $value['g'] ?? null;
            $b = $value['b'] ?? null;
            if (is_numeric($r) && is_numeric($g) && is_numeric($b)) {
                return [(float) $r / 255, (float) $g / 255, (float) $b / 255];
            }
        }

        return null;
    }

    /**
     * Remove a SectionWrapper `background` attribute from AI-generated JSX so a
     * "plain" section cannot render a background even when the AI ignored the rule.
     */
    private function stripSectionWrapperBackground(string $jsx): string
    {
        return preg_replace_callback('/<SectionWrapper\b[^>]*>/s', function ($matches) {
            $tag = $matches[0];
            $pos = strpos($tag, 'background=');
            if ($pos === false) {
                return $tag;
            }

            $start = $pos + strlen('background=');
            while ($start < strlen($tag) && ctype_space($tag[$start])) {
                $start++;
            }
            if (substr($tag, $start, 2) !== '{{') {
                return $tag;
            }

            $depth = 0;
            $end = $start;
            for (; $end < strlen($tag); $end++) {
                if ($tag[$end] === '{') {
                    $depth++;
                } elseif ($tag[$end] === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $end++;
                        break;
                    }
                }
            }

            $tag = substr($tag, 0, $pos) . substr($tag, $end);

            // Also drop an explicit dark={true} so the section stays plain/light.
            $tag = preg_replace('/\s+dark=\{true\}/', '', $tag);
            $tag = preg_replace('/\s{2,}/', ' ', $tag);

            return $tag;
        }, $jsx);
    }

    /**
     * Try to generate a section component from a premade template.
     * Returns a file array ['path' => ..., 'content' => ...] on success, null otherwise.
     * If $selectedTemplate is provided, uses it directly; otherwise falls back to random.
     */
    private function generateSectionFromTemplate(
        string  $prompt,
        string  $pageTitle,
        string  $sectionName,
        string  $sectionType,
        string  $sectionDesc,
        string  $componentName,
        array   $sharedStyles,
        string  $model,
        ?string $jobId = null,
        ?AiBuilderTemplate $selectedTemplate = null,
        string  $backgroundRole = 'plain'
    ): ?array {
        if ($selectedTemplate !== null) {
            $template = $selectedTemplate;
        } else {
            $templates = AiBuilderTemplate::active()
                ->forCategory($sectionType)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            if ($templates->isEmpty()) {
                return null;
            }

            $template = $templates->random();
        }

        $templateDefaults = $template->defaults ?? [];
        $defaultsJson = json_encode($templateDefaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $fieldsJson = json_encode($template->fields ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $system = implode("\n", [
            'You are a website and app section designer. Generate content props for the given premade template.',
            'Output ONLY valid JSON with this exact shape: {"props": object}.',
            '',
            'RULES:',
            '- Generate realistic, creative content for the props. Do NOT use Lorem ipsum.',
            '- Use the page title and user request to infer the brand voice and topic.',
            '- Only return props that exist in the template fields/defaults. Do not invent new props.',
            '- For landing sections keep text concise and suitable for a premium SaaS page.',
            '- For DASHBOARD sections (sidebar, topbar, stat cards, charts, data tables, activity feeds, quick actions) generate realistic business data: KPI values and deltas, chart series/points, table columns and rows, recent activity items, and menu/navigation labels matching the app or industry from the request.',
            '- If the template has array props (features, stats, testimonials, rows, items, etc.), generate 2-4 realistic items.',
            '- For image fields, use always-valid placeholder URLs: https://picsum.photos/seed/<slug>/800/600 (pick a slug relevant to the image). Do NOT use images.unsplash.com or source.unsplash.com URLs — they frequently break.',
            $this->brandLogoPromptLine(),
            '- Do NOT generate SVG strings for icon fields. Leave icon fields as-is from the template defaults — they will be preserved automatically.',
            'BACKGROUNDS:',
            '  If the template has a "background" prop, output the full background object { type, config }, not just a preset name string.',
            '  Background preset catalog (copy a config when you need one): ' . BackgroundPresetStore::catalogForPrompt(),
            '  This page\'s decorative background (the HERO and CTA MUST use this exact preset): ' . json_encode($sharedStyles['decorative_background'] ?? null, JSON_UNESCAPED_SLASHES),
            '  ' . $this->templateBackgroundRolePrompt($backgroundRole),
            '  Set "dark" to true ONLY when the chosen background is dark.',
            '- Output ONLY the JSON object. No markdown, no code fences, no explanation.',
        ]);

        $userPrompt = implode("\n", [
            'Page title: ' . $pageTitle,
            'User request: ' . $prompt,
            'Industry: ' . ($sharedStyles['industry'] ?? 'general'),
            'Key features to highlight: ' . json_encode($sharedStyles['features'] ?? []),
            'Section: ' . $sectionName . ' (type: ' . $sectionType . ')',
            'Section description: ' . ($sectionDesc !== '' ? $sectionDesc : 'No extra description.'),
            'Brand colors (use design-system classes in the eventual page): ' . json_encode($sharedStyles),
            'Design tone: ' . ($sharedStyles['tone'] ?? 'professional, modern'),
            'Typography: heading=' . ($sharedStyles['heading_font'] ?? 'Inter') . ', body=' . ($sharedStyles['body_font'] ?? 'Inter'),
            'Spacing: padding=' . ($sharedStyles['padding_y'] ?? 80) . 'px, gap=' . ($sharedStyles['gap'] ?? 24) . 'px, radius=' . ($sharedStyles['border_radius'] ?? 12) . 'px',
            '',
            'Template being used: ' . $template->name . ' (id: ' . $template->template_id . ')',
            'Template description: ' . $template->description,
            '',
            'Template fields:',
            $fieldsJson,
            '',
            'Template defaults (use as reference for structure):',
            $defaultsJson,
            '',
            'Generate the props JSON for this template now.',
        ]);

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $userPrompt,
            2500,
            'Template Section: ' . $componentName
        );

        $aiProps = [];
        if (is_array($result) && ($result['success'] ?? false) === true) {
            $json = is_array($result['json'] ?? null) ? $result['json'] : null;
            if (is_array($json)) {
                $aiProps = is_array($json['props'] ?? null) ? $json['props'] : [];
            }
        }

        if (empty($aiProps)) {
            \Illuminate\Support\Facades\Log::warning('PageHtmlGenerator[template-section]: AI props generation failed or empty, using template defaults', [
                'section'  => $sectionName,
                'model'    => $model,
                'ai_error' => $result['message'] ?? null,
            ]);
        }

        $templateDefaults = $template->defaults ?? [];
        $props = $this->mergeTemplateProps($templateDefaults, $aiProps);
        $props['badgeVariant'] = $this->normalizeBadgeVariant($sharedStyles['badge_variant'] ?? null);

        // Enforce the page-wide background budget on template props:
        //   hero   -> force a decorative type (gradflow/grid/grid-fade/dots/mesh)
        //   dark   -> force a solid dark/black section
        //   footer -> keep the stored template default, never AI-changed
        //   plain  -> no background at all
        $props = $this->enforceBackgroundRole($templateDefaults, $props, $backgroundRole, $sharedStyles);

        // Preserve SVG icon strings from template defaults — AI often corrupts
        // the escaping of SVG strings in JSON, producing broken JSX.
        $props = $this->preserveTemplateSvgIcons($templateDefaults, $props, $aiProps);

        $content = $this->renderTemplateJsx($template, $props, $componentName);

        $filePath = 'src/components/sections/' . $componentName . '.jsx';
        $content = $this->normalizeImports($content);
        $content = $this->normalizeImportPaths($filePath, $content);

        if ($jobId) {
            AiProgressTracker::set($jobId, [
                'status'  => 'generating',
                'message' => 'Designed ' . $sectionName . '…',
                'file'    => $filePath,
                'action'  => 'creating',
            ]);
        }

        return ['path' => $filePath, 'content' => $content];
    }

    private function mergeTemplateProps(array $defaults, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && is_array($defaults[$key] ?? null)) {
                $defaultIsList = array_keys($defaults[$key]) === range(0, count($defaults[$key]) - 1);
                $valueIsList = array_keys($value) === range(0, count($value) - 1);

                if ($defaultIsList && $valueIsList) {
                    // If the AI returned a list of a different length than the
                    // template's stored defaults, replace the whole list instead
                    // of merging item by item. Item-by-item merge would otherwise
                    // leak stale default entries past the end of the AI's list
                    // (e.g. leftover SaaS testimonials in a store landing page).
                    if (count($value) !== count($defaults[$key])) {
                        $defaults[$key] = array_values($value);
                        continue;
                    }
                    foreach ($value as $index => $item) {
                        if (is_array($item) && is_array($defaults[$key][$index] ?? null)) {
                            $defaults[$key][$index] = $this->mergeTemplateProps($defaults[$key][$index], $item);
                        } else {
                            $defaults[$key][$index] = $item;
                        }
                    }
                    continue;
                }

                $defaults[$key] = $this->mergeTemplateProps($defaults[$key], $value);
                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }

    /**
     * Recursively restore SVG icon strings from template defaults.
     * AI often corrupts SVG string escaping in JSON output, so we override
     * any AI-generated SVG strings with the original template defaults.
     */
    private function preserveTemplateSvgIcons(array $defaults, array $props, array $aiProps): array
    {
        foreach ($defaults as $key => $defaultValue) {
            if (is_array($defaultValue) && is_array($props[$key] ?? null)) {
                $props[$key] = $this->preserveTemplateSvgIcons($defaultValue, $props[$key], $aiProps[$key] ?? []);
                continue;
            }

            if (is_string($defaultValue) && str_contains($defaultValue, '<svg')) {
                // Always use the template's SVG string — AI versions are unreliable
                $props[$key] = $defaultValue;
            }
        }

        return $props;
    }

    /**
     * Backfill any props that a template's own function signature declares but
     * that are missing from the merged props. A template whose stored defaults
     * are out of sync with its component source would otherwise render
     * undefined-variable references (e.g. "eyebrow is not defined").
     */
    private function mergeSignatureProps(string $source, array $props): array
    {
        foreach ($this->extractSignatureParams($source) as $name => $default) {
            if (array_key_exists($name, $props)) {
                continue;
            }

            $props[$name] = $default !== '' ? $this->parseJsLiteral($default) : null;
        }

        return $props;
    }

    /**
     * Extract the destructured parameter names (with their JS default values)
     * from a template's default-export function or arrow-function signature.
     *
     * Returns [name => defaultJs] where defaultJs is '' when no default given.
     */
    private function extractSignatureParams(string $source): array
    {
        $block = null;
        if (preg_match('/export\s+default\s+function\s+\w+\s*\(\s*\{((?:[^{}]|\{[^{}]*\})*)\}\s*\)\s*\{/s', $source, $m)) {
            $block = $m[1];
        } elseif (preg_match('/export\s+default\s*\(\s*\{((?:[^{}]|\{[^{}]*\})*)\}\s*\)\s*=>/s', $source, $m)) {
            $block = $m[1];
        }

        if ($block === null) {
            return [];
        }

        $params = [];
        foreach ($this->splitTopLevel($block, ',') as $part) {
            if (preg_match('/^\s*([A-Za-z_$][\w$]*)\s*(?:=\s*(.+))?$/s', $part, $pm)) {
                $params[$pm[1]] = trim($pm[2] ?? '');
            }
        }

        return $params;
    }

    /**
     * Parse a JS literal (string, number, boolean, null, array, plain object)
     * into its PHP equivalent. Falls back to null for anything unparseable so a
     * template default can never break the rendered component.
     */
    private function parseJsLiteral(string $value)
    {
        $value = trim($value);
        if ($value === '' || $value === 'null') {
            return null;
        }
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        if (preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        if (preg_match('/^([\'"])(.*)\1$/s', $value, $m)) {
            return $m[2];
        }
        if ($value === '[]') {
            return [];
        }
        if (preg_match('/^\[(.*)\]$/s', $value, $m)) {
            $inner = trim($m[1]);
            if ($inner === '') {
                return [];
            }
            return array_map(fn ($item) => $this->parseJsLiteral($item), $this->splitTopLevel($inner, ','));
        }
        if (preg_match('/^\{(.*)\}$/s', $value, $m)) {
            $inner = $m[1];
            $inner = preg_replace('/([A-Za-z_$][\w$]*)\s*:/', '"$1":', $inner);
            $inner = str_replace("'", '"', $inner);
            $decoded = json_decode('{' . $inner . '}', true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Split a string on a delimiter while ignoring delimiters nested inside
     * {}, [], (), and quoted strings.
     */
    private function splitTopLevel(string $str, string $delimiter): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $quote = null;
        $length = strlen($str);

        for ($i = 0; $i < $length; $i++) {
            $char = $str[$i];
            if ($quote !== null) {
                $current .= $char;
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($char === "'" || $char === '"') {
                $quote = $char;
                $current .= $char;
                continue;
            }
            if ($char === '{' || $char === '[' || $char === '(') {
                $depth++;
            } elseif ($char === '}' || $char === ']' || $char === ')') {
                $depth--;
            }
            if ($char === $delimiter && $depth === 0) {
                $parts[] = $current;
                $current = '';
                continue;
            }
            $current .= $char;
        }

        if (trim($current) !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * Render a stored template source into a JSX component file with the given props and export name.
     */
    private function renderTemplateJsx(AiBuilderTemplate $template, array $props, string $componentName): string
    {
        $source = (string) $template->component_source;

        // Sanitize broken template JSX: fix floating key={} attributes and missing
        // opening tags that exist in some stored templates.
        $source = $this->sanitizeTemplateJsx($source);
        $source = $this->normalizeTemplateBadgeMarkup($source);

        // Replace component name placeholder
        $source = str_replace(['{{ComponentName}}', '[ComponentName]'], $componentName, $source);

        // Normalize props: ensure arrays are arrays, not objects.
        if (!array_key_exists('badgeVariant', $props)) {
            $props['badgeVariant'] = 'subtle';
        }
        $props = $this->normalizeTemplateProps($props);

        // Backfill props declared by the template's own function signature. Some
        // templates carry defaults/fields that are out of sync with their source
        // (e.g. a features template that still uses an about-us layout), which would
        // otherwise leave referenced variables undefined and crash the page.
        $props = $this->mergeSignatureProps($source, $props);

        // Generate default props object string and destructuring pattern.
        $defaultsString = $this->encodeTemplateProps($props);
        $destructuring = $this->buildPropDestructuring(array_keys($props));
        $destructuring = $destructuring !== '' ? 'const { ' . $destructuring . ' } = { ...defaults, ...props };' : '';

        // Escape $ signs in the replacement string so preg_replace does not
        // interpret them as backreferences ($0 = entire match, $1-$99 = groups).
        $escapedDefaults = str_replace('$', '\$', $defaultsString);
        $escapedDestructuring = str_replace('$', '\$', $destructuring);

        // Rewrite any default function export signature to `function ComponentName(props)` and inject defaults.
        $source = preg_replace(
            '/export\s+default\s+function\s+\w+\s*\([^)]*\)\s*\{/',
            'export default function ' . $componentName . "(props) {\n    const defaults = " . $escapedDefaults . ";\n    " . $escapedDestructuring,
            $source,
            1
        );

        // Handle arrow-function default exports: `export default (props) => {` or `const X = (props) => { ... }; export default X;`
        $source = preg_replace(
            '/export\s+default\s*\(\s*props\s*\)\s*=>\s*\{/',
            'export default function ' . $componentName . "(props) {\n    const defaults = " . $escapedDefaults . ";\n    " . $escapedDestructuring,
            $source,
            1
        );

        // Remove any existing `const { ... } = props;` block to avoid duplicate declarations
        // when the template source already destructures props.
        $source = preg_replace('/\bconst\s*(\{(?:[^{}]|(?1))*\})\s*=\s*props\s*;\s*/s', '', $source, 1);

        if (!str_contains($source, 'import React')) {
            $source = "import React from 'react';\n\n" . ltrim($source);
        }

        return $source;
    }

    /**
     * Previously attempted to fix broken JSX in stored templates.
     * DISABLED — this was the root cause of template corruption, incorrectly
     * injecting <a>, <div>, <button> tags into valid multiline JSX attributes.
     * Templates in DB are already correct and should not be modified at render time.
     */
    private function sanitizeTemplateJsx(string $source): string
    {
        return $source;
    }

    private function normalizeTemplateBadgeMarkup(string $source): string
    {
        $source = preg_replace(
            '/<span className="inline-flex items-center rounded-full border border-ds-border bg-ds-card px-3 py-1 text-xs font-medium text-ds-primary mb-6">\s*\{subheading\}\s*<\/span>/s',
            '<Badge variant={badgeVariant} className="mb-6">{subheading}</Badge>',
            $source
        );
        $source = preg_replace(
            '/\{subheading && <p className="text-sm font-semibold uppercase tracking-wider text-ds-primary mb-4">\{subheading\}<\/p>\}/',
            '{subheading && <Badge variant={badgeVariant} className="mb-4">{subheading}</Badge>}',
            $source
        );
        $source = preg_replace(
            '/\{badgeText\s*&&\s*\(\s*<span\s+className="badge w-fit">\s*\{badgeText\}\s*<\/span>\s*\)\s*\}/s',
            '{badgeText && <Badge variant={badgeVariant} className="w-fit">{badgeText}</Badge>}',
            $source
        );
        $source = str_replace(
            '<span className="inline-block mb-4 rounded-full bg-ds-primary px-3 py-1 text-xs font-semibold text-white">Popular</span>',
            '<Badge variant={badgeVariant} className="mb-4">Popular</Badge>',
            $source
        );

        if (str_contains($source, '<Badge') && !preg_match('/import\s+\{\s*Badge\s*\}\s+from/', $source)) {
            $source = "import { Badge } from '../ui/badge.jsx';\n" . ltrim($source);
        }

        return $source;
    }

    /**
     * Recursively normalize template props so arrays stay arrays.
     */
    private function normalizeTemplateProps(array $props): array
    {
        foreach ($props as $key => $value) {
            if (is_array($value)) {
                if (array_keys($value) === range(0, count($value) - 1)) {
                    $props[$key] = array_values(array_map(fn ($item) => is_array($item) ? $this->normalizeTemplateProps($item) : $item, $value));
                } else {
                    $props[$key] = $this->normalizeTemplateProps($value);
                }
            }
        }

        return $props;
    }

    /**
     * Encode props as a JS object literal string for injection into JSX source.
     */
    private function encodeTemplateProps(array $props): string
    {
        // JSON is a subset of JS — double-quoted strings are valid in JS object literals.
        // Using json_encode directly avoids the apostrophe-breaking bug that str_replace('"', "'", ...)
        // caused when prop values contain single quotes (e.g. "We're on a mission").
        return json_encode($props, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Build a destructuring pattern from prop keys.
     */
    private function buildPropDestructuring(array $keys): string
    {
        $keys = array_filter($keys, fn ($k) => is_string($k) && $k !== '');
        $keys = array_map(fn ($k) => preg_replace('/[^a-zA-Z0-9_]/', '', $k), $keys);
        $keys = array_filter($keys);
        return implode(', ', array_unique($keys));
    }

    private function generateReactPage(string $prompt, array $blueprint, array $sharedStyles, string $model, ?string $screenshotUrl = null, ?string $jobId = null): ?array
    {
        $system = implode("\n", [
            'You are a senior React/Vite app builder. You can create marketing landing pages, small web apps, dashboards, multi-page apps, or tools based on the user\'s request.',
            'Generate a complete React project as MULTIPLE files (artifacts) for a modern Vite project.',
            'Output ONLY valid JSON with this exact shape: {"files": [{"path": string, "content": string}]}.',
            '',
            'MODE DETECTION — look at the user request and choose the correct mode:',
            '  - APP/DASHBOARD/AUTH MODE: if the user asks for an app, dashboard, admin panel, auth, login, signup, users, tasks, notes, todos, contacts, data, table, multi-page, or any tool. This mode takes precedence.',
            '  - LANDING PAGE MODE: if the user explicitly asks for a marketing/landing page, website, homepage, or single-page site.',
            '  - If the user mentions app, dashboard, login, signup, tasks, users, or data, you MUST use APP mode. Do NOT generate a landing page (Hero, Features, Pricing, Testimonials, CTA, Footer) for an app request.',
            '',
            'For a MARKETING/LANDING page, use this structure:',
            '  src/pages/LandingPage.jsx          — Main page component (exports default). Import sections from ../components/sections/.',
            '  src/components/sections/Hero.jsx    — Hero section component.',
            '  src/components/sections/Features.jsx — Features grid section.',
            '  src/components/sections/Pricing.jsx — Pricing cards section (if applicable).',
            '  src/components/sections/Testimonials.jsx — Testimonials section (if applicable).',
            '  src/components/sections/CTA.jsx   — Call-to-action section.',
            '  src/components/sections/Footer.jsx — Footer section.',
            '  src/components/layout/Navbar.jsx  — Optional: sticky navbar (can use the existing one).',
            '',
            'APP/DASHBOARD/AUTH MODE — use this structure for the user request:',
            '  - Create `src/App.jsx` with a `HashRouter` and routes for every page (/, /login, /signup, /dashboard, /tasks).',
            '  - Create `src/components/layout/AppLayout.jsx` with a Navbar and/or Sidebar for shared navigation. Use `react-router-dom` `<Link>` and `useNavigate`. Do NOT use `<a>` or window.location for internal navigation.',
            '  - Create separate page files in `src/pages/` named for their purpose (Home.jsx, Login.jsx, Signup.jsx, Dashboard.jsx, Tasks.jsx). Do NOT create a single `LandingPage.jsx` file in app mode.',
            '  - If auth is requested, create `src/pages/Login.jsx` and `src/pages/Signup.jsx`, plus a protected route wrapper that redirects to /login when the user is not signed in. Use Supabase Auth (`signUp`, `signInWithPassword`, `signOut`, `onAuthStateChange`).',
            '  - For data persistence, use Supabase (`@supabase/supabase-js`) and read credentials from `window.APP_SUPABASE_URL` and `window.APP_SUPABASE_PUBLISHABLE_KEY`. If the credentials are missing, fall back to local React state.',
            '  - Generate 5-10 files. Do NOT put the whole app in one file. Do NOT create Hero, Features, Pricing, Testimonials, CTA, or Footer sections for app mode.',
            '  - The app should look like a dashboard or tool, not a marketing page.',
            '',
            'PREDEFINED COMPONENTS (already provided by the Vite sandbox — import them, do not recreate them):',
            '  From a file in `src/components/sections/`, use these exact relative paths:',
            '  `import { Button } from "../ui/button.jsx";`',
            '  `import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../ui/card.jsx";`',
            '  `import { Badge } from "../ui/badge.jsx";`',
            '  `import { Input, Label, Textarea, Select, Checkbox } from "../ui/*.jsx";`',
            '  `import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from "../ui/accordion.jsx";`',
            '  `import { Tabs, TabsList, TabsTrigger, TabsContent } from "../ui/tabs.jsx";`',
            '  `import { Dialog, DialogHeader, DialogTitle, DialogDescription } from "../ui/dialog.jsx";`',
            '  `import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from "../ui/dropdown.jsx";`',
            '  `import { Separator } from "../ui/separator.jsx";`',
            '  `import { Avatar, AvatarImage, AvatarFallback } from "../ui/avatar.jsx";`',
            '  `import { Tooltip, TooltipTrigger, TooltipContent } from "../ui/tooltip.jsx";`',
            '  `import { Progress } from "../ui/progress.jsx";`',
            '  `import { Popover, PopoverTrigger, PopoverContent } from "../ui/popover.jsx";`',
            '  `import { Toggle } from "../ui/toggle.jsx";`',
            '  `import { Skeleton } from "../ui/skeleton.jsx";`',
            '  `import { ScrollArea } from "../ui/scroll-area.jsx";`',
            '  `import { Sheet, SheetContent, SheetHeader, SheetTitle } from "../ui/sheet.jsx";`',
            '  `import Navbar from "../layout/Navbar.jsx";`',
            '  `import Footer from "../layout/Footer.jsx";`',
            '  `import SectionWrapper from "./SectionWrapper.jsx";`',
            '  `import { cn } from "../../lib/utils.js";`',
            '  `import { useIsMobile } from "../../hooks/use-mobile.js";`',
            '  From a file in `src/pages/`, use: `import { Button } from "../components/ui/button.jsx";` and `import Hero from "../components/sections/Hero.jsx";`.',
            '  For routing use: `import { HashRouter, Routes, Route, Link, useNavigate } from "react-router-dom";`. For app layouts, wrap routes in HashRouter.',
            '  For Supabase use: `import { createClient } from "@supabase/supabase-js";`.',
            '',
            'DESIGN SYSTEM (use these tokens instead of hardcoding colors):',
            '  Colors: `bg-ds-primary`, `text-ds-primary-foreground`, `bg-ds-secondary`, `text-ds-muted-foreground`, `border-ds-border`, `bg-ds-muted`, `bg-ds-card`, `bg-ds-background`, `text-ds-foreground`.',
            '  Brand: `bg-brand-primary`, `text-brand-primary`, `bg-brand-secondary`, `bg-brand-bg`, `text-brand-text`.',
            '  Radius: `rounded-ds` (use rounded-lg, rounded-xl, rounded-2xl for specific needs).',
            '  Font: Inter (system-ui fallback). Use `font-sans` for body, `font-heading` for headings.',
            '',
            'SECTION BACKGROUNDS:',
            '  Import `SectionWrapper` in every section file and wrap each landing-page section with it.',
            '  Use `<SectionWrapper id="..." background={...} className="..." dark={...}>...</SectionWrapper>`.',
            '  Available `background.type`: "grid" | "grid-fade" | "dots" | "mesh" | "blobs" | "image" | "gradflow".',
            '  Active preset catalog: ' . BackgroundPresetStore::catalogForPrompt(),
            '  Selection rules:',
            '    - Hero and CTA sections MUST use a background ~90% of the time.',
            '    - Features / Pricing / About / Testimonials / other sections MAY use a background when it improves visual hierarchy.',
            '    - Use backgrounds on at most 3 sections per page to avoid visual overload.',
            '    - For dark gradflow presets or dark image overlays, set `dark={true}` on SectionWrapper so the text stays white.',
            '',
            'CODE RULES:',
            '- No markdown, no code fences.',
            '- Each file MUST have proper ES module import/export syntax.',
            '- Use `import React, { useState } from "react";` when using hooks.',
            '- Components MUST use `export default ComponentName;`.',
            '- Use Tailwind CSS utility classes for ALL styling.',
            '- Use regular HTML elements and inline SVGs.',
            '- BADGE / EYEBROW RULE: import Badge from the provided UI component (`../ui/badge.jsx` from sections, `../components/ui/badge.jsx` from pages). Use only the page-level variant selected in design_requirements.badge_variant: solid, outline, subtle, or plain. Never create badges with raw span tags or hardcoded badge classes.',
            '- You may import lucide-react icons from "lucide-react".',
            '- MANDATORY: ALWAYS output multiple files. A landing page MUST be split into at least 5 files: LandingPage.jsx, Hero.jsx, Features.jsx, plus Pricing/Testimonials/CTA/Footer as needed.',
            '- NEVER return a single file. If the page is simple, still create separate section components.',
            '- NEVER use social-media brand icons (e.g. Twitter, Facebook, Instagram, LinkedIn/Linkedin, Youtube/YouTube, Github/GitHub, Tiktok/TikTok, Snapchat, Pinterest, Reddit, Whatsapp, Telegram, Discord, Slack, Twitch, Spotify) from lucide-react — these do not exist in the library. A BrandIcons.jsx component is available at ../components/ui/BrandIcons.jsx with predefined inline SVGs. Import brand icons from there: `import { Twitter, Github } from "../components/ui/BrandIcons.jsx";`. Use them like any icon: <Twitter className="h-5 w-5" />. NEVER reference brand icon names as JS variables without importing them.',
            '- You may import the cn utility: `import { cn } from "../../lib/utils.js";`.',
            '- Do not use fetch, XMLHttpRequest, WebSocket, localStorage, sessionStorage, indexedDB, cookies, eval, new Function, or dangerouslySetInnerHTML, EXCEPT when using the Supabase client for persistence.',
            '- Do not use backslashes before JSX attributes (e.g. type="text"\ disabled). Write clean JSX: type="text" disabled.',
            '- Do not duplicate JSX attributes on the same element (e.g. className="a" className="b"). Merge them: className="a b".',
            '- Do not access window or document except inside useEffect.',
            '- Root wrapper in the page component should include min-h-screen, bg-brand-bg, and text-brand-text.',
            '- Use the provided brand colors through classes like bg-brand-primary, text-brand-primary, bg-brand-secondary, bg-brand-bg, and text-brand-text.',
            '- Prefer semantic sections: header, section, footer, nav, main.',
            '- In the Navbar, Footer, and AppLayout, include the brand name in a <span data-app-title>{brandName}</span> and an app logo image <img data-app-logo src="" alt="App logo" class="h-8 w-8 rounded-lg object-contain hidden" /> so the published app branding is injected.',
            '- Generate 4-8 files depending on the page complexity. Do NOT put everything in one file.',
            '- The page component file should import and compose the section components.',
            '- ONLY output the "files" array. No extra keys.',
            '',
            'DATA PERSISTENCE & AUTH — if the user requests data storage (task list, notes, todos, contacts, users, etc.) or auth (login, signup, protected pages), use Supabase:',
            '  - The packages `@supabase/supabase-js` and `react-router-dom` are already installed.',
            '  - Import: `import { createClient } from "@supabase/supabase-js";`',
            '  - Inside a `useEffect`, read the credentials from the browser globals: `const url = window.APP_SUPABASE_URL; const key = window.APP_SUPABASE_PUBLISHABLE_KEY;`.',
            '  - If both are present, create a client: `const supabase = createClient(url, key);` and use it for CRUD operations on a table (e.g., `todos`).',
            '  - For auth, use `supabase.auth.signUp({ email, password })`, `supabase.auth.signInWithPassword({ email, password })`, and `supabase.auth.signOut()`. Listen to auth state changes with `supabase.auth.onAuthStateChange`.',
            '  - For protected routes, create a wrapper component that checks the auth session and redirects to /login if the user is not signed in.',
            '  - If the globals are NOT present, fall back to local React state only. Do NOT try to contact any other backend.',
            '  - Keep the UI fully functional in both cases (Supabase and local-state fallback).',
            '',
            'MODERN DESIGN REQUIREMENTS — the output must look like a 2025 premium SaaS/agency site, NOT a 2020 tutorial:',
            '',
            'HERO (Hero.jsx):',
            '- Use a bold, large headline (text-5xl md:text-6xl lg:text-7xl font-bold tracking-tight).',
            '- Add a small pill/badge above the headline (e.g. "New • Feature X" or a category tag) using rounded-full border px-3 py-1 text-xs font-medium.',
            '- Use a gradient or bg-clip-text text-transparent headline for the key word(s).',
            '- Include a subheadline (text-lg md:text-xl text-slate-600 max-w-2xl).',
            '- Two CTAs: a primary (bg-brand-primary text-white rounded-xl px-6 py-3 font-semibold shadow-lg shadow-brand-primary/20 hover:shadow-xl transition) and a secondary ghost/outline button.',
            '- Add a subtle background treatment: a radial gradient blob, a dotted grid, or a noise texture — NOT a crude hand-drawn SVG illustration. Use inline style for blobs: style={{"background":"radial-gradient(circle at 50% 0%, rgba(37,99,235,0.15), transparent 50%)"}}.',
            '- For the hero visual, use a clean product mockup: a rounded-2xl border border-slate-200 shadow-2xl card containing a fake dashboard UI (sidebar + main area with stat cards), NOT a smiley face or abstract doodle.',
            '',
            'FEATURES (Features.jsx):',
            '- Use a BENTO GRID layout (asymmetric grid with mixed card sizes) — NOT the old pattern of icon-in-circle on top + title + description stacked vertically.',
            '- Cards: rounded-2xl border border-slate-200 p-6, with a small lucide icon in the top-right (opacity-40), large bold title, short description.',
            '- Vary card sizes with col-span-2 or row-span-2 for highlighted features.',
            '- Add hover:-translate-y-1 hover:shadow-xl transition-all duration-300 to every card.',
            '',
            'PRICING (Pricing.jsx):',
            '- Three-tier pricing cards with the middle one highlighted (ring-2 ring-brand-primary scale-105).',
            '- Each card: rounded-2xl border p-8, tier name, price (text-4xl font-bold), feature list with Check icons, CTA button.',
            '',
            'TESTIMONIALS (Testimonials.jsx):',
            '- Clean cards with quote, author name, role, and a small avatar circle (use a colored circle with initials, NOT a crude SVG).',
            '- Add star rating using Star icons (fill-current text-amber-400).',
            '',
            'CTA (CTA.jsx):',
            '- A bold full-width band with bg-brand-primary text-white, large centered headline, single CTA button (bg-white text-brand-primary).',
            '',
            'GENERAL STYLING:',
            '- Use generous whitespace: py-20 md:py-28 for sections, max-w-7xl mx-auto px-6.',
            '- Alternate section backgrounds: bg-white, bg-slate-50, bg-brand-bg. Never make every section the same bg.',
            '- Typography: use tracking-tight on headings, text-slate-600 for body, font-semibold for emphasis.',
            '- Buttons: rounded-xl (not rounded-lg), px-6 py-3, font-semibold, shadow-lg with colored shadow (shadow-brand-primary/20).',
            '- Cards: rounded-2xl border border-slate-200, subtle shadow-sm, hover lift effect.',
            '- NEVER use bg-opacity-* utilities — use the slash syntax instead (e.g. bg-brand-primary/10).',
            '- NEVER use crude hand-drawn SVG illustrations as hero images or section visuals. Use clean UI mockups, gradient blobs, or simple geometric shapes instead.',
            '- NEVER use the old icon-box pattern (colored circle with tiny icon on top + stacked text). Use bento grids or horizontal inline layouts.',
            '- Icons: import from lucide-react, use size={20} or size={24}, place them inline with text or in card corners.',
            '- Add micro-interactions: hover states, transitions, subtle scale/translate on hover.',
            '- Make it feel premium and polished, like a Linear/Vercel/Stripe landing page.',
            '- CRITICAL: When rendering arrays of objects (e.g. features, plans, links, items), ALWAYS access the specific property (e.g. {feature.title}, {item.label}, {plan.name}). NEVER render the object itself as a JSX child (e.g. {feature} or {item}) — this causes a runtime error.',
        ]);

        $userParts = [];
        $blueprintType = is_string($blueprint['type'] ?? null) ? (string) $blueprint['type'] : 'page';
        $userParts[] = 'Original prompt: ' . $prompt;
        if ($blueprintType === 'app') {
            $userParts[] = '[APP MODE] This is an APP/DASHBOARD request. Do NOT generate a landing page. Generate a multi-page React app using HashRouter with separate pages in src/pages/. Each page should be a real screen, not a landing section.';
            $userParts[] = 'App plan: ' . json_encode([
                'title' => $blueprint['title'] ?? 'Generated App',
                'description' => $blueprint['description'] ?? '',
                'pages' => $blueprint['pages'] ?? [],
            ]);
        } else {
            $userParts[] = 'Blueprint: ' . json_encode([
                'title' => $blueprint['title'] ?? 'Generated Page',
                'description' => $blueprint['description'] ?? '',
                'sections' => $blueprint['sections'] ?? [],
            ]);
        }
        $userParts[] = 'Brand colors: ' . json_encode($sharedStyles);
        if ($screenshotUrl) {
            $userParts[] = 'Reference screenshot URL: ' . $screenshotUrl;
            $userParts[] = 'Use the screenshot for inspiration only. Do not reference remote assets from it directly.';
        }

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            implode("\n", $userParts),
            12000,
            'React Page Generator'
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            return null;
        }

        $json = is_array($result['json'] ?? null) ? $result['json'] : null;
        if (!is_array($json)) {
            return null;
        }

        $files = is_array($json['files'] ?? null) ? $json['files'] : null;

        if (!empty($files) && is_array($files)) {
            // If the AI puts everything in a single file, prefer the orchestrated
            // multi-file fallback so each section has its own file and progress step.
            if (count($files) < 2) {
                return null;
            }
            return $this->processMultiFileArtifacts($files, $sharedStyles);
        }

        // Fallback to single-file format for backward compatibility
        $componentName = is_string($json['component_name'] ?? null) ? trim((string) $json['component_name']) : '';
        $code = is_string($json['code'] ?? null) ? trim((string) $json['code']) : '';

        if ($code === '') {
            return null;
        }

        $code = $this->normalizeReactCode($code);
        if ($componentName === '') {
            $componentName = $this->detectReactComponentName($code);
        }

        if (!$this->isValidReactComponentName($componentName)) {
            return null;
        }

        if (!$this->isSafeReactCode($code, $componentName)) {
            return null;
        }

        return [
            'html' => $this->buildReactRuntimeSnippet($componentName, $code, $sharedStyles),
            'react_code' => $code,
            'project_files' => null,
        ];
    }

    private function processMultiFileArtifacts(array $files, array $sharedStyles): ?array
    {
        $sanitized = [];
        $pageFile = null;

        foreach ($files as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null) || !is_string($file['content'] ?? null)) {
                continue;
            }

            $path = trim((string) $file['path']);
            $content = trim((string) $file['content']);

            if ($path === '' || $content === '') {
                continue;
            }

            $path = str_replace('\\', '/', $path);
            $path = ltrim($path, '/');

            if (str_contains($path, '..')) {
                continue;
            }

            // Normalize imports to .js/.jsx references
            $content = $this->normalizeImports($content);
            // Fix incorrect relative paths that the AI sometimes emits
            $content = $this->normalizeImportPaths($path, $content);

            $sanitized[] = [
                'path' => $path,
                'content' => $content,
            ];

            if (preg_match('#^src/pages/.*\.(jsx|tsx)$#i', $path)) {
                $pageFile = ['path' => $path, 'content' => $content];
            }
            // Fallback: accept src/App.jsx as the main page if no src/pages file exists
            if ($pageFile === null && preg_match('#^src/App\.(jsx|tsx)$#i', $path)) {
                $pageFile = ['path' => $path, 'content' => $content];
            }
        }

        if (empty($sanitized)) {
            return null;
        }

        // If no explicit page file found, use the first JSX file as the page
        if ($pageFile === null) {
            foreach ($sanitized as $sf) {
                if (preg_match('#\.(jsx|tsx)$#i', $sf['path'])) {
                    $pageFile = $sf;
                    break;
                }
            }
        }

        if ($pageFile === null) {
            return null;
        }

        // Derive the main component name from the page file
        $componentName = $this->detectReactComponentName($pageFile['content']) ?: 'LandingPage';

        // Build a single combined code string for backward compatibility (react_code)
        $combinedCode = $this->buildCombinedCode($sanitized);

        return [
            'html' => $this->buildReactRuntimeSnippet($componentName, $combinedCode, $sharedStyles),
            'react_code' => $combinedCode,
            'project_files' => $sanitized,
        ];
    }

    private function normalizeImports(string $code): string
    {
        // Tailwind v3 cannot apply an opacity modifier (/70) to a color expressed
        // as a CSS variable — `bg-[var(--ds-background)]/70` and `bg-ds-background/80`
        // silently generate NO rule, so the element gets no background at all.
        // Rewrite those to an explicit color-mix() arbitrary value (same shape as
        // the working `border-[color:color-mix(...)]` classes).
        $code = $this->fixDsVarOpacityClasses($code);

        // Ensure relative imports use .js extension for Vite compatibility
        $code = preg_replace(
            '#(from\s+["\'])(\.\.?/[^"\']+)(?<!\.js|\.jsx|\.ts|\.tsx)(["\'])#',
            '$1$2.jsx$3',
            $code
        );

        return $code;
    }

    /**
     * Rewrite Tailwind classes that combine a --ds-* CSS variable with an opacity
     * modifier (e.g. bg-[var(--ds-background)]/70 or bg-ds-background/70) into an
     * arbitrary color-mix() value that Tailwind actually compiles:
     *   bg-[color:color-mix(in_srgb,var(--ds-background)_70%,transparent)]
     */
    private function fixDsVarOpacityClasses(string $code): string
    {
        $code = preg_replace(
            '/(\b(?:bg|text|border|ring|fill|stroke|from|via|to)-)\[var\(--ds-([a-z0-9-]+)\)\]\/(\d+)\b/',
            '$1[color:color-mix(in_srgb,var(--ds-$2)_$3%,transparent)]',
            $code
        );
        $code = preg_replace(
            '/(\b(?:bg|text|border|ring|fill|stroke|from|via|to))-ds-([a-z0-9-]+)\/(\d+)\b/',
            '$1-[color:color-mix(in_srgb,var(--ds-$2)_$3%,transparent)]',
            $code
        );
        return $code;
    }

    private function normalizeImportPaths(string $filePath, string $code): string
    {
        // Section files live in src/components/sections/. The default sandbox provides UI
        // components in src/components/ui/. The AI sometimes emits the wrong relative path
        // (e.g. ../components/ui/ or ../../components/ui/) instead of the correct ../ui/.
        if (preg_match('#^src/components/sections/#', $filePath)) {
            $code = preg_replace(
                '#(from\s+["\'])(\.\./components/ui/|\.\./\.\./components/ui/)#',
                '$1../ui/',
                $code
            );
            $code = preg_replace(
                '#(from\s+["\'])(\.\./components/layout/|\.\./\.\./components/layout/)#',
                '$1../layout/',
                $code
            );
        }

        return $code;
    }

    private function buildCombinedCode(array $files): string
    {
        // For backward compatibility, build a single string representation
        // This is used for react_code field and the old runtime snippet
        $pageFile = null;
        foreach ($files as $file) {
            if (preg_match('#^src/pages/.*\.(jsx|tsx)$#i', $file['path'])) {
                $pageFile = $file;
                break;
            }
        }

        if ($pageFile !== null) {
            return $pageFile['content'];
        }

        return $files[0]['content'] ?? '';
    }

    public function fixReactBuildErrors(array $previousFiles, string $buildError, string $model): ?array
    {
        $system = implode("\n", [
            'You are a senior React/Vite developer fixing build errors.',
            'You receive a set of project files and a Vite build error.',
            'Output ONLY valid JSON with this exact shape: {"files": [{"path": string, "content": string}]}.',
            '',
            'RULES:',
            '- Fix ONLY the files that have errors. Keep other files unchanged.',
            '- Common fixes:',
            '  * "is not exported by lucide-react" → Replace the invalid icon with a valid lucide icon like Globe, ExternalLink, Share2, MessageCircle, Mail, Users, Camera, Star, Heart, Zap, Shield, Check, ArrowRight, ChevronRight, BarChart3, Award, Gem, Layers, Compass, Send, Bell, Calendar, Clock, Bookmark, FileText, Folder, Lock, Key, Settings, Sliders, Filter, Plus, Minus, RotateCcw, RefreshCw, Upload, Download, Copy, Trash2, Edit3, Eye, EyeOff, Link, Unlink, Home, User, LogIn, LogOut, HelpCircle, Info, AlertTriangle, AlertCircle, CheckCircle, XCircle, TrendingUp, TrendingDown, Activity, PieChart, BarChart, LineChart, AreaChart, Wallet, CreditCard, DollarSign, Percent, Receipt, ShoppingCart, ShoppingBag, Package, Truck, Map, Navigation, Crosshair, Target, Flag, Trophy, Medal, Crown, ThumbsUp, ThumbsDown, MessageSquare, MessagesSquare, Inbox, Paperclip, AtSign, Hash, Tag, BookOpen, Library, GraduationCap, School, Brain, Lightbulb, Sparkles, Flame, Sun, Moon, Cloud, CloudRain, CloudSnow, Wind, Thermometer, Droplets, Umbrella, Snowflake, Leaf, TreePine, Mountain, Waves, Anchor, Ship, Plane, Car, Bus, Train, Bike, Walk, Run, Dumbbell, HeartPulse, Pill, Stethoscope, ScanFace, Fingerprint, ShieldCheck, ShieldAlert, Monitor, Smartphone, Tablet, Laptop, Printer, Mouse, Keyboard, Gamepad2, Headphones, Speaker, Mic, Video, Film, Clapperboard, Music, Radio, Tv, Cast, Wifi, Bluetooth, Battery, BatteryCharging, BatteryMedium, BatteryLow, BatteryWarning, Plug, Power, Cpu, HardDrive, Database, Server.',
            '  * "Multiple exports with the same name" → Remove duplicate export default.',
            '  * "is not defined" → Import the missing variable or component.',
            '  * Import path missing extension → Add .jsx to relative imports.',
            '  * Incorrect relative path (e.g. from src/components/sections/X.jsx importing ../ui/...) → Correct to ../ui/... or the proper relative path.',
            '  * Missing React import → Add import React from "react" when using JSX.',
            '  * Unescaped quotes inside string literals (e.g. "19" Alloy") → Escape the inner quote as "19\\" Alloy" or switch to single quotes.',
            '- Do not change the "path" field of any file.',
            '- You may fix import paths inside file content.',
            '- Do not add markdown or code fences.',
            '- ONLY output the corrected "files" array.',
        ]);

        $fileJson = json_encode($previousFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $userPrompt = implode("\n", [
            'Build error:\n' . $buildError,
            '',
            'Current files:\n' . $fileJson,
            '',
            'Return the corrected files array.',
        ]);

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $userPrompt,
            8000,
            'React Build Fix'
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            return null;
        }

        $json = is_array($result['json'] ?? null) ? $result['json'] : null;
        if (!is_array($json) || empty($json['files'] ?? null)) {
            return null;
        }

        $files = [];
        foreach ($json['files'] as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null) || !is_string($file['content'] ?? null)) {
                continue;
            }
            $path = str_replace('\\', '/', ltrim(trim((string) $file['path']), '/'));
            if (str_contains($path, '..')) {
                continue;
            }
            $files[] = [
                'path' => $path,
                'content' => $this->normalizeImports(trim((string) $file['content'])),
            ];
        }

        return $files ?: null;
    }

    public function iterate(array $currentFiles, string $prompt, ?string $modelOverride = null, ?array $messages = null, ?array $frontendTheme = null, ?string $mode = null, ?callable $onStep = null, ?array $segmentPlan = null): array
    {
        if ($mode === 'theme' && !empty($frontendTheme)) {
            $themeApplier = new ThemeApplier();
            $preset = $themeApplier->presetFromFrontendTheme($frontendTheme) ?? ThemeApplier::randomPreset();
            return [
                'success' => true,
                'project_files' => $this->repairProjectFiles($themeApplier->applyPreset($currentFiles, $preset)),
            ];
        }

        $model = ModelRouter::resolve('iteration', $modelOverride);
        if (!$this->router->hasCredentials($model)) {
            throw new \RuntimeException('AI API key is not configured for the selected provider.');
        }

        if ($onStep !== null) {
            $this->router->onStep($onStep);
        }

        $system = implode("\n", [
            'You are a senior React/Vite developer doing a targeted, context-aware edit on an existing project.',
            'You receive the current project files (editable source files) and a user request.',
            'Output ONLY valid JSON with this exact shape: {"files": [{"path": string, "content": string}]}.',
            'The project shown in the preview is already generated and running. You must edit the existing files to satisfy the user request. Do not start from scratch or return files that are not changed.',
            '',
            'RULES:',
            '- Modify ONLY the files necessary to fulfill the user request.',
            '- Keep all other files unchanged (do not include them in the output).',
            '- If the request only changes a style, return the full updated file with the new className or style.',
            '- If the request only changes text, return the full updated file with the new text.',
            '- If the request adds a new section, return the new section file plus any updated page that imports it.',
            '- Do not rename files or change their paths.',
            '- Preserve the existing Tailwind + shadcn-compatible design system.',
            '- Use the existing components (Button, Card, Badge, Accordion, Tabs, etc.) from src/components/ui/.',
            '- Use the design tokens (ds-* colors, --brand-* variables) from src/styles.css.',
            '- Do not use backslashes before JSX attributes (e.g. type="text"\ disabled). Write clean JSX: type="text" disabled.',
            '- Do not duplicate JSX attributes on the same element (e.g. className="a" className="b"). Merge them: className="a b".',
            $this->brandLogoPromptLine(),
            '- Common fixes to apply automatically if you notice them:',
            '  * Missing .jsx extension on relative imports → add .jsx.',
            '  * Missing React import → add import React from "react" when using JSX.',
            '  * Unescaped quotes inside string literals → escape them or switch to single quotes.',
            '  * Backslashes before JSX attributes (e.g. type="x"\ disabled) → remove the backslash.',
            '  * Duplicate JSX attributes (e.g. className="a" className="b") → merge into one: className="a b".',
            '- Do not add markdown, code fences, or explanations.',
            '- ONLY output the "files" array.',
            '',
            'Boilerplate UI files (src/components/ui/*.jsx, src/lib/utils.js, src/hooks/use-mobile.js, vite/tailwind configs, package.json, etc.) are already provided and are NOT shown in the file list below. Do not modify them unless the user explicitly asks to change a boilerplate component or config.',
            '',
            'APP MODE — if the user request turns the project into an app (dashboard, auth, login, signup, multiple pages, data, tasks, etc.), generate a HashRouter-based `src/App.jsx` with separate page files in `src/pages/` and a navigation bar using `react-router-dom` `<Link>` and `useNavigate`. Do not keep it as a single landing page.',
            '',
            'DATA PERSISTENCE & AUTH — if the user request adds or modifies data storage (task list, notes, todos, contacts, users, etc.) or auth (login, signup, protected pages), use Supabase:',
            '  - The packages `@supabase/supabase-js` and `react-router-dom` are already installed.',
            '  - Import: `import { createClient } from "@supabase/supabase-js";`',
            '  - Inside a `useEffect`, read the credentials from the browser globals: `const url = window.APP_SUPABASE_URL; const key = window.APP_SUPABASE_PUBLISHABLE_KEY;`.',
            '  - If both are present, create a client: `const supabase = createClient(url, key);` and use it for CRUD operations.',
            '  - For auth, use `supabase.auth.signUp`, `supabase.auth.signInWithPassword`, `supabase.auth.signOut`, and `supabase.auth.onAuthStateChange`.',
            '  - For protected routes, use a wrapper component that redirects to /login if the user is not signed in.',
            '  - If the globals are NOT present, fall back to local React state only.',
            '  - Keep the UI fully functional in both cases.',
        ]);

        $contextFiles = $this->prepareIterationContextFiles($currentFiles);
        $conversationContext = $this->buildIterationConversationContext($messages);

        // Try the segmented plan-then-edit path first: a small planning request
        // decides which files change, then each file is edited in its own small
        // request. This keeps every single call small enough to avoid HTTP timeouts.
        $segmented = $this->iterateSegmented($model, $currentFiles, $contextFiles, $conversationContext, $prompt, $segmentPlan);
        if (is_array($segmented) && !empty($segmented['files'])) {
            return [
                'success' => true,
                'project_files' => $segmented['files'],
                'remaining_plan' => $segmented['remaining_plan'],
            ];
        }

        $userPrompt = implode("\n", array_filter([
            $conversationContext,
            'User request: ' . $prompt,
            '',
            'Current project files (only editable source files are shown; UI components are available for import):',
            json_encode($contextFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            '',
            'Return only the files that need to change. Make sure the returned file paths exactly match the existing file paths you are editing. If you add a new page, also update src/App.jsx to include its route.',
        ]));

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $userPrompt,
            12000,
            'React Iteration Editor'
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            $message = is_array($result) && is_string($result['message'] ?? null) && trim($result['message']) !== ''
                ? trim($result['message'])
                : 'The AI provider failed to respond.';

            return [
                'success' => false,
                'error' => $message,
            ];
        }

        $json = is_array($result['json'] ?? null) ? $result['json'] : null;
        if (!is_array($json) || empty($json['files'] ?? null)) {
            return [
                'success' => false,
                'error' => 'The AI response did not contain valid JSON with a files array.',
            ];
        }

        $changedFiles = [];
        foreach ($json['files'] as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null) || !is_string($file['content'] ?? null)) {
                continue;
            }
            $path = str_replace('\\', '/', ltrim(trim((string) $file['path']), '/'));
            if (str_contains($path, '..')) {
                continue;
            }
            $changedFiles[$path] = [
                'path' => $path,
                'content' => $this->normalizeImports(trim((string) $file['content'])),
            ];
        }

        if (empty($changedFiles)) {
            return [
                'success' => false,
                'error' => 'The AI returned no changed files. Try rephrasing the request.',
            ];
        }

        // Merge changes on top of the current project files
        $currentMap = [];
        foreach ($currentFiles as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null)) {
                continue;
            }
            $path = str_replace('\\', '/', ltrim(trim((string) $file['path']), '/'));
            $currentMap[$path] = $file;
        }

        foreach ($changedFiles as $path => $file) {
            $currentMap[$path] = $file;
        }

        return [
            'success' => true,
            'project_files' => $this->repairProjectFiles(array_values($currentMap)),
        ];
    }

    /**
     * Segmented iteration path: first ask the model which files need to change
     * (a tiny request that only sees the file tree, not file bodies), then edit
     * each planned file in its own small request that only carries that file's
     * content plus the requested change. Returns one bounded batch and the
     * remaining plan, or null when no plan could be produced.
     */
    private function iterateSegmented(string $model, array $currentFiles, array $contextFiles, string $conversationContext, string $prompt, ?array $segmentPlan = null): ?array
    {
        $plan = $segmentPlan ?: $this->planIterationFiles($model, $contextFiles, $conversationContext, $prompt);
        if (empty($plan)) {
            return null;
        }

        // Return the remaining work to the browser. Each continuation gets its
        // own short request and merges the previous batch before editing again.
        $batch = array_slice($plan, 0, self::MAX_SEGMENTED_EDIT_FILES);
        $remainingPlan = array_slice($plan, self::MAX_SEGMENTED_EDIT_FILES);

        $byPath = [];
        foreach ($currentFiles as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null)) {
                continue;
            }
            $path = str_replace('\\', '/', ltrim(trim((string) $file['path']), '/'));
            if ($path === '' || str_contains($path, '..')) {
                continue;
            }
            $byPath[$path] = is_string($file['content'] ?? null) ? $file['content'] : '';
        }

        $components = $this->componentInventoryList($currentFiles);

        $changedFiles = [];
        $applied = 0;
        foreach ($batch as $item) {
            if ($applied >= self::MAX_SEGMENTED_EDIT_FILES) {
                break;
            }

            $path = is_string($item['path'] ?? null)
                ? str_replace('\\', '/', ltrim(trim((string) $item['path']), '/'))
                : '';
            if ($path === '' || str_contains($path, '..')) {
                continue;
            }

            $action = is_string($item['action'] ?? null) ? trim((string) $item['action']) : '';
            if ($action === '') {
                continue;
            }

            $edited = $this->editIterationFile(
                $model,
                $byPath[$path] ?? '',
                $path,
                $action,
                $prompt,
                $conversationContext,
                $components
            );

            if ($edited === null) {
                continue;
            }

            $changedFiles[$path] = [
                'path' => $path,
                'content' => $this->normalizeImports($edited),
            ];
            $applied++;
        }

        if (empty($changedFiles)) {
            return null;
        }

        $currentMap = [];
        foreach ($currentFiles as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null)) {
                continue;
            }
            $path = str_replace('\\', '/', ltrim(trim((string) $file['path']), '/'));
            $currentMap[$path] = $file;
        }

        foreach ($changedFiles as $path => $file) {
            $currentMap[$path] = $file;
        }

        return [
            'files' => $this->repairProjectFiles(array_values($currentMap)),
            'remaining_plan' => array_values($remainingPlan),
        ];
    }

    /**
     * Planning request: given only the editable file tree (paths + sizes) and the
     * user request, decide which files must change and what each change is. The
     * payload is tiny so this completes quickly; the model never sees file bodies.
     */
    private function planIterationFiles(string $model, array $contextFiles, string $conversationContext, string $prompt): array
    {
        $tree = array_map(static function (array $file): array {
            return [
                'path' => $file['path'],
                'bytes' => is_string($file['content'] ?? null) ? strlen($file['content']) : 0,
            ];
        }, $contextFiles);
        usort($tree, static function (array $a, array $b): int {
            return strcmp($a['path'], $b['path']);
        });

        $system = implode("\n", [
            'You are a senior React/Vite developer deciding which files must change to satisfy a user request on an existing project.',
            'You receive a list of the editable source files (path + approximate size) and a user request.',
            'Output ONLY valid JSON with this exact shape: {"files": [{"path": string, "action": string}]}.',
            '',
            'RULES:',
            '- List ONLY files that must be modified or created to fulfill the request.',
            '- Use exact existing paths. New files must live under src/pages/, src/components/, or src/lib/.',
            '- If a new page is created, also list src/App.jsx with an action to add its route.',
            '- If the request only changes text or styling, list only the affected file(s).',
            '- Do not list boilerplate UI files (src/components/ui/*, src/lib/*, src/hooks/*, configs, package.json) unless the request explicitly targets them.',
            '- Keep each action to one concise sentence describing the change.',
            '- Do not include markdown, code fences, or explanations.',
            '- ONLY output the "files" array in the JSON object.',
        ]);

        $user = implode("\n", array_filter([
            $conversationContext,
            'User request: ' . $prompt,
            '',
            'Project files (editable source):',
            json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]));

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $user,
            1200,
            'Iteration Plan'
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            return [];
        }

        $json = is_array($result['json'] ?? null) ? $result['json'] : null;
        $files = is_array($json['files'] ?? null) ? $json['files'] : [];

        $plan = [];
        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }
            $path = is_string($file['path'] ?? null) ? trim((string) $file['path']) : '';
            $action = is_string($file['action'] ?? null) ? trim((string) $file['action']) : '';
            if ($path === '') {
                continue;
            }
            $plan[] = [
                'path' => str_replace('\\', '/', ltrim($path, '/')),
                'action' => $action,
            ];
        }

        return $plan;
    }

    /**
     * Single-file edit request: sends only the target file's content (or nothing
     * when the file is new) plus the requested change. Returns the complete
     * updated file content, or null when the edit failed.
     */
    private function editIterationFile(string $model, string $currentContent, string $path, string $action, string $prompt, string $conversationContext, array $components): ?string
    {
        $isNew = trim($currentContent) === '';

        $system = implode("\n", array_filter([
            'You are a senior React/Vite developer applying ONE targeted edit to a single file in an existing project.',
            'Output ONLY the complete updated file content as plain text. No markdown code fences, no JSON, no explanations, no "here is the file" text.',
            '',
            'RULES:',
            '- Preserve all existing code you are not changing. Never truncate output and never use "..." placeholders.',
            '- Use the Tailwind + shadcn design system and the ds-*/--brand-* design tokens from src/styles.css.',
            '- Use components from the available list below via `@/components/ui/...` imports.',
            '- Keep .jsx extensions on relative imports and keep the React import when using JSX.',
            '- Do not use backslashes before JSX attributes (e.g. type="text"\ disabled). Write clean JSX: type="text" disabled.',
            '- Do not duplicate JSX attributes on the same element (e.g. className="a" className="b"). Merge them: className="a b".',
            $components !== [] ? '- Available UI components you may import: ' . implode(', ', $components) . '.' : '',
        ]));

        $userParts = array_filter([
            $conversationContext,
            'User request: ' . $prompt,
            '',
            'File to edit: ' . $path,
            'Required change: ' . $action,
            '',
            $isNew
                ? 'This file does not exist yet. Create it in full.'
                : 'Current file content:',
            $isNew ? '' : $currentContent,
            '',
            'Output only the complete updated file content.',
        ]);

        $messages = [
            ['role' => 'user', 'content' => implode("\n", $userParts)],
        ];

        $result = $this->router->generateChat($model, $messages, 6000, $system);

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            return null;
        }

        $content = is_string($result['text'] ?? null) ? trim($result['text']) : '';

        if ($content === '') {
            return null;
        }

        return $this->normalizeReactCode($content);
    }

    /**
     * Collect the exported component names available from src/components/ui/ so a
     * single-file edit knows what it can import without seeing the full UI code.
     */
    private function componentInventoryList(array $currentFiles): array
    {
        $components = [];
        foreach ($currentFiles as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null)) {
                continue;
            }
            $path = (string) $file['path'];
            if (! str_starts_with($path, 'src/components/ui/') || ! str_ends_with($path, '.jsx')) {
                continue;
            }
            $content = is_string($file['content'] ?? null) ? $file['content'] : '';
            if (preg_match('/export\s+(?:default\s+)?function\s+([A-Z][A-Za-z0-9]*)/', $content, $matches)) {
                $components[] = $matches[1];
            } elseif (preg_match('/export\s+const\s+([A-Z][A-Za-z0-9]*)\s*=/', $content, $matches)) {
                $components[] = $matches[1];
            }
        }

        return array_values(array_unique(array_filter($components)));
    }

    /**
     * Rewrite known-unreliable external image URLs. Hallucinated Unsplash photo
     * IDs, the deprecated source.unsplash.com service, unreachable image hosts,
     * and logo CDNs (GitHub raw, shadcnblocks CloudFront) are replaced with
     * deterministic, always-valid placeholders:
     *  - URLs sitting in a logo/brand context are replaced with a pick from the
     *    fixed BRAND_LOGO_URLS set so logo strips never show photos or broken images;
     *  - any other image URL is replaced with a deterministic picsum.photos URL.
     * URLs already in the BRAND_LOGO_URLS set are preserved untouched.
     */
    private function repairImageUrls(string $content): string
    {
        $pattern = '#https?://(?:'
            . 'images\.unsplash\.com/photo-[0-9a-zA-Z-]+(?:\?[^\s"\'\`<>)]*)?'
            . '|source\.unsplash\.com/[^\s"\'\`<>)]*'
            . '|i\.ibb\.co\.com/[^\s"\'\`<>)]*'
            . '|raw\.githubusercontent\.com/[^\s"\'\`<>)]*'
            . '|deifkwefumgah\.cloudfront\.net/[^\s"\'\`<>)]*'
            . ')#';

        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $content;
        }

        $out = '';
        $cursor = 0;
        $lastLogo = -1;
        foreach ($matches[0] as [$url, $offset]) {
            $out .= substr($content, $cursor, $offset - $cursor);
            $out .= $this->repairImageUrl($url, $content, $offset, $lastLogo);
            $cursor = $offset + strlen($url);
        }
        $out .= substr($content, $cursor);

        return $out;
    }

    private function repairImageUrl(string $url, string $content, int $offset, int &$lastLogo): string
    {
        foreach (static::BRAND_LOGO_URLS as $logo) {
            if (str_starts_with($url, $logo)) {
                return $url;
            }
        }

        $isLogoContext = $this->isLogoArrayContext($content, $offset)
            || (bool) preg_match('/(?:companyLogo|\/logos\/|logo)/i', $url);

        if ($isLogoContext) {
            $count = count(static::BRAND_LOGO_URLS);
            $pick = crc32($url) % $count;
            if ($pick === $lastLogo) {
                $pick = ($pick + 1) % $count;
            }
            $lastLogo = $pick;

            return static::BRAND_LOGO_URLS[$pick];
        }

        $width = 800;
        $height = 600;
        if (preg_match('/[?&]w=(\d+)/', $url, $w) && (int) $w[1] > 0) {
            $width = (int) $w[1];
        }
        if (preg_match('/[?&]h=(\d+)/', $url, $h) && (int) $h[1] > 0) {
            $height = (int) $h[1];
        }

        return 'https://picsum.photos/seed/' . substr(md5($url), 0, 12) . '/' . $width . '/' . $height;
    }

    /**
     * True when the URL at $offset sits inside a JS/JSX array literal whose
     * enclosing key is a logo/brand identifier (e.g. `"logos": [`, `logos = [`).
     */
    private function isLogoArrayContext(string $content, int $offset): bool
    {
        $pos = $offset;
        while (($open = strrpos(substr($content, 0, $pos), '[')) !== false) {
            $depth = 1;
            $n = strlen($content);
            $close = null;
            for ($i = $open + 1; $i < $n && $depth > 0; $i++) {
                if ($content[$i] === '[') {
                    $depth++;
                } elseif ($content[$i] === ']' && --$depth === 0) {
                    $close = $i;
                    break;
                }
            }

            if ($close !== null && $close > $offset) {
                $before = trim(substr($content, 0, $open));
                if (preg_match('/([\w"\'\)]+)\s*[:=]\s*$/', $before, $m)) {
                    $key = trim($m[1], " \t\n\r\0\x0B\"'()");

                    return (bool) preg_match('/logo|brand/i', $key);
                }

                return false;
            }

            $pos = $open;
        }

        return false;
    }

    /**
     * Apply image URL repair to every editable file in a generated project.
     */
    private function repairProjectFiles(array $files): array
    {
        foreach ($files as $i => $file) {
            if (!is_array($file) || !is_string($file['content'] ?? null)) {
                continue;
            }
            $files[$i]['content'] = $this->repairImageUrls($file['content']);
        }

        return $files;
    }

    /**
     * Prompt line instructing the model which URLs to use for logo/brand fields.
     */
    private function brandLogoPromptLine(): string
    {
        return '- For LOGO/BRAND fields (logos, brands, companyLogo arrays), use ONLY one of these 10 logo URLs: '
            . implode(' ', static::BRAND_LOGO_URLS)
            . '. Never invent other logo URLs. For regular images use picsum seeds.';
    }

    private function prepareIterationContextFiles(array $files): array
    {
        $byPath = [];
        foreach ($files as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null)) {
                continue;
            }
            $path = str_replace('\\', '/', ltrim(trim((string) $file['path']), '/'));
            $byPath[$path] = is_string($file['content'] ?? null) ? $file['content'] : '';
        }

        // Boilerplate files are already provided to the sandbox and are not shown
        // to the iteration model (the system prompt says so). Never include them,
        // even when sections import them.
        $isBoilerplate = function (string $path): bool {
            foreach ([
                'src/components/ui/',
                'src/lib/',
                'src/hooks/',
                'src/main.jsx',
                'vite.config.',
                'tailwind.config.',
                'postcss.config.',
                'package.json',
                'components.json',
                'eslint.config.js',
                '.prettierrc',
                '.prettierignore',
                '.gitignore',
                'README.md',
                'public/',
                'node_modules/',
            ] as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return true;
                }
            }

            return false;
        };

        $visited = [];
        $included = [];
        $totalChars = 0;

        $include = function (string $path) use (&$included, &$visited, &$totalChars, $byPath, $isBoilerplate): void {
            if (isset($visited[$path]) || $isBoilerplate($path)) {
                return;
            }
            $visited[$path] = true;
            if (!isset($byPath[$path])) {
                return;
            }
            $len = strlen($byPath[$path]);
            if (count($included) < self::MAX_ITERATION_CONTEXT_FILES && $totalChars + $len <= self::MAX_ITERATION_CONTEXT_CHARS) {
                $included[$path] = $byPath[$path];
                $totalChars += $len;
            }
        };

        // Entry points first: the app shell and any routed pages.
        foreach (array_keys($byPath) as $path) {
            if ($path === 'src/App.jsx' || $path === 'src/main.jsx' || str_starts_with($path, 'src/pages/')) {
                $include($path);
            }
        }
        // The design system is needed so edits stay on-brand.
        $include('src/styles.css');

        // Walk the import graph so only files that are actually part of the page
        // reach the model (scratch files such as *Test.jsx are skipped).
        $queue = array_keys($included);
        while ($queue !== []) {
            $current = array_shift($queue);
            $content = $byPath[$current] ?? '';
            foreach ($this->extractRelativeImports($content) as $spec) {
                $resolved = $this->resolveIterationImportPath($current, $spec, $byPath);
                if ($resolved !== null && !isset($visited[$resolved])) {
                    $include($resolved);
                    $queue[] = $resolved;
                }
            }
        }

        $context = [];
        foreach ($included as $path => $content) {
            $context[] = [
                'path' => $path,
                'content' => $content,
            ];
        }

        return $context;
    }

    /**
     * Pull bare relative import specifiers ("./X", "../ui/Y") out of a file so
     * the iteration context can be resolved to the actual files in the project.
     */
    private function extractRelativeImports(string $content): array
    {
        $specs = [];
        if (preg_match_all('/(?:\bfrom\s+|\bimport\s*\(?\s*)[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $spec) {
                if (str_starts_with((string) $spec, '.')) {
                    $specs[] = (string) $spec;
                }
            }
        }

        return array_values(array_unique($specs));
    }

    /**
     * Resolve a relative import specifier against the importing file's directory,
     * returning the exact project path that exists in $byPath (or null).
     */
    private function resolveIterationImportPath(string $currentPath, string $spec, array $byPath): ?string
    {
        $dir = trim(dirname(str_replace('\\', '/', $currentPath)), '/');
        $full = ($dir !== '' && $dir !== '.') ? $dir . '/' . $spec : $spec;

        $parts = [];
        foreach (explode('/', $full) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if ($parts !== []) {
                    array_pop($parts);
                }
                continue;
            }
            $parts[] = $part;
        }

        $base = implode('/', $parts);
        if ($base === '') {
            return null;
        }

        foreach ([$base, $base . '.jsx', $base . '.tsx', $base . '.js', $base . '.ts', $base . '/index.jsx', $base . '/index.tsx', $base . '/index.js', $base . '/index.ts'] as $candidate) {
            if (isset($byPath[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    private function buildIterationConversationContext(?array $messages): string
    {
        if (empty($messages) || !is_array($messages)) {
            return '';
        }

        $lines = ['Conversation history:'];
        $hasContent = false;
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $role = is_string($message['role'] ?? null) ? strtolower($message['role']) : '';
            $content = is_string($message['content'] ?? null) ? trim($message['content']) : '';
            if ($content === '') {
                continue;
            }

            $status = is_string($message['status'] ?? null) ? $message['status'] : '';
            if ($role === 'assistant' && ($status === 'progress' || $status === 'thought')) {
                continue;
            }

            $label = $role === 'assistant' ? 'Assistant' : 'User';
            $lines[] = $label . ': ' . $content;
            $hasContent = true;
        }

        if (!$hasContent) {
            return '';
        }

        return implode("\n", $lines) . "\n";
    }

    private function normalizeReactCode(string $code): string
    {
        $code = trim($code);
        $code = preg_replace('/^```(?:jsx|tsx|javascript|js)?\s*/i', '', $code);
        $code = preg_replace('/\s*```$/', '', $code);

        return trim((string) $code);
    }

    private function detectReactComponentName(string $code): string
    {
        if (preg_match('/(?:function|const)\s+([A-Z][A-Za-z0-9_]*)\s*(?:\(|=)/', $code, $matches)) {
            return $matches[1] ?? '';
        }

        return '';
    }

    private function isValidReactComponentName(string $componentName): bool
    {
        return (bool) preg_match('/^[A-Z][A-Za-z0-9_]*$/', $componentName);
    }

    private function isSafeReactCode(string $code, string $componentName): bool
    {
        if (strlen($code) > 40000) {
            return false;
        }

        if (!preg_match('/(?:function|const)\s+' . preg_quote($componentName, '/') . '\s*(?:\(|=)/', $code)) {
            return false;
        }

        $blockedPatterns = [
            '/\brequire\s*\(/i',
            '/\bfetch\s*\(/i',
            '/\bXMLHttpRequest\b/i',
            '/\bWebSocket\b/i',
            '/\bEventSource\b/i',
            '/\blocalStorage\b/i',
            '/\bsessionStorage\b/i',
            '/\bindexedDB\b/i',
            '/\bdocument\.cookie\b/i',
            '/\beval\s*\(/i',
            '/\bFunction\s*\(/i',
            '/dangerouslySetInnerHTML/i',
            '/<\/?script/i',
            '/<\/?iframe/i',
        ];

        $allowedImports = [
            '/^\s*import\s+.*\s+from\s+["\']react["\'];/m',
            '/^\s*import\s+.*\s+from\s+["\']react-dom["\'];/m',
            '/^\s*import\s+.*\s+from\s+["\']lucide-react["\'];/m',
            '/^\s*import\s+.*\s+from\s+["\']\.\.\/lib\/utils\.js["\'];/m',
            '/^\s*import\s+.*\s+from\s+["\']\.\.\/components\/ui\/[^"\']+["\'];/m',
            '/^\s*import\s+.*\s+from\s+["\']\.\.\/components\/layout\/[^"\']+["\'];/m',
            '/^\s*import\s+.*\s+from\s+["\']\.\.\/components\/sections\/[^"\']+["\'];/m',
            '/^\s*export\s+default\s+/m',
        ];

        foreach ($blockedPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                return false;
            }
        }

        return true;
    }

    private function buildReactRuntimeSnippet(string $componentName, string $code, array $sharedStyles): string
    {
        $primary = $sharedStyles['primary'] ?? '#3b82f6';
        $secondary = $sharedStyles['secondary'] ?? '#10b981';
        $background = $sharedStyles['background'] ?? '#ffffff';
        $text = $sharedStyles['text'] ?? '#111827';
        $strippedCode = $this->stripEsModuleSyntax($code, $componentName);
        $escapedCode = $this->escapeInlineScript($strippedCode);
        $componentNameJs = json_encode($componentName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<HTML
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script>
    window.tailwind = window.tailwind || {};
    window.tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        primary: '{$primary}',
                        secondary: '{$secondary}',
                        bg: '{$background}',
                        text: '{$text}',
                    }
                }
            }
        }
    };
    window.__MAILPURSE_AI_REACT_PREVIEW_ERROR__ = function (message) {
        var root = document.getElementById('mailpurse-ai-react-root');
        if (!root) return;
        root.innerHTML = '<div class="mailpurse-ai-react-error"><strong>Preview error:</strong> ' + String(message || 'Unable to render generated component.') + '</div>';
    };
    window.addEventListener('error', function (event) {
        if (event && event.message) {
            window.__MAILPURSE_AI_REACT_PREVIEW_ERROR__(event.message);
        }
    });
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<style>
    .mailpurse-ai-react-root, .mailpurse-ai-react-root * { box-sizing: border-box; }
    .mailpurse-ai-react-root { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .bg-brand-primary { background-color: {$primary} !important; }
    .text-brand-primary { color: {$primary} !important; }
    .bg-brand-secondary { background-color: {$secondary} !important; }
    .text-brand-secondary { color: {$secondary} !important; }
    .bg-brand-bg { background-color: {$background} !important; }
    .text-brand-text { color: {$text} !important; }
    .mailpurse-ai-react-error { margin: 16px; border-radius: 12px; border: 1px solid #fecaca; background: #fef2f2; padding: 16px; color: #b91c1c; font: 14px/1.5 'Inter', system-ui, sans-serif; }
</style>
<div id="mailpurse-ai-react-root" class="mailpurse-ai-react-root"></div>
<script type="text/babel" data-presets="react">
const { Fragment, useMemo, useRef, useState } = React;

{$escapedCode}

try {
    const Component = typeof window[{$componentNameJs}] !== 'undefined'
        ? window[{$componentNameJs}]
        : (typeof {$componentName} !== 'undefined' ? {$componentName} : null);

    if (!Component) {
        throw new Error('Generated React component could not be resolved.');
    }

    ReactDOM.createRoot(document.getElementById('mailpurse-ai-react-root')).render(<Component />);
} catch (error) {
    window.__MAILPURSE_AI_REACT_PREVIEW_ERROR__(error && error.message ? error.message : 'Unable to render generated component.');
}
</script>
HTML;
    }

    private function stripEsModuleSyntax(string $code, string $componentName): string
    {
        // 1. Remove full import statements (including multi-line ones).
        //    Matches import { ... } from '...';  import X from "...";  import * as X from "...";  import '...';
        $code = preg_replace('/^\s*import\s+(?:[^;]*?)\s*from\s*[\'\"][^\'\"]+[\'\"]\s*;\s*$/ms', '', $code);
        $code = preg_replace('/^\s*import\s+[\'\"][^\'\"]+[\'\"]\s*;\s*$/ms', '', $code);

        // 2. Convert "export default X;" → "window.X = X;"
        $code = preg_replace('/^\s*export\s+default\s+(\w+)\s*;\s*$/m', 'window.${1} = ${1};', $code);

        // 3. Convert "export default function Name(...)" → "function Name(...)" and then append window assignment.
        $code = preg_replace_callback(
            '/^\s*export\s+default\s+function\s+(\w+)\s*\(/m',
            function ($m) {
                return "function {$m[1]}(";
            },
            $code
        );
        $code = preg_replace('/^\s*export\s+default\s+class\s+(\w+)/m', 'class ${1}', $code);

        // 4. Strip remaining "export default <anything>;" assignments → assign to window component name.
        $code = preg_replace('/^\s*export\s+default\s+(.+?)\s*;\s*$/m', 'window.' . $componentName . ' = ${1};', $code);

        // 5. Strip "export" keyword from named declarations.
        $code = preg_replace('/^\s*export\s+(function|const|let|var|class)\b/m', '${1}', $code);

        // 6. Remove re-export blocks: export { ... }; and export * from '...';
        $code = preg_replace('/^\s*export\s*\{[^}]*\}\s*;\s*$/ms', '', $code);
        $code = preg_replace('/^\s*export\s*\*\s*from\s*[\'\"][^\'\"]+[\'\"]\s*;\s*$/ms', '', $code);

        // 7. Append window assignment for the primary component if it is a function/class that was default-exported.
        if (!preg_match('/window\.' . preg_quote($componentName, '/') . '\s*=/', $code)) {
            $code .= "\nif (typeof {$componentName} !== 'undefined') { window.{$componentName} = {$componentName}; }";
        }

        return $code;
    }

    private function escapeInlineScript(string $value): string
    {
        return str_ireplace('</script>', '<\\/script>', $value);
    }

    private function sharedStylesFromPreset(?array $preset): array
    {
        $palette = $preset['palette'] ?? [];
        $typography = $preset['typography'] ?? [];

        return [
            'primary'        => $palette['primary']         ?? '#2563eb',
            'secondary'      => $palette['secondary']       ?? '#06b6d4',
            'background'     => $palette['background']      ?? '#f8fafc',
            'text'           => $palette['foreground']      ?? '#0f172a',
            'foreground'     => $palette['foreground']      ?? '#0f172a',
            'muted'          => $palette['muted']           ?? '#f1f5f9',
            'muted_foreground'=> $palette['mutedForeground'] ?? '#64748b',
            'border'         => $palette['border']          ?? '#e2e8f0',
            'card'           => $palette['card']            ?? '#ffffff',
            'accent'         => $palette['accent']          ?? ($palette['primary'] ?? '#2563eb'),
            'tone'           => 'professional, modern',
            'heading_font'   => $this->fontName($typography['heading'] ?? "'Inter', sans-serif"),
            'body_font'      => $this->fontName($typography['body']    ?? "'Inter', system-ui, sans-serif"),
            'padding_y'      => 80,
            'gap'            => 24,
            'border_radius'  => 12,
            'button_style'   => 'primary',
            'badge_variant'   => 'subtle',
        ];
    }

    private function fontName(string $cssFontValue): string
    {
        if (preg_match('/[\'\"]([^\'\"]+)[\'\"]/', $cssFontValue, $m)) {
            return $m[1];
        }
        $parts = array_map('trim', explode(',', $cssFontValue));
        return $parts[0] ?: 'Inter';
    }

    /**
     * Convert AI-generated design_system (from blueprint) into a ThemeApplier-compatible preset.
     */
    private function aiDesignSystemToPreset(array $designSystem): array
    {
        $colors = is_array($designSystem['color_palette'] ?? null) ? $designSystem['color_palette'] : [];
        $typography = is_array($designSystem['typography'] ?? null) ? $designSystem['typography'] : [];
        $designReq = is_array($designSystem['design_requirements'] ?? null) ? $designSystem['design_requirements'] : [];

        $headingFont = is_string($typography['heading_font'] ?? null) ? trim((string) $typography['heading_font']) : 'Inter';
        $bodyFont = is_string($typography['body_font'] ?? null) ? trim((string) $typography['body_font']) : 'Inter';
        $googleFonts = is_array($typography['google_fonts'] ?? null) ? $typography['google_fonts'] : [$bodyFont . ':400,500,600,700'];

        $buttonRadius = is_string($designReq['button_radius'] ?? null) ? (string) $designReq['button_radius'] : 'rounded-lg';

        return [
            'palette' => [
                'primary'        => $colors['primary']         ?? '#2563eb',
                'secondary'      => $colors['secondary']       ?? '#06b6d4',
                'background'     => $colors['background']      ?? '#f8fafc',
                'foreground'     => $colors['foreground']      ?? '#0f172a',
                'muted'          => $colors['muted']           ?? '#f1f5f9',
                'mutedForeground'=> $colors['muted_foreground'] ?? '#64748b',
                'border'         => $colors['border']          ?? '#e2e8f0',
                'card'           => $colors['card']            ?? '#ffffff',
                'accent'         => $colors['accent']          ?? ($colors['primary'] ?? '#2563eb'),
            ],
            'typography' => [
                'heading'     => "'{$headingFont}', sans-serif",
                'body'        => "'{$bodyFont}', system-ui, sans-serif",
                'googleFonts' => $googleFonts,
            ],
            'buttonStyle' => ['radius' => $buttonRadius],
        ];
    }

    /**
     * Build sharedStyles (hex colors + design context) from AI-generated design_system.
     */
    private function designSystemSharedStyle(array $designSystem): array
    {
        $colors = is_array($designSystem['color_palette'] ?? null) ? $designSystem['color_palette'] : [];
        $typography = is_array($designSystem['typography'] ?? null) ? $designSystem['typography'] : [];
        $designReq = is_array($designSystem['design_requirements'] ?? null) ? $designSystem['design_requirements'] : [];

        return [
            'primary'        => $colors['primary']         ?? '#2563eb',
            'secondary'      => $colors['secondary']       ?? '#06b6d4',
            'background'     => $colors['background']      ?? '#f8fafc',
            'text'           => $colors['foreground']      ?? '#0f172a',
            'foreground'     => $colors['foreground']      ?? '#0f172a',
            'muted'          => $colors['muted']           ?? '#f1f5f9',
            'muted_foreground'=> $colors['muted_foreground'] ?? '#64748b',
            'border'         => $colors['border']          ?? '#e2e8f0',
            'card'           => $colors['card']            ?? '#ffffff',
            'accent'         => $colors['accent']          ?? ($colors['primary'] ?? '#2563eb'),
            'tone'           => is_string($designSystem['tone'] ?? null) ? (string) $designSystem['tone'] : 'professional, modern',
            'heading_font'   => is_string($typography['heading_font'] ?? null) ? trim((string) $typography['heading_font']) : 'Inter',
            'body_font'      => is_string($typography['body_font'] ?? null) ? trim((string) $typography['body_font']) : 'Inter',
            'padding_y'      => is_numeric($designReq['padding_y'] ?? null) ? (int) $designReq['padding_y'] : 80,
            'gap'            => is_numeric($designReq['gap'] ?? null) ? (int) $designReq['gap'] : 24,
            'border_radius'  => is_numeric($designReq['border_radius'] ?? null) ? (int) $designReq['border_radius'] : 12,
            'button_style'   => is_string($designReq['button_style'] ?? null) ? (string) $designReq['button_style'] : 'primary',
            'badge_variant'   => $this->normalizeBadgeVariant($designReq['badge_variant'] ?? null),
        ];
    }

    private function normalizeBadgeVariant(?string $variant): string
    {
        return in_array($variant, ['solid', 'outline', 'subtle', 'plain'], true) ? $variant : 'subtle';
    }

    /**
     * Merge enhanced section descriptions from PromptEnhancer into blueprint sections.
     * Matches by section type or name similarity, preferring the enhanced (richer) description.
     */
    private function mergeEnhancedSections(array $blueprintSections, array $enhancedSections): array
    {
        // Build a lookup by type and lowercased name from enhanced sections
        $byType = [];
        $byName = [];
        foreach ($enhancedSections as $es) {
            $type = strtolower(trim((string) ($es['type'] ?? '')));
            $name = strtolower(trim((string) ($es['name'] ?? '')));
            if ($type !== '') {
                $byType[$type] = $es;
            }
            if ($name !== '') {
                $byName[$name] = $es;
            }
        }

        foreach ($blueprintSections as $i => $section) {
            $bpType = strtolower(trim((string) ($section['type'] ?? '')));
            $bpName = strtolower(trim((string) ($section['name'] ?? '')));

            $match = $byType[$bpType] ?? $byName[$bpName] ?? null;
            if ($match !== null) {
                $enhancedDesc = trim((string) ($match['description'] ?? ''));
                // Use enhanced description if it's richer (longer) than blueprint's
                $bpDesc = trim((string) ($section['description'] ?? ''));
                if (strlen($enhancedDesc) > strlen($bpDesc)) {
                    $blueprintSections[$i]['description'] = $enhancedDesc;
                }
            }
        }

        return $blueprintSections;
    }

    private function stripInlineStyles(string $html): string
    {
        // Remove <style> blocks to avoid leaking global CSS
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/si', '', $html);
        // Keep safe inline style attributes (used for gradient blobs, grid overlays, decorative backgrounds)
        return $html;
    }

    private function sanitizeHtml(string $html): string
    {
        $dangerousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/si',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/si',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/si',
            '/<embed\b[^>]*>/si',
            '/\bon\w+\s*=\s*["\'][^"\']*["\']/si',
            '/javascript\s*:/si',
        ];

        $cleaned = $html;
        foreach ($dangerousPatterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }

        return $cleaned;
    }

    /**
     * Parse a Vite/esbuild build error log and extract the file paths that have errors.
     * Returns only the matching files from currentFiles, or null if no matches found
     * (in which case the caller should fall back to sending all files).
     */
    private function extractErrorFilePaths(string $buildLog, array $currentFiles): ?array
    {
        // Vite/esbuild error logs typically contain paths like:
        //   src/components/sections/Features.jsx:38:12
        //   /path/to/sandbox/src/components/sections/Features.jsx
        //   error: Transform failed for src/components/sections/Features.jsx
        $matchedPaths = [];

        // Match patterns like "path/to/file.jsx" or "path/to/file.jsx:line:col"
        preg_match_all('/([a-zA-Z0-9_\-\/\.]+\.(?:jsx?|tsx?|css|json))\b/', $buildLog, $matches);

        if (empty($matches[1])) {
            return null;
        }

        $errorPaths = array_unique($matches[1]);

        // Build a map of current file paths for quick lookup
        $fileMap = [];
        foreach ($currentFiles as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null)) {
                continue;
            }
            $path = str_replace('\\', '/', ltrim(trim((string) $file['path']), '/'));
            $fileMap[$path] = $file;
        }

        foreach ($errorPaths as $errorPath) {
            $errorPath = str_replace('\\', '/', $errorPath);
            // Strip leading slashes to normalize
            $errorPath = ltrim($errorPath, '/');

            // Try exact match
            if (isset($fileMap[$errorPath])) {
                $matchedPaths[$errorPath] = $fileMap[$errorPath];
                continue;
            }

            // Try suffix match (error log may contain absolute path, but fileMap has relative)
            foreach ($fileMap as $fmapPath => $file) {
                if (str_ends_with($fmapPath, $errorPath) || str_ends_with($errorPath, $fmapPath)) {
                    $matchedPaths[$fmapPath] = $file;
                    break;
                }
            }
        }

        if (empty($matchedPaths)) {
            return null;
        }

        return array_values($matchedPaths);
    }

    /**
     * Attempt to fix project files based on a Vite/Rollup build error log.
     * Returns the full merged project files or null if AI could not fix them.
     */
    public function fixBuildErrors(array $currentFiles, string $buildLog, ?string $modelOverride = null): ?array
    {
        $model = ModelRouter::resolve('iteration', $modelOverride);
        if (!$this->router->hasCredentials($model)) {
            throw new \RuntimeException('AI API key is not configured for the selected provider.');
        }

        $truncatedLog = strlen($buildLog) > 6000 ? substr($buildLog, 0, 6000) . "\n..." : $buildLog;

        // Extract file paths mentioned in the build error log so we only send
        // the relevant files to AI instead of the entire project.
        $errorFilePaths = $this->extractErrorFilePaths($buildLog, $currentFiles);

        $system = implode("\n", [
            'You are a senior React/Vite build engineer. The build has failed with the error log below.',
            'You receive the current project files and the build error log.',
            'Output ONLY valid JSON with this exact shape: {"files": [{"path": string, "content": string}]}.',
            'Return the minimal set of files that must change to make the Vite build pass.',
            'Do NOT start from scratch. Do NOT change the design or functionality. Only fix the build errors.',
            'Do not rename files or change their paths.',
            'Preserve Tailwind + shadcn-compatible design system and design tokens.',
            'Common fixes to apply automatically:',
            '  * Missing .jsx extension on relative imports → add .jsx.',
            '  * Missing React import → add import React from "react" when using JSX.',
            '  * Unescaped quotes inside string literals → escape them or switch quote style.',
            '  * Backslashes before JSX attributes (e.g. type="x"\ disabled) → remove the backslash.',
            '  * Duplicate JSX attributes (e.g. className="a" className="b") → merge into one: className="a b".',
            '  * Missing closing tags or mismatched JSX braces → fix them.',
            '  * Unknown Lucide icon imports → replace with a similar valid icon name.',
            '  * Undefined variables referenced in JSX → define them or import them.',
            '  * CSS/JSX syntax errors caused by stray characters → remove or escape them.',
            'Do not add markdown, code fences, or explanations.',
            'ONLY output the "files" array.',
            '',
            'Boilerplate UI files (src/components/ui/*.jsx, src/lib/utils.js, src/hooks/use-mobile.js, vite/tailwind configs, package.json, etc.) are already provided and are NOT shown in the file list below. Do not modify them unless the build error explicitly points to them.',
        ]);

        // Only send the files that are mentioned in the error log.
        // If we can't extract any file paths, fall back to sending all editable files.
        $contextFiles = $this->prepareIterationContextFiles(
            $errorFilePaths !== null ? $errorFilePaths : $currentFiles
        );

        $userPrompt = implode("\n", array_filter([
            'Build error log:',
            $truncatedLog,
            '',
            'Files with errors (fix ONLY these files):',
            json_encode($contextFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            '',
            'Return only the files that need to change to fix the build. Make sure the returned file paths exactly match the existing file paths you are editing.',
        ]));

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $userPrompt,
            12000,
            'React Build Fixer'
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            return null;
        }

        $json = is_array($result['json'] ?? null) ? $result['json'] : null;
        if (!is_array($json) || empty($json['files'] ?? null)) {
            return null;
        }

        $changedFiles = [];
        foreach ($json['files'] as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null) || !is_string($file['content'] ?? null)) {
                continue;
            }
            $path = str_replace('\\', '/', ltrim(trim((string) $file['path']), '/'));
            if (str_contains($path, '..')) {
                continue;
            }
            $changedFiles[$path] = [
                'path' => $path,
                'content' => $this->normalizeImports(trim((string) $file['content'])),
            ];
        }

        if (empty($changedFiles)) {
            return null;
        }

        $currentMap = [];
        foreach ($currentFiles as $file) {
            if (!is_array($file) || !is_string($file['path'] ?? null)) {
                continue;
            }
            $path = str_replace('\\', '/', ltrim(trim((string) $file['path']), '/'));
            $currentMap[$path] = $file;
        }

        foreach ($changedFiles as $path => $file) {
            $currentMap[$path] = $file;
        }

        return array_values($currentMap);
    }
}

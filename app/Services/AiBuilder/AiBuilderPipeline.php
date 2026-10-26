<?php

namespace App\Services\AiBuilder;

use App\Services\AI\AiProviderRouter;

class AiBuilderPipeline
{
    public function __construct(
        protected AiProviderRouter $router,
    ) {
    }

    public function generateSection(string $prompt, string $mode = 'page', ?string $screenshotUrl = null, ?string $modelOverride = null, array $pageContext = [], int $sectionIndex = 0): array
    {
        $enhancer = new PromptEnhancer($this->router);
        $worker = new SectionWorker($this->router);
        $validator = new Validator();

        $spec = $enhancer->enhance($prompt, $mode, $screenshotUrl);

        $enhancerName = $spec['sections'][0]['name'] ?? '';
        $promptName = $this->extractSectionNameFromPrompt($prompt);
        // If enhancer returns a generic name like "hero" but the user explicitly asked for something else, override it
        $sectionName = $this->isGenericName($enhancerName) && $promptName ? $promptName : ($enhancerName ?: 'Generated Section');

        $task = [
            'section_name' => $sectionName,
            'section_type' => $spec['sections'][0]['type'] ?? 'content',
            'layout_prompt' => $prompt,
            'page_context' => $pageContext,
            'section_index' => $sectionIndex,
            'model' => $modelOverride && $modelOverride !== 'auto' ? $modelOverride : null,
        ];

        $section = $worker->generate($task, $spec, $mode);
        $section = $validator->validate($section, $mode);

        return [
            'section' => $section,
            'spec' => $spec,
        ];
    }

    public function generatePage(string $prompt, string $mode = 'page', ?string $screenshotUrl = null, ?string $modelOverride = null): array
    {
        $enhancer = new PromptEnhancer($this->router);
        $orchestrator = new Orchestrator($this->router);
        $worker = new SectionWorker($this->router);
        $validator = new Validator();
        $assembler = new Assembler();

        $spec = $enhancer->enhance($prompt, $mode, $screenshotUrl);
        $tasks = $orchestrator->orchestrate($spec, $mode);

        $sections = [];
        foreach ($tasks as $task) {
            // Keep model selection in the admin settings; the orchestrator only plans layout.
            $task['model'] = $modelOverride && $modelOverride !== 'auto'
                ? $modelOverride
                : ModelRouter::resolve('worker');
            $section = $worker->generate($task, $spec, $mode);
            $section = $validator->validate($section, $mode);
            $sections[] = $section;
        }

        $page = $assembler->assemble($sections, $spec, $mode);
        $page = $validator->validatePage($page, $mode);

        return [
            'page' => $page,
            'spec' => $spec,
        ];
    }

    public function generatePageWithProgress(string $jobId, string $prompt, string $mode = 'page', ?string $screenshotUrl = null, ?string $modelOverride = null): array
    {
        $messages = AiProgressTracker::realisticMessages();
        $msgIndex = 0;
        $totalSteps = count($messages);

        $next = function () use ($jobId, &$msgIndex, $totalSteps, $messages) {
            $m = $messages[$msgIndex % $totalSteps];
            $step = ($msgIndex % $totalSteps) + 1;
            AiProgressTracker::setStatus($jobId, 'generating', $m['title'] . ' (' . $step . '/' . $totalSteps . ')');
            $msgIndex++;
        };

        try {
            $next();
            $enhancer = new PromptEnhancer($this->router);
            $spec = $enhancer->enhance($prompt, $mode, $screenshotUrl);

            $next();
            $orchestrator = new Orchestrator($this->router);
            $tasks = $orchestrator->orchestrate($spec, $mode);

            $next();
            $worker = new SectionWorker($this->router);
            $validator = new Validator();
            $sections = [];

            foreach ($tasks as $i => $task) {
                $next();
                $task['model'] = $modelOverride && $modelOverride !== 'auto'
                    ? $modelOverride
                    : ModelRouter::resolve('worker');
                $section = $worker->generate($task, $spec, $mode);
                $section = $validator->validate($section, $mode);
                $sections[] = $section;
            }

            while ($msgIndex < $totalSteps - 1) {
                $next();
            }

            $assembler = new Assembler();
            $page = $assembler->assemble($sections, $spec, $mode);
            $page = $validator->validatePage($page, $mode);

            AiProgressTracker::setDone($jobId, ['page' => $page, 'spec' => $spec]);

            return ['page' => $page, 'spec' => $spec];
        } catch (\Throwable $e) {
            AiProgressTracker::setError($jobId, $e->getMessage());
            throw $e;
        }
    }

    public function iterate(array $currentPage, string $changeRequest, string $mode = 'page'): array
    {
        $handler = new IterationHandler($this->router);
        return $handler->iterate($currentPage, $changeRequest, $mode);
    }

    private function extractSectionNameFromPrompt(string $prompt): string
    {
        $p = strtolower(trim($prompt));
        if ($p === '') {
            return '';
        }

        // Common section type keywords
        $keywords = [
            'pricing' => 'Pricing',
            'price' => 'Pricing',
            'plan' => 'Pricing',
            'feature' => 'Features',
            'benefit' => 'Features',
            'testimonial' => 'Testimonials',
            'review' => 'Testimonials',
            'faq' => 'FAQ',
            'contact' => 'Contact',
            'cta' => 'CTA',
            'call to action' => 'CTA',
            'hero' => 'Hero',
            'banner' => 'Hero',
            'about' => 'About',
            'team' => 'Team',
            'service' => 'Services',
            'portfolio' => 'Portfolio',
            'gallery' => 'Gallery',
            'blog' => 'Blog',
            'newsletter' => 'Newsletter',
            'subscribe' => 'Newsletter',
            'footer' => 'Footer',
            'header' => 'Header',
            'navbar' => 'Header',
            'stats' => 'Stats',
            'counter' => 'Stats',
            'integration' => 'Integrations',
            'partner' => 'Partners',
            'client' => 'Clients',
            'logo' => 'Logos',
            'social proof' => 'Social Proof',
        ];

        foreach ($keywords as $kw => $name) {
            if (str_contains($p, $kw)) {
                return $name;
            }
        }

        return '';
    }

    private function isGenericName(string $name): bool
    {
        $generic = ['hero', 'banner', 'header', 'section', 'content', 'default', 'generic', 'page'];
        $lower = strtolower(trim($name));
        foreach ($generic as $g) {
            if ($lower === $g || str_contains($lower, $g)) {
                return true;
            }
        }
        return false;
    }
}

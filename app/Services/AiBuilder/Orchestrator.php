<?php

namespace App\Services\AiBuilder;

use App\Services\AI\AiProviderRouter;

class Orchestrator
{
    public function __construct(
        protected AiProviderRouter $router,
    ) {
    }

    public function orchestrate(array $spec, string $mode = 'page'): array
    {
        $model = ModelRouter::resolve('orchestrator');
        if (!$this->router->hasCredentials($model)) {
            throw new \RuntimeException('AI API key is not configured for the selected provider.');
        }

        $system = implode("\n", [
            'You are a layout orchestrator. Given a design spec, output an ordered array of generation tasks.',
            'Output ONLY valid JSON (no markdown, no code fences).',
            'Schema: [{"section_index": int, "section_name": string, "section_type": string, "layout_prompt": string, "model": string, "dependencies": [string]}]',
            'The "model" field should be one of: gpt-4o-mini (simple static), gpt-4o (multi-column/form), gpt-4o (animation/hero).',
            'The "layout_prompt" should be a detailed prompt for generating that section as MailPurse builder JSON.',
            'In each layout_prompt, explicitly mention: use section style.maxWidthType "full" (spans full viewport), style.innerMaxWidth 1120 (content constrained to 1120px), style.paddingY (48-80px), style.borderRadius (12-24px for card-like sections), style.gap (16-32px).',
            'For CTA sections, remind: single full-width column, center content, use button style "outline" or "ghost" on colored backgrounds, use "primary" on light backgrounds, ensure color contrast.',
            'For HERO sections, remind: can be 1-column centered (single full-width column) OR 2-column split (content col md:6 + image col md:6). For 2 buttons side-by-side, use a "grid" widget with count:2, gap:12 inside the content column. Image can be left or right. Background can be solid color, gradient, or image. For striking visuals, consider an animated gradient (backgroundType: "gradient", gradientAnimate: true) or a subtle grid overlay (gridOverlay: true, gridSize: 40, gridColor: "rgba(255,255,255,0.08)", gridOpacity: 0.2). May include subtext like "Trusted by 200k+ people".',
            'For PRICING sections, remind: use the "pricing_table" widget (not separate heading/rich_text/button widgets). The pricing_table widget should include align, style (default, classic, dark, header, minimal, split, gradient), gap, featureIconType, and a single "plan" object with realistic data: title, description, monthlyPrice, monthlyPeriod, yearlyPrice, yearlyPeriod, badge, discount, icon, featuresTitle, features array, buttonText, buttonUrl, buttonTarget, featured boolean. Style color props include: headerBackgroundColor, headerTextColor, badgeBackgroundColor, badgeTextColor, darkBackgroundColor, darkTextColor, gradientFrom, gradientTo, cardBackgroundType (color, gradient, image), cardBackgroundColor, cardBackgroundGradientFrom, cardBackgroundGradientTo, cardBackgroundImageUrl, cardBorderColor, cardBorderRadius, buttonBackgroundColor, buttonTextColor. Do not use heading, subheading, columns, or a plans array.',
            'For FEATURES/BENEFITS sections, remind: set section style.itemsDirection to "column" so section columns stack vertically. Column 1: heading + description. Column 2: ONE "grid" widget with count:3-4 containing "icon" widgets as grid columns (one icon per column). Column 3: CTA button. Never use generic text like "Heading" or "Subtext".',
        ]);

        $user = "Design spec:\n" . json_encode($spec, JSON_PRETTY_PRINT);

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $user,
            2400,
            'Orchestrator'
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            $msg = is_string($result['message'] ?? null) ? (string) $result['message'] : 'Orchestration failed.';
            throw new \RuntimeException($msg);
        }

        $json = is_array($result['json'] ?? null) ? $result['json'] : null;
        if (!is_array($json) || !isset($json[0])) {
            throw new \RuntimeException('Orchestrator returned invalid task list.');
        }

        $tasks = [];
        foreach ($json as $i => $task) {
            if (!is_array($task)) {
                continue;
            }
            $tasks[] = [
                'section_index' => is_numeric($task['section_index'] ?? null) ? (int) $task['section_index'] : $i,
                'section_name' => is_string($task['section_name'] ?? null) ? (string) $task['section_name'] : ('Section ' . ($i + 1)),
                'section_type' => is_string($task['section_type'] ?? null) ? (string) $task['section_type'] : 'content',
                'layout_prompt' => is_string($task['layout_prompt'] ?? null) ? (string) $task['layout_prompt'] : '',
                'model' => is_string($task['model'] ?? null) ? (string) $task['model'] : ModelRouter::resolve('worker'),
                'dependencies' => is_array($task['dependencies'] ?? null) ? $task['dependencies'] : [],
            ];
        }

        return $tasks;
    }
}

<?php

namespace App\Services\AiBuilder;

use App\Services\AI\AiProviderRouter;

class PromptEnhancer
{
    public function __construct(
        protected AiProviderRouter $router,
    ) {
    }

    public function enhance(string $rawPrompt, string $mode = 'page', ?string $screenshotUrl = null): array
    {
        $model = ModelRouter::resolve('enhancer');
        if (!$this->router->hasCredentials($model)) {
            throw new \RuntimeException('AI API key is not configured for the selected provider.');
        }

        $system = $this->systemPrompt($mode);
        $user = $this->userPrompt($rawPrompt, $mode, $screenshotUrl);

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $user,
            3000,
            'Prompt Enhancer'
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            $msg = is_string($result['message'] ?? null) ? (string) $result['message'] : 'Prompt enhancement failed.';
            throw new \RuntimeException($msg);
        }

        $json = is_array($result['json'] ?? null) ? $result['json'] : null;
        if (!is_array($json)) {
            throw new \RuntimeException('Enhancer returned invalid JSON.');
        }

        return $this->normalize($json, $mode);
    }

    private function systemPrompt(string $mode): string
    {
        $widgets = implode(', ', BuilderSchema::allowedWidgetTypes($mode));

        return implode("\n", [
            'You are a design specification enhancer. Take a raw user prompt and output a structured JSON design spec.',
            'Output ONLY valid JSON (no markdown, no code fences).',
            'Schema: {"project_type": string, "industry": string, "tone": string, "sections": [{"name": string, "type": string, "description": string}], "color_palette": {"primary": string, "secondary": string, "background": string, "text": string}, "typography": {"heading_font": string, "body_font": string}, "widget_types": [string], "features": [string], "design_requirements": {"paddingY": int, "gap": int, "borderRadius": int, "button_style": string}}',
            "Allowed widget types for this mode ({$mode}): {$widgets}",
            'Keep the spec concise but actionable. Use hex colors.',
            'Choose a color palette with good contrast. The background and text colors should be clearly readable.',
            'Set design_requirements.paddingY to 64, gap to 24, and borderRadius to 16 for modern spacing.',
            'Set design_requirements.button_style to "primary" for light backgrounds and "outline" for colored/dark backgrounds.',
            'CRITICAL: The "name" field in each section MUST reflect what the user actually asked for. If the user says "pricing section", name it "Pricing" — never rename it to "hero" or another generic type. Preserve the user\'s intent exactly.',
        ]);
    }

    private function userPrompt(string $rawPrompt, string $mode, ?string $screenshotUrl): string
    {
        $parts = [];
        $parts[] = 'Mode: ' . $mode;
        $parts[] = 'Prompt: ' . $rawPrompt;
        if ($screenshotUrl) {
            $parts[] = 'Screenshot URL: ' . $screenshotUrl;
            $parts[] = 'Use the screenshot as a reference for layout and style.';
        }

        return implode("\n", $parts);
    }

    private function normalize(array $json, string $mode): array
    {
        $designReq = is_array($json['design_requirements'] ?? null) ? $json['design_requirements'] : [];

        return [
            'project_type' => is_string($json['project_type'] ?? null) ? (string) $json['project_type'] : 'landing_page',
            'industry' => is_string($json['industry'] ?? null) ? (string) $json['industry'] : 'general',
            'tone' => is_string($json['tone'] ?? null) ? (string) $json['tone'] : 'professional',
            'sections' => is_array($json['sections'] ?? null) ? $json['sections'] : [],
            'color_palette' => is_array($json['color_palette'] ?? null) ? $json['color_palette'] : [
                'primary' => '#3b82f6',
                'secondary' => '#10b981',
                'background' => '#f8fafc',
                'text' => '#111827',
            ],
            'typography' => is_array($json['typography'] ?? null) ? $json['typography'] : [
                'heading_font' => 'Inter',
                'body_font' => 'Inter',
            ],
            'widget_types' => is_array($json['widget_types'] ?? null) ? $json['widget_types'] : BuilderSchema::allowedWidgetTypes($mode),
            'features' => is_array($json['features'] ?? null) ? $json['features'] : [],
            'design_requirements' => [
                'paddingY' => is_numeric($designReq['paddingY'] ?? null) ? (int) $designReq['paddingY'] : 64,
                'gap' => is_numeric($designReq['gap'] ?? null) ? (int) $designReq['gap'] : 24,
                'borderRadius' => is_numeric($designReq['borderRadius'] ?? null) ? (int) $designReq['borderRadius'] : 16,
                'button_style' => is_string($designReq['button_style'] ?? null) ? $designReq['button_style'] : 'primary',
            ],
        ];
    }
}

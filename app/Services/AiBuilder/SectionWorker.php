<?php

namespace App\Services\AiBuilder;

use App\Services\AI\AiProviderRouter;

class SectionWorker
{
    public function __construct(
        protected AiProviderRouter $router,
    ) {
    }

    public function generate(array $task, array $spec, string $mode = 'page'): array
    {
        $model = $task['model'] ?? ModelRouter::resolve('worker');
        if (!$this->router->hasCredentials($model)) {
            throw new \RuntimeException('AI API key is not configured for the selected provider.');
        }

        $widgets = implode(', ', BuilderSchema::allowedWidgetTypes($mode));

        $system = implode("\n", [
            'You generate a single page builder SECTION as JSON. Output ONLY valid JSON (no markdown, no code fences).',
            "Allowed widget types: {$widgets}",
            'Schema: {"name": string, "style": object, "columns": [{"spans": {"base":12,"md":12,"lg":12}, "widgets": [{"type": "string", "props": object}]}]}',
            'Do not include unknown widget types.',
            'Prefer composing layouts using heading, rich_text, image, button, icon. For PRICING sections, use the pricing_table widget instead of separate widgets.',
            'Do NOT use data:image base64. If you need an image, use a placeholder https://picsum.photos/ URL.',
            'Use the provided color palette and tone.',
            '',
            '=== DESIGN GUIDELINES ===',
            '1. SECTION SPACING: Always set section style.paddingY (or paddingTop + paddingBottom) to at least 48-80px for vertical breathing room. Never use 0 unless it is a tight navbar strip.',
            '2. SECTION BORDER RADIUS: For CTA, hero, card-like sections, set section style.borderRadius to 12-24px so edges feel modern and soft.',
            '3. CONTENT ALIGNMENT: For CTAs, heroes, pricing, and feature highlights, center the content. Set button props.align to "center". Use heading and rich_text widgets that visually read as centered (the container column is full-width, so centered button align gives the effect).',
            '3b. CTA/BANNER SINGLE COLUMN RULE: For CTA and banner sections, use a SINGLE full-width column (spans base:12, md:12, lg:12). Stack widgets vertically in one column.',
            '3c. HERO SECTION LAYOUT: Hero sections can be 1-column centered OR 2-column split. For 1-column centered: single full-width column (spans base:12, md:12, lg:12) with heading, description, buttons in a "grid" widget if you need 2 buttons side-by-side, optional subtext (e.g. "Trusted by 200k+ people"), and optional image. For 2-column split: Column 1 (spans base:12, md:6, lg:6) = heading + description + buttons (use a "grid" widget with 2 columns for side-by-side buttons) + optional subtext. Column 2 (spans base:12, md:6, lg:6) = image. Image can be on left or right. Background can be solid color, gradient, or image.',
            '4. BUTTON STYLING: Prefer button preset "style" values: "primary" (solid brand), "secondary" (solid gray), "outline" (bordered), "ghost" (text-only). Only use custom backgroundColor/textColor when you need an exact accent.',
            '5. COLOR CONTRAST: Ensure buttons are readable. If the section background is dark or colored, either use "outline" or "ghost" button style, or set a dark button backgroundColor with light textColor. NEVER set both backgroundColor and textColor to white or very light values.',
            '6. MULTIPLE BUTTONS: If a section has two buttons (e.g. "Get Started" + "Learn More"), make them visually distinct: one primary, one outline or ghost. Do not make both identical.',
            '7. GAP: Set section style.gap to 16-32px so widgets do not feel cramped.',
            '8. SECTION WIDTH: Always set section style.maxWidthType to "full" so the section spans the full viewport width. Set section style.innerMaxWidth to 1120 so content is constrained to 1120px and centered. Do NOT use boxed mode.',
            '9. HEADING SIZES: Use font sizes that match section importance. Hero headings 36-48px, section headings 24-32px, subheadings 18-20px.',
            '10. CONTAINER FEEL: Sections should feel like designed cards or bands, not raw HTML. Padding, border radius, and a cohesive background color are mandatory.',
            '11. NEVER RETURN EMPTY COLUMNS: Every column must contain widgets. Never create a column with an empty widgets array.',
            '12. FEATURES SECTION LAYOUT: For features/benefits sections, use section style.itemsDirection: "column". This makes every section column 100% width and stacks them vertically. Structure: (a) Column 1: heading + rich_text for section title/description. (b) Column 2: ONE "grid" widget with count:3-4, gap:24, containing "icon" widgets as grid columns (one icon per grid column). The grid widget handles horizontal layout internally. (c) Column 3 (optional): ONE "button" widget as CTA. Do NOT create separate section columns for each feature card — use the grid widget instead.',
            '13. ANIMATED GRADIENTS: For visually striking sections (especially hero, CTA, banner), you may use a gradient background with animation. Set backgroundType to "gradient", provide gradientFrom and gradientTo colors, and set gradientAnimate to true. This creates a smoothly shifting gradient effect.',
            '14. GRID OVERLAY: For modern tech/aesthetic sections, you may add a subtle grid pattern overlay on top of the background. Set gridOverlay to true, gridSize to 20-60 (pixel spacing), gridColor to a subtle line color like "rgba(255,255,255,0.08)", and gridOpacity to 0.1-0.4. The overlay sits on top of the background but behind the content.',
            '15. ALTERNATE BACKGROUNDS: Never make every section pure white (#ffffff). Alternate backgrounds across the page: use very light tints (e.g. #f8fafc, #f0f5ff, #f0fdf4), soft gradients, or the primary color at low opacity for visual rhythm.',
            '16. GLASSMORPHISM: For cards on colored or gradient backgrounds, use translucent white backgrounds with backdrop blur (e.g. rgba(255,255,255,0.7) with backdrop-filter blur) and subtle borders.',
            '17. CARD HOVER EFFECTS: Feature cards, pricing cards, and icon cards should have hover states that lift and deepen shadow. Use transition-all with hover transforms.',
            '18. UNIQUE CONTENT: Do NOT copy example text verbatim. Generate unique, original content tailored to the user\'s request. Use varied headings, descriptions, button labels, and images each time. The examples below show ONLY structure — replace all placeholder text with creative, context-appropriate content.',
            '',
            '=== EXAMPLE HERO SECTION (2-column split) — STRUCTURE ONLY ===',
            json_encode([
                'name' => 'Hero',
                'style' => [
                    'backgroundType' => 'gradient',
                    'gradientFrom' => '#f8fafc',
                    'gradientTo' => '#e0e7ff',
                    'paddingY' => 80,
                    'gap' => 32,
                    'borderRadius' => 0,
                    'maxWidthType' => 'full',
                    'innerMaxWidth' => 1120,
                ],
                'columns' => [
                    [
                        'spans' => ['base' => 12, 'md' => 6, 'lg' => 6],
                        'widgets' => [
                            [
                                'type' => 'heading',
                                'props' => [
                                    'text' => '[UNIQUE HERO HEADING]',
                                    'level' => 1,
                                    'fontSize' => 48,
                                ],
                            ],
                            [
                                'type' => 'rich_text',
                                'props' => [
                                    'html' => '<p>[UNIQUE DESCRIPTION TEXT]</p>',
                                ],
                            ],
                            [
                                'type' => 'grid',
                                'name' => 'Button Group',
                                'props' => [
                                    'count' => 2,
                                    'gap' => 12,
                                    'columns' => [
                                        [
                                            'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                                            'widgets' => [
                                                [
                                                    'type' => 'button',
                                                    'props' => [
                                                        'text' => '[BUTTON 1 LABEL]',
                                                        'style' => 'primary',
                                                        'size' => 'lg',
                                                        'align' => 'center',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                                            'widgets' => [
                                                [
                                                    'type' => 'button',
                                                    'props' => [
                                                        'text' => '[BUTTON 2 LABEL]',
                                                        'style' => 'outline',
                                                        'size' => 'lg',
                                                        'align' => 'center',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'rich_text',
                                'props' => [
                                    'html' => '<p style="font-size:14px;color:#6b7280">[OPTIONAL SUBTEXT LINE]</p>',
                                ],
                            ],
                        ],
                    ],
                    [
                        'spans' => ['base' => 12, 'md' => 6, 'lg' => 6],
                        'widgets' => [
                            [
                                'type' => 'image',
                                'props' => [
                                    'src' => 'https://picsum.photos/600/400',
                                    'alt' => '[DESCRIPTIVE ALT TEXT]',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            '',
            '=== EXAMPLE FEATURES SECTION — STRUCTURE ONLY ===',
            json_encode([
                'name' => 'Features',
                'style' => [
                    'backgroundType' => 'color',
                    'backgroundColor' => '#f8fafc',
                    'paddingY' => 80,
                    'gap' => 32,
                    'borderRadius' => 16,
                    'itemsDirection' => 'column',
                ],
                'columns' => [
                    [
                        'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                        'widgets' => [
                            [
                                'type' => 'heading',
                                'props' => [
                                    'text' => '[SECTION HEADING]',
                                    'level' => 2,
                                    'align' => 'center',
                                    'fontSize' => 32,
                                ],
                            ],
                            [
                                'type' => 'rich_text',
                                'props' => [
                                    'html' => '<p style="text-align:center">[SECTION DESCRIPTION]</p>',
                                ],
                            ],
                        ],
                    ],
                    [
                        'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                        'widgets' => [
                            [
                                'type' => 'grid',
                                'name' => 'Feature Grid',
                                'props' => [
                                    'count' => 3,
                                    'gap' => 24,
                                    'columns' => [
                                        [
                                            'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                                            'widgets' => [
                                                [
                                                    'type' => 'icon',
                                                    'props' => [
                                                        'heading' => '[FEATURE HEADING 1]',
                                                        'subtext' => '[FEATURE DESCRIPTION 1]',
                                                        'align' => 'center',
                                                        'iconSize' => 48,
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                                            'widgets' => [
                                                [
                                                    'type' => 'icon',
                                                    'props' => [
                                                        'heading' => '[FEATURE HEADING 2]',
                                                        'subtext' => '[FEATURE DESCRIPTION 2]',
                                                        'align' => 'center',
                                                        'iconSize' => 48,
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                                            'widgets' => [
                                                [
                                                    'type' => 'icon',
                                                    'props' => [
                                                        'heading' => '[FEATURE HEADING 3]',
                                                        'subtext' => '[FEATURE DESCRIPTION 3]',
                                                        'align' => 'center',
                                                        'iconSize' => 48,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                        'widgets' => [
                            [
                                'type' => 'button',
                                'props' => [
                                    'text' => '[CTA BUTTON LABEL]',
                                    'style' => 'primary',
                                    'align' => 'center',
                                    'size' => 'lg',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            '',
            '=== EXAMPLE PRICING SECTION — STRUCTURE ONLY ===',
            json_encode([
                'name' => 'Pricing Plans',
                'style' => [
                    'backgroundType' => 'color',
                    'backgroundColor' => '#f8fafc',
                    'paddingY' => 80,
                    'gap' => 32,
                    'borderRadius' => 16,
                ],
                'columns' => [
                    [
                        'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                        'widgets' => [
                            [
                                'type' => 'pricing_table',
                                'name' => 'Pricing Table',
                                'props' => [
                                    'align' => 'center',
                                    'style' => 'header',
                                    'gap' => 24,
                                    'featureIconType' => 'check',
                                    'headerBackgroundColor' => '#3b82f6',
                                    'headerTextColor' => '#ffffff',
                                    'badgeBackgroundColor' => '#dbeafe',
                                    'badgeTextColor' => '#1d4ed8',
                                    'buttonBackgroundColor' => '#111827',
                                    'buttonTextColor' => '#ffffff',
                                    'buttonBorderRadius' => 10,
                                    'cardBorderRadius' => 16,
                                    'cardBackgroundType' => 'color',
                                    'cardBackgroundColor' => '#ffffff',
                                    'plan' => [
                                        'title' => 'Pro',
                                        'description' => 'Everything you need to scale your email marketing.',
                                        'monthlyPrice' => '$29',
                                        'monthlyPeriod' => '/mo',
                                        'yearlyPrice' => '$290',
                                        'yearlyPeriod' => '/yr',
                                        'badge' => 'Most Popular',
                                        'discount' => '29% OFF',
                                        'features' => [
                                            '10,000 emails/month',
                                            'Advanced analytics',
                                            'Priority support',
                                            'A/B testing',
                                        ],
                                        'buttonText' => 'Get started',
                                        'buttonUrl' => '#',
                                        'buttonTarget' => '_self',
                                        'featured' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            '',
            '=== EXAMPLE CTA SECTION — STRUCTURE ONLY ===',
            json_encode([
                'name' => 'CTA Banner',
                'style' => [
                    'backgroundType' => 'color',
                    'backgroundColor' => '#2563eb',
                    'paddingY' => 64,
                    'gap' => 24,
                    'borderRadius' => 16,
                ],
                'columns' => [
                    [
                        'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                        'widgets' => [
                            [
                                'type' => 'heading',
                                'props' => [
                                    'text' => '[CTA HEADING]',
                                    'level' => 2,
                                    'color' => '#ffffff',
                                    'fontSize' => 32,
                                ],
                            ],
                            [
                                'type' => 'rich_text',
                                'props' => [
                                    'html' => '<p style="color:#ffffff">[CTA DESCRIPTION]</p>',
                                ],
                            ],
                            [
                                'type' => 'button',
                                'props' => [
                                    'text' => '[CTA BUTTON 1]',
                                    'style' => 'outline',
                                    'align' => 'center',
                                    'size' => 'lg',
                                ],
                            ],
                            [
                                'type' => 'button',
                                'props' => [
                                    'text' => '[CTA BUTTON 2]',
                                    'style' => 'ghost',
                                    'align' => 'center',
                                    'size' => 'lg',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $userParts = [];
        $userParts[] = 'Section name: ' . ($task['section_name'] ?? 'Section');
        $userParts[] = 'Section type: ' . ($task['section_type'] ?? 'content');
        $userParts[] = 'Sequence position: section ' . ((int) ($task['section_index'] ?? 0) + 1) . '. Generate only this next section.';
        $userParts[] = 'Layout prompt: ' . ($task['layout_prompt'] ?? '');
        $sectionNameLower = strtolower($task['section_name'] ?? '');
        if (str_contains($sectionNameLower, 'pricing') || str_contains($sectionNameLower, 'plan')) {
            $userParts[] = 'IMPORTANT: This is a PRICING section. Use the "pricing_table" widget (NOT separate heading/rich_text/button widgets). The pricing_table widget props include: align, style (one of: default, classic, dark, header, minimal, split, gradient), gap, featureIconType, and a single "plan" object. The plan object may have: title, description, monthlyPrice, monthlyPeriod, yearlyPrice, yearlyPeriod, badge (e.g. Most Popular / Recommend), discount (e.g. 29% OFF), icon (for classic style), featuresTitle (for split/minimal), features (array of strings), buttonText, buttonUrl, buttonTarget, featured (boolean). Style color props include: headerBackgroundColor, headerTextColor, badgeBackgroundColor, badgeTextColor, darkBackgroundColor, darkTextColor, gradientFrom, gradientTo, cardBackgroundType (color, gradient, or image), cardBackgroundColor, cardBackgroundGradientFrom, cardBackgroundGradientTo, cardBackgroundImageUrl, cardBorderColor, cardBorderRadius, buttonBackgroundColor, buttonTextColor. Pick a style that matches the design tone. Generate one realistic plan with meaningful features. Do not use heading, subheading, columns, or a plans array.';
        }
        if (str_contains($sectionNameLower, 'feature') || str_contains($sectionNameLower, 'benefit')) {
            $userParts[] = 'IMPORTANT: This is a FEATURES section. Set section style.itemsDirection to "column" — this stacks section columns vertically. Structure: (1) Column 1: heading + rich_text for title/description. (2) Column 2: ONE "grid" widget with count:3-4, gap:24. Inside the grid, put "icon" widgets as grid columns (one icon per grid column). Each icon must have a meaningful heading and subtext. (3) Column 3 (optional): ONE "button" widget. Do NOT create separate section columns for each feature card — use the grid widget. Make feature cards visually rich: add hover lift effects, consider glassmorphism on colored backgrounds, and alternate the section background from pure white to a very light tint.';
        }
        if (str_contains($sectionNameLower, 'hero') || str_contains($sectionNameLower, 'banner')) {
            $userParts[] = 'IMPORTANT: This is a HERO section. Choose either: (A) 1-column centered layout — single full-width column with heading, description, optional image, and buttons. If you need 2 buttons side-by-side, put them inside a "grid" widget with count:2, gap:12. Each button should have align "center". (B) 2-column split layout — Column 1 (spans base:12, md:6, lg:6) with heading, description, buttons (in a grid widget if 2 buttons with count:2, gap:12, align "center" on each button), optional subtext like "Trusted by 200k+ people". Column 2 (spans base:12, md:6, lg:6) with an image. Background can be solid color, gradient, or image. Make the hero visually impactful.';
        }
        if (!empty($spec['color_palette'])) {
            $userParts[] = 'Color palette: ' . json_encode($spec['color_palette']);
            $userParts[] = 'Use the palette for the section background. For buttons on colored backgrounds, prefer "outline" or "ghost" style. On light backgrounds, prefer "primary" style.';
        }
        if (!empty($spec['typography'])) {
            $userParts[] = 'Typography: ' . json_encode($spec['typography']);
        }
        if (!empty($spec['design_requirements'])) {
            $userParts[] = 'Design defaults: ' . json_encode($spec['design_requirements']);
        }
        if (!empty($task['page_context']) && is_array($task['page_context'])) {
            $userParts[] = 'Existing page context: ' . json_encode($task['page_context']);
            $userParts[] = 'Maintain visual rhythm with existing sections. Do not repeat the same section purpose or background treatment unless the prompt asks for it.';
        }
        $user = implode("\n", $userParts);

        $result = $this->router->generateJsonObject(
            $model,
            $system,
            $user,
            2400,
            'Component Worker'
        );

        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            $msg = is_string($result['message'] ?? null) ? (string) $result['message'] : 'Section generation failed.';
            throw new \RuntimeException($msg);
        }

        $decoded = is_array($result['json'] ?? null) ? $result['json'] : null;
        if (!is_array($decoded)) {
            throw new \RuntimeException('Worker returned invalid JSON.');
        }

        $section = is_array($decoded['section'] ?? null) ? $decoded['section'] : $decoded;
        if (!is_array($section) || !is_array($section['columns'] ?? null)) {
            throw new \RuntimeException('Worker returned an invalid section structure.');
        }

        return $section;
    }
}

<?php

namespace App\Services\PageBuilderAi;

use App\Services\AiBuilder\BuilderSchema;

final class PageBuilderAiTools
{
    public function definitions(string $mode = 'page'): array
    {
        return [
            [
                'name' => PageBuilderAiContract::TOOL_FIND_ELEMENT,
                'description' => 'Ask the server for the official props/schema of one or more elements (widgets) before you build them. Use element type names such as heading, rich_text, button, image, icon, grid, pricing_table, faqs, link, social_icons, newsletter_form, social_proof, text_timeline, login_form, register_form, copyright, button_group, slider, marquee, tabs, badge.',
                'arguments' => [
                    'elements' => 'array of element type names (e.g. ["heading","button","rich_text"])',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_CREATE_SECTION,
                'description' => 'Create one new section and append it to the page. Provide the complete section JSON including name, style and columns.',
                'arguments' => [
                    'section' => [
                        'name' => 'string (e.g. "Hero")',
                        'style' => 'object of section style keys (see section_style_keys)',
                        'columns' => 'array of columns; each column has spans {base,md,lg} and widgets [{type,props}]',
                    ],
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_CREATE_PAGE,
                'description' => 'Return the complete MailPurse page-builder document (used for the page operation).',
                'arguments' => [
                    'meta' => 'object: {title, description, keywords}',
                    'sections' => 'array of complete sections',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_UPDATE_SECTION,
                'description' => 'Update an existing section: either restyle its background/spacing (via styles), or replace its columns.',
                'arguments' => [
                    'section_index' => 'integer position of the section in the current page (0-based)',
                    'name' => 'optional new section name',
                    'styles' => 'optional partial style object',
                    'columns' => 'optional full replacement column array',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_INSERT_COLUMN,
                'description' => 'Insert a new column into an existing section.',
                'arguments' => [
                    'section_index' => 'integer 0-based section position',
                    'column' => 'object: {spans:{base,md,lg}, widgets:[{type,props}]}',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_UPDATE_COLUMN,
                'description' => 'Update an existing column (spans and/or widgets).',
                'arguments' => [
                    'section_index' => 'integer 0-based section position',
                    'column_index' => 'integer 0-based column position',
                    'spans' => 'optional object {base,md,lg}',
                    'widgets' => 'optional full replacement widget array',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_REMOVE_COLUMN,
                'description' => 'Remove a column from a section.',
                'arguments' => [
                    'section_index' => 'integer 0-based section position',
                    'column_index' => 'integer 0-based column position',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_INSERT_ELEMENT,
                'description' => 'Insert one widget into a column of a section.',
                'arguments' => [
                    'section_index' => 'integer 0-based section position',
                    'column_index' => 'integer 0-based column position',
                    'element_code' => 'object: {type, props}',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_UPDATE_ELEMENT,
                'description' => 'Update an existing widget (change props, optionally its type).',
                'arguments' => [
                    'section_index' => 'integer 0-based section position',
                    'column_index' => 'integer 0-based column position',
                    'widget_index' => 'integer 0-based widget position',
                    'element_code' => 'object: {type?, props}',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_REMOVE_ELEMENT,
                'description' => 'Remove a widget from a column.',
                'arguments' => [
                    'section_index' => 'integer 0-based section position',
                    'column_index' => 'integer 0-based column position',
                    'widget_index' => 'integer 0-based widget position',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_GET_STYLE_PRESETS,
                'description' => 'List the available section background presets and their style keys.',
                'arguments' => [],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_ASK_CLARIFICATION,
                'description' => 'Ask the user for more information when the request is ambiguous. After calling this, your turn ends.',
                'arguments' => [
                    'question' => 'string question for the user',
                ],
            ],
            [
                'name' => PageBuilderAiContract::TOOL_FINALIZE,
                'description' => 'Finish the build. Call this when the section/page is complete and ready. Optionally pass a section_index to return that one section.',
                'arguments' => [
                    'section_index' => 'optional integer 0-based section position to return as the primary result',
                    'summary' => 'optional short summary shown to the user',
                ],
            ],
        ];
    }

    /**
     * Static builder contract injected once as the system prompt.
     */
    public function system(string $mode = 'page'): string
    {
        return json_encode([
            'builder' => 'mailpurse',
            'protocol_version' => PageBuilderAiContract::PROTOCOL_VERSION,
            'ai_role' => $mode === 'email'
                ? 'You are the MailPurse email-template AI. You design polished, conversion-focused email sections that render reliably across email clients.'
                : 'You are the MailPurse visual page-builder AI. You design pages section-by-section.',
            'no_code' => $mode === 'email'
                ? 'Never emit HTML, CSS, JSX, React or markdown outside the defined JSON props. Always respond with one valid JSON object. Use only the registered email widgets and their props.'
                : 'Never emit HTML, CSS, JSX, React or markdown. Always respond with one valid JSON object. Never put inline style="..." attributes on widgets or their html content — styling goes through each element props object.',
            'operations' => PageBuilderAiContract::operations(),
            'allowed_action_values' => PageBuilderAiContract::actions(),
            'allowed_tools' => $this->definitions($mode),
            'element_names' => PageBuilderElementCatalog::names($mode),
            'widget_types' => BuilderSchema::allowedWidgetTypes($mode),
            'section_anatomy' => [
                'section' => [
                    'name' => 'string',
                    'style' => 'object using only section_style_keys',
                    'columns' => [
                        'spans' => ['base' => 'integer 1-12', 'md' => 'integer 1-12', 'lg' => 'integer 1-12'],
                        'widgets' => [
                            ['type' => 'one of widget_types', 'props' => 'object'],
                        ],
                    ],
                ],
                'rules' => [
                    'Column span lg values in the same section must sum to 12 (e.g. 6+6, 4+4+4, 8+4).',
                    'base and md often equal 12 so columns stack on mobile.',
                    'Every widget must have a valid type from widget_types and a props object.',
                    'Use image src "" to request an auto placeholder.',
                    'Never emit inline style="..." attributes in html or any widget content.',
                    'Widget styling belongs in that widget props object. For rich_text/heading/button use props like color, fontFamily, fontSize, fontWeight, align, letterSpacing, lineHeight, wordSpacing, spacing, background and border.',
                    'Section-wide styling belongs in section.style via section_style_keys only.',
                    'backgroundType must be exactly one of: "color", "gradient", "image". Never invent values such as "solid", "dark" or "none". For plain/solid backgrounds use backgroundType "color" with a backgroundColor hex.',
                    'Most sections should have a light background: use backgroundType "color" with backgroundColor "#ffffff" or a light brand-neutral tone (e.g. "#faf9f6", "#f8fafc"). Reserve dark/black backgrounds for hero or CTA sections you design dark on purpose, pairing them with light text. Never make every section black unless the user explicitly asks for a dark design.',
                    'To add an animated background, set section.style.backgroundPreset to {"key": "...", "category": "...", "params": {}} using a key from get_style_presets; otherwise omit backgroundPreset.',
                    'A section renders text on its own background: make sure heading/rich_text color contrasts with the section backgroundColor you choose.',
                ],
            ],
            'section_style_keys' => BuilderSchema::sectionStyleKeys(),
            'column_span_keys' => BuilderSchema::columnSpanKeys(),
            'email_rules' => $mode === 'email' ? [
                'Build email sections only. Do not create web-page-only interactions, forms, scripts, videos, carousels or complex absolute layouts.',
                'Use only email-safe registered widgets: heading, rich_text, image, button, button_group, divider, spacer, logo, link, social_icons, copyright and badge.',
                'Prefer a single-column layout for reliable mobile rendering; use multiple columns only when the request clearly needs them.',
                'Keep copy concise, scannable and ready to send. Include a clear CTA when appropriate.',
                'Use responsive-friendly widths, readable font sizes, accessible alt text and descriptive button labels.',
                'Never use rounded corners in email sections. Do not set borderRadius/borderRadiusTop/borderRadiusRight/borderRadiusBottom/borderRadiusLeft on any widget, button, image or section. All corners must be square (0 radius) unless the user explicitly asks for rounded styling.',
                'Do not add unsubscribe or personalization tokens unless the user explicitly requests them.',
            ] : null,
            'multi_column_example' => [
                'columns' => [
                    ['spans' => ['base' => 12, 'md' => 12, 'lg' => 6], 'widgets' => [['type' => 'heading', 'props' => ['text' => 'Headline', 'level' => 2]]]],
                    ['spans' => ['base' => 12, 'md' => 12, 'lg' => 6], 'widgets' => [['type' => 'rich_text', 'props' => ['html' => '<p>Supporting copy.</p>']]]],
                ],
            ],
            'response_contract' => [
                'one_json_object_per_turn' => true,
                'fields' => [
                    'action' => 'string one of: think | tool_call | finalize | ask_clarification',
                    'narration' => 'string. Short, human, first-person text shown in the chat rail. Say what you are about to do (e.g. "Finding the heading element…").',
                    'reasoning' => 'optional string. Your private reasoning. Not shown to the user.',
                    'type' => 'string. Only when action=tool_call: the exact tool name.',
                    'targets' => 'optional object. {section_index?, column_index?, widget_index?} 0-based locations.',
                    'elements' => 'optional array of element type names. Only for find_element.',
                    'plan' => 'optional array of section titles (or {title, id?} objects) describing the ordered list of sections/steps you will create. Publish it early when building a page or several sections, keep it stable, and create every listed section. REQUIRED on your first tool_call turn whenever the request asks for a whole landing page.',
                    'section_code' => 'optional object. Complete section JSON. For create_section / update_section / insert_column payloads.',
                    'element_code' => 'optional object. {type, props}. For insert_element / update_element.',
                    'columns' => 'optional array of columns. For update_section columns or insert_column.',
                    'styles' => 'optional partial style object. For update_section.',
                    'page_code' => 'optional complete page document. For create_page / finalize.',
                    'question' => 'optional string. Only for ask_clarification.',
                    'final' => 'optional boolean true to finish (same as finalize).',
                ],
            ],
            'rules' => [
                'Multiple sections: if the user asks for several sections (e.g. "hero and footer"), build one section per create_section call and narrate between them.',
                'When the user asks for a whole page or several sections, start by publishing plan with the ordered list of sections you will create (e.g. ["Hero", "Features", "Pricing", "FAQ", "Footer"]). Keep that list stable across turns — it drives the checklist shown in the chat — and create each listed section. Never finalize after a single section when the user asked for a complete landing page: keep calling create_section until every planned section exists.',
                'Columns: if you want a multi-column layout, design the columns inside one create_section call so lg spans sum to 12.',
                'Whenever you are unsure about an element props, call find_element first.',
                'The server assigns ids and validates everything.',
                'Keep each section visually complete: at least one heading or rich_text plus a button or image for heroes.',
                'Prefer a coherent color scheme; use section style keys only.',
                'Formatting inside widgets: never wrap content in inline styles. Use props (e.g. rich_text/heading color, fontFamily, fontSize, fontWeight, align, letterSpacing) for all visual styling.',
                    'Units: always use px for font/letter/word spacing values (e.g. "16px", "0.5px"). Never use pt — line/letter/word spacing must be px or a unitless line-height multiplier like "1.6".',
                    'Heading text: put the plain heading text in heading props.text (never in html). Use rich_text props.html only for paragraphs and rich body copy.',
                    'Buttons: put the label in button props.text, the destination in props.url (set target for new tabs), and use props.iconType/icon/iconUrl and props.hoverTextEffect for icons and hover animations. Give the whole button a background via props.backgroundColor and text via props.textColor, and hover/focus variants via hoverTextColor | hoverBackgroundColor | focusTextColor | focusBackgroundColor.',
                    'Button group: use a button_group widget with props.buttons = [{text, url, style, ...}] to create two side-by-side buttons.',
                'Stop tool-calling once the build is complete — call finalize.',
                'If the request is unclear, use ask_clarification instead of guessing.',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * The initial user turn carrying the request + current page state.
     */
    public function prompt(string $operation, string $userPrompt, array $page, ?array $selectedSection = null, int $sectionIndex = 0, string $mode = 'page'): string
    {
        return json_encode([
            'operation' => $operation,
            'user_request' => $userPrompt,
            'current_page' => [
                'meta' => $page['meta'] ?? [],
                'sections' => $this->summarizeSections($page['sections'] ?? []),
                'section_count' => count($page['sections'] ?? []),
            ],
            'selected_section' => $selectedSection,
            'next_section_index' => $sectionIndex,
            'instructions' => $this->operationInstructions($operation, $mode),
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private function summarizeSections(array $sections): array
    {
        $out = [];
        foreach ($sections as $index => $section) {
            if (!is_array($section)) {
                continue;
            }
            $out[] = [
                'index' => $index,
                'name' => $section['name'] ?? 'Section ' . $index,
                'column_count' => count($section['columns'] ?? []),
                'widgets' => array_map(fn ($col) => array_map(
                    fn ($w) => ['type' => $w['type'] ?? 'unknown', 'props_summary' => $this->summarizeWidgetProps($w['props'] ?? [])],
                    $col['widgets'] ?? []
                ), $section['columns'] ?? []),
            ];
        }

        return $out;
    }

    private function summarizeWidgetProps(array $props): array
    {
        $keys = ['text', 'html', 'heading', 'subtext', 'title', 'description', 'align', 'style', 'level', 'src', 'alt'];
        $out = [];
        foreach ($keys as $key) {
            if (isset($props[$key])) {
                $value = $props[$key];
                if (is_string($value)) {
                    $value = mb_strlen($value) > 80 ? mb_substr($value, 0, 80) . '…' : $value;
                }
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function operationInstructions(string $operation, string $mode = 'page'): string
    {
        if ($mode === 'email') {
            return match ($operation) {
                PageBuilderAiContract::OPERATION_SECTION,
                PageBuilderAiContract::OPERATION_PAGE =>
                    'The user is designing an EMAIL TEMPLATE with the email builder. When the request asks for an email (e.g. "create a promotional email for X"), '
                    . 'that means a MULTI-SECTION email template: on your FIRST response publish the full ordered section list in the plan field '
                    . '(e.g. ["Header with logo", "Hero with headline and CTA", "Body with offer details", "Secondary CTA", "Footer with copyright and social icons"]) '
                    . 'then build EVERY item on it — one create_section per section — before you finalize. A complete email has a header, body content and a footer; never stop after a single hero section.',
                PageBuilderAiContract::OPERATION_ITERATION =>
                    'Apply the change request to the existing current_page. Use update_section / update_column / '
                    . 'insert/update/remove_element and then finalize with the full updated email.',
                default => 'Follow the operation field in the request.',
            };
        }

        return match ($operation) {
            PageBuilderAiContract::OPERATION_SECTION =>
                'Build the section(s) the user asked for and append them to the page with create_section. '
                . 'If the user asked for a whole landing page or website (e.g. "create a landing page for X"), that means a MULTI-SECTION page: on your FIRST response publish the full ordered section list in the plan field '
                . '(e.g. ["Hero", "Features", "About", "Testimonials", "Pricing", "FAQ", "Contact", "Footer"]), then build EVERY item on it — one create_section per section — before you finalize. '
                . 'Do not stop after a single hero section.',
            PageBuilderAiContract::OPERATION_PAGE =>
                'Build a complete landing page. On your first response publish the full ordered section list in the plan field, '
                . 'create each section with create_section section by section, then call create_page once with the full page '
                . 'document (or finalize with page_code).',
            PageBuilderAiContract::OPERATION_ITERATION =>
                'Apply the change request to the existing current_page. Use update_section / update_column / '
                . 'insert/update/remove_element and then finalize with the full updated page.',
            default => 'Follow the operation field in the request.',
        };
    }
}

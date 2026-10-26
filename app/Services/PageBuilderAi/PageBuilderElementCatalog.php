<?php

namespace App\Services\PageBuilderAi;

/**
 * The element catalog the AI can ask for via the `find_element` tool.
 * Each entry describes the props an element accepts in the MailPurse page
 * builder so the AI can build valid `element_code` JSON on its own.
 */
final class PageBuilderElementCatalog
{
    public static function all(string $mode = 'page'): array
    {
        $catalog = [
            'heading' => [
                'label' => 'Heading',
                'description' => 'A text heading (H1-H6).',
                'props' => [
                    'text' => 'string (required). The heading text.',
                    'level' => 'integer 1-6 (default 2)',
                    'align' => 'left|center|right',
                    'color' => 'hex color e.g. "#111827"',
                    'fontFamily' => 'string font family',
                    'fontSize' => 'string e.g. "48px" (use px, never pt)',
                    'fontWeight' => 'string e.g. "700"',
                    'lineHeight' => 'string like "1.2" (unitless multiplier) or "32px" (use px, never pt)',
                    'letterSpacing' => 'string e.g. "0.5px" (use px, never pt)',
                    'wordSpacing' => 'string e.g. "2px" (use px, never pt)',
                    'bold' => 'boolean',
                ],
                'default' => ['text' => 'Heading', 'level' => 2],
            ],
            'rich_text' => [
                'label' => 'Text / Paragraph',
                'description' => 'A block of formatted text or HTML. html must be plain semantic HTML (p, strong, em, br, ul/ol/li, a, span) with NO inline style attributes — style this element with its props below instead.',
                'props' => [
                    'html' => 'string of simple semantic HTML or plain text (required), no inline styles',
                    'align' => 'left|center|right',
                    'color' => 'hex color e.g. "#111827"',
                    'fontFamily' => 'string font family',
                    'fontSize' => 'string e.g. "18px"',
                    'fontWeight' => 'string e.g. "400"',
                    'lineHeight' => 'string e.g. "1.6" or number',
                    'letterSpacing' => 'string e.g. "0.5px"',
                    'wordSpacing' => 'string e.g. "2px"',
                ],
                'default' => ['html' => '<p>Write your paragraph here.</p>', 'align' => 'left', 'color' => '#111827', 'fontSize' => '18px', 'lineHeight' => '1.6'],
            ],
            'image' => [
                'label' => 'Image',
                'description' => 'An image or media placeholder.',
                'props' => [
                    'src' => 'https URL (required). Use a real Unsplash/Pexels URL or leave empty',
                    'alt' => 'string (required)',
                    'url' => 'link when clicked',
                    'width' => 'number',
                    'height' => 'number',
                    'borderRadius' => 'number',
                ],
                'default' => ['src' => '', 'alt' => 'Image'],
            ],
            'button' => [
                'label' => 'Button',
                'description' => 'A clickable action button with optional icon and link.',
                'props' => [
                    'text' => 'string (required)',
                    'url' => 'string href. Empty "#" renders a plain button',
                    'target' => '_self|_blank',
                    'iconType' => 'none|preset|custom',
                    'icon' => 'preset icon name: arrow-right|arrow-left|download|external|check|plus|heart|star',
                    'iconUrl' => 'https URL for a custom icon image (when iconType=custom)',
                    'iconPosition' => 'left|right',
                    'hoverTextEffect' => 'none|swap-up|swap-icon|slide-icon-right',
                    'style' => 'primary|secondary|outline|ghost',
                    'size' => 'xs|sm|md|lg|xl',
                    'align' => 'left|center|right',
                    'fullWidth' => 'boolean',
                    'color' => 'hex text color e.g. "#ffffff" (alias: textColor)',
                    'textColor' => 'hex text color',
                    'backgroundColor' => 'hex background color',
                    'hoverTextColor' => 'hex text color on hover',
                    'hoverBackgroundColor' => 'hex background color on hover',
                    'focusTextColor' => 'hex text color on focus',
                    'focusBackgroundColor' => 'hex background color on focus',
                    'fontFamily' => 'string font family',
                    'fontSize' => 'string e.g. "16px" (use px, never pt)',
                    'fontWeight' => 'string e.g. "600"',
                    'lineHeight' => 'string like "1.4" or "24px" (use px, never pt)',
                    'letterSpacing' => 'string e.g. "0.5px" (use px, never pt)',
                    'wordSpacing' => 'string e.g. "2px" (use px, never pt)',
                    'borderRadiusTop' => 'number px',
                    'borderRadiusRight' => 'number px',
                    'borderRadiusBottom' => 'number px',
                    'borderRadiusLeft' => 'number px',
                    'borderWidthTop' => 'number px',
                    'borderWidthRight' => 'number px',
                    'borderWidthBottom' => 'number px',
                    'borderWidthLeft' => 'number px',
                    'borderColor' => 'hex color',
                    'borderStyle' => 'solid|dashed|dotted|none',
                    'width' => 'number px',
                    'height' => 'number px',
                ],
                'default' => ['text' => 'Get Started', 'url' => '#', 'style' => 'primary', 'size' => 'md'],
            ],
            'button_group' => [
                'label' => 'Button Group',
                'description' => 'A row of buttons. Provide a buttons array; it is expanded into the per-button prefixed props the editor uses.',
                'props' => [
                    'align' => 'left|center|right',
                    'direction' => 'horizontal|vertical',
                    'gap' => 'number px',
                    'buttons' => 'array of {text (required), url, target, style, size, iconType, icon, iconUrl, iconPosition, hoverTextEffect}',
                ],
                'default' => ['align' => 'left', 'direction' => 'horizontal', 'gap' => 16],
            ],
            'icon' => [
                'label' => 'Icon Box / Feature Card',
                'description' => 'An icon with a heading and short text. Ideal for feature grids.',
                'props' => [
                    'heading' => 'string (required)',
                    'subtext' => 'string',
                    'iconPosition' => 'top|left|right|circle',
                    'iconSize' => 'number',
                    'iconColor' => 'hex color',
                    'align' => 'left|center|right',
                ],
                'default' => ['heading' => 'Feature', 'subtext' => 'Describe the feature.', 'iconPosition' => 'top'],
            ],
            'grid' => [
                'label' => 'Grid (column container)',
                'description' => 'A responsive grid of sub-columns. Place one inside a section column to create icon-card layouts.',
                'props' => [
                    'count' => 'number of grid sub-columns',
                    'gap' => 'number',
                    'columns' => 'array of grid sub-columns: [{spans:{base,md,lg}, widgets:[{type,props}]}]',
                ],
                'default' => ['count' => 3, 'gap' => 24, 'columns' => []],
            ],
            'pricing_table' => [
                'label' => 'Pricing Table',
                'description' => 'A pricing plan card.',
                'props' => [
                    'plan' => 'object: {title, monthlyPrice, yearlyPrice, features: string[], ctaText, ctaUrl, highlighted}',
                    'style' => 'default|classic|dark|header|minimal|split|gradient',
                    'align' => 'left|center|right',
                ],
                'default' => [
                    'style' => 'default',
                    'plan' => [
                        'title' => 'Pro', 'monthlyPrice' => '$29', 'yearlyPrice' => '$290',
                        'features' => ['Feature one', 'Feature two', 'Feature three'],
                        'ctaText' => 'Choose Plan', 'ctaUrl' => '#', 'highlighted' => true,
                    ],
                ],
            ],
            'faqs' => [
                'label' => 'FAQ / Accordion',
                'description' => 'A list of collapsible questions.',
                'props' => [
                    'items' => 'array of {question, answer}',
                    'align' => 'left|center|right',
                    'openFirst' => 'boolean',
                ],
                'default' => ['items' => [['question' => 'Question?', 'answer' => 'Answer.']], 'align' => 'left'],
            ],
            'nav_menu' => [
                'label' => 'Navigation Menu',
                'description' => 'A row of navigation links + optional button.',
                'props' => [
                    'links' => 'array of {label, url}',
                    'align' => 'left|center|right',
                ],
                'default' => ['links' => [], 'align' => 'left'],
            ],
            'link' => [
                'label' => 'Link',
                'description' => 'A row of inline text links separated by a symbol.',
                'props' => [
                    'items' => 'array of {label, url, target}',
                    'gap' => 'number (px) spacing between links',
                    'separator' => 'string shown between links (e.g. "·")',
                    'align' => 'left|center|right',
                    'color' => 'text color',
                    'hoverColor' => 'text hover color',
                ],
                'default' => ['items' => [['label' => 'Link one', 'url' => '#']], 'gap' => 12, 'separator' => '·', 'align' => 'left'],
            ],
            'text_timeline' => [
                'label' => 'Text Timeline',
                'description' => 'A vertical timeline of steps/events.',
                'props' => [
                    'items' => 'array of {date|meta, title, description}',
                ],
                'default' => ['items' => [['title' => 'Step 1', 'description' => 'Explain step one.', 'date' => '01']]],
            ],
            'social_icons' => [
                'label' => 'Social Icons',
                'description' => 'A row of social brand icons.',
                'props' => [
                    'icons' => 'array of {type} where type is a social network name (facebook, twitter|X, instagram, linkedin, youtube, github, dribbble, tiktok)',
                    'size' => 'number',
                    'gap' => 'number',
                    'align' => 'left|center|right',
                ],
                'default' => ['icons' => [['type' => 'facebook'], ['type' => 'instagram']], 'align' => 'left'],
            ],
            'social_proof' => [
                'label' => 'Social Proof',
                'description' => 'Star rating and avatars with a trust line.',
                'props' => [
                    'text' => 'string',
                    'rating' => 'number 1-5',
                    'showStars' => 'boolean',
                    'showAvatars' => 'boolean',
                    'align' => 'left|center|right',
                ],
                'default' => ['text' => 'Trusted by 1000+ clients', 'rating' => 5, 'align' => 'left'],
            ],
            'newsletter_form' => [
                'label' => 'Newsletter Form',
                'description' => 'An email input with a subscribe button.',
                'props' => [
                    'buttonText' => 'string',
                    'placeholder' => 'string',
                    'successMessage' => 'string',
                ],
                'default' => ['buttonText' => 'Subscribe', 'placeholder' => 'Enter your email', 'successMessage' => 'Thanks for subscribing!'],
            ],
            'login_form' => [
                'label' => 'Login Form',
                'description' => 'A login form with email + password.',
                'props' => [
                    'buttonText' => 'string',
                    'showRemember' => 'boolean',
                ],
                'default' => ['buttonText' => 'Sign in'],
            ],
            'register_form' => [
                'label' => 'Register Form',
                'description' => 'A registration form.',
                'props' => [
                    'buttonText' => 'string',
                ],
                'default' => ['buttonText' => 'Create account'],
            ],
            'copyright' => [
                'label' => 'Copyright',
                'description' => 'A copyright line + optional small links.',
                'props' => [
                    'text' => 'string',
                    'align' => 'left|center|right',
                ],
                'default' => ['text' => '© All rights reserved.', 'align' => 'center'],
            ],
            'badge' => [
                'label' => 'Badge',
                'description' => 'A small pill/capsule label.',
                'props' => [
                    'text' => 'string (required)',
                    'align' => 'left|center|right',
                ],
                'default' => ['text' => 'New', 'align' => 'left'],
            ],
            'tabs' => [
                'label' => 'Tabs',
                'description' => 'A set of tab panels with headings.',
                'props' => [
                    'items' => 'array of {label, heading, description}',
                ],
                'default' => ['items' => [['label' => 'Tab 1', 'heading' => 'Tab 1', 'description' => 'Content']]],
            ],
            'slider' => [
                'label' => 'Slider / Carousel',
                'description' => 'A horizontal carousel of slides.',
                'props' => [
                    'slides' => 'array of {src (image URL), heading, subtext}',
                ],
                'default' => ['slides' => []],
            ],
            'marquee' => [
                'label' => 'Marquee / Logo Strip',
                'description' => 'A scrolling strip of logos or text.',
                'props' => [
                    'items' => 'array of {text|src}',
                ],
                'default' => ['items' => []],
            ],
        ];

        if ($mode === 'page') {
            $catalog['tabs'] = $catalog['tabs'] ?? [];
            return $catalog;
        }

        // Form mode adds field widgets
        foreach (['form_text', 'form_email', 'form_textarea', 'form_select', 'form_checkbox', 'form_radio', 'form_submit'] as $type) {
            $catalog[$type] = [
                'label' => str_replace('_', ' ', ucwords($type, '_')),
                'description' => 'A form field.',
                'props' => ['name' => 'string field name', 'label' => 'string', 'required' => 'boolean', 'placeholder' => 'string'],
                'default' => ['name' => $type, 'label' => ucwords(str_replace('_', ' ', $type))],
            ];
        }

        return $catalog;
    }

    public static function get(string $type, string $mode = 'page'): ?array
    {
        $all = self::all($mode);

        return $all[$type] ?? null;
    }

    /** Returns catalog entries filtered to only the requested elements. */
    public static function find(array $wanted, string $mode = 'page'): array
    {
        $all = self::all($mode);
        $out = [];

        foreach ($wanted as $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            $type = strtolower(trim($name));
            if (isset($all[$type])) {
                $entry = $all[$type];
                $out[] = [
                    'type' => $type,
                    'label' => $entry['label'],
                    'description' => $entry['description'],
                    'props' => $entry['props'],
                    'default_props' => $entry['default'],
                ];
            }
        }

        return $out;
    }

    /** Returns a compact map "type => label" used inside the system prompt. */
    public static function names(string $mode = 'page'): array
    {
        $out = [];
        foreach (self::all($mode) as $type => $entry) {
            $out[$type] = $entry['label'];
        }

        return $out;
    }
}
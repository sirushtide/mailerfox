<?php

namespace App\Services\AiBuilder;

class BuilderSchema
{
    public const MODE_PAGE = 'page';
    public const MODE_FORM = 'form';
    public const MODE_EMAIL = 'email';

    public static function allowedWidgetTypes(string $mode): array
    {
        $common = [
            'heading',
            'rich_text',
            'image',
            'button',
            'button_group',
            'icon',
            'slider',
            'marquee',
            'grid',
            'badge',
            'tabs',
        ];

        return match ($mode) {
            self::MODE_PAGE => array_merge($common, [
                'pricing_table',
                'faqs',
                'nav_menu',
                'text_timeline',
                'social_icons',
                'social_proof',
                'newsletter_form',
                'login_form',
                'register_form',
                'copyright',
            ]),
            self::MODE_FORM => array_merge($common, [
                'form_text',
                'form_email',
                'form_textarea',
                'form_select',
                'form_checkbox',
                'form_radio',
                'form_submit',
            ]),
            self::MODE_EMAIL => array_merge($common, [
                'pricing_table',
                'faqs',
                'link',
                'copyright',
                'social_icons',
                'divider',
                'spacer',
                'logo',
            ]),
            default => $common,
        };
    }

    public static function widgetTypeLabels(): array
    {
        return [
            'heading' => 'Heading',
            'rich_text' => 'Rich Text',
            'image' => 'Image',
            'button' => 'Button',
            'button_group' => 'Button Group',
            'icon' => 'Icon Box',
            'slider' => 'Slider',
            'marquee' => 'Marquee',
            'grid' => 'Grid',
            'pricing_table' => 'Pricing Table',
            'faqs' => 'FAQs',
            'nav_menu' => 'Navigation Menu',
            'link' => 'Link',
            'text_timeline' => 'Text Timeline',
            'social_icons' => 'Social Icons',
            'social_proof' => 'Social Proof',
            'newsletter_form' => 'Newsletter Form',
            'login_form' => 'Login Form',
            'register_form' => 'Register Form',
            'copyright' => 'Copyright',
            'form_text' => 'Text Field',
            'form_email' => 'Email Field',
            'form_textarea' => 'Textarea Field',
            'form_select' => 'Select Field',
            'form_checkbox' => 'Checkbox Field',
            'form_radio' => 'Radio Field',
            'form_submit' => 'Submit Button',
        ];
    }

    public static function sectionStyleKeys(): array
    {
        return [
            'backgroundType',
            'backgroundColor',
            'backgroundImage',
            'backgroundSize',
            'backgroundPosition',
            'backgroundRepeat',
            'backgroundPreset',
            'gradientFrom',
            'gradientTo',
            'gradientAngle',
            'gradientAnimate',
            'gridOverlay',
            'gridSize',
            'gridColor',
            'gridOpacity',
            'paddingY',
            'paddingTop',
            'paddingBottom',
            'paddingLinked',
            'gap',
            'maxWidth',
            'maxWidthType',
            'innerMaxWidth',
            'boxedWidth',
            'minHeight',
            'marginTop',
            'marginBottom',
            'borderTopWidth',
            'borderTopColor',
            'borderBottomWidth',
            'borderBottomColor',
            'borderRadius',
            'overflow',
            'itemsDirection',
            'itemsJustify',
            'itemsAlign',
            'justifyContent',
            'customClass',
            'customCss',
        ];
    }

    public static function columnSpanKeys(): array
    {
        return ['base', 'sm', 'md', 'lg', 'xl'];
    }

    public static function minimalSectionSchema(): array
    {
        return [
            'name' => 'string',
            'style' => 'object',
            'columns' => [
                [
                    'spans' => [
                        'base' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ],
                    'widgets' => [
                        [
                            'type' => 'string',
                            'props' => 'object',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function pageSchema(): array
    {
        return [
            'builder' => 'mailpurse',
            'version' => 1,
            'meta' => [
                'title' => 'string',
                'description' => 'string',
                'keywords' => 'string',
                'featuredImage' => 'string',
            ],
            'style' => 'object',
            'customCss' => 'string',
            'sections' => [],
        ];
    }

    public static function isValidWidgetType(string $type, string $mode): bool
    {
        return in_array($type, self::allowedWidgetTypes($mode), true);
    }

    public static function mapUnknownWidgetType(string $type): string
    {
        $map = [
            'card' => 'icon',
            'testimonial' => 'icon',
            'team_member' => 'icon',
            'feature' => 'icon',
            'stat' => 'heading',
            'logo' => 'image',
            'video' => 'image',
            'divider' => 'rich_text',
            'spacer' => 'rich_text',
            'timeline' => 'rich_text',
            'chart' => 'image',
            'map' => 'image',
            'gallery' => 'slider',
            'carousel' => 'slider',
            'tabs' => 'grid',
            'accordion' => 'faqs',
            'list' => 'rich_text',
            'table' => 'grid',
        ];

        return $map[$type] ?? 'rich_text';
    }

    public static function sanitizeProps(array $props, string $widgetType): array
    {
        // The AI describes a button group as ['buttons' => [{text,url,style},...]].
        // The renderers and inspectors work with per-button prefixed props (btn1_/btn2_),
        // so expand the array here so both surfaces stay in sync.
        if ($widgetType === 'button_group' && isset($props['buttons']) && is_array($props['buttons'])) {
            foreach (array_values($props['buttons']) as $index => $button) {
                if (!is_array($button)) {
                    continue;
                }
                $number = $index + 1;
                foreach (['text', 'url', 'target', 'style', 'size', 'align'] as $key) {
                    if (isset($button[$key])) {
                        $props['btn' . $number . '_' . $key] = $button[$key];
                    }
                }
            }
            unset($props['buttons']);
        }

        $out = [];

        foreach ($props as $k => $v) {
            if (is_string($v)) {
                $clean = $v;
                $clean = preg_replace('~<\s*script\b[^>]*>.*?</\s*script\s*>~si', '', $clean);
                $clean = preg_replace('~<\s*script\b[^>]*>~si', '', $clean);
                $clean = preg_replace('~javascript\s*:\s*~i', '', $clean);
                $clean = preg_replace('~\bon\w+\s*=\s*["\'][^"\']*["\']~i', '', $clean);
                // Inline styles must live in the element's styling props, never in HTML.
                $clean = preg_replace('/\sstyle\s*=\s*(["\'])[^"\']*\1/i', '', $clean);
                // Deprecated presentational attributes: alignment and sizing belong to props too.
                $clean = preg_replace('/\s(?:align|valign|bgcolor)\s*=\s*(["\'])[^"\']*\1/i', '', $clean);
                if (str_starts_with($clean, 'data:image')) {
                    $clean = 'https://placehold.co/600x400?text=Image';
                }
                $out[$k] = $clean;
            } elseif (is_array($v)) {
                $out[$k] = self::sanitizeProps($v, $widgetType);
            } else {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}

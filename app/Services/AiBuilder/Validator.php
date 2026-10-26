<?php

namespace App\Services\AiBuilder;

class Validator
{
    public function validate(array $section, string $mode = 'page'): array
    {
        $section = $this->stripMarkdown($section);
        $section = $this->ensureStructure($section);
        $section = $this->stripEmptyColumns($section);
        $section = $this->sanitizeWidgets($section, $mode);
        $section = $this->normalizeTypographyUnits($section);
        $section = $this->clampStyles($section);
        $section = $this->enforceDesignDefaults($section);
        $section = $this->enforceFeaturesLayout($section);
        $section = $this->fixGenericWidgetText($section);
        $section = $this->ensureRequiredFields($section);

        if ($mode === 'email') {
            $section = $this->stripBorderRadius($section);
        }

        return $section;
    }

    public function validatePage(array $page, string $mode = 'page'): array
    {
        if (!isset($page['builder'])) {
            $page['builder'] = 'mailpurse';
        }
        if (!isset($page['version'])) {
            $page['version'] = 1;
        }
        if (!is_array($page['sections'] ?? null)) {
            $page['sections'] = [];
        }

        $cleanSections = [];
        foreach ($page['sections'] as $section) {
            if (is_array($section)) {
                $cleanSections[] = $this->validate($section, $mode);
            }
        }
        $page['sections'] = $cleanSections;

        return $page;
    }

    private function stripMarkdown(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = preg_replace('/^```(?:json)?\s*/i', '', $value) ?? $value;
                $value = preg_replace('/\s*```\s*$/', '', $value) ?? $value;
            }
        });

        return $data;
    }

    private function ensureStructure(array $section): array
    {
        if (!isset($section['columns']) || !is_array($section['columns'])) {
            $section['columns'] = [
                [
                    'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                    'widgets' => [],
                ],
            ];
        }

        foreach ($section['columns'] as &$column) {
            if (!is_array($column['spans'] ?? null)) {
                $column['spans'] = ['base' => 12, 'md' => 12, 'lg' => 12];
            }
            foreach (['base', 'md', 'lg'] as $key) {
                if (!isset($column['spans'][$key])) {
                    $column['spans'][$key] = 12;
                }
            }
            if (!isset($column['widgets']) || !is_array($column['widgets'])) {
                $column['widgets'] = [];
            }
        }

        return $section;
    }

    private function stripEmptyColumns(array $section): array
    {
        if (!isset($section['columns']) || !is_array($section['columns'])) {
            return $section;
        }

        $nonEmpty = [];
        foreach ($section['columns'] as $column) {
            $widgets = $column['widgets'] ?? [];
            if (is_array($widgets) && count($widgets) > 0) {
                $nonEmpty[] = $column;
            }
        }

        if (count($nonEmpty) === 0) {
            $nonEmpty[] = [
                'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                'widgets' => [
                    [
                        'type' => 'heading',
                        'props' => [
                            'text' => $section['name'] ?? 'Section',
                            'level' => 2,
                        ],
                    ],
                ],
            ];
        }

        $section['columns'] = $nonEmpty;

        return $section;
    }

    private function sanitizeWidgets(array $section, string $mode): array
    {
        $allowed = BuilderSchema::allowedWidgetTypes($mode);

        foreach ($section['columns'] as &$column) {
            foreach ($column['widgets'] as &$widget) {
                $type = is_string($widget['type'] ?? null) ? (string) $widget['type'] : 'rich_text';

                if (!in_array($type, $allowed, true)) {
                    $type = BuilderSchema::mapUnknownWidgetType($type);
                }

                $widget['type'] = $type;

                if (is_array($widget['props'] ?? null)) {
                    $widget['props'] = BuilderSchema::sanitizeProps($widget['props'], $type);
                } else {
                    $widget['props'] = [];
                }
            }
        }

        return $section;
    }

    private function normalizeTypographyUnits(array $section): array
    {
        $keys = ['fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing'];

        foreach ($section['columns'] ?? [] as &$column) {
            foreach ($column['widgets'] ?? [] as &$widget) {
                if (!is_array($widget['props'] ?? null)) {
                    continue;
                }
                foreach ($keys as $key) {
                    $value = $widget['props'][$key] ?? null;
                    if (!is_string($value) || trim($value) === '') {
                        continue;
                    }
                    if (!preg_match('/^(\d+(?:\.\d+)?)\s*(pt|px)$/i', trim($value), $m)) {
                        continue;
                    }
                    $number = (float) $m[1];
                    if (strtolower($m[2]) === 'pt') {
                        // 1pt = 96/72 px
                        $number = $number * 4 / 3;
                    }
                    $widget['props'][$key] = $key === 'lineHeight'
                        ? round($number, 2)
                        : (int) round($number);
                }
            }
            unset($widget);
        }
        unset($column);

        return $section;
    }

    private function clampStyles(array $section): array
    {
        $style = &$section['style'];
        if (!is_array($style)) {
            $style = [];
        }

        // Only these background types are recognized by the renderer.
        $validBgTypes = ['color', 'classic', 'gradient', 'image'];
        if (isset($style['backgroundType']) && is_string($style['backgroundType'])) {
            if (!in_array($style['backgroundType'], $validBgTypes, true)) {
                $style['backgroundType'] = 'color';
            }
        }

        if (isset($style['paddingY']) && (!is_numeric($style['paddingY']) || $style['paddingY'] < 0)) {
            $style['paddingY'] = 0;
        }
        if (isset($style['gap']) && (!is_numeric($style['gap']) || $style['gap'] < 0)) {
            $style['gap'] = 0;
        }

        foreach (['backgroundColor', 'gradientFrom', 'gradientTo'] as $colorKey) {
            if (isset($style[$colorKey]) && is_string($style[$colorKey])) {
                if (!preg_match('/^#([0-9A-Fa-f]{3,8})$/', $style[$colorKey])) {
                    $style[$colorKey] = '#ffffff';
                }
            }
        }

        return $section;
    }

    /**
     * Email sections must render with square corners across clients: force the
     * border-radius props to 0 on the section style, columns and widgets.
     */
    private function stripBorderRadius(array $section): array
    {
        $radiusKeys = [
            'borderRadius',
            'borderRadiusTop',
            'borderRadiusRight',
            'borderRadiusBottom',
            'borderRadiusLeft',
        ];

        $zeroProps = function (array $props) use ($radiusKeys): array {
            foreach ($radiusKeys as $key) {
                $props[$key] = 0;
            }
            // Nested button group entries carry their own radius props.
            if (isset($props['buttons']) && is_array($props['buttons'])) {
                foreach ($props['buttons'] as &$button) {
                    if (is_array($button)) {
                        foreach ($radiusKeys as $key) {
                            $button[$key] = 0;
                        }
                    }
                }
                unset($button);
            }
            return $props;
        };

        if (isset($section['style']) && is_array($section['style'])) {
            foreach ($radiusKeys as $key) {
                $section['style'][$key] = 0;
            }
        }

        if (isset($section['columns']) && is_array($section['columns'])) {
            foreach ($section['columns'] as &$column) {
                if (isset($column['style']) && is_array($column['style'])) {
                    foreach ($radiusKeys as $key) {
                        $column['style'][$key] = 0;
                    }
                }
                if (isset($column['widgets']) && is_array($column['widgets'])) {
                    foreach ($column['widgets'] as &$widget) {
                        if (is_array($widget['props'] ?? null)) {
                            $widget['props'] = $zeroProps($widget['props']);
                        }
                    }
                    unset($widget);
                }
            }
            unset($column);
        }

        return $section;
    }

    private function enforceDesignDefaults(array $section): array
    {
        $style = &$section['style'];
        if (!is_array($style)) {
            $style = [];
        }

        $paddingY = $style['paddingY'] ?? null;
        $paddingTop = $style['paddingTop'] ?? null;
        $paddingBottom = $style['paddingBottom'] ?? null;
        if ($paddingY === null && $paddingTop === null && $paddingBottom === null) {
            $style['paddingY'] = 64;
        } elseif (is_numeric($paddingY) && $paddingY < 32) {
            $style['paddingY'] = 64;
        }

        $gap = $style['gap'] ?? null;
        if ($gap === null) {
            $style['gap'] = 24;
        } elseif (is_numeric($gap) && $gap < 8) {
            $style['gap'] = 24;
        }

        $maxWidthType = $style['maxWidthType'] ?? null;
        if ($maxWidthType !== 'full') {
            $style['maxWidthType'] = 'full';
        }
        $innerMaxWidth = $style['innerMaxWidth'] ?? null;
        if (!is_numeric($innerMaxWidth) || $innerMaxWidth < 800) {
            $style['innerMaxWidth'] = 1120;
        }

        $sectionName = strtolower(trim($section['name'] ?? ''));
        $designKeywords = ['cta', 'call to action', 'get started', 'hero', 'banner', 'pricing', 'plan', 'signup', 'subscribe', 'join', 'download'];
        $isDesignSection = false;
        foreach ($designKeywords as $kw) {
            if (str_contains($sectionName, $kw)) {
                $isDesignSection = true;
                break;
            }
        }

        $buttonCount = 0;
        foreach ($section['columns'] ?? [] as $column) {
            foreach ($column['widgets'] ?? [] as $widget) {
                if (($widget['type'] ?? '') === 'button') {
                    $buttonCount++;
                }
            }
        }
        $hasButton = $buttonCount > 0;

        $borderRadius = $style['borderRadius'] ?? null;
        $hasBorderRadius = $borderRadius !== null || isset($style['borderRadiusTop']) || isset($style['borderRadiusBottom']);
        if (($isDesignSection || $hasButton) && !$hasBorderRadius) {
            $style['borderRadius'] = 16;
        } elseif (is_numeric($borderRadius) && $borderRadius < 8 && ($isDesignSection || $hasButton)) {
            $style['borderRadius'] = 16;
        }

        if (!isset($style['backgroundColor']) && !isset($style['gradientFrom']) && !isset($style['backgroundType'])) {
            if ($isDesignSection || $hasButton) {
                $style['backgroundType'] = 'color';
                $style['backgroundColor'] = '#f8fafc';
            }
        }

        $sectionBg = $this->resolveSectionBackground($style);
        $isDarkSection = $this->isDarkColor($sectionBg);
        $presetPool = ['primary', 'secondary', 'outline', 'ghost'];

        $buttonIndices = [];
        $usedPresets = [];
        if (isset($section['columns']) && is_array($section['columns'])) {
            foreach ($section['columns'] as $cIdx => $column) {
                foreach ($column['widgets'] ?? [] as $wIdx => $widget) {
                    if (($widget['type'] ?? '') === 'button') {
                        $buttonIndices[] = ['c' => $cIdx, 'w' => $wIdx];
                        $usedPresets[] = $widget['props']['style'] ?? null;
                    }
                }
            }
        }

        foreach ($buttonIndices as $pos) {
            $props = &$section['columns'][$pos['c']]['widgets'][$pos['w']]['props'];

            $hasCustomBg = isset($props['backgroundColor']) && is_string($props['backgroundColor']) && trim($props['backgroundColor']) !== '';
            $hasCustomText = isset($props['textColor']) && is_string($props['textColor']) && trim($props['textColor']) !== '';

            if ($hasCustomBg && $hasCustomText) {
                $bgLight = $this->isLightColor($props['backgroundColor']);
                $textLight = $this->isLightColor($props['textColor']);
                if ($bgLight && $textLight) {
                    $props['textColor'] = '#111827';
                }
                if (!$bgLight && !$textLight) {
                    $props['textColor'] = '#ffffff';
                }
            } elseif ($hasCustomBg && !$hasCustomText) {
                $props['textColor'] = $this->isLightColor($props['backgroundColor']) ? '#111827' : '#ffffff';
            } elseif (!$hasCustomBg && $hasCustomText) {
                $props['backgroundColor'] = $this->isLightColor($props['textColor']) ? '#111827' : '#ffffff';
            } else {
                if (!isset($props['style']) || !in_array($props['style'], $presetPool, true)) {
                    $props['style'] = $isDarkSection ? 'outline' : 'primary';
                }
            }

            if (!isset($props['align']) || !in_array($props['align'], ['left', 'center', 'right'], true)) {
                if ($isDesignSection || $hasButton) {
                    $props['align'] = 'center';
                }
            }
        }

        if (count($buttonIndices) >= 2) {
            $uniquePresets = array_unique(array_filter($usedPresets, fn($s) => $s !== null));
            if (count($uniquePresets) < count($buttonIndices)) {
                $assigned = [];
                foreach ($buttonIndices as $pos) {
                    $props = &$section['columns'][$pos['c']]['widgets'][$pos['w']]['props'];
                    $hasCustomBg = isset($props['backgroundColor']) && is_string($props['backgroundColor']) && trim($props['backgroundColor']) !== '';
                    if ($hasCustomBg) {
                        continue;
                    }
                    foreach ($presetPool as $ps) {
                        if (!in_array($ps, $assigned, true)) {
                            $props['style'] = $ps;
                            $assigned[] = $ps;
                            break;
                        }
                    }
                }
            }
        }

        return $section;
    }

    private function enforceFeaturesLayout(array $section): array
    {
        $sectionName = strtolower(trim($section['name'] ?? ''));
        $featureKeywords = ['feature', 'benefit', 'capability', 'service'];
        $isFeaturesSection = false;
        foreach ($featureKeywords as $kw) {
            if (str_contains($sectionName, $kw)) {
                $isFeaturesSection = true;
                break;
            }
        }

        if (!$isFeaturesSection) {
            return $section;
        }

        $style = &$section['style'];
        if (!is_array($style)) {
            $style = [];
        }

        // Force vertical stacking so each section column is 100% width
        $style['itemsDirection'] = 'column';

        if (!isset($section['columns']) || !is_array($section['columns'])) {
            return $section;
        }

        // Collect all widgets across all columns
        $headingWidgets = [];
        $richTextWidgets = [];
        $iconWidgets = [];
        $buttonWidgets = [];
        $otherWidgets = [];

        foreach ($section['columns'] as $column) {
            foreach ($column['widgets'] ?? [] as $widget) {
                $type = $widget['type'] ?? '';
                if ($type === 'heading') {
                    $headingWidgets[] = $widget;
                } elseif ($type === 'rich_text') {
                    $richTextWidgets[] = $widget;
                } elseif ($type === 'icon') {
                    $iconWidgets[] = $widget;
                } elseif ($type === 'button') {
                    $buttonWidgets[] = $widget;
                } else {
                    $otherWidgets[] = $widget;
                }
            }
        }

        // If there are no icon widgets, this isn't a feature section we can restructure
        if (count($iconWidgets) === 0) {
            return $section;
        }

        // Build the restructured columns
        $newColumns = [];

        // Column 1: heading + rich_text (title row)
        $headerWidgets = [];
        foreach ($headingWidgets as $w) {
            $headerWidgets[] = $w;
        }
        foreach ($richTextWidgets as $w) {
            $headerWidgets[] = $w;
        }
        if (count($headerWidgets) > 0) {
            $newColumns[] = [
                'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                'widgets' => $headerWidgets,
            ];
        }

        // Column 2: grid widget containing all icon widgets as grid columns
        $gridColumns = [];
        foreach ($iconWidgets as $iconWidget) {
            $gridColumns[] = [
                'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                'widgets' => [$iconWidget],
            ];
        }

        $count = min(4, max(1, count($gridColumns)));
        $newColumns[] = [
            'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
            'widgets' => [
                [
                    'type' => 'grid',
                    'name' => 'Feature Grid',
                    'props' => [
                        'count' => $count,
                        'gap' => 24,
                        'columns' => $gridColumns,
                    ],
                ],
            ],
        ];

        // Column 3: button CTA (if any)
        foreach ($otherWidgets as $w) {
            $buttonWidgets[] = $w;
        }
        if (count($buttonWidgets) > 0) {
            $newColumns[] = [
                'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                'widgets' => $buttonWidgets,
            ];
        }

        $section['columns'] = $newColumns;

        return $section;
    }

    private function fixGenericWidgetText(array $section): array
    {
        $sectionName = strtolower(trim($section['name'] ?? ''));
        $featureKeywords = ['feature', 'benefit', 'capability', 'service'];
        $isFeaturesSection = false;
        foreach ($featureKeywords as $kw) {
            if (str_contains($sectionName, $kw)) {
                $isFeaturesSection = true;
                break;
            }
        }

        $genericHeadings = ['heading', 'title', 'feature name', 'card title'];
        $genericSubtexts = ['subtext', 'description', 'lorem ipsum', 'sample text'];

        if (!isset($section['columns']) || !is_array($section['columns'])) {
            return $section;
        }

        foreach ($section['columns'] as $cIdx => $column) {
            foreach ($column['widgets'] ?? [] as $wIdx => $widget) {
                if (($widget['type'] ?? '') !== 'icon') {
                    continue;
                }
                $props = &$section['columns'][$cIdx]['widgets'][$wIdx]['props'];
                if (!is_array($props)) {
                    continue;
                }

                $heading = strtolower(trim($props['heading'] ?? ''));
                $subtext = strtolower(trim($props['subtext'] ?? ''));

                foreach ($genericHeadings as $gh) {
                    if ($heading === $gh || str_contains($heading, $gh)) {
                        if ($isFeaturesSection) {
                            $props['heading'] = 'Powerful Feature';
                        } else {
                            $props['heading'] = 'Explore More';
                        }
                        break;
                    }
                }

                foreach ($genericSubtexts as $gs) {
                    if ($subtext === $gs || str_contains($subtext, $gs)) {
                        if ($isFeaturesSection) {
                            $props['subtext'] = 'Discover how this helps you achieve more with less effort.';
                        } else {
                            $props['subtext'] = 'Learn more about what we offer and how it can benefit you.';
                        }
                        break;
                    }
                }
            }
        }

        return $section;
    }

    private function resolveSectionBackground(array $style): string
    {
        if (isset($style['backgroundColor']) && is_string($style['backgroundColor']) && trim($style['backgroundColor']) !== '') {
            return $style['backgroundColor'];
        }
        if (isset($style['gradientFrom']) && is_string($style['gradientFrom']) && trim($style['gradientFrom']) !== '') {
            return $style['gradientFrom'];
        }
        return '#ffffff';
    }

    private function isLightColor(string $hex): bool
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return true;
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance > 0.6;
    }

    private function isDarkColor(string $hex): bool
    {
        return !$this->isLightColor($hex);
    }

    private function ensureRequiredFields(array $section): array
    {
        if (!isset($section['name']) || !is_string($section['name']) || trim($section['name']) === '') {
            $section['name'] = 'Section';
        }
        if (!isset($section['style']) || !is_array($section['style'])) {
            $section['style'] = [];
        }

        return $section;
    }
}

<?php

namespace App\Services\AiBuilder;

class PageBuilderContext
{
    public function summarize(array $page): array
    {
        $sections = [];
        $usedWidgetTypes = [];
        $usedBackgrounds = [];

        foreach (is_array($page['sections'] ?? null) ? $page['sections'] : [] as $index => $section) {
            if (!is_array($section)) {
                continue;
            }

            $widgetTypes = [];
            foreach (is_array($section['columns'] ?? null) ? $section['columns'] : [] as $column) {
                if (!is_array($column)) {
                    continue;
                }

                foreach (is_array($column['widgets'] ?? null) ? $column['widgets'] : [] as $widget) {
                    if (!is_array($widget)) {
                        continue;
                    }

                    $type = is_string($widget['type'] ?? null) ? trim($widget['type']) : '';
                    if ($type === '') {
                        continue;
                    }

                    $widgetTypes[] = $type;
                    $usedWidgetTypes[$type] = true;
                }
            }

            $style = is_array($section['style'] ?? null) ? $section['style'] : [];
            $background = $style['backgroundColor'] ?? $style['gradientFrom'] ?? null;
            if (is_string($background) && trim($background) !== '') {
                $usedBackgrounds[trim($background)] = true;
            }

            $sections[] = [
                'index' => (int) $index,
                'id' => is_string($section['id'] ?? null) ? $section['id'] : null,
                'name' => is_string($section['name'] ?? null) ? trim($section['name']) : 'Section',
                'column_count' => is_array($section['columns'] ?? null) ? count($section['columns']) : 0,
                'widget_types' => array_values(array_unique($widgetTypes)),
                'background' => is_string($background) ? trim($background) : null,
            ];
        }

        return [
            'meta' => is_array($page['meta'] ?? null) ? [
                'title' => $page['meta']['title'] ?? null,
                'description' => $page['meta']['description'] ?? null,
            ] : [],
            'section_count' => count($sections),
            'sections' => $sections,
            'used_widget_types' => array_keys($usedWidgetTypes),
            'used_backgrounds' => array_keys($usedBackgrounds),
        ];
    }
}

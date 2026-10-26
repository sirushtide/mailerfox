<?php

namespace App\Services\AiBuilder;

class Assembler
{
    public function assemble(array $sections, array $spec, string $mode = 'page'): array
    {
        $page = BuilderSchema::pageSchema();

        $page['meta']['title'] = $spec['project_type'] ?? 'Page';
        $page['meta']['description'] = '';

        $palette = $spec['color_palette'] ?? [];
        $page['style'] = [
            'backgroundType' => 'color',
            'backgroundColor' => $palette['background'] ?? '#f8fafc',
            'pageMaxWidthType' => 'full',
        ];

        $dedupedIds = [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            if (isset($section['id']) && in_array($section['id'], $dedupedIds, true)) {
                $section['id'] = $this->generateId();
            }
            $dedupedIds[] = $section['id'] ?? null;
            $page['sections'][] = $section;
        }

        return $page;
    }

    private function generateId(): string
    {
        return 's_' . bin2hex(random_bytes(6));
    }
}

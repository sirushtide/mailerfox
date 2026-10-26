<?php

namespace App\Services;

use App\Services\PageBuilder\PageBuilderRenderer;

class EmailTemplateRenderer
{
    public function __construct(private PageBuilderRenderer $pageRenderer)
    {
    }

    public function render(array $builderData): string
    {
        $html = $this->pageRenderer->render($builderData);
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;

        $fontFamilies = $this->collectGoogleFonts($builderData);
        $fontLink = '';
        if (!empty($fontFamilies)) {
            $encoded = array_map(fn($f) => urlencode($f) . ':wght@100;200;300;400;500;600;700;800;900', $fontFamilies);
            $fontLink = '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=' . implode('&family=', $encoded) . '&display=swap" rel="stylesheet">';
        }

        $bodyFont = 'font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;';

        return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">' . $fontLink . '</head>'
            . '<body style="margin:0;padding:0;background:#f3f4f6;-webkit-text-size-adjust:100%;' . $bodyFont . '">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;background:#f3f4f6;">'
            . '<tr><td align="center" style="padding:24px 12px;">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;border-collapse:collapse;background:#ffffff;">'
            . '<tr><td style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">' . $html . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private function collectGoogleFonts(array $data): array
    {
        $fonts = [];
        $systemFonts = [
            '', 'default', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica',
            'Arial', 'sans-serif', 'serif', 'monospace', 'cursive', 'fantasy',
            'Georgia', 'Times New Roman', 'Courier New', 'Verdana', 'Trebuchet MS',
        ];

        $extractFont = function ($value) use ($systemFonts, &$fonts) {
            if (!is_string($value) || trim($value) === '') return;
            $primary = explode(',', trim($value))[0];
            $primary = trim($primary, ' \'"');
            if ($primary === '' || in_array(strtolower($primary), array_map('strtolower', $systemFonts), true)) return;
            if (!in_array($primary, $fonts, true)) {
                $fonts[] = $primary;
            }
        };

        $fontWidgetTypes = ['heading', 'rich_text', 'link', 'button', 'copyright', 'nav_menu', 'mega_menu', 'badge', 'icon'];

        $walkWidgets = function ($widgets) use (&$walkWidgets, $extractFont, $fontWidgetTypes) {
            if (!is_array($widgets)) return;
            foreach ($widgets as $w) {
                if (!is_array($w)) continue;
                $type = $w['type'] ?? '';
                $props = $w['props'] ?? [];
                if (in_array($type, $fontWidgetTypes, true)) {
                    $extractFont($props['fontFamily'] ?? '');
                }
                if ($type === 'grid') {
                    $gridCols = $props['columns'] ?? [];
                    if (is_array($gridCols)) {
                        foreach ($gridCols as $gc) {
                            if (!is_array($gc)) continue;
                            $walkWidgets($gc['widgets'] ?? []);
                        }
                    }
                }
                if ($type === 'tabs') {
                    $items = $props['items'] ?? [];
                    if (is_array($items)) {
                        foreach ($items as $it) {
                            if (!is_array($it)) continue;
                            $walkWidgets($it['widgets'] ?? []);
                        }
                    }
                }
            }
        };

        $sections = $data['sections'] ?? [];
        if (!is_array($sections)) return $fonts;
        foreach ($sections as $section) {
            if (!is_array($section)) continue;
            $columns = $section['columns'] ?? [];
            if (!is_array($columns)) continue;
            foreach ($columns as $col) {
                if (!is_array($col)) continue;
                $walkWidgets($col['widgets'] ?? []);
            }
        }
        return $fonts;
    }

    public function plainText(string $html): string
    {
        $text = preg_replace('/\s+/', ' ', strip_tags($html)) ?? '';

        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}

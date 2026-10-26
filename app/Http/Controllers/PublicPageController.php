<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\PageBuilder\PageBuilderRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PublicPageController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $isPreview = $request->boolean('preview') && Auth::guard('admin')->check();

        $pageQuery = Page::query()
            ->whereIn('type', ['page', 'homepage'])
            ->where('slug', $slug);

        if (!$isPreview) {
            $pageQuery->where('status', 'publish');
        }

        $page = $pageQuery->firstOrFail();

        $renderer = app(PageBuilderRenderer::class);
        $builder = is_array($page->builder_data) ? $page->builder_data : [];
        $publishMeta = is_array($builder['publish_meta'] ?? null) ? $builder['publish_meta'] : [];
        $visibility = is_string($publishMeta['visibility'] ?? null) ? (string) $publishMeta['visibility'] : 'public';
        $passwordHash = is_string($publishMeta['password_hash'] ?? null) ? (string) $publishMeta['password_hash'] : '';

        $content = is_string($page->html_content) ? (string) $page->html_content : '';
        if ($renderer->isBuilderData($builder)) {
            $content = $renderer->render($builder);
        }

        if (!$isPreview && $visibility === 'private') {
            abort(403);
        }

        $providedPassword = is_string($request->input('password')) ? trim((string) $request->input('password')) : '';
        $passwordValid = $visibility !== 'password' || ($passwordHash !== '' && $providedPassword !== '' && Hash::check($providedPassword, $passwordHash));

        $supabaseIntegration = $page->supabaseIntegration;
        $supabaseCredentials = null;
        if ($supabaseIntegration && $supabaseIntegration->status === 'connected') {
            $supabaseCredentials = [
                'url' => $supabaseIntegration->project_url,
                'publishable_key' => $supabaseIntegration->publishable_key,
                'app_id' => (string) $page->id,
            ];
        }

        return view('public.page', [
            'page' => $page,
            'content' => $content,
            'isPreview' => $isPreview,
            'googleFontUrls' => $this->googleFontUrlsFromBuilder($builder),
            'publishVisibility' => $visibility,
            'passwordRequired' => !$isPreview && $visibility === 'password' && !$passwordValid,
            'passwordError' => !$isPreview && $visibility === 'password' && $providedPassword !== '' && !$passwordValid,
            'supabaseCredentials' => $supabaseCredentials,
        ]);
    }

    private function googleFontUrlsFromBuilder(array $builder): array
    {
        $fontNames = [];

        $pushFont = static function (mixed $value) use (&$fontNames): void {
            if (!is_string($value)) {
                return;
            }

            $raw = trim((string) $value);
            if ($raw === '') {
                return;
            }

            $name = null;
            if (preg_match('/["\']([^"\']+)["\']/', $raw, $m) === 1) {
                $name = trim((string) ($m[1] ?? ''));
            } else {
                $parts = explode(',', $raw);
                $name = trim((string) ($parts[0] ?? ''));
            }

            if ($name === '') {
                return;
            }

            $generic = [
                'serif',
                'sans-serif',
                'monospace',
                'cursive',
                'fantasy',
                'system-ui',
                '-apple-system',
                'ui-sans-serif',
                'ui-serif',
                'ui-monospace',
                'emoji',
                'math',
                'fangsong',
            ];

            if (in_array(strtolower($name), $generic, true)) {
                return;
            }

            $fontNames[$name] = true;
        };

        $walkWidgets = static function (array $widgets) use (&$walkWidgets, $pushFont): void {
            foreach ($widgets as $w) {
                if (!is_array($w)) {
                    continue;
                }

                $props = is_array($w['props'] ?? null) ? $w['props'] : [];
                foreach ($props as $key => $val) {
                    if (!is_string($key) || !str_ends_with(strtolower($key), 'fontfamily')) {
                        continue;
                    }
                    $pushFont($val);
                }

                $type = is_string($w['type'] ?? null) ? (string) $w['type'] : '';
                if ($type === 'grid') {
                    $columns = is_array($props['columns'] ?? null) ? $props['columns'] : [];
                    foreach ($columns as $col) {
                        $walkWidgets(is_array($col['widgets'] ?? null) ? $col['widgets'] : []);
                    }
                } elseif ($type === 'tabs') {
                    $items = is_array($props['items'] ?? null) ? $props['items'] : [];
                    foreach ($items as $item) {
                        $walkWidgets(is_array($item['widgets'] ?? null) ? $item['widgets'] : []);
                    }
                }
            }
        };

        $sections = is_array($builder['sections'] ?? null) ? $builder['sections'] : [];
        foreach ($sections as $section) {
            $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
            foreach ($columns as $column) {
                $walkWidgets(is_array($column['widgets'] ?? null) ? $column['widgets'] : []);
            }
        }

        return array_map(
            static fn (string $name): string => 'https://fonts.googleapis.com/css2?family=' . str_replace('%20', '+', rawurlencode($name)) . ':wght@100;200;300;400;500;600;700;800;900&display=swap',
            array_keys($fontNames)
        );
    }
}

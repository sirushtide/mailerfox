<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\SiteLayout;
use App\Services\PageBuilder\PageBuilderRenderer;
use Illuminate\Http\Request;

class NavigationMenuController extends Controller
{
    public function index()
    {
        $menus = NavigationMenu::orderBy('id')->get();

        return view('admin.menus.index', [
            'menus' => $menus,
        ]);
    }

    public function create()
    {
        return view('admin.menus.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $key = $this->generateUniqueKey($data['name']);

        $menu = NavigationMenu::create([
            'key' => $key,
            'name' => $data['name'],
            'items' => [],
        ]);

        $this->refreshRenderedContent();

        return redirect()->route('admin.menus.edit', $menu)
            ->with('success', __('Menu created.'));
    }

    public function edit(NavigationMenu $menu)
    {
        $pages = Page::query()
            ->where('type', 'page')
            ->where('status', 'publish')
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);

        $megaMenus = SiteLayout::published()->megaMenus()->orderBy('name')->get(['id', 'name']);

        return view('admin.menus.edit', [
            'menu' => $menu,
            'pages' => $pages,
            'megaMenus' => $megaMenus,
        ]);
    }

    public function update(Request $request, NavigationMenu $menu)
    {
        $data = $request->validate([
            'items' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($data['name']) && trim($data['name']) !== '') {
            $menu->name = trim($data['name']);
        }

        $itemsRaw = $data['items'] ?? null;
        $items = [];
        if (is_string($itemsRaw) && trim($itemsRaw) !== '') {
            $decoded = json_decode($itemsRaw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $items = $decoded;
            }
        }

        $sanitized = $this->sanitizeItems($items);

        $menu->items = $sanitized;
        $menu->save();

        $this->refreshRenderedContent();

        return redirect()->route('admin.menus.edit', $menu)
            ->with('success', __('Menu updated.'));
    }

    public function destroy(NavigationMenu $menu)
    {
        $menu->delete();

        $this->refreshRenderedContent();

        return redirect()->route('admin.menus.index')
            ->with('success', __('Menu deleted.'));
    }

    /**
     * Re-render cached layout/page HTML so nav_menu elements pick up the
     * latest saved menu items (the public site serves html_content snapshots).
     */
    private function refreshRenderedContent(): void
    {
        $renderer = app(PageBuilderRenderer::class);

        SiteLayout::query()->get()->each(function (SiteLayout $layout) use ($renderer) {
            $builder = is_array($layout->builder_data) ? $layout->builder_data : null;
            if (!$builder || !$renderer->isBuilderData($builder) || !$this->builderUsesNavMenu($builder)) {
                return;
            }
            $layout->html_content = $renderer->render($builder);
            $layout->save();
        });

        Page::query()->whereNotNull('builder_data')->get()->each(function (Page $page) use ($renderer) {
            $builder = is_array($page->builder_data) ? $page->builder_data : null;
            if (!$builder || !$renderer->isBuilderData($builder) || !$this->builderUsesNavMenu($builder)) {
                return;
            }
            $page->html_content = $renderer->render($builder);
            $page->save();
        });
    }

    private function builderUsesNavMenu(array $data): bool
    {
        $stack = [$data];
        while (!empty($stack)) {
            $node = array_pop($stack);
            if (!is_array($node)) {
                continue;
            }
            if (($node['type'] ?? null) === 'nav_menu') {
                return true;
            }
            foreach ($node as $value) {
                if (is_array($value)) {
                    $stack[] = $value;
                }
            }
        }

        return false;
    }

    private function generateUniqueKey(string $name): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($name)));
        $base = trim($base, '_') ?: 'menu';
        $key = $base;
        $i = 1;
        while (NavigationMenu::where('key', $key)->exists()) {
            $key = $base . '_' . $i;
            $i++;
        }
        return $key;
    }

    private function sanitizeItems(array $items): array
    {
        $clean = [];

        $allowedRoutes = [
            'home',
            'features',
            'pricing',
            'blog.index',
            'roadmap',
            'docs',
            'api.docs.public',
        ];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = $item['type'] ?? null;
            if ($type === 'route') {
                $route = $item['route'] ?? null;
                $label = $item['label'] ?? null;

                if (!is_string($route) || !in_array($route, $allowedRoutes, true)) {
                    continue;
                }

                if (!is_string($label) || trim($label) === '') {
                    $label = $route;
                }

                $clean[] = [
                    'type' => 'route',
                    'route' => $route,
                    'label' => trim((string) $label),
                ];
                continue;
            }

            if ($type === 'page') {
                $pageId = $item['page_id'] ?? null;
                $label = $item['label'] ?? null;

                if (!is_numeric($pageId)) {
                    continue;
                }

                $page = Page::query()
                    ->where('id', (int) $pageId)
                    ->where('type', 'page')
                    ->where('status', 'publish')
                    ->first(['id', 'title']);

                if (!$page) {
                    continue;
                }

                if (!is_string($label) || trim($label) === '') {
                    $label = $page->title;
                }

                $clean[] = [
                    'type' => 'page',
                    'page_id' => (int) $page->id,
                    'label' => trim((string) $label),
                ];
                continue;
            }

            if ($type === 'custom') {
                $label = $item['label'] ?? null;
                $url = $item['url'] ?? null;

                if (!is_string($url) || trim($url) === '') {
                    continue;
                }

                $url = trim($url);
                if (!preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $url)) {
                    $url = 'https://' . ltrim($url, '/');
                }

                if (!is_string($label) || trim($label) === '') {
                    $label = $url;
                }

                $clean[] = [
                    'type' => 'custom',
                    'label' => trim((string) $label),
                    'url' => $url,
                ];
                continue;
            }

            if ($type === 'dropdown') {
                $label = $item['label'] ?? null;
                $children = $item['children'] ?? [];

                if (!is_string($label) || trim($label) === '') {
                    continue;
                }

                $cleanChildren = is_array($children) ? $this->sanitizeItems($children) : [];

                $clean[] = [
                    'type' => 'dropdown',
                    'label' => trim((string) $label),
                    'children' => $cleanChildren,
                ];
                continue;
            }

            if ($type === 'megamenu') {
                $label = $item['label'] ?? null;
                $megaMenuId = $item['mega_menu_id'] ?? null;

                if (!is_string($label) || trim($label) === '') {
                    continue;
                }

                if (!is_numeric($megaMenuId)) {
                    continue;
                }

                $mega = SiteLayout::published()->megaMenus()->where('id', (int) $megaMenuId)->first(['id', 'name']);
                if (!$mega) {
                    continue;
                }

                $clean[] = [
                    'type' => 'megamenu',
                    'label' => trim((string) $label),
                    'mega_menu_id' => (int) $mega->id,
                ];
            }
        }

        return $clean;
    }
}

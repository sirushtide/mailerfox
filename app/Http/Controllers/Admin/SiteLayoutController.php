<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteLayoutRequest;
use App\Models\EmailList;
use App\Models\Page;
use App\Models\SiteLayout;
use App\Models\NavigationMenu;
use App\Services\PageBuilder\PageBuilderRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SiteLayoutController extends Controller
{
    public function index()
    {
        $headers = SiteLayout::headers()->orderBy('sort_order')->orderByDesc('updated_at')->get();
        $footers = SiteLayout::footers()->orderBy('sort_order')->orderByDesc('updated_at')->get();
        $megaMenus = SiteLayout::megaMenus()->orderBy('sort_order')->orderByDesc('updated_at')->get();

        return view('admin.layouts.index', compact('headers', 'footers', 'megaMenus'));
    }

    public function create(Request $request)
    {
        $type = trim((string) $request->query('type', 'header'));
        if (!in_array($type, ['header', 'footer', 'megamenu'], true)) {
            $type = 'header';
        }

        $layout = new SiteLayout([
            'type' => $type,
            'status' => 'draft',
            'display_conditions' => ['all_pages' => true],
        ]);

        return view('admin.layouts.edit', [
            'layout' => $layout,
            'pageBuilderData' => $this->defaultBuilderFromLayout($layout),
            'pages' => Page::published()->orderBy('title')->get(['id', 'title', 'slug']),
            'routes' => $this->availableRoutes(),
        ]);
    }

    public function store(SiteLayoutRequest $request)
    {
        $data = $request->validated();
        if (isset($data['builder_data'])) {
            $decoded = $this->decodeBuilderData($data['builder_data']);
            if (is_array($decoded)) {
                $data['builder_data'] = $decoded;
            }
        }
        $data = $this->applyBuilderHtml($data);
        $data['sort_order'] = SiteLayout::where('type', $data['type'])->max('sort_order') + 1;

        $layout = SiteLayout::create($data);

        return redirect()->route('admin.layouts.edit', $layout)->with('success', __('Layout created.'));
    }

    public function edit(SiteLayout $layout)
    {
        return view('admin.layouts.edit', [
            'layout' => $layout,
            'pageBuilderData' => $this->defaultBuilderFromLayout($layout),
            'pages' => Page::published()->orderBy('title')->get(['id', 'title', 'slug']),
            'routes' => $this->availableRoutes(),
        ]);
    }

    public function update(SiteLayoutRequest $request, SiteLayout $layout)
    {
        $data = $request->validated();
        if (isset($data['builder_data'])) {
            $decoded = $this->decodeBuilderData($data['builder_data']);
            if (is_array($decoded)) {
                $data['builder_data'] = $decoded;
            }
        }
        $data = $this->applyBuilderHtml($data);
        $layout->update($data);

        $redirectTo = is_string($request->input('redirect_to')) ? trim((string) $request->input('redirect_to')) : '';
        if ($redirectTo === 'builder') {
            return redirect()->route('admin.layouts.builder', ['layout' => $layout, 'embed' => 1])->with('success', __('Layout updated.'));
        }

        return redirect()->route('admin.layouts.edit', $layout)->with('success', __('Layout updated.'));
    }

    public function destroy(SiteLayout $layout)
    {
        $layout->delete();
        return redirect()->route('admin.layouts.index')->with('success', __('Layout deleted.'));
    }

    public function builder(SiteLayout $layout)
    {
        return view('admin.layouts.builder', [
            'layout' => $layout,
            'pageBuilderData' => $this->defaultBuilderFromLayout($layout),
            'megaMenus' => SiteLayout::megaMenus()->orderBy('name')->get(['id', 'name']),
            'navMenus' => NavigationMenu::orderBy('name')->get(['id', 'name', 'key', 'items']),
            'lists' => EmailList::orderBy('name')->get(['id', 'name', 'display_name']),
        ]);
    }

    public function renderHtml(Request $request, SiteLayout $layout)
    {
        $builder = $this->decodeBuilderData($request->input('builder_data'));
        if (!is_array($builder)) {
            throw ValidationException::withMessages([
                'builder_data' => __('Invalid builder data.'),
            ]);
        }

        $renderer = app(PageBuilderRenderer::class);
        if (!$renderer->isBuilderData($builder)) {
            throw ValidationException::withMessages([
                'builder_data' => __('Invalid builder data.'),
            ]);
        }

        return response()->json([
            'success' => true,
            'html' => $renderer->render($builder),
        ]);
    }

    private function applyBuilderHtml(array $data): array
    {
        $builder = $data['builder_data'] ?? null;
        if (!is_array($builder)) {
            return $data;
        }

        $renderer = app(PageBuilderRenderer::class);
        if (!$renderer->isBuilderData($builder)) {
            return $data;
        }

        $data['html_content'] = $renderer->render($builder);

        return $data;
    }

    private function decodeBuilderData(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $candidates = [$raw];
        $decodedEntities = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (is_string($decodedEntities) && $decodedEntities !== $raw) {
            $candidates[] = $decodedEntities;
        }

        foreach ($candidates as $cand) {
            $first = json_decode($cand, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($first)) {
                    return $first;
                }
                if (is_string($first)) {
                    $second = json_decode($first, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($second)) {
                        return $second;
                    }
                }
            }

            $stripped = stripslashes($cand);
            if ($stripped !== $cand) {
                $first2 = json_decode($stripped, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (is_array($first2)) {
                        return $first2;
                    }
                    if (is_string($first2)) {
                        $second2 = json_decode($first2, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($second2)) {
                            return $second2;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function defaultBuilderFromLayout(SiteLayout $layout): array
    {
        $builder = is_array($layout->builder_data) ? $layout->builder_data : [];
        $renderer = app(PageBuilderRenderer::class);

        if ($renderer->isBuilderData($builder)) {
            return $builder;
        }

        $html = is_string($layout->html_content) ? (string) $layout->html_content : '';

        return [
            'builder' => 'mailpurse',
            'version' => 1,
            'sections' => [
                [
                    'id' => 'sec_' . Str::random(8),
                    'style' => [
                        'backgroundType' => 'color',
                        'backgroundColor' => '#ffffff',
                        'paddingY' => 16,
                        'gap' => 24,
                        'maxWidth' => '6xl',
                    ],
                    'columns' => [
                        [
                            'id' => 'col_' . Str::random(8),
                            'spans' => ['base' => 12, 'md' => 12, 'lg' => 12],
                            'widgets' => [
                                [
                                    'id' => 'w_' . Str::random(8),
                                    'type' => 'rich_text',
                                    'props' => [
                                        'html' => $html,
                                        'align' => 'left',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function availableRoutes(): array
    {
        return [
            'home',
            'home.variant',
            'features',
            'pricing',
            'blog.index',
            'blog.show',
            'roadmap',
            'docs',
            'api.docs.public',
            'ai.pages.show',
            'pages.show',
            'subscribe',
            'login',
            'register',
        ];
    }
}

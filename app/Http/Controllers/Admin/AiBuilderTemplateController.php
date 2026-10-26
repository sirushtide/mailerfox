<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBuilderTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AiBuilderTemplateController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $category = trim((string) $request->get('category', ''));

        $templates = AiBuilderTemplate::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('template_id', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($category !== '', function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories = AiBuilderTemplate::query()
            ->selectRaw('DISTINCT category')
            ->orderBy('category')
            ->pluck('category')
            ->all();

        return view('admin.ai-builder-templates.index', compact('templates', 'categories', 'search', 'category'));
    }

    public function create()
    {
        return view('admin.ai-builder-templates.form', [
            'template' => new AiBuilderTemplate(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['created_by'] = Auth::guard('admin')->id();

        AiBuilderTemplate::create($data);

        return redirect()->route('admin.ai-projects.ai-builder-templates.index')
            ->with('success', __('Template created successfully.'));
    }

    public function edit(AiBuilderTemplate $ai_builder_template)
    {
        $template = $ai_builder_template;
        return view('admin.ai-builder-templates.form', [
            'template' => $template,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, AiBuilderTemplate $ai_builder_template)
    {
        $template = $ai_builder_template;
        $data = $this->validateData($request, $template);

        $template->update($data);

        return redirect()->route('admin.ai-projects.ai-builder-templates.index')
            ->with('success', __('Template updated successfully.'));
    }

    public function destroy(AiBuilderTemplate $ai_builder_template)
    {
        $template = $ai_builder_template;
        $template->delete();

        return redirect()->route('admin.ai-projects.ai-builder-templates.index')
            ->with('success', __('Template deleted successfully.'));
    }

    public function activateAll()
    {
        AiBuilderTemplate::query()->update(['is_active' => true]);

        return redirect()->route('admin.ai-projects.ai-builder-templates.index')
            ->with('success', __('All templates activated successfully.'));
    }

    public function deactivateAll()
    {
        AiBuilderTemplate::query()->update(['is_active' => false]);

        return redirect()->route('admin.ai-projects.ai-builder-templates.index')
            ->with('success', __('All templates deactivated successfully.'));
    }

    public function export(Request $request): Response
    {
        $ids = $request->input('ids', []);
        $query = AiBuilderTemplate::query();
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }
        $templates = $query->orderBy('category')->orderBy('sort_order')->orderBy('name')->get();

        $payload = [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'count' => $templates->count(),
            'templates' => $templates->map(fn ($t) => [
                'template_id' => $t->template_id,
                'category' => $t->category,
                'name' => $t->name,
                'description' => $t->description,
                'tags' => $t->tags,
                'thumbnail' => $t->thumbnail,
                'component_source' => $t->component_source,
                'fields' => $t->fields,
                'defaults' => $t->defaults,
                'example_props' => $t->example_props,
                'is_active' => $t->is_active,
                'sort_order' => $t->sort_order,
            ])->toArray(),
        ];

        $filename = 'ai-builder-templates-' . now()->format('Y-m-d-His') . '.json';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json', 'max:10240'],
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return redirect()->route('admin.ai-projects.ai-builder-templates.index')
                ->with('error', __('Invalid JSON file.'));
        }

        $templates = $data['templates'] ?? $data;
        if (!is_array($templates)) {
            return redirect()->route('admin.ai-projects.ai-builder-templates.index')
                ->with('error', __('No templates found in file.'));
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($templates as $item) {
            $templateId = $item['template_id'] ?? null;
            if (!$templateId || !is_string($templateId)) {
                $skipped++;
                continue;
            }

            $required = ['category', 'name', 'component_source', 'fields', 'defaults'];
            $missing = [];
            foreach ($required as $field) {
                if (!array_key_exists($field, $item)) {
                    $missing[] = $field;
                }
            }
            if ($missing) {
                $errors[] = __('Skipped ":id": missing :fields', ['id' => $templateId, 'fields' => implode(', ', $missing)]);
                $skipped++;
                continue;
            }

            $payload = [
                'template_id' => (string) $templateId,
                'category' => (string) $item['category'],
                'name' => (string) $item['name'],
                'description' => $item['description'] ?? null,
                'tags' => is_array($item['tags'] ?? null) ? $item['tags'] : [],
                'thumbnail' => $item['thumbnail'] ?? null,
                'component_source' => (string) $item['component_source'],
                'fields' => is_array($item['fields']) ? $item['fields'] : [],
                'defaults' => is_array($item['defaults']) ? $item['defaults'] : [],
                'example_props' => is_array($item['example_props'] ?? null) && !empty($item['example_props'])
                ? $item['example_props']
                : (is_array($item['defaults'] ?? null) ? $item['defaults'] : []),
                'is_active' => (bool) ($item['is_active'] ?? true),
                'sort_order' => (int) ($item['sort_order'] ?? 0),
            ];

            $existing = AiBuilderTemplate::where('template_id', $templateId)->first();
            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                $payload['created_by'] = Auth::guard('admin')->id();
                AiBuilderTemplate::create($payload);
                $created++;
            }
        }

        $message = __('Imported: :created new, :updated updated, :skipped skipped.', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
        if ($errors) {
            $message .= ' ' . implode(' ', $errors);
        }

        return redirect()->route('admin.ai-projects.ai-builder-templates.index')
            ->with('success', $message);
    }

    private function validateData(Request $request, ?AiBuilderTemplate $template = null): array
    {
        $rules = [
            'template_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('ai_builder_templates', 'template_id')->ignore($template?->id),
            ],
            'category' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'component_source' => ['required', 'string'],
            'fields' => ['required', 'string'],
            'defaults' => ['required', 'string'],
            'example_props' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];

        $validated = $request->validate($rules);

        return [
            'template_id' => (string) $validated['template_id'],
            'category' => (string) $validated['category'],
            'name' => (string) $validated['name'],
            'description' => $validated['description'] ?? null,
            'tags' => $this->normalizeTags($validated['tags'] ?? ''),
            'thumbnail' => $validated['thumbnail'] ?? null,
            'component_source' => (string) $validated['component_source'],
            'fields' => $this->jsonOrFail($validated['fields'], 'fields'),
            'defaults' => $this->jsonOrFail($validated['defaults'], 'defaults'),
            'example_props' => ($validated['example_props'] ?? null)
                ? $this->jsonOrFail($validated['example_props'], 'example_props')
                : $this->jsonOrFail($validated['defaults'], 'defaults'),
            'is_active' => $request->has('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }

    private function normalizeTags(?string $tags): array
    {
        $tags = trim((string) ($tags ?? ''));
        if ($tags === '') {
            return [];
        }

        $items = array_map('trim', explode(',', $tags));
        $items = array_filter($items, fn ($t) => $t !== '');

        return array_values(array_unique($items));
    }

    private function jsonOrFail(?string $value, string $field): array
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => __('Invalid JSON in :field.', ['field' => $field]),
            ]);
        }

        return $decoded;
    }

    private function categoryOptions(): array
    {
        return [
            'header' => __('Header'),
            'hero' => __('Hero'),
            'about' => __('About'),
            'features' => __('Features'),
            'how-it-works' => __('How It Works'),
            'brands' => __('Brands/Logos'),
            'why-choose-us' => __('Why Choose Us'),
            'testimonials' => __('Testimonials'),
            'integration' => __('Integration'),
            'stats' => __('Stats'),
            'team' => __('Team'),
            'pricing' => __('Pricing'),
            'cta' => __('CTA'),
            'faq' => __('FAQ'),
            'contact' => __('Contact'),
            'footer' => __('Footer'),
            'sidebar' => __('Dashboard: Sidebar'),
            'topbar' => __('Dashboard: Top Bar'),
            'dashboard-stats' => __('Dashboard: Stat Cards'),
            'dashboard-chart' => __('Dashboard: Charts'),
            'data-table' => __('Dashboard: Data Table'),
            'activity-feed' => __('Dashboard: Activity Feed'),
            'quick-actions' => __('Dashboard: Quick Actions'),
        ];
    }
}

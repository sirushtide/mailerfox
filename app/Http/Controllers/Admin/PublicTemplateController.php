<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use App\Models\CustomerGroup;
use App\Models\PublicTemplate;
use App\Models\PublicTemplateCategory;
use App\Services\AI\AiProviderRegistry;
use App\Services\AI\AiProviderRouter;
use App\Services\AI\AiUsageService;
use Illuminate\Http\Request;

class PublicTemplateController extends Controller
{
    public function __construct(
        protected AiUsageService $aiUsage,
        protected AiProviderRouter $router,
    ) {
    }

    private const TEMPLATE_TYPES = ['email', 'campaign', 'transactional', 'autoresponder', 'footer', 'signature'];

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        $type = (string) $request->get('type', '');
        if (!in_array($type, self::TEMPLATE_TYPES, true)) {
            $type = '';
        }

        $templates = PublicTemplate::query()
            ->with(['category'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->when($type !== '', function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $categories = PublicTemplateCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.public-templates.index', compact('templates', 'search', 'categories', 'type'));
    }

    public function create(Request $request)
    {
        $type = (string) $request->query('type', 'email');
        if (!in_array($type, self::TEMPLATE_TYPES, true)) {
            $type = 'email';
        }

        $builderType = (string) $request->query('builder', 'email');
        if (!in_array($builderType, ['email', 'text'], true)) {
            $builderType = 'email';
        }

        $categoryId = $request->query('category_id');
        $categoryId = is_numeric($categoryId) ? (int) $categoryId : null;

        $template = new PublicTemplate([
            'type' => $type,
            'category_id' => $categoryId,
            'is_active' => true,
            'available_to_all_groups' => true,
        ]);

        return view('admin.public-templates.builder', [
            'template' => $template,
            'name' => (string) $request->query('name', ''),
            'builderType' => $builderType,
            'categories' => PublicTemplateCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'customerGroups' => CustomerGroup::query()->orderBy('name')->get(),
            'builderData' => null,
            'formAction' => route('admin.public-templates.store'),
            'formMethod' => 'POST',
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $adminUser = $request->user('admin');

        $builderData = $this->normalizeBuilderData($request->input('grapesjs_data'));

        $template = PublicTemplate::create([
            'category_id' => $data['category_id'] ?? null,
            'created_by_admin_user_id' => $adminUser?->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'email',
            'builder' => $this->resolveBuilderKey($builderData),
            'html_content' => $data['html_content'] ?? null,
            'plain_text_content' => $data['plain_text_content'] ?? null,
            'builder_data' => $builderData,
            'settings' => $data['settings'] ?? [],
            'thumbnail' => $data['thumbnail'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'available_to_all_groups' => (bool) ($data['available_to_all_groups'] ?? true),
        ]);

        $this->syncCustomerGroups($template, (bool) ($data['available_to_all_groups'] ?? true), $data['customer_group_ids'] ?? null);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Template created.'),
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                ],
                'edit_url' => route('admin.public-templates.edit', $template),
                'update_url' => route('admin.public-templates.update', $template),
            ]);
        }

        return redirect()
            ->route('admin.public-templates.edit', $template)
            ->with('success', __('Template created.'));
    }

    public function edit(PublicTemplate $public_template)
    {
        $rawBuilderData = is_array($public_template->builder_data) ? $public_template->builder_data : null;
        $builderName = is_array($rawBuilderData) ? ($rawBuilderData['builder'] ?? null) : null;

        $builderType = $builderName === 'text' || $public_template->builder === 'text' ? 'text' : 'email';
        $builderData = $builderName === 'mailpurse' ? $rawBuilderData : null;

        return view('admin.public-templates.builder', [
            'template' => $public_template->loadMissing('customerGroups'),
            'name' => (string) $public_template->name,
            'builderType' => $builderType,
            'categories' => PublicTemplateCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'customerGroups' => CustomerGroup::query()->orderBy('name')->get(),
            'builderData' => $builderData,
            'formAction' => route('admin.public-templates.update', $public_template),
            'formMethod' => 'PUT',
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, PublicTemplate $public_template)
    {
        $data = $this->validateData($request);

        $builderData = $this->normalizeBuilderData($request->input('grapesjs_data'));

        $public_template->update([
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'email',
            'builder' => $this->resolveBuilderKey($builderData),
            'html_content' => $data['html_content'] ?? null,
            'plain_text_content' => $data['plain_text_content'] ?? null,
            'builder_data' => $builderData,
            'settings' => $data['settings'] ?? (is_array($public_template->settings) ? $public_template->settings : []),
            'thumbnail' => $data['thumbnail'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'available_to_all_groups' => (bool) ($data['available_to_all_groups'] ?? true),
        ]);

        $this->syncCustomerGroups($public_template, (bool) ($data['available_to_all_groups'] ?? true), $data['customer_group_ids'] ?? null);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Template updated.'),
                'edit_url' => route('admin.public-templates.edit', $public_template),
                'update_url' => route('admin.public-templates.update', $public_template),
            ]);
        }

        return redirect()
            ->route('admin.public-templates.edit', $public_template)
            ->with('success', __('Template updated.'));
    }

    public function renderBuilder(Request $request)
    {
        $builderData = $request->input('builder_data');
        if (is_string($builderData)) {
            $builderData = json_decode($builderData, true);
        }

        if (!is_array($builderData) || ($builderData['builder'] ?? null) !== 'mailpurse') {
            return response()->json(['message' => __('Invalid email builder data.')], 422);
        }

        $renderer = app(\App\Services\EmailTemplateRenderer::class);
        $html = $renderer->render($builderData);

        return response()->json([
            'html_content' => $html,
            'plain_text_content' => $renderer->plainText($html),
        ]);
    }

    public function destroy(PublicTemplate $public_template)
    {
        $public_template->delete();

        return redirect()
            ->route('admin.public-templates.index')
            ->with('success', __('Template deleted.'));
    }

    public function aiGenerate(Request $request)
    {
        $admin = $request->user('admin');
        if (!$admin) {
            abort(403);
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:' . implode(',', AiProviderRegistry::providerKeys())],
            'model' => ['nullable', 'string', 'max:100'],
            'prompt' => ['required', 'string', 'max:5000'],
            'builder' => ['nullable', 'string', 'in:unlayer'],
        ]);

        $provider = strtolower(trim((string) $validated['provider']));
        $model = is_string($validated['model'] ?? null) && trim((string) $validated['model']) !== ''
            ? trim((string) $validated['model'])
            : null;
        $prompt = trim((string) $validated['prompt']);

        $log = AiGeneration::create([
            'customer_id' => null,
            'admin_user_id' => $admin->id,
            'tool' => 'template_generator',
            'provider' => $provider,
            'model' => $model,
            'used_admin_keys' => true,
            'prompt' => $prompt,
            'input' => $validated,
            'success' => false,
            'output' => null,
            'tokens_used' => null,
            'error_message' => null,
        ]);

        if ($provider === 'claude') {
            $log->error_message = 'Claude is not supported yet.';
            $log->save();

            return response()->json([
                'message' => $log->error_message,
            ], 501);
        }

        $estimatedTokens = $this->aiUsage->estimateTokens($prompt, 1200);
        if (!$this->aiUsage->canUseAdminTokens($provider, $estimatedTokens)) {
            $log->error_message = 'Admin AI monthly token limit reached. Please try again later or increase the limit in settings.';
            $log->save();

            return response()->json([
                'message' => $log->error_message,
            ], 429);
        }

        $settingKey = AiProviderRegistry::settingKey($provider);
        $apiKey = trim((string) \App\Models\Setting::get($settingKey));

        if ($apiKey === '') {
            $log->error_message = 'Admin AI API key is not configured yet.';
            $log->save();

            return response()->json([
                'message' => $log->error_message,
            ], 422);
        }

        $result = $this->router->generateTemplateUnlayerDesign($model, $prompt, 1200);

        $log->tokens_used = is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : null;

        if (!is_array($result) || !($result['success'] ?? false)) {
            $log->success = false;
            $log->error_message = is_array($result) && is_string($result['message'] ?? null)
                ? $result['message']
                : 'Failed to generate template.';
            $log->save();

            $status = is_numeric($result['status'] ?? null) ? (int) $result['status'] : 502;
            return response()->json([
                'message' => $log->error_message,
            ], $status);
        }

        $design = is_array($result['design'] ?? null) ? $result['design'] : null;
        if (!is_array($design)) {
            $log->success = false;
            $log->error_message = 'AI returned an invalid Unlayer design.';
            $log->save();

            return response()->json([
                'message' => $log->error_message,
            ], 502);
        }

        $html = $this->unlayerPreviewHtmlFromDesign($design);

        $log->success = true;
        $log->output = $html;
        $log->error_message = null;
        $log->save();

        return response()->json([
            'name' => 'AI Generated',
            'description' => null,
            'builder' => 'unlayer',
            'builder_data' => $design,
            'html_content' => $html,
            'plain_text_content' => trim(strip_tags($html)),
            'tokens_used' => is_numeric($log->tokens_used) ? (int) $log->tokens_used : null,
            'used_admin_keys' => true,
        ]);
    }

    private function validateData(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'in:' . implode(',', self::TEMPLATE_TYPES)],
            'category_id' => ['nullable', 'integer', 'exists:public_template_categories,id'],
            'html_content' => ['nullable', 'string'],
            'plain_text_content' => ['nullable', 'string'],
            'grapesjs_data' => ['nullable'],
            'settings' => ['nullable', 'array'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'available_to_all_groups' => ['boolean'],
            'customer_group_ids' => ['nullable', 'array'],
            'customer_group_ids.*' => ['integer', 'exists:customer_groups,id'],
        ];

        $data = $request->validate($rules);

        if (empty($data['plain_text_content']) && !empty($data['html_content'])) {
            $data['plain_text_content'] = trim(preg_replace('/\s+/', ' ', strip_tags((string) $data['html_content'])));
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['available_to_all_groups'] = (bool) ($data['available_to_all_groups'] ?? true);

        return $data;
    }

    private function resolveBuilderKey(?array $builderData): string
    {
        $name = is_array($builderData) ? ($builderData['builder'] ?? null) : null;

        return match ($name) {
            'text' => 'text',
            'unlayer' => 'unlayer',
            'grapesjs' => 'grapesjs',
            default => 'mailpurse',
        };
    }

    private function normalizeBuilderData(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function unlayerPreviewHtmlFromDesign(array $design): string
    {
        $designJson = json_encode(
            $design,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        if (!is_string($designJson) || trim($designJson) === '') {
            $designJson = 'null';
        }

        return <<<HTML
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    html, body { margin: 0; padding: 0; background: #f9fafb; height: 100%; }
    #status {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      min-height: 400px;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
      font-size: 14px;
      font-weight: 500;
      color: #6b7280;
    }
    #status .spinner {
      width: 40px;
      height: 40px;
      border: 3px solid #e5e7eb;
      border-top-color: #4f46e5;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin-bottom: 16px;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    #output { width: 100%; background: #ffffff; }
    #editor { width: 1px; height: 1px; overflow: hidden; position: absolute; left: -9999px; top: -9999px; }
  </style>
</head>
<body>
  <div id="status"><div class="spinner"></div>Rendering preview…</div>
  <div id="output"></div>
  <div id="editor"></div>
  <script src="https://editor.unlayer.com/embed.js"></script>
  <script>
    (function () {
      try {
        var design = $designJson;
        if (!design || typeof design !== 'object') {
          document.getElementById('status').textContent = 'Preview not available: invalid Unlayer design JSON.';
          return;
        }

        unlayer.init({ id: 'editor', displayMode: 'email' });
        unlayer.addEventListener('editor:ready', function () {
          unlayer.loadDesign(design);
          unlayer.exportHtml(function (data) {
            var html = (data && data.html) ? String(data.html) : '';
            if (!html.trim()) {
              document.getElementById('status').textContent = 'Preview not available.';
              return;
            }
            document.getElementById('status').remove();
            document.getElementById('output').innerHTML = html;
          });
        });
      } catch (e) {
        document.getElementById('status').textContent = 'Preview not available.';
      }
    })();
  </script>
</body>
</html>
HTML;
    }

    private function syncCustomerGroups(PublicTemplate $template, bool $availableToAllGroups, mixed $groupIds): void
    {
        if ($availableToAllGroups) {
            $template->customerGroups()->detach();
            return;
        }

        $ids = [];
        if (is_array($groupIds)) {
            $ids = array_values(array_unique(array_map(fn ($v) => (int) $v, $groupIds)));
        }

        if (empty($ids)) {
            $template->customerGroups()->detach();
            return;
        }

        $template->customerGroups()->sync($ids);
    }
}

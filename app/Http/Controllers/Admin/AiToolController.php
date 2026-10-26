<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use App\Models\AiGenerationStep;
use App\Models\AiCustomModel;
use App\Models\Setting;
use App\Services\AI\AiProviderRegistry;
use App\Services\AI\AiProviderRouter;
use App\Services\AI\AiUsageService;
use App\Services\AiBuilder\ModelRouter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiToolController extends Controller
{
    public function __construct(
        protected AiUsageService $aiUsage,
        protected AiProviderRouter $router,
    ) {
    }

    public function dashboard(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $range = (string) $request->query('range', '7d');
        $now = now();

        $start = null;
        $end = null;

        if ($range === '30d') {
            $start = $now->copy()->subDays(29)->startOfDay();
            $end = $now->copy()->endOfDay();
        } elseif ($range === 'custom') {
            $from = $request->query('from');
            $to = $request->query('to');

            $start = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->subDays(6)->startOfDay();
            $end = $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay();
        } else {
            $range = '7d';
            $start = $now->copy()->subDays(6)->startOfDay();
            $end = $now->copy()->endOfDay();
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            $range = 'custom';
        }

        $providerConfigs = AiProviderRegistry::providers();

        $byProviderModel = AiGeneration::query()
            ->where('used_admin_keys', true)
            ->where('success', true)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("provider, COALESCE(model, '') as model, SUM(COALESCE(tokens_used, 0)) as tokens")
            ->groupBy('provider', DB::raw("COALESCE(model, '')"))
            ->get();

        $providerStats = [];
        $providerDailyStats = [];
        $breakdownRows = [];
        $apiKeyRows = [];
        $providerControls = [];
        $pricingProviders = [];

        $totalTokensUsed = 0;
        $totalCostCents = 0;
        $totalLimit = 0;
        $hasUnlimited = false;

        $totalTokensUsedToday = 0;
        $totalDailyLimit = 0;
        $hasUnlimitedDaily = false;

        foreach ($providerConfigs as $providerKey => $providerConfig) {
            $providerLabel = (string) ($providerConfig['label'] ?? $providerKey);
            $limit = (int) $this->aiUsage->getAdminMonthlyLimit($providerKey);

            $dailyLimit = (int) $this->aiUsage->getAdminDailyLimit($providerKey);
            $usedToday = (int) $this->aiUsage->getAdminTokensUsedToday($providerKey);
            $dailyPercent = null;
            if ($dailyLimit > 0) {
                $dailyPercent = min(100, ($usedToday / $dailyLimit) * 100);
            }

            $used = (int) $byProviderModel->where('provider', $providerKey)->sum('tokens');

            $estimatedCostCents = 0;
            $rowsForProvider = $byProviderModel->where('provider', $providerKey)->values();
            foreach ($rowsForProvider as $row) {
                $model = is_string($row->model ?? null) ? (string) $row->model : '';
                $tokens = is_numeric($row->tokens ?? null) ? (int) $row->tokens : 0;
                $inputCostPer1m = $this->aiUsage->getAdminInputCostPer1mCentsForModel($providerKey, $model !== '' ? $model : null);
                $outputCostPer1m = $this->aiUsage->getAdminOutputCostPer1mCentsForModel($providerKey, $model !== '' ? $model : null);
                $inputTokens = (int) round($tokens * 0.6);
                $outputTokens = $tokens - $inputTokens;
                $estimatedCostCents += (int) round(($inputTokens / 1000000) * $inputCostPer1m + ($outputTokens / 1000000) * $outputCostPer1m);
            }

            $costPer1mCents = (int) $this->aiUsage->getAdminCostPer1mCents($providerKey);

            $percent = null;
            if ($limit > 0) {
                $percent = $limit > 0 ? min(100, ($used / $limit) * 100) : null;
            }

            $providerStats[$providerKey] = [
                'label' => $providerLabel,
                'tokens_used' => $used,
                'token_limit' => $limit,
                'percent' => $percent,
                'cost_per_1m_cents' => $costPer1mCents,
                'estimated_cost_cents' => $estimatedCostCents,
            ];

            $providerDailyStats[$providerKey] = [
                'label' => $providerLabel,
                'tokens_used' => $usedToday,
                'token_limit' => $dailyLimit,
                'percent' => $dailyPercent,
            ];

            $totalTokensUsed += $used;
            $totalCostCents += $estimatedCostCents;

            if ($limit > 0) {
                $totalLimit += $limit;
            } else {
                $hasUnlimited = true;
            }

            $totalTokensUsedToday += $usedToday;
            if ($dailyLimit > 0) {
                $totalDailyLimit += $dailyLimit;
            } else {
                $hasUnlimitedDaily = true;
            }

            $rows = $rowsForProvider;
            foreach ($rows as $row) {
                $model = is_string($row->model ?? null) ? (string) $row->model : '';
                $tokens = is_numeric($row->tokens ?? null) ? (int) $row->tokens : 0;

                $inputCostPer1m = $this->aiUsage->getAdminInputCostPer1mCentsForModel($providerKey, $model !== '' ? $model : null);
                $outputCostPer1m = $this->aiUsage->getAdminOutputCostPer1mCentsForModel($providerKey, $model !== '' ? $model : null);
                $inputTokens = (int) round($tokens * 0.6);
                $outputTokens = $tokens - $inputTokens;
                $modelCost = (int) round(($inputTokens / 1000000) * $inputCostPer1m + ($outputTokens / 1000000) * $outputCostPer1m);

                $breakdownRows[] = [
                    'provider' => $providerKey,
                    'provider_label' => $providerLabel,
                    'model' => $model !== '' ? $model : 'Default',
                    'tokens' => $tokens,
                    'cost_per_1m_cents' => max($inputCostPer1m, $outputCostPer1m),
                    'input_cost_per_1m_cents' => $inputCostPer1m,
                    'output_cost_per_1m_cents' => $outputCostPer1m,
                    'estimated_cost_cents' => $modelCost,
                ];
            }

            $settingKey = (string) ($providerConfig['setting_key'] ?? '');
            $apiKeyValue = $settingKey !== '' && is_string(Setting::get($settingKey)) ? trim((string) Setting::get($settingKey)) : '';

            $providerModelRows = [];
            foreach (AiProviderRegistry::allModels($providerKey) as $modelValue) {
                $inputCost = $this->aiUsage->getAdminInputCostPer1mCentsForModel($providerKey, $modelValue);
                $outputCost = $this->aiUsage->getAdminOutputCostPer1mCentsForModel($providerKey, $modelValue);

                $providerModelRows[] = [
                    'slug' => $this->modelSlug($modelValue),
                    'name' => $modelValue,
                    'enabled' => $this->router->isModelEnabled($providerKey, $modelValue),
                    'input_cost_per_1m_cents' => $inputCost,
                    'output_cost_per_1m_cents' => $outputCost,
                ];
            }

            $apiKeyRows[] = [
                'provider' => $providerKey,
                'label' => $providerLabel,
                'setting_key' => $settingKey,
                'has_value' => $apiKeyValue !== '',
                'help_url' => $this->providerHelpUrls()[$providerKey] ?? null,
            ];

            $providerControls[$providerKey] = [
                'provider' => $providerKey,
                'label' => $providerLabel,
                'enabled' => $this->router->isProviderEnabled($providerKey),
                'models' => $providerModelRows,
            ];

            $pricingProviders[$providerKey] = [
                'provider' => $providerKey,
                'label' => $providerLabel,
                'default_input_cost_per_1m_cents' => $this->aiUsage->getAdminInputCostPer1mCents($providerKey),
                'default_output_cost_per_1m_cents' => $this->aiUsage->getAdminOutputCostPer1mCents($providerKey),
                'models' => $providerModelRows,
            ];
        }

        usort($breakdownRows, function ($a, $b) {
            $p = strcmp((string) ($a['provider'] ?? ''), (string) ($b['provider'] ?? ''));
            if ($p !== 0) {
                return $p;
            }

            return ((int) ($b['tokens'] ?? 0)) <=> ((int) ($a['tokens'] ?? 0));
        });

        $totalPercent = null;
        if (!$hasUnlimited && $totalLimit > 0) {
            $totalPercent = min(100, ($totalTokensUsed / $totalLimit) * 100);
        }

        $totalDailyPercent = null;
        if (!$hasUnlimitedDaily && $totalDailyLimit > 0) {
            $totalDailyPercent = min(100, ($totalTokensUsedToday / $totalDailyLimit) * 100);
        }

        $rangeLabel = 'Last 7 Days';
        if ($range === '30d') {
            $rangeLabel = 'Last 30 Days';
        } elseif ($range === 'custom') {
            $rangeLabel = 'Custom Range';
        }

        $builderStages = $this->builderStages();
        $builderDefaults = [];

        $builderStageCosts = [];
        foreach ($builderStages as $stage => $label) {
            $model = ModelRouter::resolve($stage);
            $builderDefaults[$stage] = $model;
            $provider = ModelRouter::detectProvider($model);
            $inputCents = $this->aiUsage->getAdminInputCostPer1mCentsForModel($provider, $model);
            $outputCents = $this->aiUsage->getAdminOutputCostPer1mCentsForModel($provider, $model);
            $builderStageCosts[$stage] = [
                'label' => $label,
                'model' => $model,
                'provider' => $provider,
                'input_cents_per_1m' => $inputCents,
                'output_cents_per_1m' => $outputCents,
            ];
        }

        $builderModelMeta = [];
        $enabledProviderKeys = [];
        foreach ($providerConfigs as $providerKey => $providerConfig) {
            if ($this->router->isProviderEnabled($providerKey)) {
                $enabledProviderKeys[] = $providerKey;
            }
            foreach (AiProviderRegistry::allModels($providerKey) as $modelValue) {
                $builderModelMeta[$modelValue] = [
                    'provider' => $providerKey,
                    'provider_label' => $providerConfig['label'] ?? $providerKey,
                    'input_cents_per_1m' => $this->aiUsage->getAdminInputCostPer1mCentsForModel($providerKey, $modelValue),
                    'output_cents_per_1m' => $this->aiUsage->getAdminOutputCostPer1mCentsForModel($providerKey, $modelValue),
                ];
            }
        }

        $generations = AiGeneration::query()
            ->where('used_admin_keys', true)
            ->where('type', '!=', 'iteration')
            ->with(['steps', 'iterations' => fn ($q) => $q->with('steps')->orderBy('created_at')])
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'generations_page')
            ->withQueryString();

        $generationDetail = null;
        $detailId = $request->query('generation_id');
        if ($detailId) {
            $generationDetail = AiGeneration::query()
                ->where('used_admin_keys', true)
                ->with(['steps', 'iterations' => fn ($q) => $q->with('steps')])
                ->find($detailId);
        }

        $ragSettings = [
            'rag_chunk_size' => (int) Setting::get('rag_chunk_size', 500),
            'rag_chunk_overlap' => (int) Setting::get('rag_chunk_overlap', 50),
            'rag_top_k' => (int) Setting::get('rag_top_k', 5),
            'rag_max_context_tokens' => (int) Setting::get('rag_max_context_tokens', 3000),
            'rag_default_provider' => (string) Setting::get('rag_default_provider', 'chatgpt'),
        ];
        $ragProviderOptions = [];
        foreach ($providerConfigs as $providerKey => $providerConfig) {
            $ragProviderOptions[$providerKey] = $providerConfig['label'] ?? $providerKey;
        }

        $initialSuggestions = Setting::get('ai_builder_initial_suggestions', []);
        $initialSuggestions = is_array($initialSuggestions) ? $initialSuggestions : [];

        return view('admin.ai-tools.dashboard_v2', [
            'range' => $range,
            'startDate' => $start,
            'endDate' => $end,
            'rangeLabel' => $rangeLabel,
            'providerStats' => $providerStats,
            'providerDailyStats' => $providerDailyStats,
            'breakdownRows' => $breakdownRows,
            'totalTokensUsed' => $totalTokensUsed,
            'totalLimit' => $hasUnlimited ? null : $totalLimit,
            'totalPercent' => $totalPercent,
            'totalCostCents' => $totalCostCents,
            'totalTokensUsedToday' => $totalTokensUsedToday,
            'totalDailyLimit' => $hasUnlimitedDaily ? null : $totalDailyLimit,
            'totalDailyPercent' => $totalDailyPercent,
            'apiKeyRows' => $apiKeyRows,
            'providerControls' => $providerControls,
            'pricingProviders' => $pricingProviders,
            'providerConfigs' => $providerConfigs,
            'builderStages' => $builderStages,
            'builderDefaults' => $builderDefaults,
            'builderStageCosts' => $builderStageCosts,
            'builderModelMeta' => $builderModelMeta,
            'generations' => $generations,
            'generationDetail' => $generationDetail,
            'ragSettings' => $ragSettings,
            'ragProviderOptions' => $ragProviderOptions,
            'pageGenerationMode' => Setting::get('ai_builder_section_source', 'templates'),
            'enabledProviderKeys' => $enabledProviderKeys,
            'initialSuggestions' => $initialSuggestions,
        ]);
    }

    public function emailTextGenerator()
    {
        return view('admin.ai-tools.email-text-generator');
    }

    public function updateRagSettings(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $validated = $request->validate([
            'rag_chunk_size' => ['required', 'integer', 'min:100', 'max:10000'],
            'rag_chunk_overlap' => ['required', 'integer', 'min:0', 'max:2000'],
            'rag_top_k' => ['required', 'integer', 'min:1', 'max:50'],
            'rag_max_context_tokens' => ['required', 'integer', 'min:500', 'max:32000'],
            'rag_default_provider' => ['required', 'string', 'in:' . implode(',', array_keys(AiProviderRegistry::providers()))],
        ]);

        Setting::set('rag_chunk_size', (int) $validated['rag_chunk_size'], 'ai', 'integer');
        Setting::set('rag_chunk_overlap', (int) $validated['rag_chunk_overlap'], 'ai', 'integer');
        Setting::set('rag_top_k', (int) $validated['rag_top_k'], 'ai', 'integer');
        Setting::set('rag_max_context_tokens', (int) $validated['rag_max_context_tokens'], 'ai', 'integer');
        Setting::set('rag_default_provider', (string) $validated['rag_default_provider'], 'ai', 'string');

        return redirect()
            ->route('admin.ai-tools.dashboard', ['tab' => 'rag'])
            ->with('success', 'RAG configuration updated.');
    }

    public function updatePageGenerationMode(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $validated = $request->validate([
            'ai_builder_section_source' => ['required', 'string', 'in:templates,ai_free,templates_test'],
        ]);

        Setting::set(
            'ai_builder_section_source',
            (string) $validated['ai_builder_section_source'],
            'ai',
            'string'
        );

        return redirect()
            ->route('admin.ai-tools.dashboard', ['tab' => 'page-generation-mode'])
            ->with('success', 'Page generation mode updated.');
    }

    public function updateApiKeys(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $rules = [];
        foreach (AiProviderRegistry::providers() as $providerConfig) {
            $settingKey = (string) ($providerConfig['setting_key'] ?? '');
            if ($settingKey !== '') {
                $rules[$settingKey] = ['nullable', 'string', 'max:5000'];
            }
        }

        $validated = $request->validate($rules);

        foreach (AiProviderRegistry::providers() as $providerConfig) {
            $settingKey = (string) ($providerConfig['setting_key'] ?? '');
            if ($settingKey === '' || !array_key_exists($settingKey, $validated)) {
                continue;
            }

            $value = is_string($validated[$settingKey] ?? null) ? trim((string) $validated[$settingKey]) : '';
            if ($value === '' || $value === '********') {
                continue;
            }

            Setting::set($settingKey, $value, 'ai', 'string');
        }

        return redirect()
            ->route('admin.ai-tools.dashboard', ['tab' => 'api-keys'])
            ->with('success', 'AI API keys updated.');
    }

    public function updateProviderControls(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $providerEnabled = is_array($request->input('provider_enabled')) ? $request->input('provider_enabled') : [];
        $modelEnabled = is_array($request->input('model_enabled')) ? $request->input('model_enabled') : [];

        foreach (AiProviderRegistry::providers() as $providerKey => $providerConfig) {
            $providerIsEnabled = array_key_exists($providerKey, $providerEnabled);

            Setting::set(
                'ai_provider_enabled_' . $providerKey,
                $providerIsEnabled ? 1 : 0,
                'ai',
                'boolean'
            );

            $allModels = AiProviderRegistry::allModels($providerKey);
            $checkedModels = $modelEnabled[$providerKey] ?? [];

            foreach ($allModels as $modelValue) {
                $slug = $this->modelSlug($modelValue);

                if (! $providerIsEnabled) {
                    $enabled = 0;
                } elseif (empty($checkedModels)) {
                    $enabled = 1;
                } else {
                    $enabled = array_key_exists($slug, $checkedModels) ? 1 : 0;
                }

                Setting::set(
                    'ai_model_enabled_' . $providerKey . '_' . $slug,
                    $enabled,
                    'ai',
                    'boolean'
                );
            }
        }

        return redirect()
            ->route('admin.ai-tools.dashboard', ['tab' => 'provider-controls'])
            ->with('success', 'Provider and model availability updated.');
    }

    public function updatePricing(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $providerInputCosts = is_array($request->input('provider_input_costs')) ? $request->input('provider_input_costs') : [];
        $providerOutputCosts = is_array($request->input('provider_output_costs')) ? $request->input('provider_output_costs') : [];
        $modelInputCosts = is_array($request->input('model_input_costs')) ? $request->input('model_input_costs') : [];
        $modelOutputCosts = is_array($request->input('model_output_costs')) ? $request->input('model_output_costs') : [];

        foreach (AiProviderRegistry::providers() as $providerKey => $providerConfig) {
            Setting::set(
                'ai_provider_input_cost_per_1m_' . $providerKey,
                $this->normalizeIntegerCost($providerInputCosts[$providerKey] ?? 0),
                'ai',
                'integer'
            );

            Setting::set(
                'ai_provider_output_cost_per_1m_' . $providerKey,
                $this->normalizeIntegerCost($providerOutputCosts[$providerKey] ?? 0),
                'ai',
                'integer'
            );

            foreach (AiProviderRegistry::allModels($providerKey) as $modelValue) {
                $slug = $this->modelSlug($modelValue);

                Setting::set(
                    'ai_model_input_cost_per_1m_' . $providerKey . '_' . $slug,
                    $this->normalizeIntegerCost($modelInputCosts[$providerKey][$slug] ?? 0),
                    'ai',
                    'integer'
                );

                Setting::set(
                    'ai_model_output_cost_per_1m_' . $providerKey . '_' . $slug,
                    $this->normalizeIntegerCost($modelOutputCosts[$providerKey][$slug] ?? 0),
                    'ai',
                    'integer'
                );
            }
        }

        return redirect()
            ->route('admin.ai-tools.dashboard', ['tab' => 'api-pricing'])
            ->with('success', 'AI pricing updated.');
    }

    public function updateBuilderDefaults(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $validated = $request->validate([
            'enhancer' => ['nullable', 'string', 'max:100'],
            'orchestrator' => ['nullable', 'string', 'max:100'],
            'worker' => ['nullable', 'string', 'max:100'],
            'iteration' => ['nullable', 'string', 'max:100'],
        ]);

        $stages = array_keys($this->builderStages());
        foreach ($stages as $stage) {
            $value = is_string($validated[$stage] ?? null) ? trim((string) $validated[$stage]) : '';
            $key = 'ai_builder_model_' . $stage;
            if ($value !== '') {
                Setting::set($key, $value, 'ai_builder', 'string');
            } else {
                Setting::where('key', $key)->delete();
            }
        }

        // These stages are deterministic local processing steps, not AI model calls.
        Setting::whereIn('key', [
            'ai_builder_model_validator',
            'ai_builder_model_assembler',
        ])->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'AI builder model defaults updated.',
            ]);
        }

        return redirect()
            ->route('admin.ai-tools.dashboard', ['tab' => 'builder-models'])
            ->with('success', 'AI builder model defaults updated.');
    }

    public function updateSuggestions(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $validated = $request->validate([
            'suggestions' => ['nullable', 'array'],
            'suggestions.*.title' => ['nullable', 'string', 'max:255'],
            'suggestions.*.description' => ['nullable', 'string', 'max:4000'],
            'suggestions.*.icon' => ['nullable', 'string', 'max:10'],
        ]);

        $suggestions = [];
        foreach (is_array($validated['suggestions'] ?? null) ? $validated['suggestions'] : [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = is_string($item['title'] ?? null) ? trim($item['title']) : '';
            $description = is_string($item['description'] ?? null) ? trim($item['description']) : '';
            if ($title === '' || $description === '') {
                continue;
            }
            $icon = is_string($item['icon'] ?? null) ? trim($item['icon']) : '';
            $suggestions[] = [
                'title' => mb_substr($title, 0, 255),
                'description' => mb_substr($description, 0, 4000),
                'icon' => $icon !== '' ? mb_substr($icon, 0, 10) : null,
            ];
        }

        if (count($suggestions) > 0) {
            Setting::set('ai_builder_initial_suggestions', $suggestions, 'ai_builder', 'json');
        } else {
            Setting::where('key', 'ai_builder_initial_suggestions')->delete();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'AI builder initial suggestions updated.',
            ]);
        }

        return redirect()
            ->route('admin.ai-tools.dashboard', ['tab' => 'suggestions'])
            ->with('success', 'AI builder initial suggestions updated.');
    }

    protected function builderStages(): array
    {
        return [
            'enhancer' => 'Prompt Enhancer',
            'orchestrator' => 'Orchestrator',
            'worker' => 'Component Worker',
            'iteration' => 'Iteration Handler',
        ];
    }

    protected function providerHelpUrls(): array
    {
        return [
            'openai'      => 'https://platform.openai.com/api-keys',
            'anthropic'   => 'https://console.anthropic.com/settings/keys',
            'gemini'      => 'https://aistudio.google.com/apikey',
            'deepseek'    => 'https://platform.deepseek.com/api_keys',
            'moonshot'    => 'https://platform.moonshot.cn/console/api-keys',
            'glm'         => 'https://open.bigmodel.cn/usercenter/apikeys',
            'grok'        => 'https://console.x.ai',
            'qwen'        => 'https://bailian.console.aliyun.com/#/api-key',
            'minimax'     => 'https://platform.minimax.io/platform/userCenter/basicInformation/interfaceKey',
            'xiaomi'      => 'https://dev.mi.com/',
            'openrouter'  => 'https://openrouter.ai/keys',
        ];
    }

    protected function modelSlug(string $model): string
    {
        $model = strtolower(trim($model));
        $model = preg_replace('/[^a-z0-9]+/', '_', $model) ?? $model;

        return trim($model, '_');
    }

    protected function normalizeIntegerCost(mixed $value): int
    {
        return max(0, is_numeric($value) ? (int) $value : 0);
    }

    public function generateEmailText(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:' . implode(',', AiProviderRegistry::providerKeys())],
            'model' => ['nullable', 'string', 'max:100'],
            'email_type' => ['required', 'string', 'max:255'],
            'tone' => ['required', 'string', 'max:255'],
            'audience' => ['required', 'string', 'max:255'],
            'audience_custom' => ['nullable', 'string', 'max:255'],
            'length' => ['required', 'string', 'max:255'],
            'word_count' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'objective' => ['required', 'string', 'max:255'],
            'subject_idea' => ['nullable', 'string', 'max:255'],
            'context' => ['nullable', 'string', 'max:5000'],
            'key_points' => ['nullable', 'string', 'max:5000'],
            'cta' => ['nullable', 'string', 'max:500'],
            'offer_details' => ['nullable', 'string', 'max:2000'],
        ]);

        $audience = $validated['audience'];
        if ($audience === 'custom' && is_string($validated['audience_custom'] ?? null) && trim((string) $validated['audience_custom']) !== '') {
            $audience = trim((string) $validated['audience_custom']);
        }

        $length = $validated['length'];
        $wordCount = null;
        if ($length === 'custom' && is_numeric($validated['word_count'] ?? null)) {
            $wordCount = (int) $validated['word_count'];
        }

        $subjectIdea = is_string($validated['subject_idea'] ?? null) ? trim((string) $validated['subject_idea']) : '';
        $context = is_string($validated['context'] ?? null) ? trim((string) $validated['context']) : '';
        $keyPoints = is_string($validated['key_points'] ?? null) ? trim((string) $validated['key_points']) : '';
        $cta = is_string($validated['cta'] ?? null) ? trim((string) $validated['cta']) : '';
        $offerDetails = is_string($validated['offer_details'] ?? null) ? trim((string) $validated['offer_details']) : '';

        $promptParts = [];
        $promptParts[] = 'Write an email.';
        $promptParts[] = 'Output plain text only.';
        $promptParts[] = 'Include a subject line and the email body.';
        $promptParts[] = 'Email type: ' . $validated['email_type'] . '.';
        $promptParts[] = 'Tone: ' . $validated['tone'] . '.';
        $promptParts[] = 'Target audience: ' . $audience . '.';
        $promptParts[] = 'Objective: ' . $validated['objective'] . '.';

        if ($wordCount !== null) {
            $promptParts[] = 'Length: approximately ' . $wordCount . ' words.';
        } else {
            $promptParts[] = 'Length: ' . $length . '.';
        }

        if ($subjectIdea !== '') {
            $promptParts[] = 'Subject idea: ' . $subjectIdea . '.';
        }
        if ($context !== '') {
            $promptParts[] = 'Context: ' . $context;
        }
        if ($keyPoints !== '') {
            $promptParts[] = 'Key points to include: ' . $keyPoints;
        }
        if ($cta !== '') {
            $promptParts[] = 'CTA: ' . $cta;
        }
        if ($offerDetails !== '') {
            $promptParts[] = 'Product/Offer details: ' . $offerDetails;
        }

        $prompt = implode("\n", $promptParts);

        $log = AiGeneration::create([
            'customer_id' => null,
            'admin_user_id' => $admin->id,
            'tool' => 'email_text_generator',
            'provider' => $validated['provider'],
            'model' => is_string($validated['model'] ?? null) && trim((string) $validated['model']) !== '' ? trim((string) $validated['model']) : null,
            'used_admin_keys' => true,
            'prompt' => $prompt,
            'input' => $validated,
            'success' => false,
            'output' => null,
            'tokens_used' => null,
            'error_message' => null,
        ]);

        $model = is_string($validated['model'] ?? null) && trim((string) $validated['model']) !== '' ? trim((string) $validated['model']) : null;
        $effectiveModel = $model ?? AiProviderRegistry::defaultModel($validated['provider']);

        $availabilityMessage = $this->router->availabilityMessage($effectiveModel);
        if ($availabilityMessage !== null) {
            $log->success = false;
            $log->error_message = $availabilityMessage;
            $log->save();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $log->error_message,
                    'generation_id' => $log->id,
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $log->error_message);
        }

        $estimatedTokens = $this->aiUsage->estimateTokens($prompt, 1200);
        if (!$this->aiUsage->canUseAdminTokens($validated['provider'], $estimatedTokens)) {
            $log->success = false;
            $log->error_message = 'Admin AI monthly token limit reached. Please try again later or increase the limit in settings.';
            $log->save();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $log->error_message,
                    'generation_id' => $log->id,
                ], 429);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $log->error_message);
        }

        $apiKey = Setting::get(AiProviderRegistry::settingKey($validated['provider']));

        if (!is_string($apiKey) || trim($apiKey) === '') {
            $log->success = false;
            $log->error_message = 'Admin AI API key is not configured yet.';
            $log->save();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $log->error_message,
                    'generation_id' => $log->id,
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $log->error_message);
        }

        $result = $this->router->generateText($effectiveModel, $prompt, 1200);

        $log->tokens_used = is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : null;

        if (is_array($result) && ($result['success'] ?? false) === true) {
            $log->success = true;
            $log->output = (string) ($result['text'] ?? '');
            $log->error_message = null;
            $log->save();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'text' => (string) ($result['text'] ?? ''),
                    'tokens' => is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : null,
                    'used_admin_keys' => true,
                    'generation_id' => $log->id,
                ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('success', 'Email text generated.')
                ->with('generated_text', (string) ($result['text'] ?? ''));
        }

        $log->success = false;
        $log->output = null;
        $log->error_message = is_string($result['message'] ?? null) ? (string) $result['message'] : 'AI generation failed.';
        $log->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $log->error_message,
                'generation_id' => $log->id,
            ], 502);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $log->error_message);
    }

    public function searchExternalModels(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $request->validate([
            'provider' => ['required', 'string', 'in:openrouter'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $provider = $request->input('provider');
        $query = $request->input('q', '');

        if ($provider === 'openrouter') {
            return $this->searchOpenrouterModels($query);
        }

        return response()->json(['models' => []]);
    }

    private function searchOpenrouterModels(string $query): \Illuminate\Http\JsonResponse
    {
        $url = 'https://openrouter.ai/api/v1/models';
        if ($query !== '') {
            $url .= '?' . http_build_query(['q' => $query]);
        }

        $apiKey = Setting::get('openrouter_api_key');
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'MagicBuilds/1.0',
        ];
        if (is_string($apiKey) && trim($apiKey) !== '') {
            $headers['Authorization'] = 'Bearer ' . trim($apiKey);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->timeout(15)
                ->get($url);

            if (! $response->successful()) {
                return response()->json(['models' => [], 'error' => 'Failed to fetch models from OpenRouter.'], 502);
            }

            $data = $response->json();
            $models = $data['data'] ?? [];

            $existingCustomModels = AiCustomModel::where('provider', 'openrouter')
                ->pluck('model_id')
                ->toArray();

            $providerLogoMap = [
                'openai' => 'openai.png',
                'anthropic' => 'anthropic.png',
                'google' => 'google.png',
                'meta-llama' => 'meta.png',
                'mistralai' => 'mistral.png',
                'deepseek' => 'deepseek.png',
                'qwen' => 'qwen.png',
                'x-ai' => 'xai.png',
                'kimi' => 'kimi.png',
                'moonshot' => 'moonshot.png',
                'zhipu' => 'zai.png',
                'minimax' => 'minimax.png',
                'xiaomi' => 'xiaomi.png',
            ];
            $defaultLogo = 'openrouter.png';

            $results = [];
            foreach ($models as $model) {
                $modelId = $model['id'] ?? '';
                if ($modelId === '') {
                    continue;
                }

                $prefix = explode('/', $modelId)[0] ?? '';
                $logoFile = $providerLogoMap[$prefix] ?? $defaultLogo;

                $pricing = $model['pricing'] ?? [];
                $inputPrice = (float) ($pricing['prompt'] ?? 0);
                $outputPrice = (float) ($pricing['completion'] ?? 0);

                $results[] = [
                    'id' => $modelId,
                    'name' => $model['name'] ?? $modelId,
                    'description' => $model['description'] ?? '',
                    'logo' => asset('images/model-logos/' . $logoFile),
                    'context_length' => $model['context_length'] ?? null,
                    'input_cost_per_1m_cents' => (int) round($inputPrice * 1000000 * 100),
                    'output_cost_per_1m_cents' => (int) round($outputPrice * 1000000 * 100),
                    'already_added' => in_array($modelId, $existingCustomModels),
                ];
            }

            return response()->json(['models' => $results]);
        } catch (\Exception $e) {
            \Log::error('OpenRouter model search failed: ' . $e->getMessage());
            return response()->json(['models' => [], 'error' => 'Connection failed.'], 502);
        }
    }

    public function addCustomModel(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:openrouter'],
            'model_id' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'context_length' => ['nullable', 'integer', 'min:0'],
            'input_cost_per_1m_cents' => ['required', 'integer', 'min:0'],
            'output_cost_per_1m_cents' => ['required', 'integer', 'min:0'],
        ]);

        $existing = AiCustomModel::where('provider', $validated['provider'])
            ->where('model_id', $validated['model_id'])
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Model already added.'], 422);
        }

        $customModel = AiCustomModel::create([
            'provider' => $validated['provider'],
            'model_id' => $validated['model_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'context_length' => $validated['context_length'] ?? null,
            'input_cost_per_1m_cents' => $validated['input_cost_per_1m_cents'],
            'output_cost_per_1m_cents' => $validated['output_cost_per_1m_cents'],
            'is_enabled' => true,
        ]);

        $slug = $this->modelSlug($validated['model_id']);

        Setting::set(
            'ai_model_enabled_' . $validated['provider'] . '_' . $slug,
            1,
            'ai',
            'boolean'
        );

        Setting::set(
            'ai_model_input_cost_per_1m_' . $validated['provider'] . '_' . $slug,
            $validated['input_cost_per_1m_cents'],
            'ai',
            'integer'
        );

        Setting::set(
            'ai_model_output_cost_per_1m_' . $validated['provider'] . '_' . $slug,
            $validated['output_cost_per_1m_cents'],
            'ai',
            'integer'
        );

        return response()->json([
            'success' => true,
            'model' => [
                'id' => $customModel->id,
                'model_id' => $customModel->model_id,
                'name' => $customModel->name,
                'provider' => $customModel->provider,
            ],
        ]);
    }

    public function removeCustomModel(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $validated = $request->validate([
            'provider' => ['required', 'string'],
            'model_id' => ['required', 'string'],
        ]);

        $customModel = AiCustomModel::where('provider', $validated['provider'])
            ->where('model_id', $validated['model_id'])
            ->first();

        if (!$customModel) {
            return response()->json(['error' => 'Model not found.'], 404);
        }

        $slug = $this->modelSlug($validated['model_id']);

        Setting::where('key', 'ai_model_enabled_' . $validated['provider'] . '_' . $slug)->delete();
        Setting::where('key', 'ai_model_input_cost_per_1m_' . $validated['provider'] . '_' . $slug)->delete();
        Setting::where('key', 'ai_model_output_cost_per_1m_' . $validated['provider'] . '_' . $slug)->delete();

        $customModel->delete();

        return response()->json(['success' => true]);
    }

    public function listCustomModels(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            abort(403);
        }

        $request->validate([
            'provider' => ['required', 'string'],
        ]);

        $models = AiCustomModel::where('provider', $request->input('provider'))
            ->orderBy('name')
            ->get();

        return response()->json(['models' => $models]);
    }
}

<?php

namespace App\Services\PageBuilderAi;

use App\Services\AI\AiProviderRouter;
use App\Services\AiBuilder\ModelRouter;
use App\Services\AiBuilder\Validator;
use Illuminate\Support\Str;

final class PageBuilderAiService
{
    public function __construct(
        private AiProviderRouter $router,
        private PageBuilderAiTools $tools,
        private Validator $validator,
        private PageBuilderAiModelStrategy $strategies,
    ) {
    }

    public function message(array $request, ?callable $onEvent = null): array
    {
        $operation = (string) ($request['operation'] ?? PageBuilderAiContract::OPERATION_SECTION);
        if (!in_array($operation, PageBuilderAiContract::operations(), true)) {
            throw new \InvalidArgumentException('Unsupported page-builder AI operation.');
        }

        $model = $this->resolveModel($request);

        $page = is_array($request['page'] ?? null) ? $request['page'] : ['sections' => []];
        $prompt = trim((string) ($request['prompt'] ?? $request['change_request'] ?? ''));
        $selectedSection = is_array($request['selected_section'] ?? null) ? $request['selected_section'] : null;
        $sectionIndex = (int) ($request['section_index'] ?? count($page['sections'] ?? []));
        $mode = in_array(($request['mode'] ?? 'page'), ['page', 'email'], true) ? $request['mode'] : 'page';

        $agent = new PageBuilderAiAgent($this->router, $this->tools, $this->validator);
        $outcome = $agent->run(
            $operation,
            $prompt,
            $page,
            $selectedSection,
            $sectionIndex,
            $model,
            $mode,
            $onEvent,
        );

        return [
            'request_id' => (string) ($request['request_id'] ?? Str::uuid()),
            'operation' => $operation,
            'events' => $outcome['events'],
            'result' => $outcome['result'],
            'model' => $model,
            'tokens' => $outcome['tokens'],
            'rounds' => $outcome['rounds'],
        ];
    }

    private function resolveModel(array $request): string
    {
        $model = (string) ($request['model'] ?? 'auto');
        if ($model === 'auto' || $model === '') {
            $strategy = (string) ($request['strategy'] ?? 'auto');
            $strategyModel = collect($this->strategies->catalog()['strategies'] ?? [])
                ->firstWhere('value', $strategy)['model'] ?? null;

            return is_string($strategyModel) && $strategyModel !== ''
                ? $strategyModel
                : ModelRouter::resolve('worker');
        }

        return $model;
    }
}

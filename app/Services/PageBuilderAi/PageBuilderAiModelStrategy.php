<?php

namespace App\Services\PageBuilderAi;

use App\Services\AiBuilder\ModelRouter;
use App\Services\AI\AiProviderRegistry;
use App\Services\AI\AiProviderRouter;

final class PageBuilderAiModelStrategy
{
    public function __construct(private AiProviderRouter $router)
    {
    }

    public function catalog(): array
    {
        $models = [];
        foreach (AiProviderRegistry::providerKeys() as $provider) {
            if (!$this->router->isProviderEnabled($provider)) {
                continue;
            }
            foreach (AiProviderRegistry::models($provider) as $model) {
                if ($this->router->isModelEnabled($provider, $model)) {
                    $models[] = ['value' => $model, 'provider' => $provider, 'label' => $model];
                }
            }
        }

        return [
            'strategies' => [
                ['value' => 'auto', 'label' => 'Auto', 'model' => null],
                ['value' => 'fast', 'label' => 'Fast', 'model' => ModelRouter::resolve('worker')],
                ['value' => 'balanced', 'label' => 'Balanced', 'model' => ModelRouter::resolve('worker')],
                ['value' => 'quality', 'label' => 'Quality', 'model' => ModelRouter::resolve('orchestrator')],
            ],
            'models' => $models,
        ];
    }
}

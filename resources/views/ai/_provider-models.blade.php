@php
    $aiProviderModels = [];
    foreach (\App\Services\AI\AiProviderRegistry::providers() as $key => $config) {
        $aiProviderModels[$key] = $config['models'];
    }
@endphp
<script>window.AI_PROVIDER_MODELS = @json($aiProviderModels);</script>

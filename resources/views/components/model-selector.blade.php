@props([
    'providerModels' => [],
    'selectedProvider' => null,
    'selectedModel' => null,
    'providerName' => null,
    'modelName' => null,
    'dropup' => false,
    'placeholder' => 'Select model',
    'allowEmpty' => false,
    'emptyLabel' => 'None',
    'triggerClass' => null,
])

@php
    $keys = array_keys($providerModels);
    $initialProvider = $selectedProvider ?? ($keys[0] ?? 'chatgpt');
    $initialModel = $selectedModel ?: ($allowEmpty ? '' : ($providerModels[$initialProvider][0] ?? ''));
@endphp

<div class="relative" x-data="modelSelector({
    providerModels: @js($providerModels),
    selectedProvider: @js($initialProvider),
    selectedModel: @js($initialModel),
    dropup: @js($dropup),
    placeholder: @js($placeholder),
    allowEmpty: @js($allowEmpty),
    emptyLabel: @js($emptyLabel),
})">
    @if($providerName)
        <input type="hidden" name="{{ $providerName }}" :value="selectedProvider">
    @endif
    @if($modelName)
        <input type="hidden" name="{{ $modelName }}" :value="selectedModel">
    @endif

    <button
        type="button"
        @click="open = !open"
        class="{{ $triggerClass ?: 'text-sm text-gray-900 dark:text-admin-text-primary' }}"
    >
        <span class="truncate" x-text="selectedItem.label"></span>
        <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute z-50 w-80 bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl shadow-2xl overflow-hidden"
        :class="dropup ? 'bottom-full mb-2' : 'top-full mt-2'"
        style="right:0;"
    >
        <div class="p-2 border-b border-gray-100 dark:border-white/10">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input
                    type="text"
                    x-model="search"
                    :placeholder="placeholder"
                    class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 dark:bg-admin-sidebar border border-gray-200 dark:border-admin-border rounded-lg text-gray-900 dark:text-admin-text-primary focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent"
                >
            </div>
        </div>
        <div class="max-h-80 overflow-y-auto p-2">
            <template x-if="allowEmpty && search.trim() === ''">
                <button
                    type="button"
                    @click="select('', '')"
                    class="w-full flex items-center gap-3 px-2 py-2 rounded-lg text-left hover:bg-gray-50 dark:hover:bg-white/5 transition-colors mb-1"
                    :class="!selectedModel ? 'bg-[#1E5FEA]/10 text-[#1E5FEA]' : 'text-gray-700 dark:text-admin-text-primary'"
                >
                    <span class="w-7 h-7 rounded-md flex items-center justify-center text-gray-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </span>
                    <span class="text-sm truncate" x-text="emptyLabel"></span>
                </button>
            </template>
            <template x-for="group in filteredGroups" :key="group.key">
                <div class="mb-2">
                    <p class="px-2 py-1 text-[11px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500" x-text="group.label"></p>
                    <div class="flex flex-col gap-0.5">
                        <template x-for="item in group.models" :key="item.provider + ':' + item.model">
                            <button
                                type="button"
                                @click="select(item.provider, item.model)"
                                class="w-full flex items-center gap-3 px-2 py-2 rounded-lg text-left hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                :class="selectedProvider === item.provider && selectedModel === item.model ? 'bg-[#1E5FEA]/10 text-[#1E5FEA]' : 'text-gray-700 dark:text-admin-text-primary'"
                            >
                                <span class="w-7 h-7 rounded-md flex items-center justify-center text-[10px] font-bold text-white shrink-0" :style="'background-color:' + item.color" x-text="item.icon"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm truncate" x-text="item.label"></span>
                                        <span x-show="item.tag" class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300" x-text="item.tag"></span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="h-1.5 w-12 rounded-full bg-gray-200 dark:bg-white/10 overflow-hidden">
                                            <div class="h-full rounded-full" :class="item.meterClass" :style="'width:' + item.meter + '%'"></div>
                                        </div>
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500" x-text="item.providerLabel"></span>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

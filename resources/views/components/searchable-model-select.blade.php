@props([
    'name' => null,
    'providerModels' => [],
    'selected' => '',
    'placeholder' => 'Select model',
    'dropup' => false,
    'allowEmpty' => false,
    'emptyLabel' => 'System default',
    'triggerClass' => null,
])

@php
    $logoProviders = [
        'chatgpt' => 'openai',
        'claude' => 'anthropic',
        'gemini' => 'google',
        'glm' => 'zai',
        'grok' => 'xai',
        'kimi' => 'kimi',
    ];

    $items = [];
    foreach ($providerModels as $provider => $models) {
        $logo = $logoProviders[$provider] ?? $provider;
        $logoUrl = asset('images/model-logos/' . $logo . '.png');
        foreach ((array) $models as $model) {
            $items[] = [
                'value' => (string) $model,
                'label' => (string) $model,
                'provider' => (string) $provider,
                'logo' => $logoUrl,
            ];
        }
    }

    $trigger = $triggerClass ?: 'w-full flex items-center justify-between gap-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-admin-sidebar px-3 py-2 text-sm text-gray-900 dark:text-admin-text-primary hover:bg-gray-50 dark:hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-primary-500';
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        search: '',
        selected: @js((string) $selected),
        items: @js($items),
        placeholder: @js($placeholder),
        allowEmpty: @js((bool) $allowEmpty),
        emptyLabel: @js($emptyLabel),
        dropup: @js((bool) $dropup),
        get filtered() {
            const q = this.search.trim().toLowerCase();
            if (q === '') return this.items;
            return this.items.filter((i) => i.label.toLowerCase().includes(q) || String(i.provider).toLowerCase().includes(q));
        },
        get selectedItem() {
            return this.items.find((i) => i.value === this.selected) || null;
        },
        get triggerLabel() {
            const item = this.selectedItem;
            if (item) return item.label;
            return this.allowEmpty ? this.emptyLabel : this.placeholder;
        },
        choose(value) {
            this.selected = value;
            this.open = false;
            this.search = '';
        },
    }"
>
    @if($name)
        <input type="hidden" name="{{ $name }}" :value="selected">
    @endif

    <button type="button" @click="open = !open" class="{{ $trigger }}">
        <span class="truncate" x-text="triggerLabel"></span>
        <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute z-50 w-64 rounded-xl border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-900"
        :class="dropup ? 'bottom-full mb-2' : 'top-full mt-2'"
        style="right:0;"
    >
        <div class="relative mb-2">
            <svg class="absolute left-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input
                type="search"
                x-model="search"
                :placeholder="placeholder"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 pl-7 pr-2 text-xs text-gray-700 outline-none focus:border-gray-400 focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            >
        </div>

        <div class="max-h-52 overflow-y-auto space-y-0.5">
            <template x-if="allowEmpty && search.trim() === ''">
                <button
                    type="button"
                    @click="choose('')"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-xs transition-colors"
                    :class="!selected ? 'bg-gray-900 font-medium text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5'"
                >
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    <span class="truncate" x-text="emptyLabel"></span>
                </button>
            </template>

            <template x-for="item in filtered" :key="item.provider + ':' + item.value">
                <button
                    type="button"
                    @click="choose(item.value)"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-xs transition-colors"
                    :class="selected === item.value ? 'bg-gray-900 font-medium text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5'"
                >
                    <img :src="item.logo" alt="" class="h-4 w-4 shrink-0 rounded object-contain" x-on:error="$el.style.display = 'none'">
                    <span class="truncate" x-text="item.label"></span>
                    <svg x-show="selected === item.value" class="ml-auto shrink-0" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"/></svg>
                </button>
            </template>

            <div x-show="filtered.length === 0" class="px-2.5 py-3 text-center text-xs text-gray-400">{{ __('No models found') }}</div>
        </div>
    </div>
</div>

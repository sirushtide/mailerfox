@php
    $isCustom = !empty($value) && !in_array($value, $fonts);
    $allFonts = $isCustom ? array_merge([$value], $fonts) : $fonts;
    $selectedWeights = array_filter(array_map('trim', explode(';', $weightsValue)));
    $allWeights = [100, 200, 300, 400, 500, 600, 700, 800, 900];
@endphp
<div class="space-y-2">
    {{-- Searchable font dropdown --}}
    <div
        x-data='{
            open: false,
            query: "",
            selected: @json($value),
            fonts: @json($allFonts),
            filteredFonts: @json($allFonts),
            dropdownStyle: "",
            init() {
                this.fonts = this.normalizeOptions(this.fonts);
                this.filteredFonts = this.fonts;
            },
            normalizeOptions(value) {
                if (Array.isArray(value)) return value;
                if (value === null || value === undefined || value === "") return [];
                if (typeof value === "object") return Object.values(value);
                return [value];
            },
            get selectedLabel() {
                if (this.selected === "") return "Select font...";
                return this.selected;
            },
            filterFonts() {
                this.$nextTick(() => {
                    const q = (this.query || "").toLowerCase();
                    if (!q) {
                        this.filteredFonts = this.fonts;
                    } else {
                        this.filteredFonts = this.fonts.filter(f => f.toLowerCase().includes(q));
                    }
                });
            },
            positionDropdown() {
                this.$nextTick(() => {
                    const btn = this.$refs.fontButton;
                    if (!btn) return;
                    const rect = btn.getBoundingClientRect();
                    const gap = 4;
                    const maxHeight = 260;
                    let top = rect.bottom + gap;
                    if (top + maxHeight > window.innerHeight) {
                        top = rect.top - maxHeight - gap;
                    }
                    this.dropdownStyle = `position: fixed; top: ${top}px; left: ${rect.left}px; width: ${rect.width}px; z-index: 9999;`;
                });
            },
            focusSearch() {
                this.positionDropdown();
                this.$nextTick(() => {
                    const input = this.$refs.fontSearchInput;
                    if (input) input.focus();
                });
            }
        }'
        x-effect="filteredFonts = (query || '').trim() ? fonts.filter(f => f.toLowerCase().includes(query.toLowerCase())) : fonts"
        x-on:click.outside="open = false"
        x-on:resize.window="positionDropdown()"
        x-on:scroll.window="open = false"
        x-on:keydown.escape.window="open = false"
        class="relative"
    >
        <input type="hidden" name="{{ $name }}" :value="selected" x-model="selected">
        <button
            x-ref="fontButton"
            type="button"
            @click="open = !open; if (open) focusSearch()"
            class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm text-left px-3 py-2 flex items-center justify-between"
            :style="'font-family: ' + (selected || 'Inter') + ', sans-serif'"
        >
            <span x-text="selectedLabel" :class="selected === '' ? 'text-gray-400 dark:text-gray-500' : ''"></span>
            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak :style="dropdownStyle" class="rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
            <div class="p-2 border-b border-gray-200 dark:border-gray-600">
                <input
                    x-ref="fontSearchInput"
                    type="text"
                    x-model="query"
                    @input="open = true"
                    placeholder="Search font..."
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                    :style="'font-family: ' + (selected || 'Inter') + ', sans-serif'"
                >
            </div>
            <div class="max-h-48 overflow-y-auto">
                <template x-for="font in filteredFonts" :key="font">
                    <button
                        type="button"
                        @click="selected = font; open = false"
                        class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                        :class="selected === font ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-700 dark:text-gray-300'"
                        :style="'font-family: ' + font + ', sans-serif'"
                    >
                        <span x-text="font"></span>
                    </button>
                </template>
                <div x-show="filteredFonts.length === 0" class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500">
                    No fonts found
                </div>
            </div>
        </div>
    </div>

    @if($weightsName !== '')
    {{-- Font weights dropdown (single select) --}}
    <div
        x-data='{
            open: false,
            query: "",
            selectedWeight: @json($selectedWeights[0] ?? ""),
            allWeights: @json($allWeights),
            filteredWeights: @json($allWeights),
            dropdownStyle: "",
            init() {
                this.allWeights = this.normalizeOptions(this.allWeights);
                this.filteredWeights = this.allWeights;
            },
            normalizeOptions(value) {
                if (Array.isArray(value)) return value;
                if (value === null || value === undefined || value === "") return [];
                if (typeof value === "object") return Object.values(value);
                return [value];
            },
            get weightStr() { return this.selectedWeight !== "" ? String(this.selectedWeight) : ""; },
            get weightLabel() {
                if (this.selectedWeight === "") return "Select weight...";
                return String(this.selectedWeight);
            },
            filterWeights() {
                this.$nextTick(() => {
                    const q = (this.query || "").toLowerCase();
                    if (!q) {
                        this.filteredWeights = this.allWeights;
                    } else {
                        this.filteredWeights = this.allWeights.filter(w => String(w).includes(q));
                    }
                });
            },
            positionDropdown() {
                this.$nextTick(() => {
                    const btn = this.$refs.weightButton;
                    if (!btn) return;
                    const rect = btn.getBoundingClientRect();
                    const gap = 4;
                    const maxHeight = 260;
                    let top = rect.bottom + gap;
                    if (top + maxHeight > window.innerHeight) {
                        top = rect.top - maxHeight - gap;
                    }
                    this.dropdownStyle = `position: fixed; top: ${top}px; left: ${rect.left}px; width: ${rect.width}px; z-index: 9999;`;
                });
            }
        }'
        x-effect="filteredWeights = (query || '').trim() ? allWeights.filter(w => String(w).includes(query.toLowerCase())) : allWeights"
        x-on:click.outside="open = false"
        x-on:resize.window="positionDropdown()"
        x-on:scroll.window="open = false"
        class="relative"
    >
        <input type="hidden" name="{{ $weightsName }}" :value="weightStr">
        <button
            x-ref="weightButton"
            type="button"
            @click="open = !open; if (open) positionDropdown()"
            class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm text-left px-3 py-2 flex items-center justify-between"
        >
            <span x-text="weightLabel" :class="selectedWeight === '' ? 'text-gray-400 dark:text-gray-500' : ''"></span>
            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak :style="dropdownStyle" class="rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
            <div class="p-2 border-b border-gray-200 dark:border-gray-600">
                <input
                    type="text"
                    x-model="query"
                    @input="open = true"
                    placeholder="Search weight..."
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
            </div>
            <div class="max-h-48 overflow-y-auto">
                <template x-for="w in filteredWeights" :key="w">
                    <button
                        type="button"
                        @click="selectedWeight = w; open = false"
                        class="flex items-center w-full px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                        :class="selectedWeight === w ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-700 dark:text-gray-300'"
                    >
                        <span x-text="w"></span>
                    </button>
                </template>
                <div x-show="filteredWeights.length === 0" class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500">
                    No weights found
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

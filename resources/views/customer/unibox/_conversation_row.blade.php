{{-- Conversation row partial. Expects Alpine scope with `conv` and access to outer uniboxApp state. --}}

{{-- ═══ COLLAPSED layout (narrow 300px panel when a conversation is open) ═══ --}}
<template x-if="activeConversation">
    <div
        @click="selectConversation(conv)"
        :class="activeConversation.id === conv.id ? 'bg-blue-50/60 dark:bg-[rgba(30,95,234,0.08)]' : 'hover:bg-gray-50 dark:hover:bg-white/[0.03]'"
        class="group relative flex items-center gap-2 px-3 py-2.5 pr-3 border-b border-gray-100 dark:border-white/5 cursor-pointer transition-colors overflow-hidden"
    >
        {{-- Checkbox --}}
        <label @click.stop class="flex-shrink-0 flex items-center mt-0.5">
            <input type="checkbox"
                   :checked="isConvSelected(conv)"
                   @change="toggleSelectConv(conv)"
                   class="rounded border-gray-300 dark:border-white/20 text-[#1E5FEA] focus:ring-[#1E5FEA]">
        </label>

        {{-- Avatar --}}
        <template x-if="conv.contact?.avatar_url">
            <img :src="conv.contact.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0 mt-0.5">
        </template>
        <template x-if="!conv.contact?.avatar_url">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-0.5"
                 :style="'background: linear-gradient(135deg, ' + avatarColor(conv) + ')'">
                <span x-text="(conv.contact?.name || conv.contact?.email || '?').charAt(0).toUpperCase()"></span>
            </div>
        </template>

        {{-- Content --}}
        <div class="min-w-0 flex-1">
            {{-- Row 1: Name + count + time + star --}}
            <div class="flex items-center gap-1">
                <span x-text="conv.contact?.name || conv.contact?.email || '(unknown)'"
                      :class="!conv.is_read ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-800 dark:text-gray-200'"
                      class="text-sm truncate flex-1 min-w-0"></span>
                <template x-if="conv.message_count && conv.message_count > 1">
                    <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0" x-text="conv.message_count"></span>
                </template>
                <span class="text-[11px] text-gray-400 dark:text-gray-500 tabular-nums flex-shrink-0 ml-auto" x-text="conversationTime(conv)"></span>
                <button
                    @click.stop="toggleStar(conv)"
                    class="flex-shrink-0 p-0.5 transition-colors"
                    :class="conv.is_starred ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600 hover:text-yellow-400'"
                >
                    <svg class="w-3.5 h-3.5" :fill="conv.is_starred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
                    </svg>
                </button>
            </div>

            {{-- Row 2: Preview (last message) --}}
            <div class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5" x-text="conv.preview || conv.subject || ''"></div>

            {{-- Row 3: Tags + Priority inline --}}
            <div class="flex items-center gap-1 mt-1 flex-wrap"
                 x-show="(conv.tags || []).length > 0">
                {{-- Non-priority tags --}}
                <template x-for="tag in (conv.tags || []).filter(t => !['high','medium','low'].includes((t||'').toLowerCase()))" :key="'ct-'+tag">
                    <span
                        x-text="tag"
                        :style="tagChipStyle(tag)"
                        class="inline-block text-[10px] font-medium px-1.5 py-0.5 rounded-full truncate max-w-[80px]"
                    ></span>
                </template>
                {{-- Priority pill --}}
                <template x-if="priorityOf(conv)">
                    <span :style="priorityStyle(priorityOf(conv))" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium whitespace-nowrap">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                        <span x-text="priorityOf(conv)"></span>
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>

{{-- ═══ EXPANDED layout (full-width when no conversation is selected) ═══ --}}
<template x-if="!activeConversation">
    <div
        @click="selectConversation(conv)"
        class="group relative flex items-center gap-2 px-3 py-2 pr-[8.5rem] border-b border-gray-100 dark:border-white/5 cursor-pointer transition-colors overflow-hidden hover:bg-gray-50 dark:hover:bg-white/[0.03]"
    >
        {{-- Checkbox --}}
        <label @click.stop class="flex-shrink-0 flex items-center">
            <input type="checkbox"
                   :checked="isConvSelected(conv)"
                   @change="toggleSelectConv(conv)"
                   class="rounded border-gray-300 dark:border-white/20 text-[#1E5FEA] focus:ring-[#1E5FEA]">
        </label>

        {{-- Avatar --}}
        <template x-if="conv.contact?.avatar_url">
            <img :src="conv.contact.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0">
        </template>
        <template x-if="!conv.contact?.avatar_url">
            <div class="w-7 h-7 rounded-full bg-gradient-to-br flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0"
                 :style="'background: linear-gradient(135deg, ' + avatarColor(conv) + ')'">
                <span x-text="(conv.contact?.name || conv.contact?.email || '?').charAt(0).toUpperCase()"></span>
            </div>
        </template>

        {{-- Name + count --}}
        <div class="w-[180px] xl:w-[220px] flex-shrink-0 flex items-center gap-1.5 min-w-0">
            <span x-text="conv.contact?.name || conv.contact?.email || '(unknown)'"
                  :class="!conv.is_read ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-800 dark:text-gray-200'"
                  class="text-sm truncate"></span>
            <template x-if="conv.message_count && conv.message_count > 1">
                <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0" x-text="conv.message_count"></span>
            </template>
        </div>

        {{-- Primary tag pill (excludes priority tags) --}}
        <div class="w-[110px] flex-shrink-0 overflow-hidden flex items-center justify-start">
            <template x-if="(conv.tags || []).filter(t => !['high','medium','low'].includes((t||'').toLowerCase())).length">
                <span
                    x-text="(conv.tags || []).filter(t => !['high','medium','low'].includes((t||'').toLowerCase()))[0]"
                    :style="tagChipStyle((conv.tags || []).filter(t => !['high','medium','low'].includes((t||'').toLowerCase()))[0])"
                    class="inline-block text-xs font-medium px-2.5 py-0.5 rounded-full truncate max-w-[110px]"
                ></span>
            </template>
        </div>

        {{-- Subject + preview --}}
        <div class="min-w-0 flex-1 flex items-baseline gap-1.5 overflow-hidden">
            <span x-text="conv.subject || '(no subject)'"
                  :class="!conv.is_read ? 'font-medium text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300'"
                  class="text-sm truncate flex-shrink-0 min-w-0 max-w-[50%]"></span>
            <span x-show="conv.preview" class="text-sm text-gray-400 dark:text-gray-500 truncate min-w-0 flex-1 overflow-hidden whitespace-nowrap">
                <span>&mdash;&nbsp;</span><span x-text="conv.preview"></span>
            </span>
        </div>

        {{-- Priority pill --}}
        <div class="text-right min-w-0 hidden lg:block">
            <template x-if="priorityOf(conv)">
                <span :style="priorityStyle(priorityOf(conv))" class="inline-flex items-center gap-1 px-1.5 xl:px-2 py-0.5 rounded text-[10px] xl:text-[11px] font-medium whitespace-nowrap">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                    <span x-text="priorityOf(conv)"></span>
                </span>
            </template>
        </div>

        {{-- Timestamp + Star --}}
        <div class="absolute right-0 top-0 bottom-0 w-[8rem] flex items-center justify-end gap-1.5 pr-3 pointer-events-auto bg-inherit">
            <div class="text-right text-[11px] text-gray-500 dark:text-gray-400 tabular-nums truncate" x-text="conversationTime(conv)"></div>
            <button
                @click.stop="toggleStar(conv)"
                class="flex-shrink-0 p-0.5 transition-colors"
                :class="conv.is_starred ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600 hover:text-yellow-400'"
                :title="'{{ __('Star') }}'"
            >
                <svg class="w-4 h-4" :fill="conv.is_starred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
                </svg>
            </button>
        </div>
    </div>
</template>

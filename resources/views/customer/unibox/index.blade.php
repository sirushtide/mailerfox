@extends('layouts.customer')

@section('title', __('Inbox'))
@section('page-title', '')
@section('content-padding-classes', 'p-0 overflow-hidden')
@section('content-wrapper-attributes', 'style="flex:1;display:flex;flex-direction:column;"')
@section('force-sidebar-collapsed', '1')

@section('content')
<div
    class="flex overflow-hidden bg-white dark:bg-[#111]"
    style="height:calc(100vh - 48px)"
    x-data="uniboxApp()"
    x-init="init()"
>
    {{-- ═══════════════════════════════════════════════════════ LEFT NAV ══ --}}
    <aside class="w-56 flex-shrink-0 flex flex-col border-r border-gray-100 dark:border-white/10 bg-white dark:bg-[#0f0f0f] h-full">
        <div class="px-1 py-1 border-b border-gray-100 dark:border-white/10">
            <button
                @click="openComposeModal = true"
                class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-md bg-[#7c3aed] hover:bg-[#6d28d9] text-white text-sm font-semibold transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-pen-icon lucide-square-pen"><path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"/></svg>
                <span>{{ __('Compose') }}</span>
            </button>
        </div>

        {{-- Skeleton shown while switching inbox account --}}
        <nav x-show="navLoading" x-cloak class="flex-1 overflow-y-auto py-2 px-2 space-y-2">
            <template x-for="i in 9" :key="'sk-nav-' + i">
                <div class="flex items-center justify-between gap-2 px-1 py-1.5 animate-pulse">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <div class="w-4 h-4 rounded bg-gray-200 dark:bg-white/10 flex-shrink-0"></div>
                        <div class="h-3 rounded bg-gray-200 dark:bg-white/10" :style="'width:' + (50 + (i*7)%40) + '%'"></div>
                    </div>
                    <div class="h-4 w-6 rounded-full bg-gray-200 dark:bg-white/10" x-show="i % 3 === 1"></div>
                </div>
            </template>
            <div class="pt-3 px-1">
                <div class="h-2.5 w-16 rounded bg-gray-200 dark:bg-white/10 mb-2 animate-pulse"></div>
                <template x-for="i in 5" :key="'sk-fold-' + i">
                    <div class="flex items-center gap-2 py-1.5 animate-pulse">
                        <div class="w-4 h-4 rounded bg-gray-200 dark:bg-white/10"></div>
                        <div class="h-3 rounded bg-gray-200 dark:bg-white/10" :style="'width:' + (40 + (i*11)%50) + '%'"></div>
                    </div>
                </template>
            </div>
            <div class="pt-3 px-1">
                <div class="h-2.5 w-12 rounded bg-gray-200 dark:bg-white/10 mb-2 animate-pulse"></div>
                <template x-for="i in 3" :key="'sk-tag-' + i">
                    <div class="flex items-center gap-2 py-1.5 animate-pulse">
                        <div class="w-3 h-3 rounded-full bg-gray-200 dark:bg-white/10"></div>
                        <div class="h-3 rounded bg-gray-200 dark:bg-white/10" :style="'width:' + (40 + (i*13)%50) + '%'"></div>
                    </div>
                </template>
            </div>
        </nav>

        <nav x-show="!navLoading" class="flex-1 overflow-y-auto py-0 px-0 space-y-0.5">
            <template x-for="item in navItems" :key="item.folder">
                <button
                    @click="selectFolder(item.folder)"
                    :class="activeFolder === item.folder ? 'bg-[rgba(30,95,234,0.08)] text-[#1E5FEA]' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'"
                    class="w-full flex items-center justify-between gap-2.5 px-2.5 py-1.5 !mt-0 text-sm transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <span x-html="item.icon" class="w-4 h-4 flex-shrink-0"></span>
                        <span x-text="item.label"></span>
                    </div>
                    <span x-show="counts[item.folder] > 0" x-text="counts[item.folder]"
                          class="text-xs font-medium bg-[#1E5FEA] text-white px-1.5 py-0.5 rounded-full leading-none"></span>
                </button>
            </template>

            @if($teams->count())
            <div class="pt-3 pb-1">
                <p class="px-2.5 text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-600 font-medium mb-1">{{ __('Shared Teams') }}</p>
                @foreach($teams as $team)
                <button
                    @click="selectFolder('team_{{ $team->id }}')"
                    :class="activeFolder === 'team_{{ $team->id }}' ? 'bg-[rgba(30,95,234,0.08)] text-[#1E5FEA]' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'"
                    class="w-full flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg text-sm transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $team->color }}"></span>
                        <span>{{ $team->name }}</span>
                    </div>
                </button>
                @endforeach
            </div>
            @endif

            {{-- Folders --}}
            <div class="pt-2 border-t border-gray-100 dark:border-white/10 mb-0">
                <div class="flex items-center justify-between px-2.5 mb-0 pb-2">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-600 font-medium">{{ __('Folders') }}</p>
                    <button @click="showNewFolder = !showNewFolder; $nextTick(() => $refs.newFolderInput?.focus())" class="p-0.5 text-gray-400 hover:text-[#7c3aed]" :title="'{{ __('New folder') }}'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div x-show="showNewFolder" x-transition class="px-2 mb-1" style="display: none;">
                    <input
                        x-ref="newFolderInput"
                        x-model="newFolderName"
                        @keydown.enter.prevent="createFolder()"
                        @keydown.escape="showNewFolder = false; newFolderName = ''"
                        type="text"
                        placeholder="{{ __('Folder name') }}"
                        class="w-full px-2 py-1 text-xs border border-gray-200 dark:border-white/10 rounded bg-white dark:bg-white/5 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-[#7c3aed]"
                    >
                </div>

                <template x-for="f in customFolders" :key="f.id">
                    <div class="group flex items-center justify-between gap-1 px-2.5 py-1.5 hover:bg-gray-50 dark:hover:bg-white/5">
                        <template x-if="editingFolderId !== f.id">
                            <button @click="selectFolder('custom_' + f.id)" :class="activeFolder === 'custom_' + f.id ? 'text-[#7c3aed]' : 'text-gray-600 dark:text-gray-300'" class="flex-1 min-w-0 flex items-center gap-2 text-sm text-left">
                                <label class="relative flex-shrink-0 cursor-pointer" @click.stop :title="'{{ __('Change color') }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" :style="'color:' + f.color"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
                                    <input type="color" :value="f.color" @change="updateFolderColor(f, $event.target.value)" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer">
                                </label>
                                <span class="truncate" x-text="f.name"></span>
                            </button>
                        </template>
                        <template x-if="editingFolderId === f.id">
                            <div class="flex-1 min-w-0 flex items-center gap-1" @click.stop>
                                <input type="color" x-model="editingFolderColor" class="w-5 h-5 rounded cursor-pointer border border-gray-200 dark:border-white/10 bg-transparent flex-shrink-0">
                                <input x-ref="editFolderInput" x-model="editingFolderName"
                                       @keydown.enter.prevent="saveFolder(f)"
                                       @keydown.escape="cancelEditFolder()"
                                       type="text"
                                       class="flex-1 min-w-0 px-2 py-1 text-xs border border-gray-200 dark:border-white/10 rounded bg-white dark:bg-white/5 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-[#7c3aed]">
                            </div>
                        </template>
                        <div class="flex items-center gap-0.5 flex-shrink-0" @click.stop>
                            <template x-if="editingFolderId !== f.id">
                                <div class="flex items-center gap-0.5">
                                    <button @click.stop="startEditFolder(f)" class="opacity-0 group-hover:opacity-100 p-0.5 text-gray-400 hover:text-[#7c3aed]" :title="'{{ __('Edit') }}'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    </button>
                                    <button @click.stop="deleteFolder(f)" class="opacity-0 group-hover:opacity-100 p-0.5 text-gray-400 hover:text-red-500" :title="'{{ __('Delete') }}'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </template>
                            <template x-if="editingFolderId === f.id">
                                <div class="flex items-center gap-0.5">
                                    <button @click.stop="saveFolder(f)" class="p-0.5 text-green-500 hover:text-green-600" :title="'{{ __('Save') }}'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    </button>
                                    <button @click.stop="cancelEditFolder()" class="p-0.5 text-gray-400 hover:text-red-500" :title="'{{ __('Cancel') }}'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Tags --}}
            <div class="pt-2 border-t border-gray-100 dark:border-white/10 mb-0">
                <div class="flex items-center justify-between px-2.5 mb-0 pb-2">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-600 font-medium">{{ __('Tags') }}</p>
                    <button @click="showNewTag = !showNewTag; $nextTick(() => $refs.newTagInput?.focus())" class="p-0.5 text-gray-400 hover:text-[#7c3aed]" :title="'{{ __('New tag') }}'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div x-show="showNewTag" x-transition class="px-2 mb-1 flex gap-1 items-center" style="display: none;">
                    <input type="color" x-model="newTagColor" class="w-6 h-6 rounded cursor-pointer border border-gray-200 dark:border-white/10 bg-transparent">
                    <input
                        x-ref="newTagInput"
                        x-model="newTagName"
                        @keydown.enter.prevent="createTag()"
                        @keydown.escape="showNewTag = false; newTagName = ''"
                        type="text"
                        placeholder="{{ __('Tag name') }}"
                        class="flex-1 min-w-0 px-2 py-1 text-xs border border-gray-200 dark:border-white/10 rounded bg-white dark:bg-white/5 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-[#7c3aed]"
                    >
                </div>

                <template x-for="t in customTags" :key="t.id">
                    <div :class="activeTag === t.name ? 'bg-[rgba(124,58,237,0.08)]' : ''" class="group flex items-center justify-between gap-1 px-2.5 py-1.5 hover:bg-gray-50 dark:hover:bg-white/5">
                        <template x-if="editingTagId !== t.id">
                            <button @click="selectTag(t.name)" class="flex-1 min-w-0 flex items-center gap-2 text-sm text-left">
                                <label class="relative flex-shrink-0 cursor-pointer" @click.stop :title="'{{ __('Change color') }}'">
                                    <span class="block w-3 h-3 rounded-full border border-black/10" :style="'background-color:' + t.color"></span>
                                    <input type="color" :value="t.color" @change="updateTagColor(t, $event.target.value)" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer">
                                </label>
                                <span :class="activeTag === t.name ? 'text-[#7c3aed] font-medium' : 'text-gray-600 dark:text-gray-300'" class="truncate" x-text="t.name"></span>
                            </button>
                        </template>
                        <template x-if="editingTagId === t.id">
                            <div class="flex-1 min-w-0 flex items-center gap-1" @click.stop>
                                <input type="color" x-model="editingTagColor" class="w-5 h-5 rounded cursor-pointer border border-gray-200 dark:border-white/10 bg-transparent flex-shrink-0">
                                <input x-ref="editTagInput" x-model="editingTagName"
                                       @keydown.enter.prevent="saveTag(t)"
                                       @keydown.escape="cancelEditTag()"
                                       type="text"
                                       class="flex-1 min-w-0 px-2 py-1 text-xs border border-gray-200 dark:border-white/10 rounded bg-white dark:bg-white/5 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-[#7c3aed]">
                            </div>
                        </template>
                        <div class="flex items-center gap-0.5 flex-shrink-0" @click.stop>
                            <template x-if="editingTagId !== t.id">
                                <div class="flex items-center gap-0.5">
                                    <button @click.stop="startEditTag(t)" class="opacity-0 group-hover:opacity-100 p-0.5 text-gray-400 hover:text-[#7c3aed]" :title="'{{ __('Edit') }}'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    </button>
                                    <button @click.stop="deleteTag(t)" class="opacity-0 group-hover:opacity-100 p-0.5 text-gray-400 hover:text-red-500" :title="'{{ __('Delete') }}'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </template>
                            <template x-if="editingTagId === t.id">
                                <div class="flex items-center gap-0.5">
                                    <button @click.stop="saveTag(t)" class="p-0.5 text-green-500 hover:text-green-600" :title="'{{ __('Save') }}'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    </button>
                                    <button @click.stop="cancelEditTag()" class="p-0.5 text-gray-400 hover:text-red-500" :title="'{{ __('Cancel') }}'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Priorities --}}
            <div class="pt-2 border-t border-gray-100 dark:border-white/10 mb-0">
                <div class="flex items-center justify-between px-2.5 mb-0 pb-2">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-600 font-medium">{{ __('Priorities') }}</p>
                </div>

                <button @click="selectPriority('high')"
                    :class="activePriority === 'high' ? 'bg-[rgba(239,68,68,0.08)]' : ''"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-white/5 text-sm text-left">
                    <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                    <span :class="activePriority === 'high' ? 'text-red-600 font-medium' : 'text-gray-600 dark:text-gray-300'">{{ __('High') }}</span>
                </button>
                <button @click="selectPriority('medium')"
                    :class="activePriority === 'medium' ? 'bg-[rgba(245,158,11,0.08)]' : ''"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-white/5 text-sm text-left">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                    <span :class="activePriority === 'medium' ? 'text-amber-600 font-medium' : 'text-gray-600 dark:text-gray-300'">{{ __('Medium') }}</span>
                </button>
                <button @click="selectPriority('low')"
                    :class="activePriority === 'low' ? 'bg-[rgba(34,197,94,0.08)]' : ''"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-white/5 text-sm text-left">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                    <span :class="activePriority === 'low' ? 'text-green-600 font-medium' : 'text-gray-600 dark:text-gray-300'">{{ __('Low') }}</span>
                </button>
            </div>
        </nav>

        {{-- Inbox selector dropdown (bottom) --}}
        <div class="mt-auto border-t border-gray-100 dark:border-white/10 p-3 relative" @click.outside="showInboxPicker = false">
            <button
                @click="showInboxPicker = !showInboxPicker"
                class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 transition-colors"
            >
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.04a2.25 2.25 0 0 1 2.134 0l7.5 4.04a2.25 2.25 0 0 1 1.183 1.98V19.5Z"/></svg>
                    <span class="truncate text-sm font-medium text-gray-800 dark:text-white" x-text="activeAccountLabel"></span>
                </div>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="showInboxPicker ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
            </button>

            <div
                x-show="showInboxPicker"
                x-transition
                class="absolute left-3 right-3 bottom-[calc(100%-0.5rem)] mb-2 max-h-64 overflow-y-auto rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-[#1a1a1a] shadow-lg py-1 z-20"
                style="display: none;"
            >
                <button
                    @click="selectAccount(null)"
                    :class="activeAccountId === null ? 'bg-[rgba(124,58,237,0.08)] text-[#7c3aed]' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5'"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.04a2.25 2.25 0 0 1 2.134 0l7.5 4.04a2.25 2.25 0 0 1 1.183 1.98V19.5Z"/></svg>
                    <span class="font-medium">{{ __('All Inbox') }}</span>
                </button>
                @foreach($accounts as $acc)
                <div
                    :class="activeAccountId == {{ $acc->id }} ? 'bg-[rgba(124,58,237,0.08)] text-[#7c3aed]' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5'"
                    class="group w-full flex items-start gap-2 px-3 py-2 text-sm text-left transition-colors cursor-pointer"
                    x-data="{ accName: @js($acc->name ?: $acc->email) }"
                    @click="selectAccount({{ $acc->id }})"
                >
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Zm0 0c0 1.657 1.007 3 2.25 3S21 13.657 21 12a9 9 0 1 0-2.636 6.364M16.5 12V8.25"/></svg>
                    <div class="min-w-0 flex-1">
                        <div class="font-medium truncate" x-text="accName"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $acc->email }}</div>
                    </div>
                    <button
                        type="button"
                        @click.stop="renameAccount({{ $acc->id }}, accName).then(n => { if (n) accName = n })"
                        class="opacity-0 group-hover:opacity-100 p-1 -m-1 text-gray-400 hover:text-[#1E5FEA] rounded transition-opacity"
                        :title="'{{ __('Rename inbox') }}'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                    </button>
                </div>
                @endforeach
                <div class="mx-3 my-2 border-t border-gray-100 dark:border-white/10"></div>
                <a
                    href="{{ route('customer.unibox.accounts.index') }}"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors rounded-lg mx-1"
                >
                    <x-lucide name="settings" class="w-4 h-4 flex-shrink-0" />
                    <span class="font-medium">{{ __('Manage inboxes') }}</span>
                </a>
                <div class="mx-3 my-2 border-t border-gray-100 dark:border-white/10"></div>
                <button
                    type="button"
                    @click="showInboxPicker = false; addInboxDrawerOpen = true; addInboxProvider = 'gmail'"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left text-[#1E5FEA] hover:bg-[rgba(30,95,234,0.08)] transition-colors rounded-lg mx-1"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span class="font-medium">{{ __('Add inbox') }}</span>
                </button>
                @if($accounts->isEmpty())
                <div class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500">{{ __('No inboxes connected') }}</div>
                @endif
            </div>
        </div>

    </aside>

    {{-- ═══════════════════════════════════════════════════ MIDDLE PANEL ══ --}}
    <div
        :class="activeConversation ? 'w-[300px] flex-shrink-0' : 'flex-1'"
        class="flex flex-col border-gray-100 dark:border-white/10 bg-white dark:bg-[#0f0f0f] h-full transition-all max-w-[calc(100vw-270px)]"
    >
        {{-- Top toolbar: search + filter (left) / export + table (right) --}}
        <div class="px-1 py-1 border-b border-gray-100 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 max-w-md">
                    
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
                    <input
                        x-model.debounce.400ms="search"
                        @input="loadConversations(true)"
                        type="text"
                        placeholder="{{ __('Search name, phone, email...') }}"
                        class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] rounded-md focus:border-transparent !focus:shadow-none"
                    >
                </div>
                <button class="flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list-filter-icon lucide-list-filter"><path d="M2 5h20"/><path d="M6 12h12"/><path d="M9 19h6"/></svg>
                </button>
            </div>
        </div>

        {{-- Sub-toolbar: bulk actions + pagination --}}
        <div class="px-1 py-1 flex items-center justify-between border-b border-gray-100 dark:border-white/10">
            <div class="flex items-center gap-1">
                <label class="flex items-center gap-1 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer">
                    <input type="checkbox"
                           :checked="allListedSelected"
                           @change="toggleSelectAll()"
                           class="rounded border-gray-300 dark:border-white/20 text-[#1E5FEA] focus:ring-[#1E5FEA]">
                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M5.25 7.5 10 12.25 14.75 7.5H5.25Z"/></svg>
                </label>
                <button @click="syncNow()" :disabled="syncing" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-white/5 transition-colors" title="{{ __('Refresh') }}">
                    <svg class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                </button>
                <div x-show="selectedConvIds.length > 0" x-cloak class="relative" @click.outside="listMoreOpen = false; listMoreSubmenu = null">
                    <button
                        type="button"
                        @click.stop="listMoreOpen = !listMoreOpen; listMoreSubmenu = null;"
                        class="p-1.5 rounded transition-colors text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5"
                        title="{{ __('More') }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/></svg>
                    </button>

                    <div x-show="listMoreOpen" x-cloak class="absolute left-0 top-full mt-1 z-50 min-w-[220px] rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-[#1a1a1a] shadow-lg py-1">
                        <button @click.stop="listMoreSubmenu = listMoreSubmenu === 'folder' ? null : 'folder'" class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-plus-icon lucide-folder-plus"><path d="M12 10v6"/><path d="M9 13h6"/><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
                                {{ __('Add to folder') }}
                            </span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </button>
                        <div x-show="listMoreSubmenu === 'folder'" x-cloak class="border-t border-gray-100 dark:border-white/10 max-h-56 overflow-y-auto">
                            <template x-for="f in customFolders" :key="'toolbar-folder-' + f.id">
                                <button @click.stop="bulkToggleFolder(f)" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <svg class="w-4 h-4 flex-shrink-0" :style="'color:' + f.color" fill="currentColor" viewBox="0 0 24 24"><path d="M2 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6Z"/></svg>
                                        <span class="truncate" x-text="f.name"></span>
                                    </span>
                                    <svg x-show="selectedConvsAllHaveFolder(f.id)" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                </button>
                            </template>
                            <div x-show="!customFolders.length" class="px-3 py-2 text-xs text-gray-400">{{ __('No folders yet') }}</div>
                        </div>

                        <button @click.stop="listMoreSubmenu = listMoreSubmenu === 'tag' ? null : 'tag'" class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag-icon lucide-tag"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                                {{ __('Add Tags') }}
                            </span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </button>
                        <div x-show="listMoreSubmenu === 'tag'" x-cloak class="border-t border-gray-100 dark:border-white/10 max-h-56 overflow-y-auto">
                            <template x-for="t in customTags" :key="'toolbar-tag-' + t.id">
                                <button @click.stop="bulkToggleTag(t)" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <span class="block w-2.5 h-2.5 rounded-full flex-shrink-0 border border-black/10" :style="'background-color:' + t.color"></span>
                                        <span class="truncate" x-text="t.name"></span>
                                    </span>
                                    <svg x-show="selectedConvsAllHaveTag(t.name)" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                </button>
                            </template>
                            <div x-show="!customTags.length" class="px-3 py-2 text-xs text-gray-400">{{ __('No tags yet') }}</div>
                        </div>

                        {{-- Priority --}}
                        <button @click.stop="listMoreSubmenu = listMoreSubmenu === 'priority' ? null : 'priority'" class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/></svg>
                                {{ __('Priority') }}
                            </span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </button>
                        <div x-show="listMoreSubmenu === 'priority'" x-cloak class="border-t border-gray-100 dark:border-white/10">
                            <button @click.stop="bulkSetPriority('high')" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                                    {{ __('High') }}
                                </span>
                                <svg x-show="selectedConvsAllHavePriority('high')" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </button>
                            <button @click.stop="bulkSetPriority('medium')" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                                    {{ __('Medium') }}
                                </span>
                                <svg x-show="selectedConvsAllHavePriority('medium')" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </button>
                            <button @click.stop="bulkSetPriority('low')" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                                    {{ __('Low') }}
                                </span>
                                <svg x-show="selectedConvsAllHavePriority('low')" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </button>
                            <button @click.stop="bulkSetPriority(null)" class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                {{ __('None') }}
                            </button>
                        </div>

                        <div class="border-t border-gray-100 dark:border-white/10 my-1"></div>

                        <button @click.stop="listMoreOpen = false; bulkMarkUnread()" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><circle cx="18" cy="18" r="3"/></svg>
                            {{ __('Mark as unread') }}
                        </button>
                        <button @click.stop="listMoreOpen = false; bulkMove('snoozed')" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-clock-icon lucide-folder-clock"><path d="M16 14v2.2l1.6 1"/><path d="M7 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2"/><circle cx="16" cy="16" r="6"/></svg>
                            {{ __('Snooze') }}
                        </button>
                        <button @click.stop="listMoreOpen = false; bulkMove('archive')" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-archive-icon lucide-archive"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>
                            {{ __('Archive') }}
                        </button>
                        <button @click.stop="listMoreOpen = false; bulkMove('spam')" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert-icon lucide-circle-alert"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                            {{ __('Spam') }}
                        </button>
                        <button @click.stop="listMoreOpen = false; bulkMove('trash')" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 text-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2-icon lucide-trash-2"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            {{ __('Trash') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                <button class="p-1 rounded hover:bg-gray-50 dark:hover:bg-white/5 disabled:opacity-40" :disabled="page <= 1" @click="page = Math.max(1, page - 1); conversations = []; loadConversations(false)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <button class="p-1 rounded hover:bg-gray-50 dark:hover:bg-white/5 disabled:opacity-40" :disabled="!hasMore" @click="if (hasMore) { page = page + 1; conversations = []; loadConversations(false) }">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
                <span x-text="paginationLabel"></span>
            </div>
        </div>

        {{-- Category tabs (Primary / Promotions / Updates / Social) --}}
        {{-- <div class="px-4 py-2 flex items-center gap-1 overflow-x-auto border-b border-gray-100 dark:border-white/10">
            <template x-for="cat in categories" :key="cat.key">
                <button
                    @click="selectCategory(cat.key)"
                    :class="activeCategory === cat.key ? 'bg-[#1E5FEA]/10 text-[#1E5FEA] border-[#1E5FEA]/20' : 'text-gray-500 dark:text-gray-400 border-transparent hover:bg-gray-50 dark:hover:bg-white/5'"
                    class="flex items-center gap-1 px-2.5 py-1 text-xs rounded-full border whitespace-nowrap transition-colors"
                >
                    <span x-html="cat.icon"></span>
                    <span x-text="cat.label"></span>
                </button>
            </template>
        </div> --}}

        <div class="flex-1 overflow-y-auto" id="conv-list">
            {{-- Skeleton shown while switching inbox account --}}
            <template x-if="navLoading">
                <div>
                    <div class="px-4 py-2 bg-gray-50/50 dark:bg-white/[0.02]">
                        <div class="h-2.5 w-16 rounded bg-gray-200 dark:bg-white/10 animate-pulse"></div>
                    </div>
                    <template x-for="i in 12" :key="'sk-conv-' + i">
                        <div class="flex items-start gap-3 px-4 py-3 border-b border-gray-100 dark:border-white/5 animate-pulse">
                            <div class="w-4 h-4 rounded bg-gray-200 dark:bg-white/10 mt-1 flex-shrink-0"></div>
                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-white/10 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0 space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="h-3 rounded bg-gray-200 dark:bg-white/10" :style="'width:' + (90 + (i*13)%60) + 'px'"></div>
                                    <div class="h-2.5 w-10 rounded bg-gray-200 dark:bg-white/10"></div>
                                </div>
                                <div class="h-3 rounded bg-gray-200 dark:bg-white/10" :style="'width:' + (50 + (i*7)%40) + '%'"></div>
                                <div class="h-2.5 rounded bg-gray-200 dark:bg-white/10" :style="'width:' + (60 + (i*5)%30) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!navLoading && loadingConversations && conversations.length === 0">
                <div class="flex items-center justify-center py-16">
                    <svg class="animate-spin w-5 h-5 text-[#1E5FEA]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
            </template>

            <template x-if="!navLoading && !loadingConversations && conversations.length === 0">
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <svg class="w-10 h-10 text-gray-200 dark:text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                    <p class="text-sm text-gray-400 dark:text-gray-600">{{ __('No conversations') }}</p>
                </div>
            </template>

            {{-- STARRED section --}}
            <template x-if="!navLoading && starredConversations.length > 0">
                <div>
                    <div class="flex items-center justify-between px-4 py-2 bg-gray-50/50 dark:bg-white/[0.02]">
                        <span class="text-[11px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">{{ __('Starred') }}</span>
                        <button @click.stop="starredOpen = !starredOpen" class="p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-3.5 h-3.5 transition-transform" :class="starredOpen ? '' : '-rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/></svg>
                        </button>
                    </div>
                    <div x-show="starredOpen" x-cloak>
                        <div>
                            <template x-for="conv in starredConversations" :key="'s-' + conv.id">
                                <div>
                                    @include('customer.unibox._conversation_row')
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            {{-- MESSAGES section --}}
            <template x-if="!navLoading && otherConversations.length > 0">
                <div>
                    <div class="flex items-center justify-between px-4 py-2 bg-gray-50/50 dark:bg-white/[0.02]">
                        <span class="text-[11px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">{{ __('Messages') }}</span>
                        <button @click.stop="messagesOpen = !messagesOpen" class="p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-3.5 h-3.5 transition-transform" :class="messagesOpen ? '' : '-rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/></svg>
                        </button>
                    </div>
                    <div x-show="messagesOpen" x-cloak>
                        <div>
                            <template x-for="conv in otherConversations" :key="'m-' + conv.id">
                                <div>
                                    @include('customer.unibox._conversation_row')
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!navLoading && hasMore">
                <div class="px-3 py-3">
                    <button @click="loadMore()" class="w-full text-center text-xs text-[#1E5FEA] hover:underline py-1" :disabled="loadingConversations">
                        <span x-show="!loadingConversations">{{ __('Load more') }}</span>
                        <span x-show="loadingConversations">{{ __('Loading...') }}</span>
                    </button>
                </div>
            </template>
        </div>
    </div>
    {{-- ══════════════════════════════════════════ NEW TEMPLATE PANEL (Slide-over) INSIDE SCOPE ══ --}}
    <div x-show="showNewTemplateModal" x-cloak class="fixed inset-0 z-[60]">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/30" @click="showNewTemplateModal = false"></div>

        <!-- Panel -->
        <div class="absolute right-0 top-0 h-full w-full max-w-xl bg-white dark:bg-[#1a1a1a] shadow-2xl border-l border-gray-200 dark:border-white/10 flex flex-col" @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('New Template') }}</h2>
                <button @click="showNewTemplateModal = false" class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-white/5 text-gray-500 dark:text-gray-400 transition-colors" title="{{ __('Close') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-4 space-y-4 overflow-y-auto flex-1">
                {{-- Name --}}
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 w-16 flex-shrink-0">{{ __('Name') }}</label>
                    <input x-model="newTemplate.name" type="text" placeholder="{{ __('e.g. Sales Renewal Quote') }}" class="flex-1 text-sm border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2 bg-white dark:bg-transparent text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>

                {{-- Folder --}}
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 w-16 flex-shrink-0">{{ __('Folder') }}</label>
                    <template x-if="!showNewFolderInput">
                        <div class="flex items-center gap-2 flex-1">
                            <select x-model="newTemplate.folder" class="flex-1 text-sm border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2 bg-white dark:bg-transparent text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-400">
                                <option value="">{{ __('General') }}</option>
                                <template x-for="f in existingFolders" :key="f">
                                    <option :value="f" x-text="f"></option>
                                </template>
                            </select>
                            <button @click="showNewFolderInput = true" class="text-xs text-blue-500 hover:text-blue-600 font-medium whitespace-nowrap">{{ __('New Folder') }}</button>
                        </div>
                    </template>
                    <template x-if="showNewFolderInput">
                        <div class="flex items-center gap-2 flex-1">
                            <input x-model="newFolderInput" type="text" placeholder="{{ __('Folder name') }}" class="flex-1 text-sm border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2 bg-white dark:bg-transparent text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-400">
                            <button @click="showNewFolderInput = false" class="text-xs text-gray-400 hover:text-gray-600 font-medium">{{ __('Cancel') }}</button>
                        </div>
                    </template>
                </div>

                {{-- Subject --}}
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 w-16 flex-shrink-0">{{ __('Subject') }}</label>
                    <input x-model="newTemplate.subject" type="text" placeholder="{{ __('Optional subject line') }}" class="flex-1 text-sm border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2 bg-white dark:bg-transparent text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>

                {{-- Body (rich text editor) --}}
                <div x-data="{
                        showVarMenu: false,
                        variables: [
                            { key: 'first_name', label: '{{ __('First Name') }}' },
                            { key: 'last_name', label: '{{ __('Last Name') }}' },
                            { key: 'full_name', label: '{{ __('Full Name') }}' },
                            { key: 'email', label: '{{ __('Email') }}' },
                            { key: 'company', label: '{{ __('Company') }}' },
                        ],
                        execCmd(cmd, val = null) {
                            this.$refs.tplEditor?.focus();
                            document.execCommand(cmd, false, val);
                            this.syncBody();
                        },
                        syncBody() {
                            this.newTemplate.body = this.$refs.tplEditor?.innerHTML || '';
                        },
                        insertVar(key) {
                            this.$refs.tplEditor?.focus();
                            document.execCommand('insertText', false, '{{ '{' }}' + key + '{{ '}' }}');
                            this.showVarMenu = false;
                            this.syncBody();
                        },
                        insertLink() {
                            const url = window.prompt('{{ __('Enter URL') }}', 'https://');
                            if (url) this.execCmd('createLink', url);
                        },
                    }"
                    x-init="$watch('showNewTemplateModal', v => { if (v) $nextTick(() => { if ($refs.tplEditor) $refs.tplEditor.innerHTML = newTemplate.body || ''; }); })">
                    <div class="border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-transparent overflow-hidden">
                        {{-- Toolbar --}}
                        <div class="flex items-center gap-1 px-2 py-1.5 border-b border-gray-100 dark:border-white/10 bg-gray-50/60 dark:bg-white/5">
                            <select @change="execCmd('formatBlock', $event.target.value); $event.target.value='';" class="text-sm bg-transparent text-gray-700 dark:text-gray-300 border-0 focus:outline-none focus:ring-0 pr-2">
                                <option value="">{{ __('Normal Text') }}</option>
                                <option value="H1">{{ __('Heading 1') }}</option>
                                <option value="H2">{{ __('Heading 2') }}</option>
                                <option value="H3">{{ __('Heading 3') }}</option>
                                <option value="P">{{ __('Paragraph') }}</option>
                            </select>
                            <span class="w-px h-5 bg-gray-200 dark:bg-white/10 mx-1"></span>
                            <button type="button" @click="execCmd('bold')" class="p-1.5 rounded hover:bg-gray-200/70 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300" title="{{ __('Bold') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>
                            </button>
                            <button type="button" @click="execCmd('italic')" class="p-1.5 rounded hover:bg-gray-200/70 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300" title="{{ __('Italic') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="10" y1="4" y2="4"/><line x1="14" x2="5" y1="20" y2="20"/><line x1="15" x2="9" y1="4" y2="20"/></svg>
                            </button>
                            <button type="button" @click="execCmd('underline')" class="p-1.5 rounded hover:bg-gray-200/70 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300" title="{{ __('Underline') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v6a6 6 0 0 0 12 0V4"/><line x1="4" x2="20" y1="20" y2="20"/></svg>
                            </button>
                            <span class="w-px h-5 bg-gray-200 dark:bg-white/10 mx-1"></span>
                            <button type="button" @click="execCmd('insertUnorderedList')" class="p-1.5 rounded hover:bg-gray-200/70 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300" title="{{ __('Bulleted list') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
                            </button>
                            <button type="button" @click="insertLink()" class="p-1.5 rounded hover:bg-gray-200/70 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300" title="{{ __('Insert link') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            </button>

                            {{-- Insert Variable --}}
                            <div class="ml-auto relative" @click.outside="showVarMenu = false">
                                <button type="button" @click="showVarMenu = !showVarMenu" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-semibold hover:bg-blue-100 dark:hover:bg-blue-500/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    {{ __('Insert Variable') }}
                                </button>
                                <div x-show="showVarMenu" x-transition x-cloak class="absolute right-0 top-full mt-1 z-30 w-56 bg-white dark:bg-[#1f1f1f] border border-gray-200 dark:border-white/10 rounded-lg shadow-lg overflow-hidden">
                                    <template x-for="v in variables" :key="v.key">
                                        <button type="button" @click="insertVar(v.key)" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 flex items-center justify-between">
                                            <span x-text="v.label"></span>
                                            <code class="text-[10px] text-gray-400 dark:text-gray-500" x-text="'{' + v.key + '}'"></code>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Editable area --}}
                        <div x-ref="tplEditor"
                             contenteditable="true"
                             @input="syncBody()"
                             @blur="syncBody()"
                             data-placeholder="{{ __('Type your template content here... Use {variable_name} to add dynamic fields.') }}"
                             class="tpl-editor min-h-[260px] max-h-[420px] overflow-y-auto px-4 py-3 text-sm text-gray-800 dark:text-gray-200 focus:outline-none"></div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        {{ __('Tip: Use the Insert Variable button to add dynamic fields') }}
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-white/10">
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                    <input type="checkbox" x-model="newTemplate.shared" class="rounded border-gray-300 dark:border-white/20 text-blue-500 focus:ring-blue-400">
                    {{ __('Share with Team') }}
                </label>
                <div class="flex items-center gap-3">
                    <button @click="showNewTemplateModal = false" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium">{{ __('Cancel') }}</button>
                    <button @click="saveNewTemplate()" :disabled="!newTemplate.name || !newTemplate.body" class="px-5 py-2 bg-[#1E5FEA] hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors">{{ __('Save Template') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════ MAIN THREAD PANEL ══ --}}
    <template x-if="activeConversation">
        <div class="flex-1 flex flex-col h-full overflow-hidden bg-white dark:bg-[#111] relative border-l">
                {{-- Thread header --}}
                <div class="px-4 py-3 border-b border-gray-100 dark:border-white/10 flex items-center justify-between gap-4 flex-shrink-0">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="min-w-0 flex-1">
                            <h1 x-text="activeConversation.subject" class="text-base font-semibold text-gray-900 dark:text-white truncate"></h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                <span x-text="activeConversation.contact?.name || activeConversation.contact?.email" class="font-medium text-gray-700 dark:text-gray-300"></span>
                                <span x-show="activeConversation.contact?.name && activeConversation.contact?.email" class="text-gray-400">
                                    &lt;<span x-text="activeConversation.contact?.email"></span>&gt;
                                </span>
                            </p>
                            <div class="flex items-center gap-1.5 mt-1" x-show="(activeConversation.tags || []).length > 0">
                                <template x-for="tag in (activeConversation.tags || []).filter(t => !['high','medium','low'].includes((t||'').toLowerCase()))" :key="tag">
                                    <span x-text="tag" :style="tagChipStyle(tag)" class="text-xs font-medium px-2.5 py-0.5 rounded-full truncate"></span>
                                </template>
                                <template x-if="priorityOf(activeConversation)">
                                    <span :style="priorityStyle(priorityOf(activeConversation))" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                                        <span x-text="priorityOf(activeConversation)"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span x-show="activeConversation.last_message_at_formatted" x-text="activeConversation.last_message_at_formatted" class="text-xs text-gray-500 dark:text-gray-400 mr-1"></span>
                        <button @click="assignConversation()" class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round-plus-icon lucide-user-round-plus"><path d="M2 21a8 8 0 0 1 13.292-6"/><circle cx="10" cy="8" r="5"/><path d="M19 16v6"/><path d="M22 19h-6"/></svg>
                            {{ __('Assign') }}
                        </button>
                        <div class="relative" @click.outside="threadMoreOpen = false; threadMoreSubmenu = null">
                            <button @click="threadMoreOpen = !threadMoreOpen; threadMoreSubmenu = null" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-white/5 text-gray-400" title="{{ __('More') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                            </button>

                            <div x-show="threadMoreOpen" x-transition class="absolute right-0 top-full mt-1 z-30 min-w-[200px] rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-[#1a1a1a] shadow-lg py-1" style="display: none;">
                                {{-- Add to folder --}}
                                <button @click.stop="threadMoreSubmenu = threadMoreSubmenu === 'folder' ? null : 'folder'" class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-plus-icon lucide-folder-plus"><path d="M12 10v6"/><path d="M9 13h6"/><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
                                        {{ __('Add to folder') }}
                                    </span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                </button>
                                <div x-show="threadMoreSubmenu === 'folder'" class="border-t border-gray-100 dark:border-white/10 max-h-56 overflow-y-auto" style="display: none;">
                                    <template x-for="f in customFolders" :key="'tf-' + f.id">
                                        <button @click.stop="toggleConversationFolder(f)" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                            <span class="flex items-center gap-2 min-w-0">
                                                <svg class="w-4 h-4 flex-shrink-0" :style="'color:' + f.color" fill="currentColor" viewBox="0 0 24 24"><path d="M2 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6Z"/></svg>
                                                <span class="truncate" x-text="f.name"></span>
                                            </span>
                                            <svg x-show="(activeConversation.folder_ids || []).includes(f.id)" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        </button>
                                    </template>
                                    <div x-show="!customFolders.length" class="px-3 py-2 text-xs text-gray-400">{{ __('No folders yet') }}</div>
                                </div>

                                {{-- Add to tags --}}
                                <button @click.stop="threadMoreSubmenu = threadMoreSubmenu === 'tag' ? null : 'tag'" class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag-icon lucide-tag"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                                        {{ __('Add to tags') }}
                                    </span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                </button>
                                <div x-show="threadMoreSubmenu === 'tag'" class="border-t border-gray-100 dark:border-white/10 max-h-56 overflow-y-auto" style="display: none;">
                                    <template x-for="t in customTags" :key="'tt-' + t.id">
                                        <button @click.stop="toggleConversationTag(t)" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                            <span class="flex items-center gap-2 min-w-0">
                                                <span class="block w-2.5 h-2.5 rounded-full flex-shrink-0 border border-black/10" :style="'background-color:' + t.color"></span>
                                                <span class="truncate" x-text="t.name"></span>
                                            </span>
                                            <svg x-show="(activeConversation.tags || []).map(x => (x||'').toLowerCase()).includes(t.name.toLowerCase())" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        </button>
                                    </template>
                                    <div x-show="!customTags.length" class="px-3 py-2 text-xs text-gray-400">{{ __('No tags yet') }}</div>
                                </div>

                                {{-- Priority --}}
                                <button @click.stop="threadMoreSubmenu = threadMoreSubmenu === 'priority' ? null : 'priority'" class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                    <span class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flag"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                                        {{ __('Priority') }}
                                    </span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                </button>
                                <div x-show="threadMoreSubmenu === 'priority'" x-cloak class="border-t border-gray-100 dark:border-white/10">
                                    <button @click.stop="setConversationPriority('high')" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                                            {{ __('High') }}
                                        </span>
                                        <svg x-show="priorityOf(activeConversation) === 'high'" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    </button>
                                    <button @click.stop="setConversationPriority('medium')" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                                            {{ __('Medium') }}
                                        </span>
                                        <svg x-show="priorityOf(activeConversation) === 'medium'" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    </button>
                                    <button @click.stop="setConversationPriority('low')" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
                                            {{ __('Low') }}
                                        </span>
                                        <svg x-show="priorityOf(activeConversation) === 'low'" class="w-4 h-4 text-[#7c3aed] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    </button>
                                    <button @click.stop="setConversationPriority(null)" class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        {{ __('None') }}
                                    </button>
                                </div>

                                <div class="border-t border-gray-100 dark:border-white/10 my-1"></div>

                                <button @click.stop="threadMoreOpen = false; markConversationUnread(activeConversation)" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><circle cx="18" cy="18" r="3"/></svg>
                                    {{ __('Mark as unread') }}
                                </button>
                                <button @click.stop="threadMoreOpen = false; moveConversation(activeConversation, 'snoozed')" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-clock-icon lucide-folder-clock"><path d="M16 14v2.2l1.6 1"/><path d="M7 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2"/><circle cx="16" cy="16" r="6"/></svg>
                                    {{ __('Snooze') }}
                                </button>
                                <button @click.stop="threadMoreOpen = false; moveConversation(activeConversation, 'archive')" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-archive-icon lucide-archive"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>
                                    {{ __('Archive') }}
                                </button>
                                <button @click.stop="threadMoreOpen = false; moveConversation(activeConversation, 'spam')" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 text-left">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert-icon lucide-circle-alert"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                                    {{ __('Spam') }}
                                </button>
                                <button @click.stop="threadMoreOpen = false; moveConversation(activeConversation, 'trash')" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 text-left">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2-icon lucide-trash-2"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Messages --}}
                <div class="flex-1 overflow-y-auto space-y-4" id="messages-container">
                    <template x-if="loadingThread">
                        <div class="flex justify-center py-8">
                            <svg class="animate-spin w-5 h-5 text-[#1E5FEA]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </div>
                    </template>

                    <template x-for="(msg, idx) in threadMessages" :key="msg.id">
                        <div class="bg-white dark:bg-[#1a1a1a] border-b border-gray-100 dark:border-white/10 overflow-hidden !mt-0">
                            {{-- Header (always visible, click toggles body) --}}
                            <div
                                @click="toggleMessage(msg.id)"
                                class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                :class="isMessageExpanded(msg, idx) ? 'items-start' : 'items-center'"
                            >
                                <template x-if="activeContact?.avatar_url && activeContact?.email === msg.from_email">
                                    <img :src="activeContact.avatar_url" class="rounded-full object-cover flex-shrink-0 transition-all" :class="isMessageExpanded(msg, idx) ? 'w-8 h-8' : 'w-10 h-10'">
                                </template>
                                <template x-if="!(activeContact?.avatar_url && activeContact?.email === msg.from_email)">
                                    <div
                                        class="rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold flex-shrink-0 transition-all"
                                        :class="isMessageExpanded(msg, idx) ? 'w-8 h-8 text-xs' : 'w-10 h-10 text-base'"
                                    >
                                        <span x-text="(msg.from_name || msg.from_email || '?').charAt(0).toUpperCase()"></span>
                                    </div>
                                </template>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline gap-2">
                                        <span
                                            x-text="msg.from_name || msg.from_email"
                                            class="text-gray-900 dark:text-white truncate"
                                            :class="isMessageExpanded(msg, idx) ? 'text-sm font-semibold' : 'text-sm font-bold'"
                                        ></span>
                                        <span x-show="msg.from_name && msg.from_email" x-text="'<' + msg.from_email + '>'" class="text-xs text-gray-400 truncate"></span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-show="isMessageExpanded(msg, idx)">
                                        <span x-text="(msg.to && msg.to[0]) ? ('to ' + (msg.to[0].name || msg.to[0].email)) : ''"></span>
                                    </div>
                                    {{-- Collapsed preview line --}}
                                    <div x-show="!isMessageExpanded(msg, idx)" class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5" x-text="(msg.body_text || msg.body_html || '').replace(/<[^>]*>/g,'').replace(/\s+/g,' ').trim().substring(0, 140)"></div>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap" x-text="msg.time_human || msg.time"></div>
                                </div>
                            </div>

                            {{-- Body (expanded) --}}
                            <div x-show="isMessageExpanded(msg, idx)" x-collapse class="px-4 pb-4 pt-1">
                                <template x-if="splitQuotedText(msg.body_html).hasQuote">
                                    <div>
                                        <div class="prose prose-sm dark:prose-invert max-w-none text-sm text-gray-700 dark:text-gray-300" x-html="splitQuotedText(msg.body_html).visible || (msg.body_text || '').replace(/\n/g, '<br>')"></div>
                                        <div class="mt-2">
                                            <button
                                                @click="toggleQuote(msg.id)"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                                            >
                                                <template x-if="!expandedQuoteIds.includes(msg.id)">
                                                    <span>{{ __('Show quoted text') }}</span>
                                                </template>
                                                <template x-if="expandedQuoteIds.includes(msg.id)">
                                                    <span>{{ __('Hide quoted text') }}</span>
                                                </template>
                                            </button>
                                        </div>
                                        <div x-show="expandedQuoteIds.includes(msg.id)" x-collapse class="mt-3 prose prose-sm dark:prose-invert max-w-none text-sm text-gray-500 dark:text-gray-400 border-l-2 border-gray-200 dark:border-gray-700 pl-3">
                                            <div x-html="splitQuotedText(msg.body_html).quote"></div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!splitQuotedText(msg.body_html).hasQuote">
                                    <div class="prose prose-sm dark:prose-invert max-w-none text-sm text-gray-700 dark:text-gray-300" x-html="msg.body_html || (msg.body_text || '').replace(/\n/g, '<br>')"></div>
                                </template>
                                {{-- Attachments --}}
                                <template x-if="msg.attachments && msg.attachments.length > 0">
                                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-white/10">
                                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                            <span x-text="msg.attachments.length + ' attachment' + (msg.attachments.length > 1 ? 's' : '')"></span>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="(att, ai) in msg.attachments" :key="ai">
                                                <div class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg text-xs text-gray-600 dark:text-gray-300">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400 flex-shrink-0"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                                                    <span x-text="att.name" class="truncate max-w-[150px]"></span>
                                                    <span class="text-gray-400 text-[10px]" x-text="formatFileSize(att.size)"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Internal notes --}}
                    <template x-if="threadNotes.length > 0">
                        <div class="bg-amber-50 dark:bg-amber-900/10 border-t border-amber-200 dark:border-amber-500/20 p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v8.25A2.25 2.25 0 0 0 6 16.5h2.25m8.25-8.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-7.5A2.25 2.25 0 0 1 8.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 0 0-2.25 2.25v6"/></svg>
                                <span class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400">{{ __('Internal Notes') }}</span>
                            </div>
                            <template x-for="note in threadNotes" :key="note.id">
                                <div class="group flex items-start gap-2.5 mb-2.5 last:mb-0">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-green-400 to-teal-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                        <span x-text="(note.author || '?').charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200" x-text="note.author"></span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 ml-1" x-text="note.content"></span>
                                    </div>
                                    <button @click="deleteNote(note.id)" class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500 transition-all flex-shrink-0" title="{{ __('Delete note') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </div>
                            </template>
                            <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-500/20 flex items-center gap-2">
                                <input
                                    x-model="newNote"
                                    type="text"
                                    placeholder="{{ __('Add an internal note...') }}"
                                    @keydown.enter="addNote()"
                                    class="flex-1 text-sm bg-transparent border-0 outline-none text-gray-700 dark:text-gray-300 placeholder-gray-400"
                                >
                                <button @click="addNote()" x-show="newNote" class="text-xs font-medium text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-200">{{ __('Add') }}</button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Reply box --}}
                <div class="flex-shrink-0 border-t border-gray-100 dark:border-white/10 bg-white dark:bg-[#111]">
                    <div class=" border-gray-200 dark:border-white/10 overflow-hidden">
                        {{-- Toolbar --}}
                        <div class="flex items-center gap-0.5 px-3 py-1.5 border-b border-gray-100 dark:border-white/10">
                            {{-- Heading select --}}
                            <select @change="execFormat('formatBlock', $event.target.value); $event.target.value = ''"
                                class="text-xs text-gray-500 dark:text-gray-400 bg-transparent border-0 focus:ring-0 py-1 pl-1 pr-5 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200">
                                <option value="" disabled selected>{{ __('Normal') }}</option>
                                <option value="H1">H1</option>
                                <option value="H2">H2</option>
                                <option value="H3">H3</option>
                                <option value="P">{{ __('Paragraph') }}</option>
                            </select>

                            <div class="w-px h-4 bg-gray-200 dark:bg-white/10 mx-1"></div>

                            {{-- Bold --}}
                            <button @click="execFormat('bold')" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors" title="{{ __('Bold') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 12h9a4 4 0 0 1 0 8H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h7a4 4 0 0 1 0 8"/></svg>
                            </button>
                            {{-- Italic --}}
                            <button @click="execFormat('italic')" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors" title="{{ __('Italic') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="10" y1="4" y2="4"/><line x1="14" x2="5" y1="20" y2="20"/><line x1="15" x2="9" y1="4" y2="20"/></svg>
                            </button>
                            {{-- Underline --}}
                            <button @click="execFormat('underline')" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors" title="{{ __('Underline') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v6a6 6 0 0 0 12 0V4"/><line x1="4" x2="20" y1="20" y2="20"/></svg>
                            </button>

                            <div class="w-px h-4 bg-gray-200 dark:bg-white/10 mx-1"></div>

                            {{-- Align left --}}
                            <button @click="execFormat('justifyLeft')" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors" title="{{ __('Align Left') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="15" x2="3" y1="12" y2="12"/><line x1="17" x2="3" y1="18" y2="18"/></svg>
                            </button>
                            {{-- Align center --}}
                            <button @click="execFormat('justifyCenter')" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors" title="{{ __('Align Center') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="18" x2="6" y1="12" y2="12"/><line x1="21" x2="3" y1="18" y2="18"/></svg>
                            </button>
                            {{-- Align right --}}
                            <button @click="execFormat('justifyRight')" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors" title="{{ __('Align Right') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="7" y1="18" y2="18"/></svg>
                            </button>

                            <div class="w-px h-4 bg-gray-200 dark:bg-white/10 mx-1"></div>

                            {{-- Bullet list --}}
                            <button @click="execFormat('insertUnorderedList')" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors" title="{{ __('Bullet List') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
                            </button>
                            {{-- Numbered list --}}
                            <button @click="execFormat('insertOrderedList')" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors" title="{{ __('Numbered List') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" x2="21" y1="6" y2="6"/><line x1="10" x2="21" y1="12" y2="12"/><line x1="10" x2="21" y1="18" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
                            </button>

                            <div class="w-px h-4 bg-gray-200 dark:bg-white/10 mx-1"></div>

                            {{-- Attachment --}}
                            <button @click="$refs.replyFileInput.click()" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors" title="{{ __('Attach File') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            </button>
                            <input type="file" x-ref="replyFileInput" @change="handleReplyFiles($event)" multiple class="hidden">
                        </div>

                        {{-- Contenteditable editor --}}
                        <div
                            x-ref="replyEditor"
                            contenteditable="true"
                            @input="replyBody = $refs.replyEditor.innerHTML"
                            @keydown.meta.enter="sendReply()"
                            @keydown.ctrl.enter="sendReply()"
                            data-placeholder="{{ __('Write a reply...') }}"
                            class="reply-editor w-full px-4 py-3 text-sm text-gray-800 dark:text-gray-200 bg-white dark:bg-transparent min-h-[100px] max-h-[200px] overflow-y-auto border-0 focus:outline-none"
                        ></div>

                        {{-- Attachment previews --}}
                        <template x-if="replyAttachments.length > 0">
                            <div class="px-4 pt-2 border-t border-gray-100 dark:border-white/10 mb-0 flex flex-wrap gap-2">
                                <template x-for="(file, idx) in replyAttachments" :key="idx">
                                    <div class="flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-white/5 rounded-lg text-xs text-gray-600 dark:text-gray-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                                        <span x-text="file.name" class="truncate max-w-[120px]"></span>
                                        <span class="text-gray-400" x-text="formatFileSize(file.size)"></span>
                                        <button @click="removeReplyAttachment(idx)" class="text-gray-400 hover:text-red-500 ml-0.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Bottom bar --}}
                        <div class="flex items-center justify-between px-4 py-2 border-t border-gray-100 dark:border-white/10 mb-0">
                            {{-- Left: Templates + Internal Note --}}
                            <div class="flex items-center gap-1">
                            <div class="relative">
                                <button x-ref="tplBtn" data-templates-toggle @click="if (!showTemplatePopup) { const r = $refs.tplBtn.getBoundingClientRect(); tplTop = r.top - 8; tplLeft = r.left; loadReplyTemplates(); } showTemplatePopup = !showTemplatePopup;" class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg transition-colors" title="{{ __('Templates') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
                                    {{ __('Templates') }}
                                </button>

                                {{-- Template popup (teleported to body to escape overflow:hidden parents) --}}
                                <template x-teleport="body">
                                <div x-show="showTemplatePopup" x-transition x-cloak
                                     @click.outside="if (!$event.target.closest('[data-templates-toggle]')) showTemplatePopup = false"
                                     :style="`top: ${tplTop}px; left: ${tplLeft}px;`"
                                     class="fixed -translate-y-full z-50 w-[340px] max-h-[400px] rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-[#1a1a1a] shadow-xl flex flex-col overflow-hidden">
                                    {{-- Search + New --}}
                                    <div class="flex items-center gap-2 p-3 border-b border-gray-100 dark:border-white/10">
                                        <div class="relative flex-1">
                                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                            <input x-model="templateSearch" type="text" placeholder="{{ __('Search templates...') }}" class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-200 dark:border-white/10 rounded-lg bg-white dark:bg-transparent text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-400">
                                        </div>
                                        <button @click="openNewTemplateModal()" class="flex-shrink-0 flex flex-row items-center justify-center px-2.5 py-2 border border-gray-200 dark:border-white/10 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 text-gray-500 dark:text-gray-400 transition-colors" title="{{ __('New Template') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                                            <span class="text-[10px] font-medium mt-0.5">{{ __('New') }}</span>
                                        </button>
                                    </div>

                                    {{-- Template list grouped by folder --}}
                                    <div class="flex-1 overflow-y-auto">
                                        <template x-if="filteredTemplates.length === 0">
                                            <div class="px-4 py-8 text-center text-sm text-gray-400">{{ __('No templates yet') }}</div>
                                        </template>
                                        <template x-for="(templates, folder) in templateFolders" :key="folder">
                                            <div>
                                                <div class="px-4 pt-3 pb-1">
                                                    <p class="text-[10px] uppercase tracking-widest font-semibold text-gray-400 dark:text-gray-500" x-text="folder"></p>
                                                </div>
                                                <template x-for="tpl in templates" :key="tpl.id">
                                                    <div @click="insertTemplate(tpl)" class="group flex items-start justify-between gap-2 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer transition-colors">
                                                        <div class="min-w-0 flex-1">
                                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" x-text="tpl.name"></p>
                                                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5" x-text="tpl.body.replace(/<[^>]*>/g, '').substring(0, 60) + '...'"></p>
                                                        </div>
                                                        <button @click.stop="deleteTemplate(tpl.id)" class="flex-shrink-0 p-1 text-gray-300 dark:text-gray-600 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity" title="{{ __('Delete') }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                </template>
                            </div>

                            {{-- Internal Note toggle --}}
                            <button @click="showNoteInput = !showNoteInput" class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg transition-colors"
                                :class="showNoteInput ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/20'"
                                title="{{ __('Internal Note') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M13.4 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7.4"/><path d="M2 6h4"/><path d="M2 10h4"/><path d="M2 14h4"/><path d="M2 18h4"/><path d="M21.378 5.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/></svg>
                                {{ __('Internal Note') }}
                            </button>
                            </div>

                            {{-- Right: Send --}}
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-gray-400">⌘+Enter {{ __('to send') }}</span>
                                <button
                                    @click="sendReply()"
                                    :disabled="sendingReply || (!replyBody.replace(/<[^>]*>/g,'').trim() && replyAttachments.length === 0)"
                                    class="px-5 py-1.5 bg-[#1E5FEA] hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors"
                                >
                                    <span x-show="!sendingReply">{{ __('Send') }}</span>
                                    <span x-show="sendingReply">{{ __('Sending...') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Internal note input (toggled from reply header) --}}
                    <div x-show="showNoteInput" x-cloak x-transition class="px-4 py-2.5 bg-amber-50/80 dark:bg-amber-900/10 border-t border-amber-200 dark:border-amber-500/20 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500 flex-shrink-0"><path d="M16 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z"/><path d="M15 3v4a2 2 0 0 0 2 2h4"/></svg>
                        <input x-model="newNote" type="text" placeholder="{{ __('Type internal note...') }}"
                            @keydown.enter="addNote()"
                            class="flex-1 text-sm px-3 py-1.5 border border-amber-200 dark:border-amber-500/30 rounded-lg bg-white dark:bg-transparent text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-amber-400">
                        <button @click="addNote()" :disabled="!newNote.trim()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-medium rounded-lg disabled:opacity-50 transition-colors">{{ __('Add') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- ══════════════════════════════════════════ CONTACT PANEL ══ --}}
    <div class="w-72 flex-shrink-0 border-l border-gray-100 dark:border-white/10 bg-white dark:bg-[#0f0f0f] flex flex-col h-full overflow-y-auto" x-show="activeConversation && activeContact">
        <template x-if="activeContact">
            <div class="flex flex-col">
                {{-- Header: Avatar + Name + Stats --}}
                <div class="flex items-center gap-3 p-2 border-b border-gray-100 dark:border-white/10">
                    <div class="relative group flex-shrink-0">
                        <input type="file" accept="image/*" class="hidden" x-ref="avatarInput" @change="
                            const file = $event.target.files[0];
                            if (!file) return;
                            const fd = new FormData();
                            fd.append('avatar', file);
                            const reader = new FileReader();
                            reader.onload = (e) => { activeContact.avatar_url = e.target.result; };
                            reader.readAsDataURL(file);
                            fetch(`{{ url('customer/unibox') }}/contacts/${activeContact.id}/avatar`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                                body: fd,
                            }).then(r => r.json()).then(data => {
                                if (data.avatar_url) {
                                    activeContact.avatar_url = data.avatar_url;
                                    const conv = conversations.find(c => c.contact?.id === activeContact.id);
                                    if (conv && conv.contact) conv.contact.avatar_url = data.avatar_url;
                                }
                            });
                        ">
                        <template x-if="activeContact.avatar_url">
                            <img :src="activeContact.avatar_url" class="w-12 h-12 rounded-full object-cover">
                        </template>
                        <template x-if="!activeContact.avatar_url">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-lg font-bold">
                                <span x-text="(activeContact.name || activeContact.email || '?').charAt(0).toUpperCase()"></span>
                            </div>
                        </template>
                        <button @click="$refs.avatarInput.click()" class="absolute inset-0 w-12 h-12 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </button>
                    </div>
                    <div class="min-w-0">
                        <h3 x-text="activeContact.name || activeContact.email" class="text-sm font-bold text-gray-900 dark:text-white truncate"></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="(activeContact.conversation_count || 0) + ' conversations'"></p>
                    </div>
                </div>

                {{-- Contact Details (row-based layout) --}}
                <div class="divide-y divide-gray-50 dark:divide-white/5">
                    {{-- Email --}}
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">{{ __('Email') }}</span>
                        <span x-text="activeContact.email" class="text-xs font-medium text-gray-800 dark:text-gray-200 truncate text-right"></span>
                    </div>

                    {{-- Phone --}}
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">{{ __('Phone') }}</span>
                        <template x-if="editingContactField === 'phone'">
                            <div class="flex items-center gap-1">
                                <input id="contact-edit-input" x-model="editingContactValue" @keydown.enter="saveContactField()" @keydown.escape="cancelEditContact()" class="text-xs px-2 py-1 border border-blue-300 rounded w-28 focus:outline-none focus:ring-1 focus:ring-blue-400 dark:bg-transparent dark:text-gray-200">
                                <button @click="saveContactField()" class="text-blue-500 hover:text-blue-700"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></button>
                            </div>
                        </template>
                        <template x-if="editingContactField !== 'phone'">
                            <span @click="startEditContact('phone', activeContact.phone)" class="text-xs font-medium cursor-pointer text-right truncate" :class="activeContact.phone ? 'text-gray-800 dark:text-gray-200' : 'text-blue-500 hover:text-blue-600'" x-text="activeContact.phone || '{{ __('Add Phone...') }}'"></span>
                        </template>
                    </div>

                    {{-- Facebook --}}
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">{{ __('Facebook') }}</span>
                        <template x-if="editingContactField === 'facebook'">
                            <div class="flex items-center gap-1">
                                <input id="contact-edit-input" x-model="editingContactValue" @keydown.enter="saveContactField()" @keydown.escape="cancelEditContact()" class="text-xs px-2 py-1 border border-blue-300 rounded w-28 focus:outline-none focus:ring-1 focus:ring-blue-400 dark:bg-transparent dark:text-gray-200">
                                <button @click="saveContactField()" class="text-blue-500 hover:text-blue-700"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></button>
                            </div>
                        </template>
                        <template x-if="editingContactField !== 'facebook'">
                            <span @click="startEditContact('facebook', activeContact.social?.facebook)" class="text-xs font-medium cursor-pointer text-right truncate" :class="activeContact.social?.facebook ? 'text-gray-800 dark:text-gray-200' : 'text-blue-500 hover:text-blue-600'" x-text="activeContact.social?.facebook || '{{ __('Add Facebook...') }}'"></span>
                        </template>
                    </div>

                    {{-- Instagram --}}
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">{{ __('Instagram') }}</span>
                        <template x-if="editingContactField === 'instagram'">
                            <div class="flex items-center gap-1">
                                <input id="contact-edit-input" x-model="editingContactValue" @keydown.enter="saveContactField()" @keydown.escape="cancelEditContact()" class="text-xs px-2 py-1 border border-blue-300 rounded w-28 focus:outline-none focus:ring-1 focus:ring-blue-400 dark:bg-transparent dark:text-gray-200">
                                <button @click="saveContactField()" class="text-blue-500 hover:text-blue-700"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></button>
                            </div>
                        </template>
                        <template x-if="editingContactField !== 'instagram'">
                            <span @click="startEditContact('instagram', activeContact.social?.instagram)" class="text-xs font-medium cursor-pointer text-right truncate" :class="activeContact.social?.instagram ? 'text-gray-800 dark:text-gray-200' : 'text-blue-500 hover:text-blue-600'" x-text="activeContact.social?.instagram || '{{ __('Add Instagram...') }}'"></span>
                        </template>
                    </div>

                    {{-- X (Twitter) --}}
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">{{ __('X (twitter)') }}</span>
                        <template x-if="editingContactField === 'twitter'">
                            <div class="flex items-center gap-1">
                                <input id="contact-edit-input" x-model="editingContactValue" @keydown.enter="saveContactField()" @keydown.escape="cancelEditContact()" class="text-xs px-2 py-1 border border-blue-300 rounded w-28 focus:outline-none focus:ring-1 focus:ring-blue-400 dark:bg-transparent dark:text-gray-200">
                                <button @click="saveContactField()" class="text-blue-500 hover:text-blue-700"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></button>
                            </div>
                        </template>
                        <template x-if="editingContactField !== 'twitter'">
                            <span @click="startEditContact('twitter', activeContact.social?.twitter)" class="text-xs font-medium cursor-pointer text-right truncate" :class="activeContact.social?.twitter ? 'text-gray-800 dark:text-gray-200' : 'text-blue-500 hover:text-blue-600'" x-text="activeContact.social?.twitter || '{{ __('Add X...') }}'"></span>
                        </template>
                    </div>

                    {{-- Notes --}}
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">{{ __('Notes') }}</span>
                        <template x-if="editingContactField === 'notes'">
                            <div class="flex items-center gap-1">
                                <input id="contact-edit-input" x-model="editingContactValue" @keydown.enter="saveContactField()" @keydown.escape="cancelEditContact()" class="text-xs px-2 py-1 border border-blue-300 rounded w-28 focus:outline-none focus:ring-1 focus:ring-blue-400 dark:bg-transparent dark:text-gray-200">
                                <button @click="saveContactField()" class="text-blue-500 hover:text-blue-700"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></button>
                            </div>
                        </template>
                        <template x-if="editingContactField !== 'notes'">
                            <span @click="startEditContact('notes', activeContact.notes)" class="text-xs font-medium cursor-pointer text-right truncate" :class="activeContact.notes ? 'text-gray-800 dark:text-gray-200' : 'text-blue-500 hover:text-blue-600'" x-text="activeContact.notes || '{{ __('Add notes...') }}'"></span>
                        </template>
                    </div>
                </div>

                {{-- Tags --}}
                <div class="px-5 py-4 border-t border-gray-100 dark:border-white/10">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Tags') }}</span>
                        <span class="text-xs text-blue-500 font-medium cursor-pointer hover:text-blue-600">{{ __('Edit') }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-if="activeConversation && activeConversation.tags">
                            <template x-for="tag in activeConversation.tags.filter(t => !['high','medium','low'].includes(t))" :key="tag">
                                <span x-text="tag" class="text-[11px] font-medium px-2.5 py-1 rounded-full bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10"></span>
                            </template>
                        </template>
                        <template x-if="!activeConversation?.tags || activeConversation.tags.filter(t => !['high','medium','low'].includes(t)).length === 0">
                            <span class="text-xs text-gray-400 italic">{{ __('No tags') }}</span>
                        </template>
                    </div>
                </div>

                {{-- Lists / Segments --}}
                <div class="px-5 py-4 border-t border-gray-100 dark:border-white/10">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Lists') }}</span>
                        <button @click="showAddToList = !showAddToList" class="text-xs text-blue-500 font-medium hover:text-blue-600">+ {{ __('Add to List') }}</button>
                    </div>

                    {{-- Existing subscriptions --}}
                    <template x-if="activeContact.subscribed_lists && activeContact.subscribed_lists.length > 0">
                        <div class="space-y-1.5 mb-2">
                            <template x-for="sl in activeContact.subscribed_lists" :key="sl.id">
                                <div class="flex items-center gap-2 px-2.5 py-1.5 bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-500/20 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500 flex-shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    <span x-text="sl.name" class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate"></span>
                                    <span x-text="sl.status" class="ml-auto text-[10px] text-green-600 dark:text-green-400 capitalize"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!activeContact.subscribed_lists || activeContact.subscribed_lists.length === 0">
                        <p class="text-xs text-gray-400 italic mb-2" x-show="!showAddToList">{{ __('Not subscribed to any list') }}</p>
                    </template>

                    {{-- Add to list form --}}
                    <div x-show="showAddToList" x-cloak x-transition class="flex items-center gap-1.5 mt-2">
                        <select x-model="addToListId" class="flex-1 text-xs border border-gray-200 dark:border-white/10 rounded-lg px-2 py-1.5 bg-white dark:bg-transparent text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-400">
                            <option value="">{{ __('Select list...') }}</option>
                            <template x-for="list in emailLists" :key="list.id">
                                <option :value="list.id" x-text="list.name"></option>
                            </template>
                        </select>
                        <button @click="addContactToList()" :disabled="!addToListId" class="px-2.5 py-1.5 bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white text-xs rounded-lg transition-colors">{{ __('Add') }}</button>
                    </div>
                </div>

                {{-- Sequences (only when Cold Email addon is active) --}}
                <template x-if="coldEmailActive">
                    <div class="px-5 py-4 border-t border-gray-100 dark:border-white/10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-400"><path d="M16 2v5h5"/><path d="M21 6v6.5c0 .8-.7 1.5-1.5 1.5h-7c-.8 0-1.5-.7-1.5-1.5v-9c0-.8.7-1.5 1.5-1.5H17l4 4z"/><path d="M7 8v8.8c0 .3.2.6.4.8.2.2.5.4.8.4H15"/><path d="M3 12v8.8c0 .3.2.6.4.8.2.2.5.4.8.4H11"/></svg>
                                {{ __('Sequences') }}
                            </span>
                            <button @click="showAddToSequence = !showAddToSequence" class="text-xs text-blue-500 font-medium hover:text-blue-600">+ {{ __('Add to Sequence') }}</button>
                        </div>

                        {{-- Added sequences --}}
                        <template x-if="addedSequences.length > 0">
                            <div class="space-y-1.5 mb-2">
                                <template x-for="seq in addedSequences" :key="seq.id">
                                    <div class="flex items-center gap-2 px-2.5 py-1.5 bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-500/20 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-500 flex-shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        <span x-text="seq.name" class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate"></span>
                                        <span class="ml-auto text-[10px] text-orange-600 dark:text-orange-400">{{ __('Pending') }}</span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="addedSequences.length === 0 && !showAddToSequence">
                            <p class="text-xs text-gray-400 italic mb-2">{{ __('Not in any sequence') }}</p>
                        </template>

                        {{-- Add to sequence form --}}
                        <div x-show="showAddToSequence" x-cloak x-transition class="flex items-center gap-1.5 mt-2">
                            <select x-model="addToSequenceId" class="flex-1 text-xs border border-gray-200 dark:border-white/10 rounded-lg px-2 py-1.5 bg-white dark:bg-transparent text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-orange-400">
                                <option value="">{{ __('Select campaign...') }}</option>
                                <template x-for="camp in outreachCampaigns" :key="camp.id">
                                    <option :value="camp.id" x-text="camp.name"></option>
                                </template>
                            </select>
                            <button @click="addContactToSequence()" :disabled="!addToSequenceId" class="px-2.5 py-1.5 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white text-xs rounded-lg transition-colors">{{ __('Add') }}</button>
                        </div>
                    </div>
                </template>

                {{-- Assignee --}}
                <div class="px-5 py-3 border-t border-gray-100 dark:border-white/10">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">{{ __('Assignee') }}</span>
                        <span x-text="activeConversation?.assigned_name || '{{ __('Unassigned') }}'" class="text-xs font-medium" :class="activeConversation?.assigned_name ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400'"></span>
                    </div>
                </div>

                {{-- Chat on --}}
                <div class="px-5 py-3 border-t border-gray-100 dark:border-white/10">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">{{ __('Chat on') }}</span>
                        <span class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ __('Email') }}</span>
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div class="px-5 py-4 border-t border-gray-100 dark:border-white/10">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-600 mb-3">{{ __('Recent Activity') }}</p>
                    <template x-if="activeContact.activities && activeContact.activities.length > 0">
                        <div class="space-y-2.5">
                            <template x-for="activity in activeContact.activities" :key="activity.time + activity.type">
                                <div class="flex items-start gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 flex-shrink-0 mt-1.5"></div>
                                    <div>
                                        <p x-text="activity.description" class="text-xs text-gray-700 dark:text-gray-300"></p>
                                        <p x-text="activity.time" class="text-[10px] text-gray-400 mt-0.5"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!activeContact.activities || activeContact.activities.length === 0">
                        <p class="text-xs text-gray-400 italic">{{ __('No recent activity.') }}</p>
                    </template>
                </div>
            </div>
        </template>
    {{-- ═══════════════════════════════════════════════════════ COMPOSE MODAL ══ --}}
<template x-teleport="body">
<div x-show="openComposeModal" class="fixed inset-0 z-50" x-cloak>
    <div
        x-show="openComposeModal && !composeMinimized"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-black/30 backdrop-blur-[1px]"
        @click="openComposeModal = false; composeExpanded = false; resetCompose()"
    ></div>

    <div class="absolute inset-0 flex items-center justify-center p-6">
        <div
            x-show="openComposeModal && !composeMinimized"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-[0.98] translate-y-3"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-[0.98] translate-y-3"
            :class="composeExpanded ? 'w-[96vw] h-[88vh] max-w-[1180px] max-h-[860px]' : 'w-[72vw] h-[68vh] min-w-[60vw] min-h-[60vh] max-w-[980px] max-h-[760px]'"
            class="relative z-10 flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_25px_80px_rgba(15,23,42,0.18)] dark:border-white/10 dark:bg-[#1a1a1a]"
        >
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200 dark:border-white/10 select-none">
                <span class="text-[18px] font-semibold text-gray-900 dark:text-white">{{ __('New Message') }}</span>
                <div class="flex items-center gap-3 text-gray-400">
                    <button @click="composeMinimized = true" class="rounded-md p-1 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-white transition-colors" title="{{ __('Minimize') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6"/></svg>
                    </button>
                    <button @click="composeExpanded = !composeExpanded" class="rounded-md p-1 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-white transition-colors" title="{{ __('Expand') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                    </button>
                    <button @click="openComposeModal = false; composeMinimized = false; composeExpanded = false; resetCompose()" class="rounded-md p-1 hover:bg-gray-100 hover:text-red-500 dark:hover:bg-white/10 transition-colors" title="{{ __('Close') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex items-start gap-4 px-6 py-4 border-b border-gray-200 dark:border-white/10">
                <span class="w-16 flex-shrink-0 text-[15px] text-gray-400 pt-1">{{ __('From:') }}</span>
                <div class="relative flex min-w-0 flex-1 items-center gap-3" x-data="{ open: false }" @click.outside="open = false">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#9333EA] text-sm font-semibold text-white flex-shrink-0">
                        <span x-text="composeSelectedFromAccount ? (composeSelectedFromAccount.name || composeSelectedFromAccount.email || '').slice(0,1).toUpperCase() : '—'"></span>
                    </div>
                    <button type="button" @click="open = !open" class="flex min-w-0 items-center gap-2 text-[15px] text-gray-700 transition-colors hover:text-[#7c3aed] dark:text-gray-200">
                        <span class="truncate" x-text="composeSelectedFromAccount ? `${composeSelectedFromAccount.name || composeSelectedFromAccount.email} <${composeSelectedFromAccount.email}>` : '{{ __('No inbox connected') }}'"></span>
                        <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak x-transition class="absolute left-0 top-full z-20 mt-2 w-full max-w-xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-white/10 dark:bg-[#1a1a1a]">
                        <div class="max-h-72 overflow-y-auto py-2">
                            <template x-for="account in accounts" :key="account.id">
                                <button type="button" @click="selectComposeFromAccount(account.id); open = false" class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#9333EA]/10 text-sm font-semibold text-[#7c3aed] flex-shrink-0" x-text="(account.name || account.email || '').slice(0,1).toUpperCase()"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white" x-text="account.name || account.email"></div>
                                        <div class="truncate text-xs text-gray-500 dark:text-gray-400" x-text="account.email"></div>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                            <template x-if="!accounts.length">
                                <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ __('No inboxes connected') }}</div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-4 px-6 py-3 border-b border-gray-200 dark:border-white/10">
                <span class="w-16 flex-shrink-0 text-[15px] text-gray-400">{{ __('To:') }}</span>
                <div class="flex-1 bg-white dark:bg-transparent">
                    <div class="flex flex-wrap gap-2 min-w-0">
                        <template x-for="(addr, i) in composeTo" :key="i">
                            <span class="inline-flex items-center gap-1 rounded-full border border-[#9333EA]/15 bg-[#F3E8FF] px-2.5 py-1 text-xs font-medium text-[#7C3AED]">
                                <span x-text="addr"></span>
                                <button @click="composeTo.splice(i,1)" class="leading-none transition-colors hover:text-red-500">&times;</button>
                            </span>
                        </template>
                        <input
                            x-model="composeToInput"
                            @keydown.enter.prevent="addRecipient('to')"
                            @keydown.tab.prevent="addRecipient('to')"
                            @keydown.comma.prevent="addRecipient('to')"
                            type="text"
                            placeholder="{{ __('Add recipient...') }}"
                            class="min-w-[30px] flex-1 bg-transparent p-0 text-[15px] text-gray-700 border-0 outline-none ring-0 focus:outline-none focus:ring-0 focus:border-0 placeholder:text-gray-400 dark:text-gray-200 dark:placeholder:text-gray-500"
                            style="box-shadow: none !important;"
                        >
                    </div>
                </div>
                <div class="flex items-center gap-4 text-[15px]">
                    <button @click="showCc = !showCc" :class="showCc ? 'text-[#7c3aed]' : 'text-gray-400 hover:text-gray-600'" class="transition-colors">Cc</button>
                    <button @click="showBcc = !showBcc" :class="showBcc ? 'text-[#7c3aed]' : 'text-gray-400 hover:text-gray-600'" class="transition-colors">Bcc</button>
                </div>
            </div>

            <div x-show="showCc" class="flex items-start gap-4 px-6 py-3">
                <span class="w-16 flex-shrink-0 text-[15px] text-gray-400">Cc:</span>
                <div class="flex-1 bg-white dark:bg-transparent">
                    <div class="flex flex-wrap gap-2 min-w-0">
                        <template x-for="(addr, i) in composeCc" :key="i">
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                <span x-text="addr"></span>
                                <button @click="composeCc.splice(i,1)" class="leading-none transition-colors hover:text-red-500">&times;</button>
                            </span>
                        </template>
                        <input x-model="composeCcInput" @keydown.enter.prevent="addRecipient('cc')" @keydown.tab.prevent="addRecipient('cc')" @keydown.comma.prevent="addRecipient('cc')" type="text" placeholder="{{ __('Add Cc...') }}" class="p-0 min-w-[160px] flex-1 bg-transparent text-[15px] text-gray-700 outline-none placeholder:text-gray-400 dark:text-gray-200 dark:placeholder:text-gray-500" style="box-shadow: none !important; border: none;">
                    </div>
                </div>
            </div>

            <div x-show="showBcc" class="flex items-start gap-4 px-6 py-3">
                <span class="w-16 flex-shrink-0 text-[15px] text-gray-400">Bcc:</span>
                <div class="flex-1 bg-white dark:bg-transparent">
                    <div class="flex flex-wrap gap-2 min-w-0">
                        <template x-for="(addr, i) in composeBcc" :key="i">
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                <span x-text="addr"></span>
                                <button @click="composeBcc.splice(i,1)" class="leading-none transition-colors hover:text-red-500">&times;</button>
                            </span>
                        </template>
                        <input x-model="composeBccInput" @keydown.enter.prevent="addRecipient('bcc')" @keydown.tab.prevent="addRecipient('bcc')" @keydown.comma.prevent="addRecipient('bcc')" type="text" placeholder="{{ __('Add Bcc...') }}" class="p-0 min-w-[160px] flex-1 bg-transparent text-[15px] text-gray-700 outline-none placeholder:text-gray-400 dark:text-gray-200 dark:placeholder:text-gray-500" style="box-shadow: none !important;border:none;">
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 dark:border-white/10 px-6 py-4">
                <input
                    x-model="composeSubject"
                    type="text"
                    placeholder="{{ __('Subject') }}"
                    class="w-full border-0 bg-transparent p-0 text-[15px] text-gray-700 outline-none ring-0 focus:outline-none focus:ring-0 focus:border-0 focus:shadow-none placeholder:text-gray-500 dark:text-white dark:placeholder:text-gray-500"
                    style="box-shadow: none !important;"
                >
            </div>

            {{-- Formatting toolbar (top of message area) --}}
            <div class="flex items-center gap-1 border-b border-gray-200 px-4 py-2 dark:border-white/10">
                <select @change="execComposeFormat('formatBlock', $event.target.value); $event.target.value = ''"
                    class="text-xs text-gray-500 dark:text-gray-400 bg-transparent border-0 focus:ring-0 py-1 pl-1 pr-5 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200">
                    <option value="" disabled selected>{{ __('Normal') }}</option>
                    <option value="h1">H1</option>
                    <option value="h2">H2</option>
                    <option value="h3">H3</option>
                    <option value="p">{{ __('Paragraph') }}</option>
                </select>
                <div class="mx-1.5 h-5 w-px bg-gray-200 dark:bg-white/10"></div>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="execComposeFormat('bold')" class="rounded-md px-2 py-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Bold') }}"><strong>B</strong></button>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="execComposeFormat('italic')" class="rounded-md px-2 py-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Italic') }}"><em>I</em></button>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="execComposeFormat('underline')" class="rounded-md px-2 py-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Underline') }}"><span class="underline">U</span></button>
                <div class="mx-1.5 h-5 w-px bg-gray-200 dark:bg-white/10"></div>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="execComposeFormat('justifyLeft')" class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Align Left') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h12M3.75 17.25h16.5"/></svg>
                </button>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="execComposeFormat('justifyCenter')" class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Align Center') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6.75 12h10.5M3.75 17.25h16.5"/></svg>
                </button>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="execComposeFormat('justifyRight')" class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Align Right') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M8.25 12h12M3.75 17.25h16.5"/></svg>
                </button>
                <div class="mx-1.5 h-5 w-px bg-gray-200 dark:bg-white/10"></div>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="execComposeFormat('insertUnorderedList')" class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Bulleted List') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                </button>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="execComposeFormat('insertOrderedList')" class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Numbered List') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75V4.5m0 2.25H2.25m1.5 0H5.25M3 12h1.5a.75.75 0 0 0 0-1.5h-.75A.75.75 0 0 0 3 11.25v.75Zm0 0v.75a.75.75 0 0 0 .75.75h.75M3 18h1.5a.75.75 0 0 0 0-1.5h-.75A.75.75 0 0 0 3 17.25v.75Zm0 0v.75a.75.75 0 0 0 .75.75h.75"/></svg>
                </button>
                <div class="mx-1.5 h-5 w-px bg-gray-200 dark:bg-white/10"></div>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="$refs.composeFileInput.click()" class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Attach File') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                </button>
                <button type="button" @mousedown.prevent="saveComposeSelection()" @click="insertComposeLink()" class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white" title="{{ __('Insert Link') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 0 0-5.656 0l-2 2a4 4 0 0 0 5.656 5.656l.5-.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.172 13.828a4 4 0 0 0 5.656 0l2-2a4 4 0 0 0-5.656-5.656l-.5.5"/></svg>
                </button>
                <input type="file" x-ref="composeFileInput" @change="handleComposeFiles($event)" multiple class="hidden">
            </div>

            {{-- Attachment previews --}}
            <template x-if="composeAttachments.length > 0">
                <div class="flex flex-wrap gap-2 border-b border-gray-200 px-6 py-3 dark:border-white/10">
                    <template x-for="(file, idx) in composeAttachments" :key="idx">
                        <div class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                            <span class="truncate max-w-[160px]" x-text="file.name"></span>
                            <span class="text-[10px] text-gray-400" x-text="formatFileSize(file.size)"></span>
                            <button type="button" @click="removeComposeAttachment(idx)" class="leading-none text-gray-400 transition-colors hover:text-red-500">&times;</button>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Message body --}}
            <div class="flex-1 overflow-hidden">
                <div
                    x-ref="composeEditor"
                    contenteditable="true"
                    @input="syncComposeBody()"
                    @mouseup="saveComposeSelection()"
                    @keyup="saveComposeSelection()"
                    @focus="saveComposeSelection()"
                    @blur="syncComposeBody()"
                    @keydown.meta.enter="sendCompose()"
                    @keydown.ctrl.enter="sendCompose()"
                    data-placeholder="{{ __('Write your message...') }}"
                    class="compose-editor w-full px-6 py-4 text-[15px] leading-relaxed text-gray-700 outline-none ring-0 focus:outline-none focus:ring-0 focus:border-0 placeholder:text-gray-400 dark:bg-transparent dark:text-gray-200 dark:placeholder:text-gray-500"
                    style="min-height: 200px; box-shadow: none !important;"
                ></div>
            </div>

            {{-- Action bar --}}
            <div class="flex items-center justify-between border-t border-gray-200 px-6 py-3 dark:border-white/10">
                <div class="flex items-center gap-5">
                    <button
                        type="button"
                        x-ref="composeTplBtn"
                        data-templates-toggle
                        @click="
                            if (!showTemplatePopup) {
                                const r = $refs.composeTplBtn.getBoundingClientRect();
                                tplTop = r.top - 8;
                                tplLeft = r.left;
                                loadReplyTemplates();
                            }
                            showTemplatePopup = !showTemplatePopup;
                        "
                        class="flex items-center gap-2 text-[14px] text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                    >
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        {{ __('Templates') }}
                    </button>
                </div>
                <div class="flex items-center gap-4">
                    <span class="hidden sm:inline text-[13px] text-gray-400">&#8984;+{{ __('Enter to send') }}</span>
                    <button @click="sendCompose()" :disabled="!composeCanSend || sendingCompose || !composeSelectedFromAccount" class="rounded-lg bg-[#A78BFA] px-6 py-2 text-[14px] font-medium text-white shadow-sm transition-colors hover:bg-[#8B5CF6] disabled:cursor-not-allowed disabled:bg-[#D8B4FE] disabled:text-white/70 disabled:opacity-70">
                        <span x-show="!sendingCompose">{{ __('Send') }}</span>
                        <span x-show="sendingCompose">{{ __('Sending...') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

 

{{-- Minimized pill --}}
<div
    x-show="openComposeModal && composeMinimized"
    x-transition
    class="fixed bottom-0 right-6 z-50 flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-white/10 rounded-t-xl shadow-lg cursor-pointer select-none"
    @click="composeMinimized = false"
>
    <svg class="w-3.5 h-3.5 text-[#1E5FEA]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
    <span class="text-sm font-medium text-gray-700 dark:text-gray-200" x-text="composeSubject || '{{ __('New Message') }}'"></span>
    <button @click.stop="openComposeModal = false; composeMinimized = false; resetCompose()" class="ml-2 text-gray-400 hover:text-red-500 transition-colors leading-none">&times;</button>
</div>
</template>
</div>

@include('customer.unibox._add-inbox-drawer')

@push('scripts')
<script>
function uniboxApp() {
    return {
        activeFolder: '{{ $folder }}',
        conversations: [],
        activeConversation: null,
        activeContact: null,
        threadMessages: [],
        expandedMessageIds: [],
        expandedQuoteIds: [],
        threadNotes: [],
        starredOpen: true,
        messagesOpen: true,
        totalCount: 0,
        loadingConversations: false,
        loadingThread: false,
        sendingReply: false,
        replyBody: '',
        replyAttachments: [],
        newNote: '',
        showNoteInput: false,
        search: '{{ $search }}',
        sort: 'newest',
        page: 1,
        hasMore: false,
        openComposeModal: false,
        composeMinimized: false,
        composeExpanded: false,
        showInboxPicker: false,
        addInboxDrawerOpen: false,
        addInboxProvider: 'gmail',
        addInboxGmailName: '',
        activeAccountId: @js($accountId ?? null),
        navLoading: false,
        hoveredConversationId: null,
        openMenuId: null,
        openSubmenu: null,
        accounts: @json($accounts->map(fn($a) => ['id' => $a->id, 'name' => $a->name ?: $a->email, 'email' => $a->email])),
        customFolders: @json($folders->map(fn($f) => ['id' => $f->id, 'name' => $f->name, 'color' => $f->color])),
        customTags: @json($tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color])),
        emailLists: @json($emailLists),
        coldEmailActive: @json($coldEmailActive),
        outreachCampaigns: @json($outreachCampaigns),
        editingContactField: null,
        editingContactValue: '',
        showAddToList: false,
        addToListId: '',
        showAddToSequence: false,
        addToSequenceId: '',
        addedSequences: [],
        replyTemplates: [],
        replyTemplatesLoaded: false,
        showTemplatePopup: false,
        tplTop: 0,
        tplLeft: 0,
        templateSearch: '',
        showNewTemplateModal: false,
        newTemplate: { name: '', folder: '', subject: '', body: '', shared: false },
        newFolderInput: '',
        showNewFolderInput: false,
        showNewFolder: false,
        newFolderName: '',
        showNewTag: false,
        newTagName: '',
        newTagColor: '#7c3aed',
        editingFolderId: null,
        editingFolderName: '',
        editingFolderColor: '#7c3aed',
        editingTagId: null,
        editingTagName: '',
        editingTagColor: '#7c3aed',
        threadMoreOpen: false,
        threadMoreSubmenu: null,
        listMoreOpen: false,
        listMoreSubmenu: null,
        selectedConvIds: [],
        get allListedSelected() {
            const ids = this.conversations.map(c => c.id);
            return ids.length > 0 && ids.every(id => this.selectedConvIds.includes(id));
        },
        toggleSelectAll() {
            if (this.allListedSelected) {
                this.selectedConvIds = [];
            } else {
                this.selectedConvIds = this.conversations.map(c => c.id);
            }
        },
        toggleSelectConv(conv) {
            const i = this.selectedConvIds.indexOf(conv.id);
            if (i === -1) this.selectedConvIds.push(conv.id);
            else this.selectedConvIds.splice(i, 1);
        },
        isConvSelected(conv) {
            return this.selectedConvIds.includes(conv.id);
        },
        activeTag: null,
        activePriority: null,
        activeCategory: null,
        syncing: false,
        categories: [
            { key: 'primary',    label: '{{ __("Primary") }}',    icon: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>' },
            { key: 'promotions',label: '{{ __("Promotions") }}',icon: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.1 18.1 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>' },
            { key: 'updates',   label: '{{ __("Updates") }}',   icon: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>' },
            { key: 'social',    label: '{{ __("Social") }}',    icon: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>' },
        ],
        syncComposeBody() {
            this.composeBody = this.$refs.composeEditor?.innerHTML || '';
        },

        execComposeFormat(command, value = null) {
            this.restoreComposeSelection();
            this.$refs.composeEditor?.focus();
            const execValue = command === 'formatBlock' && value
                ? `<${String(value).replace(/[<>]/g, '')}>`
                : value;
            document.execCommand(command, false, execValue);
            this.syncComposeBody();
            this.saveComposeSelection();
        },

        saveComposeSelection() {
            const editor = this.$refs.composeEditor;
            const sel = window.getSelection ? window.getSelection() : null;
            if (!editor || !sel || sel.rangeCount === 0) return;
            const range = sel.getRangeAt(0);
            if (!editor.contains(range.commonAncestorContainer)) return;
            this.composeSelectionRange = range.cloneRange();
        },

        restoreComposeSelection() {
            const editor = this.$refs.composeEditor;
            const range = this.composeSelectionRange;
            if (!editor || !range) return false;
            const sel = window.getSelection ? window.getSelection() : null;
            if (!sel) return false;
            sel.removeAllRanges();
            sel.addRange(range);
            editor.focus();
            return true;
        },

        insertComposeLink() {
            const url = window.prompt('{{ __('Enter URL') }}', 'https://');
            if (url) this.execComposeFormat('createLink', url);
        },

        selectComposeFromAccount(accountId) {
            this.composeSelectedFromAccountId = accountId;
        },
        composeTo: [],
        composeToInput: '',
        composeCc: [],
        composeCcInput: '',
        composeBcc: [],
        composeBccInput: '',
        showCc: false,
        showBcc: false,
        sendingCompose: false,
        composeSelectionRange: null,
        composeSubject: '',
        composeBody: '',
        composeAttachments: [],
        composeSelectedFromAccountId: @js($accountId ?? null),
        get composeCanSend() {
            const bodyText = (this.composeBody || '').replace(/<[^>]*>/g, '').trim();
            const toInput = (this.composeToInput || '').trim();
            const toLooksValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(toInput);
            return (this.composeTo.length > 0 || toLooksValid) && !!bodyText;
        },
        get composeSelectedFromAccount() {
            if (this.composeSelectedFromAccountId !== null) {
                const match = this.accounts.find(a => a.id == this.composeSelectedFromAccountId);
                if (match) return match;
            }
            return this.accounts[0] || null;
        },
        counts: @json($counts),

        navItems: [
            { folder: 'inbox', label: '{{ __("Inbox") }}', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-inbox-icon lucide-inbox"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>' },
            { folder: 'assigned_to_me', label: '{{ __("Assigned to Me") }}', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-handshake-icon lucide-handshake"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/></svg>' },
            { folder: 'unassigned', label: '{{ __("Unassigned") }}', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail-question-mark-icon lucide-mail-question-mark"><path d="M22 10.5V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h12.5"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="M18 15.28c.2-.4.5-.8.9-1a2.1 2.1 0 0 1 2.6.4c.3.4.5.8.5 1.3 0 1.3-2 2-2 2"/><path d="M20 22v.01"/></svg>' },
            { folder: 'sent', label: '{{ __("Sent") }}', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send-horizontal-icon lucide-send-horizontal"><path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"/><path d="M6 12h16"/></svg>' },
            { folder: 'drafts', label: '{{ __("Drafts") }}', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-line-icon lucide-pencil-line"><path d="M13 21h8"/><path d="m15 5 4 4"/><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>' },
            { folder: 'snoozed', label: '{{ __("Snoozed") }}', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-clock-icon lucide-folder-clock"><path d="M16 14v2.2l1.6 1"/><path d="M7 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2"/><circle cx="16" cy="16" r="6"/></svg>' },
            { folder: 'archive', label: '{{ __("Archive") }}', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-archive-icon lucide-archive"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>' },
            { folder: 'spam', label: '{{ __("Spam") }}', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert-icon lucide-circle-alert"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>' },
            { folder: 'trash', label: '{{ __("Trash") }}', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2-icon lucide-trash-2"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>' },
        ],

        get activeFolderLabel() {
            if (this.activePriority) {
                const labels = { high: '{{ __("High Priority") }}', medium: '{{ __("Medium Priority") }}', low: '{{ __("Low Priority") }}' };
                return labels[this.activePriority] || this.activePriority;
            }
            if (this.activeTag) return '#' + this.activeTag;
            if (typeof this.activeFolder === 'string' && this.activeFolder.startsWith('custom_')) {
                const id = parseInt(this.activeFolder.slice(7), 10);
                const f = this.customFolders.find(x => x.id === id);
                return f ? f.name : this.activeFolder;
            }
            const item = this.navItems.find(i => i.folder === this.activeFolder);
            return item ? item.label : this.activeFolder;
        },

        get activeAccountLabel() {
            if (this.activeAccountId === null) return '{{ __("All Inbox") }}';
            const acc = this.accounts.find(a => a.id == this.activeAccountId);
            return acc ? acc.name : '{{ __("All Inbox") }}';
        },

        init() {
            // Show skeleton on the left nav for a brief moment after switching inbox accounts.
            try {
                if (sessionStorage.getItem('unibox_switching') === '1') {
                    this.navLoading = true;
                    sessionStorage.removeItem('unibox_switching');
                    setTimeout(() => { this.navLoading = false; }, 2500);
                }
            } catch (_) {}

            this.loadConversations(true);
            this.$watch('activeFolder', () => this.loadConversations(true));

            // Poll for new messages every 5 seconds (recursive setTimeout avoids overlap)
            const runPoll = async () => {
                try {
                    await this.pollNewMessages();
                    await this.loadConversations(false);
                } catch (e) {
                    // Silent fail — keep polling so temporary network/API errors
                    // don't permanently stop the auto-refresh loop.
                }
                this._pollTimer = setTimeout(runPoll, 5000);
            };
            this._pollTimer = setTimeout(runPoll, 5000);
        },

        destroy() {
            if (this._pollTimer) clearTimeout(this._pollTimer);
        },

        async pollNewMessages() {
            if (!this.activeConversation || this.loadingThread) return;
            try {
                const resp = await fetch(`{{ url('customer/unibox') }}/${this.activeConversation.id}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await resp.json();
                const prevLastId = this.threadMessages.length ? this.threadMessages[this.threadMessages.length - 1].id : null;
                const newLastId = data.messages.length ? data.messages[data.messages.length - 1].id : null;
                const hadNew = prevLastId !== newLastId;
                // Always replace with API-ordered list to maintain correct chronology
                this.threadMessages = data.messages;
                if (hadNew) {
                    this.$nextTick(() => {
                        const c = document.getElementById('messages-container');
                        if (c) c.scrollTop = c.scrollHeight;
                    });
                }
                this.threadNotes = data.notes;
                // Update conversation metadata
                if (data.conversation) {
                    this.activeConversation = { ...this.activeConversation, ...data.conversation };
                    const conv = this.conversations.find(c => c.id === this.activeConversation.id);
                    if (conv) Object.assign(conv, data.conversation);
                }
            } catch (e) {
                // Silent fail on poll
            }
        },

        selectFolder(folder) {
            this.activeFolder = folder;
            this.activeTag = null;
            this.activePriority = null;
            this.activeConversation = null;
            this.activeContact = null;
        },

        selectCategory(key) {
            this.activeCategory = this.activeCategory === key ? null : key;
            this.loadConversations(true);
        },

        connectGmailOAuth() {
            const name = (this.addInboxGmailName || '').trim();
            if (!name) {
                const input = document.getElementById('unibox-gmail-account-name');
                if (input) input.focus();
                alert('{{ __('Please enter an account name first.') }}');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('customer.oauth.gmail.redirect') }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const nameField = document.createElement('input');
            nameField.type = 'hidden';
            nameField.name = 'name';
            nameField.value = name;
            form.appendChild(nameField);

            document.body.appendChild(form);
            form.submit();
        },

        async renameAccount(id, currentName) {
            const name = window.prompt('{{ __('Rename inbox') }}', currentName || '');
            if (name === null) return null;
            const trimmed = name.trim();
            if (!trimmed || trimmed === currentName) return null;
            try {
                const res = await fetch(`{{ url('customer/unibox/accounts') }}/${id}/rename`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name: trimmed }),
                });
                if (!res.ok) return null;
                const data = await res.json();
                return data.account?.name || trimmed;
            } catch (e) {
                return null;
            }
        },

        isMessageExpanded(msg, idx) {
            // Always expand the last message; otherwise check the toggle list.
            if (idx === this.threadMessages.length - 1) return true;
            return this.expandedMessageIds.includes(msg.id);
        },

        toggleMessage(id) {
            const i = this.expandedMessageIds.indexOf(id);
            if (i === -1) this.expandedMessageIds.push(id);
            else this.expandedMessageIds.splice(i, 1);
        },

        // Split email body into new content and quoted history.
        // Returns { hasQuote: bool, visible: html, quote: html }
        splitQuotedText(html) {
            if (!html) return { hasQuote: false, visible: '', quote: '' };
            // 1. Gmail quote block
            const gmailMatch = html.match(/^(.*?)((?:<div[^>]*class="[^"]*gmail_quote[^"]*"|<blockquote[^>]*>)[\s\S]*)$/i);
            if (gmailMatch) return { hasQuote: true, visible: gmailMatch[1].trim(), quote: gmailMatch[2].trim() };
            // 2. Standard <blockquote>
            const bqMatch = html.match(/^(.*?)((?:<blockquote)[\s\S]*)$/i);
            if (bqMatch) return { hasQuote: true, visible: bqMatch[1].trim(), quote: bqMatch[2].trim() };
            // 3. "On ... wrote:" pattern (common in Outlook, Apple Mail, etc.)
            const wroteMatch = html.match(/^(.*?)(<(?:div|p|br)[^>]*>\s*On\s+.{10,200}?wrote:\s*<[^>]*>[\s\S]*)$/i);
            if (wroteMatch) return { hasQuote: true, visible: wroteMatch[1].trim(), quote: wroteMatch[2].trim() };
            // 4. "From:" line at start of quoted block
            const fromMatch = html.match(/^(.*?)(<(?:div|p)[^>]*>\s*(-{2,}|_{2,})\s*Forwarded [Mm]essage[\s\S]*)$/i);
            if (fromMatch) return { hasQuote: true, visible: fromMatch[1].trim(), quote: fromMatch[2].trim() };
            return { hasQuote: false, visible: html, quote: '' };
        },

        toggleQuote(id) {
            const i = this.expandedQuoteIds.indexOf(id);
            if (i === -1) this.expandedQuoteIds.push(id);
            else this.expandedQuoteIds.splice(i, 1);
        },

        async syncNow() {
            if (this.syncing) return;
            this.syncing = true;
            try {
                const res = await fetch('{{ route("customer.unibox.sync") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) {
                    await this.loadConversations(true);
                }
            } finally {
                this.syncing = false;
            }
        },

        selectTag(tagName) {
            this.activeTag = this.activeTag === tagName ? null : tagName;
            this.activePriority = null;
            this.activeConversation = null;
            this.activeContact = null;
            this.loadConversations(true);
        },

        selectPriority(level) {
            this.activePriority = this.activePriority === level ? null : level;
            this.activeTag = null;
            this.activeConversation = null;
            this.activeContact = null;
            this.loadConversations(true);
        },

        async createFolder() {
            const name = this.newFolderName.trim();
            if (!name) return;
            const res = await fetch('{{ route("customer.unibox.folders.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ name, color: '#7c3aed', account_id: this.activeAccountId }),
            });
            if (res.ok) {
                const data = await res.json();
                this.customFolders.push({ id: data.folder.id, name: data.folder.name, color: data.folder.color });
                this.newFolderName = '';
                this.showNewFolder = false;
            }
        },

        async deleteFolder(f) {
            if (!confirm('{{ __('Delete this folder?') }}')) return;
            const res = await fetch(`{{ url('customer/unibox/folders') }}/${f.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.ok) {
                this.customFolders = this.customFolders.filter(x => x.id !== f.id);
            }
        },

        async createTag() {
            const name = this.newTagName.trim();
            if (!name) return;
            const res = await fetch('{{ route("customer.unibox.tags.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ name, color: this.newTagColor, account_id: this.activeAccountId }),
            });
            if (res.ok) {
                const data = await res.json();
                const idx = this.customTags.findIndex(t => t.id === data.tag.id);
                const entry = { id: data.tag.id, name: data.tag.name, color: data.tag.color };
                if (idx >= 0) this.customTags.splice(idx, 1, entry); else this.customTags.push(entry);
                this.newTagName = '';
                this.showNewTag = false;
            }
        },

        async updateTagColor(t, color) {
            const res = await fetch(`{{ url('customer/unibox/tags') }}/${t.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ color }),
            });
            if (res.ok) {
                t.color = color;
            }
        },

        async deleteTag(t) {
            if (!confirm('{{ __('Delete this tag?') }}')) return;
            const res = await fetch(`{{ url('customer/unibox/tags') }}/${t.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.ok) {
                this.customTags = this.customTags.filter(x => x.id !== t.id);
            }
        },

        startEditFolder(f) {
            this.editingTagId = null;
            this.editingFolderId = f.id;
            this.editingFolderName = f.name;
            this.editingFolderColor = f.color || '#7c3aed';
            this.$nextTick(() => this.$refs.editFolderInput?.focus());
        },

        cancelEditFolder() {
            this.editingFolderId = null;
            this.editingFolderName = '';
        },

        async saveFolder(f) {
            const name = (this.editingFolderName || '').trim();
            const color = this.editingFolderColor || f.color || '#7c3aed';
            if (!name) return;
            if (name === f.name && color === f.color) {
                this.cancelEditFolder();
                return;
            }
            const res = await fetch(`{{ url('customer/unibox/folders') }}/${f.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ name, color }),
            });
            if (res.ok) {
                const data = await res.json();
                f.name = data.folder.name;
                f.color = data.folder.color;
                this.cancelEditFolder();
            }
        },

        async updateFolderColor(f, color) {
            const res = await fetch(`{{ url('customer/unibox/folders') }}/${f.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ name: f.name, color }),
            });
            if (res.ok) {
                f.color = color;
            }
        },

        startEditTag(t) {
            this.editingFolderId = null;
            this.editingTagId = t.id;
            this.editingTagName = t.name;
            this.editingTagColor = t.color || '#7c3aed';
            this.$nextTick(() => this.$refs.editTagInput?.focus());
        },

        cancelEditTag() {
            this.editingTagId = null;
            this.editingTagName = '';
        },

        async saveTag(t) {
            const name = (this.editingTagName || '').trim();
            const color = this.editingTagColor || t.color || '#7c3aed';
            if (!name) return;
            if (name === t.name && color === t.color) {
                this.cancelEditTag();
                return;
            }
            const oldName = t.name;
            try {
                const res = await fetch(`{{ url('customer/unibox/tags') }}/${t.id}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ name, color }),
                });
                if (!res.ok) {
                    let msg = `HTTP ${res.status}`;
                    try { const j = await res.json(); if (j?.message) msg = j.message; else if (j?.errors) msg = Object.values(j.errors).flat().join(' '); } catch (_) {}
                    alert('{{ __('Could not save tag') }}: ' + msg);
                    return;
                }
                const data = await res.json();
                t.name = data.tag.name;
                t.color = data.tag.color;
                // Reflect rename in any loaded conversations / active conversation.
                if (oldName !== t.name) {
                    if (Array.isArray(this.conversations)) {
                        this.conversations.forEach(c => {
                            if (Array.isArray(c.tags)) {
                                c.tags = c.tags.map(x => x === oldName ? t.name : x);
                            }
                        });
                    }
                    if (this.activeConversation && Array.isArray(this.activeConversation.tags)) {
                        this.activeConversation.tags = this.activeConversation.tags.map(x => x === oldName ? t.name : x);
                    }
                    if (this.activeTag === oldName) this.activeTag = t.name;
                }
                this.cancelEditTag();
            } catch (e) {
                alert('{{ __('Could not save tag') }}: ' + (e?.message || e));
            }
        },

        async toggleListConversationFolder(conv, folder) {
            const res = await fetch(`{{ url('customer/unibox') }}/${conv.id}/toggle-folder`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ folder_id: folder.id }),
            });
            if (res.ok) {
                const data = await res.json();
                conv.folder_ids = data.folder_ids;
                if (this.activeConversation && this.activeConversation.id === conv.id) {
                    this.activeConversation.folder_ids = data.folder_ids;
                }
            }
        },

        async toggleListConversationTag(conv, tag) {
            const res = await fetch(`{{ url('customer/unibox') }}/${conv.id}/toggle-tag`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ tag: tag.name }),
            });
            if (res.ok) {
                const data = await res.json();
                conv.tags = data.tags;
                if (this.activeConversation && this.activeConversation.id === conv.id) {
                    this.activeConversation.tags = data.tags;
                }
            }
        },

        _toast(msg, type = 'success') {
            if (window.mailpurseToast) {
                window.mailpurseToast({ type, title: type === 'success' ? '{{ __("Done") }}' : '{{ __("Error") }}', message: msg });
            }
        },

        selectedConvsAllHaveFolder(folderId) {
            const selected = this.conversations.filter(c => this.selectedConvIds.includes(c.id));
            return selected.length > 0 && selected.every(c => (c.folder_ids || []).includes(folderId));
        },

        selectedConvsAllHaveTag(tagName) {
            const lower = tagName.toLowerCase();
            const selected = this.conversations.filter(c => this.selectedConvIds.includes(c.id));
            return selected.length > 0 && selected.every(c => (c.tags || []).map(x => (x||'').toLowerCase()).includes(lower));
        },

        async bulkToggleFolder(folder) {
            const ids = [...this.selectedConvIds];
            let successCount = 0;
            for (const id of ids) {
                try {
                    const res = await fetch(`{{ url('customer/unibox') }}/${id}/toggle-folder`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ folder_id: folder.id }),
                    });
                    if (res.ok) {
                        const data = await res.json();
                        const conv = this.conversations.find(c => c.id === id);
                        if (conv) conv.folder_ids = data.folder_ids;
                        if (this.activeConversation && this.activeConversation.id === id) {
                            this.activeConversation.folder_ids = data.folder_ids;
                        }
                        successCount++;
                    }
                } catch (e) { console.error(e); }
            }
            if (successCount) this._toast(`{{ __("Folder updated for") }} ${successCount} {{ __("conversation(s)") }}`);
        },

        async bulkToggleTag(tag) {
            const ids = [...this.selectedConvIds];
            let successCount = 0;
            for (const id of ids) {
                try {
                    const res = await fetch(`{{ url('customer/unibox') }}/${id}/toggle-tag`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ tag: tag.name }),
                    });
                    if (res.ok) {
                        const data = await res.json();
                        const conv = this.conversations.find(c => c.id === id);
                        if (conv) conv.tags = data.tags;
                        if (this.activeConversation && this.activeConversation.id === id) {
                            this.activeConversation.tags = data.tags;
                        }
                        successCount++;
                    }
                } catch (e) { console.error(e); }
            }
            if (successCount) this._toast(`{{ __("Tag updated for") }} ${successCount} {{ __("conversation(s)") }}`);
        },

        selectedConvsAllHavePriority(level) {
            const selected = this.conversations.filter(c => this.selectedConvIds.includes(c.id));
            return selected.length > 0 && selected.every(c => {
                const tags = (c.tags || []).map(t => (t||'').toLowerCase());
                return tags.includes(level.toLowerCase());
            });
        },

        async bulkSetPriority(level) {
            const ids = [...this.selectedConvIds];
            const priorities = ['high', 'medium', 'low'];
            let successCount = 0;
            for (const id of ids) {
                try {
                    const conv = this.conversations.find(c => c.id === id);
                    if (!conv) continue;
                    const currentTags = (conv.tags || []).map(t => (t||'').toLowerCase());
                    // Remove existing priority tags first
                    for (const p of priorities) {
                        if (currentTags.includes(p)) {
                            const res = await fetch(`{{ url('customer/unibox') }}/${id}/toggle-tag`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                                body: JSON.stringify({ tag: p }),
                            });
                            if (res.ok) {
                                const data = await res.json();
                                conv.tags = data.tags;
                                if (this.activeConversation && this.activeConversation.id === id) this.activeConversation.tags = data.tags;
                            }
                        }
                    }
                    // Add new priority tag if not null
                    if (level) {
                        const res = await fetch(`{{ url('customer/unibox') }}/${id}/toggle-tag`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ tag: level }),
                        });
                        if (res.ok) {
                            const data = await res.json();
                            conv.tags = data.tags;
                            if (this.activeConversation && this.activeConversation.id === id) this.activeConversation.tags = data.tags;
                        }
                    }
                    successCount++;
                } catch (e) { console.error(e); }
            }
            const labels = { high: '{{ __("High") }}', medium: '{{ __("Medium") }}', low: '{{ __("Low") }}' };
            if (successCount) this._toast(`{{ __("Priority set to") }} ${level ? labels[level] : '{{ __("None") }}'}: ${successCount} {{ __("conversation(s)") }}`);
        },

        async bulkMove(folder) {
            const ids = [...this.selectedConvIds];
            const labels = { snoozed: '{{ __("Snoozed") }}', archive: '{{ __("Archived") }}', spam: '{{ __("Marked as spam") }}', trash: '{{ __("Trashed") }}' };
            let successCount = 0;
            for (const id of ids) {
                try {
                    const res = await fetch(`{{ url('customer/unibox') }}/${id}`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ folder }),
                    });
                    if (res.ok) successCount++;
                } catch (e) { console.error(e); }
            }
            this.conversations = this.conversations.filter(c => !ids.includes(c.id));
            this.selectedConvIds = [];
            if (this.activeConversation && ids.includes(this.activeConversation.id)) {
                this.activeConversation = null;
                this.activeContact = null;
            }
            if (successCount) this._toast(`${labels[folder] || folder}: ${successCount} {{ __("conversation(s)") }}`);
        },

        async bulkMarkUnread() {
            const ids = [...this.selectedConvIds];
            let successCount = 0;
            for (const id of ids) {
                try {
                    const res = await fetch(`{{ url('customer/unibox') }}/${id}`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ is_read: 0 }),
                    });
                    if (res.ok) {
                        const conv = this.conversations.find(c => c.id === id);
                        if (conv) conv.is_read = false;
                        if (this.activeConversation && this.activeConversation.id === id) this.activeConversation.is_read = false;
                        successCount++;
                    }
                } catch (e) { console.error(e); }
            }
            this.selectedConvIds = [];
            if (successCount) this._toast(`{{ __("Marked as unread") }}: ${successCount} {{ __("conversation(s)") }}`);
        },

        async markConversationUnread(conv) {
            if (!conv) return;
            try {
                const res = await fetch(`{{ url('customer/unibox') }}/${conv.id}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ is_read: 0 }),
                });
                if (res.ok) {
                    conv.is_read = false;
                    const row = this.conversations.find(c => c.id === conv.id);
                    if (row) row.is_read = false;
                    this._toast('{{ __("Marked as unread") }}');
                }
            } catch (e) { console.error(e); }
        },

        async toggleConversationFolder(folder) {
            if (!this.activeConversation) return;
            const res = await fetch(`{{ url('customer/unibox') }}/${this.activeConversation.id}/toggle-folder`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ folder_id: folder.id }),
            });
            if (res.ok) {
                const data = await res.json();
                this.activeConversation.folder_ids = data.folder_ids;
                const conv = this.conversations.find(c => c.id === this.activeConversation.id);
                if (conv) conv.folder_ids = data.folder_ids;
                this._toast('{{ __("Folder updated") }}');
            }
        },

        async setConversationPriority(level) {
            if (!this.activeConversation) return;
            const priorities = ['high', 'medium', 'low'];
            const currentPriority = this.priorityOf(this.activeConversation);

            // Remove existing priority tag first
            if (currentPriority) {
                const res = await fetch(`{{ url('customer/unibox') }}/${this.activeConversation.id}/toggle-tag`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ tag: currentPriority }),
                });
                if (res.ok) {
                    const data = await res.json();
                    this.activeConversation.tags = data.tags;
                    const conv = this.conversations.find(c => c.id === this.activeConversation.id);
                    if (conv) conv.tags = data.tags;
                }
            }

            // Add new priority tag if not "none"
            if (level && level !== currentPriority) {
                const res = await fetch(`{{ url('customer/unibox') }}/${this.activeConversation.id}/toggle-tag`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ tag: level }),
                });
                if (res.ok) {
                    const data = await res.json();
                    this.activeConversation.tags = data.tags;
                    const conv = this.conversations.find(c => c.id === this.activeConversation.id);
                    if (conv) conv.tags = data.tags;
                }
            }

            this.threadMoreOpen = false;
            this.threadMoreSubmenu = null;
            this._toast('{{ __("Priority updated") }}');
        },

        async toggleConversationTag(tag) {
            if (!this.activeConversation) return;
            const res = await fetch(`{{ url('customer/unibox') }}/${this.activeConversation.id}/toggle-tag`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ tag: tag.name }),
            });
            if (res.ok) {
                const data = await res.json();
                this.activeConversation.tags = data.tags;
                const conv = this.conversations.find(c => c.id === this.activeConversation.id);
                if (conv) conv.tags = data.tags;
                this._toast('{{ __("Tag updated") }}');
            }
        },

        async moveConversation(conv, folder) {
            this.openMenuId = null;
            this.hoveredConversationId = null;
            const labels = { snoozed: '{{ __("Snoozed") }}', archive: '{{ __("Archived") }}', spam: '{{ __("Marked as spam") }}', trash: '{{ __("Trashed") }}' };
            try {
                await fetch(`{{ url('customer/unibox') }}/${conv.id}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ folder }),
                });
                this.conversations = this.conversations.filter(c => c.id !== conv.id);
                if (this.activeConversation && this.activeConversation.id === conv.id) {
                    this.activeConversation = null;
                    this.activeContact = null;
                }
                this._toast(labels[folder] || folder);
            } catch (e) {
                console.error(e);
                this._toast('{{ __("Action failed") }}', 'error');
            }
        },

        selectAccount(id) {
            const url = new URL(window.location.href);
            if (id) {
                url.searchParams.set('account_id', id);
            } else {
                url.searchParams.delete('account_id');
            }
            try { sessionStorage.setItem('unibox_switching', '1'); } catch (_) {}
            this.navLoading = true;
            window.location.href = url.toString();
        },

        get starredConversations() {
            return this.conversations.filter(c => c.is_starred);
        },

        get otherConversations() {
            return this.conversations.filter(c => !c.is_starred);
        },

        get paginationLabel() {
            const total = this.totalCount || this.conversations.length;
            if (total === 0) return '0';
            const perPage = 25;
            const count = this.conversations.length;
            if (count === 0) return `0 of ${total}`;
            const start = Math.min((this.page - 1) * perPage + 1, total);
            const end = Math.min(start + count - 1, total);
            return `${start}-${end} of ${total}`;
        },

        async toggleStar(conv) {
            const next = !conv.is_starred;
            conv.is_starred = next;
            try {
                await fetch(`{{ url('customer/unibox') }}/${conv.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ is_starred: next ? 1 : 0 }),
                });
            } catch (e) {
                conv.is_starred = !next; // revert
            }
        },

        // Deterministic color for an avatar from the contact name/email.
        avatarColor(conv) {
            const key = (conv.contact?.name || conv.contact?.email || '?').toLowerCase();
            const palette = [
                '#3b82f6, #8b5cf6', '#ec4899, #f97316', '#10b981, #14b8a6',
                '#f59e0b, #ef4444', '#6366f1, #06b6d4', '#84cc16, #22c55e',
                '#a855f7, #ec4899', '#0ea5e9, #3b82f6',
            ];
            let hash = 0;
            for (let i = 0; i < key.length; i++) hash = (hash * 31 + key.charCodeAt(i)) >>> 0;
            return palette[hash % palette.length];
        },

        // Tag chip style using the tag's actual color from customTags.
        tagChipStyle(tag) {
            const k = String(tag || '').toLowerCase();
            const match = this.customTags.find(t => t.name.toLowerCase() === k);
            const hex = match && match.color ? match.color : '#6b7280';
            // Parse hex to RGB for tinting
            let r = 107, g = 114, b = 128;
            const h = hex.replace('#', '');
            if (h.length === 6) {
                r = parseInt(h.substring(0, 2), 16);
                g = parseInt(h.substring(2, 4), 16);
                b = parseInt(h.substring(4, 6), 16);
            } else if (h.length === 3) {
                r = parseInt(h[0] + h[0], 16);
                g = parseInt(h[1] + h[1], 16);
                b = parseInt(h[2] + h[2], 16);
            }
            const bg = `rgba(${r},${g},${b},0.10)`;
            const border = `rgba(${r},${g},${b},0.30)`;
            const fg = `rgb(${Math.round(r*0.6)},${Math.round(g*0.6)},${Math.round(b*0.6)})`;
            return `background-color:${bg};color:${fg};border:1px solid ${border};`;
        },

        // Priority derived from tags (if any of them matches High / Medium / Low).
        priorityOf(conv) {
            if (!conv.tags) return null;
            for (const t of conv.tags) {
                const l = String(t || '').toLowerCase();
                if (l === 'high' || l === 'medium' || l === 'low') {
                    return l.charAt(0).toUpperCase() + l.slice(1);
                }
            }
            return null;
        },

        priorityStyle(level) {
            if (level === 'High')   return 'background-color:#fee2e2;color:#b91c1c;';
            if (level === 'Medium') return 'background-color:#dbeafe;color:#1d4ed8;';
            if (level === 'Low')    return 'background-color:#ccfbf1;color:#0f766e;';
            return '';
        },

        conversationTime(conv) {
            const raw = conv?.last_message_at;
            if (!raw) {
                return conv?.last_message_at_human || '';
            }

            const date = new Date(raw);
            if (Number.isNaN(date.getTime())) {
                return conv?.last_message_at_human || conv?.last_message_at_formatted || '';
            }

            const now = new Date();
            const isToday = date.getFullYear() === now.getFullYear()
                && date.getMonth() === now.getMonth()
                && date.getDate() === now.getDate();

            if (isToday) {
                return date.toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true,
                });
            }

            return date.toLocaleDateString([], {
                day: 'numeric',
                month: 'long',
            });
        },

        async loadConversations(reset = false, append = false) {
            if (reset) { this.page = 1; this.conversations = []; }
            this.loadingConversations = true;
            try {
                const params = new URLSearchParams({ folder: this.activeFolder, q: this.search, sort: this.sort, page: this.page });
                if (this.activeAccountId !== null) params.set('account_id', this.activeAccountId);
                if (this.activePriority) params.set('tag', this.activePriority);
                else if (this.activeTag) params.set('tag', this.activeTag);
                if (this.activeCategory) params.set('category', this.activeCategory);
                const resp = await fetch('{{ route("customer.unibox.conversations") }}?' + params, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await resp.json();
                this.conversations = append ? [...this.conversations, ...data.data] : data.data;
                this.hasMore = data.has_more;
                if (typeof data.total === 'number') this.totalCount = data.total;
            } finally {
                this.loadingConversations = false;
            }
        },

        loadMore() {
            this.page++;
            this.loadConversations(false, true);
        },

        async selectConversation(conv) {
            this.activeConversation = conv;
            this.loadingThread = true;
            this.threadMessages = [];
            this.threadNotes = [];
            this.activeContact = null;
            this.addedSequences = [];
            this.showAddToSequence = false;
            this.showAddToList = false;
            try {
                const resp = await fetch(`{{ url('customer/unibox') }}/${conv.id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await resp.json();
                this.threadMessages = data.messages;
                this.threadNotes = data.notes;
                this.activeContact = data.contact;
                this.activeConversation = { ...this.activeConversation, ...data.conversation };
                conv.is_read = true;
                this.$nextTick(() => {
                    const container = document.getElementById('messages-container');
                    if (container) container.scrollTop = 0;
                });
            } finally {
                this.loadingThread = false;
            }
        },

        execFormat(command, value = null) {
            this.$refs.replyEditor?.focus();
            document.execCommand(command, false, value);
            this.replyBody = this.$refs.replyEditor?.innerHTML || '';
        },

        handleReplyFiles(event) {
            const files = Array.from(event.target.files || []);
            this.replyAttachments = [...this.replyAttachments, ...files];
            event.target.value = '';
        },

        removeReplyAttachment(idx) {
            this.replyAttachments.splice(idx, 1);
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        async sendReply() {
            const body = (this.$refs.replyEditor?.innerHTML || '').trim();
            const textOnly = body.replace(/<[^>]*>/g, '').trim();
            if (!textOnly && this.replyAttachments.length === 0) return;
            if (this.sendingReply) return;
            this.sendingReply = true;
            try {
                const fd = new FormData();
                fd.append('body', body);
                this.replyAttachments.forEach((f, i) => fd.append(`attachments[${i}]`, f));

                const resp = await fetch(`{{ url('customer/unibox') }}/${this.activeConversation.id}/reply`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                let data = {};
                const contentType = resp.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    data = await resp.json();
                }
                if (!resp.ok) {
                    const msg = data.message || data.error || `Server error ${resp.status}`;
                    alert('Failed to send reply: ' + msg);
                    return;
                }
                if (data.message) {
                    this.threadMessages.push(data.message);
                    this.replyBody = '';
                    this.replyAttachments = [];
                    if (this.$refs.replyEditor) this.$refs.replyEditor.innerHTML = '';
                    this.$nextTick(() => {
                        const c = document.getElementById('messages-container');
                        if (c) c.scrollTop = c.scrollHeight;
                    });
                }
            } catch (err) {
                alert('Failed to send reply: ' + (err.message || 'Network error'));
            } finally {
                this.sendingReply = false;
            }
        },

        async addNote() {
            if (!this.newNote.trim()) return;
            const resp = await fetch(`{{ url('customer/unibox') }}/${this.activeConversation.id}/notes`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ content: this.newNote }),
            });
            const data = await resp.json();
            if (data.note) {
                this.threadNotes.push(data.note);
                this.newNote = '';
                this.showNoteInput = false;
            }
        },

        async deleteNote(noteId) {
            const resp = await fetch(`{{ url('customer/unibox') }}/notes/${noteId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (resp.ok) {
                this.threadNotes = this.threadNotes.filter(n => n.id !== noteId);
            }
        },

        async archiveConversation() {
            await fetch(`{{ url('customer/unibox') }}/${this.activeConversation.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ folder: 'archive' }),
            });
            this.conversations = this.conversations.filter(c => c.id !== this.activeConversation.id);
            this.activeConversation = null;
        },

        assignConversation() {},

        startEditContact(field, currentValue) {
            this.editingContactField = field;
            this.editingContactValue = currentValue || '';
            this.$nextTick(() => {
                const el = document.getElementById('contact-edit-input');
                if (el) el.focus();
            });
        },

        async saveContactField() {
            if (!this.activeContact || !this.editingContactField) return;
            const field = this.editingContactField;
            const value = this.editingContactValue;
            await fetch(`{{ url('customer/unibox') }}/contacts/${this.activeContact.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ field, value }),
            });
            // Update local state
            const directFields = ['name', 'phone', 'company', 'job_title', 'website', 'location'];
            if (directFields.includes(field)) {
                this.activeContact[field] = value;
            } else if (['facebook', 'instagram', 'twitter'].includes(field)) {
                if (!this.activeContact.social) this.activeContact.social = {};
                this.activeContact.social[field] = value;
            } else if (field === 'notes') {
                this.activeContact.notes = value;
            }
            this.editingContactField = null;
            this.editingContactValue = '';
        },

        cancelEditContact() {
            this.editingContactField = null;
            this.editingContactValue = '';
        },

        async addContactToSequence() {
            if (!this.addToSequenceId || !this.activeContact) return;
            const resp = await fetch(`{{ url('customer/unibox') }}/contacts/add-to-sequence`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    email: this.activeContact.email,
                    campaign_id: this.addToSequenceId,
                    name: this.activeContact.name,
                    company: this.activeContact.company,
                }),
            });
            const data = await resp.json();
            if (data.success) {
                this.addedSequences.push({ id: parseInt(this.addToSequenceId), name: data.campaign_name });
                this.addToSequenceId = '';
                this.showAddToSequence = false;
            } else if (data.error) {
                alert(data.error);
            }
        },

        async addContactToList() {
            if (!this.addToListId || !this.activeContact) return;
            const resp = await fetch(`{{ url('customer/unibox') }}/contacts/add-to-list`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ email: this.activeContact.email, list_id: this.addToListId, name: this.activeContact.name }),
            });
            const data = await resp.json();
            if (data.success) {
                if (!this.activeContact.subscribed_lists) this.activeContact.subscribed_lists = [];
                this.activeContact.subscribed_lists.push({ id: parseInt(this.addToListId), name: data.list_name, status: 'confirmed' });
                this.addToListId = '';
                this.showAddToList = false;
            }
        },

        async loadReplyTemplates() {
            if (this.replyTemplatesLoaded) return;
            const resp = await fetch(`{{ url('customer/unibox') }}/reply-templates`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();
            this.replyTemplates = data.templates || [];
            this.replyTemplatesLoaded = true;
        },

        get filteredTemplates() {
            const q = (this.templateSearch || '').toLowerCase();
            if (!q) return this.replyTemplates;
            return this.replyTemplates.filter(t => t.name.toLowerCase().includes(q) || (t.body || '').toLowerCase().includes(q) || (t.folder || '').toLowerCase().includes(q));
        },

        get templateFolders() {
            const grouped = {};
            for (const t of this.filteredTemplates) {
                const f = t.folder || 'General';
                if (!grouped[f]) grouped[f] = [];
                grouped[f].push(t);
            }
            return grouped;
        },

        get existingFolders() {
            const set = new Set();
            for (const t of this.replyTemplates) { if (t.folder) set.add(t.folder); }
            return [...set].sort();
        },

        renderTemplateVariables(html) {
            const c = this.activeContact || {};
            const fullName = (c.name || '').trim();
            const parts = fullName.split(/\s+/);
            const firstName = parts[0] || '';
            const lastName = parts.length > 1 ? parts.slice(1).join(' ') : '';
            const map = {
                first_name: firstName,
                last_name: lastName,
                full_name: fullName,
                name: fullName,
                email: c.email || '',
                company: c.company || '',
            };
            return (html || '').replace(/\{(\w+)\}/g, (match, key) => {
                return Object.prototype.hasOwnProperty.call(map, key) ? (map[key] || '') : match;
            });
        },

        insertTemplate(tpl) {
            const rendered = this.renderTemplateVariables(tpl.body);
            if (this.openComposeModal) {
                const editor = this.$refs.composeEditor;
                if (editor) {
                    editor.innerHTML = (editor.innerHTML || '') + rendered;
                    this.syncComposeBody();
                } else {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = rendered;
                    const plain = tmp.innerText || tmp.textContent || '';
                    this.composeBody = (this.composeBody || '') + plain;
                }
                if (tpl.subject && !this.composeSubject) {
                    this.composeSubject = this.renderTemplateVariables(tpl.subject);
                }
            } else {
                const editor = this.$refs.replyEditor;
                if (editor) {
                    editor.innerHTML = (editor.innerHTML || '') + rendered;
                    this.replyBody = editor.innerHTML;
                } else {
                    this.replyBody = (this.replyBody || '') + rendered;
                }
            }
            this.showTemplatePopup = false;
            this.templateSearch = '';
        },

        openNewTemplateModal() {
            this.newTemplate = { name: '', folder: '', subject: '', body: '', shared: false };
            this.newFolderInput = '';
            this.showNewFolderInput = false;
            this.showNewTemplateModal = true;
            this.showTemplatePopup = false;
        },

        async saveNewTemplate() {
            if (!this.newTemplate.name || !this.newTemplate.body) return;
            const folder = this.showNewFolderInput ? this.newFolderInput : this.newTemplate.folder;
            const resp = await fetch(`{{ url('customer/unibox') }}/reply-templates`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ ...this.newTemplate, folder }),
            });
            const data = await resp.json();
            if (data.success) {
                this.replyTemplates.push(data.template);
                this.showNewTemplateModal = false;
                this._toast('{{ __("Template saved") }}');
            }
        },

        async deleteTemplate(id) {
            if (!confirm('{{ __("Delete this template?") }}')) return;
            const resp = await fetch(`{{ url('customer/unibox') }}/reply-templates/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (resp.ok) {
                this.replyTemplates = this.replyTemplates.filter(t => t.id !== id);
            }
        },

        addRecipient(field) {
            const map = { to: 'composeToInput', cc: 'composeCcInput', bcc: 'composeBccInput' };
            const listMap = { to: 'composeTo', cc: 'composeCc', bcc: 'composeBcc' };
            const val = this[map[field]].trim().replace(/,$/, '');
            if (val) { this[listMap[field]].push(val); this[map[field]] = ''; }
        },

        handleComposeFiles(event) {
            const files = Array.from(event.target.files || []);
            this.composeAttachments = [...this.composeAttachments, ...files];
            event.target.value = '';
        },

        removeComposeAttachment(idx) {
            this.composeAttachments.splice(idx, 1);
        },

        resetCompose() {
            this.composeTo = []; this.composeToInput = '';
            this.composeCc = []; this.composeCcInput = '';
            this.composeBcc = []; this.composeBccInput = '';
            this.showCc = false; this.showBcc = false;
            this.composeSubject = ''; this.composeBody = '';
            this.composeAttachments = [];
            this.composeMinimized = false; this.composeExpanded = false;
            if (this.$refs.composeEditor) this.$refs.composeEditor.innerHTML = '';
        },

        async sendCompose() {
            if (this.composeToInput.trim()) this.addRecipient('to');
            if (this.composeCcInput.trim()) this.addRecipient('cc');
            if (this.composeBccInput.trim()) this.addRecipient('bcc');

            this.syncComposeBody();

            const bodyText = (this.composeBody || '').replace(/<[^>]*>/g, '').trim();
            const selectedFrom = this.composeSelectedFromAccount;
            if (!this.composeTo.length || !selectedFrom || (!bodyText && this.composeAttachments.length === 0)) return;
            if (this.sendingCompose) return;

            this.sendingCompose = true;

            try {
                const fd = new FormData();
                this.composeTo.forEach((addr, i) => fd.append(`to[${i}]`, addr));
                this.composeCc.forEach((addr, i) => fd.append(`cc[${i}]`, addr));
                this.composeBcc.forEach((addr, i) => fd.append(`bcc[${i}]`, addr));
                fd.append('subject', this.composeSubject);
                fd.append('body', this.composeBody || '');
                fd.append('account_id', selectedFrom.id);
                this.composeAttachments.forEach((file, i) => fd.append(`attachments[${i}]`, file));

                const res = await fetch('{{ route("customer.unibox.compose") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this._toast(data.error || '{{ __('Send failed') }}', 'error');
                    return;
                }

                this._toast('{{ __('Message sent') }}');
                this.openComposeModal = false;
                this.resetCompose();
            } catch (e) {
                console.error(e);
                this._toast(e?.message || '{{ __('Send failed') }}', 'error');
            } finally {
                this.sendingCompose = false;
            }
        },
    };
}
</script>
@endpush
@endsection

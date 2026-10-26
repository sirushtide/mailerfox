@php
$adminPagesUrl = url('admin/pages');
@endphp

@extends('layouts.admin')

@section('title', __('Pages'))
@section('page-title', __('Pages'))

@section('content')
<div class="space-y-6" x-data="{
    editDrawerOpen: false,
    editPage: null,
    createModalOpen: false,
    newPageTitle: '',
    openEditDrawer(page) {
        this.editPage = page;
        this.editDrawerOpen = true;
    },
    closeEditDrawer() {
        this.editDrawerOpen = false;
        this.editPage = null;
    }
}">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between lg:gap-0">
        <div class="w-full lg:flex-1 lg:max-w-lg">
            <form method="GET" action="{{ route('admin.pages.index') }}" class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <input
                    type="text"
                    name="q"
                    value="{{ $search ?? '' }}"
                    placeholder="{{ __('Search pages...') }}"
                    class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                >
                <x-button type="submit" variant="primary" class="w-full lg:w-auto">{{ __('Search') }}</x-button>
            </form>
        </div>

        @admincan('admin.pages.create')
            <div class="flex flex-col gap-2 w-full lg:w-auto lg:flex-row">
                <x-button type="button" @click="createModalOpen = true" variant="primary" class="w-full lg:w-auto">{{ __('Create Page') }}</x-button>
            </div>
        @endadmincan
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Title') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Slug / Variant') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Last Updated') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($pages as $page)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $page->title }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $page->type }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($page->type === 'homepage')
                                    {{ $page->variant_key ?? '—' }}
                                @else
                                    <span class="font-mono">{{ $page->slug }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $page->status === 'publish' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $page->status === 'publish' ? __('Published') : __('Draft') }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $page->updated_at?->diffForHumans() ?? '—' }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    @if(in_array($page->type, ['page', 'homepage']))
                                        <x-button href="{{ route('pages.show', $page->slug) }}?preview=1" target="_blank" variant="table" size="action" :pill="true" class="p-2" title="{{ __('Preview') }}" aria-label="{{ __('Preview') }}"><x-lucide name="eye" class="h-4 w-4" /><span class="sr-only">{{ __('Preview') }}</span></x-button>
                                    @endif

                                    @admincan('admin.pages.edit')
                                        <x-button
                                            type="button"
                                            @click="openEditDrawer({{ json_encode($page->only(['id','title','slug','status','type'])) }})"
                                            variant="table"
                                            size="action"
                                            :pill="true"
                                            class="p-2"
                                            title="{{ __('Edit') }}"
                                            aria-label="{{ __('Edit') }}"
                                        ><x-lucide name="pencil" class="h-4 w-4" /><span class="sr-only">{{ __('Edit') }}</span></x-button>

                                        <x-button
                                            href="{{ route('admin.pages.builder', $page) }}"
                                            variant="table"
                                            size="action"
                                            :pill="true"
                                            class="p-2"
                                            title="{{ __('Builder') }}"
                                            aria-label="{{ __('Builder') }}"
                                        ><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.106-3.105c.32-.322.863-.22.983.218a6 6 0 0 1-8.259 7.057l-7.91 7.91a1 1 0 0 1-2.999-3l7.91-7.91a6 6 0 0 1 7.057-8.259c.438.12.54.662.219.984z"/></svg><span class="sr-only">{{ __('Builder') }}</span></x-button>
                                    @endadmincan

                                    @admincan('admin.pages.delete')
                                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="inline" onsubmit="return confirm(@json(__('Delete page?')));">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="table-danger" size="action" :pill="true" class="p-2" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}"><x-lucide name="trash-2" class="h-4 w-4" /><span class="sr-only">{{ __('Delete') }}</span></x-button>
                                        </form>
                                    @endadmincan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No pages found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pages->hasPages())
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 bg-white dark:bg-gray-800">
                {{ $pages->links() }}
            </div>
        @endif
    </x-card>

    {{-- Edit Drawer --}}
    <template x-teleport="body">
        <div x-cloak x-show="editDrawerOpen" class="fixed inset-0 z-[100]" @keydown.escape.window="closeEditDrawer()">
            <div x-show="editDrawerOpen" x-transition.opacity class="absolute inset-0 bg-gray-900/30 dark:bg-black/50" @click="closeEditDrawer()"></div>
            <aside
                x-show="editDrawerOpen"
                x-transition:enter="transition transform ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition transform ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute top-0 right-0 h-full w-full sm:w-[420px] bg-white dark:bg-admin-main shadow-2xl flex flex-col border-l border-gray-200 dark:border-admin-border"
            >
                <div class="flex items-center justify-between px-6 pt-6 pb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Edit Page') }}</h2>
                    <button type="button" @click="closeEditDrawer()" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-admin-text-primary rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form x-bind:action="'{{ $adminPagesUrl }}' + '/' + (editPage ? editPage.id : '')" method="POST" class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="redirect_to" value="index">
                    <input type="hidden" name="type" x-bind:value="editPage ? editPage.type : 'page'">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Name') }}</label>
                        <input type="text" name="title" x-bind:value="editPage ? editPage.title : ''" required class="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Slug') }}</label>
                        <input type="text" name="slug" x-bind:value="editPage ? editPage.slug : ''" class="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Status') }}</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="draft" x-bind:selected="editPage && editPage.status === 'draft'">{{ __('Draft') }}</option>
                            <option value="publish" x-bind:selected="editPage && editPage.status === 'publish'">{{ __('Published') }}</option>
                        </select>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3">
                        <x-button type="button" variant="secondary" @click="closeEditDrawer()">{{ __('Cancel') }}</x-button>
                        <x-button type="submit" variant="primary">{{ __('Save') }}</x-button>
                    </div>
                </form>
            </aside>
        </div>
    </template>

    {{-- Create Page Modal --}}
    <template x-teleport="body">
        <div x-cloak x-show="createModalOpen" class="fixed inset-0 z-[100]" @keydown.escape.window="createModalOpen = false">
            <div x-show="createModalOpen" x-transition.opacity class="absolute inset-0 bg-gray-900/30 dark:bg-black/50" @click="createModalOpen = false"></div>
            <div
                x-show="createModalOpen"
                x-transition
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white dark:bg-admin-main rounded-xl shadow-2xl border border-gray-200 dark:border-admin-border p-6"
            >
                <h2 class="text-xl font-semibold text-gray-900 dark:text-admin-text-primary mb-4">{{ __('Create New Page') }}</h2>
                <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Page Name') }}</label>
                        <input type="text" name="title" required class="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <input type="hidden" name="type" value="page">
                    <input type="hidden" name="status" value="draft">
                    <div class="pt-2 flex items-center justify-end gap-3">
                        <x-button type="button" variant="secondary" @click="createModalOpen = false">{{ __('Cancel') }}</x-button>
                        <x-button type="submit" variant="primary">{{ __('Start Building') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

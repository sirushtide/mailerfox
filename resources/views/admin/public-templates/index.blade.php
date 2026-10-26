@extends('layouts.admin')

@php
    $currentType = request()->query('type');
    $pageTitle = match ($currentType) {
        'footer' => __('Footer templates'),
        'signature' => __('Signature'),
        default => __('Templates'),
    };
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush

@section('content')
<div
    class="space-y-4"
    x-data="{
        showCreateModal: false,
        newName: '',
        newType: '{{ in_array($currentType, ['email', 'campaign', 'transactional', 'autoresponder', 'footer', 'signature'], true) ? $currentType : 'email' }}',
        newBuilder: 'email',
        newCategory: '',
        nameError: '',
        startEditing() {
            if (!this.newName.trim()) { this.nameError = '{{ __('Template name is required.') }}'; return; }
            this.nameError = '';
            const base = '{{ route('admin.public-templates.create') }}';
            const params = new URLSearchParams({
                name: this.newName.trim(),
                type: this.newType,
                builder: this.newBuilder,
            });
            if (this.newCategory) { params.set('category_id', this.newCategory); }
            window.location.href = base + '?' + params.toString();
        }
    }"
>
    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between lg:gap-0">
        <form method="GET" action="{{ route('admin.public-templates.index') }}" class="w-full lg:w-auto flex flex-col gap-2 sm:flex-row sm:items-center">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="{{ __('Search templates...') }}"
                class="block w-full sm:w-72 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
            <select name="type" class="block w-full sm:w-auto rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                <option value="">{{ __('All Types') }}</option>
                <option value="email" @selected(($type ?? '') === 'email')>{{ __('Email') }}</option>
                <option value="campaign" @selected(($type ?? '') === 'campaign')>{{ __('Campaign') }}</option>
                <option value="transactional" @selected(($type ?? '') === 'transactional')>{{ __('Transactional') }}</option>
                <option value="autoresponder" @selected(($type ?? '') === 'autoresponder')>{{ __('Autoresponder') }}</option>
                <option value="footer" @selected(($type ?? '') === 'footer')>{{ __('Footer Template') }}</option>
                <option value="signature" @selected(($type ?? '') === 'signature')>{{ __('Signature') }}</option>
            </select>
            <x-button type="submit" variant="secondary" class="w-full sm:w-auto">{{ __('Search') }}</x-button>
            @if($search !== '' || ($type ?? '') !== '')
                <x-button href="{{ route('admin.public-templates.index') }}" variant="secondary" class="w-full sm:w-auto">{{ __('Reset') }}</x-button>
            @endif
        </form>

        @admincan('admin.public_templates.create')
            <x-button type="button" variant="primary" class="w-full lg:w-auto" @click="showCreateModal = true; newName = ''; nameError = ''; newType = '{{ in_array($currentType, ['email', 'campaign', 'transactional', 'autoresponder', 'footer', 'signature'], true) ? $currentType : 'email' }}'; newBuilder = 'email'; newCategory = ''">
                {{ __('Create Template') }}
            </x-button>
        @endadmincan
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Category') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Builder') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Active') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($templates as $template)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $template->name }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $template->slug }}</div>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">
                                {{ $template->category?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $template->type }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $template->builder }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $template->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $template->is_active ? __('Active') : __('Disabled') }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    @admincan('admin.public_templates.edit')
                                        <x-button href="{{ route('admin.public-templates.edit', $template) }}" variant="table" size="action" :pill="true" class="p-2" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"><x-lucide name="pencil" class="h-4 w-4" /><span class="sr-only">{{ __('Edit') }}</span></x-button>
                                    @endadmincan
                                    @admincan('admin.public_templates.delete')
                                        <form method="POST" action="{{ route('admin.public-templates.destroy', $template) }}" class="inline" onsubmit="return confirm(@json(__('Delete template?')));">
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
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No templates found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $templates->links() }}</div>
        @endif
    </x-card>

    {{-- Create Template Modal --}}
    <div x-cloak x-show="showCreateModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-950/45" @click="showCreateModal = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Create template') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Enter the template name and choose a type to start editing.') }}</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">{{ __('Builder Type') }}</label>
                    <select x-model="newBuilder" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm">
                        <option value="email">{{ __('Email Page Builder') }}</option>
                        <option value="text">{{ __('Text Composer') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">{{ __('Template Name') }}</label>
                    <input type="text" x-model="newName" @keydown.enter.prevent="startEditing()" @keydown.escape.prevent="showCreateModal = false" autofocus
                        class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm"
                        placeholder="{{ __('My new template') }}">
                    <p x-show="nameError" x-text="nameError" class="mt-2 text-xs text-red-600"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">{{ __('Template Type') }}</label>
                    <select x-model="newType" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm">
                        <option value="email">{{ __('Email') }}</option>
                        <option value="campaign">{{ __('Campaign') }}</option>
                        <option value="transactional">{{ __('Transactional') }}</option>
                        <option value="autoresponder">{{ __('Autoresponder') }}</option>
                        <option value="footer">{{ __('Footer Template') }}</option>
                        <option value="signature">{{ __('Signature') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">{{ __('Category') }}</label>
                    <select x-model="newCategory" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm">
                        <option value="">{{ __('—') }}</option>
                        @foreach(($categories ?? []) as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" @click="showCreateModal = false" class="rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">{{ __('Cancel') }}</button>
                <button type="button" @click="startEditing()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">{{ __('Start Editing') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

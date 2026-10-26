@extends('layouts.admin')

@section('title', __('Create Template'))
@section('page-title', __('Create Template'))

@push('styles')
@include('ai._provider-models')
<style>
    #editor-container {
        height: calc(100vh - 360px);
        min-height: 700px;
    }
</style>
@endpush

@section('content')
<div class="space-y-4" x-data="templateImportModal(@js([
    'galleryUrl' => route('admin.templates.import.gallery'),
    'contentUrlBase' => url('/admin/templates/import'),
    'aiUrl' => route('admin.public-templates.ai-generate'),
    'csrfToken' => csrf_token(),
    'builder' => 'unlayer',
    'canImport' => true,
    'canAi' => true,
    'initialTab' => 'ai',
]))">
    <nav aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <li><a href="{{ route('admin.dashboard') }}" class="font-medium text-gray-600 transition hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">{{ __('Home') }}</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('admin.public-templates.index') }}" class="font-medium text-gray-600 transition hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">{{ __('Templates') }}</a></li>
            <li aria-hidden="true">/</li>
            <li class="text-gray-900 dark:text-gray-100">{{ __('Create') }}</li>
        </ol>
    </nav>

    <x-card>
        <form id="unlayer-form" method="POST" action="{{ route('admin.public-templates.store') }}" class="space-y-4">
            @csrf

            @include('admin.public-templates.form', [
                'template' => $template,
                'categories' => $categories,
                'customerGroups' => $customerGroups,
            ])

            <div class="flex items-center justify-end gap-2">
                <x-button href="{{ route('admin.public-templates.index') }}" variant="secondary">{{ __('Cancel') }}</x-button>
                <x-button type="button" variant="secondary" x-on:click="open()">{{ __('Import templates') }}</x-button>
                <x-button type="button" id="btn-save" variant="primary">{{ __('Save') }}</x-button>
            </div>

            <input type="hidden" name="html_content" id="html_content">
            <input type="hidden" name="plain_text_content" id="plain_text_content">
            <input type="hidden" name="grapesjs_data" id="grapesjs_data">
        </form>
    </x-card>

    <div x-cloak x-show="($data.importOpen ?? false)" class="fixed inset-0 z-50 flex items-center justify-center" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative w-full max-w-6xl mx-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Import templates') }}</h3>
                <button type="button" @click="close()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <span class="sr-only">Close</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="px-6 pt-4">
                <template x-if="($data.error ?? '')">
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-200" x-text="$data.error"></div>
                </template>

                <div class="flex items-center gap-6 border-b border-gray-200 dark:border-gray-700">
                    <button type="button" @click="setTab('templates')" class="py-3 text-sm font-medium" :class="($data.tab ?? '') === 'templates' ? 'text-primary-600 border-b-2 !border-primary-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">Templates</button>
                    <button type="button" @click="setTab('ai')" class="py-3 text-sm font-medium" :class="($data.tab ?? '') === 'ai' ? 'text-primary-600 border-b-2 !border-primary-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">AI Creator</button>
                </div>
            </div>

            <div class="px-6 py-4">
                <div x-show="($data.tab ?? '') !== 'ai'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Templates</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400" x-show="($data.loading ?? false)">Loading...</div>
                        </div>
                        <div class="max-h-[520px] overflow-auto divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="item in ($data.gallery ?? [])" :key="item.id">
                                <button type="button" class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700" @click="select(item)" :class="($data.selected && $data.selected.id === item.id) ? 'bg-gray-50 dark:bg-gray-700' : ''">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="item.name"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="item.description || ''"></div>
                                </button>
                            </template>
                            <div x-show="!($data.loading ?? false) && (($data.gallery ?? []).length === 0)" class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">No templates found.</div>
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Preview</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400" x-show="($data.previewLoading ?? false)">Loading...</div>
                        </div>
                        <div class="p-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white overflow-hidden">
                                <iframe class="w-full h-[420px] border-0" style="min-height: 420px;" loading="lazy" sandbox="allow-scripts allow-same-origin" x-bind:srcdoc="$data.preview?.html_content || '<p style=&quot;padding:12px&quot;>No preview</p>'"></iframe>
                            </div>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
                            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600" @click="close()">Cancel</button>
                            <button type="button" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 disabled:opacity-60" :disabled="!($data.preview ?? null) || ($data.importing ?? false)" @click="importSelected()">
                                <span x-show="!($data.importing ?? false)">Import</span>
                                <span x-show="($data.importing ?? false)">Importing...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="($data.tab ?? '') === 'ai'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provider</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" x-model="$data.ai.provider">
                                @php($aiProviders = \App\Services\AI\AiProviderRegistry::providers())
                                @foreach($aiProviders as $key => $config)
                                    <option value="{{ $key }}">{{ $config['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" x-model="$data.ai.model">
                                <option value="">Default</option>
                                <template x-for="m in aiModelsForProvider()" :key="m">
                                    <option :value="m" x-text="m"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt</label>
                            <textarea rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" x-model="$data.ai.prompt" placeholder="Describe the email template you want..."></textarea>
                        </div>
                        <template x-if="($data.ai?.error ?? '')">
                            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-200" x-text="$data.ai.error"></div>
                        </template>
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600" @click="close()">Cancel</button>
                            <button type="button" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 disabled:opacity-60" :disabled="($data.ai?.loading ?? false) || !($data.ai?.prompt ?? '')" @click="aiGenerate()">
                                <span x-show="!($data.ai?.loading ?? false)">Generate</span>
                                <span x-show="($data.ai?.loading ?? false)">Generating...</span>
                            </button>
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Preview</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400" x-show="Number.isFinite(Number($data.preview?.tokens_used))">Tokens: <span x-text="$data.preview?.tokens_used"></span></div>
                        </div>
                        <div class="p-4">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white overflow-hidden">
                                <iframe class="w-full h-[520px] border-0" style="min-height: 520px;" loading="lazy" sandbox="allow-scripts allow-same-origin" x-bind:srcdoc="$data.preview?.html_content || '<p style=&quot;padding:12px&quot;>Generate a template to preview</p>'"></iframe>
                            </div>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
                            <button type="button" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 disabled:opacity-60" :disabled="!($data.preview ?? null) || ($data.importing ?? false)" @click="importSelected()">
                                <span x-show="!($data.importing ?? false)">Import into builder</span>
                                <span x-show="($data.importing ?? false)">Importing...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="($data.previewModalOpen ?? false)" class="fixed inset-0 z-[60] flex items-center justify-center">
        <div class="fixed inset-0 bg-black/60" @click="closePreviewModal()"></div>
        <div class="relative w-full max-w-4xl mx-4 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="$data.previewModalItem?.name || 'Preview'"></h3>
                <button type="button" @click="closePreviewModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-hidden p-4">
                <div x-show="($data.previewModalLoading ?? false)" class="flex flex-col items-center justify-center h-[500px] border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <svg class="animate-spin h-10 w-10 text-primary-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Rendering preview...') }}</p>
                </div>
                <div x-show="!($data.previewModalLoading ?? false)" x-cloak class="h-[500px] border border-gray-200 dark:border-gray-700 rounded-lg bg-white overflow-hidden">
                    <iframe class="w-full h-full border-0" loading="lazy" sandbox="allow-scripts allow-same-origin" x-bind:srcdoc="$data.previewModalData?.html_content || '<p style=&quot;padding:12px&quot;>No preview available</p>'"></iframe>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
                <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600" @click="closePreviewModal()">{{ __('Cancel') }}</button>
                <button type="button" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 disabled:opacity-60" :disabled="!($data.previewModalData ?? null)" @click="insertFromPreviewModal()">
                    {{ __('Insert Template') }}
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div
            id="editor-container"
            data-unlayer-editor
            data-unlayer-display-mode="email"
            data-unlayer-project-id="{{ $unlayerProjectId }}"
        ></div>
    </div>
</div>
@endsection

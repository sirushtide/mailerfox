@extends('layouts.admin')

@section('title', $isEdit ? __('Edit Template') : __('Create Template'))
@section('page-title', $isEdit ? __('Edit Template') : __('Create Template'))

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .admin-template-builder {
        height: 100vh;
        min-height: 720px;
        overflow: hidden;
    }
</style>
@endpush

@section('content')
@php
    $builderType = $builderType ?? 'email';

    $selectedGroups = old('customer_group_ids');
    if (!is_array($selectedGroups)) {
        $selectedGroups = isset($template) && $template->relationLoaded('customerGroups')
            ? $template->customerGroups->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    } else {
        $selectedGroups = array_map(fn ($id) => (string) $id, $selectedGroups);
    }
@endphp

<div class="admin-template-builder {{ $builderType === 'text' ? 'flex flex-col' : '' }}">
    <form id="unlayer-form" method="POST" action="{{ $formAction }}" class="{{ $builderType === 'text' ? 'flex flex-col h-full min-h-0' : '' }}">
        @csrf
        @if(($formMethod ?? 'POST') !== 'POST')
            @method($formMethod)
        @endif

        <input type="hidden" name="name" id="name" value="{{ old('name', $name ?? $template->name ?? '') }}">
        <input type="hidden" name="description" id="description" value="{{ old('description', $template->description ?? '') }}">
        <input type="hidden" name="type" id="type" value="{{ old('type', $template->type ?? 'email') }}">
        <input type="hidden" name="category_id" id="category_id" value="{{ old('category_id', $template->category_id ?? '') }}">
        <input type="hidden" name="thumbnail" id="thumbnail" value="{{ old('thumbnail', $template->thumbnail ?? '') }}">
        <input type="hidden" name="is_active" id="is_active" value="{{ old('is_active', ($template->is_active ?? true) ? '1' : '0') }}">
        <input type="hidden" name="available_to_all_groups" id="available_to_all_groups" value="{{ old('available_to_all_groups', ($template->available_to_all_groups ?? true) ? '1' : '0') }}">
        @foreach($selectedGroups as $groupId)
            <input type="hidden" name="customer_group_ids[]" value="{{ $groupId }}">
        @endforeach

        <input type="hidden" name="html_content" id="html_content" value="{{ old('html_content', $template->html_content ?? '') }}">
        <input type="hidden" name="plain_text_content" id="plain_text_content" value="{{ old('plain_text_content', $template->plain_text_content ?? '') }}">
        <input type="hidden" name="grapesjs_data" id="grapesjs_data" value="{{ old('grapesjs_data', is_array($builderData ?? null) ? json_encode($builderData) : '') }}">

        @if($builderType === 'text')
            <div class="shrink-0 flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-admin-sidebar">
                <div class="flex items-center gap-2 min-w-0 text-sm">
                    <a href="{{ route('admin.public-templates.index') }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('Templates') }}
                    </a>
                    <span class="text-gray-300 dark:text-gray-600">/</span>
                    <span class="font-semibold text-gray-900 dark:text-white truncate max-w-[280px]">{{ ($name ?? '') !== '' ? $name : ($template->name ?: __('New Template')) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.public-templates.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">{{ __('Discard') }}</a>
                    <button type="button" id="btn-save" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">{{ __('Save Template') }}</button>
                </div>
            </div>

            <div class="flex-1 min-h-0 overflow-auto bg-slate-50 dark:bg-slate-900 p-6">
                <div class="mx-auto max-w-3xl rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
                    <div class="p-5">
                        <div
                            data-outreach-rich-editor
                            data-upload-url="{{ route('admin.uploads.inline-image') }}"
                            data-template-editor="1"
                            data-step-id="admin-template"
                            data-variant="a"
                            data-field="body"
                            data-value="{{ old('html_content', $template->html_content ?? '') }}"
                            data-placeholder="{{ __('Write your email body...') }}"
                        ></div>
                    </div>
                </div>
            </div>
        @else
            <div
                id="page-builder-react"
                class="h-full"
                data-mode="email"
                data-page-title="{{ e($name ?? $template->name ?? 'Email template') }}"
                data-ai-enabled="0"
                data-template-name="{{ e($name ?? $template->name ?? 'Email template') }}"
                data-templates-list-url="{{ url('admin/public-templates/gallery') }}"
                data-templates-fetch-url="{{ url('admin/public-templates/gallery-fetch') }}"
                data-templates-product-id="7"
                data-settings-url="{{ route('admin.public-templates.index') }}"
                data-render-html-url="{{ route('admin.public-templates.render-builder') }}"
                data-upload-url="{{ route('admin.public-templates.upload-image') }}"
                data-builder-input-id="grapesjs_data"
                data-initial-builder="{{ json_encode($builderData) }}"
            ></div>
        @endif
    </form>
</div>
@endsection

@if($builderType === 'text')
    @once
        @push('scripts')
        @vite('resources/js/outreach-sequence-editor.jsx')
        <script>
        (function () {
            const bindTemplateTextSave = () => {
                const button = document.getElementById('btn-save');
                const form = document.getElementById('unlayer-form');
                if (!button || !form || button.dataset.textTemplateBound === '1') return;
                button.dataset.textTemplateBound = '1';

                button.addEventListener('click', async () => {
                    const editorEl = document.querySelector('[data-outreach-rich-editor][data-template-editor="1"]');
                    const html = editorEl ? String(editorEl.dataset.value || '') : '';
                    const plain = html
                        .replace(/<style[\s\S]*?<\/style>/gi, ' ')
                        .replace(/<[^>]+>/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim();

                    document.getElementById('html_content').value = html;
                    document.getElementById('plain_text_content').value = plain;
                    document.getElementById('grapesjs_data').value = JSON.stringify({ builder: 'text' });

                    const nameInput = document.getElementById('name');
                    if (nameInput && !nameInput.value.trim()) {
                        window.alert('{{ __('Template name is required.') }}');
                        return;
                    }

                    const originalText = button.textContent;
                    button.disabled = true;
                    button.textContent = '{{ __('Saving…') }}';
                    try {
                        const formData = new FormData(form);
                        const saveResponse = await fetch(form.action, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: formData,
                        });
                        const saveResult = await saveResponse.json().catch(() => ({}));
                        if (!saveResponse.ok) {
                            const validationMessage = saveResult.errors
                                ? Object.values(saveResult.errors).flat().join('\n')
                                : null;
                            throw new Error(validationMessage || saveResult.message || '{{ __('Unable to save the email template.') }}');
                        }

                        if (saveResult.update_url) {
                            form.action = saveResult.update_url;
                            form.method = 'POST';
                            if (!form.querySelector('input[name="_method"]')) {
                                const methodInput = document.createElement('input');
                                methodInput.type = 'hidden';
                                methodInput.name = '_method';
                                methodInput.value = 'PUT';
                                form.appendChild(methodInput);
                            }
                        }

                        if (saveResult.edit_url && window.history && typeof window.history.replaceState === 'function') {
                            try {
                                window.history.replaceState(window.history.state, '', saveResult.edit_url);
                            } catch (e) {
                            }
                        }

                        if (typeof window.mailpurseToast === 'function') {
                            window.mailpurseToast({ type: 'success', message: saveResult.message || '{{ __('Template saved successfully.') }}' });
                        } else {
                            window.alert(saveResult.message || '{{ __('Template saved successfully.') }}');
                        }

                        button.disabled = false;
                        button.textContent = originalText;
                    } catch (error) {
                        window.alert(error.message || '{{ __('Unable to save the email template.') }}');
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                });
            };

            document.addEventListener('DOMContentLoaded', bindTemplateTextSave, { once: true });
            document.addEventListener('turbo:load', bindTemplateTextSave);
            window.setTimeout(bindTemplateTextSave, 250);
        })();
        </script>
        @endpush
    @endonce
@endif

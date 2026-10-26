@extends('layouts.customer')

@section('title', 'Design Subscription Form')
@section('page-title', '')
@section('force-sidebar-collapsed', '1')
@section('hide-top-header', '1')
@section('content-padding-classes', 'p-0')

@section('page-header')
<div class="px-4 sm:px-6 py-2 bg-white dark:bg-admin-sidebar dark:border-admin-border fixed z-20 w-[calc(100%-30px)] border-b">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 min-w-0 text-sm">
            <a href="{{ route('customer.lists.forms.index', $list) }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Forms
            </a>
            <span class="text-gray-300 dark:text-gray-600">/</span>
            <span class="font-semibold text-gray-900 dark:text-white truncate">{{ $form->name }}</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300 uppercase">{{ $form->builder === 'unlayer' ? 'unlayer' : 'draft' }}</span>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('customer.lists.forms.edit', [$list, $form]) }}" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Settings</a>
            <a href="{{ route('customer.lists.forms.show', [$list, $form]) }}" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Discard</a>
            <button type="button" id="btn-save" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">Save Form</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    html,
    body {
        height: 100%;
    }

    .customer-theme main {
        background: #ffffff;
    }

    #editor-container {
        height: calc(100vh - 49px);
        min-height: 720px;
    }
</style>
@endpush

@section('content')
<div class="flex-1 min-h-0 overflow-hidden flex flex-col">
    <form id="unlayer-form" method="POST" action="{{ route('customer.lists.forms.unlayer.update', [$list, $form]) }}" class="hidden">
        @csrf
        @method('PUT')
        <input type="hidden" name="html_content" id="html_content" value="{{ old('html_content', $form->html_content) }}">
        <input type="hidden" name="plain_text_content" id="plain_text_content" value="{{ old('plain_text_content', $form->plain_text_content) }}">
        <input type="hidden" name="builder_data" id="grapesjs_data" value="{{ old('builder_data', json_encode(data_get($form->builder_data, 'unlayer', $form->builder_data))) }}">
    </form>

    <div class="flex-1 min-h-0 bg-white overflow-hidden pt-[49px]">
        <script type="application/json" id="unlayer-design-json">@json($unlayerDesign)</script>
        @php
            $unlayerDisplayMode = $form->type === 'popup' ? 'popup' : 'web';

            $selectedFields = is_array($form->fields) ? $form->fields : ['email'];
            if (!in_array('email', $selectedFields, true)) {
                $selectedFields[] = 'email';
            }

            $customDefs = is_array($list->custom_fields) ? $list->custom_fields : [];
            $customDefsByKey = [];
            foreach ($customDefs as $def) {
                if (!is_array($def)) {
                    continue;
                }
                $k = trim((string) ($def['key'] ?? ''));
                if ($k === '') {
                    continue;
                }
                $customDefsByKey[$k] = $def;
            }

            $standardLabels = [
                'email' => 'Email',
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
            ];

            $inputOptions = [];
            $textareaOptions = [];

            foreach ($selectedFields as $f) {
                if (!is_string($f) || trim($f) === '') {
                    continue;
                }

                if (isset($standardLabels[$f])) {
                    $inputOptions[] = ['label' => $standardLabels[$f], 'value' => $f];
                    continue;
                }

                if (!str_starts_with($f, 'cf:')) {
                    continue;
                }

                $key = trim(substr($f, 3));
                $def = $customDefsByKey[$key] ?? null;
                if (!$def) {
                    continue;
                }

                $label = trim((string) ($def['label'] ?? $key));
                $type = (string) ($def['type'] ?? 'text');
                $opt = ['label' => $label, 'value' => 'cf:' . $key];

                if ($type === 'textarea') {
                    $textareaOptions[] = $opt;
                } else {
                    $inputOptions[] = $opt;
                }
            }

            $unlayerFieldConfig = [
                'input' => ['options' => $inputOptions],
                'textarea' => ['options' => $textareaOptions],
            ];
        @endphp
        <script type="application/json" id="mailpurse-unlayer-fields-json">@json($unlayerFieldConfig)</script>
        <div
            id="editor-container"
            data-unlayer-editor
            data-unlayer-display-mode="{{ $unlayerDisplayMode }}"
            data-unlayer-project-id="{{ $unlayerProjectId }}"
            data-unlayer-design-script-id="unlayer-design-json"
            data-unlayer-fields-script-id="mailpurse-unlayer-fields-json"
            data-unlayer-form-action-url="{{ route('public.subscribe.api', $form->slug) }}"
        ></div>
    </div>
</div>
@endsection

@extends('layouts.customer')

@section('title', __('Form Builder'))
@section('page-title', '')
@section('hide-top-header', '1')
@section('page-header')
@endsection

@push('styles')
@vite(['resources/js/page-builder/main/index.js'])
<style>
    body.mp-form-builder-mode aside[data-sidebar="app"] {
        display: none !important;
    }
    body.mp-form-builder-mode .flex > .flex-1.flex.flex-col.overflow-hidden {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    body.mp-form-builder-mode main {
        padding: 0 !important;
    }
    .customer-builder-shell {
        height: 100vh;
        min-height: 720px;
    }
</style>
@endpush

@push('scripts')
<script>
    document.body.classList.add('mp-form-builder-mode');
</script>
@endpush

@section('content')
<div class="customer-builder-shell flex flex-col">
    <form id="page-builder-form" method="POST" action="{{ route('customer.lists.forms.builder.update', [$list, $form]) }}" class="flex flex-col h-full">
        @csrf
        @method('PUT')

        <input type="hidden" name="redirect_to" value="builder">

        <input type="hidden" name="builder_data" id="builder_data" value="{{ old('builder_data', json_encode($pageBuilderData ?? [])) }}">

        <div class="flex-1">
            <div
                id="page-builder-react"
                class="h-full"
                data-builder-input-id="builder_data"
                data-initial-builder='@json($pageBuilderData ?? [])'
                data-upload-url="{{ route('customer.lists.forms.upload-icon', $list) }}"
                data-page-title="{{ $form->name }}"
                data-settings-url="{{ route('customer.lists.forms.show', [$list, $form]) }}"
                data-preview-url="{{ route('public.subscribe', ['slug' => $form->slug, 'preview' => 1]) }}"
                data-render-html-url="{{ route('customer.lists.forms.builder.render-html', [$list, $form]) }}"
                data-mode="form"
                data-lists='@json($lists ?? [])'
                data-mega-menu-builder="{{ config('mailpurse.features.mega_menu_builder') ? '1' : '0' }}"
            ></div>
        </div>
    </form>
</div>
@endsection

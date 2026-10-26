@extends('layouts.admin')

@section('title', __('Page Builder'))
@section('page-title', __('Page Builder'))

@push('styles')
<style>
    body[data-embed='1'] .admin-builder-shell {
        height: 100vh;
    }

    .admin-builder-shell {
        height: calc(100vh - 80px);
        min-height: 720px;
    }
</style>
@endpush

@section('content')
<div class="admin-builder-shell flex flex-col">
    <form id="page-builder-form" method="POST" action="{{ route('admin.pages.update', $page) }}" class="flex flex-col h-full">
        @csrf
        @method('PUT')

        @if(request()->boolean('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <input type="hidden" name="redirect_to" value="builder">

        <input type="hidden" name="title" value="{{ old('title', $page->title) }}">
        <input type="hidden" name="slug" value="{{ old('slug', $page->slug) }}">
        <input type="hidden" name="type" value="{{ old('type', $page->type) }}">
        <input type="hidden" name="variant_key" value="{{ old('variant_key', $page->variant_key) }}">
        <input type="hidden" name="status" id="status" value="{{ old('status', $page->status) }}">

        <input type="hidden" name="html_content" id="html_content" value="{{ old('html_content', $page->html_content) }}">
        <input type="hidden" name="builder_data" id="builder_data" value="{{ old('builder_data', json_encode($pageBuilderData ?? [])) }}">

        <div class="flex-1">
            <div
                id="page-builder-react"
                class="h-full"
                data-builder-input-id="builder_data"
                data-initial-builder='@json($pageBuilderData ?? [])'
                data-upload-url="{{ route('admin.pages.unlayer.upload-image') }}"
                data-ai-url="{{ route('admin.pages.builder.ai-section', $page) }}"
                data-ai-message-url="{{ route('admin.pages.builder.ai.message', $page) }}"
                data-ai-models-url="{{ route('admin.pages.builder.ai.models', $page) }}"
                data-ai-messages-url="{{ route('admin.pages.builder.ai-messages', $page) }}"
                data-page-title="{{ $page->title }}"
                data-settings-url="{{ route('admin.pages.index') }}"
                data-preview-url="{{ route('pages.show', ['slug' => $page->slug, 'preview' => 1]) }}"
                data-render-html-url="{{ route('admin.pages.builder.render-html', $page) }}"
                data-embed="{{ request()->boolean('embed') ? '1' : '0' }}"
                data-templates-list-url="{{ url('admin/page-builder/templates') }}"
                data-templates-fetch-url="{{ url('admin/page-builder/templates-fetch') }}"
                data-templates-product-id="7"
                data-nav-menus='@json($navMenus ?? [])'
                data-lists='@json($lists ?? [])'
                data-mega-menu-builder="{{ config('mailpurse.features.mega_menu_builder') ? '1' : '0' }}"
            ></div>
        </div>
    </form>
</div>
@endsection


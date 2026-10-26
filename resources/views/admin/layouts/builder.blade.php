@extends('layouts.admin')

@section('title', __('Layout Builder'))
@section('page-title', __('Layout Builder'))

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
    <form id="page-builder-form" method="POST" action="{{ route('admin.layouts.update', $layout) }}" class="flex flex-col h-full">
        @csrf
        @method('PUT')

        @if(request()->boolean('embed'))
            <input type="hidden" name="embed" value="1">
        @endif

        <input type="hidden" name="redirect_to" value="builder">

        <input type="hidden" name="name" value="{{ old('name', $layout->name) }}">
        <input type="hidden" name="type" value="{{ old('type', $layout->type) }}">
        <input type="hidden" name="status" id="status" value="{{ old('status', $layout->status) }}">

        <input type="hidden" name="html_content" id="html_content" value="{{ old('html_content', $layout->html_content) }}">
        <input type="hidden" name="builder_data" id="builder_data" value="{{ old('builder_data', json_encode($pageBuilderData ?? [])) }}">

        <div class="flex-1">
            <div
                id="page-builder-react"
                class="h-full"
                data-builder-input-id="builder_data"
                data-initial-builder='@json($pageBuilderData ?? [])'
                data-upload-url="{{ route('admin.pages.unlayer.upload-image') }}"
                data-page-title="{{ $layout->name }}"
                data-settings-url="{{ route('admin.layouts.edit', $layout) }}"
                data-render-html-url="{{ route('admin.layouts.builder.render-html', $layout) }}"
                data-embed="{{ request()->boolean('embed') ? '1' : '0' }}"
                data-mega-menus='@json($megaMenus ?? [])'
                data-mega-menu-builder="{{ config('mailpurse.features.mega_menu_builder') ? '1' : '0' }}"
                data-nav-menus='@json($navMenus ?? [])'
                data-lists='@json($lists ?? [])'
                data-layout-type="{{ $layout->type }}"
                data-templates-list-url="{{ url('admin/page-builder/templates') }}"
                data-templates-fetch-url="{{ url('admin/page-builder/templates-fetch') }}"
                data-templates-product-id="7"
            ></div>
        </div>
    </form>
</div>
@endsection

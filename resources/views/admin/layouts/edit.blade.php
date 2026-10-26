@extends('layouts.admin')

@section('title', $layout->exists ? __('Edit Layout') : __('Create Layout'))
@section('page-title', $layout->exists ? __('Edit Layout') : __('Create Layout'))

@php
$conditions = is_array($layout->display_conditions) ? $layout->display_conditions : ['all_pages' => true];
@endphp

@section('content')
<div class="space-y-6">
    <x-card>
        <form method="POST" action="{{ $layout->exists ? route('admin.layouts.update', $layout) : route('admin.layouts.store') }}" class="space-y-6" id="layout-form">
            @csrf
            @if($layout->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <div class="lg:col-span-8 space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Name') }} *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name', $layout->name) }}" class="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-2">{{ __('Display Conditions') }}</label>
                        <div class="space-y-3 rounded-lg border border-admin-border bg-white/5 p-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="cond_all_pages" name="display_conditions[all_pages]" value="1" {{ old('display_conditions.all_pages', $conditions['all_pages'] ?? false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <label for="cond_all_pages" class="ml-2 block text-sm text-gray-700 dark:text-admin-text-primary">{{ __('Show on all public pages') }}</label>
                            </div>

                            <div id="custom-conditions" class="space-y-3 {{ old('display_conditions.all_pages', $conditions['all_pages'] ?? false) ? 'hidden' : '' }}">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-admin-text-secondary mb-1">{{ __('Include Pages') }}</label>
                                    <select name="display_conditions[include_pages][]" multiple class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" size="4">
                                        @foreach($pages as $p)
                                            @php
                                                $sel = old('display_conditions.include_pages', $conditions['include_pages'] ?? []);
                                            @endphp
                                            <option value="{{ $p->id }}" {{ in_array($p->id, (array) $sel) ? 'selected' : '' }}>{{ $p->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-admin-text-secondary mb-1">{{ __('Exclude Pages') }}</label>
                                    <select name="display_conditions[exclude_pages][]" multiple class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" size="4">
                                        @foreach($pages as $p)
                                            @php
                                                $sel = old('display_conditions.exclude_pages', $conditions['exclude_pages'] ?? []);
                                            @endphp
                                            <option value="{{ $p->id }}" {{ in_array($p->id, (array) $sel) ? 'selected' : '' }}>{{ $p->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-admin-text-secondary mb-1">{{ __('Include Routes') }}</label>
                                    <select name="display_conditions[include_routes][]" multiple class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" size="4">
                                        @foreach($routes as $r)
                                            @php
                                                $sel = old('display_conditions.include_routes', $conditions['include_routes'] ?? []);
                                            @endphp
                                            <option value="{{ $r }}" {{ in_array($r, (array) $sel, true) ? 'selected' : '' }}>{{ $r }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-admin-text-secondary mb-1">{{ __('Exclude Routes') }}</label>
                                    <select name="display_conditions[exclude_routes][]" multiple class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" size="4">
                                        @foreach($routes as $r)
                                            @php
                                                $sel = old('display_conditions.exclude_routes', $conditions['exclude_routes'] ?? []);
                                            @endphp
                                            <option value="{{ $r }}" {{ in_array($r, (array) $sel, true) ? 'selected' : '' }}>{{ $r }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4 space-y-4">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Type') }}</label>
                        <select name="type" id="type" class="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="header" {{ old('type', $layout->type) === 'header' ? 'selected' : '' }}>{{ __('Header') }}</option>
                            <option value="footer" {{ old('type', $layout->type) === 'footer' ? 'selected' : '' }}>{{ __('Footer') }}</option>
                            @if(config('mailpurse.features.mega_menu_builder') || $layout->type === 'megamenu')
                            <option value="megamenu" {{ old('type', $layout->type) === 'megamenu' ? 'selected' : '' }}>{{ __('Megamenu') }}</option>
                            @endif
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Status') }}</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="draft" {{ old('status', $layout->status) === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                            <option value="publish" {{ old('status', $layout->status) === 'publish' ? 'selected' : '' }}>{{ __('Published') }}</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-button type="submit" variant="secondary">{{ __('Save') }}</x-button>
                        @if($layout->exists)
                            <a href="{{ route('admin.layouts.builder', $layout) }}" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700">{{ __('Open Builder') }}</a>
                        @endif
                    </div>
                </div>
            </div>

            <input type="hidden" name="html_content" id="html_content" value="{{ old('html_content', $layout->html_content) }}">
            <input type="hidden" name="builder_data" id="builder_data" value="{{ old('builder_data', json_encode($pageBuilderData ?? [])) }}">
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const allPagesCheckbox = document.getElementById('cond_all_pages');
    const customConditions = document.getElementById('custom-conditions');

    if (allPagesCheckbox && customConditions) {
        allPagesCheckbox.addEventListener('change', function () {
            customConditions.classList.toggle('hidden', allPagesCheckbox.checked);
        });
    }
})();
</script>
@endpush

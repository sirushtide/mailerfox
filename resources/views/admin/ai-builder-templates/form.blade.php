@extends('layouts.admin')

@section('title', $template->exists ? __('Edit Template') : __('Create Template'))
@section('page-title', $template->exists ? __('Edit Template') : __('Create Template'))

@section('breadcrumbs')
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
        <li>
            <a href="{{ route('admin.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">
                {{ __('Dashboard') }}
            </a>
        </li>
        <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
        <li>
            <a href="{{ route('admin.ai-projects.ai-builder-templates.index') }}" class="font-medium transition hover:text-admin-text-primary">
                {{ __('Templates') }}
            </a>
        </li>
        <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
        <li class="font-medium text-admin-text-primary truncate">{!! $template->exists ? __('Edit Template') : __('Create Template') !!}</li>
    </ol>
@endsection

@section('content')
<div class="space-y-4">
    <x-card>
        <form method="POST" action="{{ $template->exists ? route('admin.ai-projects.ai-builder-templates.update', $template) : route('admin.ai-projects.ai-builder-templates.store') }}" class="space-y-4">
            @csrf
            @if($template->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Template ID') }}</label>
                    <input type="text" name="template_id" id="template_id" value="{{ old('template_id', $template->template_id) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        placeholder="hero_01">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Unique slug: lowercase letters, numbers, underscore, dash.') }}</p>
                    @error('template_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Category') }}</label>
                    <select name="category" id="category" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category', $template->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name', $template->name) }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Description') }}</label>
                <textarea name="description" id="description" rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">{{ old('description', $template->description) }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tags (comma separated)') }}</label>
                    <input type="text" name="tags" id="tags" value="{{ old('tags', is_array($template->tags) ? implode(', ', $template->tags) : '') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        placeholder="saas, gradient, two-cta">
                    @error('tags')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="thumbnail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Thumbnail URL') }}</label>
                    <input type="text" name="thumbnail" id="thumbnail" value="{{ old('thumbnail', $template->thumbnail) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('thumbnail')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Sort Order') }}</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $template->sort_order ?? 0) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    @error('sort_order')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center h-full pt-6">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Active') }}</span>
                    </label>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="component_source" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Component Source (JSX)') }}</label>
                    <button type="button" id="autoGenerateBtn"
                        class="inline-flex items-center gap-1.5 rounded-md bg-[#1E5FEA] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#1a52cc] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3v4M3 5h4M6 17v4M4 19h4M13 3l3.5 7.5L24 14l-7.5 3.5L13 25l-3.5-7.5L2 14l7.5-3.5L13 3z"/></svg>
                        {{ __('Auto-generate Fields & Defaults') }}
                    </button>
                </div>
                <textarea name="component_source" id="component_source" rows="14" required
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm font-mono">{{ old('component_source', $template->component_source) }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Use [ComponentName] as the component name placeholder. Export default must use this name.') }}</p>
                @error('component_source')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="fields" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Fields (JSON)') }}</label>
                    <textarea name="fields" id="fields" rows="10" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm font-mono">{{ old('fields', $template->fields ? json_encode($template->fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                    @error('fields')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="defaults" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Defaults (JSON)') }}</label>
                    <textarea name="defaults" id="defaults" rows="10" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm font-mono">{{ old('defaults', $template->defaults ? json_encode($template->defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                    @error('defaults')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-button href="{{ route('admin.ai-projects.ai-builder-templates.index') }}" variant="secondary">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="primary">{{ $template->exists ? __('Update Template') : __('Create Template') }}</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const btn = document.getElementById('autoGenerateBtn');
    if (!btn) return;

    btn.addEventListener('click', function() {
        const jsx = document.getElementById('component_source').value;
        if (!jsx.trim()) {
            alert('Paste JSX component source first.');
            return;
        }

        const result = parseJsxToProps(jsx);
        document.getElementById('fields').value = JSON.stringify(result.fields, null, 2);
        document.getElementById('defaults').value = JSON.stringify(result.defaults, null, 2);
    });

    function parseJsxToProps(jsx) {
        const fields = [];
        const defaults = {};
        const fieldKeys = new Set();

        // 1. Try to extract destructured props from function signature:
        //    function [ComponentName]({ heading, body, items }) {  OR  ({ heading, body, items }) => {
        const sigMatch = jsx.match(/function\s+\[?ComponentName\]?\s*\(\s*\{([^}]+)\}\s*\)/);
        const arrowMatch = jsx.match(/\(\s*\{([^}]+)\}\s*\)\s*=>/);
        const destructuredRaw = sigMatch ? sigMatch[1] : (arrowMatch ? arrowMatch[1] : null);

        let topLevelKeys = [];
        if (destructuredRaw) {
            // Split by comma, handle nested destructuring like { items = [] }
            topLevelKeys = destructuredRaw
                .split(',')
                .map(s => s.trim().split('=')[0].trim().replace(/\[[^\]]*\]/g, '').trim())
                .filter(s => /^[a-zA-Z_$][a-zA-Z0-9_$]*$/.test(s));
        }

        // 2. If no destructuring found, scan for {variable} usage in JSX (not {item.field} or {object.method()})
        if (topLevelKeys.length === 0) {
            const singleVarMatches = jsx.matchAll(/\{([a-zA-Z_$][a-zA-Z0-9_$]*)\}/g);
            for (const m of singleVarMatches) {
                const v = m[1];
                if (!['true', 'false', 'null', 'undefined', 'props', 'defaults', 'React', 'item', 'col', 'columns', 'i', 'ii', 'ci', 'key'].includes(v)) {
                    topLevelKeys.push(v);
                }
            }
            topLevelKeys = [...new Set(topLevelKeys)];
        }

        // 3. Detect array props by looking for .map((itemName, ...) => or .map((itemName) =>
        const arrayProps = new Map(); // propName -> itemName
        for (const key of topLevelKeys) {
            const mapPattern = new RegExp(key + '\\s*(?:\\?\\.)?\\.map\\s*\\(\\s*\\(\\s*([a-zA-Z_$][a-zA-Z0-9_$]*)');
            const mapMatch = jsx.match(mapPattern);
            if (mapMatch) {
                arrayProps.set(key, mapMatch[1]);
            }
        }

        // 4. For each array prop, find subfields by scanning for {itemName.fieldName}
        const arraySubfields = new Map(); // propName -> [{key, label, type}]
        for (const [propName, itemName] of arrayProps) {
            const subfieldKeys = new Set();
            const subMatch = jsx.matchAll(new RegExp('\\{' + itemName + '\\.([a-zA-Z_$][a-zA-Z0-9_$]*)', 'g'));
            for (const m of subMatch) {
                subfieldKeys.add(m[1]);
            }
            const subs = [...subfieldKeys].map(k => ({
                key: k,
                label: k.charAt(0).toUpperCase() + k.slice(1).replace(/([A-Z])/g, ' $1').trim(),
                type: guessFieldType(k)
            }));
            arraySubfields.set(propName, subs);
        }

        // 5. Build fields and defaults
        for (const key of topLevelKeys) {
            if (fieldKeys.has(key)) continue;
            fieldKeys.add(key);

            if (arrayProps.has(key)) {
                const subs = arraySubfields.get(key) || [];
                fields.push({
                    key: key,
                    label: key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1').trim(),
                    type: 'array',
                    subfields: subs
                });
                // Build 2 example items for defaults
                const exampleItem = {};
                for (const sub of subs) {
                    exampleItem[sub.key] = getPlaceholderValue(sub.key, sub.type);
                }
                defaults[key] = [exampleItem, { ...exampleItem }];
                // Vary second item slightly
                for (const sub of subs) {
                    if (typeof defaults[key][1][sub.key] === 'string' && sub.type === 'text') {
                        defaults[key][1][sub.key] = defaults[key][1][sub.key] + ' 2';
                    }
                }
            } else {
                const type = guessFieldType(key);
                fields.push({
                    key: key,
                    label: key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1').trim(),
                    type: type
                });
                defaults[key] = getPlaceholderValue(key, type);
            }
        }

        return { fields, defaults };
    }

    function guessFieldType(key) {
        const lower = key.toLowerCase();
        if (lower.includes('image') || lower.includes('photo') || lower.includes('avatar') || lower.includes('logo') || lower.includes('thumbnail') || lower.includes('src')) return 'image';
        if (lower.includes('href') || lower.includes('url') || lower.includes('link')) return 'url';
        if (lower.includes('icon')) return 'text';
        if (lower === 'items' || lower === 'features' || lower === 'testimonials' || lower === 'pricing' || lower === 'stats' || lower === 'cards' || lower === 'list') return 'array';
        if (lower.includes('body') || lower.includes('description') || lower.includes('text') || lower.includes('content') || lower.includes('paragraph')) return 'textarea';
        return 'text';
    }

    function getPlaceholderValue(key, type) {
        const lower = key.toLowerCase();
        if (type === 'image') return 'https://images.unsplash.com/photo-1551434678-e076c223a692?w=400';
        if (type === 'url') return '#';
        if (lower === 'heading' || lower === 'title') return 'Your Heading Here';
        if (lower === 'subheading' || lower === 'subtitle') return 'Your subheading text goes here';
        if (lower === 'body' || lower === 'description') return 'Describe your offering in a compelling way.';
        if (lower.includes('cta') || lower.includes('button')) return 'Get Started';
        if (lower.includes('icon')) return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/></svg>';
        if (lower.includes('alt')) return 'Image description';
        if (lower === 'items' || lower === 'features' || lower === 'cards' || lower === 'list') {
            return [{ title: 'Item 1', description: 'Description 1' }, { title: 'Item 2', description: 'Description 2' }];
        }
        // Generic: prettify the key
        return key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1').trim();
    }
})();
</script>
@endpush

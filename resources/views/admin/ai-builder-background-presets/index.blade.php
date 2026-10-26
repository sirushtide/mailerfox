@extends('layouts.admin')

@section('title', __('AI Builder Background Presets'))
@section('page-title', __('AI Builder Background Presets'))

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            {{ isset($editing) && $editing ? __('Edit Preset') : __('Add New Preset') }}
        </h3>

        @php
            $preset = $editing ?? old() ?? [
                'preset_id' => '',
                'name' => '',
                'category' => 'gradflow',
                'description' => '',
                'config' => ['type' => 'gradflow', 'config' => ['color1' => '#000000', 'color2' => '#333333', 'color3' => '#666666', 'speed' => 0.5, 'scale' => 1.2, 'type' => 'silk', 'noise' => 0.1]],
                'preview_image' => '',
                'sort_order' => 0,
                'is_active' => true,
            ];
            $configJson = isset($preset['config']) ? json_encode($preset['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '';
        @endphp

        <form method="POST" action="{{ isset($editing) && $editing ? route('admin.ai-builder-background-presets.update', $preset['preset_id']) : route('admin.ai-builder-background-presets.store') }}">
            @csrf
            @if(isset($editing) && $editing)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preset ID (slug)</label>
                    <input type="text" name="preset_id" value="{{ $preset['preset_id'] ?? '' }}" pattern="[a-z0-9-]+" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        placeholder="cosmic-silk">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" name="name" value="{{ $preset['name'] ?? '' }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <select name="category" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        <option value="grid" {{ ($preset['category'] ?? '') === 'grid' ? 'selected' : '' }}>grid</option>
                        <option value="grid-fade" {{ ($preset['category'] ?? '') === 'grid-fade' ? 'selected' : '' }}>grid-fade</option>
                        <option value="dots" {{ ($preset['category'] ?? '') === 'dots' ? 'selected' : '' }}>dots</option>
                        <option value="mesh" {{ ($preset['category'] ?? '') === 'mesh' ? 'selected' : '' }}>mesh</option>
                        <option value="blobs" {{ ($preset['category'] ?? '') === 'blobs' ? 'selected' : '' }}>blobs</option>
                        <option value="image" {{ ($preset['category'] ?? '') === 'image' ? 'selected' : '' }}>image</option>
                        <option value="gradflow" {{ ($preset['category'] ?? '') === 'gradflow' ? 'selected' : '' }}>gradflow</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ $preset['sort_order'] ?? 0 }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <input type="text" name="description" value="{{ $preset['description'] ?? '' }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Config JSON</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                        Must contain a top-level "type" key and nested "config" if needed. Example: {"type":"gradflow","config":{"color1":"#000","color2":"#333","speed":0.5}}
                    </p>
                    <textarea name="config_json" rows="8" required
                        class="font-mono mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">{{ old('config_json', $configJson) }}</textarea>
                    @error('config_json')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preview Image URL</label>
                    <input type="text" name="preview_image" value="{{ $preset['preview_image'] ?? '' }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ ($preset['is_active'] ?? true) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600">
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    {{ isset($editing) && $editing ? __('Update Preset') : __('Add Preset') }}
                </button>
                <a href="{{ route('admin.ai-builder-background-presets.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Sort</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Active</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($presets as $preset)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $preset['preset_id'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $preset['name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $preset['category'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $preset['sort_order'] }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($preset['is_active'] ?? true)
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-200">Active</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('admin.ai-builder-background-presets.edit', $preset['preset_id']) }}"
                                class="mr-3 text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">Edit</a>
                            <form method="POST" action="{{ route('admin.ai-builder-background-presets.destroy', $preset['preset_id']) }}" class="inline" onsubmit="return confirm('{{ __('Delete this preset?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No presets found. Add one above.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', __('AI Builder Templates'))
@section('page-title', __('AI Builder Templates'))

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between lg:gap-0">
        <form method="GET" action="{{ route('admin.ai-projects.ai-builder-templates.index') }}" class="w-full lg:w-auto flex flex-col gap-2 lg:flex-row lg:items-center">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                placeholder="{{ __('Search by name/ID') }}"
                class="block w-full lg:w-72 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
            >
            <select name="category" class="block w-full lg:w-48 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                <option value="">{{ __('All categories') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ ucfirst(str_replace('-', ' ', $cat)) }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary" class="w-full lg:w-auto">{{ __('Search') }}</x-button>
            <x-button href="{{ route('admin.ai-projects.ai-builder-templates.index') }}" variant="secondary" class="w-full lg:w-auto">{{ __('Reset') }}</x-button>
        </form>

        @admincan('admin.ai_builder_templates.create')
            <x-button href="{{ route('admin.ai-projects.ai-builder-templates.create') }}" variant="primary" class="w-full lg:w-auto">{{ __('Create Template') }}</x-button>
        @endadmincan
    </div>

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between lg:gap-0">
        <div class="flex flex-wrap items-center gap-2">
            @admincan('admin.ai_builder_templates.edit')
                <a href="{{ route('admin.ai-projects.ai-builder-templates.export') }}" id="exportBtn"
                   class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    {{ __('Export') }}
                </a>
                <button type="button" id="importBtn"
                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    {{ __('Import') }}
                </button>
                <form method="POST" action="{{ route('admin.ai-projects.ai-builder-templates.activate-all') }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm(@json(__('Activate all templates?')));"
                        class="inline-flex items-center gap-1.5 rounded-md border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/30 px-3 py-2 text-sm font-medium text-green-700 dark:text-green-400 shadow-sm hover:bg-green-100 dark:hover:bg-green-900/50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        {{ __('Activate All') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.ai-projects.ai-builder-templates.deactivate-all') }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm(@json(__('Deactivate all templates?')));"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        {{ __('Deactivate All') }}
                    </button>
                </form>
            @endadmincan
        </div>
    </div>

    {{-- Import Modal --}}
    <div id="importModal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity" onclick="document.getElementById('importModal').classList.add('hidden')"></div>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-lg bg-white dark:bg-gray-800 shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Import Templates') }}</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="document.getElementById('importModal').classList.add('hidden')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.ai-projects.ai-builder-templates.import') }}" enctype="multipart/form-data" class="p-4 space-y-4">
                    @csrf
                    <div>
                        <label for="importFile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('JSON File') }}</label>
                        <input type="file" name="file" id="importFile" accept=".json,application/json" required
                            class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-200
                                file:mr-4 file:rounded-md file:border-0
                                file:bg-primary-50 file:px-4 file:py-2
                                file:text-sm file:font-medium file:text-primary-700
                                hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Upload a previously exported JSON file. Existing templates with the same ID will be updated.') }}</p>
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                            class="rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('Cancel') }}</button>
                        <button type="submit"
                            class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Template ID') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Category') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Active') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($templates as $template)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-mono text-gray-700 dark:text-gray-200">{{ $template->template_id }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $template->name }}
                                @if($template->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit((string) $template->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ ucfirst(str_replace('-', ' ', $template->category)) }}</td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $template->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $template->is_active ? __('Active') : __('Disabled') }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    @admincan('admin.ai_builder_templates.edit')
                                        <x-button href="{{ route('admin.ai-projects.ai-builder-templates.edit', $template) }}" variant="table" size="action" :pill="true" class="p-2" title="{{ __('Edit') }}"><x-lucide name="pencil" class="h-4 w-4" /><span class="sr-only">{{ __('Edit') }}</span></x-button>
                                    @endadmincan
                                    @admincan('admin.ai_builder_templates.delete')
                                        <form method="POST" action="{{ route('admin.ai-projects.ai-builder-templates.destroy', $template) }}" class="inline" onsubmit="return confirm(@json(__('Delete template?')));">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="table-danger" size="action" :pill="true" class="p-2" title="{{ __('Delete') }}"><x-lucide name="trash-2" class="h-4 w-4" /><span class="sr-only">{{ __('Delete') }}</span></x-button>
                                        </form>
                                    @endadmincan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No templates found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $templates->links() }}</div>
        @endif
    </x-card>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const importBtn = document.getElementById('importBtn');
    const importModal = document.getElementById('importModal');
    if (importBtn && importModal) {
        importBtn.addEventListener('click', function() {
            importModal.classList.remove('hidden');
        });
    }
});
</script>
@endpush

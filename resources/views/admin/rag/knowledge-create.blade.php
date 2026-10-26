@extends('layouts.admin')

@section('title', __('Add Knowledge Base'))

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <div class="mb-6">
        <a href="{{ route('admin.rag.knowledge.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-admin-text-secondary hover:text-[#1E5FEA] transition-colors mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            {{ __('Back to Knowledge Base') }}
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ __('Add Knowledge Base') }}</h1>
        <p class="text-sm text-gray-500 dark:text-admin-text-secondary mt-1">{{ __('This knowledge will be available to all customers using RAG features.') }}</p>
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.rag.knowledge.store') }}" enctype="multipart/form-data" class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-6 space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Name') }} <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent" placeholder="e.g. Platform Documentation">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Type') }} <span class="text-red-500">*</span></label>
            <select name="type" required class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                @foreach(['general' => 'General', 'brand' => 'Brand Guidelines', 'product' => 'Product Info', 'faq' => 'FAQ', 'campaign' => 'Campaign Context'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('type') === $val)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Description') }}</label>
            <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent" placeholder="Brief description">{{ old('description') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Source Type') }} <span class="text-red-500">*</span></label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="source_type" value="text" @checked(old('source_type', 'text') === 'text') class="text-[#1E5FEA] focus:ring-[#1E5FEA]" onchange="toggleSource(this.value)">
                    <span class="text-sm text-gray-700 dark:text-admin-text-primary">{{ __('Paste Text') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="source_type" value="file" @checked(old('source_type') === 'file') class="text-[#1E5FEA] focus:ring-[#1E5FEA]" onchange="toggleSource(this.value)">
                    <span class="text-sm text-gray-700 dark:text-admin-text-primary">{{ __('Upload File') }}</span>
                </label>
            </div>
        </div>
        <div id="text-source">
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Text Content') }}</label>
            <textarea name="text_content" rows="10" class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent font-mono" placeholder="Paste content here...">{{ old('text_content') }}</textarea>
        </div>
        <div id="file-source" class="hidden">
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Upload File') }}</label>
            <input type="file" name="file" accept=".txt,.md,.csv,.json,.html,.htm,.pdf" class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm">
            <p class="text-xs text-gray-400 mt-1">{{ __('Supported: TXT, MD, CSV, JSON, HTML, PDF. Max 10MB.') }}</p>
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors">{{ __('Create Knowledge Base') }}</button>
            <a href="{{ route('admin.rag.knowledge.index') }}" class="px-5 py-2 border border-gray-300 dark:border-admin-border text-gray-700 dark:text-admin-text-primary text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
<script>
function toggleSource(val) {
    document.getElementById('text-source').classList.toggle('hidden', val !== 'text');
    document.getElementById('file-source').classList.toggle('hidden', val !== 'file');
}
</script>
@endsection

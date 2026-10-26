@extends('layouts.customer')

@section('title', __('AI Campaign Writer'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ __('AI Campaign Writer') }}</h1>
        <p class="text-sm text-gray-500 dark:text-admin-text-secondary mt-1">{{ __('Generates email content using your past campaigns and knowledge base as context.') }}</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Input form --}}
        <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-6">
            <h2 class="font-semibold text-gray-900 dark:text-admin-text-primary mb-4">{{ __('What do you want to write?') }}</h2>
            <form method="POST" action="{{ wsRoute('customer.rag.campaign.generate') }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Prompt') }} <span class="text-red-500">*</span></label>
                    <textarea name="prompt" rows="8" required class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent" placeholder="e.g. Write a welcome email for new subscribers who signed up for our SaaS newsletter. Mention our key features and include a 20% discount code WELCOME20.">{{ old('prompt') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('AI Provider') }}</label>
                    <select name="provider" class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                        @foreach($providers as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" :disabled="loading" class="w-full px-5 py-2.5 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors disabled:opacity-50">
                    <span x-show="!loading">{{ __('Generate Email Content') }}</span>
                    <span x-show="loading" x-cloak>{{ __('Generating...') }}</span>
                </button>
            </form>
        </div>

        {{-- Output --}}
        <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-6">
            <h2 class="font-semibold text-gray-900 dark:text-admin-text-primary mb-4">{{ __('Generated Content') }}</h2>
            @if(session('generated_text'))
                <div class="prose prose-sm max-w-none dark:prose-invert">
                    <pre class="whitespace-pre-wrap text-sm text-gray-700 dark:text-admin-text-primary bg-gray-50 dark:bg-white/5 p-4 rounded-lg">{{ session('generated_text') }}</pre>
                </div>
                <div class="mt-4 flex gap-2">
                    <button onclick="copyToClipboard({{ json_encode(session('generated_text')) }})" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-admin-border rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 text-gray-700 dark:text-admin-text-primary transition-colors">{{ __('Copy') }}</button>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-100 dark:bg-white/5 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/></svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Your generated email will appear here.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}
</script>
@endsection

@extends('layouts.customer')

@section('title', __('AI Insights'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ __('AI Insights') }}</h1>
        <p class="text-sm text-gray-500 dark:text-admin-text-secondary mt-1">{{ __('Ask questions about your campaign performance, subscribers, and trends. AI analyzes your real data.') }}</p>
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
        {{-- Input --}}
        <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-6">
            <h2 class="font-semibold text-gray-900 dark:text-admin-text-primary mb-4">{{ __('Ask about your data') }}</h2>
            <form method="POST" action="{{ wsRoute('customer.rag.insights.ask') }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Question') }} <span class="text-red-500">*</span></label>
                    <textarea name="query" rows="5" required class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent" placeholder="e.g. Which campaigns had the highest open rate? What subject lines perform best?">{{ old('query') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('AI Provider') }}</label>
                    <select name="provider" class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                        @foreach($providers as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Quick suggestions --}}
                <div class="flex flex-wrap gap-2">
                    @foreach(['What is my average open rate?', 'Which campaign performed best?', 'How many active subscribers do I have?', 'What is my bounce rate trend?'] as $suggestion)
                        <button type="button" onclick="document.querySelector('textarea[name=query]').value = {{ json_encode($suggestion) }}" class="px-2.5 py-1 text-xs bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-admin-text-secondary rounded-full hover:bg-[#1E5FEA]/10 hover:text-[#1E5FEA] transition-colors">{{ __($suggestion) }}</button>
                    @endforeach
                </div>

                <button type="submit" :disabled="loading" class="w-full px-5 py-2.5 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors disabled:opacity-50">
                    <span x-show="!loading">{{ __('Get Insights') }}</span>
                    <span x-show="loading" x-cloak>{{ __('Analyzing...') }}</span>
                </button>
            </form>
        </div>

        {{-- Output --}}
        <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-6">
            <h2 class="font-semibold text-gray-900 dark:text-admin-text-primary mb-4">{{ __('AI Analysis') }}</h2>
            @if(session('generated_text'))
                <div class="text-sm text-gray-700 dark:text-admin-text-primary bg-gray-50 dark:bg-white/5 p-4 rounded-lg whitespace-pre-wrap">{{ session('generated_text') }}</div>
            @else
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-100 dark:bg-white/5 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Ask a question to get AI-powered insights from your data.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

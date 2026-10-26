@extends('layouts.customer')

@section('title', __('AI Knowledge Base'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ __('AI Knowledge Base') }}</h1>
            <p class="text-sm text-gray-500 dark:text-admin-text-secondary mt-1">{{ __('Upload documents, FAQs, and brand guidelines to power AI-assisted content generation.') }}</p>
        </div>
        <a href="{{ wsRoute('customer.rag.knowledge.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            {{ __('Add Knowledge') }}
        </a>
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

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($knowledgeBases as $kb)
            <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-5">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 uppercase">{{ $kb->type }}</span>
                        @if($kb->customer_id === null)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">{{ __('Global') }}</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ wsRoute('customer.rag.knowledge.destroy', $kb) }}" onsubmit="return confirm('{{ __('Delete this knowledge base?') }}')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" @if($kb->customer_id === null) disabled @endif>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </form>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-admin-text-primary mb-1">{{ $kb->name }}</h3>
                @if($kb->description)
                    <p class="text-sm text-gray-500 dark:text-admin-text-secondary mb-3 line-clamp-2">{{ $kb->description }}</p>
                @endif
                <div class="flex items-center gap-4 text-xs text-gray-400 dark:text-admin-text-secondary">
                    <span>{{ $kb->total_chunks }} chunks</span>
                    <span>{{ $kb->total_words }} words</span>
                    <span class="inline-flex items-center gap-1">
                        @php
                            $statusColor = match($kb->status) {
                                'active' => 'bg-green-500',
                                'failed' => 'bg-red-500',
                                default => 'bg-yellow-500',
                            };
                        @endphp
                        <span class="w-2 h-2 rounded-full {{ $statusColor }}"></span>
                        {{ $kb->status }}
                    </span>
                </div>
                @if($kb->status === 'failed' && $kb->error_message)
                    <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $kb->error_message }}</p>
                @endif
            </div>
        @empty
            <div class="col-span-full text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-admin-card mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-admin-text-primary mb-1">{{ __('No knowledge bases yet') }}</h3>
                <p class="text-sm text-gray-500 dark:text-admin-text-secondary mb-4">{{ __('Upload your first document to get started.') }}</p>
                <a href="{{ wsRoute('customer.rag.knowledge.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors">
                    {{ __('Add Knowledge') }}
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection

@extends('layouts.customer')

@section('title', __('AI Agents'))
@section('page-title', __('AI Agents'))

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ __('AI Agents') }}</h1>
            <p class="text-sm text-gray-500 dark:text-admin-text-secondary mt-1">{{ __('Manage trainable chatbot agents and their knowledge sources.') }}</p>
        </div>
        <a href="{{ wsRoute('customer.ai-agents.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            {{ __('Create Agent') }}
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

    @if($agents->isEmpty())
        <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#1E5FEA] to-[#6366f1] flex items-center justify-center text-white mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-admin-text-primary mb-1">{{ __('No AI agents yet') }}</h3>
            <p class="text-sm text-gray-500 dark:text-admin-text-secondary mb-4">{{ __('Create your first AI agent and train it on your documents, FAQs, and products.') }}</p>
            <a href="{{ wsRoute('customer.ai-agents.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors">
                {{ __('Create Agent') }}
            </a>
        </div>
    @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($agents as $agent)
                @php
                    $kbCount = $agent->knowledgeBases->count();
                @endphp
                <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-4">
                        @if($agent->imageUrl())
                            <img src="{{ $agent->imageUrl() }}" alt="" class="w-14 h-14 rounded-xl object-cover flex-shrink-0">
                        @else
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-lg font-bold text-white flex-shrink-0" style="background-color: {{ $agent->avatar_color }}">
                                {{ strtoupper(substr($agent->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-admin-text-primary truncate">{{ $agent->name }}</h3>
                                @if($agent->status === 'active')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400">{{ __('Active') }}</span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400">{{ __('Inactive') }}</span>
                                @endif
                            </div>
                            @if($agent->description)
                                <p class="text-sm text-gray-500 dark:text-admin-text-secondary line-clamp-2 mb-2">{{ $agent->description }}</p>
                            @endif
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $kbCount }} {{ __('knowledge source') }}{{ $kbCount === 1 ? '' : 's' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mt-5 pt-4 border-t border-gray-100 dark:border-white/10">
                        @if($agent->status === 'active')
                            <a href="{{ wsRoute('customer.ai-agents.chat', $agent) }}" class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                                {{ __('Chat') }}
                            </a>
                        @else
                            <button disabled class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-gray-200 text-gray-500 text-sm font-medium rounded-lg cursor-not-allowed">
                                {{ __('Inactive') }}
                            </button>
                        @endif
                        <a href="{{ wsRoute('customer.ai-agents.edit', $agent) }}" class="inline-flex items-center justify-center gap-2 px-3 py-2 border border-gray-300 dark:border-admin-border text-gray-700 dark:text-admin-text-primary text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors" title="{{ __('Edit agent') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            {{ __('Edit') }}
                        </a>
                        <form method="POST" action="{{ wsRoute('customer.ai-agents.destroy', $agent) }}" onsubmit="return confirm('{{ __('Delete this agent?') }}')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center p-2 text-gray-400 hover:text-red-600 border border-gray-300 dark:border-admin-border rounded-lg hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors" title="{{ __('Delete agent') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

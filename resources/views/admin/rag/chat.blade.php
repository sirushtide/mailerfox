@extends('layouts.admin')

@section('title', __('AI Chat'))

@section('content')
@php
    $ragConfig = [
        'providerModels' => $providerModels,
        'initialConversationId' => $activeConversation?->id,
        'askUrl' => route('admin.rag.chat.ask'),
        'indexUrl' => route('admin.rag.chat.index'),
        'feedbackUrl' => route('admin.rag.chat.feedback'),
        'csrfToken' => csrf_token(),
        'noModelsText' => __('No models'),
    ];
@endphp
<div class="p-6" x-data='ragChat(@json($ragConfig))' x-init="init()">
    <div class="grid lg:grid-cols-[280px_1fr] gap-6 max-w-7xl mx-auto">
        <div class="hidden lg:block">
            <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Conversations') }}</h3>
                    <button @click="newConversation()" class="text-[#1E5FEA] hover:text-[#1a54d4]" title="{{ __('New chat') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    </button>
                </div>
                <div class="space-y-1 max-h-[500px] overflow-y-auto">
                    @forelse($conversations as $conv)
                        <a href="{{ route('admin.rag.chat.index', ['conversation' => $conv->id]) }}" class="block px-3 py-2 rounded-lg text-sm {{ ($activeConversation?->id ?? 0) === $conv->id ? 'bg-[#1E5FEA]/10 text-[#1E5FEA]' : 'text-gray-600 dark:text-admin-text-secondary hover:bg-gray-50 dark:hover:bg-white/5' }} transition-colors truncate">{{ $conv->title ?: __('Untitled') }}</a>
                    @empty
                        <p class="text-xs text-gray-400 px-3 py-2">{{ __('No conversations yet') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl flex flex-col" style="min-height: 600px;">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-admin-border">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#1E5FEA] to-[#6366f1] flex items-center justify-center text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('AI Knowledge Assistant') }}</h2>
                        <p class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('Powered by global knowledge base') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <select x-model="provider" @change="updateModel()" class="text-xs px-2 py-1.5 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-700 dark:text-admin-text-primary">
                        @foreach($providers as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select x-model="model" x-html="getModelOptions()" class="text-xs px-2 py-1.5 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-700 dark:text-admin-text-primary min-w-[120px]">
                        <option value="" x-text="'{{ __('Select model') }}'">{{ __('Select model') }}</option>
                    </select>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages" x-ref="messages">
                @if($activeConversation && $activeConversation->messages)
                    @foreach($activeConversation->messages as $msg)
                        <div class="flex flex-col {{ $msg['role'] === 'user' ? 'items-end' : 'items-start' }}">
                            <div class="max-w-[80%] rounded-2xl px-4 py-3 {{ $msg['role'] === 'user' ? 'bg-[#1E5FEA] text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-900 dark:text-admin-text-primary' }}">
                                @if($msg['role'] === 'user')
                                    <p class="text-sm whitespace-pre-wrap">{{ $msg['content'] }}</p>
                                @else
                                    <div class="text-sm prose prose-sm max-w-none">{!! $msg['content'] !!}</div>
                                @endif
                            </div>
                            @if($msg['role'] !== 'user' && $activeConversation)
                                <div class="chat-feedback-row mt-2 flex gap-2" data-feedback-selected="{{ $msg['feedback'] ?? '' }}">
                                    <button type="button" class="chat-feedback-btn p-1 rounded transition-colors focus:outline-none {{ ($msg['feedback'] ?? '') === 'like' ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' }}" data-chat-feedback="like" data-conversation-id="{{ $activeConversation->id }}" data-message-index="{{ $loop->index }}" aria-label="Helpful">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>
                                    </button>
                                    <button type="button" class="chat-feedback-btn p-1 rounded transition-colors focus:outline-none {{ ($msg['feedback'] ?? '') === 'dislike' ? 'text-red-500' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' }}" data-chat-feedback="dislike" data-conversation-id="{{ $activeConversation->id }}" data-message-index="{{ $loop->index }}" aria-label="Not helpful">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/></svg>
                                    </button>
                                    <button type="button" class="chat-feedback-btn p-1 rounded transition-colors focus:outline-none text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300" data-chat-copy data-copy-content="{{ $msg['content'] }}" aria-label="Copy">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-[#1E5FEA] to-[#6366f1] mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-admin-text-primary mb-1">{{ __('Ask me anything') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('I can answer questions based on the global knowledge base.') }}</p>
                    </div>
                @endif
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-admin-border">
                {{-- Loading indicator --}}
                <div x-show="loading" x-cloak class="flex justify-start mb-3">
                    <div class="bg-gray-100 dark:bg-white/5 rounded-2xl px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 300ms"></div>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="sendQuery()" class="flex gap-2">
                    <input type="hidden" x-model="conversationId">
                    <textarea x-model="query" @keydown.enter.prevent="sendQuery()" rows="1" class="flex-1 px-4 py-3 border border-gray-300 dark:border-admin-border rounded-xl bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent resize-none" placeholder="{{ __('Ask a question...') }}" :disabled="loading"></textarea>
                    <button type="submit" :disabled="loading || !query.trim()" class="px-4 py-3 bg-[#1E5FEA] text-white rounded-xl hover:bg-[#1a54d4] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

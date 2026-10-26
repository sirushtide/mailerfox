@extends('layouts.customer')

@section('title', __('AI Chatbot'))
@section('page-title', '')
@section('content-padding-classes', 'p-0 overflow-hidden')
@section('content-wrapper-attributes', 'style="flex:1;display:flex;flex-direction:column;"')
@section('force-sidebar-collapsed', '1')

@section('content')
@php
    $initialConversationId = $activeConversation?->id;
    $initialProvider = $aiAgent ? ($aiAgent->provider ?? 'chatgpt') : 'chatgpt';
    $initialModel = $aiAgent ? ($aiAgent->model ?: ($providerModels[$initialProvider][0] ?? '')) : '';
    if (!$initialModel && !empty($providerModels[$initialProvider])) {
        $initialModel = $providerModels[$initialProvider][0];
    }

    $conversationsList = $conversations->map(function ($c) {
        // MySQL path: preview comes from DB raw expression; non-MySQL: extract from messages
        $preview = $c->preview ?? '';
        if (!$preview) {
            $messages = is_array($c->messages) ? $c->messages : [];
            $last = $messages ? end($messages) : null;
            $preview = $last['content'] ?? '';
        }

        return [
            'id' => $c->id,
            'title' => $c->title ?: __('Untitled'),
            'preview' => $preview ? Str::limit(strip_tags($preview), 60) : '',
            'updated_at' => $c->updated_at?->diffForHumans(),
        ];
    })->all();

    $chatConfig = [
        'agentId' => $aiAgent?->id,
        'initialConversationId' => $initialConversationId,
        'askUrl' => wsRoute('customer.ai-agents.ask'),
        'askStreamUrl' => wsRoute('customer.ai-agents.ask.stream'),
        'feedbackUrl' => $aiAgent ? wsRoute('customer.ai-agents.chat.feedback', $aiAgent) : '',
        'conversationUrl' => $aiAgent ? wsRoute('customer.ai-agents.conversation', ['aiAgent' => $aiAgent->id, 'conversation' => '__ID__']) : '',
        'conversationsListUrl' => $aiAgent ? wsRoute('customer.ai-agents.conversations.list', $aiAgent) : '',
        'csrfToken' => csrf_token(),
        'providerModels' => $providerModels,
        'initialProvider' => $initialProvider,
        'initialModel' => $initialModel,
        'messages' => [],
        'conversations' => $conversationsList,
        'hasMoreConversations' => $hasMoreConversations ?? false,
    ];
@endphp

<div
    class="flex bg-white dark:bg-[#111]"
    style="height:calc(100vh - 48px)"
    x-data="agentChat(@js($chatConfig))"
    x-init="init()"
>
    @if($aiAgent)
    {{-- Left sidebar --}}
    <aside class="w-64 flex-shrink-0 flex flex-col border-r border-gray-100 dark:border-white/10 bg-white dark:bg-[#0f0f0f] h-full">
        <div class="p-3 border-b border-gray-100 dark:border-white/10">
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="flex items-center gap-2 min-w-0">
                    @if($aiAgent->imageUrl())
                        <img src="{{ $aiAgent->imageUrl() }}" alt="" class="w-7 h-7 rounded-md flex-shrink-0 object-cover">
                    @else
                        <span class="w-7 h-7 rounded-md flex-shrink-0 flex items-center justify-center text-xs font-bold text-white" style="background-color: {{ $aiAgent->avatar_color }}">
                            {{ strtoupper(substr($aiAgent->name, 0, 1)) }}
                        </span>
                    @endif
                    <span class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary truncate">{{ $aiAgent->name }}</span>
                </div>
                <button @click="newChat()" class="p-1.5 text-gray-500 hover:text-[#1E5FEA] rounded-md hover:bg-gray-50 dark:hover:bg-white/5" title="{{ __('New chat') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                </button>
            </div>
            <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input
                    type="text"
                    x-model="searchConversations"
                    placeholder="{{ __('Search conversations') }}"
                    class="w-full pl-8 pr-2 py-1.5 text-xs bg-gray-50 dark:bg-admin-sidebar border border-gray-200 dark:border-admin-border rounded-lg text-gray-900 dark:text-admin-text-primary focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent"
                >
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-2 space-y-0.5" @scroll="onConversationsScroll($event)">
            <template x-for="conv in filteredConversations" :key="conv.id">
                <a
                    :href="`{{ wsRoute('customer.ai-agents.chat', $aiAgent) }}?conversation=${conv.id}`"
                    class="block px-3 py-2.5 rounded-lg transition-colors"
                    :class="conversationId == conv.id ? 'bg-[rgba(30,95,234,0.08)]' : 'hover:bg-gray-50 dark:hover:bg-white/5'"
                >
                    <div class="flex items-start justify-between gap-2 mb-0.5">
                        <p class="text-sm font-medium truncate flex-1 min-w-0" :class="conversationId == conv.id ? 'text-[#1E5FEA]' : 'text-gray-900 dark:text-white'" x-text="conv.title || '{{ __('Untitled') }}'"></p>
                        <span class="text-[10px] text-gray-400 shrink-0 mt-0.5" x-text="conv.updated_at"></span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="conv.preview"></p>
                </a>
            </template>
            <div x-show="loadingMoreConversations" class="px-2.5 py-3 text-xs text-gray-400 text-center">
                <svg class="animate-spin h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </div>
            <div x-show="filteredConversations.length === 0 && !loadingMoreConversations" class="px-2.5 py-4 text-xs text-gray-400 text-center">
                {{ __('No conversations yet') }}
            </div>
        </div>

        <div class="p-2 border-t border-gray-100 dark:border-white/10">
            <button
                @click="agentModalOpen = true"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-admin-text-primary hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                {{ __('Agents') }}
            </button>
        </div>
    </aside>

    {{-- Right chat --}}
    <main class="flex-1 flex flex-col h-full min-w-0 bg-gray-50 dark:bg-[#111]">
        {{-- Header --}}
        <header class="h-14 flex-shrink-0 flex items-center justify-between px-4 border-b border-gray-100 dark:border-white/10">
            <div class="flex items-center gap-3 min-w-0">
                @if($aiAgent->imageUrl())
                    <img src="{{ $aiAgent->imageUrl() }}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">
                @else
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0" style="background-color: {{ $aiAgent->avatar_color }}">
                        {{ strtoupper(substr($aiAgent->name, 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary truncate">{{ $aiAgent->name }}</h2>
                    @if($aiAgent->description)
                        <p class="text-xs text-gray-500 dark:text-admin-text-secondary truncate">{{ $aiAgent->description }}</p>
                    @endif
                </div>
            </div>

        </header>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-4" x-ref="messages">
            <div class="max-w-3xl mx-auto space-y-4 min-h-full">
                <template x-for="(msg, index) in messages" :key="msg.timestamp + '-' + msg.role">
                    <div class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                        <div class="rounded-2xl px-4 py-3" :class="msg.role === 'user' ? 'bg-[#1E5FEA] text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-900 dark:text-admin-text-primary'">
                            <template x-if="msg.role === 'user'">
                                <p class="text-sm whitespace-pre-wrap" x-text="msg.content"></p>
                            </template>
                            <template x-if="msg.role !== 'user'">
                                <div class="text-sm prose prose-sm max-w-none" x-html="msg.content"></div>
                            </template>
                            <template x-if="msg.role !== 'user' && msg.streaming">
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0ms"></div>
                                    <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 150ms"></div>
                                    <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 300ms"></div>
                                </div>
                            </template>
                        </div>
                        <template x-if="msg.role !== 'user' && !msg.streaming">
                            <div class="chat-feedback-row mt-2 flex gap-2">
                                <button type="button" class="chat-feedback-btn p-1 rounded transition-colors focus:outline-none"
                                    :class="msg.feedback === 'like' ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300'"
                                    @click="submitFeedback(index, 'like')" aria-label="Helpful">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>
                                </button>
                                <button type="button" class="chat-feedback-btn p-1 rounded transition-colors focus:outline-none"
                                    :class="msg.feedback === 'dislike' ? 'text-red-500' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300'"
                                    @click="submitFeedback(index, 'dislike')" aria-label="Not helpful">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/></svg>
                                </button>
                                <button type="button" class="chat-feedback-btn p-1 rounded transition-colors focus:outline-none text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                    @click="copyToClipboard(msg.content)" aria-label="Copy">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <div x-show="loadingMessages" x-cloak class="flex justify-center py-12">
                    <svg class="animate-spin h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>

                <div x-show="messages.length === 0 && !loading && !loadingMessages" class="flex flex-col items-center justify-center h-full text-center py-12">
                    <h3 class="text-3xl font-medium text-gray-900 dark:text-white mb-2">{{ __('What can I help with?') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Ask anything to') }} {{ $aiAgent->name }}.</p>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="p-6 bg-gray-50 dark:bg-[#0f0f0f]">
            <div class="max-w-3xl mx-auto">
                <div class="running-border rounded-[30px]" :class="{ 'loading': loading }">
                    <form @submit.prevent="sendQuery()" class="bg-white dark:bg-admin-card rounded-[1.75rem] p-4 relative z-10">
                        <textarea
                        x-model="query"
                        @keydown="handleKeydown($event)"
                        rows="3"
                        class="w-full min-h-[80px] max-h-[160px] px-2 py-1 border-0 bg-transparent text-gray-900 dark:text-admin-text-primary text-base placeholder:text-gray-400 resize-none focus:ring-0 focus:outline-none"
                        style="outline: none !important; box-shadow: none !important;"
                        placeholder="{{ __('Ask anything') }}"
                        :disabled="loading"
                    ></textarea>

                    <div class="flex items-center justify-between mt-2" x-on:model-selected.window="provider = $event.detail.provider; model = $event.detail.model">
                        <div class="w-56 shrink-0 self-start mt-[15px] ml-[6px]">
                            <x-model-selector
                                :provider-models="$providerModels"
                                :selected-provider="$initialProvider"
                                :selected-model="$initialModel"
                                dropup
                                placeholder="{{ __('Search all models') }}"
                            />
                        </div>

                        <button type="submit" :disabled="loading || !query.trim()" class="w-10 h-10 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl flex items-center justify-center hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                        </button>
                    </div>
                </form>
                </div>

                <p class="text-[11px] text-gray-400 dark:text-gray-500 text-center mt-3">{{ __('Shift + Enter for a new line. Press Enter to send.') }}</p>
            </div>
        </div>
    </main>

    {{-- Agents modal --}}
    <div
        x-cloak
        x-show="agentModalOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50"
        @click.self="agentModalOpen = false"
    >
        <div class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-white/10 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Your Agents') }}</h3>
                <button @click="agentModalOpen = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="max-h-[60vh] overflow-y-auto p-2">
                @foreach($agents as $agent)
                    <a
                        href="{{ wsRoute('customer.ai-agents.chat', $agent) }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $aiAgent->id === $agent->id ? 'bg-[rgba(30,95,234,0.08)] text-[#1E5FEA]' : 'hover:bg-gray-50 dark:hover:bg-white/5 text-gray-700 dark:text-admin-text-primary' }}"
                    >
                        @if($agent->imageUrl())
                            <img src="{{ $agent->imageUrl() }}" alt="" class="w-8 h-8 rounded-md object-cover shrink-0">
                        @else
                            <span class="w-8 h-8 rounded-md flex items-center justify-center text-xs font-bold text-white shrink-0" style="background-color: {{ $agent->avatar_color }}">
                                {{ strtoupper(substr($agent->name, 0, 1)) }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate">{{ $agent->name }}</p>
                            @if($agent->description)
                                <p class="text-xs text-gray-500 dark:text-admin-text-secondary truncate">{{ $agent->description }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="p-3 border-t border-gray-100 dark:border-white/10">
                <a href="{{ wsRoute('customer.ai-agents.create') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('Create Agent') }}
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    @property --border-angle {
        syntax: '<angle>';
        initial-value: 0deg;
        inherits: false;
    }

    .running-border {
        position: relative;
        padding: 2px;
        border-radius: 1.75rem;
        background: #e5e7eb;
    }
    .dark .running-border {
        background: rgba(255, 255, 255, 0.1);
    }
    .running-border.loading {
        background: conic-gradient(from var(--border-angle),
            #e5e7eb 0%, #e5e7eb 75%,
            #1E5FEA 82%, #5B9AFF 90%,
            #e5e7eb 97%, #e5e7eb 100%);
        animation: border-rotate 3s linear infinite;
    }
    .dark .running-border.loading {
        background: conic-gradient(from var(--border-angle),
            rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.1) 75%,
            #1E5FEA 82%, #5B9AFF 90%,
            rgba(255,255,255,0.1) 97%, rgba(255,255,255,0.1) 100%);
    }
    @keyframes border-rotate {
        to { --border-angle: 360deg; }
    }
</style>

@endsection

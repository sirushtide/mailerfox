@extends('layouts.customer')

@section('title', $agent ? __('Edit Agent') : __('Create Agent'))

@section('content')
@php
    $isEdit = $agent !== null;
    $provider = old('provider', $agent?->provider ?? 'chatgpt');
    $model = old('model', $agent?->model ?? '');
    if (!$model && !empty($providerModels[$provider])) {
        $model = $providerModels[$provider][0];
    }
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
     x-data="{ provider: '{{ $provider }}', model: '{{ $model }}' }"
>
    <div class="mb-6">
        <a href="{{ wsRoute('customer.ai-agents.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-admin-text-secondary hover:text-[#1E5FEA] transition-colors mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            {{ __('Back to AI Agents') }}
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-admin-text-primary">{{ $isEdit ? __('Edit Agent') : __('Create Agent') }}</h1>
        <p class="text-sm text-gray-500 dark:text-admin-text-secondary mt-1">{{ __('Train an AI agent on your knowledge bases and documents.') }}</p>
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? wsRoute('customer.ai-agents.update', $agent) : wsRoute('customer.ai-agents.store') }}" enctype="multipart/form-data" class="bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-xl p-6 space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="provider" :value="provider">
        <input type="hidden" name="model" :value="model">

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $agent?->name) }}" required class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent" placeholder="e.g. Support Bot">
            </div>

            <div x-data="{ avatarColor: '{{ old('avatar_color', $agent?->avatar_color ?? '#1E5FEA') }}' }">
                <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Avatar Color') }}</label>
                <div class="flex items-center gap-3">
                    <input type="color" x-model="avatarColor" class="w-10 h-10 rounded-lg border border-gray-300 dark:border-admin-border bg-white dark:bg-admin-sidebar p-1 cursor-pointer">
                    <input type="text" name="avatar_color" x-model="avatarColor" class="flex-1 px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                </div>
            </div>
        </div>

        <div x-data="{ preview: {{ $agent?->imageUrl() ? '\'' . addslashes($agent->imageUrl()) . '\'' : 'null' }} }">
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Agent Image') }}</label>
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl border border-gray-200 dark:border-admin-border overflow-hidden bg-gray-50 dark:bg-admin-sidebar flex items-center justify-center">
                    <template x-if="preview">
                        <img :src="preview" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!preview">
                        <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        </div>
                    </template>
                </div>
                <div class="flex-1">
                    <label class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-admin-border rounded-lg text-sm font-medium text-gray-700 dark:text-admin-text-primary hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                        {{ __('Upload Image') }}
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="hidden" @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                    </label>
                    <p class="text-xs text-gray-400 mt-1">{{ __('JPG, PNG, WebP up to 2MB.') }}</p>
                    @error('image')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Description') }}</label>
            <input type="text" name="description" value="{{ old('description', $agent?->description) }}" class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent" placeholder="Short description of what this agent does">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Instructions / System Prompt') }}</label>
            <textarea name="instructions" rows="6" class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent" placeholder="Describe how the agent should behave, what tone to use, and what it should know.">{{ old('instructions', $agent?->instructions) }}</textarea>
            <p class="text-xs text-gray-400 mt-1">{{ __('This prompt is included in every conversation with the agent.') }}</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Default AI Model') }}</label>
                <div x-on:model-selected="provider = $event.detail.provider; model = $event.detail.model">
                    <x-model-selector
                        :provider-models="$providerModels"
                        :selected-provider="$provider"
                        :selected-model="$model"
                        dropup
                        placeholder="{{ __('Search all models') }}"
                    />
                </div>
            </div>

            @if($isEdit)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Status') }}</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary text-sm focus:ring-2 focus:ring-[#1E5FEA] focus:border-transparent">
                    <option value="active" @selected(old('status', $agent->status) === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(old('status', $agent->status) === 'inactive')>{{ __('Inactive') }}</option>
                </select>
            </div>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Knowledge Sources') }}</label>
            <div class="border border-gray-300 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar p-3 max-h-64 overflow-y-auto">
                @if($knowledgeBases->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('No knowledge bases available.') }} <a href="{{ wsRoute('customer.rag.knowledge.create') }}" class="text-[#1E5FEA] hover:underline">{{ __('Create one first') }}</a>.</p>
                @else
                    <div class="grid sm:grid-cols-2 gap-2">
                        @php
                            $selectedIds = old('knowledge_base_ids', $agent ? $agent->knowledgeBases->pluck('id')->all() : []);
                        @endphp
                        @foreach($knowledgeBases as $kb)
                            <label class="flex items-start gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer">
                                <input type="checkbox" name="knowledge_base_ids[]" value="{{ $kb->id }}" @checked(in_array($kb->id, (array) $selectedIds)) class="mt-0.5 text-[#1E5FEA] focus:ring-[#1E5FEA]">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary truncate">{{ $kb->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ $kb->type }} · {{ $kb->total_chunks }} chunks</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ __('Select documents, FAQs, products, and URLs the agent should be trained on.') }}</p>
        </div>

        @if($isEdit && $agent->knowledgeBases->isNotEmpty())
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary mb-1.5">{{ __('Training Data') }}</label>
            <div class="border border-gray-200 dark:border-admin-border rounded-lg bg-white dark:bg-admin-sidebar p-3">
                <div class="space-y-2">
                    @foreach($agent->knowledgeBases as $kb)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-white/5">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-admin-text-primary truncate">{{ $kb->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ ucfirst($kb->type) }} · {{ $kb->total_chunks }} {{ __('chunks') }} · {{ $kb->total_words }} {{ __('words') }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $kb->status === 'active' ? 'bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400' }}">
                                {{ $kb->status === 'active' ? __('Active') : __('Inactive') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ __('These knowledge sources are currently attached to this agent.') }}</p>
        </div>
        @endif

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-[#1E5FEA] text-white text-sm font-medium rounded-lg hover:bg-[#1a54d4] transition-colors">{{ $isEdit ? __('Update Agent') : __('Create Agent') }}</button>
            <a href="{{ wsRoute('customer.ai-agents.index') }}" class="px-5 py-2 border border-gray-300 dark:border-admin-border text-gray-700 dark:text-admin-text-primary text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection

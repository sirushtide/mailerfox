@extends('layouts.customer')

@section('title', __('Projects'))
@section('page-title', __('Projects'))
@section('hide-page-header', '1')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-[22px] font-semibold tracking-tight text-admin-text-primary">{{ __('Projects') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Build and manage your AI-generated web pages.') }}</p>
        </div>

        <div x-data="{ open: false }" class="relative">
            <button
                @click="open = !open"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#1a52cc] focus:outline-none transition-colors"
            >
                {{ __('Create') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div
                x-cloak x-show="open" @click.away="open = false"
                class="absolute right-0 mt-1 w-48 rounded-xl border border-gray-100 dark:border-white/10 bg-white dark:bg-gray-900 shadow-lg py-1 z-20"
            >
                <a
                    href="{{ wsRoute('customer.ai-projects.builder') }}"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    {{ __('New project') }}
                </a>
            </div>
        </div>
    </div>

    @if($sessions->isEmpty())
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 dark:border-white/10 py-20 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#1E5FEA]/10 text-[#1E5FEA] mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/></svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('No projects yet') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-admin-text-secondary max-w-xs">{{ __('Describe what you want to build and let AI create it for you in seconds.') }}</p>
            <a
                href="{{ wsRoute('customer.ai-projects.builder') }}"
                class="mt-6 inline-flex items-center gap-2 rounded-lg bg-[#1E5FEA] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#1a52cc] transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                {{ __('Create your first project') }}
            </a>
        </div>
    @else
        {{-- Project grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($sessions as $session)
                <div class="group relative rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 hover:border-[#1E5FEA]/40 hover:shadow-md transition-all">

                    {{-- Thumbnail / preview area --}}
                    <a href="{{ wsRoute('customer.ai-projects.builder.show', $session->session_id) }}" class="block">
                        <div class="relative h-40 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-white/5 dark:to-white/[0.02] overflow-hidden">
                            @php
                                $hasReactThumb = $session->preview_mode === 'react' && !empty($session->thumbnail_url);
                                $hasHtmlThumb = $session->preview_mode !== 'react' && !empty($session->preview_html);
                            @endphp
                            @if($hasReactThumb)
                                <iframe
                                    src="{{ $session->thumbnail_url }}"
                                    class="absolute inset-0 pointer-events-none border-0 w-full h-full"
                                    sandbox="allow-scripts allow-same-origin"
                                    loading="lazy"
                                    scrolling="no"
                                ></iframe>
                            @elseif($hasHtmlThumb)
                                <iframe
                                    srcdoc="{{ $session->preview_html }}"
                                    class="absolute inset-0 pointer-events-none border-0 w-full h-full"
                                    sandbox="allow-scripts allow-same-origin"
                                    loading="lazy"
                                    scrolling="no"
                                ></iframe>
                            @else
                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-gray-400 dark:text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                                    <span class="text-xs">{{ __('No preview') }}</span>
                                </div>
                            @endif

                            {{-- status badge --}}
                            <span class="absolute top-2 right-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $session->page_status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' }}">
                                {{ ucfirst($session->page_status ?? 'draft') }}
                            </span>

                            {{-- hover overlay --}}
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors"></div>
                        </div>
                    </a>

                    {{-- Info --}}
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $session->page_title ?? $session->preview_title ?? __('Untitled project') }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                    {{ __('Edited') }} {{ $session->updated_at?->diffForHumans() }}
                                </p>
                            </div>

                            <div x-data="{ open: false }" class="relative shrink-0">
                                <button @click="open = !open" class="p-1 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                </button>
                                <div
                                    x-cloak x-show="open" @click.away="open = false"
                                    class="absolute right-0 mt-1 w-40 rounded-xl border border-gray-100 dark:border-white/10 bg-white dark:bg-gray-900 shadow-lg py-1 z-20"
                                >
                                    <a href="{{ wsRoute('customer.ai-projects.builder.show', $session->session_id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        {{ __('Open') }}
                                    </a>
                                    <form
                                        action="{{ wsRoute('customer.ai-projects.builder.destroy', $session->session_id) }}"
                                        method="POST"
                                        class="block"
                                        onsubmit="return confirm('{{ __('Are you sure you want to delete this project?') }}');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($sessions->hasPages())
            <div>{{ $sessions->links() }}</div>
        @endif
    @endif

</div>
@endsection

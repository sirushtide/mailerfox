@extends('layouts.public')

@section('title', __('AI Assistant Addon'))

@section('content')
<div class="relative overflow-hidden">
    <section class="relative bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-700 py-24 sm:py-32">
        <div class="relative mx-auto max-w-5xl px-6 text-center">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-sm">
                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">{{ __('AI Assistant') }}</h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-violet-100">
                {{ __('One AI copilot across Email, SMS and WhatsApp — drafting replies, taking actions, and helping you everywhere in the app.') }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-4">
                <a href="{{ route('pricing') }}" class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-violet-700 shadow hover:bg-violet-50">{{ __('Get Started') }}</a>
                <a href="{{ route('docs') }}" class="rounded-xl border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10">{{ __('Documentation') }}</a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-6 py-20">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([
                ['Omnichannel Replies', 'Drafts context-aware replies across Email, SMS and WhatsApp threads.'],
                ['Auto-reply', 'Choose suggest mode for review or full auto-send with human handoff.'],
                ['App-wide Copilot', 'Floating assistant everywhere plus a full-page chat workspace.'],
                ['Tool Calling', 'Sends messages, pulls list stats, searches contacts and more.'],
                ['Knowledge Base', 'Grounded answers using your existing RAG knowledge bases and agents.'],
                ['Bring Your Own Model', 'Use any configured AI provider and model, with credits tracking.'],
            ] as [$title, $desc])
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection

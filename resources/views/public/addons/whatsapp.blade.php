@extends('layouts.public')

@section('title', __('WhatsApp Messaging Addon'))

@section('content')
<div class="relative overflow-hidden">
    <section class="relative bg-gradient-to-br from-emerald-500 via-green-600 to-teal-700 py-24 sm:py-32">
        <div class="relative mx-auto max-w-5xl px-6 text-center">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-sm">
                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">{{ __('WhatsApp Messaging') }}</h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-emerald-50">
                {{ __('Broadcast approved templates and hold two-way conversations on WhatsApp via the Meta Cloud API.') }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-4">
                <a href="{{ route('pricing') }}" class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-emerald-700 shadow hover:bg-emerald-50">{{ __('Get Started') }}</a>
                <a href="{{ route('docs') }}" class="rounded-xl border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10">{{ __('Documentation') }}</a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-6 py-20">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([
                ['Template Management', 'Create and sync Meta-approved message templates for out-of-window broadcasts.'],
                ['Two-way Inbox', 'Receive and reply to WhatsApp messages inside the unified inbox.'],
                ['Delivery Receipts', 'Track sent, delivered and read states per message.'],
                ['Bring Your Own WABA', 'Connect your own WhatsApp Business account or use the platform provider.'],
                ['Media Messages', 'Send images, documents and captions.'],
                ['Automations & API', 'Trigger WhatsApp flows from automations and the REST API.'],
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

@extends('layouts.public')

@section('title', __('SMS Messaging Addon'))

@section('content')
<div class="relative overflow-hidden">
    <section class="relative bg-gradient-to-br from-sky-600 via-blue-600 to-indigo-700 py-24 sm:py-32">
        <div class="relative mx-auto max-w-5xl px-6 text-center">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-sm">
                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m7.875 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">{{ __('SMS Messaging') }}</h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-sky-100">
                {{ __('Reach customers instantly with bulk SMS/MMS campaigns and two-way conversations, powered by Twilio.') }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-4">
                <a href="{{ route('pricing') }}" class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-sky-700 shadow hover:bg-sky-50">{{ __('Get Started') }}</a>
                <a href="{{ route('docs') }}" class="rounded-xl border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10">{{ __('Documentation') }}</a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-6 py-20">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([
                ['Bulk Campaigns', 'Send personalized SMS to any list with scheduling and personalization tags.'],
                ['Two-way Inbox', 'Receive replies, manage conversations and route them inside the unified inbox.'],
                ['Compliance', 'Automatic STOP/START/HELP handling and opt-out management out of the box.'],
                ['Bring Your Own Twilio', 'Use your own Twilio account or the platform provider — your choice.'],
                ['Media (MMS)', 'Attach images and media to enrich your messaging.'],
                ['Automations & API', 'Trigger SMS from automations and send via the REST API.'],
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

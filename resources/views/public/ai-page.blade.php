@extends('layouts.public')

@php
    $appTitle = is_string($appMeta['app_title'] ?? null) ? trim($appMeta['app_title']) : '';
    $appIcon = is_string($appMeta['app_icon'] ?? null) ? trim($appMeta['app_icon']) : '';
    $description = is_string($appMeta['description'] ?? null) ? trim($appMeta['description']) : '';
    $socialImage = is_string($appMeta['social_image'] ?? null) ? trim($appMeta['social_image']) : '';
@endphp

@section('title', $appTitle !== '' ? $appTitle : $pageTitle)

@if($description !== '')
@section('metaDescription', $description)
@endif

@if($socialImage !== '')
@section('metaImage', $socialImage)
@endif

@section('content')
    @if($appIcon !== '')
        <link rel="icon" href="{{ $appIcon }}">
    @endif

    @if($appTitle !== '')
        <script>
            window.APP_META = @json(array_filter([
                'app_title' => $appTitle !== '' ? $appTitle : null,
                'app_icon' => $appIcon !== '' ? $appIcon : null,
            ]));
            (function () {
                const meta = window.APP_META || {};
                if (meta.app_title) {
                    document.title = meta.app_title;
                }
                if (meta.app_icon) {
                    let link = document.querySelector('link[rel="icon"]');
                    if (!link) {
                        link = document.createElement('link');
                        link.rel = 'icon';
                        document.head.appendChild(link);
                    }
                    link.href = meta.app_icon;
                }
            })();
        </script>
    @endif

    @if(!empty($supabaseCredentials) && is_array($supabaseCredentials))
        <script>
            window.APP_SUPABASE_URL = @json($supabaseCredentials['url'] ?? '');
            window.APP_SUPABASE_PUBLISHABLE_KEY = @json($supabaseCredentials['publishable_key'] ?? '');
            window.APP_ID = @json($supabaseCredentials['app_id'] ?? '');
        </script>
    @endif

    @if(!empty($passwordRequired))
        <div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="text-sm font-semibold text-gray-900">This page is password protected</div>
                <p class="mt-1 text-sm text-gray-500">Enter the password to view the page.</p>

                @if(!empty($passwordError))
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        Invalid password. Please try again.
                    </div>
                @endif

                <form class="mt-4 space-y-3" method="GET" action="{{ route('ai.pages.show', $session->page_slug) }}">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
                        <input
                            type="password"
                            name="password"
                            value="{{ request('password') }}"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-gray-400"
                            placeholder="Enter password"
                            autofocus
                        >
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 transition-colors">
                        Unlock page
                    </button>
                </form>
            </div>
        </div>
    @else
        {!! $content !!}
    @endif
@endsection

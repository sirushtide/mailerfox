@extends('layouts.public')

@php
    $builder = is_array($page->builder_data ?? null) ? $page->builder_data : [];
    $appMeta = is_array($builder['app_meta'] ?? null) ? $builder['app_meta'] : [];
    $pageMetaDescription = is_string(($builder['meta_description'] ?? null))
        ? trim((string) $builder['meta_description'])
        : '';
    $pageMetaImage = is_string(($builder['meta_image'] ?? null))
        ? trim((string) $builder['meta_image'])
        : '';
    $appTitle = is_string(($appMeta['app_title'] ?? null)) ? trim((string) $appMeta['app_title']) : '';
    $appLogo = is_string(($appMeta['app_logo'] ?? null)) ? trim((string) $appMeta['app_logo']) : '';
    $appIcon = is_string(($appMeta['app_icon'] ?? null)) ? trim((string) $appMeta['app_icon']) : '';

    $brandingMeta = array_filter([
        'app_title' => $appTitle !== '' ? $appTitle : null,
        'app_logo' => $appLogo !== '' ? $appLogo : null,
        'app_icon' => $appIcon !== '' ? $appIcon : null,
    ]);
@endphp

@section('title', $appTitle !== '' ? $appTitle : $page->title)

@if($pageMetaDescription !== '')
@section('metaDescription', $pageMetaDescription)
@endif

@if($pageMetaImage !== '')
@section('metaImage', $pageMetaImage)
@endif

@section('content')
    @if($appIcon !== '')
        <link rel="icon" href="{{ $appIcon }}">
    @endif

    @if(!empty($googleFontUrls) && is_array($googleFontUrls))
        @foreach($googleFontUrls as $fontUrl)
            @if(is_string($fontUrl) && trim($fontUrl) !== '')
                <link rel="stylesheet" href="{{ $fontUrl }}">
            @endif
        @endforeach
    @endif

    @if($appTitle !== '' || $appLogo !== '' || $appIcon !== '')
        <script>
            window.APP_META = @json($brandingMeta);
            (function () {
                const meta = window.APP_META || {};
                function applyAppMeta() {
                    if (meta.app_title) {
                        document.querySelectorAll('[data-app-title]').forEach((el) => {
                            el.textContent = meta.app_title;
                        });
                    }
                    if (meta.app_logo) {
                        document.querySelectorAll('[data-app-logo]').forEach((el) => {
                            if (el.tagName.toLowerCase() === 'img') {
                                el.src = meta.app_logo;
                                el.classList.remove('hidden');
                            } else {
                                el.style.backgroundImage = 'url(' + JSON.stringify(meta.app_logo).slice(1, -1) + ')';
                                el.style.backgroundSize = 'contain';
                                el.style.backgroundRepeat = 'no-repeat';
                                el.style.backgroundPosition = 'center';
                            }
                        });
                    }
                }
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
                applyAppMeta();
                if (window.MutationObserver && document.body) {
                    const observer = new MutationObserver((mutations) => {
                        let shouldApply = false;
                        for (const mutation of mutations) {
                            if (mutation.type !== 'childList') {
                                continue;
                            }
                            for (const node of mutation.addedNodes) {
                                if (node.nodeType !== 1) {
                                    continue;
                                }
                                if (
                                    (node.matches && node.matches('[data-app-title], [data-app-logo]')) ||
                                    (node.querySelector && node.querySelector('[data-app-title], [data-app-logo]'))
                                ) {
                                    shouldApply = true;
                                    break;
                                }
                            }
                            if (shouldApply) {
                                break;
                            }
                        }
                        if (shouldApply) {
                            applyAppMeta();
                        }
                    });
                    observer.observe(document.body, { childList: true, subtree: true });
                }
            })();
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

                <form class="mt-4 space-y-3" method="GET" action="{{ route('pages.show', $page->slug) }}">
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
        @if(!empty($supabaseCredentials) && is_array($supabaseCredentials))
            <script>
                window.APP_SUPABASE_URL = @json($supabaseCredentials['url'] ?? '');
                window.APP_SUPABASE_PUBLISHABLE_KEY = @json($supabaseCredentials['publishable_key'] ?? '');
                window.APP_ID = @json($supabaseCredentials['app_id'] ?? '');
            </script>
        @endif
        {!! $content ?? ($page->html_content ?? '') !!}
    @endif
@endsection

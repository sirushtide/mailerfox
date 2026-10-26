@extends('layouts.admin')

@section('content-padding-classes', 'p-0 overflow-hidden')

@php
    $tabIconPaths = [
        'general' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings2-icon lucide-settings-2"><path d="M14 17H5"/><path d="M19 7h-9"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>',
        'appearance' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-paint-bucket-icon lucide-paint-bucket"><path d="M11 7 6 2"/><path d="M18.992 12H2.041"/><path d="M21.145 18.38A3.34 3.34 0 0 1 20 16.5a3.3 3.3 0 0 1-1.145 1.88c-.575.46-.855 1.02-.855 1.595A2 2 0 0 0 20 22a2 2 0 0 0 2-2.025c0-.58-.285-1.13-.855-1.595"/><path d="m8.5 4.5 2.148-2.148a1.205 1.205 0 0 1 1.704 0l7.296 7.296a1.205 1.205 0 0 1 0 1.704l-7.592 7.592a3.615 3.615 0 0 1-5.112 0l-3.888-3.888a3.615 3.615 0 0 1 0-5.112L5.67 7.33"/></svg>',
        'seo' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-line-icon lucide-chart-line"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/></svg>',
        'account' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-user-icon lucide-circle-user"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/></svg>',
        'privacy' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-cog-corner-icon lucide-shield-cog-corner"><path d="M11 22c-3.806-1.45-7-3.966-7-9V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1v4"/><path d="M14.923 16.547 14 16.164"/><path d="m14.923 18.843-.923.383"/><path d="M16.547 14.923 16.164 14"/><path d="m16.547 20.467-.383.924"/><path d="m18.843 14.923.383-.923"/><path d="m19.225 21.391-.382-.924"/><path d="m20.467 16.547.923-.383"/><path d="m20.467 18.843.923.383"/><circle cx="17.695" cy="17.695" r="3"/></svg>',
        'email' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail-icon lucide-mail"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>',
        'auth' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-lock-icon lucide-user-lock"><path d="M19 16v-2a2 2 0 0 0-4 0v2"/><path d="M9.5 15H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><rect x="13" y="16" width="8" height="5" rx=".899"/></svg>',
        'cron' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alarm-clock-icon lucide-alarm-clock"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6"/><path d="m22 6-3-3"/><path d="M6.38 18.7 4 21"/><path d="M17.64 18.67 20 21"/></svg>',
        'storage' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hard-drive-icon lucide-hard-drive"><path d="M10 16h.01"/><path d="M2.212 11.577a2 2 0 0 0-.212.896V18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5.527a2 2 0 0 0-.212-.896L18.55 5.11A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/><path d="M21.946 12.013H2.054"/><path d="M6 16h.01"/></svg>',
        'billing' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card-icon lucide-credit-card"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>',
        'updates' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-download-icon lucide-cloud-download"><path d="M12 13v8l-4-4"/><path d="m12 21 4-4"/><path d="M4.393 15.269A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.436 8.284"/></svg>',
        'changelogs' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-terminal-icon lucide-file-terminal"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m8 16 2-2-2-2"/><path d="M12 18h4"/></svg>',
        'affiliate' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-handshake-icon lucide-handshake"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/></svg>',
        'navigation' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />',
        'navigation' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-icon lucide-map"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"/><path d="M15 5.764v15"/><path d="M9 3.236v15"/></svg>',
        'default_pages' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-template-icon lucide-layout-template"><rect width="18" height="7" x="3" y="3" rx="1"/><rect width="9" height="7" x="3" y="14" rx="1"/><rect width="5" height="7" x="16" y="14" rx="1"/></svg>',
        'workspace' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building-2-icon lucide-building-2"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>'
    ];
    $defaultIconPath = '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />';

    $tabLabels = [
        'seo' => 'SEO',
        'auth' => 'Authentication',
        'default_pages' => 'Default Pages',
    ];

    // Set page title dynamically based on category
    $categoryLabel = $tabLabels[$category] ?? ucfirst($category);
    $pageTitle = $category === 'general' ? 'Settings' : 'Settings / ' . $categoryLabel;
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@section('breadcrumbs')
<ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
    <li>
        <a href="{{ route('admin.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">
            {{ __('Dashboard') }}
        </a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li>
        <a href="{{ route('admin.settings.index') }}" class="font-medium transition hover:text-admin-text-primary">
            {{ __('Settings') }}
        </a>
    </li>
    @if($category !== 'general')
        <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
        <li class="font-medium text-admin-text-primary truncate">{{ $categoryLabel }}</li>
    @endif
</ol>
@endsection

@section('content')
<div class="flex flex-1 h-full overflow-hidden">
    @php
        $tabUpdateAvailable = false;
        if (is_array($updateStatus ?? null)) {
            $tabUpdateAvailable = (bool) ($updateStatus['update_available'] ?? false);
        }
    @endphp

    <div class="flex flex-1 h-full overflow-hidden">
        {{-- Vertical Tab Navigation --}}
        <div class="w-56 shrink-0 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
            <nav class="space-y-1" aria-label="Settings tabs">
                @foreach($categories as $cat)
                    @php
                        $isActive = $category === $cat;
                        $iconPath = $tabIconPaths[$cat] ?? $defaultIconPath;
                        $label = $tabLabels[$cat] ?? ucfirst($cat);
                    @endphp
                    <a
                        href="{{ route('admin.settings.index', ['category' => $cat]) }}"
                        class="group flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors {{ $isActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200' }}"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">{!! $iconPath !!}</svg>
                        <span class="truncate">{{ $label }}</span>
                        @if($cat === 'updates' && $tabUpdateAvailable)
                            <span class="ml-auto inline-flex items-center justify-center h-5 min-w-[20px] rounded-full text-[10px] font-semibold bg-red-500 text-white px-1.5">!</span>
                        @endif
                    </a>
                @endforeach

                <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                @admincan('admin.translations.access')
                    <a
                        href="{{ route('admin.translations.locales.index') }}"
                        class="group flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('admin.translations.*') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200' }}"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" /></svg>
                        <span class="truncate">Translations</span>
                    </a>
                @endadmincan

                <a
                    href="{{ route('admin.settings.logs') }}"
                    class="group flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('admin.settings.logs') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200' }}"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    <span class="truncate">Logs</span>
                </a>
            </nav>
        </div>

        {{-- Content Area --}}
        <div class="flex-1 min-w-0 h-full flex flex-col">
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="flex h-full min-h-0 flex-col">
                @csrf
                @method('POST')

                <input type="hidden" name="category" value="{{ $category }}">

                {{-- Scrollable Content --}}
                <div class="flex-1 min-h-0 overflow-y-auto">
                    <div class="max-w-[60%] mx-auto px-6 py-6">
                        <div class="mb-6 flex items-start justify-between">
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $categoryLabel }}</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if($category === 'general')
                                        {{ __('Manage your general application settings') }}
                                    @elseif($category === 'seo')
                                        {{ __('Configure SEO and meta tags for your application') }}
                                    @elseif($category === 'appearance')
                                        {{ __('Customize the look and feel of your application') }}
                                    @elseif($category === 'email')
                                        {{ __('Configure email sending and delivery settings') }}
                                    @elseif($category === 'billing')
                                        {{ __('Manage billing and subscription settings') }}
                                    @elseif($category === 'default_pages')
                                        {{ __('Select which page builder pages to use for public homepage, login, and register pages') }}
                                    @else
                                        {{ __('Manage your ' . strtolower($categoryLabel) . ' settings') }}
                                    @endif
                                </p>
                            </div>
                            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">
                                {{ __('Save') }}
                            </button>
                        </div>
            @if($category === 'updates')
                @php
                    $installedVersion = null;
                    $installedVersion = trim((string) config('mailpurse.version', ''));
                    if ($installedVersion === '') {
                        $installedVersion = $settings['updates']->get('app_version') ?? null;
                        $installedVersion = is_string($installedVersion) ? trim((string) $installedVersion) : null;
                    }

                    $updateStatusPayload = is_array($updateStatus ?? null) ? $updateStatus : null;
                    $updateAvailableFlag = is_array($updateStatusPayload) ? (bool) ($updateStatusPayload['update_available'] ?? false) : false;
                    $updateStatusLatest = is_array($updateStatusPayload) && is_string($updateStatusPayload['latest_version'] ?? null) ? $updateStatusPayload['latest_version'] : null;
                    $updateStatusCheckedAt = is_array($updateStatusPayload) && is_string($updateStatusPayload['checked_at'] ?? null) ? $updateStatusPayload['checked_at'] : null;

                    $installTargetVersion = is_string($updateStatusLatest) && trim((string) $updateStatusLatest) !== ''
                        ? (string) $updateStatusLatest
                        : null;

                    $latestVersion = null;
                    $productData = null;
                    if (is_array($updateProduct ?? null)) {
                        $payload = $updateProduct['data'] ?? null;
                        if (is_array($payload)) {
                            if (is_array($payload['data'] ?? null)) {
                                $productData = $payload['data'];
                            } elseif (is_array($payload['product'] ?? null)) {
                                $productData = $payload['product'];
                            } else {
                                $productData = $payload;
                            }
                        }
                    }

                    if (is_array($productData)) {
                        $latestVersion = $productData['latest_version']
                            ?? ($productData['latestVersion'] ?? ($productData['latest'] ?? null));

                        if (!is_string($latestVersion) || trim((string) $latestVersion) === '') {
                            $latestVersion = $productData['version'] ?? null;
                        }
                    }

                    if (!is_string($latestVersion) || trim((string) $latestVersion) === '') {
                        $changelogData = is_array($updateChangelogs ?? null) ? ($updateChangelogs['data'] ?? null) : null;
                        if (is_array($changelogData)) {
                            $versionMap = null;
                            if (is_array($changelogData['releases'] ?? null)) {
                                $versionMap = $changelogData['releases'];
                            } elseif (is_array($changelogData['changelog'] ?? null)) {
                                $versionMap = $changelogData['changelog'];
                            }

                            if (is_array($versionMap) && !empty($versionMap)) {
                                $versions = array_keys($versionMap);
                                usort($versions, function ($a, $b) {
                                    return version_compare((string) $b, (string) $a);
                                });
                                $latestVersion = $versions[0] ?? null;
                            }
                        }
                    }

                    if (!$updateAvailableFlag && is_string($latestVersion) && is_string($installedVersion) && trim($latestVersion) !== '' && trim($installedVersion) !== '') {
                        $updateAvailableFlag = version_compare((string) $latestVersion, (string) $installedVersion, '>');
                    }

                    if (!is_string($installTargetVersion) || trim((string) $installTargetVersion) === '') {
                        $installTargetVersion = is_string($latestVersion) && trim((string) $latestVersion) !== ''
                            ? (string) $latestVersion
                            : null;
                    }
                @endphp

                <div class="space-y-6">
                    @if($updateAvailableFlag)
                        <div class="p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg dark:bg-blue-900/50 dark:border-blue-800 dark:text-blue-200">
                            <p class="font-semibold">Update available</p>
                            <p class="mt-1 text-sm">Installed: {{ is_string($installedVersion) && trim($installedVersion) !== '' ? $installedVersion : '—' }} | Latest: {{ is_string($updateStatusLatest) && trim($updateStatusLatest) !== '' ? $updateStatusLatest : (is_string($latestVersion) ? $latestVersion : '—') }}</p>
                            @if(is_string($updateStatusCheckedAt) && trim($updateStatusCheckedAt) !== '')
                                <p class="mt-1 text-xs">Last checked: {{ $updateStatusCheckedAt }}</p>
                            @endif
                        </div>
                    @endif
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Updates</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Check the latest available version from your update server and generate a download link.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Installed Version</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ is_string($installedVersion) && trim($installedVersion) !== '' ? $installedVersion : '—' }}</p>
                        </div>
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Latest Version</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ is_string($latestVersion) && trim((string) $latestVersion) !== '' ? $latestVersion : '—' }}
                            </p>
                            @if(is_array($updateProduct ?? null) && !($updateProduct['success'] ?? false))
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $updateProduct['message'] ?? 'Unable to fetch update info.' }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="dark:border-gray-700">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Install Update</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Install the latest version in the background. The site will go into maintenance mode during installation.</p>
                            </div>
                            @admincan('admin.settings.edit')
                                <x-button
                                    type="submit"
                                    variant="primary"
                                    size="sm"
                                    formaction="{{ route('admin.settings.install-update') }}"
                                    formmethod="POST"
                                    :disabled="!$updateAvailableFlag"
                                    onclick="return confirm('Are you sure you want to install the update? The site will go into maintenance mode during the update.')"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                    Install Update (v{{ is_string($installTargetVersion) ? $installTargetVersion : 'latest' }})
                                </x-button>
                            @endadmincan
                        </div>

                        @if(is_string($installTargetVersion ?? null) && trim((string) $installTargetVersion) !== '')
                            <input type="hidden" name="target_version" value="{{ $installTargetVersion }}">
                        @endif

                        @php
                            $installState = is_array($updateInstallState ?? null) ? $updateInstallState : null;
                            $installInProgress = is_array($installState) ? (bool) ($installState['in_progress'] ?? false) : false;
                            $installStatus = is_array($installState) && is_string($installState['status'] ?? null) ? $installState['status'] : null;
                            $installMessage = is_array($installState) && is_string($installState['message'] ?? null) ? $installState['message'] : null;
                            $installVersion = is_array($installState) && is_string($installState['version'] ?? null) ? $installState['version'] : null;
                        @endphp

                        @if($installInProgress || (is_string($installStatus) && trim((string) $installStatus) !== '') || (is_string($installMessage) && trim((string) $installMessage) !== ''))
                            <div class="mt-4 p-3 rounded-md border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Installer status</p>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            {{ is_string($installStatus) ? ucfirst($installStatus) : '—' }}
                                            @if(is_string($installVersion) && trim($installVersion) !== '')
                                                ({{ $installVersion }})
                                            @endif
                                        </p>
                                        @if(is_string($installMessage) && trim($installMessage) !== '')
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $installMessage }}</p>
                                        @endif
                                    </div>
                                    @if($installInProgress)
                                        @admincan('admin.settings.edit')
                                            <x-button
                                                type="submit"
                                                variant="danger"
                                                size="sm"
                                                formaction="{{ route('admin.settings.cancel-update') }}"
                                                formmethod="POST"
                                                onclick="return confirm('Are you sure you want to cancel the update? This will abort the process and bring the site out of maintenance mode.')"
                                            >
                                                Cancel Update
                                            </x-button>
                                        @endadmincan
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if(is_string($updateLastFailureReason ?? null) && trim((string) $updateLastFailureReason) !== '')
                            <div class="mt-4 p-3 rounded-md border border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                                <p class="text-sm font-semibold">Last update failed</p>
                                <p class="mt-1 text-sm">
                                    @if(is_string($updateLastFailureVersion ?? null) && trim((string) $updateLastFailureVersion) !== '')
                                        Version: {{ $updateLastFailureVersion }}
                                    @else
                                        Version: —
                                    @endif
                                    @if(is_string($updateLastFailureAt ?? null) && trim((string) $updateLastFailureAt) !== '')
                                        | Date: {{ $updateLastFailureAt }}
                                    @endif
                                </p>
                                <p class="mt-1 text-xs">{{ $updateLastFailureReason }}</p>
                            </div>
                        @endif

                        <div class="mt-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Refresh Application Cache</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Clear Laravel caches and compiled views after updating files on cPanel. This also attempts to reset PHP OPcache.</p>
                                </div>
                                @admincan('admin.settings.edit')
                                    <x-button
                                        type="submit"
                                        variant="secondary"
                                        size="sm"
                                        formaction="{{ route('admin.settings.clear-cache') }}"
                                        formmethod="POST"
                                        onclick="return confirm('Clear application cache now? This will refresh config, routes, views, and application cache.')"
                                    >Refresh Cache</x-button>
                                @endadmincan
                            </div>
                        </div>

                        @if(is_string($updateDownloadUrl ?? null) && trim((string) $updateDownloadUrl) !== '')
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Download URL</label>
                                <div class="mt-2 flex items-center gap-3">
                                    <input
                                        type="text"
                                        readonly
                                        value="{{ $updateDownloadUrl }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                    <x-button href="{{ $updateDownloadUrl }}" variant="secondary" size="sm" target="_blank" rel="noopener noreferrer">Open</x-button>
                                </div>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This link may expire depending on your update server configuration.</p>
                            </div>
                        @endif
                    </div>

                    <div class="pt-2">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Configuration</h4>
                        
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">

                            @php
                                $licenseCheckPayload = is_array($updateLicenseCheck ?? null) ? ($updateLicenseCheck['data'] ?? null) : null;
                                $licenseCheckTopStatus = is_array($licenseCheckPayload) && is_string($licenseCheckPayload['status'] ?? null) ? $licenseCheckPayload['status'] : null;
                                $licenseCheckTopMessage = is_array($licenseCheckPayload) && is_string($licenseCheckPayload['message'] ?? null) ? $licenseCheckPayload['message'] : null;

                                $licenseCheckRequestError = null;
                                if (is_array($updateLicenseCheck ?? null) && !($updateLicenseCheck['success'] ?? false)) {
                                    $licenseCheckRequestError = is_string($updateLicenseCheck['message'] ?? null) ? $updateLicenseCheck['message'] : null;
                                }

                                $licenseObject = null;
                                if (is_array($licenseCheckPayload) && is_array($licenseCheckPayload['data'] ?? null) && is_array($licenseCheckPayload['data']['license'] ?? null)) {
                                    $licenseObject = $licenseCheckPayload['data']['license'];
                                }

                                $savedLicense = null;
                                try {
                                    $savedLicense = \App\Models\Setting::get('update_license_key');
                                } catch (\Throwable $e) {
                                    $savedLicense = null;
                                }

                                $maskedLicense = null;
                                if (is_string($savedLicense) && trim($savedLicense) !== '') {
                                    $clean = trim((string) $savedLicense);
                                    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $clean)) {
                                        $maskedLicense = substr($clean, 0, 8) . '-XXXX-XXXX-XXXX-' . substr($clean, -12);
                                    } else {
                                        $maskedLicense = substr($clean, 0, 8) . '…' . substr($clean, -6);
                                    }
                                }
                            @endphp

                            <div class="md:col-span-2">
                                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">License</p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Status and support information from the license server.</p>
                                        </div>
                                        @admincan('admin.settings.edit')
                                            <div class="shrink-0">
                                                <x-button
                                                    type="button"
                                                    variant="danger"
                                                    size="sm"
                                                    onclick="if(confirm('Deactivate this license for the current domain?')) { deactivateLicense(); }"
                                                >Deactivate</x-button>
                                            </div>
                                        @endadmincan
                                    </div>

                                    @if(is_string($licenseCheckRequestError) && trim($licenseCheckRequestError) !== '')
                                        <p class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $licenseCheckRequestError }}</p>
                                    @endif

                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ is_string($licenseObject['status'] ?? null) ? $licenseObject['status'] : ($licenseCheckTopStatus ?: '—') }}</p>
                                            @if(is_string($licenseCheckTopMessage) && trim($licenseCheckTopMessage) !== '')
                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $licenseCheckTopMessage }}</p>
                                            @endif
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">License</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $maskedLicense ?: '—' }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Support Expiry</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ is_string($licenseObject['supported_until'] ?? null) ? $licenseObject['supported_until'] : '—' }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">License Type</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ is_string($licenseObject['license_type'] ?? null) ? $licenseObject['license_type'] : '—' }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Purchase Date</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ is_string($licenseObject['purchase_date'] ?? null) ? $licenseObject['purchase_date'] : '—' }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Envato Username</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ is_string($licenseObject['envato_username'] ?? null) ? $licenseObject['envato_username'] : '—' }}</p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Domains</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ is_numeric($licenseObject['active_domains'] ?? null) ? (int) $licenseObject['active_domains'] : '—' }}
                                                /
                                                {{ is_numeric($licenseObject['max_domains'] ?? null) ? (int) $licenseObject['max_domains'] : '—' }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Normalized Domain</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ is_string($licenseObject['normalized_domain'] ?? null) ? $licenseObject['normalized_domain'] : '—' }}</p>
                                        </div>
                                    </div>

                                    @if(is_array($licenseObject) && is_array($licenseObject['domains'] ?? null) && !empty($licenseObject['domains']))
                                        <div class="mt-4">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Registered Domains</p>
                                            <div class="mt-2 overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                                        <tr>
                                                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Domain</th>
                                                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                                        @foreach($licenseObject['domains'] as $d => $st)
                                                            <tr>
                                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ is_string($d) ? $d : '' }}</td>
                                                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ is_string($st) ? $st : '' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>


                            <div class="md:col-span-2">
                                <label for="setting_update_license_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">License Key</label>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter your purchase code / license key and click Activate.</p>
                                <div class="mt-2 flex gap-2">
                                    <input
                                        type="text"
                                        name="update_license_key"
                                        id="setting_update_license_key"
                                        value="{{ old('update_license_key') }}"
                                        autocomplete="new-password"
                                        placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                    <button
                                        type="button"
                                        onclick="activateLicense()"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 whitespace-nowrap"
                                    >
                                        Activate
                                    </button>
                                </div>
                                @error('update_license_key')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <script>
                                function activateLicense() {
                                    var licenseKey = document.getElementById('setting_update_license_key').value;
                                    var form = document.createElement('form');
                                    form.method = 'POST';
                                    form.action = '{{ route('admin.settings.license-activate') }}';
                                    
                                    var csrf = document.createElement('input');
                                    csrf.type = 'hidden';
                                    csrf.name = '_token';
                                    csrf.value = '{{ csrf_token() }}';
                                    form.appendChild(csrf);
                                    
                                    var input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'update_license_key';
                                    input.value = licenseKey;
                                    form.appendChild(input);
                                    
                                    document.body.appendChild(form);
                                    form.submit();
                                }

                                function deactivateLicense() {
                                    var form = document.createElement('form');
                                    form.method = 'POST';
                                    form.action = '{{ route('admin.settings.license-deactivate') }}';
                                    
                                    var csrf = document.createElement('input');
                                    csrf.type = 'hidden';
                                    csrf.name = '_token';
                                    csrf.value = '{{ csrf_token() }}';
                                    form.appendChild(csrf);
                                    
                                    document.body.appendChild(form);
                                    form.submit();
                                }
                            </script>
                        </div>
                    </div>
                </div>

            @elseif($category === 'changelogs')
                @php
                    $items = [];
                    $changelogMap = [];
                    $releasesMap = [];
                    $sortedVersions = [];
                    if (is_array($updateChangelogs ?? null)) {
                        $data = $updateChangelogs['data'] ?? null;

                        if (is_array($data) && is_array($data['changelog'] ?? null)) {
                            $changelogMap = $data['changelog'];
                            $releasesMap = is_array($data['releases'] ?? null) ? $data['releases'] : [];
                        } elseif (is_array($data) && is_array($data['changelogs'] ?? null)) {
                            $items = $data['changelogs'];
                        } elseif (is_array($data) && is_array($data['data'] ?? null)) {
                            $items = $data['data'];
                        } elseif (is_array($data)) {
                            $items = $data;
                        }
                    }

                    if (is_array($changelogMap) && !empty($changelogMap)) {
                        $sortedVersions = array_keys($changelogMap);
                        usort($sortedVersions, function ($a, $b) {
                            return version_compare((string) $b, (string) $a);
                        });
                    }
                @endphp

                <div class="space-y-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Changelogs</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Release notes fetched from your update server.</p>
                        </div>
                    </div>

                    @if(is_array($updateChangelogs ?? null) && !($updateChangelogs['success'] ?? false))
                        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg dark:bg-red-900/50 dark:border-red-800 dark:text-red-200">
                            {{ $updateChangelogs['message'] ?? 'Unable to fetch changelogs.' }}
                        </div>
                    @endif

                    @if(!empty($sortedVersions))
                        <div class="space-y-4">
                            @foreach($sortedVersions as $version)
                                @php
                                    $release = is_array($releasesMap[$version] ?? null) ? $releasesMap[$version] : [];
                                    $releaseDate = is_string($release['release_date'] ?? null) ? $release['release_date'] : null;

                                    $releaseChangelog = is_array($release['changelog'] ?? null) ? $release['changelog'] : null;
                                    $entry = is_array($releaseChangelog) ? $releaseChangelog : (is_array($changelogMap[$version] ?? null) ? $changelogMap[$version] : []);
                                @endphp

                                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between gap-4">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $version }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $releaseDate ?: '' }}</p>
                                    </div>

                                    <div class="mt-3 space-y-3">
                                        @foreach($entry as $type => $messages)
                                            @php
                                                $title = is_string($type) ? ucfirst(str_replace('_', ' ', $type)) : 'Changes';
                                                $lines = [];

                                                $messageList = [];
                                                if (is_string($messages) && trim($messages) !== '') {
                                                    $messageList = [$messages];
                                                } elseif (is_array($messages)) {
                                                    $messageList = $messages;
                                                }

                                                if (!empty($messageList)) {
                                                    foreach ($messageList as $msg) {
                                                        if (!is_string($msg)) {
                                                            continue;
                                                        }

                                                        $parts = array_map('trim', explode(',', $msg));
                                                        foreach ($parts as $part) {
                                                            if ($part !== '') {
                                                                $lines[] = $part;
                                                            }
                                                        }
                                                    }
                                                }
                                            @endphp

                                            @if(!empty($lines))
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</p>
                                                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                                        @foreach($lines as $line)
                                                            <li>{{ $line }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif(!empty($items))
                        <div class="space-y-4">
                            @foreach($items as $item)
                                @php
                                    $version = is_array($item) && is_string($item['version'] ?? null) ? $item['version'] : null;
                                    $date = is_array($item) && is_string($item['date'] ?? null) ? $item['date'] : null;
                                    $content = is_array($item) && is_string($item['content'] ?? null) ? $item['content'] : null;
                                @endphp

                                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between gap-4">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $version ?: 'Release' }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $date ?: '' }}</p>
                                    </div>
                                    @if($content)
                                        <div class="mt-3 prose prose-sm max-w-none dark:prose-invert">
                                            {!! $content !!}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <p>No changelog entries available.</p>
                        </div>
                    @endif
                </div>

            @elseif($category === 'cron')
                @php
                    $settingsByKey = $settings->get($category, collect())->keyBy('key');
                    $cronRunOutput = session('cron_run_output');
                    $phpPathOptions = [
                        '/usr/bin/php',
                        '/bin/php',
                        '/usr/bin/php7.4',
                        '/usr/bin/php8.0',
                        '/usr/bin/php8.1',
                        '/usr/bin/php8.3',
                        '/usr/lib/php',
                        '/etc/php',
                        '/usr/include/php',
                        '/usr/share/php',
                    ];
                    $detectedPhpBinary = is_string(PHP_BINARY ?? null) ? (string) PHP_BINARY : '';
                    $selectedPhpPath = $detectedPhpBinary !== '' ? $detectedPhpBinary : '/usr/bin/php';
                    if ($detectedPhpBinary !== '' && !in_array($detectedPhpBinary, $phpPathOptions, true)) {
                        array_unshift($phpPathOptions, $detectedPhpBinary);
                    }
                    $isCustomPhpPath = !in_array($selectedPhpPath, $phpPathOptions, true);
                    $artisanPath = base_path('artisan');
                @endphp

                <div class="space-y-6">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Background Job Setup</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select your PHP executable path, then copy and paste these commands into cPanel Cron Jobs.</p>
                    </div>

                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Please specify the PHP executable path on your system:</p>
                            <div class="mt-3 space-y-2" id="cron-php-path-options">
                                @foreach($phpPathOptions as $phpPath)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input
                                            type="radio"
                                            name="cron_php_path_choice"
                                            value="{{ $phpPath }}"
                                            {{ $selectedPhpPath === $phpPath ? 'checked' : '' }}
                                            class="border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                        >
                                        <span>
                                            {{ $phpPath }}
                                            @if($phpPath === $detectedPhpBinary)
                                                <span class="text-xs text-green-600 dark:text-green-400">(Auto-detected)</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input
                                        type="radio"
                                        name="cron_php_path_choice"
                                        value="other"
                                        {{ $isCustomPhpPath ? 'checked' : '' }}
                                        class="border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                    >
                                    <span>Other path</span>
                                </label>
                                <div>
                                    <input
                                        type="text"
                                        id="cron_php_path_custom"
                                        placeholder="/opt/cpanel/ea-php81/root/usr/bin/php"
                                        value="{{ $isCustomPhpPath ? $selectedPhpPath : '' }}"
                                        class="block w-full md:w-2/3 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Insert the following line into your system cron tab. You can adjust the timing if needed.</p>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Scheduler command</label>
                                <div class="mt-2 flex items-center gap-2">
                                    <input
                                        type="text"
                                        id="cron_schedule_command"
                                        readonly
                                        value="* * * * * {{ $selectedPhpPath }} {{ $artisanPath }} schedule:run >> /dev/null 2>&1"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                    <x-button type="button" variant="secondary" size="sm" data-copy-target="cron_schedule_command">Copy</x-button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(is_string($cronRunOutput) && trim((string) $cronRunOutput) !== '')
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Last run output</p>
                            <pre class="mt-2 text-xs whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ $cronRunOutput }}</pre>
                        </div>
                    @endif

                    <script>
                        (function () {
                            var phpPathOptions = document.querySelectorAll('input[name="cron_php_path_choice"]');
                            var customPhpInput = document.getElementById('cron_php_path_custom');
                            var schedulerInput = document.getElementById('cron_schedule_command');
                            var copyButtons = document.querySelectorAll('[data-copy-target]');
                            var artisanPath = @json($artisanPath);

                            function getSelectedPhpPath() {
                                var checked = document.querySelector('input[name="cron_php_path_choice"]:checked');
                                if (!checked) return '/usr/bin/php';
                                if (checked.value === 'other') {
                                    var customVal = customPhpInput ? customPhpInput.value.trim() : '';
                                    return customVal !== '' ? customVal : '/usr/bin/php';
                                }
                                return checked.value;
                            }

                            function updateCommands() {
                                var phpPath = getSelectedPhpPath();
                                if (schedulerInput) {
                                    schedulerInput.value = '* * * * * ' + phpPath + ' ' + artisanPath + ' schedule:run >> /dev/null 2>&1';
                                }
                            }

                            for (var i = 0; i < phpPathOptions.length; i++) {
                                phpPathOptions[i].addEventListener('change', updateCommands);
                            }

                            if (customPhpInput) {
                                customPhpInput.addEventListener('input', function () {
                                    var otherOption = document.querySelector('input[name="cron_php_path_choice"][value="other"]');
                                    if (otherOption) {
                                        otherOption.checked = true;
                                    }
                                    updateCommands();
                                });
                            }

                            for (var j = 0; j < copyButtons.length; j++) {
                                copyButtons[j].addEventListener('click', function (event) {
                                    var button = event.currentTarget;
                                    var targetId = button.getAttribute('data-copy-target');
                                    var targetInput = targetId ? document.getElementById(targetId) : null;
                                    if (!targetInput) return;

                                    targetInput.select();
                                    targetInput.setSelectionRange(0, targetInput.value.length);

                                    var originalLabel = button.textContent;

                                    if (navigator.clipboard && navigator.clipboard.writeText) {
                                        navigator.clipboard.writeText(targetInput.value).then(function () {
                                            button.textContent = 'Copied';
                                            setTimeout(function () {
                                                button.textContent = originalLabel;
                                            }, 1200);
                                        }).catch(function () {
                                            button.textContent = 'Copy failed';
                                            setTimeout(function () {
                                                button.textContent = originalLabel;
                                            }, 1200);
                                        });
                                        return;
                                    }

                                    try {
                                        document.execCommand('copy');
                                        button.textContent = 'Copied';
                                    } catch (e) {
                                        button.textContent = 'Copy failed';
                                    }

                                    setTimeout(function () {
                                        button.textContent = originalLabel;
                                    }, 1200);
                                });
                            }

                            updateCommands();
                        })();
                    </script>
                </div>

            @elseif($category === 'storage')
                @php
                    $settingsByKey = $settings->get($category, collect())->keyBy('key');
                    $getVal = function (string $key, $default = null) {
                        try {
                            return \App\Models\Setting::get($key, $default);
                        } catch (\Throwable $e) {
                            return $default;
                        }
                    };

                    $s3Configured = is_string($getVal('s3_key')) && trim((string) $getVal('s3_key')) !== ''
                        && is_string($getVal('s3_secret')) && trim((string) $getVal('s3_secret')) !== ''
                        && is_string($getVal('s3_region')) && trim((string) $getVal('s3_region')) !== ''
                        && is_string($getVal('s3_bucket')) && trim((string) $getVal('s3_bucket')) !== '';

                    $wasabiConfigured = is_string($getVal('wasabi_key')) && trim((string) $getVal('wasabi_key')) !== ''
                        && is_string($getVal('wasabi_secret')) && trim((string) $getVal('wasabi_secret')) !== ''
                        && is_string($getVal('wasabi_region')) && trim((string) $getVal('wasabi_region')) !== ''
                        && is_string($getVal('wasabi_bucket')) && trim((string) $getVal('wasabi_bucket')) !== ''
                        && is_string($getVal('wasabi_endpoint')) && trim((string) $getVal('wasabi_endpoint')) !== '';

                    $gcsConfigured = is_string($getVal('gcs_project_id')) && trim((string) $getVal('gcs_project_id')) !== ''
                        && is_string($getVal('gcs_bucket')) && trim((string) $getVal('gcs_bucket')) !== ''
                        && is_string($getVal('gcs_key_file')) && trim((string) $getVal('gcs_key_file')) !== '';
                @endphp

                <div class="space-y-8">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Providers</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enable/disable providers. The app will use your default storage from General settings, and fall back to Local if the provider is disabled or not configured.</p>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Provider</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Active</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Configured</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">Local</td>
                                        <td class="px-4 py-3">
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="checkbox"
                                                    name="storage_local_enabled"
                                                    value="1"
                                                    {{ ($settingsByKey['storage_local_enabled']->value ?? false) ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                                >
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enabled</span>
                                            </label>
                                            @error('storage_local_enabled')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">Yes</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">Amazon S3</td>
                                        <td class="px-4 py-3">
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="checkbox"
                                                    name="storage_s3_enabled"
                                                    value="1"
                                                    {{ ($settingsByKey['storage_s3_enabled']->value ?? false) ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                                >
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enabled</span>
                                            </label>
                                            @error('storage_s3_enabled')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $s3Configured ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                            {{ $s3Configured ? 'Yes' : 'No' }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">Wasabi</td>
                                        <td class="px-4 py-3">
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="checkbox"
                                                    name="storage_wasabi_enabled"
                                                    value="1"
                                                    {{ ($settingsByKey['storage_wasabi_enabled']->value ?? false) ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                                >
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enabled</span>
                                            </label>
                                            @error('storage_wasabi_enabled')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $wasabiConfigured ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                            {{ $wasabiConfigured ? 'Yes' : 'No' }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">Google Cloud Storage</td>
                                        <td class="px-4 py-3">
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="checkbox"
                                                    name="storage_gcs_enabled"
                                                    value="1"
                                                    {{ ($settingsByKey['storage_gcs_enabled']->value ?? false) ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                                >
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enabled</span>
                                            </label>
                                            @error('storage_gcs_enabled')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $gcsConfigured ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                            {{ $gcsConfigured ? 'Yes' : 'No' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Common</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="setting_storage_public_root" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Storage Public Root</label>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Root folder/prefix to store public assets under (optional).</p>
                                <div class="mt-2">
                                    @php
                                        $storagePublicRootOptions = [
                                            '' => 'None',
                                            'public' => 'public',
                                            'uploads' => 'uploads',
                                            'assets' => 'assets',
                                            'media' => 'media',
                                        ];

                                        $storagePublicRootValue = $getVal('storage_public_root', 'public');
                                        $storagePublicRootValue = is_string($storagePublicRootValue) ? trim($storagePublicRootValue) : '';
                                        $storagePublicRootIsCustom = !array_key_exists($storagePublicRootValue, $storagePublicRootOptions);
                                    @endphp
                                    <select
                                        name="storage_public_root"
                                        id="setting_storage_public_root"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                        @if($storagePublicRootIsCustom)
                                            <option value="{{ $storagePublicRootValue }}" selected>
                                                {{ $storagePublicRootValue === '' ? 'None' : $storagePublicRootValue }}
                                            </option>
                                        @endif
                                        @foreach($storagePublicRootOptions as $optValue => $optLabel)
                                            <option value="{{ $optValue }}" {{ (string) $storagePublicRootValue === (string) $optValue ? 'selected' : '' }}>
                                                {{ $optLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('storage_public_root')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="setting_storage_url_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Storage URL Prefix</label>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional prefix added before /storage in public URLs. Example: <span class="font-mono">public</span> results in <span class="font-mono">/public/storage/...</span>.</p>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="storage_url_prefix"
                                        id="setting_storage_url_prefix"
                                        value="{{ $getVal('storage_url_prefix') }}"
                                        placeholder="public"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('storage_url_prefix')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Amazon S3 Settings</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="setting_s3_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">S3 Key</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="s3_key"
                                        id="setting_s3_key"
                                        value="{{ $getVal('s3_key') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('s3_key')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_s3_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">S3 Secret</label>
                                <div class="mt-2">
                                    <input
                                        type="password"
                                        name="s3_secret"
                                        id="setting_s3_secret"
                                        value=""
                                        autocomplete="new-password"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('s3_secret')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_s3_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">S3 Region</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="s3_region"
                                        id="setting_s3_region"
                                        value="{{ $getVal('s3_region') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('s3_region')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_s3_bucket" class="block text-sm font-medium text-gray-700 dark:text-gray-300">S3 Bucket</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="s3_bucket"
                                        id="setting_s3_bucket"
                                        value="{{ $getVal('s3_bucket') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('s3_bucket')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_s3_endpoint" class="block text-sm font-medium text-gray-700 dark:text-gray-300">S3 Endpoint</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="s3_endpoint"
                                        id="setting_s3_endpoint"
                                        value="{{ $getVal('s3_endpoint') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('s3_endpoint')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_s3_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">S3 URL</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="s3_url"
                                        id="setting_s3_url"
                                        value="{{ $getVal('s3_url') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('s3_url')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Wasabi Settings</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="setting_wasabi_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wasabi Key</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="wasabi_key"
                                        id="setting_wasabi_key"
                                        value="{{ $getVal('wasabi_key') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('wasabi_key')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_wasabi_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wasabi Secret</label>
                                <div class="mt-2">
                                    <input
                                        type="password"
                                        name="wasabi_secret"
                                        id="setting_wasabi_secret"
                                        value=""
                                        autocomplete="new-password"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('wasabi_secret')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_wasabi_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wasabi Region</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="wasabi_region"
                                        id="setting_wasabi_region"
                                        value="{{ $getVal('wasabi_region', 'us-east-1') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('wasabi_region')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_wasabi_bucket" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wasabi Bucket</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="wasabi_bucket"
                                        id="setting_wasabi_bucket"
                                        value="{{ $getVal('wasabi_bucket') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('wasabi_bucket')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_wasabi_endpoint" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wasabi Endpoint</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="wasabi_endpoint"
                                        id="setting_wasabi_endpoint"
                                        value="{{ $getVal('wasabi_endpoint') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('wasabi_endpoint')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_wasabi_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wasabi URL</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="wasabi_url"
                                        id="setting_wasabi_url"
                                        value="{{ $getVal('wasabi_url') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('wasabi_url')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Google Cloud Storage Settings</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="setting_gcs_project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">GCS Project ID</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="gcs_project_id"
                                        id="setting_gcs_project_id"
                                        value="{{ $getVal('gcs_project_id') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('gcs_project_id')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_gcs_bucket" class="block text-sm font-medium text-gray-700 dark:text-gray-300">GCS Bucket</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="gcs_bucket"
                                        id="setting_gcs_bucket"
                                        value="{{ $getVal('gcs_bucket') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('gcs_bucket')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_gcs_key_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">GCS Key File</label>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Absolute path to service account JSON file on the server.</p>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="gcs_key_file"
                                        id="setting_gcs_key_file"
                                        value="{{ $getVal('gcs_key_file') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('gcs_key_file')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_gcs_path_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">GCS Path Prefix</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="gcs_path_prefix"
                                        id="setting_gcs_path_prefix"
                                        value="{{ $getVal('gcs_path_prefix') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('gcs_path_prefix')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="setting_gcs_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">GCS URL</label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        name="gcs_url"
                                        id="setting_gcs_url"
                                        value="{{ $getVal('gcs_url') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                </div>
                                @error('gcs_url')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            @elseif($category === 'templates')
                @php
                    try {
                        $activeVariant = \App\Models\Setting::get('home_page_variant', '1');
                    } catch (\Throwable $e) {
                        $activeVariant = '1';
                    }
                    $activeVariant = is_string($activeVariant) ? trim($activeVariant) : '1';

                    try {
                        $activeTemplateId = \App\Models\Setting::get('active_public_template', '');
                    } catch (\Throwable $e) {
                        $activeTemplateId = '';
                    }
                    $activeTemplateId = is_string($activeTemplateId) ? trim($activeTemplateId) : '';

                    try {
                        $externalApiBaseUrl = \App\Models\Setting::get('external_templates_api_base_url', '');
                    } catch (\Throwable $e) {
                        $externalApiBaseUrl = '';
                    }
                    $externalApiBaseUrl = is_string($externalApiBaseUrl) ? trim($externalApiBaseUrl) : '';

                    try {
                        $externalApiProductId = \App\Models\Setting::get('external_templates_api_product_id', '');
                    } catch (\Throwable $e) {
                        $externalApiProductId = '';
                    }
                    $externalApiProductId = is_string($externalApiProductId) ? trim($externalApiProductId) : '';

                    try {
                        $externalApiResourceType = \App\Models\Setting::get('external_templates_api_resource_type', 'Post');
                    } catch (\Throwable $e) {
                        $externalApiResourceType = 'Post';
                    }
                    $externalApiResourceType = is_string($externalApiResourceType) ? trim($externalApiResourceType) : 'Post';

                    try {
                        $externalLicenseKey = \App\Models\Setting::get('external_templates_license_key', '');
                    } catch (\Throwable $e) {
                        $externalLicenseKey = '';
                    }
                    $externalLicenseKey = is_string($externalLicenseKey) ? trim($externalLicenseKey) : '';

                    try {
                        $externalLicenseActive = (bool) \App\Models\Setting::get('external_templates_license_active', 0);
                    } catch (\Throwable $e) {
                        $externalLicenseActive = false;
                    }

                    try {
                        $externalTemplates = \App\Models\ExternalTemplate::query()
                            ->orderByDesc('external_updated_at')
                            ->orderByDesc('updated_at')
                            ->limit(60)
                            ->get();
                    } catch (\Throwable $e) {
                        $externalTemplates = collect();
                    }

                    $templates = [
                        [
                            'id' => 'home-5',
                            'name' => 'Modern Landing',
                            'variant' => '5',
                            'description' => 'Modern, conversion-focused homepage with editable text and branding.',
                            'is_pro' => false,
                        ],
                        [
                            'id' => 'home-1-pro',
                            'name' => 'Classic SaaS',
                            'variant' => '1',
                            'description' => 'A classic SaaS layout (Pro).',
                            'is_pro' => true,
                        ],
                        [
                            'id' => 'home-2-pro',
                            'name' => 'Minimal Startup',
                            'variant' => '2',
                            'description' => 'A clean, minimal layout (Pro).',
                            'is_pro' => true,
                        ],
                    ];
                @endphp

                <div class="space-y-6">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Templates</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose a homepage template, then customize its text, colors, and icons with a live preview.</p>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">External templates</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Synced from your API. Click “Fetch JSON” to store the template payload locally.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $externalLicenseActive ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                                {{ $externalLicenseActive ? 'License Active' : 'License Inactive' }}
                            </span>
                            <x-button type="submit" variant="secondary" size="sm" formaction="{{ route('admin.settings.templates.external.sync') }}" formmethod="POST">Sync</x-button>
                        </div>
                    </div>

                    @if($externalTemplates->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach($externalTemplates as $ext)
                                @php
                                    $extRequiresLicense = (bool) ($ext->requires_license ?? false);
                                    $extLocked = $extRequiresLicense && !$externalLicenseActive;
                                    $extHasJson = !empty($ext->json_fetched_at) && is_string($ext->json_code ?? null) && trim((string) ($ext->json_code ?? '')) !== '';
                                @endphp

                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                                    <div class="relative aspect-[16/9] bg-gray-100 dark:bg-gray-900">
                                        @if(is_string($ext->preview_image ?? null) && trim((string) $ext->preview_image) !== '')
                                            <img src="{{ $ext->preview_image }}" alt="{{ $ext->name }}" class="absolute inset-0 h-full w-full object-cover">
                                        @endif
                                        <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/50 to-transparent"></div>

                                        <div class="absolute left-3 top-3 flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $extLocked ? 'bg-yellow-500 text-white' : 'bg-emerald-600 text-white' }}">
                                                {{ $extRequiresLicense ? 'Pro' : 'Free' }}
                                            </span>
                                            @if($extHasJson)
                                                <span class="inline-flex items-center rounded-full bg-primary-600 px-2.5 py-1 text-xs font-semibold text-white">JSON Saved</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="p-4 space-y-3">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $ext->name }}</p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">#{{ (int) $ext->external_id }} · {{ $ext->builder ?? 'builder' }} · {{ $ext->plan ?? 'plan' }}</p>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            @if(is_string($ext->preview_url ?? null) && trim((string) $ext->preview_url) !== '')
                                                <a href="{{ $ext->preview_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Preview</a>
                                            @endif

                                            @if($extLocked)
                                                <button type="button" class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" data-pro-license-open="1">
                                                    <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                        <path d="M5 7l3 5 4-7 4 7 3-5v10H5V7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    Customize
                                                </button>
                                            @else
                                                <x-button type="submit" variant="secondary" size="sm" formaction="{{ route('admin.settings.templates.external.fetch-json', ['externalId' => (int) $ext->external_id]) }}" formmethod="POST">Fetch JSON</x-button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($templates as $template)
                            @php
                                $isPro = (bool) ($template['is_pro'] ?? false);
                                $templateId = (string) ($template['id'] ?? '');
                                $isActive = $activeTemplateId !== ''
                                    ? (string) $activeTemplateId === (string) $templateId
                                    : (string) ($template['variant'] ?? '') === (string) $activeVariant;
                            @endphp

                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                                <div class="relative aspect-[16/9] bg-gradient-to-br from-gray-900 via-gray-700 to-gray-500">
                                    <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/50 to-transparent"></div>

                                    <div class="absolute left-3 top-3 flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $isPro ? 'bg-yellow-500 text-white' : 'bg-emerald-600 text-white' }}">
                                            {{ $isPro ? 'Pro' : 'Free' }}
                                        </span>
                                        @if($isActive)
                                            <span class="inline-flex items-center rounded-full bg-primary-600 px-2.5 py-1 text-xs font-semibold text-white">Active</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-4 space-y-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $template['name'] ?? '' }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $template['description'] ?? '' }}</p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <a
                                            href="{{ route('admin.settings.templates.preview', ['template' => $templateId]) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >Preview</a>

                                        @if($isPro && !$externalLicenseActive)
                                            <button type="button" class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" data-pro-license-open="1">
                                                <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path d="M5 7l3 5 4-7 4 7 3-5v10H5V7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                Customize
                                            </button>
                                        @else
                                            <x-button
                                                type="submit"
                                                variant="secondary"
                                                size="sm"
                                                formaction="{{ route('admin.settings.templates.activate', ['template' => $templateId]) }}"
                                                formmethod="POST"
                                            >Activate</x-button>

                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                                                data-template-import="1"
                                                data-template-id="{{ $templateId }}"
                                            >
                                                @if($isPro)
                                                    <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                        <path d="M5 7l3 5 4-7 4 7 3-5v10H5V7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                @endif
                                                Import
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <x-page-editor-modal name="page-editor" />

                    <div id="template-customizer" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
                        <div class="absolute inset-0 bg-black/60" data-template-customizer-close></div>
                        <div class="absolute inset-0 flex items-center justify-center p-4">
                            <div class="relative flex h-[92vh] w-full max-w-7xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900">
                                <div class="flex w-full flex-col xl:flex-row">
                                    <div class="flex-1 border-b border-gray-200 dark:border-gray-800 xl:border-b-0 xl:border-r">
                                        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100" data-template-customizer-title>Customize</p>
                                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Live preview updates as you type.</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a data-template-customizer-open-preview target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Open</a>
                                                <button type="button" class="inline-flex items-center rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800" data-template-customizer-close>Close</button>
                                            </div>
                                        </div>
                                        <iframe data-template-customizer-iframe class="h-[calc(92vh-52px)] w-full bg-white dark:bg-gray-900" src="about:blank"></iframe>
                                    </div>

                                    <div class="w-full xl:w-[460px]">
                                        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Customizations</p>
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="inline-flex items-center rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800" data-template-customizer-activate>Activate</button>
                                                <button type="button" class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" data-template-customizer-save>Save</button>
                                            </div>
                                        </div>

                                        <div class="h-[calc(92vh-52px)] overflow-y-auto px-4 py-4">
                                            <div class="hidden rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-900/30 dark:text-green-200" data-template-customizer-success></div>
                                            <div class="hidden rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/30 dark:text-red-200" data-template-customizer-error></div>

                                            <div class="space-y-5" data-template-customizer-form>
                                                 <div data-template-field="brand_color">
                                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Brand Color</label>
                                                    <div class="mt-2 flex items-center">
                                                        <input type="color" name="brand_color" class="admin-color-picker" aria-label="Brand color">
                                                    </div>
                                                </div>

                                                <div data-template-field="hero_title">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hero Title</label>
                                                    <div class="mt-2">
                                                        <input type="text" name="hero_title" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:text-sm">
                                                    </div>
                                                </div>

                                                <div data-template-field="hero_subtitle">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hero Subtitle</label>
                                                    <div class="mt-2">
                                                        <textarea name="hero_subtitle" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:text-sm"></textarea>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 gap-5">
                                                    <div data-template-field="cta_text">
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Primary CTA</label>
                                                        <div class="mt-2">
                                                            <input type="text" name="cta_text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:text-sm">
                                                        </div>
                                                    </div>
                                                    <div data-template-field="cta_secondary_text">
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Secondary CTA</label>
                                                        <div class="mt-2">
                                                            <input type="text" name="cta_secondary_text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:text-sm">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 gap-5">
                                                    <div data-template-field="stat_emails_sent">
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stat: Emails Sent</label>
                                                        <div class="mt-2">
                                                            <input type="text" name="stat_emails_sent" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:text-sm">
                                                        </div>
                                                    </div>
                                                    <div data-template-field="stat_users">
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stat: Users</label>
                                                        <div class="mt-2">
                                                            <input type="text" name="stat_users" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:text-sm">
                                                        </div>
                                                    </div>
                                                    <div data-template-field="stat_uptime">
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stat: Uptime</label>
                                                        <div class="mt-2">
                                                            <input type="text" name="stat_uptime" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:text-sm">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Integration Icons</p>
                                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lucide icon names (e.g. zap, message-square, globe).</p>

                                                    <div class="mt-4 grid grid-cols-1 gap-5">
                                                        @for($i = 1; $i <= 6; $i++)
                                                            @php $k = 'integration_' . $i . '_icon'; @endphp
                                                            <div data-template-field="{{ $k }}">
                                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Integration {{ $i }} Icon</label>
                                                                <div class="mt-2">
                                                                    <input type="text" name="{{ $k }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:text-sm">
                                                                </div>
                                                            </div>
                                                        @endfor
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="pro-license-modal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
                        <div class="absolute inset-0 bg-black/60" data-pro-license-close></div>
                        <div class="absolute inset-0 flex items-center justify-center p-4">
                            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900">
                                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">Pro content access</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Activate your license to unlock Pro templates.</p>
                                    </div>
                                    <button type="button" class="inline-flex items-center rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800" data-pro-license-close>Close</button>
                                </div>

                                <div class="px-5 py-5 space-y-4">
                                    <div class="hidden rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-900/30 dark:text-green-200" data-pro-license-success></div>
                                    <div class="hidden rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/30 dark:text-red-200" data-pro-license-error></div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">License Key</label>
                                        <div class="mt-2">
                                            <input type="text" id="pro_license_key" class="block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:text-gray-100 sm:text-sm" value="{{ $externalLicenseKey }}" placeholder="Enter your license key">
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700" data-pro-license-activate>Activate</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        (function () {
                            var modal = document.getElementById('template-customizer');
                            if (!modal) return;

                            var csrf = document.querySelector('meta[name="csrf-token"]');
                            var csrfToken = csrf ? csrf.getAttribute('content') : '';

                            var closeEls = modal.querySelectorAll('[data-template-customizer-close]');
                            var iframe = modal.querySelector('[data-template-customizer-iframe]');
                            var titleEl = modal.querySelector('[data-template-customizer-title]');
                            var openPreviewEl = modal.querySelector('[data-template-customizer-open-preview]');
                            var formRoot = modal.querySelector('[data-template-customizer-form]');
                            var saveBtn = modal.querySelector('[data-template-customizer-save]');
                            var activateBtn = modal.querySelector('[data-template-customizer-activate]');
                            var okBox = modal.querySelector('[data-template-customizer-success]');
                            var errBox = modal.querySelector('[data-template-customizer-error]');

                            var currentTemplateId = null;
                            var previewBase = null;

                            var hideMessages = function () {
                                if (okBox) okBox.classList.add('hidden');
                                if (errBox) errBox.classList.add('hidden');
                            };

                            var showError = function (msg) {
                                if (!errBox) return;
                                errBox.textContent = msg;
                                errBox.classList.remove('hidden');
                            };

                            var showSuccess = function (msg) {
                                if (!okBox) return;
                                okBox.textContent = msg;
                                okBox.classList.remove('hidden');
                            };

                            var setFieldVisible = function (key, visible) {
                                var el = modal.querySelector('[data-template-field="' + key + '"]');
                                if (!el) return;
                                if (visible) {
                                    el.classList.remove('hidden');
                                } else {
                                    el.classList.add('hidden');
                                }
                            };

                            var setValue = function (key, val) {
                                var input = modal.querySelector('[name="' + key + '"]');
                                if (!input) return;
                                input.value = (val === null || typeof val === 'undefined') ? '' : String(val);
                            };

                            var updateBrandText = function () {
                                var colorInput = modal.querySelector('[name="brand_color"]');
                                var textInput = modal.querySelector('[name="brand_color_text"]');
                                if (!colorInput || !textInput) return;
                                textInput.value = colorInput.value;
                            };

                            var buildPreviewUrl = function () {
                                if (!previewBase) return null;
                                var params = new URLSearchParams();

                                var inputs = modal.querySelectorAll('[data-template-customizer-form] [name]');
                                for (var i = 0; i < inputs.length; i++) {
                                    var el = inputs[i];
                                    var name = el.getAttribute('name');
                                    if (!name || name === 'brand_color_text') continue;
                                    params.append(name, el.value);
                                }

                                return previewBase + '?' + params.toString();
                            };

                            var refreshPreview = function () {
                                var url = buildPreviewUrl();
                                if (!url) return;
                                if (iframe) iframe.setAttribute('src', url);
                                if (openPreviewEl) openPreviewEl.setAttribute('href', url);
                            };

                            var openModal = function (templateId) {
                                currentTemplateId = templateId;
                                hideMessages();

                                previewBase = @json(route('admin.settings.templates.preview', ['template' => '__TPL__'])).replace('__TPL__', encodeURIComponent(templateId));
                                var valuesUrl = @json(route('admin.settings.templates.values', ['template' => '__TPL__'])).replace('__TPL__', encodeURIComponent(templateId));

                                if (titleEl) titleEl.textContent = 'Customize: ' + templateId;
                                if (iframe) iframe.setAttribute('src', previewBase);
                                if (openPreviewEl) openPreviewEl.setAttribute('href', previewBase);

                                modal.classList.remove('hidden');
                                modal.setAttribute('aria-hidden', 'false');
                                document.body.classList.add('overflow-hidden');

                                fetch(valuesUrl, {
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                }).then(function (r) {
                                    if (!r.ok) throw new Error('Failed to load template values');
                                    return r.json();
                                }).then(function (payload) {
                                    var keys = Array.isArray(payload.editable_keys) ? payload.editable_keys : [];
                                    var values = payload.values && typeof payload.values === 'object' ? payload.values : {};

                                    if (payload.template && payload.template.name && titleEl) {
                                        titleEl.textContent = 'Customize: ' + payload.template.name;
                                    }

                                    var allWrappers = modal.querySelectorAll('[data-template-customizer-form] [data-template-field]');
                                    for (var i = 0; i < allWrappers.length; i++) {
                                        allWrappers[i].classList.remove('hidden');
                                    }

                                    if (keys.length) {
                                        for (var i2 = 0; i2 < allWrappers.length; i2++) {
                                            var w = allWrappers[i2];
                                            var fieldKey = w.getAttribute('data-template-field');
                                            if (fieldKey && fieldKey !== 'brand_color' && keys.indexOf(fieldKey) === -1) {
                                                w.classList.add('hidden');
                                            }
                                        }
                                    }

                                    Object.keys(values).forEach(function (k) {
                                        setValue(k, values[k]);
                                    });

                                    var bc = modal.querySelector('[name="brand_color"]');
                                    if (bc && values.brand_color) {
                                        bc.value = String(values.brand_color);
                                    }
                                    updateBrandText();
                                    refreshPreview();
                                }).catch(function (e) {
                                    showError(e && e.message ? e.message : 'Failed to load template');
                                });
                            };

                            var closeModal = function () {
                                modal.classList.add('hidden');
                                modal.setAttribute('aria-hidden', 'true');
                                document.body.classList.remove('overflow-hidden');
                                currentTemplateId = null;
                                previewBase = null;
                                if (iframe) iframe.setAttribute('src', 'about:blank');
                            };

                            for (var i = 0; i < closeEls.length; i++) {
                                closeEls[i].addEventListener('click', function () {
                                    closeModal();
                                });
                            }

                            document.addEventListener('keydown', function (e) {
                                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                                    closeModal();
                                }
                            });

                            if (formRoot) {
                                formRoot.addEventListener('input', function (e) {
                                    if (e && e.target && e.target.getAttribute && e.target.getAttribute('name') === 'brand_color') {
                                        updateBrandText();
                                    }
                                    refreshPreview();
                                });

                                formRoot.addEventListener('change', function () {
                                    refreshPreview();
                                });
                            }

                            if (saveBtn) {
                                saveBtn.addEventListener('click', function () {
                                    if (!currentTemplateId) return;
                                    hideMessages();

                                    var url = @json(route('admin.settings.templates.update', ['template' => '__TPL__'])).replace('__TPL__', encodeURIComponent(currentTemplateId));
                                    var data = {};
                                    var inputs = modal.querySelectorAll('[data-template-customizer-form] [name]');
                                    for (var i = 0; i < inputs.length; i++) {
                                        var el = inputs[i];
                                        var name = el.getAttribute('name');
                                        if (!name || name === 'brand_color_text') continue;
                                        data[name] = el.value;
                                    }

                                    fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken
                                        },
                                        body: JSON.stringify(data)
                                    }).then(function (r) {
                                        if (!r.ok) {
                                            return r.json().then(function (payload) {
                                                var msg = payload && payload.message ? payload.message : 'Failed to save';
                                                throw new Error(msg);
                                            }).catch(function () {
                                                throw new Error('Failed to save');
                                            });
                                        }
                                        return r.json();
                                    }).then(function () {
                                        showSuccess('Saved.');
                                    }).catch(function (e) {
                                        showError(e && e.message ? e.message : 'Failed to save');
                                    });
                                });
                            }

                            if (activateBtn) {
                                activateBtn.addEventListener('click', function () {
                                    if (!currentTemplateId) return;
                                    hideMessages();

                                    var url = @json(route('admin.settings.templates.activate', ['template' => '__TPL__'])).replace('__TPL__', encodeURIComponent(currentTemplateId));
                                    fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken
                                        }
                                    }).then(function (r) {
                                        if (!r.ok) throw new Error('Failed to activate');
                                        showSuccess('Activated.');
                                        window.location.reload();
                                    }).catch(function (e) {
                                        showError(e && e.message ? e.message : 'Failed to activate');
                                    });
                                });
                            }

                            var customizeButtons = document.querySelectorAll('[data-template-customize="1"]');
                            for (var j = 0; j < customizeButtons.length; j++) {
                                customizeButtons[j].addEventListener('click', function (e) {
                                    var tpl = e && e.currentTarget ? e.currentTarget.getAttribute('data-template-id') : null;
                                    if (!tpl) return;
                                    openModal(tpl);
                                });
                            }

                            var importButtons = document.querySelectorAll('[data-template-import="1"]');
                            for (var k = 0; k < importButtons.length; k++) {
                                importButtons[k].addEventListener('click', function (e) {
                                    var tplId = e && e.currentTarget ? e.currentTarget.getAttribute('data-template-id') : null;
                                    if (!tplId) return;

                                    var url = @json(route('admin.settings.templates.import', ['template' => '__TPL__'])).replace('__TPL__', encodeURIComponent(tplId));

                                    fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken,
                                        }
                                    }).then(function (r) {
                                        return r.json().then(function (payload) {
                                            if (!r.ok) {
                                                var msg = payload && payload.message ? payload.message : 'Failed to import template';
                                                throw new Error(msg);
                                            }
                                            return payload;
                                        });
                                    }).then(function (payload) {
                                        if (!payload || !payload.edit_url) {
                                            throw new Error('Failed to import template');
                                        }

                                        if (window.mailpursePageEditor && typeof window.mailpursePageEditor.open === 'function') {
                                            window.mailpursePageEditor.open(payload.edit_url, {
                                                title: payload.title || ('Edit Page: ' + (payload.page_title || 'Imported Template')),
                                                publicUrl: payload.public_url || ''
                                            });
                                        }
                                    }).catch(function (err) {
                                        showError(err && err.message ? err.message : 'Failed to import template');
                                    });
                                });
                            }
                        })();
                    </script>

                    <script>
                        (function () {
                            var modal = document.getElementById('pro-license-modal');
                            if (!modal) return;

                            var csrf = document.querySelector('meta[name="csrf-token"]');
                            var csrfToken = csrf ? csrf.getAttribute('content') : '';

                            var closeEls = modal.querySelectorAll('[data-pro-license-close]');
                            var activateBtn = modal.querySelector('[data-pro-license-activate]');
                            var input = modal.querySelector('#pro_license_key');
                            var okBox = modal.querySelector('[data-pro-license-success]');
                            var errBox = modal.querySelector('[data-pro-license-error]');

                            var hideMessages = function () {
                                if (okBox) okBox.classList.add('hidden');
                                if (errBox) errBox.classList.add('hidden');
                            };

                            var showError = function (msg) {
                                if (!errBox) return;
                                errBox.textContent = msg;
                                errBox.classList.remove('hidden');
                            };

                            var showSuccess = function (msg) {
                                if (!okBox) return;
                                okBox.textContent = msg;
                                okBox.classList.remove('hidden');
                            };

                            var openModal = function () {
                                hideMessages();
                                modal.classList.remove('hidden');
                                modal.setAttribute('aria-hidden', 'false');
                                document.body.classList.add('overflow-hidden');
                                if (input) input.focus();
                            };

                            var closeModal = function () {
                                modal.classList.add('hidden');
                                modal.setAttribute('aria-hidden', 'true');
                                document.body.classList.remove('overflow-hidden');
                            };

                            for (var i = 0; i < closeEls.length; i++) {
                                closeEls[i].addEventListener('click', function () {
                                    closeModal();
                                });
                            }

                            document.addEventListener('keydown', function (e) {
                                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                                    closeModal();
                                }
                            });

                            var openButtons = document.querySelectorAll('[data-pro-license-open="1"]');
                            for (var j = 0; j < openButtons.length; j++) {
                                openButtons[j].addEventListener('click', function () {
                                    openModal();
                                });
                            }

                            if (activateBtn) {
                                activateBtn.addEventListener('click', function () {
                                    hideMessages();
                                    var key = input ? String(input.value || '').trim() : '';
                                    if (!key) {
                                        showError('License key is required.');
                                        return;
                                    }

                                    var url = @json(route('admin.settings.templates.external-license.activate'));
                                    fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken
                                        },
                                        body: JSON.stringify({ license_key: key })
                                    }).then(function (r) {
                                        if (!r.ok) {
                                            return r.json().then(function (payload) {
                                                var msg = payload && payload.message ? payload.message : 'Failed to activate license.';
                                                throw new Error(msg);
                                            }).catch(function () {
                                                throw new Error('Failed to activate license.');
                                            });
                                        }
                                        return r.json();
                                    }).then(function () {
                                        showSuccess('License activated.');
                                        window.location.reload();
                                    }).catch(function (e) {
                                        showError(e && e.message ? e.message : 'Failed to activate license.');
                                    });
                                });
                            }
                        })();
                    </script>
                </div>

            @elseif($category === 'appearance')
                @php
                    $appearanceSettings = $settings->get('appearance', collect())->keyBy('key');
                    $googleFonts = config('mailpurse.fonts.supported_google_families', []);
                    $googleFonts = is_array($googleFonts) ? array_unique(array_values(array_filter($googleFonts, fn($f) => is_string($f) && trim($f) !== ''))) : [];

                    $getSettingVal = function(string $key, string $default = '') use ($appearanceSettings) {
                        $s = $appearanceSettings->get($key);
                        $v = $s ? $s->value : $default;
                        return is_string($v) ? trim($v) : $default;
                    };

                    // Font family helper: renders searchable autocomplete dropdown + font weights via Blade partial
                    $renderFontSelect = function(string $name, string $value, array $fonts, string $weightsName = '', string $weightsValue = '') {
                        return view('admin.settings._font_picker', [
                            'name' => $name,
                            'value' => $value,
                            'fonts' => $fonts,
                            'weightsName' => $weightsName,
                            'weightsValue' => $weightsValue,
                        ])->render();
                    };
                @endphp
                <div class="space-y-8">
                    {{-- Dashboard --}}
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Dashboard</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Customize dashboard fonts and border radius. These settings are independent of dark mode.</p>
                        <div class="mt-4 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <div class="px-5 py-4 md:px-6">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Number Font</label>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Font family for dashboard numbers and statistics.</p>
                                    </div>
                                    <div class="flex w-full md:justify-end">
                                        <div class="w-full md:max-w-[420px] md:ml-auto">
                                            @php $numFontVal = $getSettingVal('dashboard_number_font', 'Inter'); $numFontWt = $getSettingVal('dashboard_number_font_weights', '400'); @endphp
                                            {!! $renderFontSelect('dashboard_number_font', $numFontVal, $googleFonts, 'dashboard_number_font_weights', $numFontWt) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-5 py-4 md:px-6">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Text Font</label>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Font family for dashboard body text.</p>
                                    </div>
                                    <div class="flex w-full md:justify-end">
                                        <div class="w-full md:max-w-[420px] md:ml-auto">
                                            @php $textFontVal = $getSettingVal('dashboard_text_font', 'Inter'); $textFontWt = $getSettingVal('dashboard_text_font_weights', '400'); @endphp
                                            {!! $renderFontSelect('dashboard_text_font', $textFontVal, $googleFonts, 'dashboard_text_font_weights', $textFontWt) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-5 py-4 md:px-6">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Border Radius</label>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Border radius (px) applied to buttons, cards, tables, and inputs across the dashboard.</p>
                                    </div>
                                    <div class="flex w-full md:justify-end">
                                        <div class="w-full md:max-w-[420px] md:ml-auto">
                                            @php $radiusVal = $getSettingVal('dashboard_border_radius', '8'); @endphp
                                            <div class="flex items-center gap-3">
                                                <input type="range" name="dashboard_border_radius" min="0" max="24" step="1" value="{{ $radiusVal }}" class="flex-1" oninput="this.nextElementSibling.textContent = this.value + 'px'">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 min-w-[40px]">{{ $radiusVal }}px</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Brand Color --}}
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Brand Color</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Primary brand color used across the public website.</p>
                        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @if($appearanceSettings->has('brand_color'))
                                @include('admin.settings._setting_field', ['setting' => $appearanceSettings['brand_color']])
                            @endif
                        </div>
                    </div>

                    {{-- Sidebar --}}
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Sidebar</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Customize the admin sidebar appearance. These settings are independent of dark mode.</p>
                        <div class="mt-4 space-y-5">
                            {{-- Sidebar Background --}}
                            <div
                                class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                                x-data="{ bgType: '{{ $getSettingVal('sidebar_background_type', 'color') }}' }"
                            >
                                {{-- Background Type selector (always visible) --}}
                                <div class="px-5 py-4 md:px-6">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Background Type</label>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Choose how the sidebar background is rendered.</p>
                                        </div>
                                        <div class="flex w-full md:justify-end">
                                            <div class="w-full md:max-w-[420px] md:ml-auto">
                                                <select name="sidebar_background_type" x-model="bgType" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                                    <option value="color">Color</option>
                                                    <option value="gradient">Gradient</option>
                                                    <option value="image">Image</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Color field (only when type === color) --}}
                                <div class="px-5 py-4 md:px-6" x-cloak x-show="bgType === 'color'">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Background Color</label>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Solid background color for the sidebar.</p>
                                        </div>
                                        <div class="flex w-full md:justify-end">
                                            <div class="w-full md:max-w-[420px] md:ml-auto">
                                                @php $sbBgColor = $getSettingVal('sidebar_background_color', '#161529'); @endphp
                                                <div class="flex items-center gap-3">
                                                    <input type="color" name="sidebar_background_color" value="{{ $sbBgColor }}" class="admin-color-picker" aria-label="Background color">
                                                    <input type="text" value="{{ $sbBgColor }}" readonly class="block w-32 rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Gradient fields (only when type === gradient) --}}
                                <div class="px-5 py-4 md:px-6" x-cloak x-show="bgType === 'gradient'">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gradient From</label>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Start color of the sidebar gradient.</p>
                                        </div>
                                        <div class="flex w-full md:justify-end">
                                            <div class="w-full md:max-w-[420px] md:ml-auto">
                                                @php $sbGradFrom = $getSettingVal('sidebar_gradient_from', '#1a1a3e'); @endphp
                                                <div class="flex items-center gap-3">
                                                    <input type="color" name="sidebar_gradient_from" value="{{ $sbGradFrom }}" class="admin-color-picker" aria-label="Gradient start color">
                                                    <input type="text" value="{{ $sbGradFrom }}" readonly class="block w-32 rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-5 py-4 md:px-6" x-cloak x-show="bgType === 'gradient'">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gradient To</label>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">End color of the sidebar gradient.</p>
                                        </div>
                                        <div class="flex w-full md:justify-end">
                                            <div class="w-full md:max-w-[420px] md:ml-auto">
                                                @php $sbGradTo = $getSettingVal('sidebar_gradient_to', '#0C0E1F'); @endphp
                                                <div class="flex items-center gap-3">
                                                    <input type="color" name="sidebar_gradient_to" value="{{ $sbGradTo }}" class="admin-color-picker" aria-label="Gradient end color">
                                                    <input type="text" value="{{ $sbGradTo }}" readonly class="block w-32 rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-5 py-4 md:px-6" x-cloak x-show="bgType === 'gradient'">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gradient Direction</label>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">CSS gradient direction (e.g. 180deg, 90deg, to right).</p>
                                        </div>
                                        <div class="flex w-full md:justify-end">
                                            <div class="w-full md:max-w-[420px] md:ml-auto">
                                                @php $sbGradDir = $getSettingVal('sidebar_gradient_direction', '180deg'); @endphp
                                                <input type="text" name="sidebar_gradient_direction" value="{{ $sbGradDir }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Image uploader (only when type === image) --}}
                                <div class="px-5 py-4 md:px-6" x-cloak x-show="bgType === 'image'">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Background Image</label>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Upload an image for the sidebar background.</p>
                                        </div>
                                        <div class="flex w-full md:justify-end">
                                            <div class="w-full md:max-w-[420px] md:ml-auto">
                                                @php
                                                    $sbBgImage = $getSettingVal('sidebar_background_image', '');
                                                    $brandingDisk = (string) config('filesystems.branding_disk', 'public');
                                                    $sbBgImageUrl = '';
                                                    if (is_string($sbBgImage) && trim($sbBgImage) !== '') {
                                                        try {
                                                            $sbBgImageUrl = $brandingDisk === 'public'
                                                                ? \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($sbBgImage, '/'))
                                                                : \Illuminate\Support\Facades\Storage::disk($brandingDisk)->url($sbBgImage);
                                                        } catch (\Throwable $e) {
                                                            $sbBgImageUrl = '';
                                                        }
                                                    }
                                                @endphp
                                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center" data-image-upload-wrapper data-image-setting="sidebar_background_image">
                                                    <div class="{{ $sbBgImageUrl ? '' : 'hidden' }}" data-image-preview-wrapper>
                                                        <div class="relative flex h-28 w-28 items-center justify-center overflow-visible rounded-2xl border border-gray-200 bg-transparent p-3 dark:border-gray-700">
                                                            @if($sbBgImageUrl)
                                                                <img src="{{ $sbBgImageUrl }}" alt="Sidebar background" class="h-full w-full object-cover" data-image-preview-image />
                                                            @endif
                                                            <input type="hidden" name="remove_sidebar_background_image" value="0" data-image-remove-input>
                                                            <button type="button" class="absolute right-2 top-2 z-20 inline-flex h-7 w-7 cursor-pointer items-center justify-center rounded-full bg-gray-900/80 text-white shadow-lg ring-2 ring-white transition hover:bg-gray-900 dark:ring-gray-800" aria-label="Remove sidebar background image" data-image-remove-button>
                                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <label for="setting_sidebar_background_image" class="{{ $sbBgImageUrl ? 'hidden' : '' }} flex min-h-[140px] w-full cursor-pointer flex-col items-center justify-center rounded-2xl border border-gray-300 bg-white px-6 py-5 text-center transition hover:border-primary-400 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-950/40 dark:hover:border-primary-400 dark:hover:bg-gray-900/60" data-image-upload-dropzone>
                                                        <input type="file" name="sidebar_background_image" id="setting_sidebar_background_image" accept="image/*" class="sr-only" data-image-upload-input>
                                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-200">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-upload-icon lucide-cloud-upload"><path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/></svg>
                                                        </div>
                                                        <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">Click to upload or drag and drop</p>
                                                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">PNG, JPG or GIF</p>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Sidebar Nav Colors --}}
                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @php
                                    $navColorFields = [
                                        'sidebar_nav_text_color' => ['Nav Text Color', 'Default text color for sidebar nav items. Icons inherit this color.'],
                                        'sidebar_nav_active_text_color' => ['Active Text Color', 'Text color for the active sidebar nav item.'],
                                        'sidebar_nav_hover_color' => ['Hover Text Color', 'Text color on hover for sidebar nav items.'],
                                        'sidebar_nav_hover_bg' => ['Hover Background', 'Background color on hover for sidebar nav items.'],
                                        'sidebar_nav_active_bg' => ['Active Background', 'Background color for the active sidebar nav item.'],
                                    ];
                                @endphp
                                @foreach($navColorFields as $navKey => $navInfo)
                                    <div class="px-5 py-4 md:px-6">
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                            <div class="space-y-1">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $navInfo[0] }}</label>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $navInfo[1] }}</p>
                                            </div>
                                            <div class="flex w-full md:justify-end">
                                                <div class="w-full md:max-w-[420px] md:ml-auto">
                                                    @php
                                                        $navVal = $getSettingVal($navKey, '');
                                                        $isColorPicker = str_starts_with($navKey, 'sidebar_nav_hover_bg') || str_starts_with($navKey, 'sidebar_nav_active_bg');
                                                        $isRgba = str_starts_with($navVal, 'rgba(') || str_starts_with($navVal, 'rgb(');
                                                    @endphp
                                                    @if($isColorPicker || (str_starts_with($navKey, 'sidebar_nav_') && !$isRgba))
                                                        <div class="flex items-center gap-3">
                                                             <input type="color" name="{{ $navKey }}" value="{{ $navVal }}" class="admin-color-picker" aria-label="{{ $navInfo[0] }}">
                                                        </div>
                                                    @else
                                                        <input type="text" name="{{ $navKey }}" value="{{ $navVal }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Sidebar Font + Section Title --}}
                            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <div class="px-5 py-4 md:px-6">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sidebar Font Family</label>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Font family for sidebar navigation text.</p>
                                        </div>
                                        <div class="flex w-full md:justify-end">
                                            <div class="w-full md:max-w-[420px] md:ml-auto">
                                                @php $sbFontVal = $getSettingVal('sidebar_font_family', 'Inter'); $sbFontWt = $getSettingVal('sidebar_font_weights', '400'); @endphp
                                                {!! $renderFontSelect('sidebar_font_family', $sbFontVal, $googleFonts, 'sidebar_font_weights', $sbFontWt) !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-5 py-4 md:px-6">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[50%_40%] md:items-start md:gap-x-[10%]">
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Section Title Color</label>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Color of sidebar section titles (e.g. General, Marketing, Delivery).</p>
                                        </div>
                                        <div class="flex w-full md:justify-end">
                                            <div class="w-full md:max-w-[420px] md:ml-auto">
                                                 @php $sbTitleColor = $getSettingVal('sidebar_section_title_color', '#a8a8a8'); @endphp
                                                <div class="flex items-center gap-3">
                                                    <input type="color" name="sidebar_section_title_color" value="{{ $sbTitleColor }}" class="admin-color-picker" aria-label="Section title color">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Public Meta Image (existing) --}}
                    @if($appearanceSettings->has('public_meta_image'))
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Meta / OG Image</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Default meta/OG image used for social sharing.</p>
                        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @include('admin.settings._setting_field', ['setting' => $appearanceSettings['public_meta_image']])
                        </div>
                    </div>
                    @endif
                </div>

            @elseif($category === 'navigation' && $settings->has($category) && $settings[$category]->count() > 0)
                @php
                    $navigationSettings = $settings[$category]
                        ->reject(fn ($s) => in_array((string) ($s->key ?? ''), ['nav_show_home_dropdown', 'nav_show_home_1', 'nav_show_home_2', 'nav_show_home_3', 'nav_show_home_4'], true));
                @endphp
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($navigationSettings as $setting)
                        @include('admin.settings._setting_field', ['setting' => $setting])
                    @endforeach
                </div>

            @elseif($category === 'seo')
                @php
                    $seoSettings = $settings->get('seo', collect())->keyBy('key');
                @endphp
                <div class="space-y-8">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Meta Tags</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure meta tags for search engines and social sharing.</p>
                        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach(['home_page_title', 'meta_description', 'meta_keywords'] as $seoKey)
                                @if($seoSettings->has($seoKey))
                                    @include('admin.settings._setting_field', ['setting' => $seoSettings[$seoKey]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Social & Branding</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Images used for social sharing (Open Graph) and browser identity.</p>
                        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach(['public_meta_image', 'site_favicon'] as $seoKey)
                                @if($seoSettings->has($seoKey))
                                    @include('admin.settings._setting_field', ['setting' => $seoSettings[$seoKey]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Analytics & Tracking</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add tracking codes for analytics and conversion measurement.</p>
                        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach(['google_analytics_tracking_id', 'meta_pixel_id'] as $seoKey)
                                @if($seoSettings->has($seoKey))
                                    @include('admin.settings._setting_field', ['setting' => $seoSettings[$seoKey]])
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

            @elseif($settings->has($category) && $settings[$category]->count() > 0)
                @php
                    $visibleSettings = $settings[$category]
                        ->reject(fn ($s) => in_array((string) ($s->key ?? ''), ['translation_active_locales', 'translation_locale_meta', 'update_license_key', 'home_redirect_url', 'public_header_sticky', 'new_registered_customer_group_id'], true));
                @endphp
                @if($visibleSettings->count() > 0)
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($visibleSettings as $setting)
                            @include('admin.settings._setting_field', ['setting' => $setting])
                        @endforeach
                    </div>
                @endif
            @else
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <p>No settings found for this category.</p>
                    <p class="mt-2 text-sm">Settings will appear here once they are created.</p>
                </div>
            @endif

            <!-- Form Actions -->
            @if(($settings->has($category) && $settings[$category]->count() > 0) || $category === 'seo' || $category === 'default_pages')
                <div class="flex items-center justify-end gap-4 pt-6 mt-6">
                    @admincan('admin.settings.edit')
                        <x-button type="submit" variant="primary">Save Settings</x-button>
                    @endadmincan
                </div>
            @endif
        </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


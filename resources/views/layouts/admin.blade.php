<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app('locale.direction')->dir() }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-bind:class="{ 'dark': darkMode }">
<head>
    <link rel="icon" type="image/svg+xml" href="{{ asset('branding/mailerfox-icon-blue.svg') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function () {
            try {
                if (localStorage.getItem('darkMode') === 'true') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    @php
        try {
            $appName = \App\Models\Setting::get('app_name', config('app.name', 'MailerFox'));
        } catch (\Throwable $e) {
            $appName = config('app.name', 'MailerFox');
        }

        if (!is_string($appName)) {
            $appName = config('app.name', 'MailerFox');
        }
    @endphp
    @php
        $pageTitle = trim($__env->yieldContent('title', 'Admin'));
    @endphp
    <title>{{ __($pageTitle) }} - {{ $appName }}</title>

    <!-- Fonts -->
    @php
        $fontFamily = \App\Models\Setting::get('admin_font_family', 'Inter');
        $fontWeights = \App\Models\Setting::get('admin_font_weights', '400,500,600,700');
        $fontWeightsUrl = preg_replace('/\s*,\s*/', ';', $fontWeights);
        // Convert font name to Google Fonts format (replace spaces with +)
        $fontFamilyUrl = str_replace(' ', '+', $fontFamily);
        // Build Google Fonts URL
        $googleFontsUrl = "https://fonts.googleapis.com/css2?family={$fontFamilyUrl}:wght@{$fontWeightsUrl}&display=swap";
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $googleFontsUrl }}" rel="stylesheet" />
    <style>
        body {
            font-family: '{{ $fontFamily }}', sans-serif;
        }
    </style>

    <script>
        window.__mailpurseSupportedGoogleFontFamilies = @json(config('mailpurse.fonts.supported_google_families', []));
    </script>

    @php
        try {
            $brandColor = \App\Models\Setting::get('brand_color', '#3b82f6');
        } catch (\Throwable $e) {
            $brandColor = '#3b82f6';
        }

        $brandColor = is_string($brandColor) ? trim($brandColor) : '#3b82f6';
        if ($brandColor === '' || !preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $brandColor)) {
            $brandColor = '#3b82f6';
        }

        $brandHex = ltrim($brandColor, '#');
        if (strlen($brandHex) === 3) {
            $brandHex = $brandHex[0] . $brandHex[0] . $brandHex[1] . $brandHex[1] . $brandHex[2] . $brandHex[2];
        }
        $brandR = hexdec(substr($brandHex, 0, 2));
        $brandG = hexdec(substr($brandHex, 2, 2));
        $brandB = hexdec(substr($brandHex, 4, 2));
    @endphp
    <style>
        :root {
            --brand-color: {{ $brandColor }};
            --brand-rgb: {{ $brandR }}, {{ $brandG }}, {{ $brandB }};
        }
    </style>

    @php
        // --- Sidebar appearance settings (dark-mode independent) ---
        try {
            $sbBgType = \App\Models\Setting::get('sidebar_background_type', 'color');
            $sbBgColor = \App\Models\Setting::get('sidebar_background_color', '#161529');
            $sbGradFrom = \App\Models\Setting::get('sidebar_gradient_from', '#1a1a3e');
            $sbGradTo = \App\Models\Setting::get('sidebar_gradient_to', '#0C0E1F');
            $sbGradDir = \App\Models\Setting::get('sidebar_gradient_direction', '180deg');
            $sbBgImage = \App\Models\Setting::get('sidebar_background_image', '');
            $sbNavText = \App\Models\Setting::get('sidebar_nav_text_color', '#9FACBE');
            $sbNavActiveText = \App\Models\Setting::get('sidebar_nav_active_text_color', '#1E5FEA');
            $sbNavHover = \App\Models\Setting::get('sidebar_nav_hover_color', '#1E5FEA');
            $sbNavHoverBg = \App\Models\Setting::get('sidebar_nav_hover_bg', 'rgba(30,95,234,0.08)');
            $sbNavActiveBg = \App\Models\Setting::get('sidebar_nav_active_bg', 'rgba(30,95,234,0.12)');
            $sbFontFamily = \App\Models\Setting::get('sidebar_font_family', 'Inter');
            $sbTitleColor = \App\Models\Setting::get('sidebar_section_title_color', '#a8a8a8');

            // Dashboard settings
            $dashNumFont = \App\Models\Setting::get('dashboard_number_font', 'Inter');
            $dashTextFont = \App\Models\Setting::get('dashboard_text_font', 'Inter');
            $dashRadius = \App\Models\Setting::get('dashboard_border_radius', '8');
        } catch (\Throwable $e) {
            $sbBgType = 'color';
            $sbBgColor = '#161529';
            $sbGradFrom = '#1a1a3e';
            $sbGradTo = '#0C0E1F';
            $sbGradDir = '180deg';
            $sbBgImage = '';
            $sbNavText = '#9FACBE';
            $sbNavActiveText = '#1E5FEA';
            $sbNavHover = '#1E5FEA';
            $sbNavHoverBg = 'rgba(30,95,234,0.08)';
            $sbNavActiveBg = 'rgba(30,95,234,0.12)';
            $sbFontFamily = 'Inter';
            $sbTitleColor = '#a8a8a8';
            $dashNumFont = 'Inter';
            $dashTextFont = 'Inter';
            $dashRadius = '8';
        }

        // Normalize all string values
        $sbBgType = is_string($sbBgType) ? trim($sbBgType) : 'color';
        $sbBgColor = is_string($sbBgColor) ? trim($sbBgColor) : '#161529';
        $sbGradFrom = is_string($sbGradFrom) ? trim($sbGradFrom) : '#1a1a3e';
        $sbGradTo = is_string($sbGradTo) ? trim($sbGradTo) : '#0C0E1F';
        $sbGradDir = is_string($sbGradDir) ? trim($sbGradDir) : '180deg';
        $sbBgImage = is_string($sbBgImage) ? trim($sbBgImage) : '';
        $sbNavText = is_string($sbNavText) ? trim($sbNavText) : '#9FACBE';
        $sbNavActiveText = is_string($sbNavActiveText) ? trim($sbNavActiveText) : '#1E5FEA';
        $sbNavHover = is_string($sbNavHover) ? trim($sbNavHover) : '#1E5FEA';
        $sbNavHoverBg = is_string($sbNavHoverBg) ? trim($sbNavHoverBg) : 'rgba(30,95,234,0.08)';
        $sbNavActiveBg = is_string($sbNavActiveBg) ? trim($sbNavActiveBg) : 'rgba(30,95,234,0.12)';
        $sbFontFamily = is_string($sbFontFamily) ? trim($sbFontFamily) : 'Inter';
        $sbTitleColor = is_string($sbTitleColor) ? trim($sbTitleColor) : '#a8a8a8';
        $dashNumFont = is_string($dashNumFont) ? trim($dashNumFont) : 'Inter';
        $dashTextFont = is_string($dashTextFont) ? trim($dashTextFont) : 'Inter';
        $dashRadius = is_string($dashRadius) ? trim($dashRadius) : '8';
        if (!is_numeric($dashRadius)) { $dashRadius = '8'; }
        $dashRadius = (int) $dashRadius;
        if ($dashRadius < 0) { $dashRadius = 0; }
        if ($dashRadius > 24) { $dashRadius = 24; }

        // Build sidebar background CSS value
        $sbBgCss = $sbBgColor;
        if ($sbBgType === 'gradient') {
            $sbBgCss = "linear-gradient({$sbGradDir}, {$sbGradFrom}, {$sbGradTo})";
        } elseif ($sbBgType === 'image' && $sbBgImage !== '') {
            try {
                $brandingDisk = (string) config('filesystems.branding_disk', 'public');
                $sbBgImageUrl = $brandingDisk === 'public'
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($sbBgImage, '/'))
                    : \Illuminate\Support\Facades\Storage::disk($brandingDisk)->url($sbBgImage);
            } catch (\Throwable $e) {
                $sbBgImageUrl = '';
            }
            if ($sbBgImageUrl !== '') {
                $sbBgCss = "url('{$sbBgImageUrl}') center/cover no-repeat";
            }
        }

        // Build Google Fonts URL for sidebar + dashboard fonts (with per-font weights)
        $sbFontWeights = \App\Models\Setting::get('sidebar_font_weights', '400');
        $dashNumFontWeights = \App\Models\Setting::get('dashboard_number_font_weights', '400');
        $dashTextFontWeights = \App\Models\Setting::get('dashboard_text_font_weights', '400');
        $sbFontWeights = is_string($sbFontWeights) ? trim($sbFontWeights) : '400';
        $dashNumFontWeights = is_string($dashNumFontWeights) ? trim($dashNumFontWeights) : '400';
        $dashTextFontWeights = is_string($dashTextFontWeights) ? trim($dashTextFontWeights) : '400';
        if ($sbFontWeights === '') { $sbFontWeights = '400'; }
        if ($dashNumFontWeights === '') { $dashNumFontWeights = '400'; }
        if ($dashTextFontWeights === '') { $dashTextFontWeights = '400'; }

        $extraFontConfigs = [
            ['name' => $sbFontFamily, 'weights' => $sbFontWeights],
            ['name' => $dashNumFont, 'weights' => $dashNumFontWeights],
            ['name' => $dashTextFont, 'weights' => $dashTextFontWeights],
        ];
        $extraFontsUrl = '';
        $extraParts = [];
        $processedFonts = [];
        foreach ($extraFontConfigs as $cfg) {
            $fn = trim($cfg['name']);
            if ($fn === '' || $fn === $fontFamily || isset($processedFonts[$fn])) {
                continue;
            }
            $processedFonts[$fn] = true;
            $efUrl = str_replace(' ', '+', $fn);
            $wghts = str_replace(';', ',', $cfg['weights']);
            $extraParts[] = "family={$efUrl}:wght@{$wghts}";
        }
        if (!empty($extraParts)) {
            $extraFontsUrl = "https://fonts.googleapis.com/css2?" . implode('&', $extraParts) . "&display=swap";
        }
    @endphp
    @if($extraFontsUrl !== '')
        <link href="{{ $extraFontsUrl }}" rel="stylesheet" />
    @endif
    <style>
        :root {
            --sidebar-bg: {{ $sbBgCss }};
            --sidebar-nav-text: {{ $sbNavText }};
            --sidebar-nav-active-text: {{ $sbNavActiveText }};
            --sidebar-nav-hover: {{ $sbNavHover }};
            --sidebar-nav-hover-bg: {{ $sbNavHoverBg }};
            --sidebar-nav-active-bg: {{ $sbNavActiveBg }};
            --sidebar-font-family: '{{ $sbFontFamily }}', sans-serif;
            --sidebar-font-weight: {{ $sbFontWeights }};
            --sidebar-title-color: {{ $sbTitleColor }};
            --dashboard-number-font: '{{ $dashNumFont }}', sans-serif;
            --dashboard-number-font-weight: {{ $dashNumFontWeights }};
            --dashboard-text-font: '{{ $dashTextFont }}', sans-serif;
            --dashboard-text-font-weight: {{ $dashTextFontWeights }};
            --dashboard-border-radius: {{ $dashRadius }}px;
        }
    </style>

    {{-- Apply sidebar + dashboard appearance settings (dark-mode independent) --}}
    <style>
        /* Sidebar background + font - applies in both light and dark mode */
        .admin-theme aside[data-sidebar="app"] {
            background: var(--sidebar-bg) !important;
            font-family: var(--sidebar-font-family) !important;
            font-weight: var(--sidebar-font-weight);
        }

        /* Dashboard text font - applied to entire body for all admin/customer pages */
        .admin-theme,
        .customer-theme {
            font-family: var(--dashboard-text-font) !important;
            font-weight: var(--dashboard-text-font-weight);
        }

        /* Dashboard number font */
        .admin-theme .dashboard-stat-number,
        .admin-theme .stat-number,
        .admin-theme [data-stat-number],
        .customer-theme .dashboard-stat-number,
        .customer-theme .stat-number,
        .customer-theme [data-stat-number] {
            font-family: var(--dashboard-number-font) !important;
            font-weight: var(--dashboard-number-font-weight);
        }

        /* Dashboard border radius - buttons (scoped to main content only) */
        .admin-theme main .btn:not(.mp-builder *),
        .admin-theme main button:not([class~="rounded-full"]):not(.mp-builder *),
        .admin-theme main a[class*="rounded"][class*="px"]:not([class~="rounded-full"]):not(.mp-builder *),
        .admin-theme main input[type="submit"]:not(.mp-builder *),
        .admin-theme main input[type="button"]:not(.mp-builder *) {
            border-radius: var(--dashboard-border-radius) !important;
        }
        
        .admin-theme main .elements-tab-rail{
            border-radius: var(--dashboard-border-radius) !important;
        }

        .admin-theme main .elements-tab-rail > button {
            border-radius: var(--dashboard-border-radius) !important;
        }

        /* Dashboard border radius - cards (scoped to main content, excluding image picker) */
        .admin-theme main .rounded-xl:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .admin-theme main .rounded-lg:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .admin-theme main .rounded-md:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .admin-theme main .rounded-sm:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .admin-theme main .rounded-2xl:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .admin-theme main .rounded-3xl:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .admin-theme main .rounded:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .admin-theme main .card:not([data-image-upload-wrapper] *):not(.mp-builder *) {
            border-radius: var(--dashboard-border-radius) !important;
        }

        /* Dashboard border radius - tables (scoped to main content only) */
        .admin-theme main table,
        .admin-theme main .table {
            border-radius: var(--dashboard-border-radius) !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            overflow: hidden !important;
        }
        .admin-theme main table thead th:first-child {
            border-top-left-radius: var(--dashboard-border-radius) !important;
        }
        .admin-theme main table thead th:last-child {
            border-top-right-radius: var(--dashboard-border-radius) !important;
        }
        .admin-theme main table tbody tr:last-child td:first-child {
            border-bottom-left-radius: var(--dashboard-border-radius) !important;
        }
        .admin-theme main table tbody tr:last-child td:last-child {
            border-bottom-right-radius: var(--dashboard-border-radius) !important;
        }
        .admin-theme main .overflow-x-auto:has(> table) {
            border-radius: var(--dashboard-border-radius) !important;
            overflow: hidden !important;
        }
        .admin-theme main [class~="bg-admin-sidebar"][class~="shadow-sm"][class~="border"][class~="border-admin-border"] {
            border-radius: var(--dashboard-border-radius) !important;
            overflow: hidden !important;
        }

        /* Dashboard border radius - inputs (scoped to main content only) */
        .admin-theme main input[type="text"],
        .admin-theme main input[type="email"],
        .admin-theme main input[type="password"],
        .admin-theme main input[type="number"],
        .admin-theme main input[type="search"],
        .admin-theme main input[type="url"],
        .admin-theme main input[type="tel"],
        .admin-theme main input[type="date"],
        .admin-theme main input[type="datetime-local"],
        .admin-theme main input[type="month"],
        .admin-theme main input[type="time"],
        .admin-theme main input[type="week"],
        .admin-theme main select,
        .admin-theme main textarea {
            border-radius: var(--dashboard-border-radius) !important;
        }

        /* Sidebar search input - transparent bg with 1px border via box-shadow */
        html:not(.dark) .admin-theme aside[data-sidebar="app"] input[type="search"]:not([type=checkbox]):not([type=radio]):not([type=file]) {
            background-color: transparent !important;
            border-color: transparent !important;
            box-shadow: 0 0 1px #ddd !important;
            color: var(--sidebar-nav-text) !important;
        }
        .dark .admin-theme aside[data-sidebar="app"] input[type="search"] {
            background-color: transparent !important;
            border-color: transparent !important;
            box-shadow: 0 0 1px rgba(255,255,255,0.1) !important;
        }
        .admin-theme aside[data-sidebar="app"] input[type="search"]:focus {
            box-shadow: 0 0 0 2px #1E5FEA !important;
        }

        /* ---- Sidebar nav: base text color ---- */
        .admin-theme aside[data-sidebar="app"] nav a,
        .admin-theme aside[data-sidebar="app"] nav button {
            color: var(--sidebar-nav-text) !important;
        }
        .admin-theme aside[data-sidebar="app"] nav a p,
        .admin-theme aside[data-sidebar="app"] nav button p {
            color: var(--sidebar-nav-text) !important;
        }

        /* ---- Sidebar nav: hover ---- */
        .admin-theme aside[data-sidebar="app"] nav a:hover,
        .admin-theme aside[data-sidebar="app"] nav button:hover {
            color: var(--sidebar-nav-hover) !important;
            background: var(--sidebar-nav-hover-bg) !important;
        }
        .admin-theme aside[data-sidebar="app"] nav a:hover p,
        .admin-theme aside[data-sidebar="app"] nav button:hover p {
            color: var(--sidebar-nav-hover) !important;
        }

        /* ---- Sidebar nav: active (text-[...] or bg-[...] class) ---- */
        .admin-theme aside[data-sidebar="app"] nav .text-\[var\(--sidebar-nav-active-text\)\],
        .admin-theme aside[data-sidebar="app"] nav .bg-\[var\(--sidebar-nav-active-bg\)\],
        .admin-theme aside[data-sidebar="app"] nav .text-\[var\(--sidebar-nav-active-text\)\] p,
        .admin-theme aside[data-sidebar="app"] nav .bg-\[var\(--sidebar-nav-active-bg\)\] p {
            color: var(--sidebar-nav-active-text) !important;
            background: var(--sidebar-nav-active-bg) !important;
        }

        /* ---- Sidebar nav: active overrides hover (keep active colors on hover) ---- */
        .admin-theme aside[data-sidebar="app"] nav .text-\[var\(--sidebar-nav-active-text\)\]:hover,
        .admin-theme aside[data-sidebar="app"] nav .bg-\[var\(--sidebar-nav-active-bg\)\]:hover,
        .admin-theme aside[data-sidebar="app"] nav .text-\[var\(--sidebar-nav-active-text\)\]:hover p,
        .admin-theme aside[data-sidebar="app"] nav .bg-\[var\(--sidebar-nav-active-bg\)\]:hover p {
            color: var(--sidebar-nav-active-text) !important;
            background: var(--sidebar-nav-active-bg) !important;
        }

        /* ---- Nested submenu (.ml-6) links: padding + radius ---- */
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a {
            padding-left: 0.625rem !important;
            padding-right: 0.625rem !important;
            border-radius: 0.375rem !important;
            background: transparent !important;
        }
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a:hover,
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a:hover p {
            background: transparent !important;
            color: var(--sidebar-nav-text) !important;
        }
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a.text-\[var\(--sidebar-nav-active-text\)\],
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a.bg-\[var\(--sidebar-nav-active-bg\)\],
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a.text-\[var\(--sidebar-nav-active-text\)\] p,
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a.bg-\[var\(--sidebar-nav-active-bg\)\] p {
            background: transparent !important;
            color: var(--sidebar-nav-text) !important;
            font-weight: 600 !important;
        }
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a[class*="text-"],
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a[class*="bg-"],
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a[class*="text-"]:hover,
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a[class*="bg-"]:hover {
            color: var(--sidebar-nav-text) !important;
            background: transparent !important;
        }
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a[class*="text-"]:is([class*="active"], [class*="sidebar-nav-active"]),
        .admin-theme aside[data-sidebar="app"] nav .ml-6 a.bg-\[var\(--sidebar-nav-active-bg\)\] {
            font-weight: 600 !important;
        }
    </style>

    @php
        try {
            $googleTagRaw = \App\Models\Setting::get('google_analytics_tracking_id');
        } catch (\Throwable $e) {
            $googleTagRaw = null;
        }

        $googleTagId = is_string($googleTagRaw) ? strtoupper(trim($googleTagRaw)) : null;
        if (!is_string($googleTagId) || !preg_match('/^(G-[A-Z0-9]{4,}|GTM-[A-Z0-9]{4,})$/', $googleTagId)) {
            $googleTagId = null;
        }

        $isGtmContainer = is_string($googleTagId) && str_starts_with($googleTagId, 'GTM-');
        $isGoogleTag = is_string($googleTagId) && str_starts_with($googleTagId, 'G-');
    @endphp
    @if($isGtmContainer)
        <script>
            (function (w, d, s, l, i) {
                w[l] = w[l] || [];
                w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
                const f = d.getElementsByTagName(s)[0];
                const j = d.createElement(s);
                const dl = l !== 'dataLayer' ? '&l=' + l : '';
                j.async = true;
                j.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(i) + dl;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', '{{ $googleTagId }}');
        </script>
    @elseif($isGoogleTag)
        <!-- Global site tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($googleTagId) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '{{ $googleTagId }}');
        </script>
    @endif

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/visitor-tracker.js'])
    @stack('styles')

    <style>
        aside[data-sidebar="app"][data-collapsed="true"] nav p {
            display: none;
        }

        aside[data-sidebar="app"][data-collapsed="true"] nav a,
        aside[data-sidebar="app"][data-collapsed="true"] nav button {
            justify-content: center;
            gap: 0;
        }

        aside[data-sidebar="app"][data-collapsed="true"] nav button > svg {
            display: none;
        }

        aside[data-sidebar="app"][data-collapsed="true"] nav .border-l {
            display: none;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-logo-img {
            width: 44px;
            margin-left: 0.75rem;
            margin-right: 0.75rem;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-user-info {
            display: none;
        }

        @media (min-width: 1024px) {
            aside[data-sidebar="app"][data-collapsed="true"] .sidebar-header-row {
                justify-content: center;
            }
            aside[data-sidebar="app"][data-collapsed="true"] .sidebar-logo-area {
                display: none;
            }
        }
    </style>

    @include('partials.meta-pixel')
</head>
 @php
     $disableMainScroll = request()->routeIs('admin.ai-tools.email-text-generator');
     $embedMode = request()->boolean('embed') || request()->routeIs('admin.pages.builder') || request()->routeIs('admin.layouts.builder') || request()->routeIs('admin.public-templates.create', 'admin.public-templates.edit') || request()->routeIs('admin.ai-projects.page-ai-builder', 'admin.ai-projects.page-ai-builder.*');
 @endphp
 <body class="admin-theme font-sans antialiased bg-admin-main text-admin-text-primary {{ $disableMainScroll && !$embedMode ? 'lg:h-screen lg:overflow-hidden' : '' }}" style="--app-font-family: '{{ $fontFamily }}', sans-serif;">
     @if($isGtmContainer)
         <noscript>
             <iframe src="https://www.googletagmanager.com/ns.html?id={{ rawurlencode($googleTagId) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe>
         </noscript>
     @endif
     @php
        $forceSidebarCollapsed = trim($__env->yieldContent('force-sidebar-collapsed')) !== '';
    @endphp
    <div
        class="flex {{ $embedMode ? 'min-h-screen' : 'min-h-screen lg:h-screen lg:overflow-hidden' }}"
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: @js($forceSidebarCollapsed) || (localStorage.getItem('adminSidebarCollapsed') === 'true'),
            toggleSidebarCollapsed() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('adminSidebarCollapsed', this.sidebarCollapsed ? 'true' : 'false');
            }
        }"
        @if(!$embedMode)
            :class="sidebarCollapsed ? 'lg:pl-[37px]' : 'lg:pl-64'"
        @endif
    >
        @if(!$embedMode)
            <div x-cloak x-show="sidebarOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden" @click="sidebarOpen = false"></div>
            <!-- Sidebar -->
            <x-sidebar />
        @endif

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden min-h-0" x-data="{
                profileMenuOpen: false,
                notificationsOpen: false,
                languageMenuOpen: false,
                profileLanguageOpen: false
            }">
        @if(!$embedMode)
        {{-- Top Bar --}}
        <header class="sticky top-0 z-30 shrink-0 h-10 bg-white dark:bg-admin-main border-b border-admin-border flex items-center justify-between px-4 sm:px-4 gap-4">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-md text-admin-text-secondary hover:text-admin-text-primary hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            @click.stop="sidebarOpen = true"
                            aria-label="{{ __('Open sidebar') }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        @php
                            $headerBreadcrumbTitle = trim($__env->yieldContent('page-title', $pageTitle ?? 'Dashboard'));
                            $headerHasCustomBreadcrumbs = trim($__env->yieldContent('breadcrumbs')) !== '';
                        @endphp
                        <nav aria-label="Breadcrumb" class="hidden lg:block flex-1 min-w-0">
                            @if($headerHasCustomBreadcrumbs)
                                @yield('breadcrumbs')
                            @elseif($headerBreadcrumbTitle !== '' && !request()->routeIs('admin.dashboard'))
                                <ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">
                                            {{ __('Dashboard') }}
                                        </a>
                                    </li>
                                    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
                                    <li class="font-medium text-admin-text-primary truncate">{!! __($headerBreadcrumbTitle) !!}</li>
                                </ol>
                            @else
                                <span class="text-sm font-medium text-admin-text-primary">{{ __('Dashboard') }}</span>
                            @endif
                        </nav>
                    </div>

                    {{-- Right actions --}}
                    <div class="flex items-center gap-3">
                        {{-- Notifications --}}
                        @admincan('admin.notifications.access')
                            <div class="relative">
                                <button
                                    type="button"
                                    @click.stop="notificationsOpen = !notificationsOpen; profileMenuOpen = false; languageMenuOpen = false"
                                    class="relative inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none dark:text-admin-text-secondary dark:hover:bg-white/5 dark:hover:text-admin-text-primary"
                                    data-notifications-root
                                    data-notifications-feed-url="{{ route('admin.notifications.feed') }}"
                                    data-notifications-mark-all-read-url="{{ route('admin.notifications.mark-all-read') }}"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    @php
                                        $unreadCount = auth('admin')->check()
                                            ? auth('admin')->user()->unreadNotifications()->count()
                                            : 0;
                                    @endphp
                                    @if($unreadCount > 0)
                                        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-red-500 text-white" data-notifications-badge>
                                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                        </span>
                                    @endif
                                </button>

                            </div>

                            {{-- Notifications drawer --}}
                            <template x-teleport="body">
                                <div x-cloak x-show="notificationsOpen" class="fixed inset-0 z-[100]" @keydown.escape.window="notificationsOpen = false" x-data="{ notificationsTab: 'all' }">
                                    <div
                                        x-show="notificationsOpen"
                                        x-transition.opacity
                                        class="absolute inset-0 bg-gray-900/30 dark:bg-black/50"
                                        @click="notificationsOpen = false"
                                    ></div>
                                    <aside
                                        x-show="notificationsOpen"
                                        x-transition:enter="transition transform ease-out duration-200"
                                        x-transition:enter-start="translate-x-full"
                                        x-transition:enter-end="translate-x-0"
                                        x-transition:leave="transition transform ease-in duration-150"
                                        x-transition:leave-start="translate-x-0"
                                        x-transition:leave-end="translate-x-full"
                                        class="absolute top-0 right-0 h-full w-full sm:w-[420px] bg-white dark:bg-admin-main shadow-2xl flex flex-col border-l border-gray-200 dark:border-admin-border"
                                        @click.stop
                                    >
                                        {{-- Header --}}
                                        <div class="flex items-center justify-between px-6 pt-6 pb-4">
                                            <h2 class="text-xl font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Notifications') }}</h2>
                                            <div class="flex items-center gap-1">
                                                <button type="button" @click="notificationsOpen = false" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-admin-text-primary rounded">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                        {{-- Tabs --}}
                                        <div class="px-6 pb-3 flex items-center justify-between border-b border-admin-border">
                                            <div class="inline-flex items-center bg-white/5 rounded-lg p-1 text-sm">
                                                <button type="button" @click="notificationsTab='all'; window.dispatchEvent(new CustomEvent('mailpurse-notifications-tab-change', { detail: { tab: 'all' } }))" :class="notificationsTab==='all' ? 'bg-admin-sidebar text-admin-text-primary shadow-sm' : 'text-admin-text-secondary'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md font-medium transition">
                                                    <span>{{ __('All') }}</span>
                                                    <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded text-[11px] font-semibold bg-primary-900/40 text-primary-200" data-notifications-tab-count="all">0</span>
                                                </button>
                                                <button type="button" @click="notificationsTab='unread'; window.dispatchEvent(new CustomEvent('mailpurse-notifications-tab-change', { detail: { tab: 'unread' } }))" :class="notificationsTab==='unread' ? 'bg-admin-sidebar text-admin-text-primary shadow-sm' : 'text-admin-text-secondary'" class="px-3 py-1.5 rounded-md font-medium transition">{{ __('Unread') }}</button>
                                            </div>
                                            @admincan('admin.notifications.edit')
                                                <button type="button" class="p-1.5 text-admin-text-secondary hover:text-admin-text-primary rounded" data-notifications-mark-all-read title="{{ __('Mark all read') }}">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <span class="sr-only" data-notifications-unread-label>
                                                        @if($unreadCount > 0){{ $unreadCount }} {{ __('unread') }}@else{{ __('Up to date') }}@endif
                                                    </span>
                                                </button>
                                            @endadmincan
                                        </div>
                                        {{-- List --}}
                                        <div class="flex-1 overflow-y-auto" :data-notifications-tab="notificationsTab">
                                            <div class="divide-y divide-admin-border" data-notifications-list>
                                                @php
                                                    $notifications = auth('admin')->check()
                                                        ? auth('admin')->user()->notifications()->latest()->limit(10)->get()
                                                        : collect();
                                                @endphp
                                                @forelse($notifications as $notification)
                                                    @php
                                                        $notificationUrl = is_array($notification->data) && is_string($notification->data['url'] ?? null)
                                                            ? $notification->data['url']
                                                            : null;
                                                        $itemTag = is_string($notificationUrl) && trim($notificationUrl) !== '' ? 'a' : 'div';
                                                    @endphp
                                                    <{{ $itemTag }} @if($itemTag === 'a') href="{{ $notificationUrl }}" @endif class="px-6 py-4 flex items-start gap-3 {{ $itemTag === 'a' ? 'hover:bg-white/5' : '' }}" data-notifications-status="{{ $notification->read_at ? 'read' : 'unread' }}">
                                                        <div class="w-10 h-10 rounded-full bg-primary-900/40 text-primary-300 flex items-center justify-center flex-shrink-0" data-notification-icon>
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-semibold text-admin-text-primary">
                                                                {{ $notification->data['title'] ?? __('Notification') }}
                                                            </p>
                                                            @if(!empty($notification->data['message']))
                                                                <p class="mt-1 text-sm text-admin-text-secondary">
                                                                    {{ $notification->data['message'] }}
                                                                </p>
                                                            @endif
                                                            <p class="mt-1.5 text-xs text-admin-text-secondary/80">
                                                                {{ $notification->created_at->diffForHumans() }}
                                                            </p>
                                                        </div>
                                                        @if(!$notification->read_at)
                                                            <span class="mt-2 w-2 h-2 rounded-full bg-white flex-shrink-0"></span>
                                                        @endif
                                                    </{{ $itemTag }}>
                                                @empty
                                                    <div class="px-6 py-10 text-center text-sm text-admin-text-secondary">
                                                        {{ __('No notifications yet.') }}
                                                    </div>
                                                @endforelse
                                            </div>
                                            <div class="hidden px-6 py-10 text-center text-sm text-admin-text-secondary" data-notifications-empty-unread>
                                                NO unread notificaitons
                                            </div>
                                        </div>
                                    </aside>
                                </div>
                            </template>
                        @endadmincan

                        @php
                            $activeLocales = collect(app(\App\Translation\LocaleJsonService::class)->listLocales());
                            $currentLocale = (string) app()->getLocale();
                            $currentLocale = trim($currentLocale) !== '' ? $currentLocale : 'en';
                            $localeLabelMap = [
                                'ar' => 'Arabic',
                                'de' => 'German',
                                'en' => 'English',
                                'es' => 'Spanish',
                                'fr' => 'French',
                                'it' => 'Italian',
                                'pt' => 'Portuguese',
                                'ru' => 'Russian',
                                'tr' => 'Turkish',
                                'zh' => 'Chinese',
                            ];
                        @endphp
                        {{-- Profile dropdown --}}
                        <div class="relative" @click.outside="profileMenuOpen = false; profileLanguageOpen = false">
                            <button
                                type="button"
                                @click.stop="profileMenuOpen = !profileMenuOpen; notificationsOpen = false; languageMenuOpen = false"
                                class="flex items-center text-admin-text-secondary hover:text-admin-text-primary focus:outline-none rounded-full"
                            >
                                @php
                                    $user = auth('admin')->user();
                                @endphp
                                <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center overflow-hidden">
                                    @if($user && $user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-xs font-semibold text-admin-text-primary">
                                            {{ strtoupper(Str::substr($user->first_name, 0, 1) . Str::substr($user->last_name, 0, 1)) }}
                                        </span>
                                    @endif
                                </div>
                            </button>

                            {{-- Dropdown menu --}}
                            <div
                                x-cloak
                                x-show="profileMenuOpen"
                                x-transition
                                class="{{ app('locale.direction')->dir() === 'rtl' ? 'origin-top-left absolute left-0' : 'origin-top-right absolute right-0' }} mt-2 w-72 rounded-xl shadow-2xl bg-white dark:bg-admin-sidebar ring-1 ring-gray-200 dark:ring-admin-border z-20 overflow-hidden"
                            >
                                <div class="p-0">
                                    <div class="flex items-center gap-3 px-2 py-2">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                            @if($user && $user->avatar_url)
                                                <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-sm font-semibold text-admin-text-primary">
                                                    {{ strtoupper(Str::substr($user->first_name, 0, 1) . Str::substr($user->last_name, 0, 1)) }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary truncate">{{ $user->full_name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-admin-text-secondary truncate">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-2 space-y-1">
                                        @admincan('admin.settings.access')
                                            <button type="button" @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sun-dim-icon lucide-sun-dim"><circle cx="12" cy="12" r="4"/><path d="M12 4h.01"/><path d="M20 12h.01"/><path d="M12 20h.01"/><path d="M4 12h.01"/><path d="M17.657 6.343h.01"/><path d="M17.657 17.657h.01"/><path d="M6.343 17.657h.01"/><path d="M6.343 6.343h.01"/></svg>
                                                <span>{{ __('Appearance') }}</span>
                                                <span class="ml-auto inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs text-gray-500 dark:border-admin-border dark:bg-white/5 dark:text-admin-text-secondary">
                                                    <svg x-cloak x-show="!darkMode" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                                    </svg>
                                                    <svg x-cloak x-show="darkMode" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                                    </svg>
                                                    <span x-text="darkMode ? '{{ __('Dark') }}' : '{{ __('Light') }}'"></span>
                                                </span>
                                            </button>
                                        @endadmincan
                                        @admincan('admin.translations.access')
                                            <div>
                                                <button type="button" @click="profileLanguageOpen = !profileLanguageOpen" class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-languages-icon lucide-languages"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>
                                                    <span>{{ __('Languages') }}</span>
                                                    <span class="ml-auto text-xs font-medium text-gray-500 dark:text-admin-text-secondary">{{ strtoupper($currentLocale) }}</span>
                                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="profileLanguageOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                                <div x-cloak x-show="profileLanguageOpen" x-transition class="mt-1 overflow-hidden border border-gray-200 bg-white dark:border-admin-border dark:bg-admin-sidebar">
                                                    @foreach($activeLocales as $loc)
                                                        @php
                                                            $locCode = is_string($loc->code ?? null) ? trim((string) $loc->code) : '';
                                                            // Force display for known locales
                                                            if ($locCode === 'ar') {
                                                                $locName = 'Arabic';
                                                            } elseif ($locCode === 'en') {
                                                                $locName = 'English';
                                                            } else {
                                                                $locName = is_string($loc->name ?? null) ? trim((string) $loc->name) : strtoupper($locCode);
                                                            }
                                                        @endphp
                                                        <form method="POST" action="{{ route('admin.language.update') }}" data-turbo="false">
                                                            @csrf
                                                            <button type="submit" name="locale" value="{{ $locCode }}" class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-gray-900 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5 {{ $currentLocale === $locCode ? 'bg-gray-100 dark:bg-white/10' : '' }}">
                                                                <span class="flex-1">{{ $locName }}({{ strtolower($locCode) }})</span>
                                                                @if($currentLocale === $locCode)
                                                                    <svg class="h-5 w-5 text-gray-900 dark:text-admin-text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                @endif
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endadmincan
                                    </div>
                                    <div class="border-t border-gray-200 dark:border-admin-border my-2"></div>
                                    <div class="space-y-1">
                                        @admincan('admin.settings.access')
                                            <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cog-icon lucide-cog"><path d="M11 10.27 7 3.34"/><path d="m11 13.73-4 6.93"/><path d="M12 22v-2"/><path d="M12 2v2"/><path d="M14 12h8"/><path d="m17 20.66-1-1.73"/><path d="m17 3.34-1 1.73"/><path d="M2 12h2"/><path d="m20.66 17-1.73-1"/><path d="m20.66 7-1.73 1"/><path d="m3.34 17 1.73-1"/><path d="m3.34 7 1.73 1"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="12" r="8"/></svg>
                                                <span>{{ __('Settings') }}</span>
                                            </a>
                                        @endadmincan
                                        @admincan('admin.settings.access')
                                            <a href="{{ route('admin.site-pages.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open-text-icon lucide-book-open-text"><path d="M12 7v14"/><path d="M16 12h2"/><path d="M16 8h2"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/><path d="M6 12h2"/><path d="M6 8h2"/></svg>
                                                <span>{{ __('Pages') }}</span>
                                            </a>
                                        @endadmincan
                                        @admincan('admin.ai_tools.access')
                                            <a href="{{ route('admin.ai-tools.dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bot-icon lucide-bot"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                                                <span>{{ __('AI Dashboard') }}</span>
                                            </a>
                                            <a href="{{ route('admin.rag.knowledge.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                                                <span>{{ __('Knowledge Base') }}</span>
                                            </a>
                                            <a href="{{ route('admin.rag.chat.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                                <span>{{ __('AI Chat') }}</span>
                                            </a>
                                            @admincan('admin.pages.edit')
                                                <a href="{{ route('admin.ai-projects.ai-builder-templates.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                                                    <span>{{ __('AI Builder Templates') }}</span>
                                                </a>
                                            @endadmincan
                                        @endadmincan
                                    </div>
                                    <div class="border-t border-gray-200 dark:border-admin-border my-2"></div>
                                    <div class="space-y-1">
                                        @admincan('admin.support_tickets.access')
                                            <a href="{{ route('admin.support-tickets.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square-more-icon lucide-message-square-more"><path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"/><path d="M12 11h.01"/><path d="M16 11h.01"/><path d="M8 11h.01"/></svg>
                                                <span>{{ __('Support Center') }}</span>
                                            </a>
                                        @endadmincan
                                        @admincan('admin.delivery_servers.access')
                                            <a href="{{ route('admin.integrations.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layers-plus-icon lucide-layers-plus"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 .83.18 2 2 0 0 0 .83-.18l8.58-3.9a1 1 0 0 0 0-1.831z"/><path d="M16 17h6"/><path d="M19 14v6"/><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 .825.178"/><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l2.116-.962"/></svg>
                                                <span>{{ __('Integrations') }}</span>
                                            </a>
                                        @endadmincan
                                        @admincan('admin.delivery_servers.access')
                                            <a href="{{ route('admin.supabase-configs.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-database-icon lucide-database"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
                                                <span>{{ __('Supabase Presets') }}</span>
                                            </a>
                                        @endadmincan
                                        <a href="{{ route('admin.addons.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-admin-text-primary dark:hover:bg-white/5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-blocks-icon lucide-blocks"><path d="M10 22V7a1 1 0 0 0-1-1H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5a1 1 0 0 0-1-1H2"/><rect x="14" y="2" width="8" height="8" rx="1"/></svg>
                                            <span>{{ __('Addons') }}</span>
                                        </a>
                                    </div>
                                    <div class="border-t border-gray-200 dark:border-admin-border my-2"></div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-3 px-3 py-2 mb-2 text-left text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H9m4 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                            <span>{{ __('Sign out') }}</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
                @endif

                {{-- Page Content --}}
                <main class="flex-1 flex flex-col min-h-0 overflow-y-auto {{ $disableMainScroll && !$embedMode ? 'lg:overflow-y-hidden' : '' }} overflow-x-auto sm:overflow-x-visible {{ $embedMode ? 'p-0' : (trim($__env->yieldContent('content-padding-classes')) !== '' ? trim($__env->yieldContent('content-padding-classes')) : 'p-4 sm:p-6') }}">
                    @php
                        $toastPosition = \App\Models\Setting::get('toast_position', 'top_right');
                        $allowedToastPositions = ['top_left', 'top_center', 'top_right', 'bottom_left', 'bottom_center', 'bottom_right'];
                        $toastPosition = is_string($toastPosition) ? trim($toastPosition) : 'top_right';
                        if (!in_array($toastPosition, $allowedToastPositions, true)) {
                            $toastPosition = 'top_right';
                        }

                        $toastRootClass = match ($toastPosition) {
                            'top_left' => 'fixed top-4 left-4 items-start',
                            'top_center' => 'fixed top-4 left-1/2 -translate-x-1/2 items-center',
                            'top_right' => 'fixed top-4 right-4 items-end',
                            'bottom_left' => 'fixed bottom-4 left-4 items-start',
                            'bottom_center' => 'fixed bottom-4 left-1/2 -translate-x-1/2 items-center',
                            'bottom_right' => 'fixed bottom-4 right-4 items-end',
                            default => 'fixed top-4 right-4 items-end',
                        };
                        $toastRootClass .= ' z-[9999] flex flex-col gap-3 w-full max-w-sm';

                        $toasts = [];

                        $success = session()->pull('success');
                        if (is_string($success) && trim($success) !== '') {
                            $toasts[] = ['type' => 'success', 'title' => __('You\'re all set'), 'message' => $success];
                        }

                        $info = session()->pull('info');
                        if (is_string($info) && trim($info) !== '') {
                            $toasts[] = ['type' => 'info', 'title' => __('Just so you know'), 'message' => $info];
                        }

                        $warning = session()->pull('warning');
                        if (is_string($warning) && trim($warning) !== '') {
                            $toasts[] = ['type' => 'warning', 'title' => __('Take a quick look'), 'message' => $warning];
                        }

                        $error = session()->pull('error');
                        if (is_string($error) && trim($error) !== '') {
                            $toasts[] = ['type' => 'error', 'title' => __('Something went wrong'), 'message' => $error];
                        }

                        if ($errors->any()) {
                            $toasts[] = ['type' => 'error', 'title' => __('Something went wrong'), 'message' => implode("\n", $errors->all())];
                        }
                    @endphp

                    <div data-toast-root data-toast-position="{{ $toastPosition }}" class="{{ $toastRootClass }}"></div>
                    <script>
                        window.__mailpursePageToasts = @json($toasts);
                    </script>

                @yield('content')
            </main>
        </div>
    </div>
   
 
    <script>
        (function () {
            const MASK = '********';

            function maskApiKey(key) {
                if (typeof key !== 'string' || key.length <= 10) {
                    return '*'.repeat(key.length);
                }
                return key.slice(0, key.length - 10) + '*'.repeat(10);
            }

            async function fetchSecretValue(url) {
                if (!url) {
                    return '';
                }

                const res = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!res.ok) {
                    return '';
                }

                const json = await res.json();
                if (!json || json.success !== true) {
                    return '';
                }

                return typeof json.value === 'string' ? json.value : '';
            }

            function updateEyeIcon(svg, isVisible) {
                if (!svg) {
                    return;
                }

                if (isVisible) {
                    svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
                } else {
                    svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
                }
            }

            document.addEventListener('click', function (e) {
                const btn = e.target && e.target.closest ? e.target.closest('[data-toggle-secret]') : null;
                if (!btn) {
                    return;
                }

                const wrapper = btn.closest('[data-secret-wrapper]');
                if (!wrapper) {
                    return;
                }

                const input = wrapper.querySelector('input');
                if (!input) {
                    return;
                }

                const togglingToText = input.type === 'password';
                const nextType = togglingToText ? 'text' : 'password';
                input.type = nextType;

                const changed = input.dataset.changed === '1';
                const secretUrl = input.getAttribute('data-secret-url') || '';

                if (togglingToText && !changed && input.value === MASK && secretUrl) {
                    fetchSecretValue(secretUrl)
                        .then(function (value) {
                            if (value) {
                                input.value = maskApiKey(value);
                                input.dataset.revealed = '1';
                            }
                        })
                        .catch(function () {
                            // ignore
                        });
                }

                if (!togglingToText && input.dataset.revealed === '1' && !changed) {
                    input.value = MASK;
                    delete input.dataset.revealed;
                }

                const svg = btn.querySelector('svg');
                updateEyeIcon(svg, nextType === 'text');
            });

            document.addEventListener('DOMContentLoaded', function () {
                const inputs = document.querySelectorAll('input[data-secret-input]');
                inputs.forEach(function (input) {
                    input.dataset.initialValue = input.value;
                    input.addEventListener('input', function () {
                        input.dataset.changed = '1';
                        delete input.dataset.revealed;
                    });
                });

                const forms = document.querySelectorAll('form');
                forms.forEach(function (form) {
                    form.addEventListener('submit', function () {
                        const formInputs = form.querySelectorAll('input[data-secret-input]');
                        formInputs.forEach(function (input) {
                            const initial = input.dataset.initialValue || '';
                            const changed = input.dataset.changed === '1';
                            const revealed = input.dataset.revealed === '1';
                            if (!changed && ((input.value === MASK && initial === MASK) || revealed)) {
                                input.value = '';
                            }
                        });
                    });
                });
            });
        })();
    </script>

    <div
        data-confirm-modal-root
        x-data="{
            open: false,
            title: 'Are you sure?',
            message: '',
            confirmText: 'Confirm',
            cancelText: 'Cancel',
            variant: 'default',
            form: null,
            openFromEvent(e) {
                const d = (e && e.detail) ? e.detail : {};
                this.form = d.form || null;
                this.title = (typeof d.title === 'string' && d.title) ? d.title : 'Are you sure?';
                this.message = (typeof d.message === 'string') ? d.message : '';
                this.confirmText = (typeof d.confirmText === 'string' && d.confirmText) ? d.confirmText : 'Confirm';
                this.cancelText = (typeof d.cancelText === 'string' && d.cancelText) ? d.cancelText : 'Cancel';
                this.variant = (typeof d.variant === 'string' && d.variant) ? d.variant : 'default';
                this.open = true;
                this.$nextTick(() => {
                    try {
                        const btn = this.$refs.confirmBtn;
                        if (btn) btn.focus();
                    } catch (err) {
                    }
                });
            },
            close() {
                this.open = false;
                this.form = null;
            },
            confirm() {
                const form = this.form;
                if (!form) {
                    this.close();
                    return;
                }
                form.dataset.mpSkipConfirm = '1';
                this.open = false;
                this.form = null;
                this.$nextTick(() => {
                    try {
                        form.submit();
                    } finally {
                        window.setTimeout(() => {
                            try {
                                delete form.dataset.mpSkipConfirm;
                            } catch (e) {
                            }
                        }, 0);
                    }
                });
            }
        }"
        x-on:open-confirm-modal.window="openFromEvent($event)"
    >
        <div x-cloak x-show="open" class="fixed inset-0 z-[9999]">
            <div class="absolute inset-0 bg-black/40" @click="close()"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div
                    class="w-full max-w-lg rounded-2xl bg-white shadow-xl ring-1 ring-black/5 dark:bg-gray-800 dark:ring-white/10"
                    @click.stop
                    @keydown.escape.window="close()"
                >
                    <div class="flex items-start justify-between px-6 pt-6">
                        <div class="pr-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100" x-text="title"></h3>
                        </div>
                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-gray-100" @click="close()" aria-label="Close">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 pb-6 pt-2">
                        <p class="text-sm text-gray-600 dark:text-gray-300" x-text="message"></p>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" class="inline-flex justify-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600" @click="close()" x-text="cancelText"></button>
                            <button
                                x-ref="confirmBtn"
                                type="button"
                                class="inline-flex justify-center rounded-xl px-5 py-2.5 text-sm font-medium text-white shadow-sm"
                                :class="variant === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-primary-600 hover:bg-primary-700'"
                                @click="confirm()"
                                x-text="confirmText"
                            ></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

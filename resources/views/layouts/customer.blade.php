<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app('locale.direction')->dir() }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-bind:class="{ 'dark': darkMode }">
<head>
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
        $pageTitle = trim($__env->yieldContent('title', 'Dashboard'));
    @endphp
    @php
        try {
            $siteTitle = \App\Models\Setting::get('site_title', \App\Models\Setting::get('app_name', config('app.name', 'MailerFox')));
            $faviconPath = \App\Models\Setting::get('site_favicon');
            $metaDescription = \App\Models\Setting::get('meta_description');
            $metaKeywords = \App\Models\Setting::get('meta_keywords');
            $siteMeta = \App\Models\Setting::get('site_meta');
        } catch (\Throwable $e) {
            $siteTitle = config('app.name', 'MailerFox');
            $faviconPath = null;
            $metaDescription = null;
            $metaKeywords = null;
            $siteMeta = null;
        }

        if (!is_string($siteTitle) || trim($siteTitle) === '') {
            $siteTitle = config('app.name', 'MailerFox');
        }

        $brandingDisk = (string) config('filesystems.branding_disk', 'public');
        $faviconUrl = null;
        if (is_string($faviconPath) && trim($faviconPath) !== '') {
            $faviconUrl = $brandingDisk === 'public'
                ? \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($faviconPath, '/'))
                : \Illuminate\Support\Facades\Storage::disk($brandingDisk)->url($faviconPath);
        }
    @endphp

    @if(is_string($metaDescription) && trim($metaDescription) !== '')
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if(is_string($metaKeywords) && trim($metaKeywords) !== '')
        <meta name="keywords" content="{{ $metaKeywords }}">
    @endif
    
    @if(is_string($faviconUrl) && trim($faviconUrl) !== '')
        <link rel="icon" href="{{ str_starts_with((string) $faviconPath, 'branding/mailerfox-') ? asset($faviconPath) : $faviconUrl }}">
    @endif

    @if(is_string($siteMeta) && trim($siteMeta) !== '' && preg_match('/<\s*(meta|link|script|style|base|noscript)\b/i', $siteMeta))
        @php
            $siteMetaSafe = '';
            if (preg_match_all('/<\s*(meta|link|base)\b[^>]*\/?>/i', $siteMeta, $m1)) {
                $siteMetaSafe .= implode("\n", $m1[0]) . "\n";
            }
            if (preg_match_all('/<\s*(script|style|noscript)\b[^>]*>.*?<\s*\/\s*\\1\s*>/is', $siteMeta, $m2)) {
                $siteMetaSafe .= implode("\n", $m2[0]) . "\n";
            }
            $siteMetaSafe = trim($siteMetaSafe);
        @endphp
        @if($siteMetaSafe !== '')
            {!! $siteMetaSafe !!}
        @endif
    @endif

    <title>{{ __($pageTitle) }} - {{ $siteTitle }}</title>

    <!-- Fonts -->
    @php
        $fontFamily = \App\Models\Setting::get('admin_font_family', 'Inter');
        $fontWeights = \App\Models\Setting::get('admin_font_weights', '400,500,600,700');
        $fontWeightsUrl = preg_replace('/\s*,\s*/', ';', $fontWeights);
        $fontFamilyUrl = str_replace(' ', '+', $fontFamily);
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

    @php
        try {
            $dashTextFont = \App\Models\Setting::get('dashboard_text_font', 'Inter');
            $dashNumFont = \App\Models\Setting::get('dashboard_number_font', 'Inter');
            $dashTextFontWeights = \App\Models\Setting::get('dashboard_text_font_weights', '400');
            $dashNumFontWeights = \App\Models\Setting::get('dashboard_number_font_weights', '400');

            // Sidebar appearance settings (same keys used in admin layout)
            $sbBgType = \App\Models\Setting::get('sidebar_background_type', 'color');
            $sbBgColor = \App\Models\Setting::get('sidebar_background_color', '#ffffff');
            $sbGradFrom = \App\Models\Setting::get('sidebar_gradient_from', '#1a1a3e');
            $sbGradTo = \App\Models\Setting::get('sidebar_gradient_to', '#0C0E1F');
            $sbGradDir = \App\Models\Setting::get('sidebar_gradient_direction', '180deg');
            $sbBgImage = \App\Models\Setting::get('sidebar_background_image', '');
            $sbNavText = \App\Models\Setting::get('sidebar_nav_text_color', '#1b1b20');
            $sbNavActiveText = \App\Models\Setting::get('sidebar_nav_active_text_color', '#1E5FEA');
            $sbNavHover = \App\Models\Setting::get('sidebar_nav_hover_color', '#1E5FEA');
            $sbNavHoverBg = \App\Models\Setting::get('sidebar_nav_hover_bg', 'rgba(30,95,234,0.08)');
            $sbNavActiveBg = \App\Models\Setting::get('sidebar_nav_active_bg', 'rgba(30,95,234,0.12)');
            $sbFontFamily = \App\Models\Setting::get('sidebar_font_family', 'Inter');
            $sbTitleColor = \App\Models\Setting::get('sidebar_section_title_color', '#a8a8a8');
            $sbFontWeights = \App\Models\Setting::get('sidebar_font_weights', '400');
        } catch (\Throwable $e) {
            $dashTextFont = 'Inter';
            $dashNumFont = 'Inter';
            $dashTextFontWeights = '400';
            $dashNumFontWeights = '400';

            $sbBgType = 'color';
            $sbBgColor = '#ffffff';
            $sbGradFrom = '#1a1a3e';
            $sbGradTo = '#0C0E1F';
            $sbGradDir = '180deg';
            $sbBgImage = '';
            $sbNavText = '#1b1b20';
            $sbNavActiveText = '#1E5FEA';
            $sbNavHover = '#1E5FEA';
            $sbNavHoverBg = 'rgba(30,95,234,0.08)';
            $sbNavActiveBg = 'rgba(30,95,234,0.12)';
            $sbFontFamily = 'Inter';
            $sbTitleColor = '#a8a8a8';
            $sbFontWeights = '400';
        }
        $dashTextFont = is_string($dashTextFont) ? trim($dashTextFont) : 'Inter';
        $dashNumFont = is_string($dashNumFont) ? trim($dashNumFont) : 'Inter';
        $dashTextFontWeights = is_string($dashTextFontWeights) ? trim($dashTextFontWeights) : '400';
        $dashNumFontWeights = is_string($dashNumFontWeights) ? trim($dashNumFontWeights) : '400';
        if ($dashTextFontWeights === '') { $dashTextFontWeights = '400'; }
        if ($dashNumFontWeights === '') { $dashNumFontWeights = '400'; }

        $sbBgType = is_string($sbBgType) ? trim($sbBgType) : 'color';
        $sbBgColor = is_string($sbBgColor) ? trim($sbBgColor) : '#ffffff';
        $sbGradFrom = is_string($sbGradFrom) ? trim($sbGradFrom) : '#1a1a3e';
        $sbGradTo = is_string($sbGradTo) ? trim($sbGradTo) : '#0C0E1F';
        $sbGradDir = is_string($sbGradDir) ? trim($sbGradDir) : '180deg';
        $sbBgImage = is_string($sbBgImage) ? trim($sbBgImage) : '';
        $sbNavText = is_string($sbNavText) ? trim($sbNavText) : '#1b1b20';
        $sbNavActiveText = is_string($sbNavActiveText) ? trim($sbNavActiveText) : '#1E5FEA';
        $sbNavHover = is_string($sbNavHover) ? trim($sbNavHover) : '#1E5FEA';
        $sbNavHoverBg = is_string($sbNavHoverBg) ? trim($sbNavHoverBg) : 'rgba(30,95,234,0.08)';
        $sbNavActiveBg = is_string($sbNavActiveBg) ? trim($sbNavActiveBg) : 'rgba(30,95,234,0.12)';
        $sbFontFamily = is_string($sbFontFamily) ? trim($sbFontFamily) : 'Inter';
        $sbTitleColor = is_string($sbTitleColor) ? trim($sbTitleColor) : '#a8a8a8';
        $sbFontWeights = is_string($sbFontWeights) ? trim($sbFontWeights) : '400';
        if ($sbFontWeights === '') { $sbFontWeights = '400'; }

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

        $dashRadius = '8';
        try {
            $dashRadiusRaw = \App\Models\Setting::get('dashboard_border_radius', '8');
            $dashRadius = is_string($dashRadiusRaw) ? trim($dashRadiusRaw) : '8';
        } catch (\Throwable $e) {
            $dashRadius = '8';
        }
        if ($dashRadius === '' || !is_numeric($dashRadius)) { $dashRadius = '8'; }
        $dashRadius = (int) $dashRadius;
        if ($dashRadius < 0) { $dashRadius = 0; }
        if ($dashRadius > 24) { $dashRadius = 24; }

        // Build Google Fonts URL for dashboard + sidebar fonts
        $customerExtraParts = [];
        $customerProcessedFonts = [];
        foreach ([
            ['name' => $dashTextFont, 'weights' => $dashTextFontWeights],
            ['name' => $dashNumFont, 'weights' => $dashNumFontWeights],
            ['name' => $sbFontFamily, 'weights' => $sbFontWeights],
        ] as $cfg) {
            $fn = trim($cfg['name']);
            if ($fn === '' || $fn === $fontFamily || isset($customerProcessedFonts[$fn])) {
                continue;
            }
            $customerProcessedFonts[$fn] = true;
            $efUrl = str_replace(' ', '+', $fn);
            $wghts = str_replace(';', ',', $cfg['weights']);
            $customerExtraParts[] = "family={$efUrl}:wght@{$wghts}";
        }
        $customerExtraFontsUrl = '';
        if (!empty($customerExtraParts)) {
            $customerExtraFontsUrl = "https://fonts.googleapis.com/css2?" . implode('&', $customerExtraParts) . "&display=swap";
        }
    @endphp
    @if($customerExtraFontsUrl !== '')
        <link href="{{ $customerExtraFontsUrl }}" rel="stylesheet" />
    @endif
    <style>
        :root {
            --dashboard-text-font: '{{ $dashTextFont }}', sans-serif;
            --dashboard-text-font-weight: {{ $dashTextFontWeights }};
            --dashboard-number-font: '{{ $dashNumFont }}', sans-serif;
            --dashboard-number-font-weight: {{ $dashNumFontWeights }};
            --dashboard-border-radius: {{ $dashRadius }}px;
            --sidebar-bg: {{ $sbBgCss }};
            --sidebar-nav-text: {{ $sbNavText }};
            --sidebar-nav-active-text: {{ $sbNavActiveText }};
            --sidebar-nav-hover: {{ $sbNavHover }};
            --sidebar-nav-hover-bg: {{ $sbNavHoverBg }};
            --sidebar-nav-active-bg: {{ $sbNavActiveBg }};
            --sidebar-font-family: '{{ $sbFontFamily }}', sans-serif;
            --sidebar-font-weight: {{ $sbFontWeights }};
            --sidebar-title-color: {{ $sbTitleColor }};
        }
        .customer-theme {
            font-family: var(--dashboard-text-font) !important;
            font-weight: var(--dashboard-text-font-weight);
        }
        .customer-theme .dashboard-stat-number,
        .customer-theme .stat-number,
        .customer-theme [data-stat-number] {
            font-family: var(--dashboard-number-font) !important;
            font-weight: var(--dashboard-number-font-weight);
        }

        /* Dashboard border radius - standard controls and cards */
        .customer-theme main .btn:not(.mp-builder *),
        .customer-theme main button:not([class~="rounded-full"]):not([class~="rounded-none"]):not(.mp-builder *),
        .customer-theme main a[class*="rounded"][class*="px"]:not([class~="rounded-full"]):not([class~="rounded-none"]):not(.mp-builder *),
        .customer-theme main input[type="submit"]:not(.mp-builder *),
        .customer-theme main input[type="button"]:not(.mp-builder *) {
            border-radius: var(--dashboard-border-radius) !important;
        }
        .customer-theme main .rounded-xl:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .customer-theme main .rounded-lg:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .customer-theme main .rounded-md:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .customer-theme main .rounded-sm:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .customer-theme main .rounded-2xl:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .customer-theme main .rounded-3xl:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .customer-theme main .rounded:not([data-image-upload-wrapper] *):not(.mp-builder *),
        .customer-theme main .card:not([data-image-upload-wrapper] *):not(.mp-builder *) {
            border-radius: var(--dashboard-border-radius) !important;
        }
        .customer-theme main input[type="text"],
        .customer-theme main input[type="email"],
        .customer-theme main input[type="password"],
        .customer-theme main input[type="number"],
        .customer-theme main input[type="search"],
        .customer-theme main input[type="url"],
        .customer-theme main input[type="tel"],
        .customer-theme main input[type="date"],
        .customer-theme main input[type="datetime-local"],
        .customer-theme main input[type="month"],
        .customer-theme main input[type="time"],
        .customer-theme main input[type="week"],
        .customer-theme main select,
        .customer-theme main textarea {
            border-radius: var(--dashboard-border-radius) !important;
        }

        .customer-theme main table,
        .customer-theme main .table {
            border-radius: var(--dashboard-border-radius) !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            overflow: hidden !important;
        }
        .customer-theme main table thead th:first-child {
            border-top-left-radius: var(--dashboard-border-radius) !important;
        }
        .customer-theme main table thead th:last-child {
            border-top-right-radius: var(--dashboard-border-radius) !important;
        }
        .customer-theme main table tbody tr:last-child td:first-child {
            border-bottom-left-radius: var(--dashboard-border-radius) !important;
        }
        .customer-theme main table tbody tr:last-child td:last-child {
            border-bottom-right-radius: var(--dashboard-border-radius) !important;
        }
        .customer-theme main .overflow-x-auto:has(> table) {
            border-radius: var(--dashboard-border-radius) !important;
            overflow: hidden !important;
        }
        .customer-theme main [class~="bg-admin-sidebar"][class~="shadow-sm"][class~="border"][class~="border-admin-border"] {
            border-radius: var(--dashboard-border-radius) !important;
            overflow: hidden !important;
        }

        /* Customer sidebar appearance overrides */
        .customer-theme aside[data-sidebar="app"] {
            background: var(--sidebar-bg) !important;
            font-family: var(--sidebar-font-family) !important;
            font-weight: var(--sidebar-font-weight);
        }
        .customer-theme aside[data-sidebar="app"] nav a,
        .customer-theme aside[data-sidebar="app"] nav button {
            color: var(--sidebar-nav-text);
        }
        .customer-theme aside[data-sidebar="app"] nav a p,
        .customer-theme aside[data-sidebar="app"] nav button p {
            color: var(--sidebar-nav-text);
        }
        .customer-theme aside[data-sidebar="app"] nav button:hover p,
        .customer-theme aside[data-sidebar="app"] nav a:hover p{
            color: var(--sidebar-nav-hover) !important;
        }
        .customer-theme aside[data-sidebar="app"] nav a:hover,
        .customer-theme aside[data-sidebar="app"] nav button:hover {
            color: var(--sidebar-nav-hover) !important;
            background: var(--sidebar-nav-hover-bg) !important;
        }
        .customer-theme aside[data-sidebar="app"] nav a[aria-current="page"],
        .customer-theme aside[data-sidebar="app"] nav a.active-link,
        .customer-theme aside[data-sidebar="app"] nav a.text-\[\#1E5FEA\],
        .customer-theme aside[data-sidebar="app"] nav a.bg-\[rgba\(30\,95\,234\,0\.08\)\] {
            color: var(--sidebar-nav-active-text) !important;
            background: var(--sidebar-nav-active-bg) !important;
        }
        .customer-theme aside[data-sidebar="app"] nav a[aria-current="page"] p,
        .customer-theme aside[data-sidebar="app"] nav a.active-link p,
        .customer-theme aside[data-sidebar="app"] nav a.text-\[\#1E5FEA\] p,
        .customer-theme aside[data-sidebar="app"] nav a.bg-\[rgba\(30\,95\,234\,0\.08\)\] p {
            color: var(--sidebar-nav-active-text) !important;
        }

        /* Parent dropdowns retain active and hover appearance. */
        .customer-theme aside[data-sidebar="app"] nav .sidebar-dropdown > button:hover {
            color: var(--sidebar-nav-hover) !important;
            background: var(--sidebar-nav-hover-bg) !important;
        }
        .customer-theme aside[data-sidebar="app"] nav .sidebar-dropdown > button:hover p {
            color: var(--sidebar-nav-hover) !important;
        }
        .customer-theme aside[data-sidebar="app"] nav .sidebar-dropdown > button.text-\[\#1E5FEA\],
        .customer-theme aside[data-sidebar="app"] nav .sidebar-dropdown > button.bg-\[rgba\(30\,95\,234\,0\.08\)\] {
            color: var(--sidebar-nav-active-text) !important;
            background: var(--sidebar-nav-active-bg) !important;
        }
        .customer-theme aside[data-sidebar="app"] nav .sidebar-dropdown > button.text-\[\#1E5FEA\] p,
        .customer-theme aside[data-sidebar="app"] nav .sidebar-dropdown > button.bg-\[rgba\(30\,95\,234\,0\.08\)\] p {
            color: var(--sidebar-nav-active-text) !important;
        }

        /* Keep active item readable when hovered (don't switch to hover text color) */
        .customer-theme aside[data-sidebar="app"] nav a[aria-current="page"]:hover,
        .customer-theme aside[data-sidebar="app"] nav a.active-link:hover,
        .customer-theme aside[data-sidebar="app"] nav a.text-\[\#1E5FEA\]:hover,
        .customer-theme aside[data-sidebar="app"] nav a.bg-\[rgba\(30\,95\,234\,0\.08\)\]:hover {
            color: var(--sidebar-nav-active-text) !important;
            background: var(--sidebar-nav-active-bg) !important;
        }
        .customer-theme aside[data-sidebar="app"] nav a[aria-current="page"]:hover p,
        .customer-theme aside[data-sidebar="app"] nav a.active-link:hover p,
        .customer-theme aside[data-sidebar="app"] nav a.text-\[\#1E5FEA\]:hover p,
        .customer-theme aside[data-sidebar="app"] nav a.bg-\[rgba\(30\,95\,234\,0\.08\)\]:hover p {
            color: var(--sidebar-nav-active-text) !important;
        }
        .customer-theme aside[data-sidebar="app"] nav > div > p,
        .customer-theme aside[data-sidebar="app"] nav .text-\[\#a8a8a8\] {
            color: var(--sidebar-title-color) !important;
        }

        /* Nested submenu links - ensure hover background applies with padding + rounded */
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            border-radius: 0.375rem !important;
            color: var(--sidebar-nav-text) !important;
            background: transparent !important;
        }
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a:hover,
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a:hover p {
            color: var(--sidebar-nav-text) !important;
            background: transparent !important;
        }
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a.text-\[\#1E5FEA\],
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a.text-\[\#1E5FEA\] p {
            color: var(--sidebar-nav-text) !important;
            background: transparent !important;
            font-weight: 600 !important;
        }
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a[class*="text-"],
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a[class*="bg-"],
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a[class*="text-"]:hover,
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a[class*="bg-"]:hover {
            color: var(--sidebar-nav-text) !important;
            background: transparent !important;
        }
        .customer-theme aside[data-sidebar="app"] .sidebar-submenu a.text-\[\#1E5FEA\] {
            font-weight: 600 !important;
        }

        /* Workspace name + icons + bottom profile text use sidebar nav text color */
        .customer-theme aside[data-sidebar="app"] .sidebar-header-row .text-gray-900,
        .customer-theme aside[data-sidebar="app"] .sidebar-header-row .dark\:text-white,
        .customer-theme aside[data-sidebar="app"] .sidebar-header-row .text-gray-400,
        .customer-theme aside[data-sidebar="app"] .sidebar-header-row svg,
        .customer-theme aside[data-sidebar="app"] .sidebar-user-info .text-admin-text-primary,
        .customer-theme aside[data-sidebar="app"] .sidebar-user-info .text-admin-text-secondary,
        .customer-theme aside[data-sidebar="app"] .sidebar-user-info p,
        .customer-theme aside[data-sidebar="app"] .sidebar-user-info + div .text-admin-text-secondary,
        .customer-theme aside[data-sidebar="app"] .sidebar-user-info + div svg {
            color: var(--sidebar-nav-text) !important;
        }

        /* Sidebar search input - transparent bg with 1px border via box-shadow */
        html:not(.dark) .customer-theme aside[data-sidebar="app"] input[type="search"]:not([type=checkbox]):not([type=radio]):not([type=file]) {
            background-color: transparent !important;
            border-color: transparent !important;
            box-shadow: 0 0 1px #ddd !important;
            color: var(--sidebar-nav-text) !important;
        }
        .dark .customer-theme aside[data-sidebar="app"] input[type="search"] {
            background-color: transparent !important;
            border-color: transparent !important;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.1) !important;
        }
        .customer-theme aside[data-sidebar="app"] input[type="search"]:focus {
            box-shadow: 0 0 0 2px #1E5FEA !important;
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

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/visitor-tracker.js'])
    
    @stack('styles')
    @php
        // Prevent FOUC on heavy multi-step campaign pages by keeping body hidden until styles & scripts are ready
        $isCampaignCreateOrEdit = request()->routeIs('customer.campaigns.create') || request()->routeIs('customer.campaigns.edit');
    @endphp
    @if($isCampaignCreateOrEdit)
        {{-- Force a full reload when navigating to/from these pages so the
             Plate-based email content editor (loaded via @push('scripts'))
             always boots fresh on this URL. Without this, Turbo Drive keeps
             the original document and the editor module never re-initializes. --}}
        <meta name="turbo-visit-control" content="reload">
        <style>
            body.preload-campaign { visibility: hidden; }
        </style>
    @endif
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

        aside[data-sidebar="app"][data-collapsed="true"] nav {
            overflow: visible;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-dropdown {
            position: relative;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-dropdown > button {
            position: relative;
            z-index: 51;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu {
            position: absolute;
            left: 100%;
            top: 0;
            min-width: 200px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: 0;
            z-index: 52;
            margin: 0;
            display: none !important;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-dropdown:hover .sidebar-submenu {
            display: flex !important;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu::before {
            content: attr(data-label);
            display: block;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #fff;
            text-align: left;
            background: #1e2433;
            border-bottom: 1px solid #374151;
            border-radius: 8px 8px 0 0;
            position: relative;
            left: 0;
            top: 0;
            width: 100%;
            height: auto;
            pointer-events: none;
        }

        .dark aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu {
            background: #1f2937;
            border-color: #374151;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu a {
            padding: 8px 16px;
            white-space: nowrap;
            margin: 0;
            border: none;
            text-align: left;
            justify-content: flex-start !important;
        }

        [dir="rtl"] aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu {
            left: auto;
            right: 100%;
        }

        [dir="rtl"] aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu a {
            text-align: right;
            justify-content: flex-end !important;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .border-l,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .border-r,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .ml-4,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .mr-4,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .pl-4,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .pr-4,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .ml-6,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .mr-6,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .pl-5,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .pr-5 {
            margin: 0;
            padding: 0;
            border: none;
        }

        @media (min-width: 1024px) {
            aside[data-sidebar="app"][data-collapsed="true"] .sidebar-header-row {
                justify-content: center;
            }
            aside[data-sidebar="app"][data-collapsed="true"] .sidebar-logo-area {
                display: none;
            }
        }

        #sidebar-collapsed-tooltip {
            position: fixed;
            background: #1e2433;
            color: #fff;
            padding: 5px 12px;
            border-radius: 7px;
            white-space: nowrap;
            font-size: 13px;
            font-weight: 500;
            z-index: 99999;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.18);
            display: none;
            transform: translateY(-50%);
        }
        #sidebar-collapsed-tooltip-arrow {
            position: fixed;
            border: 6px solid transparent;
            border-right-color: #1e2433;
            z-index: 99999;
            pointer-events: none;
            display: none;
            transform: translateY(-50%);
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

    @include('partials.meta-pixel')
    @php
        // Prevent FOUC on heavy multi-step campaign pages by keeping body hidden until styles & scripts are ready
        $isCampaignCreateOrEdit = request()->routeIs('customer.campaigns.create') || request()->routeIs('customer.campaigns.edit');
    @endphp
    @if($isCampaignCreateOrEdit)
        {{-- Force a full reload when navigating to/from these pages so the
             Plate-based email content editor (loaded via @push('scripts'))
             always boots fresh on this URL. Without this, Turbo Drive keeps
             the original document and the editor module never re-initializes. --}}
        <meta name="turbo-visit-control" content="reload">
        <style>
            body.preload-campaign { visibility: hidden; }
        </style>
    @endif
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

        aside[data-sidebar="app"][data-collapsed="true"] nav {
            overflow: visible;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-dropdown {
            position: relative;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-dropdown > button {
            position: relative;
            z-index: 51;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu {
            position: absolute;
            left: 100%;
            top: 0;
            min-width: 200px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: 0;
            z-index: 52;
            margin: 0;
            display: none !important;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-dropdown:hover .sidebar-submenu {
            display: flex !important;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu::before {
            content: attr(data-label);
            display: block;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #fff;
            text-align: left;
            background: #1e2433;
            border-bottom: 1px solid #374151;
            border-radius: 8px 8px 0 0;
            position: relative;
            left: 0;
            top: 0;
            width: 100%;
            height: auto;
            pointer-events: none;
        }

        .dark aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu {
            background: #1f2937;
            border-color: #374151;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu a {
            padding: 8px 16px;
            white-space: nowrap;
            margin: 0;
            border: none;
            text-align: left;
            justify-content: flex-start !important;
        }

        [dir="rtl"] aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu {
            left: auto;
            right: 100%;
        }

        [dir="rtl"] aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu a {
            text-align: right;
            justify-content: flex-end !important;
        }

        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .border-l,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .border-r,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .ml-4,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .mr-4,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .pl-4,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .pr-4,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .ml-6,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .mr-6,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .pl-5,
        aside[data-sidebar="app"][data-collapsed="true"] .sidebar-submenu .pr-5 {
            margin: 0;
            padding: 0;
            border: none;
        }

        @media (min-width: 1024px) {
            aside[data-sidebar="app"][data-collapsed="true"] .sidebar-header-row {
                justify-content: center;
            }
            aside[data-sidebar="app"][data-collapsed="true"] .sidebar-logo-area {
                display: none;
            }
        }

        #sidebar-collapsed-tooltip {
            position: fixed;
            background: #1e2433;
            color: #fff;
            padding: 5px 12px;
            border-radius: 7px;
            white-space: nowrap;
            font-size: 13px;
            font-weight: 500;
            z-index: 99999;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.18);
            display: none;
            transform: translateY(-50%);
        }
        #sidebar-collapsed-tooltip-arrow {
            position: fixed;
            border: 6px solid transparent;
            border-right-color: #1e2433;
            z-index: 99999;
            pointer-events: none;
            display: none;
            transform: translateY(-50%);
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

    @include('partials.meta-pixel')
 </head>
 @php
     $disableMainScroll = request()->routeIs('customer.ai-tools.email-text-generator');
     $forceSidebarCollapsed = trim($__env->yieldContent('force-sidebar-collapsed')) !== '';
    $isCampaign = request()->routeIs('customer.campaigns.create') || request()->routeIs('customer.campaigns.edit');
     $forceSidebarCollapsed = trim($__env->yieldContent('force-sidebar-collapsed')) !== '';
    $isCampaign = request()->routeIs('customer.campaigns.create') || request()->routeIs('customer.campaigns.edit');
     $appLogo = null;
 @endphp
 <body class="customer-theme font-sans antialiased bg-admin-main text-admin-text-primary {{ $disableMainScroll ? 'lg:h-screen lg:overflow-hidden' : '' }} {{ $isCampaign ? 'preload-campaign' : '' }}" style="--app-font-family: '{{ $fontFamily }}', sans-serif;">
 @if($isCampaign)
     <script>
         // Reveal the page after all styles/scripts have loaded to avoid a brief unstyled flash
         (function() {
             var reveal = function() { document.body.classList.remove('preload-campaign'); };
             if (document.readyState === 'complete') {
                 reveal();
             } else {
                 window.addEventListener('load', reveal, { once: true });
             }
         })();
     </script>
 @endif 
 @if($isGtmContainer)
     <noscript>
         <iframe src="https://www.googletagmanager.com/ns.html?id={{ rawurlencode($googleTagId) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe>
     </noscript>
 @endif 
 @php
     $isEmbeddedFrame = request()->boolean('embedded');
 @endphp
 @php
    $collapsedSidebarPaddingClass = trim($__env->yieldContent('collapsed-sidebar-padding-class'));
@endphp
<div
        class="flex {{ $disableMainScroll ? 'lg:h-screen lg:overflow-hidden' : ($isEmbeddedFrame ? 'h-screen overflow-hidden' : 'min-h-screen') }}"
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: @js($forceSidebarCollapsed) || (localStorage.getItem('customerSidebarCollapsed') === 'true'),
             toggleSidebarCollapsed() {
                 this.sidebarCollapsed = !this.sidebarCollapsed;
                 localStorage.setItem('customerSidebarCollapsed', this.sidebarCollapsed ? 'true' : 'false');
             }
         }"
        @if(!$isEmbeddedFrame)
        :class="sidebarCollapsed ? '{{ $collapsedSidebarPaddingClass !== '' ? $collapsedSidebarPaddingClass : (app('locale.direction')->dir() === 'rtl' ? 'lg:pr-[37px]' : 'lg:pl-[37px]') }}' : '{{ app('locale.direction')->dir() === 'rtl' ? 'lg:pr-64' : 'lg:pl-64' }}'"
        @endif
    >
        <div x-cloak x-show="sidebarOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden" @click="sidebarOpen = false" @if($isEmbeddedFrame) style="display:none" @endif></div>
        <!-- Sidebar -->
        <aside
            data-sidebar="app"
            class="bg-white dark:bg-admin-sidebar fixed inset-y-0 left-0 z-40 h-screen w-64 border-r border-gray-100 dark:border-admin-border flex flex-col transform -translate-x-full lg:translate-x-0 transition-all duration-200"
            :data-collapsed="sidebarCollapsed ? 'true' : 'false'"
            :class="[(sidebarOpen ? 'translate-x-0' : ''), (sidebarCollapsed ? 'lg:w-auto' : 'lg:w-64')]"
            @if($isEmbeddedFrame || request()->routeIs('customer.ai-projects.builder') || request()->routeIs('customer.ai-projects.builder.show') || request()->routeIs('customer.templates.create') || request()->routeIs('customer.templates.unlayer.create') || request()->routeIs('customer.templates.edit') || request()->routeIs('customer.templates.unlayer.edit')) style="display:none" @endif
        >
             <div class="flex h-full w-full flex-col overflow-visible">
                 <div class="flex h-full w-full flex-col items-start relative overflow-visible">
                     <div class="flex flex-col gap-4 items-start relative w-full flex-1 min-h-0">
                         <!-- Logo / Workspace Dropdown -->
                         <div class="sidebar-header-row flex items-center justify-between w-full">
                           @php
                            $appLogoDark = null;
                            $brandingDisk = (string) config('filesystems.branding_disk', 'public');
                            try {
                                $appLogo = \App\Models\Setting::get('app_logo');
                                $appLogoDark = \App\Models\Setting::get('app_logo_dark');
                            } catch (\Throwable $e) {
                                $appLogo = null;
                                $appLogoDark = null;
                            }
                            $workspaceAddonActive = \App\Models\Addon::isActive('workspace') && auth('customer')->check();
                            $canCreateWorkspace = $workspaceAddonActive
                                && auth('customer')->user()->groupAllows('workspaces.permissions.can_create_workspace');
                           @endphp

                           @php
                               $wsColors = ['#F97316','#6366F1','#3B82F6','#10B981','#EC4899','#8B5CF6','#EF4444','#14B8A6'];
                               $hasWorkspaces = isset($customerWorkspaces) && $customerWorkspaces->count() > 0;
                               $showWsSwitcher = $workspaceAddonActive && ($hasWorkspaces || $canCreateWorkspace);
                           @endphp
                           @if($showWsSwitcher)
                               {{-- Workspace dropdown (team switcher style) --}}
                               <div class="relative w-full" x-show="!sidebarCollapsed" x-data="{ wsSidebarOpen: false }" @click.outside="wsSidebarOpen = false">
                                   <button type="button" @click.stop="wsSidebarOpen = !wsSidebarOpen"
                                       class="flex items-center gap-1.5 w-full rounded-md px-1 py-1 text-left transition-colors">
                                       @if(isset($activeWorkspace) && $activeWorkspace)
                                           <span class="flex h-6 w-6 items-center justify-center rounded-md text-white text-sm font-bold shrink-0" style="background: {{ $wsColors[($activeWorkspace->id ?? 0) % count($wsColors)] }}">
                                               {{ strtoupper(substr($activeWorkspace->name, 0, 1)) }}
                                           </span>
                                           <span class="flex-1 min-w-0">
                                               <span class="block text-sm font-semibold text-gray-900 dark:text-white truncate leading-tight">{{ $activeWorkspace->name }}</span>
                                           </span>
                                       @else
                                           <span class="flex h-6 w-6 items-center justify-center rounded">
                                               <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-icon lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                           </span>
                                           <span class="flex-1 min-w-0">
                                               <span class="block text-sm font-semibold text-gray-900 dark:text-white truncate leading-tight">{{ __('Personal') }}</span>
                                           </span>
                                       @endif
                                       <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/></svg>
                                   </button>

                                   <div x-show="wsSidebarOpen" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute top-full mt-1 rounded-md border border-gray-200 bg-white py-1 shadow-xl dark:border-gray-700 dark:bg-gray-800 z-50 w-[100%]">

                                       {{-- Personal account --}}
                                       <form method="POST" action="{{ wsRoute('customer.workspaces.switch-personal') }}">
                                           @csrf
                                           <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-white/5 transition-colors {{ !isset($activeWorkspace) || !$activeWorkspace ? 'bg-gray-50 dark:bg-white/5' : '' }}">
                                               <span class="flex h-4 w-4 items-center justify-center rounded shrink-0">
                                                   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-icon lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                               </span>
                                               <span class="text-sm font-medium text-gray-700 dark:text-gray-200 {{ !isset($activeWorkspace) || !$activeWorkspace ? 'font-semibold' : '' }}">{{ __('Personal Account') }}</span>
                                           </button>
                                       </form>

                                       @if(isset($customerWorkspaces) && $customerWorkspaces->count())
                                           <div class="border-t border-gray-100 dark:border-gray-700"></div>
                                           @foreach($customerWorkspaces as $wsIdx => $ws)
                                           <form method="POST" action="{{ wsRoute('customer.workspaces.switch', $ws) }}">
                                               @csrf
                                               <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-white/5 transition-colors {{ isset($activeWorkspace) && $activeWorkspace && (int)$activeWorkspace->id === (int)$ws->id ? 'bg-gray-50 dark:bg-white/5' : '' }}">
                                                   <span class="flex h-4 w-4 items-center justify-center rounded-xl text-white text-xs font-bold shrink-0" style="background: {{ $wsColors[($ws->id ?? $wsIdx) % count($wsColors)] }}">
                                                       {{ strtoupper(substr($ws->name, 0, 1)) }}
                                                   </span>
                                                   <span class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate {{ isset($activeWorkspace) && $activeWorkspace && (int)$activeWorkspace->id === (int)$ws->id ? 'font-semibold' : '' }}">{{ $ws->name }}</span>
                                               </button>
                                           </form>
                                           @endforeach
                                       @endif

                                       @if($canCreateWorkspace)
                                       <div class="border-t border-gray-100 dark:border-gray-700"></div>
                                       <a href="{{ wsRoute('customer.workspaces.create') }}" class="flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                           <span class="flex h-4 w-4 items-center justify-center rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 shrink-0">
                                               <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                           </span>
                                           <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Create a workspace') }}</span>
                                       </a>
                                       @endif
                                   </div>
                               </div>
                           @else
                               <a href="{{ wsRoute('customer.dashboard') }}" class="sidebar-logo-area relative shrink-0">
                                   @if(isset($appLogo) && is_string($appLogo) && trim($appLogo) !== '')
                                       <img
                                           src="{{ str_starts_with((string) $appLogo, 'branding/mailerfox-') ? asset($appLogo) : ($brandingDisk === 'public' ? \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($appLogo, '/')) : \Illuminate\Support\Facades\Storage::disk($brandingDisk)->url($appLogo)) }}"
                                           alt="{{ __('App Logo') }}"
                                           class="sidebar-logo-img block dark:hidden h-auto object-contain w-[120px] mx-1 my-1"
                                       />
                                       @if(isset($appLogoDark) && is_string($appLogoDark) && trim($appLogoDark) !== '')
                                           <img
                                               src="{{ str_starts_with((string) $appLogoDark, 'branding/mailerfox-') ? asset($appLogoDark) : ($brandingDisk === 'public' ? \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($appLogoDark, '/')) : \Illuminate\Support\Facades\Storage::disk($brandingDisk)->url($appLogoDark)) }}"
                                               alt="{{ __('App Logo') }}"
                                               class="sidebar-logo-img hidden dark:block h-auto object-contain w-[150px] mx-1 my-1"
                                           />
                                       @endif
                                   @else
                                       <span class="block text-xl font-bold text-admin-text-primary px-3 py-2">
                                           {{ \App\Models\Setting::get('app_name', config('app.name', 'MailerFox')) }}
                                       </span>
                                   @endif
                               </a>
                           @endif

                             
                             <!-- Desktop: sidebar collapse toggle (panel icon) -->
                             <button
                                 type="button"
                                 class="hidden lg:inline-flex items-center justify-center p-2 rounded-lg text-gray-500 dark:text-admin-text-secondary hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-white/5 transition-colors flex-shrink-0"
                                 @click="toggleSidebarCollapsed()"
                                 :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                             >
                                 <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                     <rect x="3" y="3" width="18" height="18" rx="2"/>
                                     <path d="M9 3v18"/>
                                 </svg>
                             </button>

                             <!-- Mobile: close button -->
                             <button
                                 type="button"
                                 class="lg:hidden p-2 rounded-md text-admin-text-secondary hover:text-admin-text-primary hover:bg-white/5"
                                 @click="sidebarOpen = false"
                                 aria-label="{{ __('Close sidebar') }}"
                             >
                                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                 </svg>
                             </button>
                         </div>
    
                        <!-- Sidebar Search -->
                        <div class="px-2 w-full shrink-0" x-show="!sidebarCollapsed" x-data @keydown.window.prevent.cmd.k="$refs.sidebarSearch.focus()" @keydown.window.prevent.ctrl.k="$refs.sidebarSearch.focus()">
                            <form action="{{ wsRoute('customer.search.index') }}" method="GET">
                                <div class="relative flex items-center">
                                    <span class="pointer-events-none absolute left-3 flex items-center text-gray-400 dark:text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                    </span>
                                    <input
                                        x-ref="sidebarSearch"
                                        type="search"
                                        name="q"
                                        placeholder="{{ __('Search') }}"
                                        class="block h-9 w-full rounded-lg border-0 bg-transparent pl-9 pr-14 py-0 text-sm text-gray-700 placeholder:text-gray-400 outline-none dark:text-white dark:placeholder:text-gray-500"
                                    >
                                    <span class="pointer-events-none absolute right-2.5 flex items-center gap-0.5">
                                        <kbd class="inline-flex h-5 items-center rounded border border-gray-300 bg-white px-1 font-sans text-[10px] font-medium text-gray-400 dark:border-gray-600 dark:bg-white/10 dark:text-gray-500">⌘</kbd>
                                        <kbd class="inline-flex h-5 items-center rounded border border-gray-300 bg-white px-1 font-sans text-[10px] font-medium text-gray-400 dark:border-gray-600 dark:bg-white/10 dark:text-gray-500">K</kbd>
                                    </span>
                                </div>
                            </form>
                        </div>

                        <!-- Navigation -->
                        <nav class="flex flex-col gap-6 items-start relative w-full flex-1 min-h-0 overflow-y-auto">
                            <div class="flex flex-col gap-1 items-start relative shrink-0 w-full">
                                <p class="font-normal leading-[18px] relative shrink-0 text-[#a8a8a8] dark:text-admin-text-secondary text-xs tracking-[-0.36px] ml-2">{{ __('General') }}</p>
                                <div class="flex flex-col items-start relative shrink-0 mx-auto m-0" :class="sidebarCollapsed ? 'w-auto' : 'w-full'">
                                    <a href="{{ wsRoute('customer.dashboard') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.dashboard') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gauge-icon lucide-gauge"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Dashboard') }}</p>
                                    </a>

                                    <a href="{{ wsRoute('customer.analytics.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.analytics.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-no-axes-combined-icon lucide-chart-no-axes-combined"><path d="M12 16v5"/><path d="M16 14v7"/><path d="M20 10v11"/><path d="m22 3-8.646 8.646a.5.5 0 0 1-.708 0L9.354 8.354a.5.5 0 0 0-.707 0L2 15"/><path d="M4 18v3"/><path d="M8 14v7"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Analytics') }}</p>
                                    </a>

                                    @if(\App\Models\Addon::isActive('unibox'))
                                        @php
                                            $uniboxMessagesCount = auth('customer')->check()
                                                ? \App\Models\UniboxMessage::where('customer_id', auth('customer')->id())->count()
                                                : 0;
                                        @endphp
                                        <a href="{{ wsRoute('customer.unibox.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.unibox.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                            <div class="relative shrink-0 w-[18px] h-[18px]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/></svg>
                                            </div>
                                            <p class="font-normal leading-[22px] relative min-w-0 flex-1 text-sm tracking-[-0.48px]">{{ __('Inbox') }}</p>
                                            <span x-show="!sidebarCollapsed" class="inline-flex min-w-5 items-center justify-center rounded-full bg-[#1E5FEA]/10 px-1.5 py-0.5 text-[11px] font-medium leading-none text-[#1E5FEA] dark:bg-[#1E5FEA]/20">
                                                {{ $uniboxMessagesCount > 99 ? '99+' : $uniboxMessagesCount }}
                                            </span>
                                        </a>
                                    @endif

                                    @php
                                        $listsView = (string) request()->input('view', 'all');
                                        $listsTab = (string) request()->input('tab', 'lists');
                                        $listsMenuOpen = request()->routeIs('customer.lists.*') || request()->routeIs('customer.forms.*') || request()->routeIs('customer.tags.*');
                                        $listsTabIsOverview = request()->routeIs('customer.lists.index') && ($listsView === 'all' || $listsTab === 'overview');
                                        $listsTabIsLists = request()->routeIs('customer.lists.*') && ($listsView === 'lists' || in_array($listsTab, ['', 'lists'], true));
                                        $listsTabIsSegments = request()->routeIs('customer.lists.index') && ($listsView === 'segments' || $listsTab === 'segments');
                                        $listsTabIsTags = request()->routeIs('customer.tags.*') || (request()->routeIs('customer.lists.index') && ($listsTab === 'tags'));
                                        $listsTabIsForms = request()->routeIs('customer.forms.*') || (request()->routeIs('customer.lists.index') && ($listsTab === 'forms'));
                                    @endphp

                                    <div x-data="{ open: {{ $listsMenuOpen ? 'true' : 'false' }} }" class="w-full sidebar-dropdown">
                                        <button type="button" @click="open = !open" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ $listsMenuOpen ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                            <div class="relative shrink-0 w-[18px] h-[18px]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-contact-round-icon lucide-contact-round"><path d="M16 2v2"/><path d="M17.915 22a6 6 0 0 0-12 0"/><path d="M8 2v2"/><circle cx="12" cy="12" r="4"/><rect x="3" y="4" width="18" height="18" rx="2"/></svg>
                                            </div>
                                            <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px] flex-1 text-left">{{ __('Contacts') }}</p>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="open ? 'rotate-180' : ''">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div x-cloak x-show="open" data-label="{{ __('Contacts') }}" class="sidebar-submenu mt-1 flex flex-col {{ app('locale.direction')->dir() === 'rtl' ? 'mr-4 border-r pr-5' : 'ml-4 border-l pl-5' }} border-gray-200 dark:border-white/10">
                                            <a href="{{ wsRoute('customer.lists.index', ['view' => 'all']) }}" class="flex items-center w-full py-2 text-sm {{ $listsTabIsOverview ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Overview') }}
                                            </a>
                                            <a href="{{ wsRoute('customer.lists.index', ['view' => 'lists']) }}" class="flex items-center w-full py-2 text-sm {{ $listsTabIsLists ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Lists') }}
                                            </a>
                                            <a href="{{ wsRoute('customer.lists.index', ['view' => 'segments']) }}" class="flex items-center w-full py-2 text-sm {{ $listsTabIsSegments ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Segments') }}
                                            </a>
                                            <a href="{{ wsRoute('customer.tags.index') }}" class="flex items-center w-full py-2 text-sm {{ $listsTabIsTags ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Tags') }}
                                            </a>
                                            <a href="{{ wsRoute('customer.forms.index') }}" class="flex items-center w-full py-2 text-sm {{ $listsTabIsForms ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Forms & Popups') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            @if(\App\Models\Addon::isActive('workspace'))
                            @php
                                $crmMenuOpen = request()->routeIs('customer.crm.*');
                            @endphp
                            <div class="flex flex-col gap-1 items-start relative shrink-0 w-full">
                                <p class="font-normal leading-[18px] relative shrink-0 text-[#a8a8a8] dark:text-admin-text-secondary text-xs tracking-[-0.36px] ml-2">{{ __('CRM') }}</p>
                                <div class="flex flex-col items-start relative shrink-0 w-auto mx-auto m-0" :class="sidebarCollapsed ? 'w-auto' : 'w-full'">
                                    <a href="{{ wsRoute('customer.crm.dashboard') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.crm.dashboard') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard-icon lucide-layout-dashboard"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Dashboard') }}</p>
                                    </a>

                                    <a href="{{ wsRoute('customer.crm.contacts.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.crm.contacts.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-icon lucide-users"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Contacts') }}</p>
                                    </a>

                                    <a href="{{ wsRoute('customer.crm.companies.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.crm.companies.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building-2-icon lucide-building-2"><path d="M10 12h4"/><path d="M10 8h4"/><path d="M14 21v-3a2 2 0 0 0-4 0v3"/><path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2"/><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Companies') }}</p>
                                    </a>

                                    <a href="{{ wsRoute('customer.crm.pipelines.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.crm.pipelines.*') || request()->routeIs('customer.crm.deals.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-kanban-icon lucide-kanban"><path d="M5 15a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2"/><path d="M12 3v18"/><path d="M5 10h14"/><path d="M5 15h14"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Pipelines') }}</p>
                                    </a>

                                    <a href="{{ wsRoute('customer.crm.tasks.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.crm.tasks.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle-2-icon lucide-check-circle-2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Tasks') }}</p>
                                    </a>
                                </div>
                            </div>
                            @endif

                            <div class="flex flex-col gap-1 items-start relative shrink-0 w-full">
                                <p class="font-normal leading-[18px] relative shrink-0 text-[#a8a8a8] dark:text-admin-text-secondary text-xs tracking-[-0.36px] ml-2">{{ __('Marketing') }}</p>
                                <div class="flex flex-col items-start relative shrink-0 w-auto mx-auto m-0" :class="sidebarCollapsed ? 'w-auto' : 'w-full'">
                                    <div x-data="{ open: {{ request()->routeIs('customer.templates.*') ? 'true' : 'false' }} }" class="w-full sidebar-dropdown">
                                        <button type="button" @click="open = !open" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.templates.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                            <div class="relative shrink-0 w-[18px] h-[18px]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-template-icon lucide-layout-template"><rect width="18" height="7" x="3" y="3" rx="1"/><rect width="9" height="7" x="3" y="14" rx="1"/><rect width="5" height="7" x="16" y="14" rx="1"/></svg>
                                            </div>
                                            <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px] flex-1 text-left">{{ __('Templates') }}</p>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="open ? 'rotate-180' : ''">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div x-cloak x-show="open" data-label="{{ __('Templates') }}" class="sidebar-submenu mt-1 flex flex-col gap-1 {{ app('locale.direction')->dir() === 'rtl' ? 'mr-6 border-r pr-4' : 'ml-6 border-l pl-4' }} border-gray-200 dark:border-white/10">
                                            <a href="{{ wsRoute('customer.templates.index') }}" class="flex items-center w-full py-2 text-sm {{ (request()->routeIs('customer.templates.*') && (request()->query('type') === null || request()->query('type') === 'email')) ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Email templates') }}
                                            </a>
                                             @customercan('templates.permissions.can_access_footer_templates')
                                             <a href="{{ wsRoute('customer.templates.index', ['type' => 'footer']) }}" class="flex items-center w-full py-2 text-sm {{ (request()->routeIs('customer.templates.*') && request()->query('type') === 'footer') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                 {{ __('Footer templates') }}
                                             </a>
                                             @endcustomercan
                                             @customercan('templates.permissions.can_access_signature_templates')
                                             <a href="{{ wsRoute('customer.templates.index', ['type' => 'signature']) }}" class="flex items-center w-full py-2 text-sm {{ (request()->routeIs('customer.templates.*') && request()->query('type') === 'signature') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                 {{ __('Signature') }}
                                             </a>
                                             @endcustomercan
                                        </div>
                                    </div>

                                    <a href="{{ wsRoute('customer.campaigns.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.campaigns.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone-icon lucide-megaphone"><path d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/><path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"/><path d="M8 6v8"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Campaigns') }}</p>
                                    </a>

                                    @if(\App\Models\Addon::isActive('cold-email-outreach'))
                                        @customercan('outreach.access')
                                        <a href="{{ wsRoute('customer.outreach.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.outreach.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                            <div class="relative shrink-0 w-[18px] h-[18px]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send-icon lucide-send"><path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/></svg>
                                            </div>
                                            <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Outreach') }}</p>
                                        </a>
                                        @endcustomercan
                                    @endif

                                    @if(\App\Models\Addon::isActive('whatsapp'))
                                        <a href="{{ wsRoute('customer.whatsapp.accounts.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.whatsapp.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                            <div class="relative shrink-0 w-[18px] h-[18px]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                                            </div>
                                            <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('WhatsApp') }}</p>
                                        </a>
                                    @endif

                                    @if(\App\Models\Addon::isActive('sms'))
                                        <a href="{{ wsRoute('customer.sms.accounts.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.sms.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                            <div class="relative shrink-0 w-[18px] h-[18px]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                            </div>
                                            <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('SMS') }}</p>
                                        </a>
                                    @endif

                                    @customercan('automations.enabled')
                                        <a href="{{ wsRoute('customer.automations.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.automations.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                            <div class="relative shrink-0 w-[18px] h-[18px]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-git-branch-plus-icon lucide-git-branch-plus"><path d="M6 3v12"/><path d="M18 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M6 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M15 6a9 9 0 0 0-9 9"/><path d="M18 15v6"/><path d="M21 18h-6"/></svg>
                                            </div>
                                            <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Automation') }}</p>
                                        </a>
                                    @endcustomercan

                                    <a href="{{ wsRoute('customer.email-validation.runs.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.email-validation.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail-search-icon lucide-mail-search"><path d="M22 12.5V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h7.5"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="M18 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><circle cx="18" cy="18" r="3"/><path d="m22 22-1.5-1.5"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Email Validation') }}</p>
                                    </a>
                                </div>
                            </div>

                            @customercan('ai_tools.permissions.can_access_ai_tools')
                            <div class="flex flex-col gap-1 items-start relative shrink-0 w-full">
                                <p class="font-normal leading-[18px] relative shrink-0 text-[#a8a8a8] dark:text-admin-text-secondary text-xs tracking-[-0.36px] ml-2">{{ __('AI') }}</p>
                                <div class="flex flex-col items-start relative shrink-0 w-auto mx-auto m-0" :class="sidebarCollapsed ? 'w-auto' : 'w-full'">
                                    @php
                                        $ragMenuOpen = request()->routeIs('customer.rag.*') || request()->routeIs('customer.ai-agents.*');
                                        $chatbotAgent = auth('customer')->check()
                                            ? \App\Models\AiAgent::forCustomer(auth('customer')->id())->active()->orderBy('name')->first()
                                            : null;
                                        $chatbotUrl = $chatbotAgent
                                            ? wsRoute('customer.ai-agents.chat', $chatbotAgent)
                                            : wsRoute('customer.ai-agents.index');
                                    @endphp
                                    <div x-data="{ open: {{ $ragMenuOpen ? 'true' : 'false' }} }" class="w-full sidebar-dropdown">
                                        <button type="button" @click="open = !open" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ $ragMenuOpen ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                            <div class="relative shrink-0 w-[18px] h-[18px]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                                            </div>
                                            <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px] flex-1 text-left">{{ __('AI Assistant') }}</p>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="open ? 'rotate-180' : ''">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-cloak x-show="open" data-label="{{ __('AI Assistant') }}" class="sidebar-submenu mt-1 flex flex-col gap-1 {{ app('locale.direction')->dir() === 'rtl' ? 'mr-6 border-r pr-4' : 'ml-6 border-l pl-4' }} border-gray-200 dark:border-white/10">
                                            <a href="{{ wsRoute('customer.ai-agents.index') }}" class="flex items-center w-full py-2 text-sm {{ request()->routeIs('customer.ai-agents.index') || request()->routeIs('customer.ai-agents.create') || request()->routeIs('customer.ai-agents.edit') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('AI Agents') }}
                                            </a>
                                            <a href="{{ $chatbotUrl }}" class="flex items-center w-full py-2 text-sm {{ request()->routeIs('customer.ai-agents.chat') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('AI Chatbot') }}
                                            </a>
                                            <a href="{{ wsRoute('customer.rag.knowledge.index') }}" class="flex items-center w-full py-2 text-sm {{ request()->routeIs('customer.rag.knowledge.*') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Knowledge Base') }}
                                            </a>
                                            <a href="{{ wsRoute('customer.rag.campaign.index') }}" class="flex items-center w-full py-2 text-sm {{ request()->routeIs('customer.rag.campaign.*') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Campaign Writer') }}
                                            </a>
                                            <a href="{{ wsRoute('customer.rag.insights.index') }}" class="flex items-center w-full py-2 text-sm {{ request()->routeIs('customer.rag.insights.*') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('AI Insights') }}
                                            </a>
                                            @if(\App\Models\Addon::isActive('ai-assistant'))
                                                <a href="{{ wsRoute('customer.ai-assistant.index') }}" class="flex items-center w-full py-2 text-sm {{ request()->routeIs('customer.ai-assistant.*') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                    {{ __('AI Assistant') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endcustomercan

                            @if(\App\Models\Addon::isActive('super-scrape'))
                            @customercan('scraper.permissions.can_access_scraper')
                            <div class="flex flex-col gap-1 items-start relative shrink-0 w-full">
                                <p class="font-normal leading-[18px] relative shrink-0 text-[#a8a8a8] dark:text-admin-text-secondary text-xs tracking-[-0.36px] ml-2">{{ __('Leads') }}</p>
                                <div class="flex flex-col items-start relative shrink-0 w-auto mx-auto m-0" :class="sidebarCollapsed ? 'w-auto' : 'w-full'">
                                    @php
                                        $leadsMenuOpen = request()->routeIs('customer.scraper.*');
                                    @endphp
                                    <div x-data="{ open: {{ $leadsMenuOpen ? 'true' : 'false' }} }" class="w-full sidebar-dropdown">
                                        <button type="button" @click="open = !open" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ $leadsMenuOpen ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                            <div class="relative shrink-0 w-[18px] h-[18px]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                            </div>
                                            <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px] flex-1 text-left">{{ __('Lead Scraper') }}</p>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="open ? 'rotate-180' : ''">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-cloak x-show="open" data-label="{{ __('Lead Scraper') }}" class="sidebar-submenu mt-1 flex flex-col gap-1 {{ app('locale.direction')->dir() === 'rtl' ? 'mr-6 border-r pr-4' : 'ml-6 border-l pl-4' }} border-gray-200 dark:border-white/10">
                                            <a href="{{ wsRoute('customer.scraper.index') }}" class="flex items-center w-full py-2 text-sm {{ request()->routeIs('customer.scraper.index') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Scraper') }}
                                            </a>
                                            <a href="{{ wsRoute('customer.scraper.lookup') }}" class="flex items-center w-full py-2 text-sm {{ request()->routeIs('customer.scraper.lookup') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Lookup') }}
                                            </a>
                                            <a href="{{ wsRoute('customer.scraper.jobs') }}" class="flex items-center w-full py-2 text-sm {{ request()->routeIs('customer.scraper.jobs') ? 'text-[#1E5FEA]' : 'text-gray-500 dark:text-admin-text-secondary' }} hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                                {{ __('Jobs') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endcustomercan
                            @endif

                            {{-- Projects (AI Builder) --}}
                            <div class="flex flex-col gap-1 items-start relative shrink-0 w-full">
                                <p class="font-normal leading-[18px] relative shrink-0 text-[#a8a8a8] dark:text-admin-text-secondary text-xs tracking-[-0.36px] ml-2">{{ __('Projects') }}</p>
                                <div class="flex flex-col items-start relative shrink-0 w-auto mx-auto m-0" :class="sidebarCollapsed ? 'w-auto' : 'w-full'">
                                    <a href="{{ wsRoute('customer.ai-projects.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.ai-projects.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles-icon lucide-sparkles"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><path d="M4 22v-4"/><path d="M2 18h4"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('All Projects') }}</p>
                                    </a>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1 items-start relative shrink-0 w-full">
                                <p class="font-normal leading-[18px] relative shrink-0 text-[#a8a8a8] dark:text-admin-text-secondary text-xs tracking-[-0.36px] ml-2">{{ __('Deliverability') }}</p>
                                <div class="flex flex-col items-start relative shrink-0 w-auto mx-auto m-0" :class="sidebarCollapsed ? 'w-auto' : 'w-full'">
                                    @customercan('servers.permissions.can_access_delivery_servers')
                                    <a href="{{ wsRoute('customer.delivery-servers.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.delivery-servers.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-truck-electric-icon lucide-truck-electric"><path d="M14 19V7a2 2 0 0 0-2-2H9"/><path d="M15 19H9"/><path d="M19 19h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62L18.3 9.38a1 1 0 0 0-.78-.38H14"/><path d="M2 13v5a1 1 0 0 0 1 1h2"/><path d="M4 3 2.15 5.15a.495.495 0 0 0 .35.86h2.15a.47.47 0 0 1 .35.86L3 9.02"/><circle cx="17" cy="19" r="2"/><circle cx="7" cy="19" r="2"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Delivery Servers') }}</p>
                                    </a>
                                    @endcustomercan

                                    <a href="{{ wsRoute('customer.warmups.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.warmups.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flame-icon lucide-flame"><path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Email Warmups') }}</p>
                                    </a>

                                    @customercan('servers.permissions.can_access_bounce_servers')
                                    <a href="{{ wsRoute('customer.bounce-servers.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.bounce-servers.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package-x-icon lucide-package-x"><path d="M12 22V12"/><path d="m16.5 14.5 5 5"/><path d="m16.5 19.5 5-5"/><path d="M21 10.5V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.729l7 4a2 2 0 0 0 2 .001l.13-.074"/><path d="M3.29 7 12 12l8.71-5"/><path d="m7.5 4.27 8.997 5.148"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Bounce Servers') }}</p>
                                    </a>
                                    @endcustomercan

                                    @customercan('servers.permissions.can_access_reply_servers')
                                    <a href="{{ wsRoute('customer.reply-servers.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.reply-servers.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square-reply-icon lucide-message-square-reply"><path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"/><path d="m10 8-3 3 3 3"/><path d="M17 14v-1a2 2 0 0 0-2-2H7"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Reply Servers') }}</p>
                                    </a>
                                    @endcustomercan

                                    @customercan('domains.tracking_domains.permissions.can_access_tracking_domains')
                                    <a href="{{ wsRoute('customer.tracking-domains.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.tracking-domains.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-scan-icon lucide-scan"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Tracking Domains') }}</p>
                                    </a>
                                    @endcustomercan

                                    @customercan('domains.sending_domains.permissions.can_access_sending_domains')
                                    <a href="{{ wsRoute('customer.sending-domains.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.sending-domains.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-globe-icon lucide-globe"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Sending Domains') }}</p>
                                    </a>
                                    @endcustomercan
                                </div>
                            </div>

                            <div class="flex flex-col gap-1 items-start relative shrink-0 w-full">
                                <p class="font-normal leading-[18px] relative shrink-0 text-[#a8a8a8] dark:text-admin-text-secondary text-xs tracking-[-0.36px] ml-2">{{ __('Developers') }}</p>
                                <div class="flex flex-col items-start relative shrink-0 w-auto mx-auto m-0" :class="sidebarCollapsed ? 'w-auto' : 'w-full'">
                                    @customercan('api.permissions.can_access_api')
                                    <a href="{{ wsRoute('customer.api.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.api.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-webhook-icon lucide-webhook"><path d="M18 16.98h-5.99c-1.1 0-1.95.94-2.48 1.9A4 4 0 0 1 2 17c.01-.7.2-1.4.57-2"/><path d="m6 17 3.13-5.78c.53-.97.1-2.18-.5-3.1a4 4 0 1 1 6.89-4.06"/><path d="m12 6 3.13 5.73C15.66 12.7 16.9 13 18 13a4 4 0 0 1 0 8"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('API') }}</p>
                                    </a>
                                    @endcustomercan

                                    @customercan('servers.permissions.can_access_delivery_servers')
                                    <a href="{{ wsRoute('customer.integrations.index') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.integrations.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package-plus-icon lucide-package-plus"><path d="M12 22V12"/><path d="M16 17h6"/><path d="M19 14v6"/><path d="M21 10.535V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.729l7 4a2 2 0 0 0 2 .001l1.675-.955"/><path d="M3.29 7 12 12l8.71-5"/><path d="m7.5 4.27 8.997 5.148"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Integrations') }}</p>
                                    </a>
                                    @endcustomercan
                                </div>
                            </div>

                            @if(isset($activeWorkspace) && $activeWorkspace)
                            <div class="flex flex-col gap-1 items-start relative shrink-0 w-full">
                                <p class="font-normal leading-[18px] relative shrink-0 text-[#a8a8a8] dark:text-admin-text-secondary text-xs tracking-[-0.36px] ml-2">{{ __('Workspace') }}</p>
                                <div class="flex flex-col items-start relative shrink-0 w-auto mx-auto m-0" :class="sidebarCollapsed ? 'w-auto' : 'w-full'">
                                    <a href="{{ wsRoute('customer.workspace-settings.general') }}" class="flex gap-2.5 items-center relative w-full shrink-0 py-1.5 px-2 {{ request()->routeIs('customer.workspace-settings.*') ? 'text-[#1E5FEA] bg-[rgba(30,95,234,0.08)] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5 hover:text-[#1E5FEA] dark:hover:text-[#1E5FEA] transition-colors">
                                        <div class="relative shrink-0 w-[18px] h-[18px]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </div>
                                        <p class="font-normal leading-[22px] relative shrink-0 text-sm tracking-[-0.48px]">{{ __('Settings') }}</p>
                                    </a>
                                </div>
                            </div>
                            @endif
                        </nav>
                    </div>

                    <div class="sticky bottom-0 z-20 w-full mt-auto shrink-0 border-gray-100 dark:border-admin-border dark:bg-admin-sidebar">
                        @php
                            $sidebarCustomer = auth()->guard('customer')->user();
                            $sidebarInitials = strtoupper(Str::substr($sidebarCustomer->first_name, 0, 1) . Str::substr($sidebarCustomer->last_name, 0, 1));
                            $sidebarQuotaUsage = app(\App\Services\Billing\UsageService::class)->getUsage($sidebarCustomer);
                            $sidebarQuotaUsed = (int) ($sidebarQuotaUsage['emails_sent_this_month'] ?? 0);
                            $sidebarQuotaLimit = $sidebarCustomer->groupLimit('sending_quota.monthly_quota')
                                ?? ((int) ($sidebarCustomer->quota ?? 0) ?: null);
                            $sidebarQuotaPercent = $sidebarQuotaLimit && $sidebarQuotaLimit > 0
                                ? min(100, max(0, (int) round(($sidebarQuotaUsed / $sidebarQuotaLimit) * 100)))
                                : 0;
                            $sidebarSubscription = $sidebarCustomer->subscriptions()
                                ->whereIn('status', ['active', 'trialing', 'past_due'])
                                ->latest()
                                ->first();
                            $sidebarPendingSubscription = $sidebarCustomer->subscriptions()
                                ->where('status', 'pending')
                                ->where('provider', 'manual')
                                ->latest()
                                ->first();
                            $sidebarHasPendingManualPayment = $sidebarPendingSubscription !== null;
                            $sidebarPlanName = trim((string) (
                                $sidebarSubscription?->plan_name
                                ?? data_get($sidebarSubscription, 'plan.name')
                                ?? ''
                            ));
                            if ($sidebarPlanName === '') {
                                $sidebarPlanName = 'Free plan';
                            }
                            $sidebarCurrentPlanPrice = (float) ($sidebarSubscription?->price ?? 0);
                            $sidebarUpgradePlan = \App\Models\Plan::query()
                                ->where('is_active', true)
                                ->where('is_public', true)
                                ->where('price', '>', $sidebarCurrentPlanPrice)
                                ->orderBy('price')
                                ->first();
                            $activeLocales = collect(app(\App\Translation\LocaleJsonService::class)->listLocales());
                            $currentLocale = (string) app()->getLocale();
                            $currentLocale = trim($currentLocale) !== '' ? $currentLocale : 'en';
                            $localeLabelMap = [
                                'ar' => 'Arabic',
                                'de' => 'German',
                                'en' => 'English',
                                'es' => 'Spanish',
                                'fr' => 'French',
                                'hi' => 'Hindi',
                                'id' => 'Indonesian',
                                'it' => 'Italian',
                                'ja' => 'Japanese',
                                'ko' => 'Korean',
                                'pt' => 'Portuguese',
                                'pt-br' => 'Portuguese (Brazil)',
                                'ru' => 'Russian',
                                'tr' => 'Turkish',
                                'ur' => 'Urdu',
                                'zh' => 'Chinese',
                                'zh-cn' => 'Chinese (Simplified)',
                                'zh-tw' => 'Chinese (Traditional)',
                            ];
                        @endphp
                        <div class="p-2 bg-gray-100/80 dark:bg-blue-900/80 backdrop-blur-sm rounded-lg" x-show="!sidebarCollapsed">
                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        
                                        <div class="min-w-0">
                                            <p class="truncate text-[13px] font-semibold text-[#101828] dark:text-admin-text-primary">
                                                {{ $sidebarPlanName }}
                                                @if($sidebarHasPendingManualPayment && !$sidebarSubscription)
                                                    <span class="ml-1 text-[11px] font-medium text-amber-600 dark:text-amber-300">({{ __('pending review') }})</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ wsRoute('customer.billing.index') }}" class="shrink-0 text-xs font-semibold text-[#1E5FEA] transition-colors hover:text-[#184fc3]">
                                        {{ __('Upgrade') }}
                                    </a>
                                </div>
                                <div class="mt-1 h-1 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-[#12B76A]" style="width: {{ $sidebarQuotaPercent > 0 ? $sidebarQuotaPercent : 8 }}%; background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0) 0, rgba(255,255,255,0) 7px, rgba(255,255,255,0.22) 7px, rgba(255,255,255,0.22) 12px);"></div>
                                </div>
                                <div class="mt-1 text-xs text-[#667085] dark:text-admin-text-secondary">
                                    @if($sidebarQuotaLimit)
                                        <span class="font-semibold text-[#344054] dark:text-admin-text-primary">{{ number_format($sidebarQuotaUsed) }}</span>
                                        {{ __('of') }}
                                        <span class="font-semibold text-[#344054] dark:text-admin-text-primary">{{ number_format($sidebarQuotaLimit) }}</span>
                                        {{ __('used this month') }}
                                    @else
                                        <span class="font-semibold text-[#344054] dark:text-admin-text-primary">{{ number_format($sidebarQuotaUsed) }}</span>
                                        {{ __('used this month') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="relative z-30" x-data="{ accountMenuOpen: false }" @click.outside="accountMenuOpen = false">
                            <button
                                type="button"
                                @click="accountMenuOpen = !accountMenuOpen"
                                class="w-full flex items-center gap-3 rounded-2xl p-2 transition-colors {{ app('locale.direction')->dir() === 'rtl' ? 'text-right' : 'text-left' }}"
                                :class="sidebarCollapsed ? 'justify-center px-0' : ''"
                            >
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#2f2f2f] text-xs font-semibold text-white">
                                    @if($sidebarCustomer && $sidebarCustomer->avatar_url)
                                        <img src="{{ $sidebarCustomer->avatar_url }}" alt="{{ $sidebarCustomer->full_name }}" class="h-full w-full object-cover">
                                    @else
                                        {{ $sidebarInitials }}
                                    @endif
                                </div>
                                <div class="sidebar-user-info min-w-0 flex-1" x-show="!sidebarCollapsed">
                                    <p class="text-sm font-semibold text-admin-text-primary truncate">
                                        {{ $sidebarCustomer->full_name }}
                                    </p>
                                    <p class="text-xs text-admin-text-secondary truncate">
                                        {{ $sidebarPlanName }}
                                    </p>
                                </div>
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border-gray-200 text-admin-text-secondary dark:border-admin-border" x-show="!sidebarCollapsed">
                                    <svg class="w-4 h-4 transition-transform" :class="accountMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>

                            <div
                                x-cloak
                                x-show="accountMenuOpen"
                                x-transition
                                class="absolute z-[60] rounded border border-gray-200 bg-white p-0 shadow-2xl dark:border-admin-border dark:bg-admin-sidebar"
                                :class="sidebarCollapsed
                                    ? '{{ app('locale.direction')->dir() === 'rtl' ? 'right-0 left-auto bottom-full mb-3 w-80 lg:right-full lg:bottom-0 lg:mb-0 lg:mr-3' : 'left-0 right-auto bottom-full mb-3 w-80 lg:left-full lg:bottom-0 lg:mb-0 lg:ml-3' }}'
                                    : '{{ app('locale.direction')->dir() === 'rtl' ? 'right-0 left-0 bottom-full mb-3 w-full' : 'left-0 right-0 bottom-full mb-3 w-full' }}'"
                            >
                                <div class="flex items-center gap-3 px-3 py-2 border-b border-gray-200 dark:border-admin-border">
                                    <div class="min-w-0 flex-1 text-sm text-admin-text-secondary truncate">
                                        {{ $sidebarCustomer->email }}
                                    </div>
                                    @if($sidebarUpgradePlan)
                                        <a
                                            href="{{ wsRoute('customer.billing.index') }}"
                                            class="shrink-0 inline-flex items-center rounded-full bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 px-3 py-1 text-xs font-semibold text-amber-950 shadow-sm transition-transform duration-200 hover:scale-105 animate-pulse"
                                        >
                                            {{ __('Upgrade') }}
                                        </a>
                                    @endif
                                </div>
  
                                <div class="py-2 space-y-1">
                                    <a href="{{ wsRoute('customer.settings.index') }}" class="flex items-center gap-3 p-2 text-sm {{ request()->routeIs('customer.settings.*') ? 'bg-[rgba(30,95,234,0.08)] text-[#1E5FEA] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M14 17H5"/><path d="M19 7h-9"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                                        <span>{{ __('Settings') }}</span>
                                    </a>

                                    @if($sidebarCustomer && (bool) $sidebarCustomer->groupSetting('supabase.access', false))
                                    <a href="{{ wsRoute('customer.supabase.index') }}" class="flex items-center gap-3 p-2 text-sm {{ request()->routeIs('customer.supabase.*') ? 'bg-[rgba(30,95,234,0.08)] text-[#1E5FEA] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>
                                        <span>{{ __('Database') }}</span>
                                    </a>
                                    @endif

                                    @if($activeLocales->count() > 1)
                                        @php
                                            $currentLocaleMeta = $activeLocales->firstWhere('code', $currentLocale);
                                            $currentLocaleName = is_string($currentLocaleMeta->name ?? null) ? trim((string) $currentLocaleMeta->name) : '';
                                            if ($currentLocaleName === '' || Str::lower($currentLocaleName) === Str::lower($currentLocale)) {
                                                $currentLocaleName = $localeLabelMap[Str::lower($currentLocale)] ?? Str::upper($currentLocale);
                                            }
                                        @endphp
                                        <div class="relative" x-data="{ languageFlyoutOpen: false }" @mouseenter="languageFlyoutOpen = true" @mouseleave="languageFlyoutOpen = false">
                                            <button
                                                type="button"
                                                @click="languageFlyoutOpen = !languageFlyoutOpen"
                                                class="flex w-full items-center gap-3 p-2 text-sm text-[#1b1b20] dark:text-admin-text-primary hover:bg-gray-50 dark:hover:bg-white/5 {{ app('locale.direction')->dir() === 'rtl' ? 'text-right' : 'text-left' }}"
                                                :class="languageFlyoutOpen ? 'bg-gray-100 dark:bg-white/10' : ''"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>
                                                <span class="flex-1 {{ app('locale.direction')->dir() === 'rtl' ? 'text-right' : 'text-left' }}">{{ __('Language') }}</span>
                                                <span class="text-xs text-admin-text-secondary truncate max-w-[110px]">{{ $currentLocaleName }}</span>
                                                <svg class="h-4 w-4 shrink-0 text-admin-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ app('locale.direction')->dir() === 'rtl' ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7' }}" />
                                                </svg>
                                            </button>

                                            <div
                                                x-cloak
                                                x-show="languageFlyoutOpen"
                                                x-transition
                                                class="absolute top-0 z-[60] w-72 rounded border border-gray-200 bg-white p-0 shadow-2xl dark:border-admin-border dark:bg-admin-sidebar {{ app('locale.direction')->dir() === 'rtl' ? 'right-full mr-2' : 'left-full ml-2' }}"
                                            >
                                                <div class="max-h-80 overflow-y-auto space-y-1">
                                                    @foreach($activeLocales as $loc)
                                                        @php
                                                            $locCode = is_string($loc->code ?? null) ? trim((string) $loc->code) : '';
                                                            $locName = is_string($loc->name ?? null) ? trim((string) $loc->name) : '';
                                                            if ($locName === '' || Str::lower($locName) === Str::lower($locCode)) {
                                                                $locName = $localeLabelMap[Str::lower($locCode)] ?? Str::upper($locCode);
                                                            }
                                                        @endphp
                                                        <form method="POST" action="{{ wsRoute('customer.language.update') }}" data-turbo="false">
                                                            @csrf
                                                            <button
                                                                type="submit"
                                                                name="locale"
                                                                value="{{ $locCode }}"
                                                                class="flex w-full items-center gap-3 p-2 text-sm {{ app('locale.direction')->dir() === 'rtl' ? 'text-right' : 'text-left' }} {{ $currentLocale === $locCode ? 'bg-gray-100 text-[#1b1b20] dark:bg-white/10 dark:text-admin-text-primary' : 'text-[#1b1b20] hover:bg-gray-50 dark:text-admin-text-primary dark:hover:bg-white/5' }}"
                                                            >
                                                                <span class="flex-1 min-w-0">
                                                                    <span class="block truncate">{{ $locName }}</span>
                                                                    <span class="block text-xs text-admin-text-secondary uppercase mt-0.5">{{ $locCode }}</span>
                                                                </span>
                                                                @if($currentLocale === $locCode)
                                                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                @endif
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @customercan('support.permissions.can_access_support')
                                        <a href="{{ wsRoute('customer.support-tickets.index') }}" class="flex items-center gap-3 p-2 text-sm {{ request()->routeIs('customer.support-tickets.*') ? 'bg-[rgba(30,95,234,0.08)] text-[#1E5FEA] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm0 0a9 9 0 1 1 18 0m0 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/></svg>
                                            <span>{{ __('Support') }}</span>
                                        </a>
                                    @endcustomercan

                                    <div class="my-2 border-t border-gray-200 dark:border-admin-border"></div>

                                    <a href="{{ wsRoute('customer.billing.index') }}" class="flex items-center gap-3 p-2 text-sm {{ request()->routeIs('customer.billing.*') ? 'bg-[rgba(30,95,234,0.08)] text-[#1E5FEA] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M15 12h-5"/><path d="M15 8h-5"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3"/></svg>
                                        <span>{{ __('Billing') }}</span>
                                    </a>

                                    <a href="{{ wsRoute('customer.affiliate.index') }}" class="flex items-center gap-3 p-2 text-sm {{ request()->routeIs('customer.affiliate.*') ? 'bg-[rgba(30,95,234,0.08)] text-[#1E5FEA] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5">
                                        <svg class="block h-[18px] w-[18px] shrink-0" fill="none" preserveAspectRatio="none" viewBox="0 0 18 18">
                                            <path d="M3.75 15.75V3.75C3.75 2.92157 4.42157 2.25 5.25 2.25H12.75C13.5784 2.25 14.25 2.92157 14.25 3.75V15.75" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.125" />
                                            <path d="M6 6.75H12" stroke="currentColor" stroke-linecap="round" stroke-width="1.125" />
                                            <path d="M6 9.75H12" stroke="currentColor" stroke-linecap="round" stroke-width="1.125" />
                                            <path d="M6 12.75H10.5" stroke="currentColor" stroke-linecap="round" stroke-width="1.125" />
                                        </svg>
                                        <span>{{ __('Affiliate') }}</span>
                                    </a>

                                    <a href="{{ wsRoute('customer.usage.index') }}" class="flex items-center gap-3 p-2 text-sm {{ request()->routeIs('customer.usage.*') ? 'bg-[rgba(30,95,234,0.08)] text-[#1E5FEA] dark:bg-[rgba(30,95,234,0.12)]' : 'text-[#1b1b20] dark:text-admin-text-primary' }} hover:bg-gray-50 dark:hover:bg-white/5">
                                        <svg class="block h-[18px] w-[18px] shrink-0" fill="none" preserveAspectRatio="none" viewBox="0 0 18 18">
                                            <path d="M7.3125 2.25H4.3125C3.78916 2.25 3.5275 2.25 3.31457 2.31459C2.83517 2.46001 2.46001 2.83517 2.31459 3.31457C2.25 3.5275 2.25 3.78917 2.25 4.3125C2.25 4.83584 2.25 5.0975 2.31459 5.31043C2.46001 5.78983 2.83517 6.16499 3.31457 6.31041C3.5275 6.375 3.78916 6.375 4.3125 6.375H7.3125C7.83585 6.375 8.09752 6.375 8.31045 6.31041C8.78985 6.16499 9.165 5.78983 9.31042 5.31043C9.375 5.0975 9.375 4.83584 9.375 4.3125C9.375 3.78917 9.375 3.5275 9.31042 3.31457C9.165 2.83517 8.78985 2.46001 8.31045 2.31459C8.09752 2.25 7.83585 2.25 7.3125 2.25Z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.25" />
                                            <path d="M15.75 7.3125V4.3125C15.75 3.78916 15.75 3.5275 15.6854 3.31457C15.54 2.83517 15.1649 2.46001 14.6855 2.31459C14.4725 2.25 14.2109 2.25 13.6875 2.25C13.1642 2.25 12.9025 2.25 12.6896 2.31459C12.2102 2.46001 11.835 2.83517 11.6896 3.31457C11.625 3.5275 11.625 3.78916 11.625 4.3125V7.3125C11.625 7.83585 11.625 8.09752 11.6896 8.31045C11.835 8.78985 12.2102 9.165 12.6896 9.31042C12.9025 9.375 13.1642 9.375 13.6875 9.375C14.2109 9.375 14.4725 9.375 14.6855 9.31042C15.1649 9.165 15.54 8.78985 15.6854 8.31045C15.75 8.09752 15.75 7.83585 15.75 7.3125Z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.25" />
                                            <path d="M12.6896 15.6854C12.9025 15.75 13.1642 15.75 13.6875 15.75C14.2109 15.75 14.4725 15.75 14.6855 15.6854C15.1649 15.54 15.54 15.1649 15.6854 14.6855C15.75 14.4725 15.75 14.2109 15.75 13.6875C15.75 13.1642 15.75 12.9025 15.6854 12.6896C15.54 12.2102 15.1649 11.835 14.6855 11.6896C14.4725 11.625 14.2109 11.625 13.6875 11.625C13.1642 11.625 12.9025 11.625 12.6896 11.6896C12.2102 11.835 11.835 12.2102 11.6896 12.6896C11.625 12.9025 11.625 13.1642 11.625 13.6875C11.625 14.2109 11.625 14.4725 11.6896 14.6855C11.835 15.1649 12.2102 15.54 12.6896 15.6854Z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.25" />
                                            <path d="M6.375 8.625H5.25C3.83578 8.625 3.12868 8.625 2.68934 9.06435C2.25 9.5037 2.25 10.2108 2.25 11.625V12.75C2.25 14.1642 2.25 14.8713 2.68934 15.3106C3.12868 15.75 3.83578 15.75 5.25 15.75H6.375C7.7892 15.75 8.4963 15.75 8.93565 15.3106C9.375 14.8713 9.375 14.1642 9.375 12.75V11.625C9.375 10.2108 9.375 9.5037 8.93565 9.06435C8.4963 8.625 7.7892 8.625 6.375 8.625Z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.25" />
                                        </svg>
                                        <span>{{ __('Usage') }}</span>
                                    </a>

                                    <button
                                        type="button"
                                        @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                                        class="flex w-full items-center gap-3 p-2 text-sm text-[#1b1b20] hover:bg-gray-50 dark:text-admin-text-primary dark:hover:bg-white/5"
                                    >
                                        <svg x-cloak x-show="!darkMode" class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <svg x-cloak x-show="darkMode" class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                        </svg>
                                        <span>{{ __('Theme') }}</span>
                                        <span class="ml-auto text-xs text-admin-text-secondary" x-text="darkMode ? 'Dark' : 'Light'"></span>
                                    </button>
                                </div>
  
                                <div class="border-t border-gray-200 py-1 dark:border-admin-border">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-3 p-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30">
                                            <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            <span>{{ __('Log out') }}</span>
                                        </button>
                                    </form>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            @php
                $pageHeaderTitle = trim($__env->yieldContent('page-title', $pageTitle));
                $hasPageActions = trim($__env->yieldContent('page-actions')) !== '';
                $hasCustomBreadcrumbs = trim($__env->yieldContent('breadcrumbs')) !== '';
                $hasPageTitleMeta = trim($__env->yieldContent('page-title-meta')) !== '';
                $hasPageTitleDetails = trim($__env->yieldContent('page-title-details')) !== '';
                $headerSearchQuery = trim((string) request()->query('q', ''));
                $hideTopHeader = $isEmbeddedFrame
                    || trim($__env->yieldContent('hide-top-header')) !== ''
                    || request()->routeIs('customer.campaigns.create')
                    || request()->routeIs('customer.campaigns.edit')
                    || request()->routeIs('customer.ai-projects.builder')
                    || request()->routeIs('customer.ai-projects.builder.show')
                    || request()->routeIs('customer.templates.create')
                    || request()->routeIs('customer.templates.unlayer.create')
                    || request()->routeIs('customer.templates.edit')
                    || request()->routeIs('customer.templates.unlayer.edit');
            @endphp

            <div class="flex-1 flex flex-col overflow-hidden min-h-0" x-data="{ 
                    notificationsOpen: false
                }">
                <header class="sticky top-0 z-30 shrink-0 h-10 bg-white dark:bg-admin-main border-b border-admin-border flex items-center justify-between px-4 sm:px-4 gap-4" @if($hideTopHeader) style="display:none" @endif>
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-md text-admin-text-secondary hover:text-admin-text-primary hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            @click.stop="sidebarOpen = true"
                            aria-label="Open sidebar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        @php
                            $headerBreadcrumbTitle = trim($__env->yieldContent('page-title', $pageTitle ?? ''));
                            $headerHasCustomBreadcrumbs = trim($__env->yieldContent('breadcrumbs')) !== '';
                        @endphp
                        <nav aria-label="Breadcrumb" class="hidden lg:block flex-1 min-w-0">
                            @if($headerHasCustomBreadcrumbs)
                                @yield('breadcrumbs')
                            @elseif($headerBreadcrumbTitle !== '' && !request()->routeIs('customer.dashboard'))
                                <ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
                                    <li>
                                        <a href="{{ wsRoute('customer.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">
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

                    <div class="flex items-center gap-4 shrink-0">
                        @php
                            $customer = auth('customer')->user();
                        @endphp

                        {{-- Invite Team (visible when active workspace member has members.manage permission) --}}
                        @if(isset($activeWorkspace) && $activeWorkspace && isset($activeWorkspaceMember) && $activeWorkspaceMember && $activeWorkspaceMember->hasPermission('members.manage'))
                        <a href="{{ wsRoute('customer.workspaces.members.index', $activeWorkspace) }}"
                           class="inline-flex items-center gap-1.5 rounded border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-plus-icon lucide-user-plus"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                            <span>{{ __('Invite') }}</span>
                        </a>
                        @endif

                        <div class="relative">
                            <button
                                type="button"
                                @click.stop="notificationsOpen = !notificationsOpen"
                                class="relative inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none dark:text-admin-text-secondary dark:hover:bg-white/5 dark:hover:text-admin-text-primary"
                                data-notifications-root
                                data-notifications-feed-url="{{ wsRoute('customer.notifications.feed') }}"
                                data-notifications-mark-all-read-url="{{ wsRoute('customer.notifications.mark-all-read') }}"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                @php
                                    $unreadCount = auth('customer')->check()
                                        ? auth('customer')->user()->unreadNotifications()->count()
                                        : 0;
                                @endphp
                                @if($unreadCount > 0)
                                    <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-red-500" data-notifications-badge></span>
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
                                    <div class="px-6 pb-3 flex items-center justify-between border-b border-gray-100 dark:border-admin-border">
                                        <div class="inline-flex items-center bg-gray-100 dark:bg-white/5 rounded-lg p-1 text-sm">
                                            <button type="button" @click="notificationsTab='all'; window.dispatchEvent(new CustomEvent('mailpurse-notifications-tab-change', { detail: { tab: 'all' } }))" :class="notificationsTab==='all' ? 'bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary shadow-sm' : 'text-gray-500 dark:text-admin-text-secondary'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md font-medium transition">
                                                <span>{{ __('All') }}</span>
                                                <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded text-[11px] font-semibold bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-200" data-notifications-tab-count="all">0</span>
                                            </button>
                                            <button type="button" @click="notificationsTab='unread'; window.dispatchEvent(new CustomEvent('mailpurse-notifications-tab-change', { detail: { tab: 'unread' } }))" :class="notificationsTab==='unread' ? 'bg-white dark:bg-admin-sidebar text-gray-900 dark:text-admin-text-primary shadow-sm' : 'text-gray-500 dark:text-admin-text-secondary'" class="px-3 py-1.5 rounded-md font-medium transition">{{ __('Unread') }}</button>
                                        </div>
                                        <button type="button" class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-admin-text-primary rounded" data-notifications-mark-all-read title="{{ __('Mark all read') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="sr-only" data-notifications-unread-label>
                                                @if($unreadCount > 0){{ $unreadCount }} {{ __('unread') }}@else{{ __('Up to date') }}@endif
                                            </span>
                                        </button>
                                    </div>
                                    {{-- List --}}
                                    <div class="flex-1 overflow-y-auto" :data-notifications-tab="notificationsTab">
                                        <div class="divide-y divide-gray-100 dark:divide-admin-border" data-notifications-list>
                                            @php
                                                $notifications = auth('customer')->check()
                                                    ? auth('customer')->user()->notifications()->latest()->limit(10)->get()
                                                    : collect();
                                            @endphp
                                            @forelse($notifications as $notification)
                                                <div class="px-6 py-4 flex items-start gap-3" data-notifications-status="{{ $notification->read_at ? 'read' : 'unread' }}">
                                                    <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center flex-shrink-0" data-notification-icon>
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">
                                                            {{ $notification->data['title'] ?? 'Notification' }}
                                                        </p>
                                                        @if(!empty($notification->data['message']))
                                                            <p class="mt-1 text-sm text-gray-600 dark:text-admin-text-secondary">
                                                                {{ $notification->data['message'] }}
                                                            </p>
                                                        @endif
                                                        <p class="mt-1.5 text-xs text-gray-400">
                                                            {{ $notification->created_at->diffForHumans() }}
                                                        </p>
                                                    </div>
                                                    @if(!$notification->read_at)
                                                        <button
                                                            type="button"
                                                            class="ml-3 shrink-0 rounded-md px-2.5 py-1 text-xs font-medium text-primary-600 hover:bg-primary-50 hover:text-primary-700 dark:text-primary-300 dark:hover:bg-white/5"
                                                            data-notification-mark-read
                                                            data-notification-mark-read-url="{{ route('customer.notifications.mark-read', ['notification' => $notification->id]) }}"
                                                            aria-label="{{ __('Mark notification as read') }}"
                                                        >
                                                            {{ __('Mark read') }}
                                                        </button>
                                                    @endif
                                                    @if(!$notification->read_at)
                                                        <span class="mt-2 w-2 h-2 rounded-full bg-gray-900 dark:bg-white flex-shrink-0"></span>
                                                    @endif
                                                </div>
                                            @empty
                                                <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-admin-text-secondary">
                                                    No notifications yet.
                                                </div>
                                            @endforelse
                                        </div>
                                        <div class="hidden px-6 py-10 text-center text-sm text-gray-500 dark:text-admin-text-secondary" data-notifications-empty-unread>
                                            NO unread notificaitons
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </template>

                        <div class="relative">
                            @php
                                $customer = auth('customer')->user();
                            @endphp
                            <a
                                href="{{ wsRoute('customer.profile.edit') }}"
                                class="flex items-center text-admin-text-secondary hover:text-admin-text-primary focus:outline-none"
                                aria-label="{{ __('Profile') }}"
                            >
                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center overflow-hidden">
                                    @if($customer && $customer->avatar_url)
                                        <img src="{{ $customer->avatar_url }}" alt="{{ $customer->full_name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-xs font-semibold text-admin-text-primary">
                                            {{ strtoupper(Str::substr($customer->first_name, 0, 1) . Str::substr($customer->last_name, 0, 1)) }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </div>
                    </div>
                </header>

             <!-- Page Content -->
             <main class="{{ $isEmbeddedFrame ? 'flex-1' : ($isCampaign ? 'lg:flex-none' : 'flex-1') }} flex flex-col min-h-0 overflow-y-auto {{ $disableMainScroll ? 'lg:overflow-y-hidden' : '' }} overflow-x-auto lg:overflow-x-visible {{ trim($__env->yieldContent('content-padding-classes')) !== '' ? trim($__env->yieldContent('content-padding-classes')) : 'p-4 sm:p-6' }}" {!! trim($__env->yieldContent('page-container-attributes')) !!} @if($isCampaign && !$isEmbeddedFrame) style="width: calc(100vw - 450px); max-width: calc(100vw - 450px);" @endif>
                 @php
                     $toastPosition = \App\Models\Setting::get('toast_position', 'bottom_center');
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
                        default => 'fixed bottom-4 left-1/2 -translate-x-1/2 items-center',
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

                @if(session()->has('impersonator_admin_id'))
                    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 shadow-sm dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold">{{ __('Admin impersonation active') }}</div>
                                <div class="text-sm text-amber-800 dark:text-amber-200">{{ __('You are currently browsing as this customer.') }}</div>
                            </div>
                            <form method="POST" action="{{ wsRoute('customer.stop-impersonation') }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:bg-amber-500 dark:hover:bg-amber-400 dark:text-amber-950">
                                    {{ __('Return to Admin') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                @php
                    $hasCustomPageHeader = trim($__env->yieldContent('page-header')) !== '';
                    $hidePageHeader = trim($__env->yieldContent('hide-page-header')) !== ''
                        || request()->routeIs('customer.templates.create')
                        || request()->routeIs('customer.templates.unlayer.create')
                        || request()->routeIs('customer.templates.edit')
                        || request()->routeIs('customer.templates.unlayer.edit');
                @endphp
                @if($hasCustomPageHeader && !$hidePageHeader)
                    @yield('page-header')
                @elseif($pageHeaderTitle !== '' && !$hidePageHeader)
                    <div class="mp-page-header">
                        <div class="mp-page-header__main">
                            <div class="flex flex-wrap items-center gap-3 min-w-0">
                                <h1 class="mp-page-title">{!! __($pageHeaderTitle) !!}</h1>
                                @if($hasPageTitleMeta)
                                    @yield('page-title-meta')
                                @endif
                            </div>
                            @if($hasPageTitleDetails)
                                <div class="mp-page-desc">
                                    @yield('page-title-details')
                                </div>
                            @endif
                        </div>

                        @if($hasPageActions)
                            <div class="mp-page-header__actions w-full lg:w-auto">
                                @yield('page-actions')
                            </div>
                        @endif
                    </div>
                @endif

                <div {!! trim($__env->yieldContent('content-wrapper-attributes')) !!}>
                    @yield('content')
                </div>
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
                        // ignore
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
                                // ignore
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

    <script>
        (function () {
            function initSidebarTooltips() {
                var sidebar = document.querySelector('[data-sidebar="app"]');
                if (!sidebar) return;

                var tip = document.createElement('div');
                tip.id = 'sidebar-collapsed-tooltip';
                document.body.appendChild(tip);

                var arrow = document.createElement('div');
                arrow.id = 'sidebar-collapsed-tooltip-arrow';
                document.body.appendChild(arrow);

                function showTip(el, label) {
                    if (sidebar.getAttribute('data-collapsed') !== 'true') return;
                    var rect = el.getBoundingClientRect();
                    var sw = sidebar.getBoundingClientRect().right;
                    var midY = rect.top + rect.height / 2;
                    tip.textContent = label;
                    tip.style.left = (sw + 10) + 'px';
                    tip.style.top = midY + 'px';
                    tip.style.display = 'block';
                    arrow.style.left = (sw + 4) + 'px';
                    arrow.style.top = midY + 'px';
                    arrow.style.display = 'block';
                }

                function hideTip() {
                    tip.style.display = 'none';
                    arrow.style.display = 'none';
                }

                sidebar.querySelectorAll('nav a, nav button[type="button"]').forEach(function (el) {
                    if (el.closest('.sidebar-dropdown')) return;
                    var p = el.querySelector(':scope > p');
                    if (p && p.textContent.trim()) {
                        var label = p.textContent.trim();
                        el.addEventListener('mouseenter', function () { showTip(el, label); });
                        el.addEventListener('mouseleave', hideTip);
                        el.addEventListener('click', hideTip);
                    }
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSidebarTooltips);
            } else {
                initSidebarTooltips();
            }
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
                        // ignore
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
                                // ignore
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

    <script>
        (function () {
            function initSidebarTooltips() {
                var sidebar = document.querySelector('[data-sidebar="app"]');
                if (!sidebar) return;

                var tip = document.createElement('div');
                tip.id = 'sidebar-collapsed-tooltip';
                document.body.appendChild(tip);

                var arrow = document.createElement('div');
                arrow.id = 'sidebar-collapsed-tooltip-arrow';
                document.body.appendChild(arrow);

                function showTip(el, label) {
                    if (sidebar.getAttribute('data-collapsed') !== 'true') return;
                    var rect = el.getBoundingClientRect();
                    var sw = sidebar.getBoundingClientRect().right;
                    var midY = rect.top + rect.height / 2;
                    tip.textContent = label;
                    tip.style.left = (sw + 10) + 'px';
                    tip.style.top = midY + 'px';
                    tip.style.display = 'block';
                    arrow.style.left = (sw + 4) + 'px';
                    arrow.style.top = midY + 'px';
                    arrow.style.display = 'block';
                }

                function hideTip() {
                    tip.style.display = 'none';
                    arrow.style.display = 'none';
                }

                sidebar.querySelectorAll('nav a, nav button[type="button"]').forEach(function (el) {
                    if (el.closest('.sidebar-dropdown')) return;
                    var p = el.querySelector(':scope > p');
                    if (p && p.textContent.trim()) {
                        var label = p.textContent.trim();
                        el.addEventListener('mouseenter', function () { showTip(el, label); });
                        el.addEventListener('mouseleave', hideTip);
                        el.addEventListener('click', hideTip);
                    }
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSidebarTooltips);
            } else {
                initSidebarTooltips();
            }
        })();
    </script>

    @if(\App\Models\Addon::isActive('ai-assistant') && filter_var(\App\Models\Setting::get('ai_assistant_widget_enabled', false), FILTER_VALIDATE_BOOLEAN))
    <div class="fixed bottom-5 right-5 z-[60]" x-data="assistantWidget()">
        <div x-cloak x-show="open" class="absolute bottom-16 right-0 w-80 max-w-[calc(100vw-2rem)] h-96 bg-white dark:bg-[#111] border border-gray-200 dark:border-white/10 rounded-xl shadow-xl flex flex-col overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-white/10 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('AI Assistant') }}</span>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <div class="flex-1 overflow-y-auto p-3 space-y-2" x-ref="wscroller">
                <template x-for="(m, i) in messages" :key="i">
                    <div class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[85%] rounded-xl px-3 py-1.5 text-xs" :class="m.role === 'user' ? 'bg-[#1E5FEA] text-white' : 'bg-gray-100 dark:bg-white/10 text-gray-900 dark:text-white'">
                            <span class="whitespace-pre-wrap" x-text="m.content"></span>
                        </div>
                    </div>
                </template>
                <div x-show="loading" class="text-[11px] text-gray-400">{{ __('Thinking...') }}</div>
            </div>
            <form @submit.prevent="send()" class="border-t border-gray-100 dark:border-white/10 p-2 flex gap-2">
                <input type="text" x-model="input" :disabled="loading" required placeholder="{{ __('Ask...') }}" class="flex-1 rounded-lg border-gray-200 dark:border-white/10 dark:bg-admin-bg text-xs">
                <button type="submit" :disabled="loading" class="px-3 py-1.5 bg-[#1E5FEA] text-white text-xs rounded-lg disabled:opacity-50">{{ __('Send') }}</button>
            </form>
        </div>
        <button type="button" @click="open = !open" class="w-12 h-12 rounded-full bg-[#1E5FEA] text-white shadow-lg flex items-center justify-center hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
        </button>
    </div>
    <script>
        function assistantWidget() {
            return {
                open: false,
                input: '',
                loading: false,
                conversationId: null,
                messages: [],
                async send() {
                    const text = this.input.trim();
                    if (!text || this.loading) return;
                    this.messages.push({ role: 'user', content: text });
                    this.input = '';
                    this.loading = true;
                    try {
                        const res = await fetch('{{ wsRoute('customer.ai-assistant.ask') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ message: text, conversation_id: this.conversationId }),
                        });
                        const data = await res.json();
                        if (data.conversation_id) this.conversationId = data.conversation_id;
                        this.messages.push({ role: 'assistant', content: data.reply || data.error || '' });
                    } catch (e) {
                        this.messages.push({ role: 'assistant', content: '{{ __('Request failed.') }}' });
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
    @endif

    @stack('scripts')
</body>
</html>

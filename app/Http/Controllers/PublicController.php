<?php

namespace App\Http\Controllers;

use App\Models\AiBuilderSession;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Setting;
use App\Services\PageBuilder\PageBuilderRenderer;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Get the configured app domain for subdomain detection.
     */
    private function getAppDomain(): ?string
    {
        $url = config('app.url');
        if (!is_string($url) || $url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        return preg_replace('/:\d+$/', '', $host);
    }

    /**
     * Show the home/landing page.
     */
    public function home(Request $request)
    {
        $host = $request->getHost();
        $hostWithoutPort = preg_replace('/:\d+$/', '', $host);

        // Serve page if this host is mapped as a custom domain
        if ($hostWithoutPort) {
            $page = Page::where('custom_domain', $hostWithoutPort)
                ->where('status', 'publish')
                ->whereIn('type', ['page', 'homepage'])
                ->first();

            if ($page) {
                return $this->renderPage($request, $page);
            }

            $aiSession = AiBuilderSession::where('app_meta->custom_domain', $hostWithoutPort)
                ->where('page_status', 'published')
                ->whereNotNull('preview_html')
                ->first();

            if ($aiSession) {
                return $this->renderAiBuilderPage($request, $aiSession);
            }

            // Serve page for wildcard subdomain (slug.appdomain.com)
            $appDomain = $this->getAppDomain();
            if ($appDomain && $hostWithoutPort !== $appDomain && str_ends_with($hostWithoutPort, '.' . $appDomain)) {
                $subdomain = explode('.', $hostWithoutPort)[0] ?? '';
                $reserved = ['www', 'admin', 'api', 'app', 'mail', 'ftp', 'smtp', 'imap', 'cdn', 'static', 'status', 'webmail', 'cpanel'];
                if ($subdomain !== '' && !in_array(strtolower($subdomain), $reserved, true)) {
                    $aiSession = AiBuilderSession::where('page_slug', $subdomain)
                        ->where('page_status', 'published')
                        ->whereNotNull('preview_html')
                        ->first();

                    if ($aiSession) {
                        return $this->renderAiBuilderPage($request, $aiSession);
                    }
                }
            }
        }

        // Check for custom homepage via setting
        try {
            $homepagePageId = Setting::get('homepage_page_id');
        } catch (\Throwable $e) {
            $homepagePageId = null;
        }
        if (is_numeric($homepagePageId)) {
            $page = Page::where('id', (int) $homepagePageId)
                ->where('status', 'publish')
                ->first();
            if ($page) {
                return $this->renderPage($request, $page);
            }
        }

        try {
            $redirectEnabled = (bool) Setting::get('home_redirect_enabled', 0);
        } catch (\Throwable $e) {
            $redirectEnabled = false;
        }

        if ($redirectEnabled) {
            try {
                $target = Setting::get('home_redirect_url', '');
            } catch (\Throwable $e) {
                $target = '';
            }

            $target = is_string($target) ? trim($target) : '';

            if ($target !== '' && $target !== '/') {
                $parsed = parse_url($target);
                $scheme = is_array($parsed) && is_string($parsed['scheme'] ?? null)
                    ? strtolower((string) $parsed['scheme'])
                    : null;

                if ($scheme === null && str_starts_with($target, '/')) {
                    $current = rtrim((string) $request->url(), '/');
                    $dest = rtrim((string) url($target), '/');
                    if ($dest !== '' && $dest !== $current) {
                        return redirect()->to($target);
                    }
                }

                if (in_array($scheme, ['http', 'https'], true)) {
                    $current = rtrim((string) $request->url(), '/');
                    $dest = rtrim($target, '/');
                    if ($dest !== '' && $dest !== $current) {
                        return redirect()->away($target);
                    }
                }
            }
        }

        try {
            $homeVersion = Setting::get('home_page_variant', '1');
        } catch (\Throwable $e) {
            $homeVersion = '1';
        }

        $homeVersion = is_string($homeVersion) ? trim($homeVersion) : '1';
        if ($homeVersion === 'all') {
            $homeVersion = '1';
        }

        if (!in_array($homeVersion, ['1', '2', '3', '4', '5'], true)) {
            $homeVersion = '1';
        }

        return $this->renderHomepageVariant($homeVersion);
    }

    public function homeVariant(string $variant)
    {
        $variant = trim($variant);
        if (!in_array($variant, ['1', '2', '3', '4', '5'], true)) {
            abort(404);
        }

        try {
            $selected = Setting::get('home_page_variant', '1');
        } catch (\Throwable $e) {
            $selected = '1';
        }

        $selected = is_string($selected) ? trim($selected) : '1';
        if (!in_array($selected, ['all', '1', '2', '3', '4', '5'], true)) {
            $selected = '1';
        }

        if ($selected !== 'all' && $selected !== $variant) {
            abort(404);
        }

        return $this->renderHomepageVariant($variant);
    }

    private function renderHomepageVariant(string $variant): Response
    {
        $view = match ($variant) {
            '2' => 'public.home-2',
            '3' => 'public.home-3',
            '4' => 'public.home-v2',
            '5' => 'public.home-5',
            default => 'public.home',
        };

        $html = view($view)->render();

        return response($html);
    }

    /**
     * Show the features page.
     */
    public function features()
    {
        return view('public.features');
    }

    /**
     * Show the pricing page.
     */
    public function pricing()
    {
        $defaultFaq = [
            [
                'q' => 'Can I change plans later?',
                'a' => 'Yes, you can upgrade or downgrade your plan at any time. Changes will be prorated and reflected in your next billing cycle.',
            ],
            [
                'q' => 'What payment methods do you accept?',
                'a' => 'We accept all major credit cards, PayPal, and bank transfers for annual plans.',
            ],
            [
                'q' => 'Is there a free trial?',
                'a' => 'Yes, all plans include a 14-day free trial. No credit card required to start.',
            ],
            [
                'q' => 'What happens if I exceed my plan limits?',
                'a' => "We'll notify you when you're approaching your limits. You can upgrade your plan or purchase additional credits as needed.",
            ],
        ];

        $plans = Plan::query()
            ->with('customerGroup')
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('price')
            ->get();

        try {
            $faq = \App\Models\Setting::get('pricing_faq', $defaultFaq);
        } catch (\Throwable $e) {
            $faq = $defaultFaq;
        }
        $faq = is_array($faq) ? $faq : $defaultFaq;

        return view('public.pricing', compact('plans', 'faq'));
    }

    public function pricingCheckout(Request $request, string $slug): View|RedirectResponse
    {
        $plan = Plan::where('slug', $slug)->firstOrFail();
        abort_if(!$plan->is_public || !$plan->is_active, 404);

        if (auth('customer')->check()) {
            return view('public.pricing-checkout', compact('plan'));
        }

        $request->session()->put('url.intended', route('pricing.checkout', ['slug' => $plan->slug]));

        return redirect()->route('register');
    }
    public function showAiBuilderPage(Request $request, string $slug): View
    {
        $session = AiBuilderSession::where('page_slug', $slug)
            ->where('page_status', 'published')
            ->whereNotNull('preview_html')
            ->first();

        if (!$session) {
            abort(404);
        }

        return $this->renderAiBuilderPage($request, $session);
    }

    private function renderAiBuilderPage(Request $request, AiBuilderSession $session): View
    {
        $appMeta = is_array($session->app_meta) ? $session->app_meta : [];
        $visibility = is_string($appMeta['publish_visibility'] ?? null) ? $appMeta['publish_visibility'] : 'public';
        $password = is_string($appMeta['publish_password'] ?? null) ? $appMeta['publish_password'] : '';

        if ($visibility === 'private') {
            $customer = auth('customer')->user();
            if (!$customer || $customer->id !== $session->customer_id) {
                abort(403);
            }
        }

        $providedPassword = is_string($request->input('password')) ? trim((string) $request->input('password')) : '';
        $passwordRequired = false;
        $passwordError = false;
        if ($visibility === 'password') {
            $passwordValid = $password !== '' && $providedPassword !== '' && $providedPassword === $password;
            $passwordRequired = !$passwordValid;
            $passwordError = $providedPassword !== '' && !$passwordValid;
        }

        $supabaseIntegration = $session->page_id ? $session->page?->supabaseIntegration : null;
        if (! $supabaseIntegration || $supabaseIntegration->status !== 'connected') {
            $supabaseIntegration = $session->customer_id
                ? \App\Models\SupabaseIntegration::where('customer_id', $session->customer_id)
                    ->where('status', 'connected')
                    ->latest()
                    ->first()
                : null;
        }

        $supabaseCredentials = null;
        if ($supabaseIntegration && $supabaseIntegration->status === 'connected') {
            $appId = $session->page_id ? (string) $session->page_id : 'ai_' . $session->id;
            $supabaseCredentials = [
                'url' => $supabaseIntegration->project_url,
                'publishable_key' => $supabaseIntegration->publishable_key,
                'secret_key' => maskApiKey($supabaseIntegration->secret_key),
                'app_id' => $appId,
            ];
        }

        return view('public.ai-page', [
            'session' => $session,
            'content' => is_string($session->preview_html) ? $session->preview_html : '',
            'appMeta' => $appMeta,
            'pageTitle' => is_string($session->page_title) ? $session->page_title : '',
            'publishVisibility' => $visibility,
            'passwordRequired' => $passwordRequired,
            'passwordError' => $passwordError,
            'supabaseCredentials' => $supabaseCredentials,
        ]);
    }

    public function docs()
    {
        try {
            $docsEnabled = (bool) Setting::get('docs_enabled', true);
        } catch (\Throwable $e) {
            $docsEnabled = true;
        }

        if (!$docsEnabled) {
            abort(404);
        }

        return view('public.docs');
    }

    public function apiDocs()
    {
        return view('public.api-docs');
    }

    public function roadmap()
    {
        return view('public.roadmap');
    }

    private function renderPage(Request $request, Page $page): View
    {
        $isPreview = $request->boolean('preview') && Auth::guard('admin')->check();

        $renderer = app(PageBuilderRenderer::class);
        $builder = is_array($page->builder_data) ? $page->builder_data : [];
        $publishMeta = is_array($builder['publish_meta'] ?? null) ? $builder['publish_meta'] : [];
        $visibility = is_string($publishMeta['visibility'] ?? null) ? (string) $publishMeta['visibility'] : 'public';
        $passwordHash = is_string($publishMeta['password_hash'] ?? null) ? (string) $publishMeta['password_hash'] : '';

        $content = is_string($page->html_content) ? (string) $page->html_content : '';
        if ($renderer->isBuilderData($builder)) {
            $content = $renderer->render($builder);
        }

        if (!$isPreview && $visibility === 'private') {
            abort(403);
        }

        $providedPassword = is_string($request->input('password')) ? trim((string) $request->input('password')) : '';
        $passwordValid = $visibility !== 'password' || ($passwordHash !== '' && $providedPassword !== '' && Hash::check($providedPassword, $passwordHash));

        $supabaseIntegration = $page->supabaseIntegration;
        $supabaseCredentials = null;
        if ($supabaseIntegration && $supabaseIntegration->status === 'connected') {
            $supabaseCredentials = [
                'url' => $supabaseIntegration->project_url,
                'publishable_key' => $supabaseIntegration->publishable_key,
                'app_id' => (string) $page->id,
            ];
        }

        return view('public.page', [
            'page' => $page,
            'content' => $content,
            'isPreview' => $isPreview,
            'googleFontUrls' => $this->googleFontUrlsFromBuilder($builder),
            'publishVisibility' => $visibility,
            'passwordRequired' => !$isPreview && $visibility === 'password' && !$passwordValid,
            'passwordError' => !$isPreview && $visibility === 'password' && $providedPassword !== '' && !$passwordValid,
            'supabaseCredentials' => $supabaseCredentials,
        ]);
    }

    private function googleFontUrlsFromBuilder(array $builder): array
    {
        $fontNames = [];

        $pushFont = static function (mixed $value) use (&$fontNames): void {
            if (!is_string($value)) {
                return;
            }

            $raw = trim((string) $value);
            if ($raw === '') {
                return;
            }

            $name = null;
            if (preg_match('/["\']([^"\']+)["\']/', $raw, $m) === 1) {
                $name = trim((string) ($m[1] ?? ''));
            } else {
                $parts = explode(',', $raw);
                $name = trim((string) ($parts[0] ?? ''));
            }

            if ($name === '') {
                return;
            }

            $generic = [
                'serif',
                'sans-serif',
                'monospace',
                'cursive',
                'fantasy',
                'system-ui',
                '-apple-system',
                'ui-sans-serif',
                'ui-serif',
                'ui-monospace',
                'emoji',
                'math',
                'fangsong',
            ];

            if (in_array(strtolower($name), $generic, true)) {
                return;
            }

            $fontNames[$name] = true;
        };

        $walkWidgets = static function (array $widgets) use (&$walkWidgets, $pushFont): void {
            foreach ($widgets as $w) {
                if (!is_array($w)) {
                    continue;
                }

                $props = is_array($w['props'] ?? null) ? $w['props'] : [];
                foreach ($props as $key => $val) {
                    if (!is_string($key) || !str_ends_with(strtolower($key), 'fontfamily')) {
                        continue;
                    }
                    $pushFont($val);
                }

                $type = is_string($w['type'] ?? null) ? (string) $w['type'] : '';
                if ($type === 'grid') {
                    $columns = is_array($props['columns'] ?? null) ? $props['columns'] : [];
                    foreach ($columns as $col) {
                        $walkWidgets(is_array($col['widgets'] ?? null) ? $col['widgets'] : []);
                    }
                } elseif ($type === 'tabs') {
                    $items = is_array($props['items'] ?? null) ? $props['items'] : [];
                    foreach ($items as $item) {
                        $walkWidgets(is_array($item['widgets'] ?? null) ? $item['widgets'] : []);
                    }
                }
            }
        };

        $sections = is_array($builder['sections'] ?? null) ? $builder['sections'] : [];
        foreach ($sections as $section) {
            $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
            foreach ($columns as $column) {
                $walkWidgets(is_array($column['widgets'] ?? null) ? $column['widgets'] : []);
            }
        }

        return array_map(
            static fn (string $name): string => 'https://fonts.googleapis.com/css2?family=' . str_replace('%20', '+', rawurlencode($name)) . ':wght@100;200;300;400;500;600;700;800;900&display=swap',
            array_keys($fontNames)
        );
    }
}


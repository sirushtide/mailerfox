<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\AiBuilderTemplateController as AdminAiBuilderTemplateController;
use App\Http\Controllers\Admin\AiBuilderBackgroundPresetController as AdminAiBuilderBackgroundPresetController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\VisitorTrackingController as AdminVisitorTrackingController;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Customer\SearchController as CustomerSearchController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/storage/{path}', [\App\Http\Controllers\PublicStorageController::class, 'show'])
    ->where('path', '.*');

Route::get('/public/storage/{path}', [\App\Http\Controllers\PublicStorageController::class, 'show'])
    ->where('path', '.*');

Route::prefix('install')
    ->name('install.')
    ->middleware(\App\Http\Middleware\RedirectIfInstalled::class)
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\InstallController::class, 'welcome'])->name('welcome');
        Route::get('/requirements', [\App\Http\Controllers\InstallController::class, 'requirements'])->name('requirements');
        Route::get('/setup', [\App\Http\Controllers\InstallController::class, 'setup'])->name('setup');
        Route::post('/setup', [\App\Http\Controllers\InstallController::class, 'storeSetup'])->name('setup.store');
        Route::get('/done', [\App\Http\Controllers\InstallController::class, 'done'])->name('done');
    });

// Unified Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);

    Route::get('/register', [\App\Http\Controllers\AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

    Route::match(['GET', 'POST'], '/language', function (\Illuminate\Http\Request $request) {
        $locale = trim((string) $request->input('locale', ''));

        if ($locale === '') {
            return back();
        }

        $svc = app(\App\Translation\LocaleJsonService::class);

        if (!$svc->validateLocaleCode($locale) || !$svc->localeExists($locale)) {
            return back()->with('error', __('Invalid language.'));
        }

        try {
            $request->session()->put('locale', $locale);
        } catch (\Throwable $e) {
            //
        }

        return redirect()->to(url()->previous())->withCookie(cookie()->forever('locale', $locale));
    })->name('language.guest.update');
});

Route::middleware(['auth.any'])->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();

        if (\Illuminate\Support\Facades\Auth::guard('admin')->check()) {
            return redirect()->intended(route('admin.dashboard'))->with('success', 'Email verified successfully.');
        }

        return redirect()->intended(route('customer.dashboard'))->with('success', 'Email verified successfully.');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
        try {
            $request->user()->sendEmailVerificationNotification();
            return back()->with('success', 'Verification link sent.');
        } catch (\Throwable $e) {
            \Log::error('Failed to send verification email', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Email send failed.');
        }
    })->middleware(['throttle:6,1'])->name('verification.send');
});

Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::get('/cron/run', [\App\Http\Controllers\CronController::class, 'run'])
    ->middleware(['throttle:6,1'])
    ->name('cron.run');

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    Route::middleware(['auth:admin', 'user.active'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->middleware('admin.access:admin.dashboard')
            ->name('dashboard');

        Route::post('/language', function (\Illuminate\Http\Request $request) {
            $locale = trim((string) $request->input('locale', ''));

            if ($locale === '') {
                return back();
            }

            $svc = app(\App\Translation\LocaleJsonService::class);

            if (!$svc->validateLocaleCode($locale) || !$svc->localeExists($locale)) {
                return back()->with('error', __('Invalid language.'));
            }

            $user = $request->user('admin');
            if ($user) {
                $user->forceFill(['language' => $locale])->save();
            }

            return back();
        })->name('language.update');

        // Blog Posts
        Route::resource('blog-posts', AdminBlogPostController::class)
            ->middleware('admin.access:admin.blog_posts')
            ->except(['show']);
        Route::post('blog-posts/{blog_post}/publish', [AdminBlogPostController::class, 'publish'])
            ->middleware(['admin.access:admin.blog_posts.edit', 'demo.prevent'])
            ->name('blog-posts.publish');
        Route::post('blog-posts/{blog_post}/unpublish', [AdminBlogPostController::class, 'unpublish'])
            ->middleware(['admin.access:admin.blog_posts.edit', 'demo.prevent'])
            ->name('blog-posts.unpublish');

        // Pages
        Route::resource('pages', \App\Http\Controllers\Admin\PageController::class)
            ->middleware('admin.access:admin.pages')
            ->except(['show']);

        Route::get('pages/{page}/builder', [\App\Http\Controllers\Admin\PageController::class, 'builder'])
            ->middleware('admin.access:admin.pages.edit')
            ->name('pages.builder');

        Route::post('pages/{page}/builder/render-html', [\App\Http\Controllers\Admin\PageController::class, 'renderHtml'])
            ->middleware(['admin.access:admin.pages.edit'])
            ->name('pages.builder.render-html');

        Route::post('pages/{page}/builder/ai-section', [\App\Http\Controllers\Admin\PageController::class, 'aiSection'])
            ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
            ->name('pages.builder.ai-section');

        Route::post('pages/{page}/builder/ai/message', [\App\Http\Controllers\Admin\PageBuilderAiController::class, 'message'])
            ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
            ->name('pages.builder.ai.message');

        Route::get('pages/{page}/builder/ai/models', [\App\Http\Controllers\Admin\PageBuilderAiController::class, 'models'])
            ->middleware('admin.access:admin.pages.edit')
            ->name('pages.builder.ai.models');

        Route::get('pages/{page}/builder/ai-messages', [\App\Http\Controllers\Admin\PageController::class, 'aiMessages'])
            ->middleware(['admin.access:admin.pages.edit'])
            ->name('pages.builder.ai-messages');

        Route::delete('pages/{page}/builder/ai-messages', [\App\Http\Controllers\Admin\PageController::class, 'aiMessagesClear'])
            ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
            ->name('pages.builder.ai-messages.clear');

        Route::post('pages/{page}/builder/ai-generate', [\App\Http\Controllers\Admin\AiBuilderController::class, 'section'])
            ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
            ->name('pages.builder.ai-generate');

        Route::post('pages/{page}/builder/ai-iterate', [\App\Http\Controllers\Admin\AiBuilderController::class, 'iterate'])
            ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
            ->name('pages.builder.ai-iterate');

        Route::get('ai-status/{jobId}', [\App\Http\Controllers\Admin\AiBuilderController::class, 'status'])
            ->middleware(['admin.access:admin.pages.edit'])
            ->name('ai.status');

        // Page Builder Templates Proxy (avoids CORS by proxying to upstream gallery)
        Route::get('page-builder/templates/{resourceType}', [\App\Http\Controllers\Admin\PageBuilderTemplatesController::class, 'list'])
            ->where('resourceType', '.*')
            ->middleware('admin.access:admin.pages.edit')
            ->name('page-builder.templates.list');
        Route::post('page-builder/templates-fetch/{templateId}', [\App\Http\Controllers\Admin\PageBuilderTemplatesController::class, 'show'])
            ->whereNumber('templateId')
            ->middleware('admin.access:admin.pages.edit')
            ->name('page-builder.templates.show');

        Route::post('pages/unlayer/upload-image', [\App\Http\Controllers\Admin\PageController::class, 'uploadImage'])
            ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
            ->name('pages.unlayer.upload-image');

        // AI Projects
        Route::prefix('ai-projects')->name('ai-projects.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AiProjectsController::class, 'dashboard'])
                ->middleware('admin.access:admin.pages.access')
                ->name('dashboard');

            // AI Builder Section Templates
            Route::resource('ai-builder-templates', AdminAiBuilderTemplateController::class)
                ->middleware('admin.access:admin.pages.edit')
                ->names('ai-builder-templates');
            Route::get('ai-builder-templates-export', [AdminAiBuilderTemplateController::class, 'export'])
                ->middleware('admin.access:admin.pages.edit')
                ->name('ai-builder-templates.export');
            Route::post('ai-builder-templates-import', [AdminAiBuilderTemplateController::class, 'import'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('ai-builder-templates.import');
            Route::post('ai-builder-templates/activate-all', [AdminAiBuilderTemplateController::class, 'activateAll'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('ai-builder-templates.activate-all');
            Route::post('ai-builder-templates/deactivate-all', [AdminAiBuilderTemplateController::class, 'deactivateAll'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('ai-builder-templates.deactivate-all');

            // Page AI Builder
            Route::get('page-ai-builder', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'index'])
                ->middleware('admin.access:admin.pages.edit')
                ->name('page-ai-builder');

            Route::post('page-ai-builder/analyze', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'analyze'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.analyze');

            Route::post('page-ai-builder/suggestions', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'suggestions'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.suggestions');

            Route::post('page-ai-builder/generate', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'generate'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.generate');

            Route::post('page-ai-builder/progress', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'progress'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.progress');

            Route::post('page-ai-builder/stream', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'stream'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.stream');

            Route::post('page-ai-builder/cancel', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'cancel'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.cancel');

            Route::post('page-ai-builder/save', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'save'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.save');

            Route::post('page-ai-builder/custom-domain', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'updateCustomDomain'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.custom-domain');

            Route::post('page-ai-builder/session', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'storeSession'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.session.store');

            Route::put('page-ai-builder/session/{session}', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'updateSession'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.session.update');

            Route::post('page-ai-builder/session/{session}/compile', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'compileSandbox'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.sandbox.compile');

            Route::post('page-ai-builder/session/{session}/iterate', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'iterate'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.iterate');

            Route::post('page-ai-builder/session/{session}/discuss', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'discuss'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.discuss');

            Route::get('page-ai-builder/session/{session}/sandbox/{path?}', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'serveSandbox'])
                ->middleware('admin.access:admin.pages.edit')
                ->where('path', '.*')
                ->name('page-ai-builder.sandbox.preview');

            Route::get('page-ai-builder/{session}/export', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'export'])
                ->middleware('admin.access:admin.pages.edit')
                ->name('page-ai-builder.export');

            Route::get('page-ai-builder/starter-files', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'starterFiles'])
                ->middleware('admin.access:admin.pages.edit')
                ->name('page-ai-builder.starter-files');

            Route::get('page-ai-builder/{session}', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'show'])
                ->middleware('admin.access:admin.pages.edit')
                ->name('page-ai-builder.show');

            // Project sharing routes
            Route::get('page-ai-builder/{session}/shares', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'getShares'])
                ->middleware('admin.access:admin.pages.edit')
                ->name('page-ai-builder.shares.index');

            Route::post('page-ai-builder/{session}/shares', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'inviteCollaborator'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.shares.invite');

            Route::put('page-ai-builder/{session}/shares/{shareId}', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'updateCollaboratorRole'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.shares.update');

            Route::delete('page-ai-builder/{session}/shares/{shareId}', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'removeCollaborator'])
                ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
                ->name('page-ai-builder.shares.remove');

            Route::get('page-ai-builder/invite/{token}', [\App\Http\Controllers\Admin\PageAiBuilderController::class, 'acceptInvite'])
                ->name('page-ai-builder.accept-invite');
        });

        // AI Builder Background Presets
        Route::resource('ai-builder-background-presets', AdminAiBuilderBackgroundPresetController::class)
            ->middleware('admin.access:admin.pages.edit')
            ->names('ai-builder-background-presets');

        // App Supabase integration
        Route::get('apps/{page}/supabase', [\App\Http\Controllers\Admin\SupabaseIntegrationController::class, 'show'])
            ->middleware('admin.access:admin.pages.edit')
            ->name('apps.supabase.show');
        Route::post('apps/{page}/supabase', [\App\Http\Controllers\Admin\SupabaseIntegrationController::class, 'connect'])
            ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
            ->name('apps.supabase.connect');
        Route::delete('apps/{page}/supabase', [\App\Http\Controllers\Admin\SupabaseIntegrationController::class, 'disconnect'])
            ->middleware(['admin.access:admin.pages.edit', 'demo.prevent'])
            ->name('apps.supabase.disconnect');
        Route::get('apps/{page}/supabase/users', [\App\Http\Controllers\Admin\SupabaseIntegrationController::class, 'users'])
            ->middleware('admin.access:admin.pages.edit')
            ->name('apps.supabase.users');
        Route::get('apps/{page}/supabase/buckets', [\App\Http\Controllers\Admin\SupabaseIntegrationController::class, 'buckets'])
            ->middleware('admin.access:admin.pages.edit')
            ->name('apps.supabase.buckets');
        Route::get('apps/{page}/supabase/tables', [\App\Http\Controllers\Admin\SupabaseIntegrationController::class, 'tables'])
            ->middleware('admin.access:admin.pages.edit')
            ->name('apps.supabase.tables');
        Route::get('apps/{page}/supabase/table-data', [\App\Http\Controllers\Admin\SupabaseIntegrationController::class, 'tableData'])
            ->middleware('admin.access:admin.pages.edit')
            ->name('apps.supabase.table-data');
        Route::post('apps/{page}/supabase/auth-providers', [\App\Http\Controllers\Admin\SupabaseIntegrationController::class, 'updateAuthProviders'])
            ->middleware('admin.access:admin.pages.edit')
            ->name('apps.supabase.auth-providers');

        // Admin Supabase presets
        Route::resource('supabase-configs', \App\Http\Controllers\Admin\SupabaseConfigController::class)
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->except(['show', 'create', 'edit']);
        Route::post('supabase-configs/oauth-settings', [\App\Http\Controllers\Admin\SupabaseConfigController::class, 'storeOAuthSettings'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('supabase-configs.oauth-settings');
        Route::get('supabase-configs/list/json', [\App\Http\Controllers\Admin\SupabaseConfigController::class, 'list'])
            ->middleware('admin.access:admin.settings.edit')
            ->name('supabase-configs.list.json');
        Route::post('supabase-configs/{config}/default', [\App\Http\Controllers\Admin\SupabaseConfigController::class, 'setDefault'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('supabase-configs.default');

        Route::get('supabase/oauth/authorize', [\App\Http\Controllers\Admin\SupabaseOAuthController::class, 'startAuthorization'])
            ->middleware('admin.access:admin.settings.edit')
            ->name('supabase.oauth.authorize');
        Route::post('supabase/oauth/redirect', [\App\Http\Controllers\Admin\SupabaseOAuthController::class, 'redirect'])
            ->middleware('admin.access:admin.settings.edit')
            ->name('supabase.oauth.redirect');
        Route::get('supabase/oauth/callback', [\App\Http\Controllers\Admin\SupabaseOAuthController::class, 'callback'])
            ->name('supabase.oauth.callback');

        // Layouts
        Route::resource('layouts', \App\Http\Controllers\Admin\SiteLayoutController::class)
            ->middleware('admin.access:admin.pages')
            ->except(['show']);

        Route::get('layouts/{layout}/builder', [\App\Http\Controllers\Admin\SiteLayoutController::class, 'builder'])
            ->middleware('admin.access:admin.pages.edit')
            ->name('layouts.builder');

        Route::post('layouts/{layout}/builder/render-html', [\App\Http\Controllers\Admin\SiteLayoutController::class, 'renderHtml'])
            ->middleware(['admin.access:admin.pages.edit'])
            ->name('layouts.builder.render-html');

        // Menus
        Route::get('/menus', [\App\Http\Controllers\Admin\NavigationMenuController::class, 'index'])
            ->middleware('admin.access:admin.settings.access')
            ->name('menus.index');
        Route::get('/menus/create', [\App\Http\Controllers\Admin\NavigationMenuController::class, 'create'])
            ->middleware('admin.access:admin.settings.access')
            ->name('menus.create');
        Route::post('/menus', [\App\Http\Controllers\Admin\NavigationMenuController::class, 'store'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('menus.store');
        Route::get('/menus/{menu}/edit', [\App\Http\Controllers\Admin\NavigationMenuController::class, 'edit'])
            ->middleware('admin.access:admin.settings.access')
            ->name('menus.edit');
        Route::put('/menus/{menu}', [\App\Http\Controllers\Admin\NavigationMenuController::class, 'update'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('menus.update');
        Route::delete('/menus/{menu}', [\App\Http\Controllers\Admin\NavigationMenuController::class, 'destroy'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('menus.destroy');

        Route::prefix('homepages')->name('homepages.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\HomepageTextController::class, 'index'])
                ->middleware('admin.access:admin.settings.access')
                ->name('index');
            Route::get('/{variant}', [\App\Http\Controllers\Admin\HomepageTextController::class, 'edit'])
                ->middleware('admin.access:admin.settings.access')
                ->where('variant', '[1-4]')
                ->name('edit');
            Route::post('/{variant}', [\App\Http\Controllers\Admin\HomepageTextController::class, 'update'])
                ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
                ->where('variant', '[1-4]')
                ->name('update');
        });

        Route::prefix('site-pages')->name('site-pages.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SitePageController::class, 'index'])
                ->middleware('admin.access:admin.settings.access')
                ->name('index');

            Route::get('/features', [\App\Http\Controllers\Admin\SitePageController::class, 'editFeatures'])
                ->middleware('admin.access:admin.settings.access')
                ->name('features.edit');
            Route::post('/features', [\App\Http\Controllers\Admin\SitePageController::class, 'updateFeatures'])
                ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
                ->name('features.update');

            Route::get('/pricing', [\App\Http\Controllers\Admin\SitePageController::class, 'editPricing'])
                ->middleware('admin.access:admin.settings.access')
                ->name('pricing.edit');
            Route::post('/pricing', [\App\Http\Controllers\Admin\SitePageController::class, 'updatePricing'])
                ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
                ->name('pricing.update');

            Route::get('/login', [\App\Http\Controllers\Admin\SitePageController::class, 'editLogin'])
                ->middleware('admin.access:admin.settings.access')
                ->name('login.edit');
            Route::post('/login', [\App\Http\Controllers\Admin\SitePageController::class, 'updateLogin'])
                ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
                ->name('login.update');

            Route::get('/register', [\App\Http\Controllers\Admin\SitePageController::class, 'editRegister'])
                ->middleware('admin.access:admin.settings.access')
                ->name('register.edit');
            Route::post('/register', [\App\Http\Controllers\Admin\SitePageController::class, 'updateRegister'])
                ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
                ->name('register.update');
        });

        Route::get('/accessibility-control', [\App\Http\Controllers\Admin\AccessibilityControlController::class, 'index'])
            ->middleware('admin.access:admin.accessibility_control')
            ->name('accessibility-control.index');
        Route::get('/accessibility-control/create', [\App\Http\Controllers\Admin\AccessibilityControlController::class, 'create'])
            ->middleware('admin.access:admin.accessibility_control')
            ->name('accessibility-control.create');
        Route::post('/accessibility-control', [\App\Http\Controllers\Admin\AccessibilityControlController::class, 'update'])
            ->middleware(['admin.access:admin.accessibility_control', 'demo.prevent'])
            ->name('accessibility-control.update');

        Route::get('/activities', [AdminActivityController::class, 'index'])
            ->middleware('admin.access:admin.activities')
            ->name('activities.index');

        // Visitor Tracking
        Route::get('/visitor-tracking', [AdminVisitorTrackingController::class, 'index'])
            ->middleware('admin.access:admin.activities')
            ->name('visitor-tracking.index');
        Route::get('/visitor-tracking/{session}', [AdminVisitorTrackingController::class, 'show'])
            ->middleware('admin.access:admin.activities')
            ->name('visitor-tracking.show');
        Route::delete('/visitor-tracking/{session}', [AdminVisitorTrackingController::class, 'destroy'])
            ->middleware(['admin.access:admin.activities', 'demo.prevent'])
            ->name('visitor-tracking.destroy');
        Route::get('/visitor-tracking-summary', [AdminVisitorTrackingController::class, 'summary'])
            ->middleware('admin.access:admin.activities')
            ->name('visitor-tracking.summary');
        Route::get('/visitor-tracking-customer/{customerId}', [AdminVisitorTrackingController::class, 'customerJourney'])
            ->middleware('admin.access:admin.activities')
            ->name('visitor-tracking.customer');
        
        // Notifications
        Route::get('/notifications/feed', [\App\Http\Controllers\Admin\NotificationController::class, 'feed'])
            ->middleware('admin.access:admin.notifications.access')
            ->name('notifications.feed');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllRead'])
            ->middleware('admin.access:admin.notifications.edit')
            ->name('notifications.mark-all-read');
        
        // Invoices
        Route::get('/invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'index'])
            ->middleware('admin.access:admin.invoices')
            ->name('invoices.index');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'show'])
            ->middleware('admin.access:admin.invoices')
            ->name('invoices.show');

        // Manual Payments
        Route::get('/manual-payments', [\App\Http\Controllers\Admin\ManualPaymentController::class, 'index'])
            ->middleware('admin.access:admin.payment_methods.access')
            ->name('manual-payments.index');
        Route::get('/manual-payments/{manual_payment}', [\App\Http\Controllers\Admin\ManualPaymentController::class, 'show'])
            ->middleware('admin.access:admin.payment_methods.access')
            ->name('manual-payments.show');
        Route::post('/manual-payments/{manual_payment}/approve', [\App\Http\Controllers\Admin\ManualPaymentController::class, 'approve'])
            ->middleware(['admin.access:admin.payment_methods.edit', 'demo.prevent'])
            ->name('manual-payments.approve');
        Route::post('/manual-payments/{manual_payment}/reject', [\App\Http\Controllers\Admin\ManualPaymentController::class, 'reject'])
            ->middleware(['admin.access:admin.payment_methods.edit', 'demo.prevent'])
            ->name('manual-payments.reject');

        // Coupons
        Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class)->middleware('admin.access:admin.coupons')->except(['show']);

        // Public Templates
        Route::resource('public-template-categories', \App\Http\Controllers\Admin\PublicTemplateCategoryController::class)
            ->middleware('admin.access:admin.public_template_categories')
            ->except(['show']);
        Route::resource('public-templates', \App\Http\Controllers\Admin\PublicTemplateController::class)
            ->middleware('admin.access:admin.public_templates')
            ->except(['show']);
        Route::post('public-templates/ai-generate', [\App\Http\Controllers\Admin\PublicTemplateController::class, 'aiGenerate'])
            ->middleware(['admin.access:admin.public_templates.access', 'demo.prevent'])
            ->name('public-templates.ai-generate');
        Route::post('public-templates/render-builder', [\App\Http\Controllers\Admin\PublicTemplateController::class, 'renderBuilder'])
            ->middleware('admin.access:admin.public_templates.access')
            ->name('public-templates.render-builder');
        Route::post('public-templates/upload-image', [\App\Http\Controllers\Admin\PageController::class, 'uploadImage'])
            ->middleware(['admin.access:admin.public_templates.edit', 'demo.prevent'])
            ->name('public-templates.upload-image');
        Route::get('public-templates/gallery/{resourceType}', [\App\Http\Controllers\Admin\PageBuilderTemplatesController::class, 'list'])
            ->where('resourceType', '.*')
            ->middleware('admin.access:admin.public_templates.access')
            ->name('public-templates.gallery.list');
        Route::post('public-templates/gallery-fetch/{templateId}', [\App\Http\Controllers\Admin\PageBuilderTemplatesController::class, 'show'])
            ->whereNumber('templateId')
            ->middleware('admin.access:admin.public_templates.access')
            ->name('public-templates.gallery.fetch');

        // Built-in Templates (File Gallery)
        Route::get('built-in-templates/{builtInTemplateSetting}/edit', [\App\Http\Controllers\Admin\BuiltInTemplateSettingController::class, 'edit'])
            ->middleware('admin.access:admin.public_templates.edit')
            ->name('built-in-templates.edit');
        Route::put('built-in-templates/{builtInTemplateSetting}', [\App\Http\Controllers\Admin\BuiltInTemplateSettingController::class, 'update'])
            ->middleware('admin.access:admin.public_templates.edit')
            ->name('built-in-templates.update');

        // Template Import (Admin)
        Route::get('templates/import/gallery', [\App\Http\Controllers\Admin\TemplateImportController::class, 'importGallery'])
            ->middleware('admin.access:admin.public_templates.access')
            ->name('templates.import.gallery');
        Route::get('templates/import/file/{key}/content', [\App\Http\Controllers\Admin\TemplateImportController::class, 'importFileContent'])
            ->middleware('admin.access:admin.public_templates.access')
            ->name('templates.import.file.content');
        Route::get('templates/import/public/{publicTemplate}/content', [\App\Http\Controllers\Admin\TemplateImportController::class, 'importPublicContent'])
            ->middleware('admin.access:admin.public_templates.access')
            ->name('templates.import.public.content');
        
        // Plans
        Route::get('plans/export', [\App\Http\Controllers\Admin\PlanController::class, 'export'])
            ->middleware('admin.access:admin.plans')
            ->name('plans.export');
        Route::post('plans/import', [\App\Http\Controllers\Admin\PlanController::class, 'import'])
            ->middleware('admin.access:admin.plans')
            ->name('plans.import');
        Route::post('plans/import-defaults', [\App\Http\Controllers\Admin\PlanController::class, 'importDefaults'])
            ->middleware('admin.access:admin.plans')
            ->name('plans.import-defaults');
        Route::resource('plans', \App\Http\Controllers\Admin\PlanController::class)->middleware('admin.access:admin.plans');
        Route::post('plans/pricing-settings', [\App\Http\Controllers\Admin\PlanController::class, 'updatePricingSettings'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('plans.pricing-settings.update');

        // Users
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->middleware('admin.access:admin.users')->parameters([
            'users' => 'user',
        ]);

        // Roles
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class)
            ->middleware('admin.access:admin.roles')
            ->parameters(['roles' => 'role']);
        
        // Customers
        Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)->middleware('admin.access:admin.customers')->parameters([
            'customers' => 'customer'
        ]);
        Route::patch('customers/{customer}/email-verification', [\App\Http\Controllers\Admin\CustomerController::class, 'updateEmailVerification'])
            ->middleware('admin.access:admin.customers.edit')
            ->name('customers.email-verification.update');
        Route::post('customers/{customer}/impersonate', [\App\Http\Controllers\Admin\CustomerController::class, 'impersonate'])
            ->middleware('admin.access:admin.customers.edit')
            ->name('customers.impersonate');
        
        // Customer Groups
        Route::resource('customer-groups', \App\Http\Controllers\Admin\CustomerGroupController::class)->middleware('admin.access:admin.customer_groups')->parameters([
            'customer-groups' => 'customer_group'
        ]);

        // Inline image upload (used by the admin rich-text editor)
        Route::post('uploads/inline-image', [\App\Http\Controllers\Admin\InlineImageController::class, 'store'])
            ->name('uploads.inline-image');
        
        // Settings
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])
            ->middleware('admin.access:admin.settings.access')
            ->name('settings.index');
        Route::get('/settings/logs', [\App\Http\Controllers\Admin\SettingController::class, 'logs'])
            ->middleware('admin.access:admin.settings.access')
            ->name('settings.logs');
        Route::post('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.update');

        Route::post('/settings/templates/activate/{template}', [\App\Http\Controllers\Admin\SettingController::class, 'activateTemplate'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.templates.activate');
        Route::get('/settings/templates/{template}/edit', [\App\Http\Controllers\Admin\SettingController::class, 'editTemplate'])
            ->middleware('admin.access:admin.settings.access')
            ->name('settings.templates.edit');
        Route::get('/settings/templates/{template}/values', [\App\Http\Controllers\Admin\SettingController::class, 'templateValues'])
            ->middleware('admin.access:admin.settings.access')
            ->name('settings.templates.values');
        Route::post('/settings/templates/{template}/import', [\App\Http\Controllers\Admin\SettingController::class, 'importTemplateToPage'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.templates.import');
        Route::post('/settings/templates/{template}', [\App\Http\Controllers\Admin\SettingController::class, 'updateTemplate'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.templates.update');
        Route::get('/settings/templates/{template}/preview', [\App\Http\Controllers\Admin\SettingController::class, 'previewTemplate'])
            ->middleware('admin.access:admin.settings.access')
            ->name('settings.templates.preview');

        Route::post('/settings/templates/external/sync', [\App\Http\Controllers\Admin\SettingController::class, 'syncExternalTemplates'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.templates.external.sync');
        Route::post('/settings/templates/external/{externalId}/fetch-json', [\App\Http\Controllers\Admin\SettingController::class, 'fetchExternalTemplateJson'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.templates.external.fetch-json');
        Route::post('/settings/templates/external-license/activate', [\App\Http\Controllers\Admin\SettingController::class, 'activateExternalTemplatesLicense'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.templates.external-license.activate');
        Route::post('/settings/templates/external-license/deactivate', [\App\Http\Controllers\Admin\SettingController::class, 'deactivateExternalTemplatesLicense'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.templates.external-license.deactivate');
        Route::post('/settings/update-download', [\App\Http\Controllers\Admin\SettingController::class, 'requestUpdateDownload'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.update-download');
        Route::post('/settings/install-update', [\App\Http\Controllers\Admin\SettingController::class, 'installUpdate'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.install-update');
        Route::post('/settings/cancel-update', [\App\Http\Controllers\Admin\SettingController::class, 'cancelUpdate'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.cancel-update');
        Route::post('/settings/clear-cache', [\App\Http\Controllers\Admin\SettingController::class, 'clearApplicationCache'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.clear-cache');
        Route::post('/settings/license-activate', [\App\Http\Controllers\Admin\SettingController::class, 'activateLicense'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.license-activate');
        Route::post('/settings/license-deactivate', [\App\Http\Controllers\Admin\SettingController::class, 'deactivateLicense'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.license-deactivate');

        Route::post('/settings/cron/regenerate-token', [\App\Http\Controllers\Admin\SettingController::class, 'regenerateCronToken'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.cron.regenerate-token');
        Route::post('/settings/cron/run-now', [\App\Http\Controllers\Admin\SettingController::class, 'runCronNow'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('settings.cron.run-now');

        Route::get('/settings/secret/{key}', [\App\Http\Controllers\Admin\SettingController::class, 'revealSecret'])
            ->middleware('admin.access:admin.settings.access')
            ->name('settings.secret');

        // Integrations
        Route::get('/integrations', [\App\Http\Controllers\Admin\IntegrationController::class, 'index'])
            ->middleware('admin.access:admin.delivery_servers')
            ->name('integrations.index');

        Route::post('/integrations/google', [\App\Http\Controllers\Admin\IntegrationController::class, 'storeGoogle'])
            ->middleware(['admin.access:admin.delivery_servers', 'demo.prevent'])
            ->name('integrations.google.store');
        Route::get('/integrations/google/secret', [\App\Http\Controllers\Admin\IntegrationController::class, 'revealGoogleSecret'])
            ->middleware('admin.access:admin.delivery_servers')
            ->name('integrations.google.secret');

        Route::post('/integrations/wordpress', [\App\Http\Controllers\Admin\IntegrationController::class, 'storeWordpress'])
            ->middleware(['admin.access:admin.delivery_servers', 'demo.prevent'])
            ->name('integrations.wordpress.store');
        Route::get('/integrations/wordpress/secret', [\App\Http\Controllers\Admin\IntegrationController::class, 'revealWordpressSecret'])
            ->middleware('admin.access:admin.delivery_servers')
            ->name('integrations.wordpress.secret');

        Route::get('/integrations/wordpress/plugin', [\App\Http\Controllers\Admin\IntegrationController::class, 'downloadWordpressPlugin'])
            ->name('integrations.wordpress.plugin');

        // Addons
        Route::prefix('addons')
            ->name('addons.')
            ->middleware('admin.access:admin.addons.access')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\AddonController::class, 'index'])->name('index');
                Route::post('/upload', [\App\Http\Controllers\Admin\AddonController::class, 'upload'])
                    ->middleware(['admin.access:admin.addons.create', 'demo.prevent'])
                    ->name('upload');
                Route::post('/remote-install', [\App\Http\Controllers\Admin\AddonController::class, 'remoteInstall'])
                    ->middleware(['admin.access:admin.addons.create', 'demo.prevent'])
                    ->name('remote-install');
                Route::post('/{addon}/install-update', [\App\Http\Controllers\Admin\AddonController::class, 'installUpdate'])
                    ->middleware(['admin.access:admin.addons.edit', 'demo.prevent'])
                    ->name('install-update');
                Route::post('/{addon}/activate', [\App\Http\Controllers\Admin\AddonController::class, 'activate'])
                    ->middleware(['admin.access:admin.addons.edit', 'demo.prevent'])
                    ->name('activate');
                Route::post('/{addon}/deactivate', [\App\Http\Controllers\Admin\AddonController::class, 'deactivate'])
                    ->middleware(['admin.access:admin.addons.edit', 'demo.prevent'])
                    ->name('deactivate');
                Route::delete('/{addon}', [\App\Http\Controllers\Admin\AddonController::class, 'uninstall'])
                    ->middleware(['admin.access:admin.addons.delete', 'demo.prevent'])
                    ->name('uninstall');
            });

        // SMS addon settings
        Route::get('/settings/sms', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'edit'])->name('sms.settings');
        Route::put('/settings/sms', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'update'])
            ->middleware(['demo.prevent'])
            ->name('sms.settings.update');

        // WhatsApp addon settings
        Route::get('/settings/whatsapp', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'edit'])->name('whatsapp.settings');
        Route::put('/settings/whatsapp', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'update'])
            ->middleware(['demo.prevent'])
            ->name('whatsapp.settings.update');

        // AI Assistant addon settings
        Route::get('/settings/ai-assistant', [\App\Http\Controllers\Admin\AiAssistantSettingsController::class, 'edit'])->name('ai-assistant.settings');
        Route::put('/settings/ai-assistant', [\App\Http\Controllers\Admin\AiAssistantSettingsController::class, 'update'])
            ->middleware(['demo.prevent'])
            ->name('ai-assistant.settings.update');

        // AI Tools
        Route::prefix('ai-tools')
            ->name('ai-tools.')
            ->middleware('admin.access:admin.ai_tools.access')
            ->group(function () {
                Route::get('/dashboard', [\App\Http\Controllers\Admin\AiToolController::class, 'dashboard'])->name('dashboard');
                Route::put('/api-keys', [\App\Http\Controllers\Admin\AiToolController::class, 'updateApiKeys'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('api-keys.update');
                Route::put('/provider-controls', [\App\Http\Controllers\Admin\AiToolController::class, 'updateProviderControls'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('provider-controls.update');
                Route::put('/pricing', [\App\Http\Controllers\Admin\AiToolController::class, 'updatePricing'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('pricing.update');
                Route::get('/email-text-generator', [\App\Http\Controllers\Admin\AiToolController::class, 'emailTextGenerator'])->name('email-text-generator');
                Route::post('/email-text-generator', [\App\Http\Controllers\Admin\AiToolController::class, 'generateEmailText'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('email-text-generator.generate');
                Route::put('/builder-defaults', [\App\Http\Controllers\Admin\AiToolController::class, 'updateBuilderDefaults'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('builder-defaults.update');
                Route::put('/suggestions', [\App\Http\Controllers\Admin\AiToolController::class, 'updateSuggestions'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('suggestions.update');
                Route::put('/page-generation-mode', [\App\Http\Controllers\Admin\AiToolController::class, 'updatePageGenerationMode'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('page-generation-mode.update');
                Route::put('/rag-settings', [\App\Http\Controllers\Admin\AiToolController::class, 'updateRagSettings'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('rag-settings.update');
                Route::get('/custom-models/search', [\App\Http\Controllers\Admin\AiToolController::class, 'searchExternalModels'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('custom-models.search');
                Route::post('/custom-models', [\App\Http\Controllers\Admin\AiToolController::class, 'addCustomModel'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('custom-models.store');
                Route::delete('/custom-models', [\App\Http\Controllers\Admin\AiToolController::class, 'removeCustomModel'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('custom-models.destroy');
                Route::get('/custom-models', [\App\Http\Controllers\Admin\AiToolController::class, 'listCustomModels'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('custom-models.index');
            });

        // RAG — Knowledge Base & AI Chat
        Route::prefix('rag')
            ->name('rag.')
            ->middleware('admin.access:admin.ai_tools.access')
            ->group(function () {
                Route::get('/knowledge', [\App\Http\Controllers\Admin\RagController::class, 'knowledgeIndex'])->name('knowledge.index');
                Route::get('/knowledge/create', [\App\Http\Controllers\Admin\RagController::class, 'knowledgeCreate'])->name('knowledge.create');
                Route::post('/knowledge', [\App\Http\Controllers\Admin\RagController::class, 'knowledgeStore'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('knowledge.store');
                Route::delete('/knowledge/{knowledgeBase}', [\App\Http\Controllers\Admin\RagController::class, 'knowledgeDestroy'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('knowledge.destroy');

                Route::get('/chat', [\App\Http\Controllers\Admin\RagController::class, 'chatIndex'])->name('chat.index');
                Route::post('/chat/ask', [\App\Http\Controllers\Admin\RagController::class, 'chatAsk'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('chat.ask');
                Route::post('/chat/feedback', [\App\Http\Controllers\Admin\RagController::class, 'chatFeedback'])
                    ->middleware(['admin.access:admin.ai_tools.create', 'demo.prevent'])
                    ->name('chat.feedback');
            });

        // API
        Route::get('/api', [\App\Http\Controllers\Admin\ApiTokenController::class, 'index'])
            ->middleware('admin.access:admin.api.access')
            ->name('api.index');
        Route::post('/api', [\App\Http\Controllers\Admin\ApiTokenController::class, 'store'])
            ->middleware(['admin.access:admin.api.create', 'demo.prevent'])
            ->name('api.store');
        Route::delete('/api/{tokenId}', [\App\Http\Controllers\Admin\ApiTokenController::class, 'destroy'])
            ->middleware(['admin.access:admin.api.delete', 'demo.prevent'])
            ->name('api.destroy');

        // Support Tickets
        Route::get('/support-tickets', [\App\Http\Controllers\Admin\SupportTicketController::class, 'index'])
            ->middleware('admin.access:admin.support_tickets.access')
            ->name('support-tickets.index');
        Route::get('/support-tickets/{support_ticket}', [\App\Http\Controllers\Admin\SupportTicketController::class, 'show'])
            ->middleware('admin.access:admin.support_tickets.access')
            ->name('support-tickets.show');
        Route::get('/support-tickets/{support_ticket}/drawer', [\App\Http\Controllers\Admin\SupportTicketController::class, 'drawer'])
            ->middleware('admin.access:admin.support_tickets.access')
            ->name('support-tickets.drawer');
        Route::post('/support-tickets/{support_ticket}/reply', [\App\Http\Controllers\Admin\SupportTicketController::class, 'reply'])
            ->middleware(['admin.access:admin.support_tickets.edit', 'demo.prevent'])
            ->name('support-tickets.reply');
        Route::post('/support-tickets/{support_ticket}/status', [\App\Http\Controllers\Admin\SupportTicketController::class, 'setStatus'])
            ->middleware(['admin.access:admin.support_tickets.edit', 'demo.prevent'])
            ->name('support-tickets.status');
        Route::post('/support-tickets/{support_ticket}/priority', [\App\Http\Controllers\Admin\SupportTicketController::class, 'setPriority'])
            ->middleware(['admin.access:admin.support_tickets.edit', 'demo.prevent'])
            ->name('support-tickets.priority');

        // Translations (file-based JSON)
        Route::prefix('translations')
            ->name('translations.')
            ->middleware('admin.access:admin.translations')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\TranslationJsonLocaleController::class, 'index'])
                    ->name('locales.index');

                Route::get('/download-main', [\App\Http\Controllers\Admin\TranslationJsonLocaleController::class, 'downloadMain'])
                    ->middleware('admin.access:admin.translations.edit')
                    ->name('locales.download_main');

                Route::get('/{locale}/download', [\App\Http\Controllers\Admin\TranslationJsonLocaleController::class, 'downloadLocale'])
                    ->middleware('admin.access:admin.translations.edit')
                    ->name('locales.download');

                Route::get('/{locale}/edit', [\App\Http\Controllers\Admin\TranslationJsonLocaleController::class, 'edit'])
                    ->middleware('admin.access:admin.translations.edit')
                    ->name('locales.edit');

                Route::put('/{locale}', [\App\Http\Controllers\Admin\TranslationJsonLocaleController::class, 'update'])
                    ->middleware(['admin.access:admin.translations.edit', 'demo.prevent'])
                    ->name('locales.update');

                Route::post('/{locale}/active', [\App\Http\Controllers\Admin\TranslationJsonLocaleController::class, 'setActive'])
                    ->middleware(['admin.access:admin.translations.edit', 'demo.prevent'])
                    ->name('locales.active');

                Route::post('/upload', [\App\Http\Controllers\Admin\TranslationJsonLocaleController::class, 'upload'])
                    ->middleware(['admin.access:admin.translations.create', 'demo.prevent'])
                    ->name('locales.upload');

                Route::get('/{locale}/bulk', [\App\Http\Controllers\Admin\TranslationJsonBulkController::class, 'edit'])
                    ->middleware('admin.access:admin.translations.edit')
                    ->name('bulk.edit');

                Route::post('/{locale}/bulk', [\App\Http\Controllers\Admin\TranslationJsonBulkController::class, 'update'])
                    ->middleware(['admin.access:admin.translations.edit', 'demo.prevent'])
                    ->name('bulk.update');
            });
        
        // Campaigns
        Route::resource('campaigns', \App\Http\Controllers\Admin\CampaignController::class)->middleware('admin.access:admin.campaigns');
        
        // Email Lists
        Route::resource('lists', \App\Http\Controllers\Admin\EmailListController::class)->middleware('admin.access:admin.lists')->parameters([
            'lists' => 'list'
        ]);
        
        // Email Validation
        Route::get('email-validation', [\App\Http\Controllers\Admin\EmailValidationController::class, 'index'])
            ->middleware('admin.access:admin.email_validation')
            ->name('email-validation.index');

        Route::prefix('email-validation')
            ->name('email-validation.')
            ->middleware('admin.access:admin.email_validation')
            ->group(function () {
                Route::get('tools/create', [\App\Http\Controllers\Admin\EmailValidationToolController::class, 'create'])
                    ->name('tools.create');
                Route::post('tools', [\App\Http\Controllers\Admin\EmailValidationToolController::class, 'store'])
                    ->name('tools.store');
                Route::get('tools/{tool}/edit', [\App\Http\Controllers\Admin\EmailValidationToolController::class, 'edit'])
                    ->name('tools.edit');
                Route::put('tools/{tool}', [\App\Http\Controllers\Admin\EmailValidationToolController::class, 'update'])
                    ->name('tools.update');
                Route::delete('tools/{tool}', [\App\Http\Controllers\Admin\EmailValidationToolController::class, 'destroy'])
                    ->name('tools.destroy');
            });
        
        // Delivery Servers
        Route::resource('delivery-servers', \App\Http\Controllers\Admin\DeliveryServerController::class)->middleware('admin.access:admin.delivery_servers')->parameters([
            'delivery-servers' => 'delivery_server'
        ]);

        Route::get('delivery-servers/{delivery_server}/secret', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'revealSecret'])
            ->middleware('admin.access:admin.delivery_servers')
            ->name('delivery-servers.secret');
        Route::post('delivery-servers/{delivery_server}/make-primary', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'makePrimary'])
            ->middleware(['admin.access:admin.delivery_servers.make_primary', 'demo.prevent'])
            ->name('delivery-servers.make-primary');

        Route::post('delivery-servers/{delivery_server}/clock-skew-check', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'clockSkewCheck'])
            ->middleware(['admin.access:admin.delivery_servers', 'demo.prevent'])
            ->name('delivery-servers.clock-skew-check');

        Route::post('delivery-servers/{delivery_server}/restart-workers', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'restartWorkers'])
            ->middleware(['admin.access:admin.delivery_servers', 'demo.prevent'])
            ->name('delivery-servers.restart-workers');
        Route::post('delivery-servers/{delivery_server}/test-email', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'sendTestEmail'])
            ->middleware(['admin.access:admin.delivery_servers.test', 'demo.prevent'])
            ->name('delivery-servers.test-email');
        Route::get('delivery-servers/test/connection', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'showTest'])
            ->middleware('admin.access:admin.delivery_servers.test')
            ->name('delivery-servers.test');
        Route::post('delivery-servers/test/connection', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'test'])
            ->middleware(['admin.access:admin.delivery_servers.test', 'demo.prevent'])
            ->name('delivery-servers.test.send');
        Route::get('delivery-servers/{delivery_server}/verify/{token}', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'verify'])
            ->middleware('admin.access:admin.delivery_servers.access')
            ->name('delivery-servers.verify');
        Route::post('delivery-servers/{delivery_server}/resend-verification', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'resendVerification'])
            ->middleware(['admin.access:admin.delivery_servers.resend_verification', 'demo.prevent'])
            ->name('delivery-servers.resend-verification');
        Route::post('delivery-servers/fetch-sendgrid-domains', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'fetchSendGridDomains'])
            ->middleware(['admin.access:admin.delivery_servers', 'demo.prevent'])
            ->name('delivery-servers.fetch-sendgrid-domains');
        Route::post('delivery-servers/fetch-emailit-domains', [\App\Http\Controllers\Admin\DeliveryServerController::class, 'fetchEmailitDomains'])
            ->middleware(['admin.access:admin.delivery_servers', 'demo.prevent'])
            ->name('delivery-servers.fetch-emailit-domains');

        // Sending Domains
        Route::get('sending-domains', [\App\Http\Controllers\Admin\SendingDomainController::class, 'index'])
            ->middleware('admin.access:admin.sending_domains')
            ->name('sending-domains.index');
        Route::get('sending-domains/create', [\App\Http\Controllers\Admin\SendingDomainController::class, 'create'])
            ->middleware('admin.access:admin.sending_domains')
            ->name('sending-domains.create');
        Route::post('sending-domains', [\App\Http\Controllers\Admin\SendingDomainController::class, 'store'])
            ->middleware('admin.access:admin.sending_domains')
            ->name('sending-domains.store');
        Route::get('sending-domains/{sending_domain}/edit', [\App\Http\Controllers\Admin\SendingDomainController::class, 'edit'])
            ->middleware('admin.access:admin.sending_domains')
            ->name('sending-domains.edit');
        Route::put('sending-domains/{sending_domain}', [\App\Http\Controllers\Admin\SendingDomainController::class, 'update'])
            ->middleware('admin.access:admin.sending_domains')
            ->name('sending-domains.update');
        Route::delete('sending-domains/{sending_domain}', [\App\Http\Controllers\Admin\SendingDomainController::class, 'destroy'])
            ->middleware('admin.access:admin.sending_domains')
            ->name('sending-domains.destroy');
        Route::post('sending-domains/{sending_domain}/make-primary', [\App\Http\Controllers\Admin\SendingDomainController::class, 'makePrimary'])
            ->middleware('admin.access:admin.sending_domains.edit')
            ->name('sending-domains.make-primary');
        Route::get('sending-domains/{sending_domain}', [\App\Http\Controllers\Admin\SendingDomainController::class, 'show'])
            ->middleware('admin.access:admin.sending_domains')
            ->name('sending-domains.show');
        Route::post('sending-domains/{sending_domain}/verify', [\App\Http\Controllers\Admin\SendingDomainController::class, 'verify'])
            ->middleware('admin.access:admin.sending_domains.edit')
            ->name('sending-domains.verify');
        Route::post('sending-domains/{sending_domain}/mark-verified', [\App\Http\Controllers\Admin\SendingDomainController::class, 'markVerified'])
            ->middleware('admin.access:admin.sending_domains.edit')
            ->name('sending-domains.mark-verified');

        // Tracking Domains
        Route::get('tracking-domains', [\App\Http\Controllers\Admin\TrackingDomainController::class, 'index'])
            ->middleware('admin.access:admin.tracking_domains')
            ->name('tracking-domains.index');
        Route::get('tracking-domains/create', [\App\Http\Controllers\Admin\TrackingDomainController::class, 'create'])
            ->middleware('admin.access:admin.tracking_domains')
            ->name('tracking-domains.create');
        Route::post('tracking-domains', [\App\Http\Controllers\Admin\TrackingDomainController::class, 'store'])
            ->middleware('admin.access:admin.tracking_domains')
            ->name('tracking-domains.store');
        Route::get('tracking-domains/{tracking_domain}/edit', [\App\Http\Controllers\Admin\TrackingDomainController::class, 'edit'])
            ->middleware('admin.access:admin.tracking_domains')
            ->name('tracking-domains.edit');
        Route::put('tracking-domains/{tracking_domain}', [\App\Http\Controllers\Admin\TrackingDomainController::class, 'update'])
            ->middleware('admin.access:admin.tracking_domains')
            ->name('tracking-domains.update');
        Route::delete('tracking-domains/{tracking_domain}', [\App\Http\Controllers\Admin\TrackingDomainController::class, 'destroy'])
            ->middleware('admin.access:admin.tracking_domains')
            ->name('tracking-domains.destroy');
        Route::get('tracking-domains/{tracking_domain}', [\App\Http\Controllers\Admin\TrackingDomainController::class, 'show'])
            ->middleware('admin.access:admin.tracking_domains')
            ->name('tracking-domains.show');
        Route::post('tracking-domains/{tracking_domain}/verify', [\App\Http\Controllers\Admin\TrackingDomainController::class, 'verify'])
            ->middleware('admin.access:admin.tracking_domains.edit')
            ->name('tracking-domains.verify');
        Route::post('tracking-domains/{tracking_domain}/mark-verified', [\App\Http\Controllers\Admin\TrackingDomainController::class, 'markVerified'])
            ->middleware('admin.access:admin.tracking_domains.edit')
            ->name('tracking-domains.mark-verified');

        // Bounce Servers
        Route::resource('bounce-servers', \App\Http\Controllers\Admin\BounceServerController::class)->middleware('admin.access:admin.bounce_servers')->parameters([
            'bounce-servers' => 'bounce_server'
        ]);

        // Reply Servers
        Route::resource('reply-servers', \App\Http\Controllers\Admin\ReplyServerController::class)->middleware('admin.access:admin.reply_servers')->parameters([
            'reply-servers' => 'reply_server'
        ]);

        // Bounced Emails
        Route::get('bounced-emails', [\App\Http\Controllers\Admin\BouncedEmailController::class, 'index'])
            ->middleware('admin.access:admin.bounced_emails')
            ->name('bounced-emails.index');

        Route::get('bounced-emails/{bounced_email}', [\App\Http\Controllers\Admin\BouncedEmailController::class, 'show'])
            ->middleware('admin.access:admin.bounced_emails')
            ->name('bounced-emails.show');

        // Profile
        Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])
            ->middleware('admin.access:admin.profile')
            ->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])
            ->middleware('admin.access:admin.profile')
            ->name('profile.update');

        // Search
        Route::get('/search/suggest', [\App\Http\Controllers\Admin\SearchController::class, 'suggest'])
            ->middleware('admin.access:admin.search.access')
            ->name('search.suggest');
        Route::get('/search', [\App\Http\Controllers\Admin\SearchController::class, 'index'])
            ->middleware('admin.access:admin.search.access')
            ->name('search.index');

        // Payment Methods
        Route::get('/payment-methods', [\App\Http\Controllers\Admin\PaymentMethodController::class, 'index'])
            ->middleware('admin.access:admin.payment_methods.access')
            ->name('payment-methods.index');
        Route::post('/payment-methods', [\App\Http\Controllers\Admin\PaymentMethodController::class, 'update'])
            ->middleware('admin.access:admin.payment_methods.edit')
            ->name('payment-methods.update');

        // Vat/Tax
        Route::get('/vat-tax', [\App\Http\Controllers\Admin\VatTaxController::class, 'index'])
            ->middleware('admin.access:admin.vat_tax.access')
            ->name('vat-tax.index');
        Route::post('/vat-tax', [\App\Http\Controllers\Admin\VatTaxController::class, 'update'])
            ->middleware('admin.access:admin.vat_tax.edit')
            ->name('vat-tax.update');

        // Affiliates
        Route::get('/affiliates', [\App\Http\Controllers\Admin\AffiliateController::class, 'index'])
            ->middleware('admin.access:admin.settings.access')
            ->name('affiliates.index');

        Route::get('/affiliates/referrals', [\App\Http\Controllers\Admin\AffiliateController::class, 'referrals'])
            ->middleware('admin.access:admin.settings.access')
            ->name('affiliates.referrals');

        Route::get('/affiliates/commissions', [\App\Http\Controllers\Admin\AffiliateController::class, 'commissions'])
            ->middleware('admin.access:admin.settings.access')
            ->name('affiliates.commissions');

        Route::get('/affiliates/payouts', [\App\Http\Controllers\Admin\AffiliateController::class, 'payouts'])
            ->middleware('admin.access:admin.settings.access')
            ->name('affiliates.payouts');

        Route::get('/affiliates/settings', [\App\Http\Controllers\Admin\AffiliateController::class, 'settings'])
            ->middleware('admin.access:admin.settings.access')
            ->name('affiliates.settings');

        Route::post('/affiliates/settings', [\App\Http\Controllers\Admin\AffiliateController::class, 'updateSettings'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('affiliates.settings.update');

        Route::get('/affiliates/create', [\App\Http\Controllers\Admin\AffiliateController::class, 'create'])
            ->middleware('admin.access:admin.settings.edit')
            ->name('affiliates.create');

        Route::post('/affiliates', [\App\Http\Controllers\Admin\AffiliateController::class, 'store'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('affiliates.store');

        Route::post('/affiliates/{affiliate}/approve', [\App\Http\Controllers\Admin\AffiliateController::class, 'approve'])
            ->middleware(['admin.access:admin.settings.edit', 'demo.prevent'])
            ->name('affiliates.approve');
    });
});

Route::get('/billing/flutterwave/callback', \App\Http\Controllers\Billing\FlutterwaveCallbackController::class)
    ->name('billing.flutterwave.callback')
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

Route::match(['GET', 'POST'], '/billing/uddoktapay/callback', \App\Http\Controllers\Billing\UddoktaPayCallbackController::class)
    ->name('billing.uddoktapay.callback')
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

Route::get('/billing/razorpay/callback', \App\Http\Controllers\Billing\RazorpayCallbackController::class)
    ->name('billing.razorpay.callback')
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

Route::get('/billing/paypal/callback', \App\Http\Controllers\Billing\PayPalCallbackController::class)
    ->name('billing.paypal.callback')
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/auth/google', [\App\Http\Controllers\Admin\GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
        Route::get('/auth/google/callback', [\App\Http\Controllers\Admin\GoogleAuthController::class, 'callback'])->name('auth.google.callback');
    });

    Route::middleware(['auth:admin', 'user.active'])->group(function () {
        // SuperScrape Addon Settings
        Route::get('/super-scrape/settings', [\App\Http\Controllers\Admin\SuperScrapeSettingsController::class, 'edit'])
            ->name('super-scrape.settings');
        Route::put('/super-scrape/settings', [\App\Http\Controllers\Admin\SuperScrapeSettingsController::class, 'update'])
            ->middleware('demo.prevent')
            ->name('super-scrape.settings.update');

        // UniBox Addon Settings
        Route::get('/unibox/settings', [\App\Http\Controllers\Admin\UniboxSettingsController::class, 'edit'])
            ->name('unibox.settings');
        Route::put('/unibox/settings', [\App\Http\Controllers\Admin\UniboxSettingsController::class, 'update'])
            ->middleware('demo.prevent')
            ->name('unibox.settings.update');

        // Workspace Addon Settings
        Route::get('/workspace/settings', [\App\Http\Controllers\Admin\WorkspaceSettingsController::class, 'edit'])
            ->name('workspace.settings');
        Route::put('/workspace/settings', [\App\Http\Controllers\Admin\WorkspaceSettingsController::class, 'update'])
            ->middleware('demo.prevent')
            ->name('workspace.settings.update');
    });
});

// Customer Routes
Route::prefix('customer')->name('customer.')->group(function () {
    // Registration (still separate for customers)
    Route::middleware('guest:customer')->group(function () {
        Route::get('/register', function () {
            return redirect()->route('register');
        })->name('register');

        Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
        Route::get('/login', [CustomerAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [CustomerAuthController::class, 'login'])->name('login.submit');

        Route::get('/auth/google', [\App\Http\Controllers\Customer\GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
        Route::get('/auth/google/callback', [\App\Http\Controllers\Customer\GoogleAuthController::class, 'callback'])->name('auth.google.callback');
    });

    Route::middleware(['auth:customer', 'customer.active', 'customer.verified_if_required', 'workspace.context'])->group(function () {
        Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
        Route::post('/stop-impersonation', [CustomerAuthController::class, 'stopImpersonation'])->name('stop-impersonation');
        Route::post('/language', function (\Illuminate\Http\Request $request) {
            $locale = trim((string) $request->input('locale', ''));

            if ($locale === '') {
                return back();
            }

            $svc = app(\App\Translation\LocaleJsonService::class);

            if (!$svc->validateLocaleCode($locale) || !$svc->localeExists($locale)) {
                return back()->with('error', __('Invalid language.'));
            }

            $user = $request->user('customer');
            if ($user) {
                $user->forceFill(['language' => $locale])->save();
            }

            return back();
        })->name('language.update');
        // Profile
        Route::middleware('customer.access:profile.permissions.can_access_profile')->group(function () {
            Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');
        });
        // Global search
        Route::get('/search/suggest', [CustomerSearchController::class, 'suggest'])->name('search.suggest');
        Route::get('/search', [CustomerSearchController::class, 'index'])->name('search.index');
        
        // Notifications
        Route::get('/notifications/feed', [\App\Http\Controllers\Customer\NotificationController::class, 'feed'])->name('notifications.feed');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\Customer\NotificationController::class, 'markRead'])->name('notifications.mark-read');

        // API
        Route::get('/api', [\App\Http\Controllers\Customer\ApiTokenController::class, 'index'])
            ->middleware('customer.access:api.permissions.can_access_api')
            ->name('api.index');
        Route::post('/api', [\App\Http\Controllers\Customer\ApiTokenController::class, 'store'])
            ->middleware(['customer.access:api.permissions.can_create_api_keys', 'demo.prevent'])
            ->name('api.store');
        Route::delete('/api/{tokenId}', [\App\Http\Controllers\Customer\ApiTokenController::class, 'destroy'])
            ->middleware(['customer.access:api.permissions.can_delete_api_keys', 'demo.prevent'])
            ->name('api.destroy');
        
        // Email Lists
        Route::middleware('customer.access:lists.permissions.can_access_lists')->group(function () {
            Route::resource('lists', \App\Http\Controllers\Customer\EmailListController::class)->parameters([
                'lists' => 'list'
            ]);
            Route::get('tags', [\App\Http\Controllers\Customer\TagController::class, 'index'])->name('tags.index');
            Route::post('tags', [\App\Http\Controllers\Customer\TagController::class, 'store'])->name('tags.store');
            Route::put('tags/{tag}', [\App\Http\Controllers\Customer\TagController::class, 'update'])->name('tags.update');
            Route::delete('tags/{tag}', [\App\Http\Controllers\Customer\TagController::class, 'destroy'])->name('tags.destroy');
            Route::post('lists/{list}/tags', [\App\Http\Controllers\Customer\EmailListController::class, 'storeTag'])->name('lists.tags.store');
            Route::put('lists/{list}/tags', [\App\Http\Controllers\Customer\EmailListController::class, 'updateTag'])->name('lists.tags.update');
            Route::delete('lists/{list}/tags', [\App\Http\Controllers\Customer\EmailListController::class, 'destroyTag'])->name('lists.tags.destroy');
            Route::get('lists/{list}/analytics', [\App\Http\Controllers\Customer\ListAnalyticsController::class, 'index'])->name('lists.analytics');
            Route::get('lists/{list}/segments', [\App\Http\Controllers\Customer\ListSegmentController::class, 'index'])->name('lists.segments.index');
            Route::get('lists/{list}/segments/{segment}/edit', [\App\Http\Controllers\Customer\ListSegmentController::class, 'edit'])->name('lists.segments.edit');
            Route::put('lists/{list}/segments/{segment}', [\App\Http\Controllers\Customer\ListSegmentController::class, 'update'])->name('lists.segments.update');
            Route::delete('lists/{list}/segments/{segment}', [\App\Http\Controllers\Customer\ListSegmentController::class, 'destroy'])->name('lists.segments.destroy');
            Route::get('segments/create', [\App\Http\Controllers\Customer\ListSegmentController::class, 'create'])->name('segments.create');
            Route::post('segments', [\App\Http\Controllers\Customer\ListSegmentController::class, 'store'])->name('segments.store');
            Route::get('lists/{list}/settings', [\App\Http\Controllers\Customer\EmailListSettingsController::class, 'edit'])->name('lists.settings');
            Route::put('lists/{list}/settings', [\App\Http\Controllers\Customer\EmailListSettingsController::class, 'update'])->name('lists.settings.update');
            Route::delete('lists/{list}/empty', [\App\Http\Controllers\Customer\EmailListSettingsController::class, 'emptyList'])->name('lists.empty');

            // List Subscribers
            // Import routes must be defined before resource route to avoid conflicts
            Route::get('lists/{list}/subscribers/import', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'showImport'])->name('lists.subscribers.import');
            Route::post('lists/{list}/subscribers/import', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'import'])->name('lists.subscribers.import.store');
            Route::get('lists/{list}/subscribers/export', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'export'])->name('lists.subscribers.export');
            Route::post('lists/{list}/subscribers/import/ajax/start', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'importAjaxStart'])->name('lists.subscribers.import.ajax.start');
            Route::post('lists/{list}/subscribers/import/ajax/step', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'importAjaxStep'])->name('lists.subscribers.import.ajax.step');
            Route::get('lists/{list}/subscribers/import/stats', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'importStats'])->name('lists.subscribers.import.stats');

            // Bulk operations
            Route::delete('lists/{list}/subscribers/bulk-delete', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'bulkDelete'])->name('lists.subscribers.bulk-delete');
            Route::post('lists/{list}/subscribers/bulk-confirm', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'bulkConfirm'])->name('lists.subscribers.bulk-confirm');
            Route::post('lists/{list}/subscribers/bulk-unsubscribe', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'bulkUnsubscribe'])->name('lists.subscribers.bulk-unsubscribe');
            Route::post('lists/{list}/subscribers/bulk-resend', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'bulkResend'])->name('lists.subscribers.bulk-resend');
            Route::post('lists/{list}/subscribers/bulk-add-to-segment', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'bulkAddToSegment'])->name('lists.subscribers.bulk-add-to-segment');

            Route::resource('lists.subscribers', \App\Http\Controllers\Customer\ListSubscriberController::class)->parameters([
                'lists' => 'list',
                'subscribers' => 'subscriber'
            ]);
            Route::post('lists/{list}/subscribers/{subscriber}/confirm', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'confirm'])->name('lists.subscribers.confirm');
            Route::post('lists/{list}/subscribers/{subscriber}/unsubscribe', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'unsubscribe'])->name('lists.subscribers.unsubscribe');
            Route::post('lists/{list}/subscribers/{subscriber}/resend-confirmation', [\App\Http\Controllers\Customer\ListSubscriberController::class, 'resendConfirmation'])->name('lists.subscribers.resend-confirmation');

            // Subscription Forms
            Route::get('lists/{list}/forms/{form}/builder', [\App\Http\Controllers\Customer\SubscriptionFormController::class, 'editBuilder'])
                ->name('lists.forms.builder.edit');
            Route::put('lists/{list}/forms/{form}/builder', [\App\Http\Controllers\Customer\SubscriptionFormController::class, 'updateBuilder'])
                ->name('lists.forms.builder.update');
            Route::post('lists/{list}/forms/{form}/builder/render-html', [\App\Http\Controllers\Customer\SubscriptionFormController::class, 'renderBuilderHtml'])
                ->name('lists.forms.builder.render-html');
            Route::get('lists/{list}/forms/{form}/unlayer/edit', [\App\Http\Controllers\Customer\SubscriptionFormController::class, 'editUnlayer'])
                ->name('lists.forms.unlayer.edit');
            Route::put('lists/{list}/forms/{form}/unlayer', [\App\Http\Controllers\Customer\SubscriptionFormController::class, 'updateUnlayer'])
                ->name('lists.forms.unlayer.update');
            Route::resource('lists.forms', \App\Http\Controllers\Customer\SubscriptionFormController::class)->parameters([
                'lists' => 'list',
                'forms' => 'form'
            ]);

            Route::post('lists/{list}/forms/upload-icon', [\App\Http\Controllers\Customer\SubscriptionFormController::class, 'uploadIcon'])
                ->name('lists.forms.upload-icon');
        });

        // Inline image upload (used by the customer rich-text editor)
        Route::post('uploads/inline-image', [\App\Http\Controllers\Customer\InlineImageController::class, 'store'])
            ->name('uploads.inline-image');

        // Forms (Global)
        Route::get('forms', [\App\Http\Controllers\Customer\FormsController::class, 'index'])->name('forms.index');
        Route::get('forms/create', [\App\Http\Controllers\Customer\FormsController::class, 'create'])->name('forms.create');
        Route::post('forms', [\App\Http\Controllers\Customer\FormsController::class, 'store'])->name('forms.store');
        
        // Campaigns
        Route::middleware('customer.access:campaigns.permissions.can_access_campaigns')->group(function () {
            Route::resource('campaigns', \App\Http\Controllers\Customer\CampaignController::class);
            Route::post('campaigns/{campaign}/duplicate', [\App\Http\Controllers\Customer\CampaignController::class, 'duplicate'])->name('campaigns.duplicate');
            Route::post('campaigns/{campaign}/start', [\App\Http\Controllers\Customer\CampaignController::class, 'start'])->name('campaigns.start');
            Route::post('campaigns/{campaign}/pause', [\App\Http\Controllers\Customer\CampaignController::class, 'pause'])->name('campaigns.pause');
            Route::post('campaigns/{campaign}/stop', [\App\Http\Controllers\Customer\CampaignController::class, 'stop'])->name('campaigns.stop');
            Route::post('campaigns/{campaign}/resume', [\App\Http\Controllers\Customer\CampaignController::class, 'resume'])->name('campaigns.resume');
            Route::post('campaigns/{campaign}/rerun', [\App\Http\Controllers\Customer\CampaignController::class, 'rerun'])->name('campaigns.rerun');
            Route::post('campaigns/spam-preview', [\App\Http\Controllers\Customer\CampaignController::class, 'previewSpamScore'])->name('campaigns.spam-preview');
            Route::post('campaigns/server-ping', [\App\Http\Controllers\Customer\CampaignController::class, 'serverPing'])->name('campaigns.server-ping');
            Route::get('campaigns/{campaign}/stats', [\App\Http\Controllers\Customer\CampaignController::class, 'stats'])->name('campaigns.stats');
            Route::get('campaigns/{campaign}/preview-html', [\App\Http\Controllers\Customer\CampaignController::class, 'previewHtml'])->name('campaigns.preview-html');
            Route::get('campaigns/{campaign}/recipients', [\App\Http\Controllers\Customer\CampaignController::class, 'recipients'])->name('campaigns.recipients');
            Route::get('campaigns/{campaign}/replies', [\App\Http\Controllers\Customer\CampaignController::class, 'replies'])->name('campaigns.replies');
            Route::get('campaigns/{campaign}/ab-test', [\App\Http\Controllers\Customer\CampaignController::class, 'showAbTest'])->middleware('customer.access:campaigns.features.ab_testing')->name('campaigns.ab-test');
            Route::post('campaigns/{campaign}/ab-test', [\App\Http\Controllers\Customer\CampaignController::class, 'storeAbTest'])->middleware('customer.access:campaigns.features.ab_testing')->name('campaigns.ab-test.store');
            Route::post('campaigns/{campaign}/variants/{variant}/select-winner', [\App\Http\Controllers\Customer\CampaignController::class, 'selectWinner'])->middleware('customer.access:campaigns.features.ab_testing')->name('campaigns.variants.select-winner');
        });

        // Auto Responders
        Route::resource('auto-responders', \App\Http\Controllers\Customer\AutoResponderController::class)->middleware('customer.access:autoresponders.enabled');

        Route::resource('automations', \App\Http\Controllers\Customer\AutomationController::class)->middleware('customer.access:automations.enabled');
        Route::get('automations/{automation}/analytics', [\App\Http\Controllers\Customer\AutomationAnalyticsController::class, 'show'])->name('automations.analytics')->middleware('customer.access:automations.enabled');
        Route::post('automations/{automation}/run-test', [\App\Http\Controllers\Customer\AutomationController::class, 'runTest'])->name('automations.run-test')->middleware('customer.access:automations.enabled');

        // Tracking Domains
        Route::middleware('customer.access:domains.tracking_domains.permissions.can_access_tracking_domains')->group(function () {
            Route::resource('tracking-domains', \App\Http\Controllers\Customer\TrackingDomainController::class);
            Route::post('tracking-domains/{tracking_domain}/verify', [\App\Http\Controllers\Customer\TrackingDomainController::class, 'verify'])->name('tracking-domains.verify');
            Route::post('tracking-domains/{tracking_domain}/mark-verified', [\App\Http\Controllers\Customer\TrackingDomainController::class, 'markVerified'])->name('tracking-domains.mark-verified');
        });

        // Sending Domains
        Route::middleware('customer.access:domains.sending_domains.permissions.can_access_sending_domains')->group(function () {
            Route::resource('sending-domains', \App\Http\Controllers\Customer\SendingDomainController::class);
            Route::post('sending-domains/{sending_domain}/verify', [\App\Http\Controllers\Customer\SendingDomainController::class, 'verify'])->name('sending-domains.verify');
            Route::post('sending-domains/{sending_domain}/mark-verified', [\App\Http\Controllers\Customer\SendingDomainController::class, 'markVerified'])->name('sending-domains.mark-verified');
        });
        
        // Transactional Emails
        Route::resource('transactional-emails', \App\Http\Controllers\Customer\TransactionalEmailController::class);
        
        // Subscriptions
        Route::resource('subscriptions', \App\Http\Controllers\Customer\SubscriptionController::class);
        Route::post('subscriptions/{subscription}/cancel', [\App\Http\Controllers\Customer\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('subscriptions/{subscription}/renew', [\App\Http\Controllers\Customer\SubscriptionController::class, 'renew'])->name('subscriptions.renew');

        // Billing UI
        Route::get('billing', [\App\Http\Controllers\Customer\BillingController::class, 'index'])->name('billing.index');
        Route::get('billing/checkout/{plan}', [\App\Http\Controllers\Customer\BillingController::class, 'showCheckout'])->name('billing.checkout.show');
        Route::post('billing/checkout/{plan}', [\App\Http\Controllers\Customer\BillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('billing/success', [\App\Http\Controllers\Customer\BillingController::class, 'success'])->name('billing.success');
        Route::delete('billing/history/{event}', [\App\Http\Controllers\Customer\BillingController::class, 'destroyInvoiceEvent'])->name('billing.history.destroy');

        Route::get('billing/manual/{subscription}', [\App\Http\Controllers\Customer\ManualPaymentController::class, 'show'])->name('billing.manual.show');
        Route::post('billing/manual/{subscription}/confirm', [\App\Http\Controllers\Customer\ManualPaymentController::class, 'confirm'])->name('billing.manual.confirm');

        // Affiliate
        Route::get('affiliate', [\App\Http\Controllers\Customer\AffiliateController::class, 'index'])
            ->middleware('affiliate.enabled')
            ->name('affiliate.index');

        Route::get('affiliate/payments', [\App\Http\Controllers\Customer\AffiliateController::class, 'payments'])
            ->middleware('affiliate.enabled')
            ->name('affiliate.payments');

        Route::post('affiliate/payout-settings', [\App\Http\Controllers\Customer\AffiliateController::class, 'updatePayoutSettings'])
            ->middleware(['demo.prevent', 'affiliate.enabled'])
            ->name('affiliate.payout-settings.update');

        Route::post('affiliate/apply', [\App\Http\Controllers\Customer\AffiliateController::class, 'apply'])
            ->middleware(['demo.prevent', 'affiliate.enabled'])
            ->name('affiliate.apply');
        
        // Email Verification
        Route::get('/verify-email/{token}', [\App\Http\Controllers\Customer\EmailVerificationController::class, 'verify'])->name('email.verify');
        
        // Templates
        Route::middleware('customer.access:templates.permissions.can_access_templates')->group(function () {
            Route::get('templates/unlayer/create', [\App\Http\Controllers\Customer\TemplateController::class, 'createUnlayer'])->name('templates.unlayer.create');
            Route::post('templates/unlayer', [\App\Http\Controllers\Customer\TemplateController::class, 'storeUnlayer'])->name('templates.unlayer.store');
            Route::post('templates/render-builder', [\App\Http\Controllers\Customer\TemplateController::class, 'renderBuilder'])->name('templates.render-builder');
            Route::get('templates/gallery/{resourceType}', [\App\Http\Controllers\Customer\TemplateGalleryController::class, 'list'])->name('templates.gallery.list');
            Route::post('templates/gallery-fetch/{templateId}', [\App\Http\Controllers\Customer\TemplateGalleryController::class, 'show'])->name('templates.gallery.fetch');
            Route::post('templates/test-builder-email', [\App\Http\Controllers\Customer\TemplateController::class, 'sendBuilderTestEmail'])->name('templates.test-builder-email');
            Route::get('templates/ai/models', [\App\Http\Controllers\Customer\TemplateAiController::class, 'models'])->name('templates.ai.models');
            Route::post('templates/ai/message', [\App\Http\Controllers\Customer\TemplateAiController::class, 'message'])->name('templates.ai.message');
            Route::get('templates/{template}/unlayer/edit', [\App\Http\Controllers\Customer\TemplateController::class, 'editUnlayer'])->name('templates.unlayer.edit');
            Route::put('templates/{template}/unlayer', [\App\Http\Controllers\Customer\TemplateController::class, 'updateUnlayer'])->name('templates.unlayer.update');
            Route::get('templates/import/gallery', [\App\Http\Controllers\Customer\TemplateController::class, 'importGallery'])->name('templates.import.gallery');
            Route::get('templates/import/file/{key}/content', [\App\Http\Controllers\Customer\TemplateController::class, 'importFileContent'])
                ->name('templates.import.file.content');
            Route::get('templates/import/{template}/content', [\App\Http\Controllers\Customer\TemplateController::class, 'importContent'])->name('templates.import.content');
            Route::get('templates/import/public/{publicTemplate}/content', [\App\Http\Controllers\Customer\TemplateController::class, 'importPublicContent'])->name('templates.import.public.content');
            Route::post('templates/ai-generate', [\App\Http\Controllers\Customer\TemplateController::class, 'aiGenerate'])->name('templates.ai-generate');
            Route::resource('templates', \App\Http\Controllers\Customer\TemplateController::class);
            Route::post('templates/{template}/duplicate', [\App\Http\Controllers\Customer\TemplateController::class, 'duplicate'])->name('templates.duplicate');
            Route::patch('templates/{template}/settings', [\App\Http\Controllers\Customer\TemplateController::class, 'updateSettings'])->name('templates.settings.update');
            Route::get('templates/{template}/preview', [\App\Http\Controllers\Customer\TemplateController::class, 'preview'])->name('templates.preview');
            Route::get('templates/{template}/content', [\App\Http\Controllers\Customer\TemplateController::class, 'getContent'])->name('templates.content');
            Route::post('templates/{template}/test-email', [\App\Http\Controllers\Customer\TemplateController::class, 'sendTestEmail'])->name('templates.test-email');
        });

        // AI Tools
        Route::prefix('ai-tools')
            ->name('ai-tools.')
            ->middleware('customer.access:ai_tools.permissions.can_access_ai_tools')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Customer\AiToolController::class, 'index'])->name('index');

                Route::get('/email-text-generator', [\App\Http\Controllers\Customer\AiToolController::class, 'emailTextGenerator'])
                    ->middleware('customer.access:ai_tools.permissions.can_use_email_text_generator')
                    ->name('email-text-generator');
                Route::post('/email-text-generator', [\App\Http\Controllers\Customer\AiToolController::class, 'generateEmailText'])
                    ->middleware(['customer.access:ai_tools.permissions.can_use_email_text_generator', 'demo.prevent'])
                    ->name('email-text-generator.generate');

                Route::post('/email-text-generator/export-to-template', [\App\Http\Controllers\Customer\AiToolController::class, 'exportEmailTextToTemplate'])
                    ->middleware(['customer.access:ai_tools.permissions.can_use_email_text_generator', 'customer.access:templates.permissions.can_create_templates', 'demo.prevent'])
                    ->name('email-text-generator.export-to-template');
            });

        // RAG — Knowledge Base, AI Chat, Campaign Writer, Insights
        Route::prefix('rag')
            ->name('rag.')
            ->middleware('customer.access:ai_tools.permissions.can_access_ai_tools')
            ->group(function () {
                Route::get('/knowledge', [\App\Http\Controllers\Customer\RagController::class, 'knowledgeIndex'])->name('knowledge.index');
                Route::get('/knowledge/create', [\App\Http\Controllers\Customer\RagController::class, 'knowledgeCreate'])->name('knowledge.create');
                Route::post('/knowledge', [\App\Http\Controllers\Customer\RagController::class, 'knowledgeStore'])
                    ->middleware('demo.prevent')
                    ->name('knowledge.store');
                Route::delete('/knowledge/{knowledgeBase}', [\App\Http\Controllers\Customer\RagController::class, 'knowledgeDestroy'])
                    ->middleware('demo.prevent')
                    ->name('knowledge.destroy');

                Route::get('/chat', [\App\Http\Controllers\Customer\RagController::class, 'chatIndex'])->name('chat.index');
                Route::post('/chat/ask', [\App\Http\Controllers\Customer\RagController::class, 'chatAsk'])
                    ->middleware('demo.prevent')
                    ->name('chat.ask');
                Route::post('/chat/feedback', [\App\Http\Controllers\Customer\RagController::class, 'chatFeedback'])
                    ->middleware('demo.prevent')
                    ->name('chat.feedback');

                Route::get('/campaign', [\App\Http\Controllers\Customer\RagController::class, 'campaignIndex'])->name('campaign.index');
                Route::post('/campaign/generate', [\App\Http\Controllers\Customer\RagController::class, 'campaignGenerate'])
                    ->middleware('demo.prevent')
                    ->name('campaign.generate');

                Route::get('/insights', [\App\Http\Controllers\Customer\RagController::class, 'insightsIndex'])->name('insights.index');
                Route::post('/insights/ask', [\App\Http\Controllers\Customer\RagController::class, 'insightsAsk'])
                    ->middleware('demo.prevent')
                    ->name('insights.ask');
            });

        // AI Agents — trainable chatbot agents
        Route::prefix('ai-agents')
            ->name('ai-agents.')
            ->middleware('customer.access:ai_tools.permissions.can_access_ai_tools')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Customer\AiAgentController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Customer\AiAgentController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Customer\AiAgentController::class, 'store'])
                    ->middleware('demo.prevent')
                    ->name('store');
                Route::get('/{aiAgent}/chat', [\App\Http\Controllers\Customer\AiAgentController::class, 'chat'])->name('chat');
                Route::get('/{aiAgent}/edit', [\App\Http\Controllers\Customer\AiAgentController::class, 'edit'])->name('edit');
                Route::put('/{aiAgent}', [\App\Http\Controllers\Customer\AiAgentController::class, 'update'])
                    ->middleware('demo.prevent')
                    ->name('update');
                Route::delete('/{aiAgent}', [\App\Http\Controllers\Customer\AiAgentController::class, 'destroy'])
                    ->middleware('demo.prevent')
                    ->name('destroy');

                Route::post('/ask', [\App\Http\Controllers\Customer\AiAgentController::class, 'ask'])
                    ->middleware('demo.prevent')
                    ->name('ask');
                Route::post('/ask-stream', [\App\Http\Controllers\Customer\AiAgentController::class, 'askStream'])
                    ->middleware('demo.prevent')
                    ->name('ask.stream');
                Route::post('/{aiAgent}/chat/feedback', [\App\Http\Controllers\Customer\AiAgentController::class, 'chatFeedback'])
                    ->middleware('demo.prevent')
                    ->name('chat.feedback');
                Route::get('/{aiAgent}/conversations-list', [\App\Http\Controllers\Customer\AiAgentController::class, 'conversationsList'])
                    ->name('conversations.list');
                Route::get('/{aiAgent}/conversations/{conversation}', [\App\Http\Controllers\Customer\AiAgentController::class, 'conversation'])
                    ->name('conversation');
            });

        // Analytics
        Route::get('/analytics', [\App\Http\Controllers\Customer\AnalyticsController::class, 'index'])->name('analytics.index');

        // Delivery Servers
        Route::middleware('customer.access:servers.permissions.can_access_delivery_servers')->group(function () {
            Route::get('delivery-servers/webhook-events', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'webhookEvents'])
                ->name('delivery-servers.webhook-events');

            Route::resource('delivery-servers', \App\Http\Controllers\Customer\DeliveryServerController::class)->parameters([
                'delivery-servers' => 'delivery_server'
            ]);

            Route::get('delivery-servers/{delivery_server}/secret', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'revealSecret'])
                ->name('delivery-servers.secret');

            Route::post('delivery-servers/{delivery_server}/test-email', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'sendTestEmail'])
                ->middleware(['demo.prevent', 'customer.access:servers.permissions.can_edit_delivery_servers'])
                ->name('delivery-servers.test-email');
            Route::post('delivery-servers/{delivery_server}/test-connection', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'testConnection'])
                ->middleware(['demo.prevent', 'customer.access:servers.permissions.can_edit_delivery_servers'])
                ->name('delivery-servers.test-connection');
            Route::post('delivery-servers/fetch-sparkpost-domains', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'fetchSparkPostDomains'])
                ->middleware(['demo.prevent', 'customer.access:servers.permissions.can_edit_delivery_servers'])
                ->name('delivery-servers.fetch-sparkpost-domains');
            Route::post('delivery-servers/fetch-resend-domains', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'fetchResendDomains'])
                ->middleware(['demo.prevent', 'customer.access:servers.permissions.can_edit_delivery_servers'])
                ->name('delivery-servers.fetch-resend-domains');
            Route::post('delivery-servers/fetch-sendgrid-domains', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'fetchSendGridDomains'])
                ->middleware(['demo.prevent', 'customer.access:servers.permissions.can_edit_delivery_servers'])
                ->name('delivery-servers.fetch-sendgrid-domains');
            Route::post('delivery-servers/fetch-emailit-domains', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'fetchEmailitDomains'])
                ->middleware(['demo.prevent', 'customer.access:servers.permissions.can_edit_delivery_servers'])
                ->name('delivery-servers.fetch-emailit-domains');
            Route::get('delivery-servers/{delivery_server}/verify/{token}', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'verify'])
                ->name('delivery-servers.verify');
            Route::post('delivery-servers/{delivery_server}/resend-verification', [\App\Http\Controllers\Customer\DeliveryServerController::class, 'resendVerification'])
                ->name('delivery-servers.resend-verification');
        });

        // Gmail OAuth for Delivery Servers
        Route::post('oauth/gmail/redirect', [\App\Http\Controllers\Customer\GmailOAuthController::class, 'redirect'])
            ->name('oauth.gmail.redirect');
        Route::get('oauth/gmail/callback', [\App\Http\Controllers\Customer\GmailOAuthController::class, 'callback'])
            ->name('oauth.gmail.callback');

        // Bounce Servers
        Route::middleware('customer.access:servers.permissions.can_access_bounce_servers')->group(function () {
            Route::resource('bounce-servers', \App\Http\Controllers\Customer\BounceServerController::class)->parameters([
                'bounce-servers' => 'bounce_server'
            ]);
        });

        // Email Warmups
        Route::resource('warmups', \App\Http\Controllers\Customer\EmailWarmupController::class)->parameters([
            'warmups' => 'warmup'
        ]);
        Route::post('warmups/check-domain-auth', [\App\Http\Controllers\Customer\EmailWarmupController::class, 'checkDomainAuth'])
            ->name('warmups.check-domain-auth');
        Route::post('warmups/{warmup}/start', [\App\Http\Controllers\Customer\EmailWarmupController::class, 'start'])
            ->name('warmups.start');
        Route::post('warmups/{warmup}/pause', [\App\Http\Controllers\Customer\EmailWarmupController::class, 'pause'])
            ->name('warmups.pause');
        Route::get('warmups/{warmup}/stats', [\App\Http\Controllers\Customer\EmailWarmupController::class, 'stats'])
            ->name('warmups.stats');

        // AI Projects (Page Builder)
        Route::prefix('ai-projects')->name('ai-projects.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'projects'])->name('index');
            Route::get('builder', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'index'])->name('builder');
            Route::get('builder/starter-files', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'starterFiles'])->name('builder.starter-files');
            Route::get('builder/{session}', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'show'])->name('builder.show');
            Route::delete('builder/{session}', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'destroy'])->name('builder.destroy');

            Route::post('builder/analyze', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'analyze'])->name('builder.analyze');
            Route::post('builder/suggestions', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'suggestions'])->name('builder.suggestions');
            Route::post('builder/generate', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'generate'])->name('builder.generate');
            Route::post('builder/progress', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'progress'])->name('builder.progress');
            Route::post('builder/stream', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'stream'])->name('builder.stream');
            Route::post('builder/cancel', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'cancel'])->name('builder.cancel');
            Route::post('builder/save', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'save'])->name('builder.save');
            Route::get('builder/credit-status', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'creditStatus'])->name('builder.credit-status');
            Route::post('builder/session', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'storeSession'])->name('builder.session.store');
            Route::put('builder/session/{session}', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'updateSession'])->name('builder.session.update');
            Route::post('builder/session/{session}/compile', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'compileSandbox'])->name('builder.sandbox.compile');
            Route::post('builder/session/{session}/iterate', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'iterate'])->name('builder.iterate');
            Route::post('builder/session/{session}/discuss', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'discuss'])->name('builder.discuss');
            Route::post('builder/session/{session}/custom-domain', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'customDomain'])->name('builder.custom-domain');
            Route::get('builder/session/{session}/shares', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'shares'])->name('builder.shares');
            Route::post('builder/session/{session}/shares/invite', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'inviteShare'])->name('builder.shares.invite');
            Route::put('builder/session/{session}/shares/{share}', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'updateShare'])->name('builder.shares.update');
            Route::delete('builder/session/{session}/shares/{share}', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'removeShare'])->name('builder.shares.remove');
            Route::get('builder/{session}/export', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'export'])
                ->name('builder.export');

            Route::get('builder/session/{session}/sandbox/{path?}', [\App\Http\Controllers\Customer\PageAiBuilderController::class, 'serveSandbox'])
                ->where('path', '.*')
                ->name('builder.sandbox.preview');
        });

        // Cold Email Outreach (addon)
        Route::prefix('outreach')->name('outreach.')->middleware('customer.access:outreach.access')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\OutreachController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'store'])->name('campaigns.store');

            Route::prefix('campaigns')->name('campaigns.')->group(function () {
                Route::get('{campaign}', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'show'])->name('show');
                Route::get('{campaign}/preview', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'preview'])->name('preview');
                Route::post('{campaign}/leads', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'updateLeads'])->name('leads.update');
                Route::put('{campaign}/leads/{lead}', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'updateLead'])->name('leads.edit');
                Route::delete('{campaign}/leads/{lead}', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'destroyLead'])->name('leads.destroy');
                Route::post('{campaign}/sequences', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'updateSequences'])->name('sequences.update');
                Route::post('{campaign}/variables', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'updateVariables'])->name('variables.update');
                Route::post('{campaign}/fields', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'updateCustomFields'])->name('fields.update');
                Route::post('{campaign}/schedule', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'updateSchedule'])->name('schedule.update');
                Route::post('{campaign}/options', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'updateOptions'])->name('options.update');
                Route::post('{campaign}/pause', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'pause'])->name('pause');
                Route::post('{campaign}/resume', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'resume'])->name('resume');
                Route::post('{campaign}/duplicate', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'duplicate'])->name('duplicate');
                Route::delete('{campaign}', [\App\Http\Controllers\Customer\OutreachCampaignController::class, 'destroy'])->name('destroy');
            });
        });

        // SuperScrape — Lead Scraper
        Route::prefix('scraper')->name('scraper.')->middleware('customer.access:scraper.permissions.can_access_scraper')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\ScraperController::class, 'index'])->name('index');
            Route::post('/start', [\App\Http\Controllers\Customer\ScraperController::class, 'start'])->name('start');
            Route::get('/jobs', [\App\Http\Controllers\Customer\ScraperController::class, 'jobs'])->name('jobs');
            Route::get('/settings', [\App\Http\Controllers\Customer\ScraperController::class, 'settings'])->name('settings');
            Route::get('/lookup', [\App\Http\Controllers\Customer\ScraperController::class, 'lookup'])->name('lookup');
            Route::post('/lookup', [\App\Http\Controllers\Customer\ScraperController::class, 'lookup']);
            Route::get('/jobs/{job}/results', [\App\Http\Controllers\Customer\ScraperController::class, 'results'])->name('results');
            Route::get('/jobs/{job}/enriched', [\App\Http\Controllers\Customer\ScraperController::class, 'enrichedResults'])->name('enriched-results');
            Route::get('/jobs/{job}/export', [\App\Http\Controllers\Customer\ScraperController::class, 'exportCsv'])->name('export');
            Route::get('/jobs/{job}/export-enriched', [\App\Http\Controllers\Customer\ScraperController::class, 'exportEnrichedCsv'])->name('export-enriched');
            Route::post('/jobs/{job}/enrich', [\App\Http\Controllers\Customer\ScraperController::class, 'enrich'])->name('enrich');
            Route::post('/jobs/{job}/push', [\App\Http\Controllers\Customer\ScraperController::class, 'pushToList'])->name('push');
            Route::delete('/jobs/{job}', [\App\Http\Controllers\Customer\ScraperController::class, 'deleteJob'])->name('delete');
            Route::get('/jobs/{job}/status', [\App\Http\Controllers\Customer\ScraperController::class, 'status'])->name('status');
            Route::get('/jobs/{job}/diagnosis', [\App\Http\Controllers\Customer\ScraperController::class, 'diagnosis'])->name('diagnosis');
        });

        // SMS Addon
        Route::prefix('sms')->name('sms.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\SmsAccountController::class, 'index'])->name('accounts.index');
            Route::post('/accounts', [\App\Http\Controllers\Customer\SmsAccountController::class, 'store'])->name('accounts.store');
            Route::delete('/accounts/{smsAccount}', [\App\Http\Controllers\Customer\SmsAccountController::class, 'destroy'])->name('accounts.destroy');
            Route::post('/accounts/{smsAccount}/test', [\App\Http\Controllers\Customer\SmsAccountController::class, 'test'])->name('accounts.test');

            Route::prefix('campaigns')->name('campaigns.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Customer\SmsCampaignController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Customer\SmsCampaignController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Customer\SmsCampaignController::class, 'store'])->name('store');
                Route::get('/{smsCampaign}', [\App\Http\Controllers\Customer\SmsCampaignController::class, 'show'])->name('show');
                Route::post('/{smsCampaign}/send', [\App\Http\Controllers\Customer\SmsCampaignController::class, 'send'])->name('send');
            });

            Route::prefix('messages')->name('messages.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Customer\SmsMessageController::class, 'index'])->name('index');
                Route::get('/{smsThread}', [\App\Http\Controllers\Customer\SmsMessageController::class, 'show'])->name('show');
                Route::post('/{smsThread}/reply', [\App\Http\Controllers\Customer\SmsMessageController::class, 'reply'])->name('reply');
            });
        });

        // WhatsApp Addon
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\WhatsAppAccountController::class, 'index'])->name('accounts.index');
            Route::post('/accounts', [\App\Http\Controllers\Customer\WhatsAppAccountController::class, 'store'])->name('accounts.store');
            Route::delete('/accounts/{whatsappAccount}', [\App\Http\Controllers\Customer\WhatsAppAccountController::class, 'destroy'])->name('accounts.destroy');
            Route::post('/accounts/{whatsappAccount}/test', [\App\Http\Controllers\Customer\WhatsAppAccountController::class, 'test'])->name('accounts.test');

            Route::prefix('campaigns')->name('campaigns.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Customer\WhatsAppCampaignController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Customer\WhatsAppCampaignController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Customer\WhatsAppCampaignController::class, 'store'])->name('store');
                Route::get('/{whatsappCampaign}', [\App\Http\Controllers\Customer\WhatsAppCampaignController::class, 'show'])->name('show');
                Route::post('/{whatsappCampaign}/send', [\App\Http\Controllers\Customer\WhatsAppCampaignController::class, 'send'])->name('send');
            });

            Route::prefix('messages')->name('messages.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Customer\WhatsAppMessageController::class, 'index'])->name('index');
                Route::get('/{whatsappThread}', [\App\Http\Controllers\Customer\WhatsAppMessageController::class, 'show'])->name('show');
                Route::post('/{whatsappThread}/reply', [\App\Http\Controllers\Customer\WhatsAppMessageController::class, 'reply'])->name('reply');
            });
        });

        // AI Assistant Addon
        Route::prefix('ai-assistant')->name('ai-assistant.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\AiAssistantController::class, 'index'])->name('index');
            Route::post('/ask', [\App\Http\Controllers\Customer\AiAssistantController::class, 'ask'])->name('ask');
            Route::delete('/{conversation}', [\App\Http\Controllers\Customer\AiAssistantController::class, 'destroy'])->name('destroy');
        });

        // Reply Servers
        Route::middleware('customer.access:servers.permissions.can_access_reply_servers')->group(function () {
            Route::resource('reply-servers', \App\Http\Controllers\Customer\ReplyServerController::class)->parameters([
                'reply-servers' => 'reply_server'
            ]);
        });

        // Bounced Emails
        Route::middleware('customer.access:bounced_emails.access')->group(function () {
            Route::get('bounced-emails', [\App\Http\Controllers\Customer\BouncedEmailController::class, 'index'])->name('bounced-emails.index');
            Route::get('bounced-emails/{bounced_email}', [\App\Http\Controllers\Customer\BouncedEmailController::class, 'show'])->name('bounced-emails.show');
        });

        // Email Validation
        Route::prefix('email-validation')
            ->name('email-validation.')
            ->middleware('customer.access:email_validation.access')
            ->group(function () {
                Route::resource('tools', \App\Http\Controllers\Customer\EmailValidationToolController::class);
                Route::resource('runs', \App\Http\Controllers\Customer\EmailValidationRunController::class)->only(['index', 'create', 'store', 'show']);
                Route::get('runs/{run}/stats', [\App\Http\Controllers\Customer\EmailValidationRunController::class, 'stats'])->name('runs.stats');
                Route::get('runs/{run}/errors', [\App\Http\Controllers\Customer\EmailValidationRunController::class, 'errors'])->name('runs.errors');
                Route::get('runs/{run}/export-valid', [\App\Http\Controllers\Customer\EmailValidationRunController::class, 'exportValid'])->name('runs.export-valid');
                Route::post('runs/{run}/pause', [\App\Http\Controllers\Customer\EmailValidationRunController::class, 'pause'])->name('runs.pause');
                Route::post('runs/{run}/resume', [\App\Http\Controllers\Customer\EmailValidationRunController::class, 'resume'])->name('runs.resume');
                Route::post('runs/{run}/resume-failed', [\App\Http\Controllers\Customer\EmailValidationRunController::class, 'resumeFailed'])->name('runs.resume-failed');
            });

        // Usage
        Route::get('usage', [\App\Http\Controllers\Customer\UsageController::class, 'index'])->name('usage.index');

        // Settings
        Route::middleware('customer.access:settings.permissions.can_access_settings')->group(function () {
            Route::get('settings', [\App\Http\Controllers\Customer\SettingController::class, 'index'])->name('settings.index');
            Route::put('settings', [\App\Http\Controllers\Customer\SettingController::class, 'update'])->name('settings.update');
            Route::put('settings/email', [\App\Http\Controllers\Customer\SettingController::class, 'updateEmail'])->name('settings.email.update');
            Route::put('settings/password', [\App\Http\Controllers\Customer\SettingController::class, 'updatePassword'])->name('settings.password.update');

            Route::get('settings/secret/{key}', [\App\Http\Controllers\Customer\SettingController::class, 'revealSecret'])
                ->name('settings.secret');
        });

        // Supabase settings
        Route::get('supabase', [\App\Http\Controllers\Customer\SupabaseController::class, 'index'])
            ->name('supabase.index');
        Route::post('supabase/connect-admin', [\App\Http\Controllers\Customer\SupabaseController::class, 'connectAdmin'])
            ->middleware('demo.prevent')
            ->name('supabase.connect-admin');
        Route::post('supabase/store-byos', [\App\Http\Controllers\Customer\SupabaseController::class, 'storeByos'])
            ->middleware('demo.prevent')
            ->name('supabase.store-byos');
        Route::delete('supabase/disconnect', [\App\Http\Controllers\Customer\SupabaseController::class, 'disconnect'])
            ->middleware('demo.prevent')
            ->name('supabase.disconnect');
        Route::get('supabase/tables', [\App\Http\Controllers\Customer\SupabaseController::class, 'tables'])
            ->name('supabase.tables');
        Route::get('supabase/buckets', [\App\Http\Controllers\Customer\SupabaseController::class, 'buckets'])
            ->name('supabase.buckets');
        Route::get('supabase/users', [\App\Http\Controllers\Customer\SupabaseController::class, 'users'])
            ->name('supabase.users');
        Route::get('supabase/table-data', [\App\Http\Controllers\Customer\SupabaseController::class, 'tableData'])
            ->name('supabase.table-data');
         Route::get('supabase/status', [\App\Http\Controllers\Customer\SupabaseController::class, 'status'])
             ->name('supabase.status');
Route::post('supabase/login', [\App\Http\Controllers\Customer\SupabaseController::class, 'login'])
              ->name('supabase.login');
          Route::post('supabase/signup', [\App\Http\Controllers\Customer\SupabaseController::class, 'signup'])
              ->name('supabase.signup');

         // Integrations
        Route::get('integrations', [\App\Http\Controllers\Customer\IntegrationController::class, 'index'])
            ->middleware('customer.access:servers.permissions.can_access_delivery_servers')
            ->name('integrations.index');

        Route::get('integrations/wordpress/plugin', [\App\Http\Controllers\Customer\IntegrationController::class, 'downloadWordpressPlugin'])
            ->middleware('customer.access:servers.permissions.can_access_delivery_servers')
            ->name('integrations.wordpress.plugin');

        Route::prefix('integrations/google')
            ->name('integrations.google.')
            ->middleware('customer.access:integrations.permissions.can_access_google')
            ->group(function () {
                Route::get('{service}/connect', [\App\Http\Controllers\Customer\GoogleIntegrationController::class, 'connect'])
                    ->name('connect');
                Route::get('{service}/callback', [\App\Http\Controllers\Customer\GoogleIntegrationController::class, 'callback'])
                    ->name('callback');
                Route::post('{service}/disconnect', [\App\Http\Controllers\Customer\GoogleIntegrationController::class, 'disconnect'])
                    ->middleware('demo.prevent')
                    ->name('disconnect');
            });

        // Support Tickets
        Route::middleware('customer.access:support.permissions.can_access_support')->group(function () {
            Route::resource('support-tickets', \App\Http\Controllers\Customer\SupportTicketController::class)
                ->only(['index', 'create', 'store', 'show']);
            Route::post('support-tickets/{support_ticket}/reply', [\App\Http\Controllers\Customer\SupportTicketController::class, 'reply'])
                ->name('support-tickets.reply');
            Route::post('support-tickets/{support_ticket}/close', [\App\Http\Controllers\Customer\SupportTicketController::class, 'close'])
                ->name('support-tickets.close');
        });

        // Workspace Settings (active workspace from session)
        Route::prefix('workspace-settings')->name('workspace-settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'general'])->name('general');
            Route::put('/', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'updateGeneral'])->name('general.update');
            Route::get('/members', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'members'])->name('members');
            Route::post('/members/invite', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'inviteMember'])->name('members.invite');
            Route::put('/members/{member}/role', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'updateMemberRole'])->name('members.update-role');
            Route::delete('/members/{member}', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'removeMember'])->name('members.remove');
            Route::delete('/invitations/{invitation}', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'cancelInvitation'])->name('invitations.cancel');
            Route::get('/permissions', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'permissions'])->name('permissions');
            Route::post('/roles', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'storeRole'])->name('roles.store');
            Route::put('/roles/{role}', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'updateRole'])->name('roles.update');
            Route::delete('/roles/{role}', [\App\Http\Controllers\Customer\WorkspaceSettingsController::class, 'destroyRole'])->name('roles.destroy');
        });

        // Workspace & Teams Addon
        Route::prefix('workspaces')->name('workspaces.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\WorkspaceController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Customer\WorkspaceController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Customer\WorkspaceController::class, 'store'])->name('store');
            Route::post('/switch-personal', [\App\Http\Controllers\Customer\WorkspaceController::class, 'switchPersonal'])->name('switch-personal');
            Route::get('/{workspace}', [\App\Http\Controllers\Customer\WorkspaceController::class, 'show'])->name('show');
            Route::get('/{workspace}/edit', [\App\Http\Controllers\Customer\WorkspaceController::class, 'edit'])->name('edit');
            Route::put('/{workspace}', [\App\Http\Controllers\Customer\WorkspaceController::class, 'update'])->name('update');
            Route::post('/{workspace}/switch', [\App\Http\Controllers\Customer\WorkspaceController::class, 'switchWorkspace'])->name('switch');

            // Members
            Route::get('/{workspace}/members', [\App\Http\Controllers\Customer\WorkspaceMemberController::class, 'index'])->name('members.index');
            Route::post('/{workspace}/members/invite', [\App\Http\Controllers\Customer\WorkspaceMemberController::class, 'invite'])->name('members.invite');
            Route::put('/{workspace}/members/{member}/role', [\App\Http\Controllers\Customer\WorkspaceMemberController::class, 'updateRole'])->name('members.update-role');
            Route::delete('/{workspace}/members/{member}', [\App\Http\Controllers\Customer\WorkspaceMemberController::class, 'remove'])->name('members.remove');
            Route::delete('/{workspace}/invitations/{invitation}', [\App\Http\Controllers\Customer\WorkspaceMemberController::class, 'cancelInvitation'])->name('invitations.cancel');

            // Roles
            Route::get('/{workspace}/roles', [\App\Http\Controllers\Customer\WorkspaceRoleController::class, 'index'])->name('roles.index');
            Route::get('/{workspace}/roles/create', [\App\Http\Controllers\Customer\WorkspaceRoleController::class, 'create'])->name('roles.create');
            Route::post('/{workspace}/roles', [\App\Http\Controllers\Customer\WorkspaceRoleController::class, 'store'])->name('roles.store');
            Route::get('/{workspace}/roles/{role}/edit', [\App\Http\Controllers\Customer\WorkspaceRoleController::class, 'edit'])->name('roles.edit');
            Route::put('/{workspace}/roles/{role}', [\App\Http\Controllers\Customer\WorkspaceRoleController::class, 'update'])->name('roles.update');
            Route::delete('/{workspace}/roles/{role}', [\App\Http\Controllers\Customer\WorkspaceRoleController::class, 'destroy'])->name('roles.destroy');
        });

        // CRM (Workspace Addon)
        Route::prefix('crm')->name('crm.')->middleware('customer.access:crm.permissions.can_access_crm')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Customer\Crm\CrmDashboardController::class, 'index'])->name('dashboard');

            // Contacts
            Route::get('/contacts', [\App\Http\Controllers\Customer\Crm\CrmContactController::class, 'index'])->name('contacts.index');
            Route::get('/contacts/create', [\App\Http\Controllers\Customer\Crm\CrmContactController::class, 'create'])->name('contacts.create');
            Route::post('/contacts', [\App\Http\Controllers\Customer\Crm\CrmContactController::class, 'store'])->name('contacts.store');
            Route::get('/contacts/{contact}', [\App\Http\Controllers\Customer\Crm\CrmContactController::class, 'show'])->name('contacts.show');
            Route::get('/contacts/{contact}/edit', [\App\Http\Controllers\Customer\Crm\CrmContactController::class, 'edit'])->name('contacts.edit');
            Route::put('/contacts/{contact}', [\App\Http\Controllers\Customer\Crm\CrmContactController::class, 'update'])->name('contacts.update');
            Route::delete('/contacts/{contact}', [\App\Http\Controllers\Customer\Crm\CrmContactController::class, 'destroy'])->name('contacts.destroy');
            Route::post('/contacts/{contact}/link-subscriber', [\App\Http\Controllers\Customer\Crm\CrmContactController::class, 'linkSubscriber'])->name('contacts.link-subscriber');
            Route::delete('/contacts/{contact}/unlink-subscriber/{subscriberId}', [\App\Http\Controllers\Customer\Crm\CrmContactController::class, 'unlinkSubscriber'])->name('contacts.unlink-subscriber');

            // Companies
            Route::get('/companies', [\App\Http\Controllers\Customer\Crm\CrmCompanyController::class, 'index'])->name('companies.index');
            Route::get('/companies/create', [\App\Http\Controllers\Customer\Crm\CrmCompanyController::class, 'create'])->name('companies.create');
            Route::post('/companies', [\App\Http\Controllers\Customer\Crm\CrmCompanyController::class, 'store'])->name('companies.store');
            Route::get('/companies/{company}', [\App\Http\Controllers\Customer\Crm\CrmCompanyController::class, 'show'])->name('companies.show');
            Route::get('/companies/{company}/edit', [\App\Http\Controllers\Customer\Crm\CrmCompanyController::class, 'edit'])->name('companies.edit');
            Route::put('/companies/{company}', [\App\Http\Controllers\Customer\Crm\CrmCompanyController::class, 'update'])->name('companies.update');
            Route::delete('/companies/{company}', [\App\Http\Controllers\Customer\Crm\CrmCompanyController::class, 'destroy'])->name('companies.destroy');

            // Pipelines
            Route::get('/pipelines', [\App\Http\Controllers\Customer\Crm\CrmPipelineController::class, 'index'])->name('pipelines.index');
            Route::get('/pipelines/create', [\App\Http\Controllers\Customer\Crm\CrmPipelineController::class, 'create'])->name('pipelines.create');
            Route::post('/pipelines', [\App\Http\Controllers\Customer\Crm\CrmPipelineController::class, 'store'])->name('pipelines.store');
            Route::get('/pipelines/{pipeline}', [\App\Http\Controllers\Customer\Crm\CrmPipelineController::class, 'show'])->name('pipelines.show');
            Route::get('/pipelines/{pipeline}/edit', [\App\Http\Controllers\Customer\Crm\CrmPipelineController::class, 'edit'])->name('pipelines.edit');
            Route::put('/pipelines/{pipeline}', [\App\Http\Controllers\Customer\Crm\CrmPipelineController::class, 'update'])->name('pipelines.update');
            Route::delete('/pipelines/{pipeline}', [\App\Http\Controllers\Customer\Crm\CrmPipelineController::class, 'destroy'])->name('pipelines.destroy');

            // Deals
            Route::get('/deals', [\App\Http\Controllers\Customer\Crm\CrmDealController::class, 'index'])->name('deals.index');
            Route::get('/deals/create', [\App\Http\Controllers\Customer\Crm\CrmDealController::class, 'create'])->name('deals.create');
            Route::post('/deals', [\App\Http\Controllers\Customer\Crm\CrmDealController::class, 'store'])->name('deals.store');
            Route::get('/deals/{deal}', [\App\Http\Controllers\Customer\Crm\CrmDealController::class, 'show'])->name('deals.show');
            Route::get('/deals/{deal}/edit', [\App\Http\Controllers\Customer\Crm\CrmDealController::class, 'edit'])->name('deals.edit');
            Route::put('/deals/{deal}', [\App\Http\Controllers\Customer\Crm\CrmDealController::class, 'update'])->name('deals.update');
            Route::delete('/deals/{deal}', [\App\Http\Controllers\Customer\Crm\CrmDealController::class, 'destroy'])->name('deals.destroy');
            Route::post('/deals/{deal}/move-stage', [\App\Http\Controllers\Customer\Crm\CrmDealController::class, 'moveStage'])->name('deals.move-stage');

            // Tasks
            Route::get('/tasks', [\App\Http\Controllers\Customer\Crm\CrmTaskController::class, 'index'])->name('tasks.index');
            Route::get('/tasks/create', [\App\Http\Controllers\Customer\Crm\CrmTaskController::class, 'create'])->name('tasks.create');
            Route::post('/tasks', [\App\Http\Controllers\Customer\Crm\CrmTaskController::class, 'store'])->name('tasks.store');
            Route::get('/tasks/{task}/edit', [\App\Http\Controllers\Customer\Crm\CrmTaskController::class, 'edit'])->name('tasks.edit');
            Route::put('/tasks/{task}', [\App\Http\Controllers\Customer\Crm\CrmTaskController::class, 'update'])->name('tasks.update');
            Route::delete('/tasks/{task}', [\App\Http\Controllers\Customer\Crm\CrmTaskController::class, 'destroy'])->name('tasks.destroy');
            Route::post('/tasks/{task}/toggle-status', [\App\Http\Controllers\Customer\Crm\CrmTaskController::class, 'toggleStatus'])->name('tasks.toggle-status');

            // Notes
            Route::post('/notes', [\App\Http\Controllers\Customer\Crm\CrmNoteController::class, 'store'])->name('notes.store');
            Route::delete('/notes/{note}', [\App\Http\Controllers\Customer\Crm\CrmNoteController::class, 'destroy'])->name('notes.destroy');
        });

        // UniBox Addon
        Route::prefix('unibox')->name('unibox.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\UniboxController::class, 'index'])->name('index');
            Route::get('/conversations', [\App\Http\Controllers\Customer\UniboxController::class, 'conversations'])->name('conversations');

            // Reply Templates (must be before /{conversation} to avoid model binding conflicts)
            Route::get('/reply-templates', [\App\Http\Controllers\Customer\UniboxController::class, 'replyTemplates'])->name('reply-templates.index');
            Route::post('/reply-templates', [\App\Http\Controllers\Customer\UniboxController::class, 'storeReplyTemplate'])->name('reply-templates.store');
            Route::delete('/reply-templates/{template}', [\App\Http\Controllers\Customer\UniboxController::class, 'destroyReplyTemplate'])->name('reply-templates.destroy');

            // Accounts (must be before /{conversation} to avoid model binding conflicts)
            Route::prefix('accounts')->name('accounts.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Customer\UniboxAccountController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Customer\UniboxAccountController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Customer\UniboxAccountController::class, 'store'])->name('store');
                Route::get('/{account}/edit', [\App\Http\Controllers\Customer\UniboxAccountController::class, 'edit'])->name('edit');
                Route::put('/{account}', [\App\Http\Controllers\Customer\UniboxAccountController::class, 'update'])->name('update');
                Route::patch('/{account}/rename', [\App\Http\Controllers\Customer\UniboxAccountController::class, 'rename'])->name('rename');
                Route::delete('/{account}', [\App\Http\Controllers\Customer\UniboxAccountController::class, 'destroy'])->name('destroy');
                Route::post('/{account}/sync', [\App\Http\Controllers\Customer\UniboxAccountController::class, 'sync'])->name('sync');
            });

            Route::get('/{conversation}', [\App\Http\Controllers\Customer\UniboxController::class, 'show'])->name('show');
            Route::post('/{conversation}/reply', [\App\Http\Controllers\Customer\UniboxController::class, 'reply'])->name('reply');
            Route::post('/{conversation}/notes', [\App\Http\Controllers\Customer\UniboxController::class, 'addNote'])->name('notes.store');
            Route::delete('/notes/{note}', [\App\Http\Controllers\Customer\UniboxController::class, 'deleteNote'])->name('notes.destroy');
            Route::patch('/{conversation}', [\App\Http\Controllers\Customer\UniboxController::class, 'update'])->name('update');
            Route::post('/{conversation}/toggle-folder', [\App\Http\Controllers\Customer\UniboxController::class, 'toggleFolder'])->name('toggle-folder');
            Route::post('/{conversation}/toggle-tag', [\App\Http\Controllers\Customer\UniboxController::class, 'toggleTag'])->name('toggle-tag');
            Route::post('/{conversation}/assign', [\App\Http\Controllers\Customer\UniboxController::class, 'assign'])->name('assign');
            Route::post('/compose', [\App\Http\Controllers\Customer\UniboxController::class, 'compose'])->name('compose');
            Route::post('/sync', [\App\Http\Controllers\Customer\UniboxController::class, 'sync'])->name('sync');
            Route::patch('/contacts/{contact}', [\App\Http\Controllers\Customer\UniboxController::class, 'updateContact'])->name('contacts.update');
            Route::post('/contacts/{contact}/avatar', [\App\Http\Controllers\Customer\UniboxController::class, 'uploadContactAvatar'])->name('contacts.avatar');
            Route::post('/contacts/add-to-list', [\App\Http\Controllers\Customer\UniboxController::class, 'addContactToList'])->name('contacts.add-to-list');
            Route::post('/contacts/add-to-sequence', [\App\Http\Controllers\Customer\UniboxController::class, 'addContactToSequence'])->name('contacts.add-to-sequence');

            // Folders
            Route::post('/folders', [\App\Http\Controllers\Customer\UniboxFolderTagController::class, 'storeFolder'])->name('folders.store');
            Route::patch('/folders/{folder}', [\App\Http\Controllers\Customer\UniboxFolderTagController::class, 'updateFolder'])->name('folders.update');
            Route::delete('/folders/{folder}', [\App\Http\Controllers\Customer\UniboxFolderTagController::class, 'destroyFolder'])->name('folders.destroy');

            // Tags
            Route::post('/tags', [\App\Http\Controllers\Customer\UniboxFolderTagController::class, 'storeTag'])->name('tags.store');
            Route::patch('/tags/{tag}', [\App\Http\Controllers\Customer\UniboxFolderTagController::class, 'updateTag'])->name('tags.update');
            Route::delete('/tags/{tag}', [\App\Http\Controllers\Customer\UniboxFolderTagController::class, 'destroyTag'])->name('tags.destroy');
        });
    });
});

// Public Routes
Route::get('/', [\App\Http\Controllers\PublicController::class, 'home'])->name('home');
Route::get('/home/{variant}', [\App\Http\Controllers\PublicController::class, 'homeVariant'])
    ->where('variant', '[1-5]')
    ->name('home.variant');
Route::get('/features', [\App\Http\Controllers\PublicController::class, 'features'])->name('features');
Route::get('/pricing', [\App\Http\Controllers\PublicController::class, 'pricing'])->name('pricing');
Route::get('/pages/{slug}', [\App\Http\Controllers\PublicController::class, 'showAiBuilderPage'])->name('ai.pages.show');
Route::prefix('addons')->name('addons.')->group(function () {
    Route::get('/outreach', [\App\Http\Controllers\AddonLandingController::class, 'outreach'])->name('outreach');
    Route::get('/unibox', [\App\Http\Controllers\AddonLandingController::class, 'unibox'])->name('unibox');
    Route::get('/workspace', [\App\Http\Controllers\AddonLandingController::class, 'workspace'])->name('workspace');
    Route::get('/sms', [\App\Http\Controllers\AddonLandingController::class, 'sms'])->name('sms');
    Route::get('/whatsapp', [\App\Http\Controllers\AddonLandingController::class, 'whatsapp'])->name('whatsapp');
    Route::get('/ai-assistant', [\App\Http\Controllers\AddonLandingController::class, 'aiAssistant'])->name('ai-assistant');
});
Route::get('/pricing/checkout/{slug}', [\App\Http\Controllers\PublicController::class, 'pricingCheckout'])
    ->name('pricing.checkout');
Route::get('/docs', [\App\Http\Controllers\PublicController::class, 'docs'])->name('docs');
 Route::get('/api-docs', [\App\Http\Controllers\PublicController::class, 'apiDocs'])->name('api.docs.public');
 Route::get('/roadmap', [\App\Http\Controllers\PublicController::class, 'roadmap'])->name('roadmap');
 Route::get('/openapi', \App\Http\Controllers\Api\OpenApiController::class)->name('openapi');
Route::get('/openapi.json', \App\Http\Controllers\Api\OpenApiController::class)->name('openapi.json');

// Visitor Tracking (Public API — no CSRF)
Route::post('/visitor-tracking/event', [\App\Http\Controllers\VisitorTrackingApiController::class, 'event'])
    ->name('visitor-tracking.event')
    ->middleware('throttle:60,1');
Route::post('/visitor-tracking/dwell', [\App\Http\Controllers\VisitorTrackingApiController::class, 'dwell'])
    ->name('visitor-tracking.dwell')
    ->middleware('throttle:60,1');

// Workspace Invitation (Public)
Route::get('/workspace/invite/{token}', [\App\Http\Controllers\Customer\WorkspaceInvitationController::class, 'accept'])
    ->name('workspace.invite.accept');
Route::post('/workspace/invite/{token}', [\App\Http\Controllers\Customer\WorkspaceInvitationController::class, 'acceptAndRegister'])
    ->name('workspace.invite.register');

Route::get('/page/preview/{slug}', [\App\Http\Controllers\PublicPageController::class, 'show'])->name('pages.show');

// Public Blog
Route::get('/blog', [PublicBlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [PublicBlogController::class, 'show'])->name('blog.show');

// Public Subscription Forms
Route::get('/subscribe/{slug}', [\App\Http\Controllers\PublicSubscriptionController::class, 'show'])->name('public.subscribe');
Route::get('/subscribe/{slug}/popup.js', [\App\Http\Controllers\PublicSubscriptionController::class, 'popupScript'])->name('public.subscribe.popup_js');
Route::get('/subscribe/{slug}/popup', [\App\Http\Controllers\PublicSubscriptionController::class, 'popupScript'])->name('public.subscribe.popup');
Route::post('/subscribe/{slug}', [\App\Http\Controllers\PublicSubscriptionController::class, 'subscribe'])
    ->name('public.subscribe.store')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
    ->middleware('throttle:30,1');
// API endpoint - excluded from CSRF in middleware
Route::post('/subscribe/{slug}/api', [\App\Http\Controllers\PublicSubscriptionController::class, 'subscribe'])
    ->name('public.subscribe.api')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
    ->middleware('throttle:30,1');

// Public Subscription Actions
Route::get('/subscribe/confirm/{token}', [\App\Http\Controllers\PublicSubscriptionController::class, 'confirm'])->name('public.subscribe.confirm');
Route::get('/unsubscribe/{list}/{email}/{token}', [\App\Http\Controllers\PublicSubscriptionController::class, 'unsubscribe'])->name('public.unsubscribe');

// Legacy Campaign Tracking Routes (Public) - kept for backward compatibility
Route::get('/track/open/{uuid}', [\App\Http\Controllers\TrackingController::class, 'trackOpen'])->name('track.open.legacy');
Route::get('/track/click/{uuid}/{url}', [\App\Http\Controllers\TrackingController::class, 'trackClick'])->name('track.click.legacy');

// Cold-email Outreach Tracking Routes (Public)
Route::get('/outreach/track/open/{uuid}', [\App\Http\Controllers\Public\OutreachTrackingController::class, 'open'])
    ->name('outreach.track.open');
Route::get('/outreach/track/click/{uuid}/{url}', [\App\Http\Controllers\Public\OutreachTrackingController::class, 'click'])
    ->name('outreach.track.click')
    ->where('url', '.*');
Route::get('/outreach/unsubscribe/{uuid}', [\App\Http\Controllers\Public\OutreachTrackingController::class, 'showUnsubscribe'])
    ->name('outreach.unsubscribe');
Route::post('/outreach/unsubscribe/{uuid}', [\App\Http\Controllers\Public\OutreachTrackingController::class, 'unsubscribe'])
    ->name('outreach.unsubscribe.confirm');

// New hash-based tracking routes
Route::get('/t/open/{campaign}/{subscriber}', [\App\Http\Controllers\Tracking\V2TrackingController::class, 'open'])->name('track.open');
Route::get('/t/click/{campaign}/{subscriber}', [\App\Http\Controllers\Tracking\V2TrackingController::class, 'click'])->name('track.click');
Route::get('/unsubscribe/{uuid}', [\App\Http\Controllers\PublicSubscriptionController::class, 'unsubscribeByUuid'])->name('unsubscribe');

Route::post('/ses/sns', [\App\Http\Controllers\Webhook\SesWebhookController::class, 'handle'])
    ->name('ses.sns')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('throttle:60,1');

// Webhook Routes (No CSRF protection)
Route::prefix('webhooks')->name('webhooks.')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::post('/mailgun', [\App\Http\Controllers\Webhook\MailgunWebhookController::class, 'handle'])->name('mailgun');
    Route::post('/mailgun/bounce', [\App\Http\Controllers\Webhook\MailgunWebhookController::class, 'handle'])->name('mailgun.bounce');
    Route::post('/mailgun/open', [\App\Http\Controllers\Webhook\MailgunWebhookController::class, 'handle'])->name('mailgun.open');
    Route::post('/mailgun/click', [\App\Http\Controllers\Webhook\MailgunWebhookController::class, 'handle'])->name('mailgun.click');

    Route::post('/ses', [\App\Http\Controllers\Webhook\SesWebhookController::class, 'handle'])->name('ses');
    Route::post('/ses/bounce', [\App\Http\Controllers\Webhook\SesWebhookController::class, 'handle'])->name('ses.bounce');
    Route::post('/ses/open', [\App\Http\Controllers\Webhook\SesWebhookController::class, 'handle'])->name('ses.open');
    Route::post('/ses/click', [\App\Http\Controllers\Webhook\SesWebhookController::class, 'handle'])->name('ses.click');

    Route::post('/sendgrid', [\App\Http\Controllers\Webhook\SendGridWebhookController::class, 'handle'])->name('sendgrid');
    Route::post('/sendgrid/bounce', [\App\Http\Controllers\Webhook\SendGridWebhookController::class, 'handle'])->name('sendgrid.bounce');
    Route::post('/sendgrid/open', [\App\Http\Controllers\Webhook\SendGridWebhookController::class, 'handle'])->name('sendgrid.open');
    Route::post('/sendgrid/click', [\App\Http\Controllers\Webhook\SendGridWebhookController::class, 'handle'])->name('sendgrid.click');

    Route::post('/resend', [\App\Http\Controllers\Webhook\ResendWebhookController::class, 'handle'])->name('resend');
    Route::post('/resend/bounce', [\App\Http\Controllers\Webhook\ResendWebhookController::class, 'handle'])->name('resend.bounce');
    Route::post('/resend/open', [\App\Http\Controllers\Webhook\ResendWebhookController::class, 'handle'])->name('resend.open');
    Route::post('/resend/click', [\App\Http\Controllers\Webhook\ResendWebhookController::class, 'handle'])->name('resend.click');
    Route::post('/resend/delivered', [\App\Http\Controllers\Webhook\ResendWebhookController::class, 'handle'])->name('resend.delivered');

    Route::post('/stripe', [\App\Http\Controllers\Webhook\WebhookController::class, 'handleStripe'])->name('stripe');
    Route::post('/flutterwave', [\App\Http\Controllers\Webhook\FlutterwaveWebhookController::class, 'handle'])->name('flutterwave');
    Route::post('/uddoktapay', [\App\Http\Controllers\Webhook\UddoktaPayWebhookController::class, 'handle'])->name('uddoktapay');
    Route::post('/razorpay', [\App\Http\Controllers\Webhook\RazorpayWebhookController::class, 'handle'])->name('razorpay');
    Route::post('/paypal', [\App\Http\Controllers\Webhook\PayPalWebhookController::class, 'handle'])->name('paypal');

    Route::post('/automations/{automation}', [\App\Http\Controllers\Webhook\AutomationWebhookController::class, 'handle'])
        ->name('automations.handle')
        ->middleware('throttle:60,1');

    // SMS (Twilio) addon
    Route::post('/sms/twilio', [\App\Http\Controllers\Webhook\SmsWebhookController::class, 'twilio'])
        ->name('sms.twilio')
        ->middleware('throttle:120,1');

    // WhatsApp (Meta) addon
    Route::get('/whatsapp', [\App\Http\Controllers\Webhook\WhatsAppWebhookController::class, 'verify'])
        ->name('whatsapp.verify');
    Route::post('/whatsapp', [\App\Http\Controllers\Webhook\WhatsAppWebhookController::class, 'handle'])
        ->name('whatsapp.handle');
});

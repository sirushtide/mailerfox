<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkipInstallWizard($request) || is_file(storage_path('app/private/installed.json'))) {
            return redirect()->route('login');
        }

        return $next($request);
    }

    private function shouldSkipInstallWizard(Request $request): bool
    {
        return (bool) config('mailpurse.skip_install_wizard', false)
            || in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    }
}

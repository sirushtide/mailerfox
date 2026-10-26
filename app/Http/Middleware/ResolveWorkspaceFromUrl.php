<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware: detects /customer/w/{slug}/... URLs, strips the /w/{slug}
 * segment so existing routes match, and stores the slug in request attributes
 * for EnsureWorkspaceContext to resolve later (with session access).
 */
class ResolveWorkspaceFromUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if (!preg_match('#^/customer/w/([a-zA-Z0-9_-]+)(/.*)?$#', $path, $matches)) {
            return $next($request);
        }

        $slug = $matches[1];
        $rest = $matches[2] ?? '/dashboard';

        // DEBUG: remove after confirming
        \Log::info('[WS-URL] matched', ['path' => $path, 'slug' => $slug, 'rest' => $rest]);

        // Build rewritten URI
        $newPath = '/customer' . $rest;
        $qs = $request->getQueryString();
        $newUri = $newPath . ($qs ? '?' . $qs : '');

        // Build a new server bag with the rewritten URI
        $server = $request->server->all();
        $server['REQUEST_URI'] = $newUri;

        // Create a fresh request with the rewritten path (clears cached pathInfo)
        $newRequest = $request->duplicate(null, null, null, null, null, $server);

        // Store slug in request attributes — EnsureWorkspaceContext will resolve it
        $newRequest->attributes->set('workspace_url_slug', $slug);

        // Replace the request in the container so downstream code sees the rewrite
        app()->instance('request', $newRequest);

        return $next($newRequest);
    }
}

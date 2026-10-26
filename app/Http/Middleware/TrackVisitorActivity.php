<?php

namespace App\Http\Middleware;

use App\Services\VisitorTrackingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TrackVisitorActivity
{
    public function __construct(
        private readonly VisitorTrackingService $trackingService
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        try {
            if (
                $request->method() === 'GET'
                && !$request->ajax()
                && !$request->wantsJson()
                && !str_starts_with($request->path(), 'api/')
                && !str_starts_with($request->path(), 'visitor-tracking/')
                && !str_starts_with($request->path(), 'livewire/')
                && Schema::hasTable('visitor_tracking_sessions')
            ) {
                $this->trackingService->logPageView($request);
            }
        } catch (\Throwable $e) {
            // Tracking should never break the request
        }

        return $response;
    }
}

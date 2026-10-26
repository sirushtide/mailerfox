<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\IpGeolocation;
use App\Models\VisitorTrackingEvent;
use App\Models\VisitorTrackingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class VisitorTrackingService
{
    public function __construct(
        private readonly IpGeolocationService $geoService
    ) {}

    public function getOrCreateSession(Request $request): ?VisitorTrackingSession
    {
        if (!Schema::hasTable('visitor_tracking_sessions')) {
            return null;
        }

        $sessionUid = $this->resolveSessionUid($request);
        $ip = (string) ($request->ip() ?? '');
        $userAgent = (string) ($request->userAgent() ?? '');

        $customer = $this->resolveCustomer($request);

        $session = VisitorTrackingSession::where('session_uid', $sessionUid)->first();

        if ($session) {
            $updates = [
                'last_activity_at' => now(),
            ];

            if ($customer && !$session->customer_id) {
                $updates['customer_id'] = $customer->id;
                $updates['is_guest'] = false;
            }

            $session->update($updates);
            return $session;
        }

        $geo = null;
        if ($ip !== '') {
            try {
                $geo = $this->geoService->lookup($ip);
            } catch (\Throwable $e) {
                $geo = null;
            }
        }

        return VisitorTrackingSession::create([
            'session_uid' => $sessionUid,
            'customer_id' => $customer?->id,
            'ip' => $ip !== '' ? $ip : null,
            'user_agent' => $userAgent !== '' ? $userAgent : null,
            'ip_geolocation_id' => $geo?->id,
            'country' => $geo?->country,
            'country_code' => $geo?->country_code,
            'city' => $geo?->city,
            'is_guest' => $customer === null,
            'started_at' => now(),
            'last_activity_at' => now(),
            'metadata' => [
                'referrer' => $request->header('referer'),
                'landing_page' => $request->fullUrl(),
            ],
        ]);
    }

    public function logPageView(Request $request): void
    {
        if (!Schema::hasTable('visitor_tracking_events')) {
            return;
        }

        $session = $this->getOrCreateSession($request);
        if (!$session) {
            return;
        }

        VisitorTrackingEvent::create([
            'visitor_tracking_session_id' => $session->id,
            'event_type' => 'page_view',
            'url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'method' => $request->method(),
            'created_at' => now(),
        ]);

        $session->increment('page_views_count');
    }

    public function logClick(Request $request, array $data): void
    {
        if (!Schema::hasTable('visitor_tracking_events')) {
            return;
        }

        $session = $this->getOrCreateSession($request);
        if (!$session) {
            return;
        }

        VisitorTrackingEvent::create([
            'visitor_tracking_session_id' => $session->id,
            'event_type' => 'click',
            'url' => $data['url'] ?? $request->fullUrl(),
            'element_label' => $data['label'] ?? null,
            'element_selector' => $data['selector'] ?? null,
            'element_track_id' => $data['track_id'] ?? null,
            'metadata' => !empty($data['meta']) ? $data['meta'] : null,
            'created_at' => now(),
        ]);

        $session->increment('clicks_count');
    }

    public function logDwell(Request $request, array $data): void
    {
        if (!Schema::hasTable('visitor_tracking_events')) {
            return;
        }

        $session = $this->getOrCreateSession($request);
        if (!$session) {
            return;
        }

        $dwellSeconds = (int) ($data['dwell_seconds'] ?? 0);
        if ($dwellSeconds <= 0) {
            return;
        }

        VisitorTrackingEvent::create([
            'visitor_tracking_session_id' => $session->id,
            'event_type' => 'dwell',
            'url' => $data['url'] ?? null,
            'duration_seconds' => $dwellSeconds,
            'created_at' => now(),
        ]);

        $session->increment('duration_seconds', $dwellSeconds);
    }

    public function endSession(string $sessionUid): void
    {
        $session = VisitorTrackingSession::where('session_uid', $sessionUid)->first();
        if ($session && !$session->ended_at) {
            $session->update(['ended_at' => now()]);
        }
    }

    private function resolveSessionUid(Request $request): string
    {
        $fromHeader = $request->header('X-Visitor-Session-Uid');
        if (is_string($fromHeader) && strlen($fromHeader) === 36) {
            return $fromHeader;
        }

        $fromBody = $request->input('session_uid');
        if (is_string($fromBody) && strlen($fromBody) === 36) {
            return $fromBody;
        }

        return \Illuminate\Support\Str::uuid()->toString();
    }

    private function resolveCustomer(Request $request): ?Customer
    {
        $customer = $request->user('customer');
        if ($customer instanceof Customer) {
            return $customer;
        }

        return null;
    }
}

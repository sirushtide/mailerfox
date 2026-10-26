<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\VisitorTrackingSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitorTrackingController extends Controller
{
    public function index(Request $request)
    {
        $range = (string) $request->query('range', '7d');
        $now = now();

        $startDate = match ($range) {
            '24h' => $now->copy()->subDay(),
            '30d' => $now->copy()->subDays(29)->startOfDay(),
            'custom' => $request->query('from')
                ? Carbon::parse($request->query('from'))->startOfDay()
                : $now->copy()->subDays(6)->startOfDay(),
            default => $now->copy()->subDays(6)->startOfDay(),
        };

        $endDate = $range === 'custom' && $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : $now->copy()->endOfDay();

        if ($startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
            $range = 'custom';
        }

        $query = VisitorTrackingSession::query()
            ->whereBetween('started_at', [$startDate, $endDate])
            ->with(['customer:id,email,name', 'ipGeolocation:id,country,city,country_code']);

        // Filters
        $filterType = (string) $request->query('filter_type', '');
        if ($filterType === 'guest') {
            $query->where('is_guest', true);
        } elseif ($filterType === 'customer') {
            $query->where('is_guest', false);
        }

        $filterCountry = (string) $request->query('country', '');
        if ($filterCountry !== '') {
            $query->where('country_code', $filterCountry);
        }

        $search = (string) $request->query('search', '');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ip', 'like', "%{$search}%")
                    ->orWhere('session_uid', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('email', 'like', "%{$search}%");
                    });
            });
        }

        $sessions = $query->orderByDesc('last_activity_at')->paginate(25)->withQueryString();

        // Summary stats
        $stats = $this->getSummaryStats($startDate, $endDate);

        // Countries for filter dropdown
        $countries = VisitorTrackingSession::query()
            ->whereNotNull('country_code')
            ->whereBetween('started_at', [$startDate, $endDate])
            ->select('country_code', DB::raw('COUNT(*) as cnt'))
            ->groupBy('country_code')
            ->orderByDesc('cnt')
            ->limit(50)
            ->pluck('country_code', 'country_code')
            ->filter()
            ->toArray();

        return view('admin.visitor-tracking.index', [
            'range' => $range,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sessions' => $sessions,
            'stats' => $stats,
            'countries' => $countries,
            'filterType' => $filterType,
            'filterCountry' => $filterCountry,
            'search' => $search,
        ]);
    }

    public function show(Request $request, VisitorTrackingSession $session)
    {
        $session->load(['customer:id,email,name', 'ipGeolocation']);

        $events = $session->events()->orderBy('created_at')->paginate(50);

        // Aggregated click data
        $clickSummary = $session->events()
            ->where('event_type', 'click')
            ->select('element_track_id', 'element_label', DB::raw('COUNT(*) as cnt'))
            ->groupBy('element_track_id', 'element_label')
            ->orderByDesc('cnt')
            ->get();

        // Page flow
        $pageFlow = $session->events()
            ->where('event_type', 'page_view')
            ->orderBy('created_at')
            ->get(['url', 'route_name', 'created_at']);

        return view('admin.visitor-tracking.show', [
            'session' => $session,
            'events' => $events,
            'clickSummary' => $clickSummary,
            'pageFlow' => $pageFlow,
        ]);
    }

    public function destroy(Request $request, VisitorTrackingSession $session)
    {
        $session->delete();

        return redirect()->route('admin.visitor-tracking.index')
            ->with('success', __('Visitor session deleted.'));
    }

    public function customerJourney(int $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $sessions = VisitorTrackingSession::where('customer_id', $customerId)
            ->with(['ipGeolocation:id,country,city,country_code'])
            ->orderBy('started_at')
            ->get();

        if ($sessions->isEmpty()) {
            return view('admin.visitor-tracking.customer-journey', [
                'customer' => $customer,
                'sessions' => collect(),
                'allEvents' => collect(),
                'pageFlow' => collect(),
                'clickSummary' => collect(),
                'totalStats' => [
                    'sessions' => 0,
                    'page_views' => 0,
                    'clicks' => 0,
                    'duration' => 0,
                    'first_visit' => null,
                    'last_visit' => null,
                ],
            ]);
        }

        // Merge all events across sessions, chronologically
        $allEvents = collect();
        foreach ($sessions as $session) {
            $sessionEvents = $session->events()->orderBy('created_at')->get();
            foreach ($sessionEvents as $event) {
                $event->session_started_at = $session->started_at;
                $allEvents->push($event);
            }
        }
        $allEvents = $allEvents->sortBy('created_at')->values();

        // Page flow across all sessions
        $pageFlow = $allEvents->where('event_type', 'page_view')
            ->values();

        // Click summary across all sessions
        $clickSummary = $allEvents->where('event_type', 'click')
            ->groupBy('element_track_id')
            ->map(function ($clicks) {
                return (object) [
                    'element_track_id' => $clicks->first()->element_track_id,
                    'element_label' => $clicks->first()->element_label,
                    'cnt' => $clicks->count(),
                ];
            })
            ->sortByDesc('cnt')
            ->values();

        $totalStats = [
            'sessions' => $sessions->count(),
            'page_views' => $sessions->sum('page_views_count'),
            'clicks' => $sessions->sum('clicks_count'),
            'duration' => $sessions->sum('duration_seconds'),
            'first_visit' => $sessions->first()->started_at,
            'last_visit' => $sessions->last()->last_activity_at,
        ];

        return view('admin.visitor-tracking.customer-journey', [
            'customer' => $customer,
            'sessions' => $sessions,
            'allEvents' => $allEvents,
            'pageFlow' => $pageFlow,
            'clickSummary' => $clickSummary,
            'totalStats' => $totalStats,
        ]);
    }

    public function summary(Request $request)
    {
        $now = now();
        $today = $now->copy()->startOfDay();

        $activeNow = VisitorTrackingSession::query()
            ->where('last_activity_at', '>=', $now->copy()->subMinutes(5))
            ->whereNull('ended_at')
            ->count();

        $todaySessions = VisitorTrackingSession::query()
            ->where('started_at', '>=', $today)
            ->count();

        $todayClicks = VisitorTrackingSession::query()
            ->where('started_at', '>=', $today)
            ->sum('clicks_count');

        $avgDuration = VisitorTrackingSession::query()
            ->where('started_at', '>=', $today)
            ->avg('duration_seconds') ?? 0;

        $topPages = DB::table('visitor_tracking_events')
            ->join('visitor_tracking_sessions', 'visitor_tracking_events.visitor_tracking_session_id', '=', 'visitor_tracking_sessions.id')
            ->where('visitor_tracking_sessions.started_at', '>=', $today)
            ->where('visitor_tracking_events.event_type', 'page_view')
            ->select('visitor_tracking_events.url', DB::raw('COUNT(*) as cnt'))
            ->groupBy('visitor_tracking_events.url')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        $topClicks = DB::table('visitor_tracking_events')
            ->join('visitor_tracking_sessions', 'visitor_tracking_events.visitor_tracking_session_id', '=', 'visitor_tracking_sessions.id')
            ->where('visitor_tracking_sessions.started_at', '>=', $today)
            ->where('visitor_tracking_events.event_type', 'click')
            ->select('visitor_tracking_events.element_track_id', 'visitor_tracking_events.element_label', DB::raw('COUNT(*) as cnt'))
            ->groupBy('visitor_tracking_events.element_track_id', 'visitor_tracking_events.element_label')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        return response()->json([
            'active_now' => $activeNow,
            'today_sessions' => $todaySessions,
            'today_clicks' => $todayClicks,
            'avg_duration' => (int) $avgDuration,
            'top_pages' => $topPages,
            'top_clicks' => $topClicks,
        ]);
    }

    private function getSummaryStats(Carbon $startDate, Carbon $endDate): array
    {
        $totalSessions = VisitorTrackingSession::query()
            ->whereBetween('started_at', [$startDate, $endDate])
            ->count();

        $guestSessions = VisitorTrackingSession::query()
            ->whereBetween('started_at', [$startDate, $endDate])
            ->where('is_guest', true)
            ->count();

        $customerSessions = VisitorTrackingSession::query()
            ->whereBetween('started_at', [$startDate, $endDate])
            ->where('is_guest', false)
            ->count();

        $totalPageViews = VisitorTrackingSession::query()
            ->whereBetween('started_at', [$startDate, $endDate])
            ->sum('page_views_count');

        $totalClicks = VisitorTrackingSession::query()
            ->whereBetween('started_at', [$startDate, $endDate])
            ->sum('clicks_count');

        $avgDuration = VisitorTrackingSession::query()
            ->whereBetween('started_at', [$startDate, $endDate])
            ->avg('duration_seconds') ?? 0;

        $uniqueCountries = VisitorTrackingSession::query()
            ->whereBetween('started_at', [$startDate, $endDate])
            ->whereNotNull('country_code')
            ->distinct('country_code')
            ->count('country_code');

        return [
            'total_sessions' => $totalSessions,
            'guest_sessions' => $guestSessions,
            'customer_sessions' => $customerSessions,
            'total_page_views' => $totalPageViews,
            'total_clicks' => $totalClicks,
            'avg_duration' => (int) $avgDuration,
            'unique_countries' => $uniqueCountries,
        ];
    }
}

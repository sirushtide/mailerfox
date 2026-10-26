<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\AutomationRunLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationAnalyticsController extends Controller
{
    protected function authorizeOwnership(Automation $automation): Automation
    {
        $customerId = auth('customer')->id();

        if (!$customerId || (int) $automation->customer_id !== (int) $customerId) {
            abort(404);
        }

        return $automation;
    }

    public function show(Automation $automation): JsonResponse
    {
        $this->authorizeOwnership($automation);

        $overview = [
            'total' => AutomationRun::query()->where('automation_id', $automation->id)->count(),
            'active' => AutomationRun::query()->where('automation_id', $automation->id)->where('status', 'active')->count(),
            'completed' => AutomationRun::query()->where('automation_id', $automation->id)->where('status', 'completed')->count(),
            'stopped' => AutomationRun::query()->where('automation_id', $automation->id)->where('status', 'stopped')->count(),
        ];

        $totalStarted = max(1, $overview['total']);
        $overview['conversion_rate'] = $overview['completed'] > 0
            ? round(($overview['completed'] / $totalStarted) * 100, 1)
            : 0;

        // Per-node funnel counts
        $nodeCounts = AutomationRunLog::query()
            ->selectRaw('node_id, node_type, COUNT(DISTINCT automation_run_id) as unique_runs, COUNT(*) as total_entries')
            ->where('automation_id', $automation->id)
            ->groupBy('node_id', 'node_type')
            ->get()
            ->map(fn ($row) => [
                'node_id' => $row->node_id,
                'node_type' => $row->node_type,
                'unique_runs' => (int) $row->unique_runs,
                'total_entries' => (int) $row->total_entries,
            ])
            ->keyBy('node_id')
            ->toArray();

        // Build funnel from graph nodes
        $graph = (array) ($automation->graph ?? []);
        $nodes = (array) ($graph['nodes'] ?? []);
        $funnel = [];
        foreach ($nodes as $n) {
            if (!is_array($n) || !isset($n['id'])) {
                continue;
            }
            $id = (string) $n['id'];
            $funnel[] = [
                'node_id' => $id,
                'label' => (string) ($n['label'] ?? $id),
                'type' => (string) ($n['type'] ?? ''),
                'unique_runs' => (int) ($nodeCounts[$id]['unique_runs'] ?? 0),
                'total_entries' => (int) ($nodeCounts[$id]['total_entries'] ?? 0),
            ];
        }

        // Daily runs over last 30 days
        $dailyRuns = AutomationRun::query()
            ->selectRaw('DATE(triggered_at) as date, COUNT(*) as count')
            ->where('automation_id', $automation->id)
            ->where('triggered_at', '>=', now()->subDays(30))
            ->groupByRaw('DATE(triggered_at)')
            ->orderByRaw('DATE(triggered_at)')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'count' => (int) $row->count,
            ]);

        return response()->json([
            'overview' => $overview,
            'funnel' => $funnel,
            'daily_runs' => $dailyRuns,
        ]);
    }
}

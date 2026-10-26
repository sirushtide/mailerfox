<?php

namespace App\Http\Controllers\Customer\Crm;

use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmDeal;
use App\Models\Crm\CrmTask;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmDashboardController extends CrmBaseController
{
    public function index(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.dashboard.view');

        $stats = [
            'contacts' => CrmContact::where('workspace_id', $workspace->id)->count(),
            'deals_total_value' => CrmDeal::where('workspace_id', $workspace->id)->sum('value'),
            'open_tasks' => CrmTask::where('workspace_id', $workspace->id)
                ->where('status', '!=', 'done')
                ->where('status', '!=', 'cancelled')
                ->count(),
            'won_deals' => CrmDeal::where('workspace_id', $workspace->id)->where('status', 'won')->count(),
            'lost_deals' => CrmDeal::where('workspace_id', $workspace->id)->where('status', 'lost')->count(),
            'open_deals' => CrmDeal::where('workspace_id', $workspace->id)->where('status', 'open')->count(),
        ];

        $recentContacts = CrmContact::where('workspace_id', $workspace->id)
            ->with('company')
            ->latest()
            ->limit(5)
            ->get();

        $upcomingTasks = CrmTask::where('workspace_id', $workspace->id)
            ->with(['deal', 'contact', 'assignee'])
            ->where('status', '!=', 'done')
            ->where('status', '!=', 'cancelled')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $recentDeals = CrmDeal::where('workspace_id', $workspace->id)
            ->with(['contact', 'stage', 'pipeline'])
            ->latest()
            ->limit(5)
            ->get();

        return view('customer.crm.dashboard', compact(
            'workspace', 'member', 'stats', 'recentContacts', 'upcomingTasks', 'recentDeals'
        ));
    }
}

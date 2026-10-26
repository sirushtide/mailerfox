<?php

namespace App\Http\Controllers\Customer\Crm;

use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmDeal;
use App\Models\Crm\CrmDealActivity;
use App\Models\Crm\CrmPipeline;
use App\Models\Crm\CrmPipelineStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmDealController extends CrmBaseController
{
    public function index(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.deals.view');

        $query = CrmDeal::where('workspace_id', $workspace->id)
            ->with(['contact', 'stage', 'pipeline', 'owner'])
            ->latest();

        if ($request->filled('pipeline_id')) {
            $query->where('pipeline_id', $request->input('pipeline_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $deals = $query->paginate(20)->withQueryString();
        $pipelines = CrmPipeline::where('workspace_id', $workspace->id)->orderBy('name')->pluck('name', 'id');

        return view('customer.crm.deals.index', compact('workspace', 'member', 'deals', 'pipelines'));
    }

    public function create(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.deals.create');

        $pipelines = CrmPipeline::where('workspace_id', $workspace->id)
            ->with('stages')
            ->where('status', 'active')
            ->get();

        $contacts = CrmContact::where('workspace_id', $workspace->id)
            ->orderBy('first_name')
            ->get();

        return view('customer.crm.deals.create', compact('workspace', 'member', 'pipelines', 'contacts'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.deals.create');

        $validated = $request->validate([
            'title'               => 'required|string|max:200',
            'description'         => 'nullable|string|max:5000',
            'pipeline_id'         => 'required|exists:crm_pipelines,id',
            'stage_id'            => 'required|exists:crm_pipeline_stages,id',
            'contact_id'          => 'required|exists:crm_contacts,id',
            'value'               => 'nullable|numeric|min:0',
            'currency'            => 'nullable|string|max:3',
            'expected_close_date' => 'nullable|date',
        ]);

        $validated['workspace_id'] = $workspace->id;
        $validated['status'] = 'open';
        $validated['owner_id'] = $member?->id;

        $deal = CrmDeal::create($validated);

        CrmDealActivity::create([
            'workspace_id' => $workspace->id,
            'deal_id'      => $deal->id,
            'stage_id'     => $deal->stage_id,
            'performed_by' => $member?->id,
            'type'         => 'deal_created',
            'description'  => __('Deal created'),
        ]);

        return redirect()->route('customer.crm.deals.show', $deal)
            ->with('success', __('Deal created successfully.'));
    }

    public function show(Request $request, CrmDeal $deal): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.deals.view');

        if ((int) $deal->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $deal->load(['contact', 'stage', 'pipeline', 'owner', 'tasks.assignee', 'notes.creator', 'activities.performer']);

        return view('customer.crm.deals.show', compact('workspace', 'member', 'deal'));
    }

    public function edit(Request $request, CrmDeal $deal): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.deals.edit');

        if ((int) $deal->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $pipelines = CrmPipeline::where('workspace_id', $workspace->id)->with('stages')->get();
        $contacts = CrmContact::where('workspace_id', $workspace->id)->orderBy('first_name')->get();

        return view('customer.crm.deals.edit', compact('workspace', 'member', 'deal', 'pipelines', 'contacts'));
    }

    public function update(Request $request, CrmDeal $deal): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.deals.edit');

        if ((int) $deal->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title'               => 'required|string|max:200',
            'description'         => 'nullable|string|max:5000',
            'pipeline_id'         => 'required|exists:crm_pipelines,id',
            'stage_id'            => 'required|exists:crm_pipeline_stages,id',
            'contact_id'          => 'required|exists:crm_contacts,id',
            'value'               => 'nullable|numeric|min:0',
            'currency'            => 'nullable|string|max:3',
            'expected_close_date' => 'nullable|date',
        ]);

        $oldStageId = $deal->stage_id;
        $wasOpen = $deal->isOpen();

        $deal->update($validated);

        if ($deal->stage_id !== $oldStageId) {
            CrmDealActivity::create([
                'workspace_id' => $workspace->id,
                'deal_id'      => $deal->id,
                'stage_id'     => $deal->stage_id,
                'performed_by' => $member?->id,
                'type'         => 'stage_moved',
                'description'  => __('Moved to :stage', ['stage' => $deal->stage?->name ?? '']),
                'meta'         => ['from_stage_id' => $oldStageId, 'to_stage_id' => $deal->stage_id],
            ]);
        }

        $newStage = CrmPipelineStage::find($deal->stage_id);
        if ($newStage && $newStage->type !== 'open' && $wasOpen) {
            $deal->update([
                'status' => $newStage->type,
                'actual_close_date' => now()->toDateString(),
            ]);

            CrmDealActivity::create([
                'workspace_id' => $workspace->id,
                'deal_id'      => $deal->id,
                'performed_by' => $member?->id,
                'type'         => $newStage->type === 'won' ? 'deal_won' : 'deal_lost',
                'description'  => $newStage->type === 'won' ? __('Deal marked as won') : __('Deal marked as lost'),
            ]);
        }

        return redirect()->route('customer.crm.deals.show', $deal)
            ->with('success', __('Deal updated successfully.'));
    }

    public function destroy(Request $request, CrmDeal $deal): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.deals.delete');

        if ((int) $deal->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $deal->delete();

        return redirect()->route('customer.crm.deals.index')
            ->with('success', __('Deal deleted successfully.'));
    }

    public function moveStage(Request $request, CrmDeal $deal): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.deals.edit');

        if ((int) $deal->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'stage_id' => 'required|exists:crm_pipeline_stages,id',
        ]);

        $oldStageId = $deal->stage_id;
        $wasOpen = $deal->isOpen();

        $deal->update(['stage_id' => $validated['stage_id']]);

        if ($oldStageId !== $deal->stage_id) {
            CrmDealActivity::create([
                'workspace_id' => $workspace->id,
                'deal_id'      => $deal->id,
                'stage_id'     => $deal->stage_id,
                'performed_by' => $member?->id,
                'type'         => 'stage_moved',
                'description'  => __('Moved to :stage', ['stage' => $deal->stage?->name ?? '']),
                'meta'         => ['from_stage_id' => $oldStageId, 'to_stage_id' => $deal->stage_id],
            ]);
        }

        $newStage = CrmPipelineStage::find($deal->stage_id);
        if ($newStage && $newStage->type !== 'open' && $wasOpen) {
            $deal->update([
                'status' => $newStage->type,
                'actual_close_date' => now()->toDateString(),
            ]);

            CrmDealActivity::create([
                'workspace_id' => $workspace->id,
                'deal_id'      => $deal->id,
                'performed_by' => $member?->id,
                'type'         => $newStage->type === 'won' ? 'deal_won' : 'deal_lost',
                'description'  => $newStage->type === 'won' ? __('Deal marked as won') : __('Deal marked as lost'),
            ]);
        }

        return back()->with('success', __('Deal moved successfully.'));
    }
}

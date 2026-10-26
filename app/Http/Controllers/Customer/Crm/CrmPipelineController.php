<?php

namespace App\Http\Controllers\Customer\Crm;

use App\Models\Crm\CrmPipeline;
use App\Models\Crm\CrmPipelineStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CrmPipelineController extends CrmBaseController
{
    public function index(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.pipelines.view');

        $pipelines = CrmPipeline::where('workspace_id', $workspace->id)
            ->with(['stages', 'deals'])
            ->withCount('deals')
            ->latest()
            ->get();

        return view('customer.crm.pipelines.index', compact('workspace', 'member', 'pipelines'));
    }

    public function create(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.pipelines.create');

        return view('customer.crm.pipelines.create', compact('workspace', 'member'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.pipelines.create');

        $validated = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'stages'      => 'required|array|min:2',
            'stages.*.name' => 'required|string|max:100',
            'stages.*.color' => 'nullable|string|max:20',
            'stages.*.type' => 'nullable|in:open,won,lost',
        ]);

        $pipeline = CrmPipeline::create([
            'workspace_id' => $workspace->id,
            'name'         => $validated['name'],
            'slug'         => Str::slug($validated['name']) . '-' . uniqid(),
            'description'  => $validated['description'] ?? null,
        ]);

        foreach ($validated['stages'] as $index => $stageData) {
            CrmPipelineStage::create([
                'workspace_id' => $workspace->id,
                'pipeline_id'  => $pipeline->id,
                'name'         => $stageData['name'],
                'position'     => $index,
                'color'        => $stageData['color'] ?? '#3b82f6',
                'type'         => $stageData['type'] ?? 'open',
            ]);
        }

        return redirect()->route('customer.crm.pipelines.show', $pipeline)
            ->with('success', __('Pipeline created successfully.'));
    }

    public function show(Request $request, CrmPipeline $pipeline): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.pipelines.view');

        if ((int) $pipeline->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $pipeline->load([
            'stages.deals.contact',
            'stages.deals.owner',
            'stages' => fn ($q) => $q->orderBy('position'),
        ]);

        return view('customer.crm.pipelines.show', compact('workspace', 'member', 'pipeline'));
    }

    public function edit(Request $request, CrmPipeline $pipeline): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.pipelines.edit');

        if ((int) $pipeline->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $pipeline->load('stages');

        return view('customer.crm.pipelines.edit', compact('workspace', 'member', 'pipeline'));
    }

    public function update(Request $request, CrmPipeline $pipeline): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.pipelines.edit');

        if ((int) $pipeline->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'status'      => 'nullable|in:active,archived',
        ]);

        $pipeline->update($validated);

        return redirect()->route('customer.crm.pipelines.show', $pipeline)
            ->with('success', __('Pipeline updated successfully.'));
    }

    public function destroy(Request $request, CrmPipeline $pipeline): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.pipelines.delete');

        if ((int) $pipeline->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $pipeline->delete();

        return redirect()->route('customer.crm.pipelines.index')
            ->with('success', __('Pipeline deleted successfully.'));
    }
}

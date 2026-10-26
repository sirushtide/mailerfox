<?php

namespace App\Http\Controllers\Customer\Crm;

use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmDeal;
use App\Models\Crm\CrmTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmTaskController extends CrmBaseController
{
    public function index(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.tasks.view');

        $query = CrmTask::where('workspace_id', $workspace->id)
            ->with(['deal', 'contact', 'assignee'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        $tasks = $query->paginate(20)->withQueryString();
        $members = $workspace->activeMembers()->with('customer')->get();

        return view('customer.crm.tasks.index', compact('workspace', 'member', 'tasks', 'members'));
    }

    public function create(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.tasks.create');

        $deals = CrmDeal::where('workspace_id', $workspace->id)
            ->where('status', 'open')
            ->with('contact')
            ->get();
        $contacts = CrmContact::where('workspace_id', $workspace->id)->orderBy('first_name')->get();
        $members = $workspace->activeMembers()->with('customer')->get();

        return view('customer.crm.tasks.create', compact('workspace', 'member', 'deals', 'contacts', 'members'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.tasks.create');

        $validated = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'deal_id'     => 'nullable|exists:crm_deals,id',
            'contact_id'  => 'nullable|exists:crm_contacts,id',
            'assigned_to' => 'nullable|exists:workspace_members,id',
            'due_date'    => 'nullable|date',
            'due_time'    => 'nullable',
            'priority'    => 'nullable|in:low,medium,high',
        ]);

        $validated['workspace_id'] = $workspace->id;
        $validated['status'] = 'pending';

        $task = CrmTask::create($validated);

        return redirect()->route('customer.crm.tasks.index')
            ->with('success', __('Task created successfully.'));
    }

    public function edit(Request $request, CrmTask $task): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.tasks.edit');

        if ((int) $task->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $deals = CrmDeal::where('workspace_id', $workspace->id)->where('status', 'open')->with('contact')->get();
        $contacts = CrmContact::where('workspace_id', $workspace->id)->orderBy('first_name')->get();
        $members = $workspace->activeMembers()->with('customer')->get();

        return view('customer.crm.tasks.edit', compact('workspace', 'member', 'task', 'deals', 'contacts', 'members'));
    }

    public function update(Request $request, CrmTask $task): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.tasks.edit');

        if ((int) $task->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'deal_id'     => 'nullable|exists:crm_deals,id',
            'contact_id'  => 'nullable|exists:crm_contacts,id',
            'assigned_to' => 'nullable|exists:workspace_members,id',
            'due_date'    => 'nullable|date',
            'due_time'    => 'nullable',
            'priority'    => 'nullable|in:low,medium,high',
            'status'      => 'nullable|in:pending,in_progress,done,cancelled',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'done' && $task->status !== 'done') {
            $validated['completed_at'] = now();
        } elseif (isset($validated['status']) && $validated['status'] !== 'done') {
            $validated['completed_at'] = null;
        }

        $task->update($validated);

        return redirect()->route('customer.crm.tasks.index')
            ->with('success', __('Task updated successfully.'));
    }

    public function destroy(Request $request, CrmTask $task): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.tasks.delete');

        if ((int) $task->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $task->delete();

        return redirect()->route('customer.crm.tasks.index')
            ->with('success', __('Task deleted successfully.'));
    }

    public function toggleStatus(Request $request, CrmTask $task): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.tasks.edit');

        if ((int) $task->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        if ($task->status === 'done') {
            $task->markPending();
        } else {
            $task->markDone();
        }

        return back()->with('success', __('Task status updated.'));
    }
}

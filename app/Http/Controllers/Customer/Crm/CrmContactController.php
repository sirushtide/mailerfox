<?php

namespace App\Http\Controllers\Customer\Crm;

use App\Models\Crm\CrmCompany;
use App\Models\Crm\CrmContact;
use App\Models\ListSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmContactController extends CrmBaseController
{
    public function index(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.contacts.view');

        $query = CrmContact::where('workspace_id', $workspace->id)
            ->with('company')
            ->withCount('deals')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        $contacts = $query->paginate(20)->withQueryString();
        $companies = CrmCompany::where('workspace_id', $workspace->id)->orderBy('name')->pluck('name', 'id');

        return view('customer.crm.contacts.index', compact('workspace', 'member', 'contacts', 'companies'));
    }

    public function create(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.contacts.create');

        $companies = CrmCompany::where('workspace_id', $workspace->id)->orderBy('name')->get();

        return view('customer.crm.contacts.create', compact('workspace', 'member', 'companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.contacts.create');

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'title'      => 'nullable|string|max:100',
            'company_id' => 'nullable|exists:crm_companies,id',
            'source'     => 'nullable|string|max:100',
            'status'     => 'nullable|in:lead,prospect,customer,churned',
            'notes'      => 'nullable|string|max:5000',
        ]);

        $validated['workspace_id'] = $workspace->id;
        $validated['status'] = $validated['status'] ?? 'lead';

        $contact = CrmContact::create($validated);

        if ($contact->email) {
            $subscriber = ListSubscriber::where('email', $contact->email)->first();
            if ($subscriber) {
                $contact->subscribers()->syncWithoutDetaching([$subscriber->id]);
            }
        }

        return redirect()->route('customer.crm.contacts.show', $contact)
            ->with('success', __('Contact created successfully.'));
    }

    public function show(Request $request, CrmContact $contact): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.contacts.view');

        if ((int) $contact->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $contact->load(['company', 'deals.stage', 'tasks.assignee', 'notes.creator', 'subscribers.list']);

        return view('customer.crm.contacts.show', compact('workspace', 'member', 'contact'));
    }

    public function edit(Request $request, CrmContact $contact): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.contacts.edit');

        if ((int) $contact->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $companies = CrmCompany::where('workspace_id', $workspace->id)->orderBy('name')->get();

        return view('customer.crm.contacts.edit', compact('workspace', 'member', 'contact', 'companies'));
    }

    public function update(Request $request, CrmContact $contact): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.contacts.edit');

        if ((int) $contact->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'title'      => 'nullable|string|max:100',
            'company_id' => 'nullable|exists:crm_companies,id',
            'source'     => 'nullable|string|max:100',
            'status'     => 'nullable|in:lead,prospect,customer,churned',
            'notes'      => 'nullable|string|max:5000',
        ]);

        $oldEmail = $contact->email;
        $contact->update($validated);

        if ($contact->email && $contact->email !== $oldEmail) {
            $subscriber = ListSubscriber::where('email', $contact->email)->first();
            if ($subscriber) {
                $contact->subscribers()->syncWithoutDetaching([$subscriber->id]);
            }
        }

        return redirect()->route('customer.crm.contacts.show', $contact)
            ->with('success', __('Contact updated successfully.'));
    }

    public function destroy(Request $request, CrmContact $contact): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.contacts.delete');

        if ((int) $contact->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $contact->delete();

        return redirect()->route('customer.crm.contacts.index')
            ->with('success', __('Contact deleted successfully.'));
    }

    public function linkSubscriber(Request $request, CrmContact $contact): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.contacts.edit');

        if ((int) $contact->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'subscriber_id' => 'required|exists:list_subscribers,id',
        ]);

        $contact->subscribers()->syncWithoutDetaching([$validated['subscriber_id']]);

        return back()->with('success', __('Subscriber linked successfully.'));
    }

    public function unlinkSubscriber(Request $request, CrmContact $contact, int $subscriberId): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.contacts.edit');

        if ((int) $contact->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $contact->subscribers()->detach($subscriberId);

        return back()->with('success', __('Subscriber unlinked successfully.'));
    }
}

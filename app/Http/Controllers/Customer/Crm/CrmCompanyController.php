<?php

namespace App\Http\Controllers\Customer\Crm;

use App\Models\Crm\CrmCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CrmCompanyController extends CrmBaseController
{
    public function index(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.companies.view');

        $query = CrmCompany::where('workspace_id', $workspace->id)
            ->withCount('contacts')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('industry', 'like', "%{$search}%");
            });
        }

        $companies = $query->paginate(20)->withQueryString();

        return view('customer.crm.companies.index', compact('workspace', 'member', 'companies'));
    }

    public function create(Request $request): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.companies.create');

        return view('customer.crm.companies.create', compact('workspace', 'member'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.companies.create');

        $validated = $request->validate([
            'name'        => 'required|string|max:150',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'website'     => 'nullable|url|max:255',
            'industry'    => 'nullable|string|max:100',
            'size'        => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'description' => 'nullable|string|max:5000',
        ]);

        $validated['workspace_id'] = $workspace->id;
        $validated['slug'] = Str::slug($validated['name']);

        $baseSlug = $validated['slug'];
        $counter = 1;
        while (CrmCompany::where('workspace_id', $workspace->id)->where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter;
            $counter++;
        }

        $company = CrmCompany::create($validated);

        return redirect()->route('customer.crm.companies.show', $company)
            ->with('success', __('Company created successfully.'));
    }

    public function show(Request $request, CrmCompany $company): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.companies.view');

        if ((int) $company->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $company->load(['contacts' => fn ($q) => $q->latest()->limit(10)]);

        return view('customer.crm.companies.show', compact('workspace', 'member', 'company'));
    }

    public function edit(Request $request, CrmCompany $company): View
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.companies.edit');

        if ((int) $company->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        return view('customer.crm.companies.edit', compact('workspace', 'member', 'company'));
    }

    public function update(Request $request, CrmCompany $company): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.companies.edit');

        if ((int) $company->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:150',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'website'     => 'nullable|url|max:255',
            'industry'    => 'nullable|string|max:100',
            'size'        => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'description' => 'nullable|string|max:5000',
            'status'      => 'nullable|in:active,inactive',
        ]);

        $company->update($validated);

        return redirect()->route('customer.crm.companies.show', $company)
            ->with('success', __('Company updated successfully.'));
    }

    public function destroy(Request $request, CrmCompany $company): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizePermission($workspace, $member, 'crm.companies.delete');

        if ((int) $company->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $company->delete();

        return redirect()->route('customer.crm.companies.index')
            ->with('success', __('Company deleted successfully.'));
    }
}

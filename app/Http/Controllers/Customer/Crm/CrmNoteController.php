<?php

namespace App\Http\Controllers\Customer\Crm;

use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmDeal;
use App\Models\Crm\CrmNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CrmNoteController extends CrmBaseController
{
    public function store(Request $request): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizeAnyPermission($workspace, $member, ['crm.contacts.edit', 'crm.deals.edit']);

        $validated = $request->validate([
            'notable_type' => 'required|in:contact,deal',
            'notable_id'   => 'required|integer',
            'content'      => 'required|string|max:10000',
            'type'         => 'nullable|string|max:50',
        ]);

        $notableType = $validated['notable_type'] === 'contact'
            ? CrmContact::class
            : CrmDeal::class;

        $notable = $notableType::where('id', $validated['notable_id'])
            ->where('workspace_id', $workspace->id)
            ->first();

        if (!$notable) {
            abort(404);
        }

        CrmNote::create([
            'workspace_id' => $workspace->id,
            'notable_type' => $notableType,
            'notable_id'   => $validated['notable_id'],
            'created_by'   => $member?->id,
            'content'      => $validated['content'],
            'type'         => $validated['type'] ?? 'note',
        ]);

        if ($notable instanceof CrmContact) {
            $notable->touchActivity();
        }

        return back()->with('success', __('Note added successfully.'));
    }

    public function destroy(Request $request, CrmNote $note): RedirectResponse
    {
        [$workspace, $member] = $this->resolveContext($request);
        $this->authorizeAnyPermission($workspace, $member, ['crm.contacts.edit', 'crm.deals.edit']);

        if ((int) $note->workspace_id !== (int) $workspace->id) {
            abort(404);
        }

        $note->delete();

        return back()->with('success', __('Note deleted successfully.'));
    }
}

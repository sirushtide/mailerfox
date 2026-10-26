<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\UniboxFolder;
use App\Models\UniboxTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniboxFolderTagController extends Controller
{
    protected function customer()
    {
        return Auth::guard('customer')->user();
    }

    // ========== Folders ==========

    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
            'account_id' => 'nullable|integer',
        ]);

        $accountId = $this->resolveAccountId($data['account_id'] ?? null);

        $folder = UniboxFolder::create([
            'customer_id' => $this->customer()->id,
            'account_id' => $accountId,
            'name' => trim($data['name']),
            'color' => $data['color'] ?? '#7c3aed',
        ]);

        return response()->json(['folder' => $folder]);
    }

    protected function resolveAccountId($accountId): ?int
    {
        if (!$accountId) {
            return null;
        }
        $accountId = (int) $accountId;
        $owns = \App\Models\UniboxAccount::where('id', $accountId)
            ->where('customer_id', $this->customer()->id)
            ->exists();
        return $owns ? $accountId : null;
    }

    public function updateFolder(Request $request, UniboxFolder $folder)
    {
        abort_if($folder->customer_id !== $this->customer()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $folder->update([
            'name' => trim($data['name']),
            'color' => $data['color'] ?? $folder->color,
        ]);

        return response()->json(['folder' => $folder]);
    }

    public function destroyFolder(UniboxFolder $folder)
    {
        abort_if($folder->customer_id !== $this->customer()->id, 403);
        $folder->delete();
        return response()->json(['success' => true]);
    }

    // ========== Tags ==========

    public function storeTag(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $accountId = $this->resolveAccountId($request->input('account_id'));

        $tag = UniboxTag::updateOrCreate(
            [
                'customer_id' => $this->customer()->id,
                'account_id' => $accountId,
                'name' => trim($data['name']),
            ],
            [
                'color' => $data['color'] ?? '#7c3aed',
            ]
        );

        return response()->json(['tag' => $tag]);
    }

    public function updateTag(Request $request, UniboxTag $tag)
    {
        abort_if($tag->customer_id !== $this->customer()->id, 403);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'color' => 'sometimes|required|string|max:20',
        ]);

        $oldName = $tag->name;
        $newName = isset($data['name']) ? trim($data['name']) : $oldName;
        $newColor = $data['color'] ?? $tag->color;

        // If renaming to a name that already exists for this customer, reject cleanly.
        if ($newName !== $oldName) {
            $exists = \App\Models\UniboxTag::where('customer_id', $tag->customer_id)
                ->where('name', $newName)
                ->where('id', '!=', $tag->id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => __('A tag with that name already exists.'),
                ], 422);
            }
        }

        try {
            $tag->update([
                'name' => $newName,
                'color' => $newColor,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => __('Could not update tag.') . ' ' . $e->getMessage(),
            ], 500);
        }

        // Conversations store tags as a JSON array of names, so rename them in place.
        if ($newName !== $oldName) {
            $conversations = \App\Models\UniboxConversation::where('customer_id', $tag->customer_id)
                ->whereJsonContains('tags', $oldName)
                ->get();
            foreach ($conversations as $conversation) {
                $tags = is_array($conversation->tags) ? $conversation->tags : [];
                $tags = array_values(array_unique(array_map(
                    fn ($t) => $t === $oldName ? $newName : $t,
                    $tags
                )));
                $conversation->update(['tags' => $tags]);
            }
        }

        return response()->json(['tag' => $tag->fresh()]);
    }

    public function destroyTag(UniboxTag $tag)
    {
        abort_if($tag->customer_id !== $this->customer()->id, 403);
        $tag->delete();
        return response()->json(['success' => true]);
    }
}

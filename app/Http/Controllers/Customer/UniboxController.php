<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\EmailList;
use App\Models\ListSubscriber;
use App\Models\UniboxAccount;
use App\Models\UniboxConversation;
use App\Models\UniboxFolder;
use App\Models\UniboxMessage;
use App\Models\UniboxNote;
use App\Models\UniboxReplyTemplate;
use App\Models\UniboxTag;
use App\Models\UniboxTeam;
use App\Services\UniboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniboxController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Addon::isActive('unibox')) {
                if ($request->expectsJson()) {
                    abort(403, 'UniBox addon is not active.');
                }
                return redirect()->route('customer.dashboard')
                    ->with('error', 'UniBox addon is not active.');
            }
            return $next($request);
        });
    }

    protected function customer()
    {
        return Auth::guard('customer')->user();
    }

    public function index(Request $request)
    {
        $customer = $this->customer();
        $folder = $request->get('folder', 'inbox');
        $search = $request->get('q', '');
        $accountId = $request->get('account_id');

        UniboxAccount::syncFromDeliveryServersForCustomer($customer->id);

        $accounts = UniboxAccount::where('customer_id', $customer->id)
            ->where('status', '!=', 'paused')
            ->get();
        $teams = UniboxTeam::where('customer_id', $customer->id)->get();

        // Folders and tags are now scoped per account when an account is selected.
        // When viewing All Inbox (no account_id), show every folder/tag the customer owns.
        $foldersQ = UniboxFolder::where('customer_id', $customer->id);
        $tagsQ = UniboxTag::where('customer_id', $customer->id);
        if ($accountId) {
            $foldersQ->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)->orWhereNull('account_id');
            });
            $tagsQ->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)->orWhereNull('account_id');
            });
        }
        $folders = $foldersQ->orderBy('name')->get();
        $tags = $tagsQ->orderBy('name')->get();

        // Scope counts to the selected account
        $baseQuery = UniboxConversation::where('customer_id', $customer->id);
        if ($accountId) {
            $baseQuery->where('account_id', $accountId);
        }

        $counts = [
            'inbox' => (clone $baseQuery)->where('folder', 'inbox')->where('status', 'open')->count(),
            'assigned_to_me' => (clone $baseQuery)->where('assigned_to', $customer->id)->where('status', 'open')->count(),
            'unassigned' => (clone $baseQuery)->whereNull('assigned_to')->where('folder', 'inbox')->where('status', 'open')->count(),
            'sent' => (clone $baseQuery)->where('folder', 'sent')->count(),
            'drafts' => (clone $baseQuery)->where('folder', 'drafts')->count(),
            'snoozed' => (clone $baseQuery)->where('folder', 'snoozed')->count(),
            'archive' => (clone $baseQuery)->where('folder', 'archive')->count(),
            'spam' => (clone $baseQuery)->where('folder', 'spam')->count(),
            'trash' => (clone $baseQuery)->where('folder', 'trash')->count(),
        ];

        $emailLists = EmailList::where('customer_id', $customer->id)->orderBy('name')->get(['id', 'name']);

        $coldEmailActive = Addon::isActive('cold-email-outreach');
        $outreachCampaigns = $coldEmailActive
            ? \App\Models\OutreachCampaign::where('customer_id', $customer->id)->orderBy('name')->get(['id', 'name', 'status'])
            : collect();

        // Omnichannel threads from the SMS / WhatsApp addons.
        $activeChannels = [];
        if (Addon::isActive('sms')) {
            $activeChannels[] = 'sms';
        }
        if (Addon::isActive('whatsapp')) {
            $activeChannels[] = 'whatsapp';
        }

        $channelThreads = $activeChannels === []
            ? collect()
            : \App\Models\ChannelThread::where('customer_id', $customer->id)
                ->whereIn('channel', $activeChannels)
                ->with('contact')
                ->orderByDesc('last_message_at')
                ->limit(8)
                ->get();

        return view('customer.unibox.index', compact('accounts', 'teams', 'folders', 'tags', 'counts', 'folder', 'search', 'emailLists', 'coldEmailActive', 'outreachCampaigns', 'accountId', 'channelThreads'));
    }

    public function conversations(Request $request)
    {
        $customer = $this->customer();
        $folder = $request->get('folder', 'inbox');
        $search = $request->get('q', '');
        $sort = $request->get('sort', 'newest');
        $assignedTo = $request->get('assigned_to');

        $query = UniboxConversation::with(['contact', 'account', 'latestMessage'])
            ->where('customer_id', $customer->id);

        if (str_starts_with($folder, 'custom_')) {
            $folderId = (int) substr($folder, 7);
            $query->whereHas('customFolders', fn($q) => $q->where('unibox_folders.id', $folderId));
        } else {
            match ($folder) {
                'assigned_to_me' => $query->where('assigned_to', $customer->id)->where('status', 'open'),
                'unassigned' => $query->whereNull('assigned_to')->where('folder', 'inbox')->where('status', 'open'),
                default => $query->where('folder', $folder),
            };
        }

        if ($tag = $request->get('tag')) {
            $query->whereJsonContains('tags', $tag);
        }

        if ($category = $request->get('category')) {
            $this->applyCategoryFilter($query, $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhereHas('contact', fn($q2) => $q2->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        if ($assignedTo) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($accountId = $request->get('account_id')) {
            $query->where('account_id', (int) $accountId);
        }

        $sort === 'oldest'
            ? $query->orderBy('last_message_at')
            : $query->orderByDesc('last_message_at');

        $conversations = $query->paginate(25);

        return response()->json([
            'data' => $conversations->map(fn($c) => $this->serializeConversation($c)),
            'has_more' => $conversations->hasMorePages(),
            'total' => $conversations->total(),
        ]);
    }

    protected function applyCategoryFilter($query, string $category): void
    {
        if (!in_array($category, ['primary', 'promotions', 'updates', 'social'], true)) {
            return;
        }

        if ($category === 'primary') {
            // Include Gmail-tagged primary AND anything with no category (IMAP/non-Gmail).
            $query->where(function ($q) {
                $q->where('category', 'primary')->orWhereNull('category');
            });
        } else {
            $query->where('category', $category);
        }
    }

    public function show(UniboxConversation $conversation)
    {
        $customer = $this->customer();
        abort_if($conversation->customer_id !== $customer->id, 403);

        $conversation->load(['contact.activities', 'account', 'team', 'messages', 'notes', 'customFolders']);

        if (!$conversation->is_read) {
            $conversation->update(['is_read' => true]);
        }

        return response()->json([
            'conversation' => $this->serializeConversation($conversation, true),
            'messages' => $conversation->messages->sortBy(fn($m) => $m->received_at ?? $m->sent_at ?? $m->created_at)->values()->map(fn($m) => $this->serializeMessage($m)),
            'notes' => $conversation->notes->map(fn($n) => ['id' => $n->id, 'author' => $n->author_name, 'content' => $n->content, 'time' => $n->created_at->diffForHumans()]),
            'contact' => $conversation->contact ? $this->serializeContact($conversation->contact) : null,
        ]);
    }

    public function reply(Request $request, UniboxConversation $conversation)
    {
        $customer = $this->customer();
        abort_if($conversation->customer_id !== $customer->id, 403);

        $request->validate([
            'body' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = [
                    'path' => $file->store("unibox/{$conversation->id}/attachments"),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        try {
            $service = app(UniboxService::class);
            $message = $service->sendReply($conversation, [
                'body' => $request->body,
                'to' => $conversation->contact->email ?? '',
                'attachments' => $attachmentPaths,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            return response()->json(['error' => 'Failed to send reply: ' . $e->getMessage()], 422);
        }

        return response()->json(['message' => $this->serializeMessage($message)]);
    }

    public function addNote(Request $request, UniboxConversation $conversation)
    {
        $customer = $this->customer();
        abort_if($conversation->customer_id !== $customer->id, 403);

        $request->validate(['content' => 'required|string|max:5000']);

        $note = UniboxNote::create([
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'author_name' => $customer->name ?? $customer->email,
            'content' => $request->content,
        ]);

        return response()->json(['note' => ['id' => $note->id, 'author' => $note->author_name, 'content' => $note->content, 'time' => $note->created_at->diffForHumans()]]);
    }

    public function deleteNote(UniboxNote $note)
    {
        $customer = $this->customer();
        abort_if($note->customer_id !== $customer->id, 403);

        $note->delete();

        return response()->json(['success' => true]);
    }

    public function toggleFolder(Request $request, UniboxConversation $conversation)
    {
        $customer = $this->customer();
        abort_if($conversation->customer_id !== $customer->id, 403);

        $request->validate(['folder_id' => 'required|integer']);
        $folderId = (int) $request->folder_id;

        $folder = UniboxFolder::where('customer_id', $customer->id)->where('id', $folderId)->firstOrFail();

        if ($conversation->customFolders()->where('folder_id', $folder->id)->exists()) {
            $conversation->customFolders()->detach($folder->id);
            $attached = false;
        } else {
            $conversation->customFolders()->attach($folder->id);
            $attached = true;
        }

        return response()->json([
            'attached' => $attached,
            'folder_ids' => $conversation->customFolders()->pluck('unibox_folders.id'),
        ]);
    }

    public function toggleTag(Request $request, UniboxConversation $conversation)
    {
        $customer = $this->customer();
        abort_if($conversation->customer_id !== $customer->id, 403);

        $request->validate(['tag' => 'required|string|max:100']);
        $tagName = trim($request->tag);

        $tags = is_array($conversation->tags) ? $conversation->tags : [];
        $exists = collect($tags)->contains(fn ($t) => mb_strtolower((string) $t) === mb_strtolower($tagName));

        if ($exists) {
            $tags = array_values(array_filter($tags, fn ($t) => mb_strtolower((string) $t) !== mb_strtolower($tagName)));
            $attached = false;
        } else {
            $tags[] = $tagName;
            $attached = true;
        }

        $conversation->update(['tags' => $tags]);

        return response()->json(['attached' => $attached, 'tags' => $tags]);
    }

    public function update(Request $request, UniboxConversation $conversation)
    {
        $customer = $this->customer();
        abort_if($conversation->customer_id !== $customer->id, 403);

        $allowed = ['status', 'folder', 'is_starred', 'is_read', 'assigned_to', 'team_id', 'tags'];
        $data = $request->only($allowed);

        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_filter(array_map('trim', explode(',', $data['tags'])));
        }

        $conversation->update($data);

        return response()->json(['success' => true]);
    }

    public function assign(Request $request, UniboxConversation $conversation)
    {
        $customer = $this->customer();
        abort_if($conversation->customer_id !== $customer->id, 403);

        $request->validate(['assigned_to' => 'nullable|integer']);
        $conversation->update(['assigned_to' => $request->assigned_to]);

        return response()->json(['success' => true]);
    }

    public function compose(Request $request)
    {
        $request->validate([
            'to'      => 'required|array|min:1',
            'to.*'    => 'email',
            'subject' => 'nullable|string|max:998',
            'body'    => 'nullable|string',
            'cc'      => 'nullable|array',
            'bcc'     => 'nullable|array',
            'account_id' => 'nullable|integer',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $customer = $this->customer();
        UniboxAccount::syncFromDeliveryServersForCustomer($customer->id);

        $accountQuery = UniboxAccount::where('customer_id', $customer->id);
        if ($request->filled('account_id')) {
            $account = (clone $accountQuery)->where('id', $request->account_id)->first();
        } else {
            $account = (clone $accountQuery)->where('status', 'active')->first()
                ?: (clone $accountQuery)->first();
        }

        if (! $account) {
            return response()->json(['error' => 'No email account configured. Please connect an inbox first.'], 422);
        }

        try {
            $service = new UniboxService($account);
            $service->sendEmail([
                'to'      => $request->to,
                'cc'      => $request->cc ?? [],
                'bcc'     => $request->bcc ?? [],
                'subject' => $request->subject ?: '(no subject)',
                'body'    => $request->body ?? '',
                'attachments' => $request->file('attachments', []),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('UniBox compose send failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Send failed: ' . $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function sync(Request $request)
    {
        $customer = $this->customer();
        UniboxAccount::syncFromDeliveryServersForCustomer($customer->id);
        $accounts = UniboxAccount::where('customer_id', $customer->id)->where('status', 'active')->get();

        $service = app(\App\Services\UniboxService::class);
        $totalSynced = 0;
        $errors = [];

        foreach ($accounts as $account) {
            try {
                $result = $service->syncAccount($account);
                $totalSynced += $result['synced'] ?? 0;
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return response()->json([
            'synced' => $totalSynced,
            'accounts' => $accounts->count(),
            'errors' => $errors,
        ]);
    }

    protected function serializeConversation(UniboxConversation $c, bool $full = false): array
    {
        $latest = $c->relationLoaded('latestMessage') ? $c->latestMessage : $c->messages->last();
        return [
            'id' => $c->id,
            'subject' => $c->subject,
            'status' => $c->status,
            'folder' => $c->folder,
            'is_read' => $c->is_read,
            'is_starred' => $c->is_starred,
            'tags' => $c->tags ?? [],
            'folder_ids' => $c->relationLoaded('customFolders') ? $c->customFolders->pluck('id')->all() : $c->customFolders()->pluck('unibox_folders.id')->all(),
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'last_message_at_human' => $c->last_message_at?->diffForHumans() ?? '',
            'last_message_at_formatted' => $c->last_message_at?->format('M j, Y, g:i A') ?? '',
            'sla' => $c->sla_display,
            'message_count' => $c->message_count,
            'assigned_to' => $c->assigned_to,
            'team_id' => $c->team_id,
            'contact' => $c->contact ? [
                'id' => $c->contact->id,
                'name' => $c->contact->display_name,
                'email' => $c->contact->email,
                'company' => $c->contact->company,
                'avatar_url' => $c->contact->avatar_url,
            ] : null,
            'preview' => $latest ? $latest->body_preview : '',
        ];
    }

    protected function serializeMessage(UniboxMessage $m): array
    {
        return [
            'id' => $m->id,
            'from_email' => $m->from_email,
            'from_name' => $m->from_name,
            'from_display' => $m->from_display,
            'to' => $m->to,
            'subject' => $m->subject,
            'body_html' => $m->body_html,
            'body_text' => $m->body_text,
            'type' => $m->type,
            'status' => $m->status,
            'time' => ($m->received_at ?? $m->sent_at ?? $m->created_at)->format('M j, Y, g:i A'),
            'time_human' => ($m->received_at ?? $m->sent_at ?? $m->created_at)->diffForHumans(),
            'attachments' => $m->attachments ?? [],
        ];
    }

    public function uploadContactAvatar(Request $request, \App\Models\UniboxContact $contact)
    {
        $customer = $this->customer();
        abort_if($contact->customer_id !== $customer->id, 403);

        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $path = $request->file('avatar')->store("unibox/avatars/{$customer->id}", 'public');
        $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        $contact->update(['avatar_url' => $url]);

        return response()->json(['success' => true, 'avatar_url' => $url]);
    }

    public function updateContact(Request $request, \App\Models\UniboxContact $contact)
    {
        $customer = $this->customer();
        abort_if($contact->customer_id !== $customer->id, 403);

        $request->validate([
            'field' => 'required|string',
            'value' => 'nullable|string|max:500',
        ]);

        $field = $request->field;
        $value = $request->value;

        // Direct model fields
        $directFields = ['name', 'phone', 'company', 'job_title', 'website', 'location'];
        if (in_array($field, $directFields)) {
            $contact->update([$field => $value]);
        } else {
            // Meta fields (social, notes, etc.)
            $meta = $contact->meta ?? [];
            $meta[$field] = $value;
            $contact->update(['meta' => $meta]);
        }

        return response()->json(['success' => true]);
    }

    public function addContactToList(Request $request)
    {
        $customer = $this->customer();
        $request->validate([
            'email' => 'required|email',
            'list_id' => 'required|integer',
            'name' => 'nullable|string|max:255',
        ]);

        $list = EmailList::where('customer_id', $customer->id)->where('id', $request->list_id)->firstOrFail();

        $existing = ListSubscriber::where('list_id', $list->id)->where('email', $request->email)->first();
        if ($existing) {
            return response()->json(['error' => 'Already subscribed to this list'], 422);
        }

        $nameParts = explode(' ', $request->name ?? '', 2);
        ListSubscriber::create([
            'list_id' => $list->id,
            'email' => $request->email,
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? '',
            'status' => 'confirmed',
            'source' => 'unibox',
            'subscribed_at' => now(),
            'confirmed_at' => now(),
        ]);

        return response()->json(['success' => true, 'list_name' => $list->name]);
    }

    public function addContactToSequence(Request $request)
    {
        $customer = $this->customer();
        if (!Addon::isActive('cold-email-outreach')) {
            return response()->json(['error' => 'Cold Email Outreach addon is not active.'], 403);
        }

        $request->validate([
            'email' => 'required|email',
            'campaign_id' => 'required|integer',
            'name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
        ]);

        $campaign = \App\Models\OutreachCampaign::where('customer_id', $customer->id)
            ->where('id', $request->campaign_id)->firstOrFail();

        $existing = \App\Models\OutreachLead::where('campaign_id', $campaign->id)
            ->where('email', $request->email)->first();
        if ($existing) {
            return response()->json(['error' => 'Already added to this sequence'], 422);
        }

        $nameParts = explode(' ', $request->name ?? '', 2);
        \App\Models\OutreachLead::create([
            'campaign_id' => $campaign->id,
            'email' => $request->email,
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? '',
            'company' => $request->company ?? '',
            'status' => 'pending',
        ]);

        $campaign->increment('leads_count');

        return response()->json(['success' => true, 'campaign_name' => $campaign->name]);
    }

    protected function serializeContact(\App\Models\UniboxContact $c): array
    {
        // Find which lists this contact's email is subscribed to
        $subscribedLists = ListSubscriber::where('email', $c->email)
            ->whereHas('list', fn($q) => $q->where('customer_id', $c->customer_id))
            ->with('list:id,name')
            ->get()
            ->map(fn($s) => ['id' => $s->list_id, 'name' => $s->list?->name, 'status' => $s->status])
            ->values()
            ->all();

        return [
            'id' => $c->id,
            'name' => $c->display_name,
            'email' => $c->email,
            'company' => $c->company,
            'job_title' => $c->job_title,
            'phone' => $c->phone,
            'website' => $c->website,
            'location' => $c->location,
            'timezone' => $c->timezone,
            'avatar_url' => $c->avatar_url,
            'tags' => $c->tags ?? [],
            'notes' => $c->meta['notes'] ?? '',
            'social' => [
                'facebook' => $c->meta['facebook'] ?? '',
                'instagram' => $c->meta['instagram'] ?? '',
                'twitter' => $c->meta['twitter'] ?? '',
            ],
            'revenue' => $c->formatted_revenue,
            'conversation_count' => $c->conversation_count,
            'first_seen_at' => $c->first_seen_at?->year,
            'last_reply_at' => $c->last_reply_at?->diffForHumans(),
            'subscribed_lists' => $subscribedLists,
            'activities' => $c->activities?->take(5)->map(fn($a) => [
                'type' => $a->type,
                'description' => $a->description,
                'time' => $a->occurred_at->diffForHumans(),
                'meta' => $a->meta,
            ])->values()->all() ?? [],
        ];
    }

    public function replyTemplates()
    {
        $customer = $this->customer();
        $templates = UniboxReplyTemplate::where('customer_id', $customer->id)
            ->orderBy('folder')
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'folder' => $t->folder,
                'name' => $t->name,
                'subject' => $t->subject,
                'body' => $t->body,
                'shared' => $t->shared,
            ]);

        \Log::info('UniBox replyTemplates fetched', [
            'customer_id' => $customer?->id,
            'count' => $templates->count(),
        ]);

        return response()->json([
            'templates' => $templates,
            // Debug fields to verify scope on the client; safe to ignore in UI
            'customer_id' => $customer?->id,
            'count' => $templates->count(),
        ]);
    }

    public function storeReplyTemplate(Request $request)
    {
        $customer = $this->customer();
        $request->validate([
            'name' => 'required|string|max:255',
            'folder' => 'nullable|string|max:100',
            'subject' => 'nullable|string|max:500',
            'body' => 'required|string',
            'shared' => 'boolean',
        ]);

        $template = UniboxReplyTemplate::create([
            'customer_id' => $customer->id,
            'name' => $request->name,
            'folder' => $request->folder ?: null,
            'subject' => $request->subject ?: null,
            'body' => $request->body,
            'shared' => $request->boolean('shared'),
        ]);

        return response()->json([
            'success' => true,
            'template' => [
                'id' => $template->id,
                'folder' => $template->folder,
                'name' => $template->name,
                'subject' => $template->subject,
                'body' => $template->body,
                'shared' => $template->shared,
            ],
        ]);
    }

    public function destroyReplyTemplate(UniboxReplyTemplate $template)
    {
        $customer = $this->customer();
        abort_if($template->customer_id !== $customer->id, 403);
        $template->delete();

        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\DeliveryServer;
use App\Models\DeliveryServerLog;
use App\Models\EmailList;
use App\Models\ListSegment;
use App\Models\ListSubscriber;
use App\Models\OutreachCampaign;
use App\Models\OutreachLead;
use App\Models\OutreachSequence;
use App\Models\Template;
use App\Models\TrackingDomain;
use App\Services\DeliveryServerService;
use App\Services\TemplateService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OutreachCampaignController extends Controller
{
    public function __construct(
        protected TemplateService $templateService,
        protected DeliveryServerService $deliveryServerService,
    ) {}

    private function guardAddon(): void
    {
        if (! Addon::isActive('cold-email-outreach')) {
            abort(403, 'Cold Email Outreach addon is not active.');
        }
    }

    private function ownedCampaign(int $id): OutreachCampaign
    {
        return OutreachCampaign::where('customer_id', auth('customer')->id())
            ->findOrFail($id);
    }

    public function show(Request $request, int $campaign): View
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);
        $tab = $request->query('tab', 'analytics');

        $statusInsights = $this->buildStatusInsights($campaign);

        $data = compact('campaign', 'tab', 'statusInsights');

        if ($tab === 'analytics') {
            $leadStats = OutreachLead::where('campaign_id', $campaign->id)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $selectedSenderIds = collect((array) ($campaign->getSetting('sender_account_ids', []) ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values();

            $data['totalSent'] = $leadStats->except(['pending'])->sum();
            $data['delivered'] = $leadStats->except(['pending', 'bounced'])->sum();
            $data['opens'] = $leadStats->only(['opened', 'clicked', 'replied'])->sum();
            $data['clicks'] = $leadStats->only(['clicked'])->sum();
            $data['replies'] = $leadStats->only(['replied'])->sum();
            $data['bounces'] = $leadStats->only(['bounced'])->sum();
            $data['leadStats'] = $leadStats;

            $engagedLeads = OutreachLead::where('campaign_id', $campaign->id)
                ->whereIn('status', ['opened', 'clicked', 'replied'])
                ->whereNotNull('last_activity_at')
                ->get(['status', 'last_activity_at']);

            $heatmapGrid = collect(range(0, 6))->map(function ($weekday) {
                return collect(range(0, 23))->map(function ($hour) use ($weekday) {
                    return [
                        'weekday' => $weekday,
                        'hour' => $hour,
                        'opens' => 0,
                        'clicks' => 0,
                        'total' => 0,
                    ];
                })->values();
            })->values();

            foreach ($engagedLeads as $lead) {
                $activityAt = $lead->last_activity_at ? Carbon::parse($lead->last_activity_at) : null;
                if (! $activityAt) {
                    continue;
                }

                $weekday = (int) $activityAt->dayOfWeek;
                $hour = (int) $activityAt->hour;
                $cell = $heatmapGrid[$weekday][$hour];
                $cell['opens'] += in_array($lead->status, ['opened', 'clicked', 'replied'], true) ? 1 : 0;
                $cell['clicks'] += in_array($lead->status, ['clicked', 'replied'], true) ? 1 : 0;
                $cell['total'] += 1;
                $heatmapGrid[$weekday][$hour] = $cell;
            }

            $deliveryServerStats = DeliveryServerLog::query()
                ->with(['deliveryServer:id,name,from_email,type'])
                ->whereHas('deliveryServer', function ($q) {
                    $q->where('customer_id', auth('customer')->id());
                })
                ->whereRaw("CAST(json_extract(meta, '$.outreach_campaign_id') AS INTEGER) = ?", [(int) $campaign->id])
                ->where('event', 'outreach_sent')
                ->get()
                ->groupBy('delivery_server_id')
                ->map(function ($logs) {
                    $server = optional($logs->first())->deliveryServer;

                    return [
                        'label' => $server?->name ?? __('Unknown server'),
                        'from_email' => $server?->from_email,
                        'type' => $server?->type,
                        'sent_count' => $logs->count(),
                    ];
                })
                ->sortByDesc('sent_count')
                ->values();

            $campaignDeliveryServers = DeliveryServer::query()
                ->where('customer_id', auth('customer')->id())
                ->where('status', 'active')
                ->when($selectedSenderIds->isNotEmpty(), function ($query) use ($selectedSenderIds) {
                    $query->whereIn('id', $selectedSenderIds->all());
                })
                ->orderBy('name')
                ->get(['id', 'name', 'from_email', 'type']);

            if ($deliveryServerStats->isEmpty()) {
                $deliveryServerStats = $campaignDeliveryServers
                    ->map(function (DeliveryServer $server) {
                        return [
                            'server_id' => $server->id,
                            'label' => $server->name ?: __('Unknown server'),
                            'from_email' => $server->from_email,
                            'type' => $server->type,
                            'sent_count' => 0,
                        ];
                    })
                    ->values();
            }

            $data['engagementHeatmap'] = $heatmapGrid;
            $data['engagementHeatmapMax'] = max(1, (int) $heatmapGrid->flatten(1)->max('total'));
            $data['deliveryServerChart'] = $deliveryServerStats;
        }

        if ($tab === 'leads') {
            $search = (string) $request->query('search', '');
            $status = (string) $request->query('status', '');

            $query = OutreachLead::where('campaign_id', $campaign->id)
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($s) use ($search) {
                        $s->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
                    });
                })
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->latest();

            $data['leads'] = $query->paginate(20)->withQueryString();
            $data['searchQuery'] = $search;
            $data['statusFilter'] = $status;
            $data['emailLists'] = EmailList::where('customer_id', auth('customer')->id())
                ->orderBy('name')
                ->get(['id', 'name', 'display_name', 'subscribers_count']);
            $listIds = $data['emailLists']->pluck('id');
            $data['listSegments'] = ListSegment::whereIn('list_id', $listIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'list_id', 'name', 'subscribers_count']);
            $data['customVariables'] = $campaign->getSetting('custom_variables', []);
            $data['customFields'] = $campaign->getSetting('custom_fields', []);
            $data['totalSequenceSteps'] = OutreachSequence::where('campaign_id', $campaign->id)->count();
        }

        if ($tab === 'sequences') {
            $data['customFields'] = $campaign->getSetting('custom_fields', []);
            $data['sequences'] = OutreachSequence::where('campaign_id', $campaign->id)
                ->orderBy('sort_order')
                ->get();
            $customer = auth('customer')->user();
            $data['templates'] = Template::query()
                ->whereIn('type', ['email', 'campaign'])
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'customer_id', 'is_system', 'is_public'])
                ->filter(fn (Template $template) => $customer && $this->templateService->canAccessTemplate($template, $customer))
                ->values();
        }

        if ($tab === 'options') {
            $data['deliveryServers'] = $this->deliveryServerService
                ->getSelectableDeliveryServersForCustomer(auth('customer')->user(), false, false);
            $data['trackingDomains'] = TrackingDomain::where('customer_id', auth('customer')->id())
                ->where('status', 'verified')
                ->get();
            $data['customVariables'] = $campaign->getSetting('custom_variables', []);
            $data['customFields'] = $campaign->getSetting('custom_fields', []);
            $customer = auth('customer')->user();
            $data['templates'] = Template::query()
                ->whereIn('type', ['email', 'campaign'])
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'customer_id', 'is_system', 'is_public'])
                ->filter(fn (Template $template) => $customer && $this->templateService->canAccessTemplate($template, $customer))
                ->values();
        }

        if ($tab === 'logs') {
            $serverLogs = DeliveryServerLog::query()
                ->with(['deliveryServer:id,name,type,from_email'])
                ->whereHas('deliveryServer', function ($q) {
                    $q->where('customer_id', auth('customer')->id());
                })
                ->whereRaw("CAST(json_extract(meta, '$.outreach_campaign_id') AS INTEGER) = ?", [(int) $campaign->id])
                ->latest()
                ->limit(100)
                ->get();

            $usageByServer = $serverLogs
                ->groupBy('delivery_server_id')
                ->map(function ($logs) {
                    $server = optional($logs->first())->deliveryServer;
                    $sentCount = $logs->where('status', 'success')->count();
                    $failedCount = $logs->where('status', 'failed')->count();

                    return [
                        'server_id' => $server?->id,
                        'server_name' => $server?->name ?? __('Unknown server'),
                        'from_email' => $server?->from_email,
                        'type' => $server?->type,
                        'sent_count' => $sentCount,
                        'failed_count' => $failedCount,
                        'total_count' => $sentCount + $failedCount,
                        'last_sent_at' => optional($logs->where('status', 'success')->first())->created_at,
                    ];
                })
                ->sortByDesc('sent_count')
                ->values();

            $maxSentCount = max(1, (int) $usageByServer->max('sent_count'));

            $dailyUsage = collect(range(6, 0))->map(function ($daysAgo) use ($serverLogs) {
                $day = now()->subDays($daysAgo);
                $dayKey = $day->toDateString();
                $dayLogs = $serverLogs->filter(function ($log) use ($dayKey) {
                    return optional($log->created_at)?->toDateString() === $dayKey;
                });

                return [
                    'date' => $dayKey,
                    'label' => $day->format('M j'),
                    'sent_count' => $dayLogs->where('status', 'success')->count(),
                    'failed_count' => $dayLogs->where('status', 'failed')->count(),
                ];
            });

            $maxDailyCount = max(1, (int) $dailyUsage->max('sent_count'));

            $sequences = OutreachSequence::where('campaign_id', $campaign->id)
                ->orderBy('sort_order')
                ->get(['id', 'sort_order', 'delay_days', 'delay_type', 'subject_a', 'subject_b']);

            $logsByStep = $serverLogs->where('status', 'success')
                ->groupBy(fn ($log) => (int) ($log->meta['outreach_step'] ?? 0));

            $failedLogsByStep = $serverLogs->where('status', 'failed')
                ->groupBy(fn ($log) => (int) ($log->meta['outreach_step'] ?? 0));

            $maxCompletedStep = $logsByStep->keys()->max() ?? 0;
            $timeline = $sequences->map(function ($seq) use ($logsByStep, $failedLogsByStep, $maxCompletedStep) {
                $stepNum = $seq->sort_order;
                $stepLogs = $logsByStep->get($stepNum, collect());
                $failedLogs = $failedLogsByStep->get($stepNum, collect());
                $lastSent = $stepLogs->first()?->created_at;
                $totalSent = $stepLogs->count();
                $lastFailed = $failedLogs->first()?->created_at;
                $errorMessage = $failedLogs->first()?->error_message;

                $status = 'pending';
                if ($stepNum <= $maxCompletedStep) {
                    $status = 'done';
                } elseif ($failedLogs->isNotEmpty() && $stepLogs->isEmpty()) {
                    $status = 'failed';
                } elseif ($stepNum === $maxCompletedStep + 1) {
                    $status = 'active';
                }

                return [
                    'step' => $stepNum,
                    'title' => __('Step :step', ['step' => $stepNum]),
                    'subject' => $seq->subject_a ?: $seq->subject_b ?: __('No subject'),
                    'delay' => $seq->delay_days > 0 ? __('+:count :unit', ['count' => $seq->delay_days, 'unit' => $seq->delay_type ?: 'days']) : null,
                    'status' => $status,
                    'last_sent_at' => $lastSent,
                    'total_sent' => $totalSent,
                    'last_failed_at' => $lastFailed,
                    'failed_count' => $failedLogs->count(),
                    'error_message' => $errorMessage,
                    'recipients' => $stepLogs->map(fn ($log) => [
                        'email' => $log->to_email ?: ($log->meta['outreach_lead_id'] ?? null),
                        'sent_at' => $log->created_at,
                        'lead_id' => (int) ($log->meta['outreach_lead_id'] ?? 0),
                    ])->values()->toArray(),
                    'failed_recipients' => $failedLogs->map(fn ($log) => [
                        'email' => $log->to_email,
                        'sent_at' => $log->created_at,
                        'error' => $log->error_message,
                    ])->values()->toArray(),
                ];
            })->values();

            $data['sequences'] = $sequences;
            $data['timeline'] = $timeline;
            $data['serverUsageStats'] = $usageByServer;
            $data['serverUsageMaxSent'] = $maxSentCount;
            $data['serverDailyUsage'] = $dailyUsage;
            $data['serverDailyUsageMaxSent'] = $maxDailyCount;
            $data['recentServerLogs'] = $serverLogs;
        }

        return view('customer.outreach.show', $data);
    }

    public function preview(Request $request, int $campaign)
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);
        $stepIndex = max(0, (int) $request->query('step', 0));
        $variant = $request->query('variant', 'a') === 'b' ? 'b' : 'a';
        $step = OutreachSequence::where('campaign_id', $campaign->id)
            ->orderBy('sort_order')
            ->skip($stepIndex)
            ->firstOrFail();
        $lead = OutreachLead::where('campaign_id', $campaign->id)->orderBy('id')->first();

        if (! $lead) {
            $lead = new OutreachLead([
                'email' => 'preview@example.com',
                'first_name' => 'Alex',
                'last_name' => 'Preview',
                'company' => 'Example Co',
                'meta' => collect($campaign->getSetting('custom_fields', []))
                    ->mapWithKeys(fn ($field) => [($field['key'] ?? '') => 'Sample value'])
                    ->filter(fn ($value, $key) => $key !== '')
                    ->all(),
            ]);
        }

        $subjectTemplate = $variant === 'b' && $step->has_variant_b ? $step->subject_b : $step->subject_a;
        $bodyTemplate = $variant === 'b' && $step->has_variant_b ? $step->body_b : $step->body_a;
        $subject = $lead->renderTokensForCampaign((string) $subjectTemplate, $campaign);
        $body = $lead->renderTokensForCampaign((string) $bodyTemplate, $campaign);

        return response()->view('customer.outreach.preview', compact('campaign', 'lead', 'subject', 'body'));
    }

    private function buildStatusInsights(OutreachCampaign $campaign): array
    {
        $issues = [];

        if ($campaign->leads_count < 1) {
            $issues[] = __('No leads have been added to this campaign yet.');
        }

        if (! OutreachSequence::where('campaign_id', $campaign->id)->exists()) {
            $issues[] = __('No sequence steps have been configured yet.');
        }

        if (count($campaign->getSetting('sender_account_ids', [])) < 1) {
            $issues[] = __('No sender account has been selected in campaign options.');
        }

        $logs = $campaign->statusLogs()->map(function ($log) {
            $status = (string) ($log['status'] ?? 'info');

            return [
                'status' => $status,
                'badge' => match ($status) {
                    'failed' => 'text-red-700 bg-red-50 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
                    'paused' => 'text-amber-700 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
                    'active' => 'text-green-700 bg-green-50 border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800',
                    default => 'text-gray-700 bg-gray-50 border-gray-200 dark:bg-white/5 dark:text-admin-text-primary dark:border-admin-border',
                },
                'title' => Str::headline($status),
                'message' => (string) ($log['message'] ?? ''),
                'created_at' => $log['created_at'] ?? null,
            ];
        })->all();

        if (empty($logs) && in_array($campaign->status, ['paused', 'failed'], true)) {
            $logs[] = [
                'status' => $campaign->status,
                'badge' => $campaign->status === 'failed'
                    ? 'text-red-700 bg-red-50 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800'
                    : 'text-amber-700 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
                'title' => Str::headline($campaign->status),
                'message' => $campaign->status === 'failed'
                    ? __('This campaign entered a failed state. Review the setup below and retry after fixing the issue.')
                    : __('This campaign is currently paused.'),
                'created_at' => optional($campaign->updated_at)?->toDateTimeString(),
            ];
        }

        return [
            'issues' => $issues,
            'logs' => $logs,
            'has_attention_items' => ! empty($issues) || ! empty($logs),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guardAddon();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $campaign = OutreachCampaign::create([
            'customer_id' => auth('customer')->id(),
            'name' => $validated['name'],
            'status' => 'draft',
            'settings' => (new OutreachCampaign)->getDefaultSettings(),
        ]);

        return redirect()->route('customer.outreach.campaigns.show', $campaign)
            ->with('success', __('Campaign created successfully.'));
    }

    public function duplicate(int $campaign): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        $duplicate = DB::transaction(function () use ($campaign) {
            $copy = OutreachCampaign::create([
                'customer_id' => auth('customer')->id(),
                'name' => $campaign->name.' '.__('(Copy)'),
                'status' => 'draft',
                'leads_count' => 0,
                'settings' => $campaign->settings ?? $campaign->getDefaultSettings(),
            ]);

            foreach ($campaign->sequences()->get() as $sequence) {
                OutreachSequence::create([
                    'campaign_id' => $copy->id,
                    'sort_order' => $sequence->sort_order,
                    'delay_days' => $sequence->delay_days,
                    'delay_type' => $sequence->delay_type,
                    'subject_a' => $sequence->subject_a,
                    'body_a' => $sequence->body_a,
                    'footer_a' => $sequence->footer_a,
                    'subject_b' => $sequence->subject_b,
                    'body_b' => $sequence->body_b,
                    'footer_b' => $sequence->footer_b,
                    'variant_split' => $sequence->variant_split,
                    'has_variant_b' => $sequence->has_variant_b,
                ]);
            }

            return $copy;
        });

        return redirect()->route('customer.outreach.campaigns.show', ['campaign' => $duplicate, 'tab' => 'options'])
            ->with('success', __('Campaign duplicated successfully.'));
    }

    public function updateLeads(Request $request, int $campaign): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        $request->validate([
            'leads_source' => 'nullable|in:list,form,paste,file',
            'leads_import_mode' => 'in:append,replace',
            'import_source' => 'nullable|string',
            'rows' => 'nullable|array',
            'rows.*.email' => 'nullable|string',
            'rows.*.first_name' => 'nullable|string|max:255',
            'rows.*.last_name' => 'nullable|string|max:255',
            'rows.*.company' => 'nullable|string|max:255',
            'rows.*.meta' => 'nullable|array',
            'rows.*.meta.*' => 'nullable|string|max:2000',
            'leads' => 'nullable|string',
            'paste_data' => 'nullable|string',
            'import_file' => 'nullable|file|mimes:csv,xlsx,xls',
            'field_mapping' => 'nullable|array',
            'field_mapping.*' => 'nullable|string|max:255',
        ]);

        $source = $request->input('leads_source', 'form');
        $mode = $request->input('leads_import_mode', 'append');
        $rows = [];

        if ($source === 'list') {
            $importSource = $request->input('import_source', '');
            if (! $importSource) {
                return back()->withErrors(['import_source' => __('Please select a list or segment.')]);
            }

            // Parse import_source format: "list:{id}" or "segment:{id}"
            $parts = explode(':', $importSource);
            if (count($parts) !== 2) {
                return back()->withErrors(['import_source' => __('Invalid selection format.')]);
            }

            $type = $parts[0];
            $id = (int) $parts[1];

            if ($type === 'list') {
                $list = EmailList::where('customer_id', auth('customer')->id())
                    ->where('id', $id)
                    ->first();

                if (! $list) {
                    return back()->withErrors(['import_source' => __('Please select a valid email list.')]);
                }

                $subscriberQuery = ListSubscriber::where('list_id', $list->id)
                    ->whereIn('status', ['confirmed', 'unconfirmed']);
            } elseif ($type === 'segment') {
                $segment = ListSegment::where('id', $id)
                    ->where('is_active', true)
                    ->first();

                if (! $segment) {
                    return back()->withErrors(['import_source' => __('Please select a valid segment.')]);
                }

                // Verify the segment belongs to a list owned by the customer
                $list = EmailList::where('id', $segment->list_id)
                    ->where('customer_id', auth('customer')->id())
                    ->first();

                if (! $list) {
                    return back()->withErrors(['import_source' => __('Please select a valid segment.')]);
                }

                $subscriberQuery = ListSubscriber::where('list_id', $segment->list_id)
                    ->whereIn('status', ['confirmed', 'unconfirmed'])
                    ->whereHas('segments', function ($q) use ($id) {
                        $q->where('list_segments.id', $id);
                    });
            } else {
                return back()->withErrors(['import_source' => __('Invalid selection type.')]);
            }

            $subscriberQuery->select(['email', 'first_name', 'last_name', 'company', 'custom_fields'])
                ->chunk(500, function ($chunk) use (&$rows, $campaign) {
                    foreach ($chunk as $sub) {
                        $email = trim((string) $sub->email);
                        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }
                        $rows[] = [
                            'campaign_id' => $campaign->id,
                            'uuid' => (string) \Illuminate\Support\Str::uuid(),
                            'email' => strtolower($email),
                            'first_name' => (string) ($sub->first_name ?? ''),
                            'last_name' => (string) ($sub->last_name ?? ''),
                            'company' => (string) ($sub->company ?? ''),
                            'meta' => $this->leadMetaFromSubscriber($sub, $campaign),
                            'status' => 'pending',
                            'current_step' => 0,
                            'next_send_at' => now(),
                            'last_activity_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                });
        } elseif ($source === 'form' && is_array($request->input('rows'))) {
            foreach ((array) $request->input('rows', []) as $row) {
                $email = trim((string) ($row['email'] ?? ''));
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $rows[] = [
                    'campaign_id' => $campaign->id,
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'email' => strtolower($email),
                    'first_name' => trim((string) ($row['first_name'] ?? '')),
                    'last_name' => trim((string) ($row['last_name'] ?? '')),
                    'company' => trim((string) ($row['company'] ?? '')),
                    'meta' => $this->cleanLeadMeta($row['meta'] ?? [], $campaign),
                    'status' => 'pending',
                    'current_step' => 0,
                    'next_send_at' => now(),
                    'last_activity_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        } elseif ($source === 'paste') {
            // Parse CSV data from textarea
            $raw = (string) $request->input('paste_data', '');
            $lines = array_filter(array_map('trim', explode("\n", $raw)));
            foreach ($lines as $line) {
                $parts = $this->parseCsvLine($line);
                $email = trim($parts[0] ?? '');
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $rows[] = [
                    'campaign_id' => $campaign->id,
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'email' => strtolower($email),
                    'first_name' => trim($parts[1] ?? ''),
                    'last_name' => trim($parts[2] ?? ''),
                    'company' => trim($parts[3] ?? ''),
                    'meta' => [],
                    'status' => 'pending',
                    'current_step' => 0,
                    'next_send_at' => now(),
                    'last_activity_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        } elseif ($source === 'file') {
            // Parse uploaded CSV/XLS file
            $file = $request->file('import_file');
            if (! $file) {
                return back()->withErrors(['import_file' => __('Please select a file to upload.')]);
            }

            $extension = $file->getClientOriginalExtension();
            $rows = $this->parseImportFile($file, $extension, $campaign, (array) $request->input('field_mapping', []));
        } else {
            // Backward compatibility: parse textarea CSV input
            $raw = (string) $request->input('leads', '');
            $lines = array_filter(array_map('trim', explode("\n", $raw)));
            foreach ($lines as $line) {
                $parts = $this->parseCsvLine($line);
                $email = trim($parts[0] ?? '');
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $rows[] = [
                    'campaign_id' => $campaign->id,
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'email' => strtolower($email),
                    'first_name' => trim($parts[1] ?? ''),
                    'last_name' => trim($parts[2] ?? ''),
                    'company' => trim($parts[3] ?? ''),
                    'meta' => [],
                    'status' => 'pending',
                    'current_step' => 0,
                    'next_send_at' => now(),
                    'last_activity_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (empty($rows) && $mode !== 'replace') {
            return back()->withErrors(['leads' => __('No valid leads were provided.')]);
        }

        DB::transaction(function () use ($campaign, $mode, $rows) {
            if ($mode === 'replace') {
                OutreachLead::where('campaign_id', $campaign->id)->delete();
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                $chunk = array_map(function (array $row): array {
                    if (isset($row['meta']) && is_array($row['meta'])) {
                        $row['meta'] = json_encode($row['meta'], JSON_UNESCAPED_UNICODE);
                    }

                    return $row;
                }, $chunk);
                OutreachLead::insertOrIgnore($chunk);
            }

            $campaign->update([
                'leads_count' => OutreachLead::where('campaign_id', $campaign->id)->count(),
            ]);
        });

        return redirect()->route('customer.outreach.campaigns.show', [$campaign->id, 'tab' => 'leads'])
            ->with('success', __('Leads imported successfully.'));
    }

    /**
     * Parse imported file (CSV or Excel)
     */
    private function parseImportFile($file, string $extension, $campaign, array $fieldMapping = []): array
    {
        $rows = [];

        if ($extension === 'csv') {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle) {
                $header = fgetcsv($handle);
                $columns = $this->resolveCsvColumns($header, $fieldMapping);
                while (($data = fgetcsv($handle)) !== false) {
                    $row = $this->buildLeadRow($data, $columns, $campaign);
                    if ($row !== null) {
                        $rows[] = $row;
                    }
                }
                fclose($handle);
            }
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rowsData = $worksheet->toArray();

            $header = array_shift($rowsData);
            $columns = $this->resolveCsvColumns($header, $fieldMapping);

            foreach ($rowsData as $data) {
                $row = $this->buildLeadRow($data, $columns, $campaign);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * Resolve CSV column indices from header row.
     * Falls back to positional mapping (0=email, 1=first_name, etc.) if no recognizable header is found.
     */
    private function resolveCsvColumns(?array $header, array $fieldMapping = []): array
    {
        $columns = [
            'email' => 0,
            'first_name' => 1,
            'last_name' => 2,
            'company' => 3,
            'name' => null,
            'custom_fields' => [],
        ];

        if (! $header || empty($header)) {
            return $columns;
        }

        $normalized = [];
        foreach ($header as $i => $h) {
            $key = strtolower(trim((string) $h));
            $key = preg_replace('/[\s\-]+/', '_', $key) ?? $key;
            $normalized[$key] = $i;
        }

        foreach ((array) $fieldMapping as $key => $columnName) {
            $key = trim((string) $key);
            $columnName = strtolower(trim((string) $columnName));
            if ($key === '' || $columnName === '') {
                continue;
            }
            if (isset($normalized[$columnName])) {
                $columns['custom_fields'][$key] = $normalized[$columnName];
            }
        }

        // Find email column by header name
        $emailIdx = null;
        foreach (['email', 'e_mail', 'mail', 'email_address'] as $c) {
            if (isset($normalized[$c])) {
                $emailIdx = $normalized[$c];
                break;
            }
        }

        // If no email-like header found, fall back to positional
        if ($emailIdx === null) {
            return $columns;
        }

        $columns['email'] = $emailIdx;

        $hasFirstName = false;
        foreach (['first_name', 'firstname', 'first', 'fname', 'given_name'] as $c) {
            if (isset($normalized[$c])) {
                $columns['first_name'] = $normalized[$c];
                $hasFirstName = true;
                break;
            }
        }

        $hasLastName = false;
        foreach (['last_name', 'lastname', 'last', 'lname', 'surname', 'family_name'] as $c) {
            if (isset($normalized[$c])) {
                $columns['last_name'] = $normalized[$c];
                $hasLastName = true;
                break;
            }
        }

        foreach (['company', 'organization', 'organisation', 'org', 'business'] as $c) {
            if (isset($normalized[$c])) {
                $columns['company'] = $normalized[$c];
                break;
            }
        }

        // If no first_name/last_name columns, look for a "name" / "full name" column
        if (! $hasFirstName && ! $hasLastName) {
            foreach (['name', 'full_name', 'fullname'] as $c) {
                if (isset($normalized[$c])) {
                    $columns['name'] = $normalized[$c];
                    $columns['first_name'] = null;
                    $columns['last_name'] = null;
                    break;
                }
            }
        }

        return $columns;
    }

    /**
     * Build a lead row array from parsed CSV/Excel data using resolved column indices.
     */
    private function buildLeadRow(array $data, array $columns, $campaign): ?array
    {
        $email = trim($data[$columns['email']] ?? '');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $firstName = '';
        $lastName = '';

        if ($columns['name'] !== null) {
            $fullName = trim($data[$columns['name']] ?? '');
            [$firstName, $lastName] = $this->splitFullName($fullName);
        } else {
            if ($columns['first_name'] !== null) {
                $firstName = trim($data[$columns['first_name']] ?? '');
            }
            if ($columns['last_name'] !== null) {
                $lastName = trim($data[$columns['last_name']] ?? '');
            }
        }

        $company = '';
        if ($columns['company'] !== null) {
            $company = trim($data[$columns['company']] ?? '');
        }

        return [
            'campaign_id' => $campaign->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'email' => strtolower($email),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company' => $company,
            'meta' => $this->cleanLeadMeta(
                $this->customMetaFromColumns($data, $columns['custom_fields'] ?? []),
                $campaign
            ),
            'status' => 'pending',
            'current_step' => 0,
            'next_send_at' => now(),
            'last_activity_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function customMetaFromColumns(array $data, array $columns): array
    {
        $meta = [];
        foreach ($columns as $key => $index) {
            $key = trim((string) $key);
            if ($key === '' || ! is_int($index) && ! is_numeric($index)) {
                continue;
            }
            $value = trim((string) ($data[(int) $index] ?? ''));
            if ($value !== '') {
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

    private function cleanLeadMeta(mixed $meta, OutreachCampaign $campaign): array
    {
        $allowed = collect($campaign->getSetting('custom_fields', []))
            ->map(fn ($field) => is_array($field) ? trim((string) ($field['key'] ?? '')) : '')
            ->filter()
            ->all();
        $allowed = array_fill_keys($allowed, true);
        $clean = [];
        foreach ((array) $meta as $key => $value) {
            $key = trim((string) $key);
            if ($key !== '' && (empty($allowed) || isset($allowed[$key])) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                $clean[$key] = (string) ($value ?? '');
            }
        }

        return $clean;
    }

    private function leadMetaFromSubscriber(ListSubscriber $subscriber, OutreachCampaign $campaign): array
    {
        return $this->cleanLeadMeta($subscriber->custom_fields ?? [], $campaign);
    }

    /**
     * Split a full name into first and last name.
     */
    private function splitFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');
        if ($fullName === '') {
            return ['', ''];
        }

        $parts = explode(' ', $fullName);
        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $first = array_shift($parts);
        $last = implode(' ', $parts);

        return [trim($first), trim($last)];
    }

    /**
     * Parse a single CSV line auto-detecting common delimiters.
     */
    private function parseCsvLine(string $line): array
    {
        $delimiters = [',', "\t", ';', '|'];
        $best = [];
        $bestCount = 0;

        foreach ($delimiters as $delimiter) {
            $fields = str_getcsv($line, $delimiter);
            $count = count($fields);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $fields;
            }
        }

        return $best ?: [$line];
    }

    public function updateLead(Request $request, int $campaign, OutreachLead $lead): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        if ($lead->campaign_id !== $campaign->id) {
            abort(404);
        }

        $statusOptions = ['pending', 'sent', 'opened', 'clicked', 'replied', 'bounced', 'unsubscribed'];

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', $statusOptions)],
            'meta' => ['nullable', 'array'],
            'meta.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $duplicate = OutreachLead::where('campaign_id', $campaign->id)
            ->where('email', strtolower($validated['email']))
            ->where('id', '!=', $lead->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['email' => __('Another lead in this campaign already uses that email.')])->withInput();
        }

        // Merge submitted meta with existing meta so unrelated keys are preserved.
        $existingMeta = is_array($lead->meta) ? $lead->meta : [];
        $submittedMeta = $validated['meta'] ?? [];
        $cleanMeta = [];
        foreach ($submittedMeta as $key => $value) {
            $key = (string) $key;
            if ($key === '' || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                continue;
            }
            $cleanMeta[$key] = (string) ($value ?? '');
        }

        $lead->update([
            'email' => strtolower($validated['email']),
            'first_name' => $validated['first_name'] ?? '',
            'last_name' => $validated['last_name'] ?? '',
            'company' => $validated['company'] ?? '',
            'status' => $validated['status'],
            'meta' => array_merge($existingMeta, $cleanMeta),
        ]);

        return back()->with('success', __('Lead updated successfully.'));
    }

    public function updateVariables(Request $request, int $campaign)
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        $request->validate([
            'variables' => ['nullable', 'array'],
            'variables.*.name' => ['nullable', 'string', 'max:255'],
            'variables.*.tokenName' => ['nullable', 'string', 'max:64'],
            'variables.*.field' => ['nullable', 'string', 'max:64'],
        ]);

        $builtIn = ['email', 'first_name', 'last_name', 'full_name', 'company'];
        $customFieldKeys = collect($campaign->getSetting('custom_fields', []))
            ->map(fn ($f) => is_array($f) ? (string) ($f['key'] ?? '') : '')
            ->filter()
            ->values()
            ->all();
        $allowedFields = array_merge($builtIn, $customFieldKeys);

        $clean = [];
        $seen = [];
        foreach ((array) $request->input('variables', []) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $token = trim((string) ($row['tokenName'] ?? ''));
            $field = trim((string) ($row['field'] ?? ''));
            if ($token === '' || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $token)) {
                continue;
            }
            if (isset($seen[$token])) {
                continue;
            }
            if ($field !== '' && ! in_array($field, $allowedFields, true)) {
                $field = '';
            }
            $seen[$token] = true;
            $clean[] = [
                'name' => $name !== '' ? $name : $token,
                'tokenName' => $token,
                'field' => $field,
            ];
        }

        $settings = array_merge($campaign->getDefaultSettings(), $campaign->settings ?? []);
        $settings['custom_variables'] = $clean;
        $campaign->update(['settings' => $settings]);

        if ($request->wantsJson()) {
            return response()->json(['variables' => $clean]);
        }

        return back()->with('success', __('Variables saved.'));
    }

    public function updateCustomFields(Request $request, int $campaign)
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        $request->validate([
            'fields' => ['nullable', 'array'],
            'fields.*.key' => ['nullable', 'string', 'max:64'],
            'fields.*.label' => ['nullable', 'string', 'max:255'],
        ]);

        $reserved = ['email', 'first_name', 'last_name', 'full_name', 'company'];
        $clean = [];
        $seen = [];
        foreach ((array) $request->input('fields', []) as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            if ($key === '' || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                continue;
            }
            if (in_array($key, $reserved, true) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = [
                'key' => $key,
                'label' => $label !== '' ? $label : $key,
            ];
        }

        $settings = array_merge($campaign->getDefaultSettings(), $campaign->settings ?? []);
        $settings['custom_fields'] = $clean;
        $campaign->update(['settings' => $settings]);

        if ($request->wantsJson()) {
            return response()->json(['fields' => $clean]);
        }

        return back()->with('success', __('Custom fields saved.'));
    }

    public function destroyLead(int $campaign, OutreachLead $lead): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        if ($lead->campaign_id !== $campaign->id) {
            abort(404);
        }

        $lead->delete();
        $campaign->decrement('leads_count');

        return back()->with('success', __('Lead removed.'));
    }

    public function updateSequences(Request $request, int $campaign): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        $steps = $request->input('steps', []);

        DB::transaction(function () use ($campaign, $steps) {
            OutreachSequence::where('campaign_id', $campaign->id)->delete();

            foreach ($steps as $i => $step) {
                OutreachSequence::create([
                    'campaign_id' => $campaign->id,
                    'sort_order' => $i,
                    'delay_days' => max(0, (int) ($step['delay_days'] ?? 0)),
                    'delay_type' => in_array(($step['delay_type'] ?? 'days'), ['minutes', 'hours', 'days', 'weeks', 'months'], true)
                        ? $step['delay_type']
                        : 'days',
                    'subject_a' => $step['subject_a'] ?? '',
                    'body_a' => $step['body_a'] ?? '',
                    'footer_a' => $step['footer_a'] ?? '',
                    'subject_b' => $step['subject_b'] ?? null,
                    'body_b' => $step['body_b'] ?? null,
                    'footer_b' => $step['footer_b'] ?? null,
                    'variant_split' => min(100, max(0, (int) ($step['variant_split'] ?? 50))),
                    'has_variant_b' => ! empty($step['has_variant_b']),
                ]);
            }
        });

        return back()->with('success', __('Sequences saved.'));
    }

    public function updateSchedule(Request $request, int $campaign): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        $timeBlocks = collect($request->input('send_time_blocks', []))
            ->map(function ($block) {
                if (! is_array($block)) {
                    return null;
                }

                $start = (string) ($block['start'] ?? '');
                $end = (string) ($block['end'] ?? '');

                if (! preg_match('/^\d{2}:\d{2}$/', $start) || ! preg_match('/^\d{2}:\d{2}$/', $end)) {
                    return null;
                }

                return [
                    'start' => $start,
                    'end' => $end,
                ];
            })
            ->filter()
            ->values();

        if ($timeBlocks->isEmpty()) {
            $timeBlocks = collect([[
                'start' => (string) $request->input('send_hours_start', '09:00'),
                'end' => (string) $request->input('send_hours_end', '17:30'),
            ]]);
        }

        $primaryBlock = $timeBlocks->first();

        $settings = array_merge($campaign->settings ?? $campaign->getDefaultSettings(), [
            'timezone' => $request->input('timezone', 'UTC'),
            'send_days' => $request->input('send_days', []),
            'send_hours_start' => $primaryBlock['start'] ?? '09:00',
            'send_hours_end' => $primaryBlock['end'] ?? '17:30',
            'send_time_blocks' => $timeBlocks->all(),
            'max_per_day' => max(1, (int) $request->input('max_per_day', 150)),
            'rate_per_minute' => max(0, (int) $request->input('rate_per_minute', 0)),
            'min_delay_enabled' => $request->boolean('min_delay_enabled'),
            'min_delay' => max(1, (int) $request->input('min_delay', 1)),
            'min_delay_unit' => in_array($request->input('min_delay_unit'), ['seconds', 'minutes', 'hours']) ? $request->input('min_delay_unit') : 'minutes',
            'track_opens' => $request->boolean('track_opens'),
            'track_clicks' => $request->boolean('track_clicks'),
        ]);

        $campaign->update(['settings' => $settings]);

        return back()->with('success', __('Schedule saved.'));
    }

    public function updateOptions(Request $request, int $campaign): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        $settings = array_merge($campaign->settings ?? $campaign->getDefaultSettings(), [
            'stop_on_reply' => $request->boolean('stop_on_reply'),
            'stop_on_auto_reply' => $request->boolean('stop_on_auto_reply'),
            'tracking_domain' => $request->input('tracking_domain'),
            'bcc_email' => $request->input('bcc_email'),
            'email_footer' => (string) $request->input('email_footer', ''),
            'sender_account_ids' => $request->input('sender_account_ids', []),
            'enable_account_rotation' => $request->boolean('enable_account_rotation'),
            'send_styled_body' => $request->boolean('send_styled_body'),
            'include_unsubscribe_link' => $request->boolean('include_unsubscribe_link'),
            'unsubscribe_text' => $request->input('unsubscribe_text', ''),
        ]);

        $campaign->update([
            'name' => $request->input('name', $campaign->name),
            'settings' => $settings,
        ]);

        return back()->with('success', __('Options saved.'));
    }

    public function pause(int $campaign): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);
        $campaign->update(['status' => 'paused']);
        $campaign->appendStatusLog('paused', __('Campaign was paused manually.'));

        return back()->with('success', __('Campaign paused.'));
    }

    public function resume(int $campaign): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        $issues = $this->buildStatusInsights($campaign)['issues'];

        if (! empty($issues)) {
            $campaign->update(['status' => 'failed']);
            $campaign->appendStatusLog('failed', __('Campaign could not be started: :reasons', ['reasons' => implode(' ', $issues)]));

            return back()->with('error', __('Campaign could not be started: :issues', ['issues' => implode('; ', $issues)]));
        }

        $campaign->update(['status' => 'active']);
        $campaign->appendStatusLog('active', __('Campaign was started successfully.'));

        return back()->with('success', __('Campaign resumed.'));
    }

    public function destroy(int $campaign): RedirectResponse
    {
        $this->guardAddon();
        $campaign = $this->ownedCampaign($campaign);

        DB::transaction(function () use ($campaign) {
            OutreachSequence::where('campaign_id', $campaign->id)->delete();
            OutreachLead::where('campaign_id', $campaign->id)->delete();
            $campaign->delete();
        });

        return redirect()->route('customer.outreach.index')
            ->with('success', __('Campaign deleted.'));
    }
}

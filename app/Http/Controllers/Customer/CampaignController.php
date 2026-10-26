<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Jobs\StartCampaignJob;
use App\Models\Automation;
use App\Models\BounceLog;
use App\Models\BounceServer;
use App\Models\Campaign;
use App\Models\DeliveryServer;
use App\Models\EmailList;
use App\Models\ListSegment;
use App\Models\ListSubscriber;
use App\Models\ReplyServer;
use App\Models\SendingDomain;
use App\Models\Template;
use App\Models\TrackingDomain;
use App\Notifications\CampaignStatusUpdatedNotification;
use App\Services\CampaignService;
use App\Services\DeliveryServerService;
use App\Services\SendingSpeedResolver;
use App\Services\SpamScoringService;
use App\Services\TemplateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $campaignService,
        protected TemplateService $templateService
    ) {
        $this->middleware('customer.access:campaigns.permissions.can_access_campaigns')->only([
            'index',
            'show',
            'stats',
            'recipients',
            'replies',
        ]);
        $this->middleware('customer.access:campaigns.permissions.can_create_campaigns')->only(['create', 'store', 'duplicate']);
        $this->middleware('customer.access:campaigns.permissions.can_edit_campaigns')->only(['edit', 'update']);
        $this->middleware('customer.access:campaigns.permissions.can_delete_campaigns')->only(['destroy']);
        $this->middleware('customer.access:campaigns.permissions.can_start_campaigns')->only(['start', 'pause', 'stop', 'resume', 'rerun']);

        $this->middleware('demo.prevent')->only([
            'create',
            'store',
            'duplicate',
            'destroy',
            'start',
            'pause',
            'stop',
            'resume',
            'rerun',
        ]);
    }

    protected function authorizeOwnership(Campaign $campaign): Campaign
    {
        $customer = auth('customer')->user();

        if (! $customer || ! $this->workspaceResourceMatches($campaign, $customer)) {
            abort(404);
        }

        return $campaign;
    }

    protected function getCampaignStartDeliverabilityResult(Campaign $campaign): ?array
    {
        $spamScoringEnabled = $campaign->settings['spam_scoring_enabled'] ?? $campaign->customer?->groupSetting('spam_scoring.enabled', false);

        if (! $spamScoringEnabled) {
            return null;
        }

        $fromEmail = $campaign->from_email
            ?: $campaign->deliveryServer?->from_email
            ?: $campaign->emailList?->from_email
            ?: config('mail.from.address');

        $replyToEmail = $campaign->reply_to ?: $fromEmail;

        $rotationEnabled = (bool) data_get($campaign->settings, 'inbox_rotation_enabled', false);
        $rotationServerIds = collect((array) data_get($campaign->settings, 'inbox_rotation_server_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $rotationServerTypes = [];
        if ($rotationEnabled && ! empty($rotationServerIds)) {
            $rotationServerTypes = \App\Models\DeliveryServer::query()
                ->whereIn('id', $rotationServerIds)
                ->pluck('type')
                ->map(fn ($type) => strtolower((string) $type))
                ->all();
        }

        return app(SpamScoringService::class)->calculateSpamScore(
            $campaign->subject ?? '',
            $campaign->html_content ?? '',
            $campaign->plain_text_content ?? '',
            [
                'from_name' => $campaign->from_name,
                'from_email' => $fromEmail,
                'reply_to' => $replyToEmail,
                'delivery_server_id' => $campaign->delivery_server_id,
                'delivery_server_type' => $campaign->deliveryServer?->type,
                'delivery_server_from_email' => $campaign->deliveryServer?->from_email,
                'reply_server_id' => $campaign->reply_server_id,
                'delivery_server_mode' => $rotationEnabled ? 'rotation' : 'single',
                'rotation_server_ids' => $rotationServerIds,
                'rotation_server_types' => $rotationServerTypes,
            ]
        );
    }

    private function accessibleTemplateIdsForCustomer(int $customerId, array $types = ['email', 'campaign']): array
    {
        $customer = auth('customer')->user();
        if (! $customer || (int) $customer->id !== $customerId) {
            return [];
        }

        return Template::query()
            ->whereIn('type', $types)
            ->get(['id', 'customer_id', 'is_system', 'is_public'])
            ->filter(fn (Template $template) => $this->templateService->canAccessTemplate($template, $customer))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function normalizeUnlayerDesign(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || $value === 'null') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return null;
        }

        $sanitized = $this->sanitizeUnlayerContent($decoded);

        return is_array($sanitized) ? $sanitized : null;
    }

    private function stripLegacyUnsubscribeArtifacts(?string $content): ?string
    {
        if (! is_string($content) || $content === '') {
            return $content;
        }

        $updated = preg_replace(
            '/<p\b[^>]*>\s*<a\b[^>]*>\s*Unsubscribe(?:\s+from\s+this\s+list)?\s*<\/a>\s*<\/p>/i',
            '',
            $content
        );

        $updated = preg_replace('/\s*---\s*Unsubscribe:\s*https?:\/\/\S+/i', '', (string) $updated);
        $updated = preg_replace('/(?:\r?\n\s*){3,}/', "\n\n", (string) $updated);

        return is_string($updated) ? trim($updated) : $content;
    }

    private function sanitizeUnlayerContent(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->sanitizeUnlayerContent($item);
            }

            return $value;
        }

        if (is_string($value)) {
            return $this->stripLegacyUnsubscribeArtifacts($value) ?? $value;
        }

        return $value;
    }

    private function buildUnlayerTemplateData(mixed $value): ?array
    {
        $decoded = $this->normalizeUnlayerDesign($value);
        if ($decoded === null) {
            return null;
        }

        return [
            'builder' => 'unlayer',
            'unlayer' => $decoded,
        ];
    }

    private function unlayerDesignFromCampaign(Campaign $campaign): ?array
    {
        $data = $campaign->template_data;
        if (! is_array($data)) {
            return null;
        }

        if (($data['builder'] ?? null) !== 'unlayer') {
            return null;
        }

        $unlayer = $data['unlayer'] ?? null;
        $sanitized = $this->sanitizeUnlayerContent($unlayer);

        return is_array($sanitized) ? $sanitized : null;
    }

    private function buildCampaignTagsByList($emailLists): array
    {
        $tagsByList = [];
        $listIds = [];

        foreach ($emailLists as $list) {
            $listId = (int) ($list->id ?? 0);
            if ($listId <= 0) {
                continue;
            }

            $listIds[] = $listId;

            $defs = is_array($list->custom_fields) ? $list->custom_fields : [];
            $custom = [];
            foreach ($defs as $def) {
                if (! is_array($def)) {
                    continue;
                }

                $key = trim((string) ($def['key'] ?? ''));
                if ($key === '') {
                    continue;
                }

                $label = trim((string) ($def['label'] ?? $key));
                $custom[] = [
                    'label' => $label,
                    'tag' => '@{{cf:'.$key.'}}',
                ];
            }

            $tagsByList[(string) $listId] = [
                'custom' => $custom,
                'standard' => [],
            ];
        }

        if (empty($listIds)) {
            return $tagsByList;
        }

        $availabilityRows = ListSubscriber::query()
            ->select('list_id')
            ->selectRaw("MAX(CASE WHEN TRIM(COALESCE(first_name, '')) <> '' THEN 1 ELSE 0 END) as has_first_name")
            ->selectRaw("MAX(CASE WHEN TRIM(COALESCE(last_name, '')) <> '' THEN 1 ELSE 0 END) as has_last_name")
            ->whereIn('list_id', $listIds)
            ->whereNull('deleted_at')
            ->groupBy('list_id')
            ->get()
            ->keyBy(function ($row) {
                return (string) $row->list_id;
            });

        foreach ($listIds as $listId) {
            $key = (string) $listId;
            $row = $availabilityRows->get($key);
            $hasFirst = (bool) ($row?->has_first_name ?? false);
            $hasLast = (bool) ($row?->has_last_name ?? false);
            $hasName = $hasFirst || $hasLast;

            $standard = [];
            if ($hasFirst) {
                $standard[] = ['label' => 'First Name', 'tag' => '@{{first_name}}'];
            }
            if ($hasLast) {
                $standard[] = ['label' => 'Last Name', 'tag' => '@{{last_name}}'];
            }

            $standard[] = ['label' => 'Email', 'tag' => '@{{email}}'];

            if ($hasName) {
                $standard[] = ['label' => 'Full Name', 'tag' => '@{{full_name}}'];
                $standard[] = ['label' => 'Name', 'tag' => '@{{name}}'];
            }

            if (isset($tagsByList[$key])) {
                $tagsByList[$key]['standard'] = $standard;
            }
        }

        return $tagsByList;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'type']);
        $campaigns = $this->campaignService->getPaginated(auth('customer')->user(), $filters);

        // Calculate expected recipients for campaigns that haven't started yet
        $campaigns->getCollection()->transform(function ($campaign) {
            if ($campaign->total_recipients === 0 && $campaign->emailList) {
                $campaign->expected_recipients = $this->calculateExpectedRecipients($campaign);
            } else {
                $campaign->expected_recipients = $campaign->total_recipients;
            }

            return $campaign;
        });

        return view('customer.campaigns.index', compact('campaigns', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customer = auth('customer')->user();
        $runPreflightIssues = [];
        $emailLists = $this->workspaceResourceQuery(EmailList::query(), $customer)
            ->where('status', 'active')
            ->withCount('confirmedSubscribers')
            ->with(['segments' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        $templates = Template::where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhere(function ($subQ) {
                    $subQ->where('is_public', true)
                        ->where('is_system', false);
                });
        })
            ->whereIn('type', ['email', 'campaign'])
            ->get();

        $footerTemplates = Template::where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhere(function ($subQ) {
                    $subQ->where('is_public', true)
                        ->where('is_system', false);
                });
        })
            ->where('type', 'footer')
            ->get();

        $signatureTemplates = Template::where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhere(function ($subQ) {
                    $subQ->where('is_public', true)
                        ->where('is_system', false);
                });
        })
            ->where('type', 'signature')
            ->get();

        $mustAddDelivery = (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false);
        $mustAddReply = (bool) $customer->groupSetting('servers.permissions.must_add_reply_server', false);
        $mustAddSending = (bool) $customer->groupSetting('domains.sending_domains.must_add', false);
        $mustAddTracking = (bool) $customer->groupSetting('domains.tracking_domains.must_add', false);
        $canUseSystem = (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false);

        $deliveryServers = app(DeliveryServerService::class)->getSelectableDeliveryServersForCustomer(
            $customer,
            $mustAddDelivery,
            $canUseSystem
        );

        $replyServers = ReplyServer::query()
            ->where('active', true)
            ->when($mustAddReply, function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            }, function ($q) use ($customer, $canUseSystem) {
                $q->where(function ($sub) use ($customer, $canUseSystem) {
                    $sub->where('customer_id', $customer->id);
                    if ($canUseSystem) {
                        $sub->orWhereNull('customer_id');
                    }
                });
            })
            ->orderBy('name')
            ->get();

        $sendingDomains = SendingDomain::query()
            ->where('status', 'verified')
            ->when($mustAddSending, function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            }, function ($q) use ($customer, $canUseSystem) {
                $q->where(function ($sub) use ($customer, $canUseSystem) {
                    $sub->where('customer_id', $customer->id);
                    if ($canUseSystem) {
                        $sub->orWhereNull('customer_id');
                    }
                });
            })
            ->orderBy('domain')
            ->get();

        $trackingDomains = TrackingDomain::query()
            ->where('status', 'verified')
            ->when($mustAddTracking, function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            }, function ($q) use ($customer, $canUseSystem) {
                $q->where(function ($sub) use ($customer, $canUseSystem) {
                    $sub->where('customer_id', $customer->id);
                    if ($canUseSystem) {
                        $sub->orWhereNull('customer_id');
                    }
                });
            })
            ->orderBy('domain')
            ->get();

        if ((bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false)) {
            $hasSelectableDelivery = app(DeliveryServerService::class)
                ->querySelectableDeliveryServersForCustomer($customer, $mustAddDelivery, $canUseSystem)
                ->exists();

            if (! $hasSelectableDelivery) {
                $runPreflightIssues[] = 'You must add a delivery server before running a campaign.';
            }
        }

        if ((bool) $customer->groupSetting('servers.permissions.must_add_bounce_server', false)) {
            $hasOwnBounce = BounceServer::query()
                ->where('customer_id', $customer->id)
                ->where('active', true)
                ->exists();

            if (! $hasOwnBounce) {
                $runPreflightIssues[] = 'You must add a bounce server before running a campaign.';
            }
        }

        if ($mustAddReply) {
            $hasOwnReply = ReplyServer::query()
                ->where('customer_id', $customer->id)
                ->where('active', true)
                ->exists();

            if (! $hasOwnReply) {
                $runPreflightIssues[] = 'You must add a reply server before running a campaign.';
            }
        }

        if ((bool) $customer->groupSetting('domains.sending_domains.must_add', false)) {
            if ($sendingDomains->isEmpty()) {
                $runPreflightIssues[] = 'You must add and verify a sending domain before running a campaign.';
            }
        }

        if ((bool) $customer->groupSetting('domains.tracking_domains.must_add', false)) {
            if ($trackingDomains->isEmpty()) {
                $runPreflightIssues[] = 'You must add and verify a tracking domain before running a campaign.';
            }
        }

        $bounceServers = BounceServer::query()
            ->where('active', true)
            ->when((bool) $customer->groupSetting('servers.permissions.must_add_bounce_server', false), function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            }, function ($q) use ($customer, $canUseSystem) {
                $q->where(function ($sub) use ($customer, $canUseSystem) {
                    $sub->where('customer_id', $customer->id);
                    if ($canUseSystem) {
                        $sub->orWhereNull('customer_id');
                    }
                });
            })
            ->orderBy('name')
            ->get();

        $unlayerProjectId = config('services.unlayer.project_id');
        $unlayerDesign = null;
        $campaignTagsByList = $this->buildCampaignTagsByList($emailLists);

        return view('customer.campaigns.create', compact('emailLists', 'templates', 'footerTemplates', 'signatureTemplates', 'deliveryServers', 'replyServers', 'sendingDomains', 'trackingDomains', 'bounceServers', 'runPreflightIssues', 'unlayerProjectId', 'unlayerDesign', 'campaignTagsByList'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $customer = auth('customer')->user();
        $customer->enforceGroupLimit('campaigns.limits.max_campaigns', $customer->campaigns()->count(), 'Campaign limit reached.');

        $mustAddReply = (bool) $customer->groupSetting('servers.permissions.must_add_reply_server', false);
        $mustAddDelivery = (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false);
        $canUseSystem = (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false);

        $selectableDeliveryServerIds = app(DeliveryServerService::class)
            ->getSelectableDeliveryServerIdsForCustomer($customer, $mustAddDelivery, $canUseSystem);

        $payload = $request->all();
        if (! empty($payload['html_content_b64']) && isset($payload['html_content'])) {
            $decoded = base64_decode((string) $payload['html_content'], true);
            if ($decoded !== false) {
                $payload['html_content'] = $decoded;
            }
        }
        if (array_key_exists('delivery_server_id', $payload) && $payload['delivery_server_id'] === '') {
            $payload['delivery_server_id'] = null;
        }

        // If inbox rotation is enabled and a primary delivery server isn't chosen,
        // fall back to the first selected rotation server so the required-rule passes
        // (especially when must_add_delivery_server is enabled).
        if ((bool) ($payload['inbox_rotation_enabled'] ?? false) && empty($payload['delivery_server_id'])) {
            $rotationFallback = collect((array) ($payload['inbox_rotation_server_ids'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->first();
            if ($rotationFallback) {
                $payload['delivery_server_id'] = $rotationFallback;
            }
        }

        $validator = validator($payload, [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'list_id' => ['nullable', 'exists:email_lists,id'],
            'segments' => ['nullable', 'array'],
            'segments.*' => ['integer'],
            'delivery_server_id' => [
                $mustAddDelivery ? 'required' : 'nullable',
                'integer',
                Rule::in($selectableDeliveryServerIds),
            ],
            'reply_server_id' => [
                'nullable',
                Rule::exists('reply_servers', 'id')->where(function ($q) use ($customer, $mustAddReply, $canUseSystem) {
                    $q->where('active', true);

                    if ($mustAddReply || ! $canUseSystem) {
                        $q->where('customer_id', $customer->id);

                        return;
                    }

                    $q->where(function ($sub) use ($customer) {
                        $sub->where('customer_id', $customer->id)
                            ->orWhereNull('customer_id');
                    });
                }),
            ],
            'sending_domain_id' => ['nullable', 'exists:sending_domains,id'],
            'tracking_domain_id' => ['nullable', 'exists:tracking_domains,id'],
            'template_id' => [
                'nullable',
                Rule::exists('templates', 'id')->where(function ($q) use ($customer) {
                    $q->whereIn('type', ['email', 'campaign'])
                        ->whereNull('deleted_at')
                        ->where(function ($subQ) use ($customer) {
                            $subQ->where('customer_id', $customer->id)
                                ->orWhere(function ($inner) {
                                    $inner->where('is_public', true)->where('is_system', false);
                                });
                        });
                }),
            ],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'type' => ['nullable', 'in:regular,autoresponder,recurring'],
            'status' => ['nullable', 'in:draft,queued,scheduled,running,paused,completed,failed'],
            'html_content' => ['nullable', 'string'],
            'plain_text_content' => ['nullable', 'string'],
            'template_data' => ['nullable'],
            'footer_template_id' => [
                'nullable',
                Rule::exists('templates', 'id')->where(function ($q) use ($customer) {
                    $q->where('type', 'footer')
                        ->whereNull('deleted_at')
                        ->where(function ($subQ) use ($customer) {
                            $subQ->where('customer_id', $customer->id)
                                ->orWhere(function ($inner) {
                                    $inner->where('is_public', true)->where('is_system', false);
                                });
                        });
                }),
            ],
            'signature_template_id' => [
                'nullable',
                Rule::exists('templates', 'id')->where(function ($q) use ($customer) {
                    $q->where('type', 'signature')
                        ->whereNull('deleted_at')
                        ->where(function ($subQ) use ($customer) {
                            $subQ->where('customer_id', $customer->id)
                                ->orWhere(function ($inner) {
                                    $inner->where('is_public', true)->where('is_system', false);
                                });
                        });
                }),
            ],
            'send_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'recurring_interval_days' => ['nullable', 'integer', 'min:1'],
            'track_opens' => ['nullable', 'boolean'],
            'track_clicks' => ['nullable', 'boolean'],
            'enable_spintax' => ['nullable', 'boolean'],
            'spam_scoring_enabled' => ['nullable', 'boolean'],
            'inbox_rotation_enabled' => ['nullable', 'boolean'],
            'inbox_rotation_server_ids' => ['nullable', 'array'],
            'inbox_rotation_server_ids.*' => ['integer', Rule::in($selectableDeliveryServerIds)],
            'inbox_rotation_min_delay' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'inbox_rotation_max_delay' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'inbox_rotation_daily_cap' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_scheduled' => ['nullable', 'boolean'],
            'schedule_timezone' => ['nullable', 'string', 'max:64'],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['integer', 'between:1,7'],
            'schedule_time_blocks' => ['nullable', 'array'],
            'schedule_time_blocks.*.from' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'schedule_time_blocks.*.to' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'send_gap_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
            'send_rate_per_minute' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $validator->after(function ($validator) use ($payload) {
            $enabled = (bool) ($payload['inbox_rotation_enabled'] ?? false);
            if (! $enabled) {
                return;
            }

            $rotationServerIds = collect((array) ($payload['inbox_rotation_server_ids'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            if ($rotationServerIds->count() < 2) {
                $validator->errors()->add('inbox_rotation_server_ids', 'Select at least two delivery servers to enable inbox rotation.');
            }
        });

        $validated = $validator->validate();

        if (array_key_exists('html_content', $validated)) {
            $validated['html_content'] = $this->stripLegacyUnsubscribeArtifacts($validated['html_content']);
        }
        if (array_key_exists('plain_text_content', $validated)) {
            $validated['plain_text_content'] = $this->stripLegacyUnsubscribeArtifacts($validated['plain_text_content']);
        }

        $unlayerData = $this->buildUnlayerTemplateData($request->input('template_data'));
        if ($unlayerData !== null) {
            $validated['template_data'] = $unlayerData;
        } else {
            unset($validated['template_data']);
        }

        if (empty($validated['plain_text_content']) && ! empty($validated['html_content'])) {
            $validated['plain_text_content'] = trim(preg_replace('/\s+/', ' ', strip_tags($validated['html_content'])));
        }

        $appTimezone = config('app.timezone', 'UTC');
        $scheduleTimezone = trim((string) ($validated['schedule_timezone'] ?? '')) ?: ($customer->timezone ?? $appTimezone);
        if (! in_array($scheduleTimezone, \DateTimeZone::listIdentifiers(), true)) {
            $scheduleTimezone = $customer->timezone ?? $appTimezone;
        }
        // If user toggled off scheduling, clear send_at
        $isScheduled = (bool) ($validated['is_scheduled'] ?? false);
        if (! $isScheduled) {
            $validated['send_at'] = null;
            $validated['scheduled_at'] = null;
        }
        if (! empty($validated['send_at'])) {
            $validated['send_at'] = Carbon::parse($validated['send_at'], $scheduleTimezone)->setTimezone($appTimezone);
        }
        if (! empty($validated['scheduled_at'])) {
            $validated['scheduled_at'] = Carbon::parse($validated['scheduled_at'], $scheduleTimezone)->setTimezone($appTimezone);
        }

        // Convert empty string to null for nullable fields
        if (isset($validated['delivery_server_id']) && $validated['delivery_server_id'] === '') {
            $validated['delivery_server_id'] = null;
        }
        if (isset($validated['reply_server_id']) && $validated['reply_server_id'] === '') {
            $validated['reply_server_id'] = null;
        }
        if (isset($validated['bounce_server_id']) && $validated['bounce_server_id'] === '') {
            $validated['bounce_server_id'] = null;
        }
        if (isset($validated['sending_domain_id']) && $validated['sending_domain_id'] === '') {
            $validated['sending_domain_id'] = null;
        }
        if (isset($validated['tracking_domain_id']) && $validated['tracking_domain_id'] === '') {
            $validated['tracking_domain_id'] = null;
        }
        if (isset($validated['list_id']) && $validated['list_id'] === '') {
            $validated['list_id'] = null;
        }
        if (isset($validated['segments']) && ! is_array($validated['segments'])) {
            $validated['segments'] = [];
        }
        if (isset($validated['segments'])) {
            $validated['segments'] = collect((array) $validated['segments'])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }
        if (isset($validated['template_id']) && $validated['template_id'] === '') {
            $validated['template_id'] = null;
        }

        $footerTemplateId = $validated['footer_template_id'] ?? null;
        $signatureTemplateId = $validated['signature_template_id'] ?? null;
        unset($validated['footer_template_id'], $validated['signature_template_id']);

        $settings = (array) ($validated['settings'] ?? []);
        if (! empty($footerTemplateId)) {
            $settings['footer_template_id'] = (int) $footerTemplateId;
        }
        if (! empty($signatureTemplateId)) {
            $settings['signature_template_id'] = (int) $signatureTemplateId;
        }

        // Save spintax and spam scoring settings
        if (isset($validated['enable_spintax'])) {
            $settings['enable_spintax'] = (bool) $validated['enable_spintax'];
        }
        if (isset($validated['spam_scoring_enabled'])) {
            $settings['spam_scoring_enabled'] = (bool) $validated['spam_scoring_enabled'];
        }

        $rotationServerIds = collect((array) ($validated['inbox_rotation_server_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $rotationEnabled = (bool) ($validated['inbox_rotation_enabled'] ?? false) && count($rotationServerIds) >= 2;
        $settings['inbox_rotation_enabled'] = $rotationEnabled;
        $settings['inbox_rotation_server_ids'] = $rotationEnabled ? $rotationServerIds : [];

        if (array_key_exists('inbox_rotation_min_delay', $validated)) {
            $settings['inbox_rotation_min_delay'] = $validated['inbox_rotation_min_delay'] === null
                ? null
                : max(0, (int) $validated['inbox_rotation_min_delay']);
        }
        if (array_key_exists('inbox_rotation_max_delay', $validated)) {
            $settings['inbox_rotation_max_delay'] = $validated['inbox_rotation_max_delay'] === null
                ? null
                : max(0, (int) $validated['inbox_rotation_max_delay']);
        }
        if (array_key_exists('inbox_rotation_daily_cap', $validated)) {
            $settings['inbox_rotation_daily_cap'] = $validated['inbox_rotation_daily_cap'] === null
                ? null
                : max(0, (int) $validated['inbox_rotation_daily_cap']);
        }

        // When rotation is enabled, ensure the campaign still has a delivery_server_id
        // (use the first rotation server) so downstream jobs/UI don't treat it as unconfigured.
        if ($rotationEnabled && empty($validated['delivery_server_id'])) {
            $validated['delivery_server_id'] = (int) $rotationServerIds[0];
        }

        if (! empty($settings)) {
            $validated['settings'] = $settings;
        }

        // Persist sending schedule into settings.sending_schedule
        $sendingSchedule = [
            'timezone' => $scheduleTimezone,
            'days' => array_values(array_unique(array_map('intval', (array) ($validated['schedule_days'] ?? [])))),
            'time_blocks' => array_values(array_filter(array_map(function ($block) {
                $from = trim((string) ($block['from'] ?? ''));
                $to = trim((string) ($block['to'] ?? ''));
                if ($from === '' || $to === '') {
                    return null;
                }

                return ['from' => $from, 'to' => $to];
            }, (array) ($validated['schedule_time_blocks'] ?? [])))),
        ];
        $settings['sending_schedule'] = $sendingSchedule;

        $sendSpeed = [];
        if (array_key_exists('send_gap_ms', $validated)) {
            $sendSpeed['gap_ms'] = $validated['send_gap_ms'] === null || $validated['send_gap_ms'] === ''
                ? null
                : max(0, (int) $validated['send_gap_ms']);
        }
        if (array_key_exists('send_rate_per_minute', $validated)) {
            $sendSpeed['rate_per_minute'] = $validated['send_rate_per_minute'] === null || $validated['send_rate_per_minute'] === ''
                ? null
                : max(0, (int) $validated['send_rate_per_minute']);
        }
        if ($sendSpeed !== []) {
            $settings['send_speed'] = $sendSpeed;
        }

        $validated['settings'] = $settings;

        if (! empty($validated['segments']) && ! empty($validated['list_id'])) {
            $segmentsOnSelectedList = ListSegment::query()
                ->where('list_id', (int) $validated['list_id'])
                ->whereIn('id', $validated['segments'])
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $validated['segments'] = $segmentsOnSelectedList;
        }

        // Remove from validated as they're now in settings
        unset(
            $validated['enable_spintax'],
            $validated['spam_scoring_enabled'],
            $validated['inbox_rotation_enabled'],
            $validated['inbox_rotation_server_ids'],
            $validated['inbox_rotation_min_delay'],
            $validated['inbox_rotation_max_delay'],
            $validated['inbox_rotation_daily_cap'],
            $validated['is_scheduled'],
            $validated['schedule_timezone'],
            $validated['schedule_days'],
            $validated['schedule_time_blocks'],
            $validated['send_gap_ms'],
            $validated['send_rate_per_minute']
        );

        if (! empty($validated['send_at']) && empty($validated['scheduled_at'])) {
            $validated['scheduled_at'] = $validated['send_at'];
            $validated['status'] = 'scheduled';
        }

        if (($validated['type'] ?? 'regular') === 'recurring') {
            $settings = (array) ($validated['settings'] ?? []);
            $settings['recurring'] = array_merge((array) ($settings['recurring'] ?? []), [
                'interval_days' => (int) ($validated['recurring_interval_days'] ?? 7),
            ]);
            $validated['settings'] = $settings;

            if (empty($validated['scheduled_at'])) {
                $validated['scheduled_at'] = now();
            }
            $validated['status'] = 'scheduled';
        }

        unset($validated['recurring_interval_days']);

        $campaign = $this->campaignService->create($customer, $validated);

        return redirect()
            ->route('customer.campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Campaign $campaign, Request $request)
    {
        $this->authorizeOwnership($campaign);
        $runPreflightIssues = [];
        $activeTab = (string) $request->query('tab', 'overview');
        $campaignLogsData = [
            'status' => $campaign->status,
            'failure_reason' => null,
            'preflight_issues' => [],
            'error_breakdown' => collect(),
            'recent_events' => collect(),
        ];

        $campaignAutomations = collect();
        if ($activeTab === 'automations') {
            $campaignAutomations = Automation::query()
                ->where('customer_id', $campaign->customer_id)
                ->withCount([
                    'runs as runs_total',
                    'runs as runs_active' => function ($q) {
                        $q->where('status', 'active');
                    },
                ])
                ->latest()
                ->limit(12)
                ->get();
        }

        $inboxRotationServers = collect();
        $inboxRotationData = [
            'enabled' => (bool) data_get($campaign->settings, 'inbox_rotation_enabled', false),
            'server_ids' => [],
            'daily_cap' => null,
            'min_delay' => null,
            'max_delay' => null,
            'schedule_days' => [],
            'start_time' => null,
            'end_time' => null,
            'timezone' => null,
            'pause_on_bounce' => null,
            'max_bounce_rate' => null,
            'exclude_generic_roles' => null,
        ];
        $selectedInboxRotationServer = null;
        $selectedInboxRotationLogs = collect();
        $selectedInboxRotationLogStats = [
            'sent_count' => 0,
            'delivered_count' => 0,
            'opened_count' => 0,
            'open_rate' => 0,
            'bounced_count' => 0,
            'events_count' => 0,
            'recipient_count' => 0,
            'event_filter' => 'all',
            'search' => '',
        ];
        $selectedInboxRotationDeliveryLogs = collect();
        $selectedInboxRotationDeliveryLogStats = [
            'accepted_count' => 0,
            'delivered_count' => 0,
            'bounced_count' => 0,
            'failed_count' => 0,
            'events_count' => 0,
        ];
        $inboxRotationDeliveryLogSummary = collect();
        $selectedInboxRotationLogCounts = [
            'all' => 0,
            'sends' => 0,
            'errors' => 0,
            'system' => 0,
        ];

        if ($activeTab === 'inbox-rotation') {
            $rotationServerIds = collect((array) data_get($campaign->settings, 'inbox_rotation_server_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            $inboxRotationServers = $rotationServerIds->isEmpty()
                ? collect()
                : DeliveryServer::query()
                    ->whereIn('id', $rotationServerIds)
                    ->orderBy('name')
                    ->get();

            $inboxRotationData = [
                'enabled' => (bool) data_get($campaign->settings, 'inbox_rotation_enabled', false),
                'server_ids' => $rotationServerIds->all(),
                'daily_cap' => data_get($campaign->settings, 'inbox_rotation_daily_cap'),
                'min_delay' => data_get($campaign->settings, 'inbox_rotation_min_delay'),
                'max_delay' => data_get($campaign->settings, 'inbox_rotation_max_delay'),
                'schedule_days' => (array) data_get($campaign->settings, 'inbox_rotation_schedule_days', []),
                'start_time' => data_get($campaign->settings, 'inbox_rotation_start_time'),
                'end_time' => data_get($campaign->settings, 'inbox_rotation_end_time'),
                'timezone' => data_get($campaign->settings, 'inbox_rotation_timezone'),
                'pause_on_bounce' => data_get($campaign->settings, 'inbox_rotation_pause_on_bounce'),
                'max_bounce_rate' => data_get($campaign->settings, 'inbox_rotation_max_bounce_rate'),
                'exclude_generic_roles' => data_get($campaign->settings, 'inbox_rotation_exclude_generic_roles'),
            ];

            $deliveryEventNames = ['accepted', 'delivered', 'bounced', 'failed', 'blocked_by_spam_filter', 'opened', 'clicked', 'replied', 'complained'];
            $rotationServerNames = $inboxRotationServers->pluck('name')->filter()->map(fn ($value) => (string) $value)->unique()->values();
            $rotationServerEmails = $inboxRotationServers->pluck('from_email')->filter()->map(fn ($value) => (string) $value)->unique()->values();

            $selectedServerId = (int) $request->query('inbox_rotation_server', 0);
            $selectedInboxRotationServer = $selectedServerId > 0
                ? $inboxRotationServers->firstWhere('id', $selectedServerId)
                : null;

            $eventFilter = (string) $request->query('rotation_event', 'all');
            if (! in_array($eventFilter, ['all', 'sends', 'errors', 'system'], true)) {
                $eventFilter = 'all';
            }

            $search = trim((string) $request->query('rotation_search', ''));

            $applyRotationPoolFilter = static function ($query) use ($rotationServerIds, $rotationServerNames, $rotationServerEmails) {
                $query->where(function ($subQuery) use ($rotationServerIds, $rotationServerNames, $rotationServerEmails) {
                    $hasMatchClause = false;

                    if ($rotationServerIds->isNotEmpty()) {
                        $subQuery->whereIn('meta->delivery_server_id', $rotationServerIds->all());
                        $hasMatchClause = true;
                    }

                    if ($rotationServerNames->isNotEmpty()) {
                        $hasMatchClause
                            ? $subQuery->orWhereIn('meta->delivery_server_name', $rotationServerNames->all())
                            : $subQuery->whereIn('meta->delivery_server_name', $rotationServerNames->all());
                        $hasMatchClause = true;
                    }

                    if ($rotationServerEmails->isNotEmpty()) {
                        $hasMatchClause
                            ? $subQuery->orWhereIn('meta->delivery_server_from_email', $rotationServerEmails->all())
                            : $subQuery->whereIn('meta->delivery_server_from_email', $rotationServerEmails->all());
                        $hasMatchClause = true;
                    }

                    if (! $hasMatchClause) {
                        $subQuery->whereRaw('1 = 0');
                    }
                });
            };

            $applySelectedServerFilter = static function ($query) use ($selectedInboxRotationServer, $selectedServerId) {
                if (! $selectedInboxRotationServer) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function ($subQuery) use ($selectedInboxRotationServer, $selectedServerId) {
                    $subQuery->where('meta->delivery_server_id', $selectedServerId)
                        ->orWhere('meta->delivery_server_name', (string) $selectedInboxRotationServer->name);

                    if (! empty($selectedInboxRotationServer->from_email)) {
                        $subQuery->orWhere('meta->delivery_server_from_email', (string) $selectedInboxRotationServer->from_email);
                    }
                });
            };

            $deliveryLogsBaseQuery = $campaign->logs()
                ->with('recipient:id,email,first_name,last_name')
                ->whereIn('event', $deliveryEventNames);

            $applyRotationPoolFilter($deliveryLogsBaseQuery);

            if ($selectedServerId > 0) {
                $applySelectedServerFilter($deliveryLogsBaseQuery);
            }

            $deliveryLogsStatsBaseQuery = clone $deliveryLogsBaseQuery;

            $selectedInboxRotationLogCounts = [
                'all' => (int) (clone $deliveryLogsStatsBaseQuery)->count(),
                'sends' => (int) (clone $deliveryLogsStatsBaseQuery)->whereIn('event', ['accepted', 'delivered', 'opened', 'clicked', 'replied'])->count(),
                'errors' => (int) (clone $deliveryLogsStatsBaseQuery)->whereIn('event', ['failed', 'bounced', 'blocked_by_spam_filter', 'complained'])->count(),
                'system' => (int) (clone $deliveryLogsStatsBaseQuery)->whereNull('recipient_id')->count(),
            ];

            if ($eventFilter === 'sends') {
                $deliveryLogsBaseQuery->whereIn('event', ['accepted', 'delivered', 'opened', 'clicked', 'replied']);
            } elseif ($eventFilter === 'errors') {
                $deliveryLogsBaseQuery->whereIn('event', ['failed', 'bounced', 'blocked_by_spam_filter', 'complained']);
            } elseif ($eventFilter === 'system') {
                $deliveryLogsBaseQuery->whereNull('recipient_id');
            }

            if ($search !== '') {
                $deliveryLogsBaseQuery->where(function ($query) use ($search) {
                    $query->whereHas('recipient', function ($recipientQuery) use ($search) {
                        $recipientQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })->orWhere('meta->email', 'like', "%{$search}%");
                });
            }

            $selectedInboxRotationDeliveryLogs = $deliveryLogsBaseQuery
                ->latest()
                ->paginate(15)
                ->withQueryString();

            $selectedInboxRotationDeliveryLogStats = [
                'accepted_count' => (int) (clone $deliveryLogsStatsBaseQuery)->where('event', 'accepted')->count(),
                'delivered_count' => (int) (clone $deliveryLogsStatsBaseQuery)->where('event', 'delivered')->count(),
                'bounced_count' => (int) (clone $deliveryLogsStatsBaseQuery)->where('event', 'bounced')->count(),
                'failed_count' => (int) (clone $deliveryLogsStatsBaseQuery)->where('event', 'failed')->count(),
                'events_count' => (int) (clone $deliveryLogsStatsBaseQuery)->count(),
                'recipient_count' => (int) (clone $deliveryLogsStatsBaseQuery)
                    ->whereNotNull('recipient_id')
                    ->distinct('recipient_id')
                    ->count('recipient_id'),
                'event_filter' => $eventFilter,
                'search' => $search,
                'server_id' => $selectedServerId,
                'server_name' => $selectedInboxRotationServer?->name,
            ];
        }

        if ($campaign->canStart()) {
            try {
                $this->campaignService->ensureCanRun($campaign);
            } catch (\RuntimeException $e) {
                $runPreflightIssues[] = $e->getMessage();
            }
        }
        // Sync stats from actual recipient statuses to ensure accuracy
        $campaign->syncStats();

        $campaign->load(['emailList', 'trackingDomain', 'sendingDomain', 'deliveryServer', 'variants', 'recipients', 'logs']);

        // Calculate total recipients - use actual count if campaign has started, otherwise calculate expected
        $totalRecipients = $campaign->total_recipients;
        if ($campaign->total_recipients === 0 && $campaign->emailList) {
            // Calculate expected recipients for campaigns that haven't started yet
            $totalRecipients = $this->calculateExpectedRecipients($campaign);
        }

        // Calculate initial stats
        $delivered = max(0, $campaign->sent_count - $campaign->bounced_count);

        // Recipient status breakdown
        $recipientStatuses = $campaign->recipients()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Calculate unique opens (recipients who opened at least once)
        $uniqueOpens = ($recipientStatuses['opened'] ?? 0) + ($recipientStatuses['clicked'] ?? 0);

        // Calculate rates based on unique opens
        $openRate = $delivered > 0 ? round(($uniqueOpens / $delivered) * 100, 2) : 0;
        $clickRate = $delivered > 0 ? round(($campaign->clicked_count / $delivered) * 100, 2) : 0;
        $bounceRate = $campaign->sent_count > 0 ? round(($campaign->bounced_count / $campaign->sent_count) * 100, 2) : 0;
        $failureRate = $campaign->sent_count > 0 ? round(($campaign->failed_count / $campaign->sent_count) * 100, 2) : 0;
        $deliveryRate = $campaign->sent_count > 0 ? round(($delivered / $campaign->sent_count) * 100, 2) : 0;

        // Top clicked links with unique + total clicks + last clicked
        $topLinks = $campaign->logs()
            ->where('event', 'clicked')
            ->whereNotNull('url')
            ->selectRaw('url, COUNT(*) as total_clicks, COUNT(DISTINCT recipient_id) as unique_clicks, MAX(created_at) as last_clicked_at')
            ->groupBy('url')
            ->orderByDesc('total_clicks')
            ->limit(10)
            ->get();

        // Total open events (all opens including repeat opens)
        $totalOpenEvents = $campaign->logs()->where('event', 'opened')->count();

        // Engagement heatmap: opens/clicks by day-of-week (0=Sun) and hour
        $isMysql = in_array(\DB::getDriverName(), ['mysql', 'mariadb']);
        if ($isMysql) {
            $heatDowExpr = '(DAYOFWEEK(campaign_logs.created_at) - 1)';
            $heatHourExpr = 'HOUR(campaign_logs.created_at)';
            $heatSelect = $heatDowExpr.' as dow, '.$heatHourExpr.' as hr, event, COUNT(*) as cnt';
            $heatGroupBy = $heatDowExpr.', '.$heatHourExpr.', event';
        } else {
            $heatDowExpr = "CAST(strftime('%w', campaign_logs.created_at) AS INTEGER)";
            $heatHourExpr = "CAST(strftime('%H', campaign_logs.created_at) AS INTEGER)";
            $heatSelect = $heatDowExpr.' as dow, '.$heatHourExpr.' as hr, event, COUNT(*) as cnt';
            $heatGroupBy = $heatDowExpr.', '.$heatHourExpr.', event';
        }
        $heatmapRaw = $campaign->logs()
            ->whereIn('event', ['opened', 'clicked'])
            ->selectRaw($heatSelect)
            ->groupByRaw($heatGroupBy)
            ->get();

        $heatmapData = ['opened' => [], 'clicked' => []];
        foreach ($heatmapRaw as $row) {
            if (! isset($heatmapData[$row->event][$row->dow])) {
                $heatmapData[$row->event][$row->dow] = [];
            }
            $heatmapData[$row->event][$row->dow][$row->hr] = (int) $row->cnt;
        }

        // Agent groups for device/client breakdown
        $agentGroups = $campaign->logs()
            ->whereIn('event', ['opened', 'clicked'])
            ->whereNotNull('user_agent')
            ->selectRaw('user_agent, COUNT(*) as cnt')
            ->groupBy('user_agent')
            ->orderByDesc('cnt')
            ->get(['user_agent', 'cnt']);

        // Engagement trend - opens and clicks per day
        $engagementTrend = $campaign->logs()
            ->whereIn('event', ['opened', 'clicked'])
            ->selectRaw('DATE(created_at) as date, event, COUNT(*) as count')
            ->groupByRaw('DATE(created_at), event')
            ->orderBy('date')
            ->get();

        $trendByDate = [];
        foreach ($engagementTrend as $row) {
            if (! isset($trendByDate[$row->date])) {
                $trendByDate[$row->date] = ['opened' => 0, 'clicked' => 0];
            }
            $trendByDate[$row->date][$row->event] = (int) $row->count;
        }
        ksort($trendByDate);

        $engagementChartData = [
            'labels' => array_keys($trendByDate),
            'opens' => array_values(array_column(array_values($trendByDate), 'opened')),
            'clicks' => array_values(array_column(array_values($trendByDate), 'clicked')),
        ];

        $deliverabilityChartData = [
            'labels' => [],
            'delivered' => [],
            'bounced' => [],
        ];

        $autoResumeAt = null;
        $autoResumeReason = data_get($campaign->settings, 'auto_resume_reason');
        $autoResumeAtValue = data_get($campaign->settings, 'auto_resume_at');

        if (is_string($autoResumeAtValue) && $autoResumeAtValue !== '') {
            try {
                $autoResumeAt = Carbon::parse($autoResumeAtValue, config('app.timezone', 'UTC'));
            } catch (\Throwable $e) {
                $autoResumeAt = null;
            }
        }

        // Live activity - most recent opens/clicks
        $liveActivity = $campaign->logs()
            ->whereIn('event', ['opened', 'clicked'])
            ->with('recipient')
            ->latest()
            ->limit(6)
            ->get();

        // Subscribers & Activity insights
        $audienceTotal = max(0, (int) $totalRecipients);
        $activeUsers = max(0, (int) $uniqueOpens);
        $unengagedUsers = max(0, $audienceTotal - $activeUsers);

        $newSubscribers = 0;
        if ($campaign->emailList) {
            $periodStart = $campaign->started_at ?? $campaign->created_at;
            $periodEnd = $campaign->finished_at ?? now();

            $newSubscribers = $campaign->emailList->subscribers()
                ->where(function ($q) use ($periodStart, $periodEnd) {
                    $q->whereBetween('subscribed_at', [$periodStart, $periodEnd])
                        ->orWhere(function ($q2) use ($periodStart, $periodEnd) {
                            $q2->whereNull('subscribed_at')
                                ->whereBetween('created_at', [$periodStart, $periodEnd]);
                        });
                })
                ->count();
        }

        $subscriberInsights = [
            'active' => $activeUsers,
            'unengaged' => $unengagedUsers,
            'new_subscribers' => (int) $newSubscribers,
            'active_pct' => $audienceTotal > 0 ? round(($activeUsers / $audienceTotal) * 100, 1) : 0,
            'unengaged_pct' => $audienceTotal > 0 ? round(($unengagedUsers / $audienceTotal) * 100, 1) : 0,
            'audience_total' => $audienceTotal,
        ];

        $feedbackRows = $campaign->logs()
            ->whereIn('event', ['unsubscribed', 'complained'])
            ->selectRaw('DATE(created_at) as date, event, COUNT(*) as count')
            ->groupByRaw('DATE(created_at), event')
            ->orderBy('date')
            ->get();

        $feedbackByDate = [];
        foreach ($feedbackRows as $row) {
            if (! isset($feedbackByDate[$row->date])) {
                $feedbackByDate[$row->date] = ['unsubscribed' => 0, 'complained' => 0];
            }
            $feedbackByDate[$row->date][$row->event] = (int) $row->count;
        }

        if (empty($feedbackByDate)) {
            $seedStart = now()->subDays(6)->startOfDay();
            for ($i = 0; $i < 7; $i++) {
                $d = $seedStart->copy()->addDays($i)->format('Y-m-d');
                $feedbackByDate[$d] = ['unsubscribed' => 0, 'complained' => 0];
            }
        }

        ksort($feedbackByDate);

        $subscriberFeedbackChartData = [
            'labels' => array_keys($feedbackByDate),
            'unsubscribes' => array_values(array_column(array_values($feedbackByDate), 'unsubscribed')),
            'complaints' => array_values(array_column(array_values($feedbackByDate), 'complained')),
        ];

        $topEngagers = $campaign->recipients()
            ->withCount([
                'logs as total_opens' => fn ($q) => $q->where('event', 'opened'),
                'logs as total_clicks' => fn ($q) => $q->where('event', 'clicked'),
            ])
            ->withMax('logs as last_activity_at', 'created_at')
            ->where(function ($q) {
                $q->whereNotNull('opened_at')
                    ->orWhereNotNull('clicked_at');
            })
            ->orderByDesc('total_clicks')
            ->orderByDesc('total_opens')
            ->orderByDesc('last_activity_at')
            ->limit(10)
            ->get(['id', 'email', 'first_name', 'last_name']);

        // Deliverability & Bounces insights
        $bounceLogs = BounceLog::query()->where('campaign_id', $campaign->id);
        $totalBounceLogs = (clone $bounceLogs)->count();
        $hardBounces = (clone $bounceLogs)->where('bounce_type', 'hard')->count();
        $softBounces = max(0, $totalBounceLogs - $hardBounces);

        if ($totalBounceLogs === 0 && (int) $campaign->bounced_count > 0) {
            $hardBounces = (int) round($campaign->bounced_count * 0.25);
            $softBounces = max(0, (int) $campaign->bounced_count - $hardBounces);
        }

        $spamRate = $delivered > 0 ? round((($campaign->complained_count ?? 0) / $delivered) * 100, 2) : 0;
        $blocklistedCount = $campaign->emailList
            ? $campaign->emailList->subscribers()->where('status', 'blacklisted')->count()
            : 0;

        $deliverabilityInsights = [
            'delivery_rate' => $deliveryRate,
            'delivered' => $delivered,
            'total_bounces' => (int) ($campaign->bounced_count ?? 0),
            'hard_bounces' => (int) $hardBounces,
            'soft_bounces' => (int) $softBounces,
            'bounce_rate' => $bounceRate,
            'spam_reports' => (int) ($campaign->complained_count ?? 0),
            'spam_rate' => $spamRate,
            'blocklisted' => (int) $blocklistedCount,
        ];

        $campaignDeliveryServerIds = collect([
            (int) ($campaign->delivery_server_id ?? 0),
            (int) data_get($campaign->settings, 'inbox_rotation_enabled') ? null : null,
        ]);

        $campaignDeliveryServerIds = collect((array) data_get($campaign->settings, 'inbox_rotation_server_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($campaignDeliveryServerIds->isEmpty() && (int) $campaign->delivery_server_id > 0) {
            $campaignDeliveryServerIds = collect([(int) $campaign->delivery_server_id]);
        }

        $campaignDeliveryServers = $campaignDeliveryServerIds->isEmpty()
            ? collect()
            : DeliveryServer::query()
                ->whereIn('id', $campaignDeliveryServerIds->all())
                ->orderBy('name')
                ->get();

        $deliveryEventLogs = $campaign->logs()
            ->with('recipient:id,email,first_name,last_name')
            ->whereIn('event', ['accepted', 'delivered', 'bounced', 'failed'])
            ->get();

        $deliveryAnalyticsByServer = [];
        foreach ($campaignDeliveryServers as $server) {
            $deliveryAnalyticsByServer[$server->id] = [
                'server' => $server,
                'total_sent' => 0,
                'delivered' => 0,
                'bounced' => 0,
                'failed' => 0,
                'bounce_rate' => 0,
                'delivery_rate' => 0,
                'volume_share' => 0,
                'status' => $server->isActive() ? 'Active' : 'Paused',
            ];
        }

        $deliveryAnalyticsRows = $deliveryEventLogs
            ->sortByDesc('created_at')
            ->take(8)
            ->values()
            ->map(function ($log) use ($campaignDeliveryServers) {
                $meta = is_array($log->meta) ? $log->meta : [];

                return [
                    'time' => $log->created_at,
                    'event' => $log->event,
                    'recipient' => trim((string) (($log->recipient?->first_name ?? '').' '.($log->recipient?->last_name ?? ''))),
                    'email' => $log->recipient?->email ?? ($meta['email'] ?? null),
                    'server_name' => $meta['delivery_server_name'] ?? ($campaignDeliveryServers->firstWhere('id', (int) ($meta['delivery_server_id'] ?? 0))?->name ?? 'Unknown'),
                    'server_email' => $meta['delivery_server_from_email'] ?? null,
                ];
            });

        foreach ($deliveryEventLogs as $log) {
            $meta = is_array($log->meta) ? $log->meta : [];
            $serverId = (int) ($meta['delivery_server_id'] ?? 0);
            if ($serverId <= 0) {
                continue;
            }

            if (! isset($deliveryAnalyticsByServer[$serverId])) {
                $server = $campaignDeliveryServers->firstWhere('id', $serverId) ?: DeliveryServer::find($serverId);
                if (! $server) {
                    continue;
                }

                $deliveryAnalyticsByServer[$serverId] = [
                    'server' => $server,
                    'total_sent' => 0,
                    'delivered' => 0,
                    'bounced' => 0,
                    'failed' => 0,
                    'bounce_rate' => 0,
                    'delivery_rate' => 0,
                    'volume_share' => 0,
                    'status' => $server->isActive() ? 'Active' : 'Paused',
                ];
            }

            if ($log->event === 'accepted') {
                $deliveryAnalyticsByServer[$serverId]['total_sent']++;
            } elseif ($log->event === 'delivered') {
                $deliveryAnalyticsByServer[$serverId]['delivered']++;
            } elseif ($log->event === 'bounced') {
                $deliveryAnalyticsByServer[$serverId]['bounced']++;
            } elseif ($log->event === 'failed') {
                $deliveryAnalyticsByServer[$serverId]['failed']++;
            }
        }

        $deliveryAnalyticsOverviewTotals = [
            'active_senders' => $campaignDeliveryServers->filter(fn (DeliveryServer $server) => $server->isActive())->count(),
            'total_dispatches' => (int) $deliveryEventLogs->where('event', 'accepted')->count(),
            'delivered' => (int) $deliveryEventLogs->where('event', 'delivered')->count(),
            'bounced' => (int) $deliveryEventLogs->whereIn('event', ['bounced', 'failed'])->count(),
            'queued_today' => (int) $campaign->recipients()->where('status', 'pending')->count(),
        ];

        $deliveryAnalyticsOverviewTotals['avg_deliverability'] = $deliveryAnalyticsOverviewTotals['total_dispatches'] > 0
            ? round(($deliveryAnalyticsOverviewTotals['delivered'] / $deliveryAnalyticsOverviewTotals['total_dispatches']) * 100, 1)
            : 0;

        $totalDispatches = max(1, $deliveryAnalyticsOverviewTotals['total_dispatches']);
        foreach ($deliveryAnalyticsByServer as &$serverStats) {
            $total = max(0, (int) $serverStats['total_sent']);
            $deliveredCount = max(0, (int) $serverStats['delivered']);
            $bouncedCount = max(0, (int) $serverStats['bounced']) + max(0, (int) $serverStats['failed']);

            $serverStats['bounce_rate'] = $total > 0 ? round(($bouncedCount / $total) * 100, 1) : 0;
            $serverStats['delivery_rate'] = $total > 0 ? round(($deliveredCount / $total) * 100, 1) : 0;
            $serverStats['volume_share'] = round((max($total, $deliveredCount, $bouncedCount) / $totalDispatches) * 100, 1);

            if (! $serverStats['server']->isActive()) {
                $serverStats['status'] = 'Paused';
            } elseif ($serverStats['bounce_rate'] >= 8) {
                $serverStats['status'] = 'Paused - High Bounces';
            } elseif ($total > 0 || $deliveredCount > 0 || $bouncedCount > 0) {
                $serverStats['status'] = 'Active';
            } else {
                $serverStats['status'] = 'Warming Up';
            }
        }
        unset($serverStats);

        $deliveryAnalyticsByServer = collect($deliveryAnalyticsByServer)
            ->sortByDesc(fn ($serverStats) => $serverStats['total_sent'])
            ->values();

        $isMysqlDriver = in_array(DB::getDriverName(), ['mysql', 'mariadb']);
        if ($isMysqlDriver) {
            $bounceTimelineRows = BounceLog::query()
                ->where('campaign_id', $campaign->id)
                ->selectRaw('DATE(COALESCE(bounced_at, created_at)) as period, bounce_type, COUNT(*) as cnt')
                ->groupByRaw('DATE(COALESCE(bounced_at, created_at)), bounce_type')
                ->orderBy('period')
                ->get();

            $bounceHourlyRows = BounceLog::query()
                ->where('campaign_id', $campaign->id)
                ->selectRaw('HOUR(COALESCE(bounced_at, created_at)) as hr, bounce_type, COUNT(*) as cnt')
                ->groupByRaw('HOUR(COALESCE(bounced_at, created_at)), bounce_type')
                ->orderBy('hr')
                ->get();
        } else {
            $bounceTimelineRows = BounceLog::query()
                ->where('campaign_id', $campaign->id)
                ->selectRaw("strftime('%Y-%m-%d', COALESCE(bounced_at, created_at)) as period, bounce_type, COUNT(*) as cnt")
                ->groupByRaw("strftime('%Y-%m-%d', COALESCE(bounced_at, created_at)), bounce_type")
                ->orderBy('period')
                ->get();

            $bounceHourlyRows = BounceLog::query()
                ->where('campaign_id', $campaign->id)
                ->selectRaw("CAST(strftime('%H', COALESCE(bounced_at, created_at)) AS INTEGER) as hr, bounce_type, COUNT(*) as cnt")
                ->groupByRaw("strftime('%H', COALESCE(bounced_at, created_at)), bounce_type")
                ->orderBy('hr')
                ->get();
        }

        $bounceByDay = [];
        foreach ($bounceTimelineRows as $row) {
            if (! isset($bounceByDay[$row->period])) {
                $bounceByDay[$row->period] = ['soft' => 0, 'hard' => 0];
            }
            $type = $row->bounce_type === 'hard' ? 'hard' : 'soft';
            $bounceByDay[$row->period][$type] = (int) $row->cnt;
        }
        if (empty($bounceByDay)) {
            $seedStart = now()->subDays(6)->startOfDay();
            for ($i = 0; $i < 7; $i++) {
                $d = $seedStart->copy()->addDays($i)->format('Y-m-d');
                $bounceByDay[$d] = ['soft' => 0, 'hard' => 0];
            }
        }

        ksort($bounceByDay);

        $bounceByHour = [];
        for ($h = 0; $h < 24; $h++) {
            $bounceByHour[$h] = ['soft' => 0, 'hard' => 0];
        }
        foreach ($bounceHourlyRows as $row) {
            $hour = (int) $row->hr;
            if (! isset($bounceByHour[$hour])) {
                $bounceByHour[$hour] = ['soft' => 0, 'hard' => 0];
            }
            $type = $row->bounce_type === 'hard' ? 'hard' : 'soft';
            $bounceByHour[$hour][$type] = (int) $row->cnt;
        }

        $bounceTimelineData = [
            'daily' => [
                'labels' => array_keys($bounceByDay),
                'soft' => array_values(array_column(array_values($bounceByDay), 'soft')),
                'hard' => array_values(array_column(array_values($bounceByDay), 'hard')),
            ],
            'hourly' => [
                'labels' => array_map(fn ($h) => sprintf('%02d:00', $h), array_keys($bounceByHour)),
                'soft' => array_values(array_column(array_values($bounceByHour), 'soft')),
                'hard' => array_values(array_column(array_values($bounceByHour), 'hard')),
            ],
        ];

        $deliverabilityLabels = $bounceTimelineData['daily']['labels'];
        $deliverabilityChartData['labels'] = $deliverabilityLabels;
        $deliverabilityChartData['bounced'] = collect($bounceTimelineData['daily']['soft'])
            ->zip($bounceTimelineData['daily']['hard'])
            ->map(fn ($pair) => (int) (($pair[0] ?? 0) + ($pair[1] ?? 0)))
            ->values()
            ->all();

        $deliveryLogQuery = $campaign->logs()->where('event', 'delivered');
        $deliveryTrend = $isMysqlDriver
            ? $deliveryLogQuery
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')
                ->get()
            : $deliveryLogQuery
                ->selectRaw("strftime('%Y-%m-%d', created_at) as date, COUNT(*) as count")
                ->groupByRaw("strftime('%Y-%m-%d', created_at)")
                ->orderBy('date')
                ->get();

        $deliveredByDate = [];
        foreach ($deliveryTrend as $row) {
            $deliveredByDate[$row->date] = (int) $row->count;
        }

        $deliverabilityChartData['delivered'] = array_map(
            fn ($label) => (int) ($deliveredByDate[$label] ?? 0),
            $deliverabilityLabels
        );

        $providerBuckets = [
            'Gmail' => ['sent' => 0, 'delivered' => 0, 'bounced' => 0],
            'Microsoft Outlook' => ['sent' => 0, 'delivered' => 0, 'bounced' => 0],
            'Yahoo Mail' => ['sent' => 0, 'delivered' => 0, 'bounced' => 0],
            'Apple Mail' => ['sent' => 0, 'delivered' => 0, 'bounced' => 0],
            'Other Domains' => ['sent' => 0, 'delivered' => 0, 'bounced' => 0],
        ];

        $recipientRows = $campaign->recipients()->get(['email', 'status']);
        foreach ($recipientRows as $recipient) {
            $email = strtolower((string) $recipient->email);
            $domain = strpos($email, '@') !== false ? substr(strrchr($email, '@'), 1) : '';

            $provider = 'Other Domains';
            if (str_contains($domain, 'gmail')) {
                $provider = 'Gmail';
            } elseif (str_contains($domain, 'outlook') || str_contains($domain, 'hotmail') || str_contains($domain, 'live.')) {
                $provider = 'Microsoft Outlook';
            } elseif (str_contains($domain, 'yahoo')) {
                $provider = 'Yahoo Mail';
            } elseif (str_contains($domain, 'icloud') || str_contains($domain, 'me.com') || str_contains($domain, 'mac.com')) {
                $provider = 'Apple Mail';
            }

            $status = (string) $recipient->status;
            if ($status !== 'pending') {
                $providerBuckets[$provider]['sent']++;
            }
            if (! in_array($status, ['bounced', 'failed', 'pending'], true)) {
                $providerBuckets[$provider]['delivered']++;
            }
            if ($status === 'bounced' || $status === 'failed') {
                $providerBuckets[$provider]['bounced']++;
            }
        }

        $providerPerformance = collect($providerBuckets)
            ->map(function (array $bucket, string $provider) {
                $sent = (int) $bucket['sent'];
                $deliveredCount = (int) $bucket['delivered'];
                $bounceRate = $sent > 0 ? round(((int) $bucket['bounced'] / $sent) * 100, 1) : 0;

                if ($sent === 0) {
                    return [
                        'provider' => $provider,
                        'sent' => 0,
                        'delivered' => 0,
                        'delivery_pct' => 0,
                        'bounce_rate' => 0,
                        'health' => 'Excellent',
                    ];
                }

                $deliveryPct = round(($deliveredCount / $sent) * 100, 1);
                $health = $bounceRate <= 2.0 ? 'Excellent' : ($bounceRate <= 5.0 ? 'Needs Attention' : 'Critical');

                return [
                    'provider' => $provider,
                    'sent' => $sent,
                    'delivered' => $deliveredCount,
                    'delivery_pct' => $deliveryPct,
                    'bounce_rate' => $bounceRate,
                    'health' => $health,
                ];
            })
            ->values();

        // A/B testing setup data
        $abSettings = is_array($campaign->settings) ? ($campaign->settings['ab_testing'] ?? []) : [];
        $abVariantTemplateIds = is_array($abSettings['variant_template_ids'] ?? null) ? $abSettings['variant_template_ids'] : [];
        $abVariantSender = is_array($abSettings['variant_sender'] ?? null) ? $abSettings['variant_sender'] : [];
        $abTestConfig = [
            'test_type' => (string) ($abSettings['test_type'] ?? 'subject'),
            'test_group_percent' => max(5, min(50, (int) ($abSettings['test_group_percent'] ?? 20))),
            'winning_metric' => (string) ($abSettings['winning_metric'] ?? 'open_rate'),
            'duration_hours' => (int) ($abSettings['duration_hours'] ?? 4),
        ];
        $abTestConfig['winner_group_percent'] = 100 - $abTestConfig['test_group_percent'];

        $abTestVariants = $campaign->variants
            ->sortBy('id')
            ->take(5)
            ->map(function ($variant, $idx) use ($abVariantSender, $abVariantTemplateIds) {
                $sender = is_array($abVariantSender[$idx] ?? null) ? $abVariantSender[$idx] : [];

                return [
                    'name' => $variant->name ?: ('Variant '.chr(65 + $idx)),
                    'subject' => (string) ($variant->subject ?? ''),
                    'html_content' => (string) ($variant->html_content ?? ''),
                    'template_id' => isset($abVariantTemplateIds[$idx]) ? (int) $abVariantTemplateIds[$idx] : null,
                    'from_name' => (string) ($sender['from_name'] ?? ''),
                    'delivery_server_id' => isset($sender['delivery_server_id']) ? (int) $sender['delivery_server_id'] : null,
                    'from_email' => (string) ($sender['from_email'] ?? ''),
                    'split_percentage' => (int) ($variant->split_percentage ?? 0),
                    'is_winner' => (bool) ($variant->is_winner ?? false),
                    'open_rate' => (float) ($variant->open_rate ?? 0),
                    'click_rate' => (float) ($variant->click_rate ?? 0),
                    'bounce_rate' => (float) ($variant->bounce_rate ?? 0),
                ];
            })
            ->values()
            ->toArray();

        $customer = auth('customer')->user();
        $mustAddDelivery = (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false);
        $canUseSystem = (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false);
        $abDeliveryServerOptions = app(DeliveryServerService::class)
            ->getSelectableDeliveryServersForCustomer($customer, $mustAddDelivery, $canUseSystem)
            ->map(fn ($server) => [
                'id' => (int) $server->id,
                'name' => (string) $server->name,
                'from_email' => (string) ($server->from_email ?? ''),
                'from_name' => (string) ($server->from_name ?? ''),
            ])
            ->values()
            ->toArray();

        if (count($abTestVariants) === 0) {
            $abTestVariants = [
                [
                    'name' => 'Variant A',
                    'subject' => (string) ($campaign->subject ?? ''),
                    'html_content' => (string) ($campaign->html_content ?? ''),
                    'template_id' => (int) ($campaign->template_id ?? 0) ?: null,
                    'from_name' => (string) ($campaign->from_name ?? ''),
                    'delivery_server_id' => (int) ($campaign->delivery_server_id ?? 0) ?: null,
                    'from_email' => (string) ($campaign->from_email ?? ''),
                    'split_percentage' => 50,
                    'is_winner' => false,
                    'open_rate' => 0,
                    'click_rate' => 0,
                    'bounce_rate' => 0,
                ],
                [
                    'name' => 'Variant B',
                    'subject' => (string) ($campaign->subject ?? ''),
                    'html_content' => (string) ($campaign->html_content ?? ''),
                    'template_id' => (int) ($campaign->template_id ?? 0) ?: null,
                    'split_percentage' => 50,
                    'is_winner' => false,
                    'open_rate' => 0,
                    'click_rate' => 0,
                    'bounce_rate' => 0,
                ],
            ];
        } elseif (count($abTestVariants) === 1) {
            $abTestVariants[] = [
                'name' => 'Variant B',
                'subject' => (string) ($campaign->subject ?? ''),
                'html_content' => (string) ($campaign->html_content ?? ''),
                'template_id' => (int) ($campaign->template_id ?? 0) ?: null,
                'from_name' => (string) ($campaign->from_name ?? ''),
                'delivery_server_id' => (int) ($campaign->delivery_server_id ?? 0) ?: null,
                'from_email' => (string) ($campaign->from_email ?? ''),
                'split_percentage' => max(0, 100 - (int) $abTestVariants[0]['split_percentage']),
                'is_winner' => false,
                'open_rate' => 0,
                'click_rate' => 0,
                'bounce_rate' => 0,
            ];
        }

        $abTemplateOptions = Template::query()
            ->whereIn('type', ['email', 'campaign'])
            ->whereIn('id', $this->accessibleTemplateIdsForCustomer((int) $campaign->customer_id))
            ->select(['id', 'name', 'description', 'thumbnail'])
            ->orderByRaw('CASE WHEN customer_id = ? THEN 0 ELSE 1 END', [$campaign->customer_id])
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(fn ($tpl) => [
                'id' => (int) $tpl->id,
                'name' => (string) $tpl->name,
                'description' => (string) ($tpl->description ?? ''),
                'thumbnail' => (string) ($tpl->thumbnail ?? ''),
                'preview_url' => route('customer.templates.preview', $tpl),
            ])
            ->values()
            ->toArray();

        // Error breakdown
        $errorBreakdown = $campaign->recipients()
            ->where('status', 'failed')
            ->whereNotNull('failure_reason')
            ->selectRaw('failure_reason, COUNT(*) as count')
            ->groupBy('failure_reason')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Calculate sending speed
        $sendingSpeed = 0;
        if ($campaign->started_at && $campaign->sent_count > 0) {
            $secondsElapsed = max(1, now()->diffInSeconds($campaign->started_at));
            $sendingSpeed = round($campaign->sent_count / $secondsElapsed, 2);
        }

        // Resolved 3-layer send speed (campaign → server → global) for display.
        $speedResolver = app(SendingSpeedResolver::class);
        $resolvedDeliveryServer = $campaign->delivery_server_id
            ? DeliveryServer::find($campaign->delivery_server_id)
            : null;
        $resolvedSpeed = [
            'gap_ms' => $speedResolver->gapMs($campaign, $resolvedDeliveryServer),
            'rate_per_minute' => $speedResolver->ratePerMinute($campaign, $resolvedDeliveryServer),
        ];

        // Check deliverability (DKIM/SPF/DMARC) - get from sending domain
        $deliverability = [
            'dkim' => false,
            'spf' => false,
            'dmarc' => false,
        ];

        if ($campaign->from_email) {
            $domain = substr(strrchr($campaign->from_email, '@'), 1);
            $sendingDomain = \App\Models\SendingDomain::where('domain', $domain)
                ->where('customer_id', $campaign->customer_id)
                ->first();

            if ($sendingDomain && $sendingDomain->status === 'verified') {
                $deliverability['dkim'] = true;
                $deliverability['spf'] = true;
                $deliverability['dmarc'] = true;
            }
        }

        $stats = [
            'total_recipients' => $totalRecipients,
            'sent_count' => $campaign->sent_count ?? 0,
            'delivered' => $delivered,
            'pending_count' => $recipientStatuses['pending'] ?? 0,
            'opened_count' => $uniqueOpens, // Use unique opens instead of total open events
            'clicked_count' => $campaign->clicked_count ?? 0,
            'bounced_count' => $campaign->bounced_count ?? 0,
            'failed_count' => $campaign->failed_count ?? 0,
            'unsubscribed_count' => $campaign->unsubscribed_count ?? 0,
            'complained_count' => $campaign->complained_count ?? 0,
            'open_rate' => $openRate,
            'click_rate' => $clickRate,
            'bounce_rate' => $bounceRate,
            'failure_rate' => $failureRate,
            'delivery_rate' => $deliveryRate,
            'sending_speed' => $sendingSpeed,
            'resolved_speed' => $resolvedSpeed,
            'recipient_statuses' => $recipientStatuses,
            'top_links' => $topLinks,
            'error_breakdown' => $errorBreakdown,
            'deliverability' => $deliverability,
        ];

        $campaignLogsData = [
            'status' => $campaign->status,
            'failure_reason' => $campaign->failure_reason,
            'preflight_issues' => $runPreflightIssues,
            'error_breakdown' => $errorBreakdown,
            'recent_events' => $campaign->logs()
                ->with('recipient:id,email,first_name,last_name')
                ->latest()
                ->limit(20)
                ->get(),
        ];

        return view('customer.campaigns.show', compact(
            'campaign', 'stats', 'runPreflightIssues',
            'engagementChartData', 'liveActivity',
            'totalOpenEvents', 'heatmapData', 'agentGroups',
            'subscriberInsights', 'subscriberFeedbackChartData', 'topEngagers',
            'deliverabilityInsights', 'deliverabilityChartData', 'bounceTimelineData', 'providerPerformance',
            'deliveryAnalyticsOverviewTotals', 'deliveryAnalyticsByServer', 'deliveryAnalyticsRows',
            'abTestConfig', 'abTestVariants', 'abTemplateOptions', 'abDeliveryServerOptions',
            'autoResumeAt', 'autoResumeReason',
            'campaignAutomations', 'campaignLogsData', 'inboxRotationServers', 'inboxRotationData',
            'selectedInboxRotationServer', 'selectedInboxRotationLogs', 'selectedInboxRotationLogStats', 'selectedInboxRotationLogCounts',
            'selectedInboxRotationDeliveryLogs', 'selectedInboxRotationDeliveryLogStats', 'inboxRotationDeliveryLogSummary'
        ));
    }

    /**
     * Show campaign recipients table.
     */
    public function recipients(Campaign $campaign, Request $request)
    {
        $this->authorizeOwnership($campaign);

        $query = $campaign->recipients()->with([
            'logs' => function ($q) {
                $q->where('event', 'clicked')
                    ->whereNotNull('url')
                    ->orderBy('created_at');
            },
        ]);

        // Apply filters
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $recipients = $query->latest('created_at')->paginate(50);

        return view('customer.campaigns.recipients', compact('campaign', 'recipients'));
    }

    public function replies(Campaign $campaign, Request $request)
    {
        $this->authorizeOwnership($campaign);

        $query = $campaign->replies()->with('recipient');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('from_email', 'like', "%{$search}%")
                    ->orWhere('from_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('recipient', function ($sub) use ($search) {
                        $sub->where('email', 'like', "%{$search}%");
                    });
            });
        }

        $replies = $query
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('customer.campaigns.replies', compact('campaign', 'replies'));
    }

    /**
     * Get campaign stats (AJAX endpoint for real-time updates).
     */
    public function stats(Campaign $campaign)
    {
        $this->authorizeOwnership($campaign);

        $campaign->refresh();
        $campaign->load(['recipients', 'logs']);

        // Calculate total recipients - use actual count if campaign has started, otherwise calculate expected
        $totalRecipients = $campaign->total_recipients;
        if ($campaign->total_recipients === 0 && $campaign->emailList) {
            // Calculate expected recipients for campaigns that haven't started yet
            $totalRecipients = $this->calculateExpectedRecipients($campaign);
        }

        // Calculate delivered count (sent - bounced)
        $delivered = max(0, $campaign->sent_count - $campaign->bounced_count);

        // Recipient status breakdown
        $recipientStatuses = $campaign->recipients()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Calculate unique opens (recipients who opened at least once)
        $uniqueOpens = ($recipientStatuses['opened'] ?? 0) + ($recipientStatuses['clicked'] ?? 0);

        // Calculate rates based on unique opens
        $openRate = $delivered > 0 ? round(($uniqueOpens / $delivered) * 100, 2) : 0;
        $clickRate = $delivered > 0 ? round(($campaign->clicked_count / $delivered) * 100, 2) : 0;
        $bounceRate = $campaign->sent_count > 0 ? round(($campaign->bounced_count / $campaign->sent_count) * 100, 2) : 0;
        $failureRate = $campaign->sent_count > 0 ? round(($campaign->failed_count / $campaign->sent_count) * 100, 2) : 0;
        $deliveryRate = $campaign->sent_count > 0 ? round(($delivered / $campaign->sent_count) * 100, 2) : 0;

        // Calculate sending speed (emails per second) - based on recent activity
        $sendingSpeed = 0;
        if ($campaign->started_at && $campaign->sent_count > 0) {
            // Get recent activity from last 30 seconds to calculate current rate
            $recentActivity = $campaign->recipients()
                ->where('status', 'sent')
                ->where('updated_at', '>=', now()->subSeconds(30))
                ->count();

            if ($recentActivity > 0) {
                $sendingSpeed = round($recentActivity / 30, 2);
            } else {
                // Fallback: calculate average over last 5 minutes if no recent activity
                $fiveMinutesAgo = $campaign->recipients()
                    ->where('status', 'sent')
                    ->where('updated_at', '>=', now()->subMinutes(5))
                    ->count();

                if ($fiveMinutesAgo > 0) {
                    $sendingSpeed = round($fiveMinutesAgo / 300, 2);
                } else {
                    // Final fallback: show average over entire campaign
                    $secondsElapsed = max(1, now()->diffInSeconds($campaign->started_at));
                    $sendingSpeed = round($campaign->sent_count / $secondsElapsed, 2);
                }
            }
        }

        // Top clicked links
        $topLinks = $campaign->logs()
            ->where('event', 'clicked')
            ->whereNotNull('url')
            ->selectRaw('url, COUNT(*) as clicks')
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'url' => $log->url,
                    'clicks' => $log->clicks,
                ];
            });

        // Error breakdown
        $errorBreakdown = $campaign->recipients()
            ->where('status', 'failed')
            ->whereNotNull('failure_reason')
            ->selectRaw('failure_reason, COUNT(*) as count')
            ->groupBy('failure_reason')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'failure_reason')
            ->toArray();

        $stats = [
            'status' => $campaign->status,
            'total_recipients' => $totalRecipients,
            'sent_count' => $campaign->sent_count ?? 0,
            'delivered' => $delivered,
            'pending_count' => $recipientStatuses['pending'] ?? 0,
            'opened_count' => $uniqueOpens, // Use unique opens instead of total open events
            'clicked_count' => $campaign->clicked_count ?? 0,
            'replied_count' => $campaign->replied_count ?? 0,
            'bounced_count' => $campaign->bounced_count ?? 0,
            'failed_count' => $campaign->failed_count ?? 0,
            'unsubscribed_count' => $campaign->unsubscribed_count ?? 0,
            'complained_count' => $campaign->complained_count ?? 0,
            'open_rate' => $openRate,
            'click_rate' => $clickRate,
            'bounce_rate' => $bounceRate,
            'failure_rate' => $failureRate,
            'delivery_rate' => $deliveryRate,
            'progress_percentage' => $totalRecipients > 0
                ? round(($campaign->sent_count / $totalRecipients) * 100, 2)
                : 0,
            'sending_speed' => $sendingSpeed,
            'recipient_statuses' => $recipientStatuses,
            'top_links' => $topLinks,
            'error_breakdown' => $errorBreakdown,
            'started_at' => $campaign->started_at?->toIso8601String(),
            'finished_at' => $campaign->finished_at?->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Campaign $campaign)
    {
        $this->authorizeOwnership($campaign);
        $customer = auth('customer')->user();
        $runPreflightIssues = [];

        if ($campaign->canStart()) {
            try {
                $this->campaignService->ensureCanRun($campaign);
            } catch (\RuntimeException $e) {
                $runPreflightIssues[] = $e->getMessage();
            }
        }
        $emailLists = EmailList::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->withCount('confirmedSubscribers')
            ->with(['segments' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        $templates = Template::where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhere(function ($subQ) {
                    $subQ->where('is_public', true)
                        ->where('is_system', false);
                });
        })
            ->whereIn('type', ['email', 'campaign'])
            ->get();

        $footerTemplates = Template::where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhere(function ($subQ) {
                    $subQ->where('is_public', true)
                        ->where('is_system', false);
                });
        })
            ->where('type', 'footer')
            ->get();

        $signatureTemplates = Template::where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhere(function ($subQ) {
                    $subQ->where('is_public', true)
                        ->where('is_system', false);
                });
        })
            ->where('type', 'signature')
            ->get();

        $mustAddDelivery = (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false);
        $mustAddReply = (bool) $customer->groupSetting('servers.permissions.must_add_reply_server', false);
        $mustAddSending = (bool) $customer->groupSetting('domains.sending_domains.must_add', false);
        $mustAddTracking = (bool) $customer->groupSetting('domains.tracking_domains.must_add', false);
        $canUseSystem = (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false);

        $deliveryServers = app(DeliveryServerService::class)->getSelectableDeliveryServersForCustomer(
            $customer,
            $mustAddDelivery,
            $canUseSystem
        );

        $replyServers = ReplyServer::query()
            ->where('active', true)
            ->when($mustAddReply, function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            }, function ($q) use ($customer, $canUseSystem) {
                $q->where(function ($sub) use ($customer, $canUseSystem) {
                    $sub->where('customer_id', $customer->id);
                    if ($canUseSystem) {
                        $sub->orWhereNull('customer_id');
                    }
                });
            })
            ->orderBy('name')
            ->get();

        $sendingDomains = SendingDomain::query()
            ->where('status', 'verified')
            ->when($mustAddSending, function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            }, function ($q) use ($customer, $canUseSystem) {
                $q->where(function ($sub) use ($customer, $canUseSystem) {
                    $sub->where('customer_id', $customer->id);
                    if ($canUseSystem) {
                        $sub->orWhereNull('customer_id');
                    }
                });
            })
            ->orderBy('domain')
            ->get();

        $trackingDomains = TrackingDomain::query()
            ->where('status', 'verified')
            ->when($mustAddTracking, function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            }, function ($q) use ($customer, $canUseSystem) {
                $q->where(function ($sub) use ($customer, $canUseSystem) {
                    $sub->where('customer_id', $customer->id);
                    if ($canUseSystem) {
                        $sub->orWhereNull('customer_id');
                    }
                });
            })
            ->orderBy('domain')
            ->get();

        $bounceServers = BounceServer::query()
            ->where('active', true)
            ->when((bool) $customer->groupSetting('servers.permissions.must_add_bounce_server', false), function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            }, function ($q) use ($customer, $canUseSystem) {
                $q->where(function ($sub) use ($customer, $canUseSystem) {
                    $sub->where('customer_id', $customer->id);
                    if ($canUseSystem) {
                        $sub->orWhereNull('customer_id');
                    }
                });
            })
            ->orderBy('name')
            ->get();

        $unlayerProjectId = config('services.unlayer.project_id');
        $unlayerDesign = $this->unlayerDesignFromCampaign($campaign);
        $campaignTagsByList = $this->buildCampaignTagsByList($emailLists);

        return view('customer.campaigns.edit', compact('campaign', 'emailLists', 'templates', 'footerTemplates', 'signatureTemplates', 'deliveryServers', 'replyServers', 'sendingDomains', 'trackingDomains', 'bounceServers', 'runPreflightIssues', 'unlayerProjectId', 'unlayerDesign', 'campaignTagsByList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Campaign $campaign)
    {
        $this->authorizeOwnership($campaign);
        $customer = auth('customer')->user();
        $mustAddReply = (bool) $customer->groupSetting('servers.permissions.must_add_reply_server', false);
        $mustAddDelivery = (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false);
        $canUseSystem = (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false);

        $selectableDeliveryServerIds = app(DeliveryServerService::class)
            ->getSelectableDeliveryServerIdsForCustomer($customer, $mustAddDelivery, $canUseSystem);

        $payload = $request->all();
        if (! empty($payload['html_content_b64']) && isset($payload['html_content'])) {
            $decoded = base64_decode((string) $payload['html_content'], true);
            if ($decoded !== false) {
                $payload['html_content'] = $decoded;
            }
        }
        if (array_key_exists('delivery_server_id', $payload) && $payload['delivery_server_id'] === '') {
            $payload['delivery_server_id'] = null;
        }

        // If inbox rotation is enabled and a primary delivery server isn't chosen,
        // fall back to the first selected rotation server so the required-rule passes
        // (especially when must_add_delivery_server is enabled).
        if ((bool) ($payload['inbox_rotation_enabled'] ?? false) && empty($payload['delivery_server_id'])) {
            $rotationFallback = collect((array) ($payload['inbox_rotation_server_ids'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->first();
            if ($rotationFallback) {
                $payload['delivery_server_id'] = $rotationFallback;
            }
        }

        $validator = validator($payload, [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'list_id' => ['nullable', 'exists:email_lists,id'],
            'delivery_server_id' => [
                $mustAddDelivery ? 'required' : 'nullable',
                'integer',
                Rule::in($selectableDeliveryServerIds),
            ],
            'reply_server_id' => [
                'nullable',
                Rule::exists('reply_servers', 'id')->where(function ($q) use ($customer, $mustAddReply, $canUseSystem) {
                    $q->where('active', true);

                    if ($mustAddReply || ! $canUseSystem) {
                        $q->where('customer_id', $customer->id);

                        return;
                    }

                    $q->where(function ($sub) use ($customer) {
                        $sub->where('customer_id', $customer->id)
                            ->orWhereNull('customer_id');
                    });
                }),
            ],
            'sending_domain_id' => ['nullable', 'exists:sending_domains,id'],
            'tracking_domain_id' => ['nullable', 'exists:tracking_domains,id'],
            'bounce_server_id' => [
                'nullable',
                Rule::exists('bounce_servers', 'id')->where(function ($q) use ($customer) {
                    $q->where('active', true);
                    $mustAddBounce = (bool) $customer->groupSetting('servers.permissions.must_add_bounce_server', false);
                    $canUseSystem = (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false);

                    if ($mustAddBounce || ! $canUseSystem) {
                        $q->where('customer_id', $customer->id);

                        return;
                    }

                    $q->where(function ($sub) use ($customer) {
                        $sub->where('customer_id', $customer->id)
                            ->orWhereNull('customer_id');
                    });
                }),
            ],
            'template_id' => ['nullable', 'exists:templates,id'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'type' => ['nullable', 'in:regular,autoresponder,recurring'],
            'status' => ['nullable', 'in:draft,queued,scheduled,running,paused,completed,failed'],
            'html_content' => ['nullable', 'string'],
            'plain_text_content' => ['nullable', 'string'],
            'template_data' => ['nullable'],
            'footer_template_id' => [
                'nullable',
                Rule::exists('templates', 'id')->where(function ($q) use ($customer) {
                    $q->where('type', 'footer')
                        ->whereNull('deleted_at')
                        ->where(function ($subQ) use ($customer) {
                            $subQ->where('customer_id', $customer->id)
                                ->orWhere(function ($inner) {
                                    $inner->where('is_public', true)->where('is_system', false);
                                });
                        });
                }),
            ],
            'signature_template_id' => [
                'nullable',
                Rule::exists('templates', 'id')->where(function ($q) use ($customer) {
                    $q->where('type', 'signature')
                        ->whereNull('deleted_at')
                        ->where(function ($subQ) use ($customer) {
                            $subQ->where('customer_id', $customer->id)
                                ->orWhere(function ($inner) {
                                    $inner->where('is_public', true)->where('is_system', false);
                                });
                        });
                }),
            ],
            'send_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'track_opens' => ['nullable', 'boolean'],
            'track_clicks' => ['nullable', 'boolean'],
            'enable_spintax' => ['nullable', 'boolean'],
            'spam_scoring_enabled' => ['nullable', 'boolean'],
            'inbox_rotation_enabled' => ['nullable', 'boolean'],
            'inbox_rotation_server_ids' => ['nullable', 'array'],
            'inbox_rotation_server_ids.*' => ['integer', Rule::in($selectableDeliveryServerIds)],
            'inbox_rotation_min_delay' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'inbox_rotation_max_delay' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'inbox_rotation_daily_cap' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_scheduled' => ['nullable', 'boolean'],
            'schedule_timezone' => ['nullable', 'string', 'max:64'],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['integer', 'between:1,7'],
            'schedule_time_blocks' => ['nullable', 'array'],
            'schedule_time_blocks.*.from' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'schedule_time_blocks.*.to' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'send_gap_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
            'send_rate_per_minute' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $validator->after(function ($validator) use ($payload) {
            $enabled = (bool) ($payload['inbox_rotation_enabled'] ?? false);
            if (! $enabled) {
                return;
            }

            $rotationServerIds = collect((array) ($payload['inbox_rotation_server_ids'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            if ($rotationServerIds->count() < 2) {
                $validator->errors()->add('inbox_rotation_server_ids', 'Select at least two delivery servers to enable inbox rotation.');
            }
        });

        $validated = $validator->validate();

        if (array_key_exists('html_content', $validated)) {
            $validated['html_content'] = $this->stripLegacyUnsubscribeArtifacts($validated['html_content']);
        }
        if (array_key_exists('plain_text_content', $validated)) {
            $validated['plain_text_content'] = $this->stripLegacyUnsubscribeArtifacts($validated['plain_text_content']);
        }

        $unlayerData = $this->buildUnlayerTemplateData($request->input('template_data'));
        if ($unlayerData !== null) {
            $validated['template_data'] = $unlayerData;
        } else {
            unset($validated['template_data']);
        }

        if (empty($validated['plain_text_content']) && ! empty($validated['html_content'])) {
            $validated['plain_text_content'] = trim(preg_replace('/\s+/', ' ', strip_tags($validated['html_content'])));
        }

        $appTimezone = config('app.timezone', 'UTC');
        $scheduleTimezone = trim((string) ($validated['schedule_timezone'] ?? '')) ?: ($customer->timezone ?? $appTimezone);
        if (! in_array($scheduleTimezone, \DateTimeZone::listIdentifiers(), true)) {
            $scheduleTimezone = $customer->timezone ?? $appTimezone;
        }
        $isScheduled = (bool) ($validated['is_scheduled'] ?? false);
        if (! $isScheduled) {
            $validated['send_at'] = null;
            $validated['scheduled_at'] = null;
        }
        if (! empty($validated['send_at'])) {
            $validated['send_at'] = Carbon::parse($validated['send_at'], $scheduleTimezone)->setTimezone($appTimezone);
        }
        if (! empty($validated['scheduled_at'])) {
            $validated['scheduled_at'] = Carbon::parse($validated['scheduled_at'], $scheduleTimezone)->setTimezone($appTimezone);
        }

        // Convert empty string to null for nullable fields
        if (isset($validated['delivery_server_id']) && $validated['delivery_server_id'] === '') {
            $validated['delivery_server_id'] = null;
        }
        if (isset($validated['reply_server_id']) && $validated['reply_server_id'] === '') {
            $validated['reply_server_id'] = null;
        }
        if (isset($validated['bounce_server_id']) && $validated['bounce_server_id'] === '') {
            $validated['bounce_server_id'] = null;
        }
        if (isset($validated['sending_domain_id']) && $validated['sending_domain_id'] === '') {
            $validated['sending_domain_id'] = null;
        }
        if (isset($validated['tracking_domain_id']) && $validated['tracking_domain_id'] === '') {
            $validated['tracking_domain_id'] = null;
        }
        if (isset($validated['list_id']) && $validated['list_id'] === '') {
            $validated['list_id'] = null;
        }
        if (isset($validated['segments']) && ! is_array($validated['segments'])) {
            $validated['segments'] = [];
        }
        if (isset($validated['segments'])) {
            $validated['segments'] = collect((array) $validated['segments'])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }
        if (! empty($validated['segments']) && ! empty($validated['list_id'])) {
            $validated['segments'] = ListSegment::query()
                ->where('list_id', (int) $validated['list_id'])
                ->whereIn('id', $validated['segments'])
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }
        if (isset($validated['template_id']) && $validated['template_id'] === '') {
            $validated['template_id'] = null;
        }

        $footerTemplateId = $validated['footer_template_id'] ?? null;
        $signatureTemplateId = $validated['signature_template_id'] ?? null;
        unset($validated['footer_template_id'], $validated['signature_template_id']);

        $settings = array_replace((array) ($campaign->settings ?? []), (array) ($validated['settings'] ?? []));
        if (! empty($footerTemplateId)) {
            $settings['footer_template_id'] = (int) $footerTemplateId;
        }
        if (! empty($signatureTemplateId)) {
            $settings['signature_template_id'] = (int) $signatureTemplateId;
        }

        // Update spintax and spam scoring settings
        if (isset($validated['enable_spintax'])) {
            $settings['enable_spintax'] = (bool) $validated['enable_spintax'];
        }
        if (isset($validated['spam_scoring_enabled'])) {
            $settings['spam_scoring_enabled'] = (bool) $validated['spam_scoring_enabled'];
        }

        $rotationServerIds = collect((array) ($validated['inbox_rotation_server_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $rotationEnabled = (bool) ($validated['inbox_rotation_enabled'] ?? false) && count($rotationServerIds) >= 2;
        $settings['inbox_rotation_enabled'] = $rotationEnabled;
        $settings['inbox_rotation_server_ids'] = $rotationEnabled ? $rotationServerIds : [];

        if (array_key_exists('inbox_rotation_min_delay', $validated)) {
            $settings['inbox_rotation_min_delay'] = $validated['inbox_rotation_min_delay'] === null
                ? null
                : max(0, (int) $validated['inbox_rotation_min_delay']);
        }
        if (array_key_exists('inbox_rotation_max_delay', $validated)) {
            $settings['inbox_rotation_max_delay'] = $validated['inbox_rotation_max_delay'] === null
                ? null
                : max(0, (int) $validated['inbox_rotation_max_delay']);
        }
        if (array_key_exists('inbox_rotation_daily_cap', $validated)) {
            $settings['inbox_rotation_daily_cap'] = $validated['inbox_rotation_daily_cap'] === null
                ? null
                : max(0, (int) $validated['inbox_rotation_daily_cap']);
        }

        // When rotation is enabled, ensure the campaign still has a delivery_server_id
        // (use the first rotation server) so downstream jobs/UI don't treat it as unconfigured.
        if ($rotationEnabled && empty($validated['delivery_server_id'])) {
            $validated['delivery_server_id'] = (int) $rotationServerIds[0];
        }

        if (! empty($settings)) {
            $validated['settings'] = $settings;
        }

        // Persist sending schedule into settings.sending_schedule
        $sendingSchedule = [
            'timezone' => $scheduleTimezone,
            'days' => array_values(array_unique(array_map('intval', (array) ($validated['schedule_days'] ?? [])))),
            'time_blocks' => array_values(array_filter(array_map(function ($block) {
                $from = trim((string) ($block['from'] ?? ''));
                $to = trim((string) ($block['to'] ?? ''));
                if ($from === '' || $to === '') {
                    return null;
                }

                return ['from' => $from, 'to' => $to];
            }, (array) ($validated['schedule_time_blocks'] ?? [])))),
        ];
        $settings['sending_schedule'] = $sendingSchedule;

        $sendSpeed = [];
        if (array_key_exists('send_gap_ms', $validated)) {
            $sendSpeed['gap_ms'] = $validated['send_gap_ms'] === null || $validated['send_gap_ms'] === ''
                ? null
                : max(0, (int) $validated['send_gap_ms']);
        }
        if (array_key_exists('send_rate_per_minute', $validated)) {
            $sendSpeed['rate_per_minute'] = $validated['send_rate_per_minute'] === null || $validated['send_rate_per_minute'] === ''
                ? null
                : max(0, (int) $validated['send_rate_per_minute']);
        }
        if ($sendSpeed !== []) {
            $settings['send_speed'] = $sendSpeed;
        }

        $validated['settings'] = $settings;

        // Remove from validated as they're now in settings
        unset(
            $validated['enable_spintax'],
            $validated['spam_scoring_enabled'],
            $validated['inbox_rotation_enabled'],
            $validated['inbox_rotation_server_ids'],
            $validated['inbox_rotation_min_delay'],
            $validated['inbox_rotation_max_delay'],
            $validated['inbox_rotation_daily_cap'],
            $validated['is_scheduled'],
            $validated['schedule_timezone'],
            $validated['schedule_days'],
            $validated['schedule_time_blocks'],
            $validated['send_gap_ms'],
            $validated['send_rate_per_minute']
        );

        if (! empty($validated['send_at']) && empty($validated['scheduled_at'])) {
            $validated['scheduled_at'] = $validated['send_at'];
            $validated['status'] = 'scheduled';
        }

        $this->campaignService->update($campaign, $validated);

        return redirect()
            ->route('customer.campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Ping a delivery/reply/bounce server and return its status without sending an email.
     */
    public function serverPing(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:delivery,reply,bounce'],
            'id' => ['required', 'integer'],
        ]);

        $customer = auth('customer')->user();
        $customerId = $customer?->id;
        $type = $validated['type'];
        $id = (int) $validated['id'];

        try {
            if ($type === 'delivery') {
                $server = \App\Models\DeliveryServer::query()
                    ->where('id', $id)
                    ->where(fn ($q) => $q->where('customer_id', $customerId)->orWhereNull('customer_id'))
                    ->first();

                if (! $server) {
                    return response()->json(['ok' => false, 'message' => 'Delivery server not found or not accessible.'], 404);
                }

                if ($server->status !== 'active') {
                    return response()->json(['ok' => false, 'message' => "Server is {$server->status}. Only active servers can be used."]);
                }

                return response()->json(['ok' => true, 'message' => "Connected — {$server->name} is active."]);
            }

            if ($type === 'reply') {
                $server = \App\Models\ReplyServer::query()
                    ->where('id', $id)
                    ->where(fn ($q) => $q->where('customer_id', $customerId)->orWhereNull('customer_id'))
                    ->first();

                if (! $server) {
                    return response()->json(['ok' => false, 'message' => 'Reply server not found or not accessible.'], 404);
                }

                if (isset($server->status) && $server->status !== 'active') {
                    return response()->json(['ok' => false, 'message' => "Server is {$server->status}."]);
                }

                return response()->json(['ok' => true, 'message' => "Connected — {$server->name} is active."]);
            }

            if ($type === 'bounce') {
                $server = \App\Models\BounceServer::query()
                    ->where('id', $id)
                    ->where(fn ($q) => $q->where('customer_id', $customerId)->orWhereNull('customer_id'))
                    ->first();

                if (! $server) {
                    return response()->json(['ok' => false, 'message' => 'Bounce server not found or not accessible.'], 404);
                }

                if (isset($server->status) && $server->status !== 'active') {
                    return response()->json(['ok' => false, 'message' => "Server is {$server->status}."]);
                }

                return response()->json(['ok' => true, 'message' => "Connected — {$server->name} is active."]);
            }
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }

        return response()->json(['ok' => false, 'message' => 'Unknown server type.'], 400);
    }

    /**
     * Preview spam score for current draft content.
     */
    public function previewSpamScore(Request $request, SpamScoringService $spamScoringService)
    {
        $payload = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'html_content' => ['nullable', 'string'],
            'plain_text_content' => ['nullable', 'string'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'delivery_server_id' => ['nullable', 'integer'],
            'delivery_server_type' => ['nullable', 'string', 'max:100'],
            'delivery_server_from_email' => ['nullable', 'email', 'max:255'],
            'reply_server_id' => ['nullable', 'integer'],
            'delivery_server_mode' => ['nullable', 'string', 'in:single,rotation'],
            'rotation_server_ids' => ['nullable', 'array'],
            'rotation_server_ids.*' => ['integer'],
        ]);

        $subject = trim((string) ($payload['subject'] ?? ''));
        $htmlContent = (string) ($payload['html_content'] ?? '');
        $plainTextContent = (string) ($payload['plain_text_content'] ?? '');

        if ($plainTextContent === '' && $htmlContent !== '') {
            $plainTextContent = trim(preg_replace('/\s+/', ' ', strip_tags($htmlContent)));
        }

        $fromEmail = trim((string) ($payload['from_email'] ?? ''));
        $replyTo = $payload['reply_to'] ?? $fromEmail;
        $fromName = trim((string) ($payload['from_name'] ?? ''));
        $deliveryServerId = $payload['delivery_server_id'] ?? null;
        $deliveryServerType = trim((string) ($payload['delivery_server_type'] ?? ''));
        $deliveryServerFromEmail = trim((string) ($payload['delivery_server_from_email'] ?? ''));
        $replyServerId = $payload['reply_server_id'] ?? null;
        $deliveryServerMode = strtolower(trim((string) ($payload['delivery_server_mode'] ?? '')));
        $rotationServerIds = array_values(array_filter(array_map('intval', (array) ($payload['rotation_server_ids'] ?? []))));
        $rotationServerTypes = [];
        if (! empty($rotationServerIds)) {
            $rotationServerTypes = \App\Models\DeliveryServer::query()
                ->whereIn('id', $rotationServerIds)
                ->pluck('type')
                ->map(fn ($type) => strtolower((string) $type))
                ->all();
        }

        $result = $spamScoringService->calculateSpamScore(
            $subject,
            $htmlContent,
            $plainTextContent,
            [
                'from_name' => $fromName,
                'from_email' => $fromEmail,
                'reply_to' => $replyTo,
                'delivery_server_id' => $deliveryServerId,
                'delivery_server_type' => $deliveryServerType,
                'delivery_server_from_email' => $deliveryServerFromEmail,
                'reply_server_id' => $replyServerId,
                'delivery_server_mode' => $deliveryServerMode,
                'rotation_server_ids' => $rotationServerIds,
                'rotation_server_types' => $rotationServerTypes,
            ]
        );

        return response()->json([
            'ok' => true,
            'result' => $result,
        ]);
    }

    /**
     * Render the campaign HTML content as a full-page preview.
     */
    public function previewHtml(Campaign $campaign)
    {
        $this->authorizeOwnership($campaign);

        $html = (string) ($campaign->html_content ?? '');

        if ($html === '') {
            abort(404, 'This campaign has no HTML content to preview.');
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "default-src 'self' 'unsafe-inline' 'unsafe-eval' https: data:; img-src * data:;",
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Campaign $campaign)
    {
        $this->authorizeOwnership($campaign);
        $this->campaignService->delete($campaign);

        return redirect()
            ->route('customer.campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    public function duplicate(Campaign $campaign)
    {
        $this->authorizeOwnership($campaign);

        $customer = auth('customer')->user();
        $customer->enforceGroupLimit('campaigns.limits.max_campaigns', $customer->campaigns()->count(), 'Campaign limit reached.');

        $campaign->loadMissing('variants');

        $copy = DB::transaction(function () use ($campaign) {
            $clone = $campaign->replicate();

            $clone->name = Str::limit((string) ($campaign->name ?? 'Campaign').' (Copy)', 255, '');
            $clone->status = 'draft';
            $clone->failure_reason = null;
            $clone->scheduled_at = null;
            $clone->send_at = null;
            $clone->started_at = null;
            $clone->finished_at = null;

            $clone->total_recipients = 0;
            $clone->sent_count = 0;
            $clone->delivered_count = 0;
            $clone->opened_count = 0;
            $clone->clicked_count = 0;
            $clone->failed_count = 0;
            $clone->bounced_count = 0;
            $clone->unsubscribed_count = 0;
            $clone->complained_count = 0;
            $clone->replied_count = 0;
            $clone->open_rate = 0;
            $clone->click_rate = 0;
            $clone->bounce_rate = 0;

            $clone->save();

            if ($campaign->relationLoaded('variants') && $campaign->variants->isNotEmpty()) {
                foreach ($campaign->variants as $variant) {
                    $clone->variants()->create([
                        'name' => $variant->name,
                        'subject' => $variant->subject,
                        'html_content' => $variant->html_content,
                        'plain_text_content' => $variant->plain_text_content,
                        'split_percentage' => $variant->split_percentage,
                        'total_recipients' => 0,
                        'sent_count' => 0,
                        'delivered_count' => 0,
                        'opened_count' => 0,
                        'clicked_count' => 0,
                        'bounced_count' => 0,
                        'unsubscribed_count' => 0,
                        'open_rate' => 0,
                        'click_rate' => 0,
                        'bounce_rate' => 0,
                        'is_winner' => false,
                        'sent_at' => null,
                    ]);
                }
            }

            return $clone;
        });

        return redirect()
            ->route('customer.campaigns.edit', $copy)
            ->with('success', 'Campaign duplicated successfully.');
    }

    /**
     * Start a campaign (queue-based).
     */
    public function start(Request $request, Campaign $campaign)
    {
        // Check if campaign belongs to the authenticated customer
        if ((int) $campaign->customer_id !== (int) auth('customer')->id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($campaign->type === 'recurring') {
            if (! $campaign->scheduled_at || $campaign->scheduled_at->isPast()) {
                $campaign->update([
                    'status' => 'scheduled',
                    'scheduled_at' => now(),
                ]);
            } else {
                $campaign->update([
                    'status' => 'scheduled',
                ]);
            }

            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('success', 'Recurring campaign has been scheduled and will run automatically.');
        }

        // Validate campaign can be started
        if (! $campaign->canStart()) {
            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'Campaign cannot be started. Only draft or scheduled campaigns can be started.');
        }

        try {
            $this->campaignService->ensureCanRun($campaign);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', $e->getMessage());
        }

        // Validate campaign is ready to send
        if (! $campaign->list_id) {
            $campaign->update([
                'status' => 'failed',
                'failure_reason' => 'Campaign must have an email list selected.',
            ]);

            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'Campaign must have an email list selected.');
        }

        if (! $campaign->html_content && ! $campaign->plain_text_content) {
            $campaign->update([
                'status' => 'failed',
                'failure_reason' => 'Campaign must have content (HTML or plain text).',
            ]);

            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'Campaign must have content (HTML or plain text).');
        }

        // Check if email list has confirmed subscribers
        if ($campaign->emailList && $campaign->emailList->subscribers()->where('status', 'confirmed')->count() === 0) {
            $campaign->update([
                'status' => 'failed',
                'failure_reason' => 'Email list has no confirmed subscribers. Please add subscribers to the list first.',
            ]);

            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'Email list has no confirmed subscribers. Please add subscribers to the list first.');
        }

        try {
            $campaign->loadMissing(['customer', 'deliveryServer', 'emailList']);
            $deliverabilityResult = $this->getCampaignStartDeliverabilityResult($campaign);
        } catch (\Throwable $e) {
            Log::warning('Failed to evaluate campaign deliverability before start', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
            $deliverabilityResult = null;
        }

        $deliverabilityPercent = (int) data_get($deliverabilityResult, 'score_percent', data_get($deliverabilityResult, 'deliverability_score', 0));
        $requiresConfirmation = $deliverabilityResult && $deliverabilityPercent < 40;

        if ($requiresConfirmation && ! $request->boolean('confirm_low_deliverability')) {
            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('deliverability_warning', [
                    'score_percent' => $deliverabilityPercent,
                    'assessment' => (string) data_get($deliverabilityResult, 'assessment', 'Poor'),
                    'message' => 'This campaign deliverability score is less than 40%. Would you like to continue anyway?',
                ]);
        }

        $queueConnection = config('queue.default', 'sync');
        if ($queueConnection === 'sync') {
            Log::warning(
                "Campaign {$campaign->id} started with sync queue. ".
                "Jobs will run synchronously. Consider using 'database' or 'redis' queue connection."
            );
        }

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture() && $queueConnection !== 'sync') {
            $campaign->update([
                'status' => 'scheduled',
            ]);

            StartCampaignJob::dispatch($campaign)
                ->delay($campaign->scheduled_at)
                ->onQueue('campaigns');

            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('success', 'Campaign has been scheduled and will start automatically at the selected time.');
        }

        try {
            $campaign->update([
                'status' => 'queued',
            ]);

            // Dispatch start job to queue (non-blocking)
            // IMPORTANT: This will only work if QUEUE_CONNECTION is NOT 'sync'
            // If using 'sync', jobs run immediately and synchronously
            StartCampaignJob::dispatch($campaign)
                ->onQueue('campaigns');

            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('success', 'Campaign has been queued to start. '.
                    ($queueConnection === 'sync'
                        ? 'Note: Queue is set to sync - emails will send synchronously. '.
                          'For background processing, change QUEUE_CONNECTION to "database" and run: php artisan queue:work'
                        : 'It will begin sending shortly in the background.'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to dispatch StartCampaignJob for campaign {$campaign->id}: ".$e->getMessage());

            $campaign->update([
                'status' => 'failed',
                'failure_reason' => 'Failed to queue campaign: '.$e->getMessage(),
            ]);

            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'Failed to start campaign: '.$e->getMessage());
        }
    }

    /**
     * Pause a running campaign.
     */
    public function pause(Campaign $campaign)
    {
        // Check if campaign belongs to the authenticated customer
        if ((int) $campaign->customer_id !== (int) auth('customer')->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (! $campaign->canPause()) {
            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'Campaign cannot be paused. Only running campaigns can be paused.');
        }

        DB::transaction(function () use ($campaign) {
            $oldStatus = $campaign->status;
            $settings = is_array($campaign->settings) ? $campaign->settings : [];
            unset($settings['auto_resume_at'], $settings['auto_resume_reason']);
            $campaign->update([
                'status' => 'paused',
                'settings' => $settings,
            ]);

            if ($campaign->customer) {
                $campaign->customer->notify(
                    new CampaignStatusUpdatedNotification($campaign, $oldStatus, 'paused')
                );
            }
        });

        return redirect()
            ->route('customer.campaigns.show', $campaign)
            ->with('success', 'Campaign has been paused. Jobs will stop processing automatically.');
    }

    /**
     * Stop a running or paused campaign.
     */
    public function stop(Campaign $campaign)
    {
        // Check if campaign belongs to the authenticated customer
        if ((int) $campaign->customer_id !== (int) auth('customer')->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (! $campaign->canStop()) {
            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'Campaign cannot be stopped. Only running or paused campaigns can be stopped.');
        }

        DB::transaction(function () use ($campaign) {
            $oldStatus = $campaign->status;
            $settings = is_array($campaign->settings) ? $campaign->settings : [];
            unset($settings['auto_resume_at'], $settings['auto_resume_reason']);

            $campaign->update([
                'status' => 'stopped',
                'finished_at' => now(),
                'settings' => $settings,
            ]);

            if ($campaign->customer) {
                $campaign->customer->notify(
                    new CampaignStatusUpdatedNotification($campaign, $oldStatus, 'stopped')
                );
            }
        });

        return redirect()
            ->route('customer.campaigns.show', $campaign)
            ->with('success', 'Campaign has been stopped. Pending jobs will exit automatically.');
    }

    /**
     * Resume a paused campaign.
     */
    public function resume(Campaign $campaign)
    {
        // Check if campaign belongs to the authenticated customer
        if ((int) $campaign->customer_id !== (int) auth('customer')->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (! $campaign->canResume()) {
            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'Campaign cannot be resumed. Only paused campaigns can be resumed.');
        }

        // Sync stats first to ensure accuracy
        $campaign->syncStats();

        // Get pending recipients (also include failed recipients that can be retried)
        $resumeChunkSize = max(1, (int) app(SendingSpeedResolver::class)->chunkSize());
        $pendingRecipients = $campaign->recipients()
            ->whereIn('status', ['pending', 'failed'])
            ->pluck('id')
            ->chunk($resumeChunkSize);

        if ($pendingRecipients->isEmpty()) {
            // Check if there are any recipients at all
            $totalRecipients = $campaign->recipients()->count();
            if ($totalRecipients === 0) {
                return redirect()
                    ->route('customer.campaigns.show', $campaign)
                    ->with('error', 'No recipients found for this campaign.');
            }

            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'No pending or failed recipients to resume sending. All recipients have been processed.');
        }

        // Reset failed recipients back to pending so they can be retried
        $campaign->recipients()
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'failed_at' => null,
                'failure_reason' => null,
            ]);

        DB::transaction(function () use ($campaign) {
            $oldStatus = $campaign->status;
            $settings = is_array($campaign->settings) ? $campaign->settings : [];
            unset($settings['auto_resume_at'], $settings['auto_resume_reason']);
            $campaign->update([
                'status' => 'running',
                'settings' => $settings,
            ]);

            if ($campaign->customer) {
                $campaign->customer->notify(
                    new CampaignStatusUpdatedNotification($campaign, $oldStatus, 'running')
                );
            }
        });

        // Dispatch remaining chunks
        foreach ($pendingRecipients as $chunk) {
            \App\Jobs\SendCampaignChunkJob::dispatch($campaign, $chunk->toArray())
                ->onQueue('campaigns');
        }

        return redirect()
            ->route('customer.campaigns.show', $campaign)
            ->with('success', 'Campaign has been resumed. Remaining emails will be sent.');
    }

    /**
     * Rerun a failed campaign.
     */
    public function rerun(Campaign $campaign)
    {
        // Check if campaign belongs to the authenticated customer
        if ((int) $campaign->customer_id !== (int) auth('customer')->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Allow rerun for failed, completed, or stopped campaigns
        if (! ($campaign->isFailed() || $campaign->isCompleted() || $campaign->isStopped())) {
            return redirect()
                ->route('customer.campaigns.show', $campaign)
                ->with('error', 'Campaign cannot be restarted. Only failed, completed, or stopped campaigns can be started over.');
        }

        // Reset campaign status and clear failure reason
        DB::transaction(function () use ($campaign) {
            $oldStatus = $campaign->status;
            $settings = is_array($campaign->settings) ? $campaign->settings : [];
            unset($settings['auto_resume_at'], $settings['auto_resume_reason']);

            $campaign->logs()->delete();

            $campaign->recipients()->update([
                'status' => 'pending',
                'sent_at' => null,
                'opened_at' => null,
                'clicked_at' => null,
                'replied_at' => null,
                'bounced_at' => null,
                'failed_at' => null,
                'failure_reason' => null,
            ]);

            $campaign->update([
                'status' => 'draft',
                'failure_reason' => null,
                'started_at' => null,
                'finished_at' => null,
                'total_recipients' => (int) $campaign->recipients()->count(),
                'sent_count' => 0,
                'delivered_count' => 0,
                'opened_count' => 0,
                'clicked_count' => 0,
                'replied_count' => 0,
                'bounced_count' => 0,
                'complained_count' => 0,
                'failed_count' => 0,
                'unsubscribed_count' => 0,
                'settings' => $settings,
            ]);

            if ($campaign->customer) {
                $campaign->customer->notify(
                    new CampaignStatusUpdatedNotification($campaign, $oldStatus, 'draft')
                );
            }
        });

        return redirect()
            ->route('customer.campaigns.show', $campaign)
            ->with('success', 'Campaign has been reset. You can now start it again.');
    }

    /**
     * Show A/B testing page for a campaign.
     */
    public function showAbTest(Campaign $campaign)
    {
        // Check if campaign belongs to the authenticated customer
        if ((int) $campaign->customer_id !== (int) auth('customer')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $campaign->load(['emailList', 'variants']);

        return view('customer.campaigns.ab-test', compact('campaign'));
    }

    /**
     * Store A/B test variants.
     */
    public function storeAbTest(Request $request, Campaign $campaign)
    {
        // Check if campaign belongs to the authenticated customer
        if ((int) $campaign->customer_id !== (int) auth('customer')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $customer = auth('customer')->user();
        $mustAddDelivery = (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false);
        $canUseSystem = (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false);
        $deliveryServers = app(DeliveryServerService::class)
            ->getSelectableDeliveryServersForCustomer($customer, $mustAddDelivery, $canUseSystem)
            ->keyBy('id');
        $deliveryServerIds = $deliveryServers->keys()->map(fn ($id) => (int) $id)->all();

        $validated = $request->validate([
            'test_type' => ['nullable', 'in:subject,content,sender'],
            'test_group_percent' => ['nullable', 'integer', 'min:5', 'max:50'],
            'winning_metric' => ['nullable', 'in:open_rate,click_rate,conversion_rate,ctor,revenue_per_email,unsubscribe_rate,bounce_rate'],
            'duration_hours' => ['nullable', 'integer', 'in:1,2,4,8,12,24'],
            'variants' => ['required', 'array', 'min:2', 'max:5'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.subject' => ['nullable', 'string', 'max:255'],
            'variants.*.template_id' => [
                'nullable',
                'integer',
                Rule::in($this->accessibleTemplateIdsForCustomer((int) $campaign->customer_id)),
            ],
            'variants.*.from_name' => ['nullable', 'string', 'max:255'],
            'variants.*.delivery_server_id' => ['nullable', 'integer', Rule::in($deliveryServerIds)],
            'variants.*.from_email' => ['nullable', 'email', 'max:255'],
            'variants.*.html_content' => ['nullable', 'string'],
            'variants.*.plain_text_content' => ['nullable', 'string'],
            'variants.*.split_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        // Validate split percentages add up to 100
        $totalPercentage = array_sum(array_column($validated['variants'], 'split_percentage'));
        if ($totalPercentage !== 100) {
            return redirect()
                ->route('customer.campaigns.show', [$campaign, 'tab' => 'ab-testing'])
                ->with('error', 'Split percentages must add up to 100%.')
                ->withInput();
        }

        $templateIds = collect($validated['variants'])
            ->pluck('template_id')
            ->filter(fn ($id) => ! is_null($id) && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $templatesById = Template::query()
            ->whereIn('id', $templateIds)
            ->get()
            ->keyBy('id');

        $variantTemplateIds = [];
        $variantSenderData = [];

        $settings = is_array($campaign->settings) ? $campaign->settings : [];
        $settings['ab_testing'] = [
            'test_type' => (string) ($validated['test_type'] ?? 'subject'),
            'test_group_percent' => (int) ($validated['test_group_percent'] ?? 20),
            'winning_metric' => (string) ($validated['winning_metric'] ?? 'open_rate'),
            'duration_hours' => (int) ($validated['duration_hours'] ?? 4),
        ];

        $campaign->update([
            'settings' => $settings,
        ]);

        // Delete existing variants
        $campaign->variants()->delete();

        // Create new variants
        foreach ($validated['variants'] as $idx => $variantData) {
            $subject = trim((string) ($variantData['subject'] ?? ''));
            $htmlContent = (string) ($variantData['html_content'] ?? '');
            $plainTextContent = (string) ($variantData['plain_text_content'] ?? '');
            $templateId = isset($variantData['template_id']) && $variantData['template_id'] !== ''
                ? (int) $variantData['template_id']
                : null;
            $template = $templateId ? $templatesById->get($templateId) : null;
            $deliveryServerId = isset($variantData['delivery_server_id']) && $variantData['delivery_server_id'] !== ''
                ? (int) $variantData['delivery_server_id']
                : null;
            $deliveryServer = $deliveryServerId ? $deliveryServers->get($deliveryServerId) : null;
            $fromName = trim((string) ($variantData['from_name'] ?? ''));
            $fromEmailInput = trim((string) ($variantData['from_email'] ?? ''));
            $fromEmail = $deliveryServer
                ? (string) ($deliveryServer->from_email ?: $fromEmailInput)
                : $fromEmailInput;

            $variantTemplateIds[$idx] = $template?->id;
            $variantSenderData[$idx] = [
                'from_name' => $fromName !== '' ? $fromName : (string) ($campaign->from_name ?? ''),
                'delivery_server_id' => $deliveryServer?->id,
                'from_email' => $fromEmail !== '' ? $fromEmail : (string) ($campaign->from_email ?? ''),
            ];

            $campaign->variants()->create([
                'name' => $variantData['name'],
                'subject' => $subject !== '' ? $subject : $campaign->subject,
                'html_content' => $template
                    ? ((string) ($template->html_content ?? '') !== '' ? (string) $template->html_content : $campaign->html_content)
                    : (trim($htmlContent) !== '' ? $htmlContent : $campaign->html_content),
                'plain_text_content' => $template
                    ? ((string) ($template->plain_text_content ?? '') !== '' ? (string) $template->plain_text_content : $campaign->plain_text_content)
                    : (trim($plainTextContent) !== '' ? $plainTextContent : $campaign->plain_text_content),
                'split_percentage' => $variantData['split_percentage'],
            ]);
        }

        $settings['ab_testing']['variant_template_ids'] = $variantTemplateIds;
        $settings['ab_testing']['variant_sender'] = $variantSenderData;
        $campaign->update([
            'settings' => $settings,
        ]);

        return redirect()
            ->route('customer.campaigns.show', [$campaign, 'tab' => 'ab-testing'])
            ->with('success', 'A/B test variants created successfully.');
    }

    /**
     * Mark a variant as winner and send to remaining audience.
     */
    public function selectWinner(Request $request, Campaign $campaign, CampaignVariant $variant)
    {
        // Check if campaign belongs to the authenticated customer
        if ((int) $campaign->customer_id !== (int) auth('customer')->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if variant belongs to campaign
        if ($variant->campaign_id !== $campaign->id) {
            abort(404, 'Variant not found.');
        }

        // Mark all variants as not winner
        $campaign->variants()->update(['is_winner' => false]);

        // Mark selected variant as winner
        $variant->update(['is_winner' => true]);

        // Send winning variant to remaining audience if requested
        if ($request->has('send_to_remaining')) {
            $this->campaignService->sendWinningVariant($campaign, $variant);
        }

        return redirect()
            ->route('customer.campaigns.ab-test', $campaign)
            ->with('success', 'Winner selected successfully.');
    }

    /**
     * Calculate expected recipients for a campaign that hasn't started yet.
     */
    private function calculateExpectedRecipients(Campaign $campaign): int
    {
        if (! $campaign->emailList) {
            return 0;
        }

        // Keep the preview/count logic aligned with the actual send pipeline.
        $query = ListSubscriber::query()
            ->where('list_id', $campaign->emailList->id)
            ->where('status', 'confirmed')
            ->where('is_bounced', false)
            ->where('is_complained', false)
            ->whereNull('suppressed_at');

        $segmentIds = collect((array) ($campaign->segments ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($segmentIds->isNotEmpty()) {
            $query->whereHas('segments', function ($segmentQuery) use ($segmentIds) {
                $segmentQuery->whereIn('list_segments.id', $segmentIds->all());
            });
        }

        // Also check global suppression list
        $suppressedEmails = \App\Models\SuppressionList::where('customer_id', $campaign->customer_id)
            ->pluck('email')
            ->toArray();

        if (! empty($suppressedEmails)) {
            $query->whereNotIn('email', $suppressedEmails);
        }

        return $query->count();
    }

    /**
     * Preview spam score for campaign content.
     */
    public function spamPreview(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'html_content' => ['nullable', 'string'],
            'plain_text_content' => ['nullable', 'string'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email'],
            'reply_to' => ['nullable', 'email'],
            'delivery_server_id' => ['nullable', 'integer'],
            'delivery_server_type' => ['nullable', 'string'],
            'delivery_server_from_email' => ['nullable', 'email'],
            'reply_server_id' => ['nullable', 'integer'],
        ]);

        $spamService = app(\App\Services\SpamScoringService::class);

        $result = $spamService->calculateSpamScore(
            $validated['subject'] ?? '',
            $validated['html_content'] ?? '',
            $validated['plain_text_content'] ?? '',
            [
                'from_name' => $validated['from_name'] ?? '',
                'from_email' => $validated['from_email'] ?? '',
                'reply_to' => $validated['reply_to'] ?? '',
                'delivery_server_id' => $validated['delivery_server_id'] ?? null,
                'delivery_server_type' => $validated['delivery_server_type'] ?? '',
                'delivery_server_from_email' => $validated['delivery_server_from_email'] ?? '',
                'reply_server_id' => $validated['reply_server_id'] ?? null,
            ]
        );

        return response()->json([
            'result' => $result,
        ]);
    }
}

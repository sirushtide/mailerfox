<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Customer;
use App\Models\DeliveryServer;
use App\Models\EmailList;
use App\Models\ListSegment;
use App\Models\ListSubscriber;
use App\Models\Template;
use App\Services\AutomationTriggerService;
use App\Services\DeliveryServerService;
use App\Services\PersonalizationService;
use App\Services\ZeptoMailApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutomationController extends Controller
{
    protected function authorizeOwnership(Automation $automation): Automation
    {
        $customerId = auth('customer')->id();

        if (!$customerId || (int) $automation->customer_id !== (int) $customerId) {
            abort(404);
        }

        return $automation;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        $query = Automation::query()
            ->where('customer_id', auth('customer')->id());

        if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
            $search = trim($filters['search']);
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['status']) && is_string($filters['status']) && trim($filters['status']) !== '') {
            $query->where('status', trim($filters['status']));
        }

        $query
            ->withCount([
                'runs as runs_total',
                'runs as runs_active' => function ($q) {
                    $q->where('status', 'active');
                },
                'runs as runs_completed' => function ($q) {
                    $q->where('status', 'completed');
                },
                'runs as runs_stopped' => function ($q) {
                    $q->where('status', 'stopped');
                },
            ])
            ->addSelect([
                'last_triggered_at' => AutomationRun::query()
                    ->selectRaw('MAX(triggered_at)')
                    ->whereColumn('automation_id', 'automations.id'),
                'next_scheduled_for' => AutomationRun::query()
                    ->selectRaw('MIN(next_scheduled_for)')
                    ->whereColumn('automation_id', 'automations.id')
                    ->where('status', 'active')
                    ->whereNotNull('next_scheduled_for'),
            ]);

        $automations = $query->latest()->paginate(15);

        return view('customer.automations.index', compact('automations', 'filters'));
    }

    public function create()
    {
        return view('customer.automations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $customer = auth('customer')->user();
        $customer->enforceGroupLimit('automations.max_automations', $customer->automations()->count(), 'Automation limit reached.');

        $automation = Automation::create([
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'status' => 'draft',
            'graph' => [
                'nodes' => [
                    [
                        'id' => 'trigger_1',
                        'type' => 'trigger',
                        'label' => 'Trigger',
                        'x' => 40,
                        'y' => 40,
                        'settings' => [
                            'list_id' => null,
                            'trigger' => '',
                        ],
                    ],
                ],
                'edges' => [],
            ],
        ]);

        return redirect()
            ->route('customer.automations.edit', $automation)
            ->with('success', 'Automation created successfully.');
    }

    public function show(Automation $automation)
    {
        $this->authorizeOwnership($automation);

        return redirect()->route('customer.automations.edit', $automation);
    }

    public function edit(Automation $automation)
    {
        $this->authorizeOwnership($automation);

        $customer = auth('customer')->user();

        $emailLists = EmailList::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $mustAddDelivery = (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false);
        $canUseSystem = (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false);

        $selectableDeliveryServerIds = app(DeliveryServerService::class)
            ->getSelectableDeliveryServerIdsForCustomer($customer, $mustAddDelivery, $canUseSystem);

        $deliveryServers = app(DeliveryServerService::class)->getSelectableDeliveryServersForCustomer(
            $customer,
            $mustAddDelivery,
            $canUseSystem
        );

        $templates = Template::query()
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                    ->orWhere(function ($subQ) {
                        $subQ->where('is_public', true)
                            ->where('is_system', false);
                    });
            })
            ->whereIn('type', ['email', 'autoresponder', 'campaign'])
            ->orderBy('name')
            ->get();

        $campaigns = Campaign::query()
            ->where('customer_id', $customer->id)
            ->orderBy('name')
            ->get();

        $segments = ListSegment::query()
            ->whereHas('emailList', fn ($q) => $q->where('customer_id', $customer->id))
            ->orderBy('name')
            ->get();

        return view('customer.automations.edit', compact('automation', 'emailLists', 'templates', 'deliveryServers', 'campaigns', 'segments'));
    }

    protected function validateGraph(array $graph): array
    {
        $nodes = isset($graph['nodes']) && is_array($graph['nodes']) ? $graph['nodes'] : [];
        $edges = isset($graph['edges']) && is_array($graph['edges']) ? $graph['edges'] : [];

        if (empty($nodes)) {
            return ['graph_json' => 'Automation must contain at least one node.'];
        }

        $nodeIds = [];
        foreach ($nodes as $n) {
            if (!is_array($n)) {
                return ['graph_json' => 'Automation nodes are invalid.'];
            }
            $id = isset($n['id']) ? (string) $n['id'] : '';
            if ($id === '') {
                return ['graph_json' => 'Automation node is missing an id.'];
            }
            $nodeIds[$id] = true;
        }

        if (!isset($nodeIds['trigger_1'])) {
            return ['graph_json' => 'Automation must include a trigger node.'];
        }

        foreach ($edges as $e) {
            if (!is_array($e)) {
                return ['graph_json' => 'Automation edges are invalid.'];
            }
            $from = isset($e['from']) ? (string) $e['from'] : '';
            $to = isset($e['to']) ? (string) $e['to'] : '';
            if ($from === '' || $to === '' || !isset($nodeIds[$from]) || !isset($nodeIds[$to])) {
                return ['graph_json' => 'Automation contains an invalid connection.'];
            }
        }

        $customer = auth('customer')->user();

        $allowedTriggers = [
            'subscriber_added',
            'subscriber_confirmed',
            'subscriber_unsubscribed',
            'webhook_received',
            'wp_user_registered',
            'wp_user_updated',
            'woo_customer_created',
            'woo_order_created',
            'woo_order_paid',
            'woo_order_completed',
            'woo_order_refunded',
            'woo_order_cancelled',
            'woo_abandoned_checkout',
            'campaign_opened',
            'campaign_clicked',
            'campaign_replied',
            'campaign_not_opened',
            'campaign_not_replied',
            'campaign_opened_not_clicked',
            'subscriber_birthday',
            'subscriber_anniversary',
            'scheduled',
        ];
        $allowedNodeTypes = [
            'trigger',
            'email',
            'delay',
            'webhook',
            'condition',
            'run_campaign',
            'move_subscribers',
            'copy_subscribers',
            'add_tag',
            'remove_tag',
            'update_field',
            'goal',
            'ab_split',
            'wait_until',
        ];
        $allowedDelayUnits = ['minutes', 'hours', 'days', 'weeks'];
        $allowedWebhookMethods = ['GET', 'POST', 'PUT'];

        $mustAddDelivery = (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false);
        $canUseSystem = (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false);

        foreach ($nodes as $node) {
            $type = isset($node['type']) ? (string) $node['type'] : '';
            $settings = (isset($node['settings']) && is_array($node['settings'])) ? $node['settings'] : [];
            $nodeLabel = isset($node['label']) ? (string) $node['label'] : 'Node';

            if ($type === '' || !in_array($type, $allowedNodeTypes, true)) {
                return ['graph_json' => $nodeLabel . ': Node type is invalid.'];
            }

            if ($type === 'trigger') {
                $trigger = isset($settings['trigger']) ? (string) $settings['trigger'] : '';
                $listId = isset($settings['list_id']) ? (int) $settings['list_id'] : 0;
                $campaignId = isset($settings['campaign_id']) ? (int) $settings['campaign_id'] : 0;
                $windowValue = isset($settings['window_value']) ? (int) $settings['window_value'] : 0;
                $windowUnit = isset($settings['window_unit']) ? (string) $settings['window_unit'] : '';

                if ($trigger === '' || !in_array($trigger, $allowedTriggers, true)) {
                    return ['graph_json' => $nodeLabel . ': Trigger event is required.'];
                }

                if (str_starts_with($trigger, 'subscriber_')) {
                    if ($listId <= 0 || !EmailList::query()->where('id', $listId)->where('customer_id', $customer->id)->exists()) {
                        return ['graph_json' => $nodeLabel . ': Email list is required.'];
                    }
                }

                if (str_starts_with($trigger, 'wp_') || str_starts_with($trigger, 'woo_')) {
                    if ($listId > 0 && !EmailList::query()->where('id', $listId)->where('customer_id', $customer->id)->exists()) {
                        return ['graph_json' => $nodeLabel . ': Email list is invalid.'];
                    }
                }

                if ($trigger === 'webhook_received') {
                    $webhookToken = isset($settings['webhook_token']) ? trim((string) $settings['webhook_token']) : '';
                    if ($webhookToken === '') {
                        return ['graph_json' => $nodeLabel . ': Webhook token is required.'];
                    }

                    if ($listId > 0 && !EmailList::query()->where('id', $listId)->where('customer_id', $customer->id)->exists()) {
                        return ['graph_json' => $nodeLabel . ': Email list is invalid.'];
                    }
                }

                if (str_starts_with($trigger, 'campaign_')) {
                    if ($campaignId <= 0 || !Campaign::query()->where('id', $campaignId)->where('customer_id', $customer->id)->exists()) {
                        return ['graph_json' => $nodeLabel . ': Campaign is required.'];
                    }

                    $negativeTriggers = ['campaign_not_opened', 'campaign_not_replied', 'campaign_opened_not_clicked'];
                    if (in_array($trigger, $negativeTriggers, true)) {
                        if ($windowValue <= 0) {
                            return ['graph_json' => $nodeLabel . ': Time window is required.'];
                        }
                        if ($windowUnit === '' || !in_array($windowUnit, $allowedDelayUnits, true)) {
                            return ['graph_json' => $nodeLabel . ': Time window unit is invalid.'];
                        }
                    }
                }

                if (in_array($trigger, ['subscriber_birthday', 'subscriber_anniversary'], true)) {
                    if ($listId <= 0 || !EmailList::query()->where('id', $listId)->where('customer_id', $customer->id)->exists()) {
                        return ['graph_json' => $nodeLabel . ': Email list is required.'];
                    }
                    $dateField = isset($settings['date_field']) ? trim((string) $settings['date_field']) : '';
                    if ($dateField === '') {
                        return ['graph_json' => $nodeLabel . ': Date field is required.'];
                    }
                }

                if ($trigger === 'scheduled') {
                    $scheduleMode = isset($settings['schedule_mode']) ? trim((string) $settings['schedule_mode']) : '';
                    $allowedModes = ['every_day', 'weekly', 'monthly', 'every_x_months'];
                    if (!in_array($scheduleMode, $allowedModes, true)) {
                        return ['graph_json' => $nodeLabel . ': Schedule mode is required.'];
                    }
                    $scheduleTime = isset($settings['schedule_time']) ? trim((string) $settings['schedule_time']) : '';
                    if ($scheduleTime === '' || !preg_match('/^\d{2}:\d{2}$/', $scheduleTime)) {
                        return ['graph_json' => $nodeLabel . ': Time of day is required (HH:MM).'];
                    }
                    if ($scheduleMode === 'weekly') {
                        $days = isset($settings['schedule_days']) && is_array($settings['schedule_days']) ? $settings['schedule_days'] : [];
                        if (empty($days)) {
                            return ['graph_json' => $nodeLabel . ': At least one day of week is required.'];
                        }
                    }
                    if (in_array($scheduleMode, ['monthly', 'every_x_months'], true)) {
                        $date = isset($settings['schedule_date']) ? (int) $settings['schedule_date'] : 0;
                        if ($date < 1 || $date > 31) {
                            return ['graph_json' => $nodeLabel . ': Day of month must be between 1 and 31.'];
                        }
                    }
                }
            }

            if ($type === 'delay') {
                $value = isset($settings['delay_value']) ? (int) $settings['delay_value'] : null;
                $unit = isset($settings['delay_unit']) ? (string) $settings['delay_unit'] : '';

                if ($value === null || $value < 0) {
                    return ['graph_json' => $nodeLabel . ': Delay value is required.'];
                }
                if ($unit === '' || !in_array($unit, $allowedDelayUnits, true)) {
                    return ['graph_json' => $nodeLabel . ': Delay unit is required.'];
                }
            }

            if ($type === 'email') {
                $subject = isset($settings['subject']) ? trim((string) $settings['subject']) : '';
                $templateId = isset($settings['template_id']) ? (int) $settings['template_id'] : 0;
                $deliveryServerId = isset($settings['delivery_server_id']) ? (int) $settings['delivery_server_id'] : 0;
                $fromEmail = isset($settings['from_email']) ? trim((string) $settings['from_email']) : '';
                $replyTo = isset($settings['reply_to']) ? trim((string) $settings['reply_to']) : '';

                if ($subject === '') {
                    return ['graph_json' => $nodeLabel . ': Subject is required.'];
                }

                if ($templateId <= 0 || !Template::query()->where('id', $templateId)->exists()) {
                    return ['graph_json' => $nodeLabel . ': Template is required.'];
                }

                if (!Template::query()
                    ->where('id', $templateId)
                    ->where(function ($q) use ($customer) {
                        $q->where('customer_id', $customer->id)
                            ->orWhere(function ($subQ) {
                                $subQ->where('is_public', true)
                                    ->where('is_system', false);
                            });
                    })
                    ->exists()) {
                    return ['graph_json' => $nodeLabel . ': Template is invalid.'];
                }

                if ($mustAddDelivery && $deliveryServerId <= 0) {
                    return ['graph_json' => $nodeLabel . ': Delivery server is required.'];
                }

                if ($deliveryServerId > 0) {
                    if (!in_array($deliveryServerId, $selectableDeliveryServerIds, true)) {
                        return ['graph_json' => $nodeLabel . ': Delivery server is invalid.'];
                    }
                }

                if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                    return ['graph_json' => $nodeLabel . ': From email is invalid.'];
                }

                if ($replyTo !== '' && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    return ['graph_json' => $nodeLabel . ': Reply-to email is invalid.'];
                }
            }

            if ($type === 'run_campaign') {
                $campaignId = isset($settings['campaign_id']) ? (int) $settings['campaign_id'] : 0;
                if ($campaignId <= 0 || !Campaign::query()->where('id', $campaignId)->where('customer_id', $customer->id)->exists()) {
                    return ['graph_json' => $nodeLabel . ': Campaign is required.'];
                }
            }

            if ($type === 'move_subscribers' || $type === 'copy_subscribers') {
                $targetListId = isset($settings['target_list_id']) ? (int) $settings['target_list_id'] : 0;
                if ($targetListId <= 0 || !EmailList::query()->where('id', $targetListId)->where('customer_id', $customer->id)->exists()) {
                    return ['graph_json' => $nodeLabel . ': Target email list is required.'];
                }
            }

            if ($type === 'webhook') {
                $url = isset($settings['url']) ? trim((string) $settings['url']) : '';
                $method = isset($settings['method']) ? strtoupper(trim((string) $settings['method'])) : '';

                if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                    return ['graph_json' => $nodeLabel . ': Webhook URL is required.'];
                }

                if ($method === '' || !in_array($method, $allowedWebhookMethods, true)) {
                    return ['graph_json' => $nodeLabel . ': Webhook method is invalid.'];
                }
            }

            if ($type === 'condition') {
                $allowedOperators = [
                    'equals', 'not_equals', 'contains', 'not_contains',
                    'starts_with', 'ends_with',
                    'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal',
                    'is_empty', 'is_not_empty', 'in_list',
                ];

                // New AND/OR group format
                if (isset($settings['groups']) && is_array($settings['groups'])) {
                    $groups = array_values(array_filter($settings['groups'], 'is_array'));
                    if (empty($groups)) {
                        return ['graph_json' => $nodeLabel . ': At least one condition is required.'];
                    }
                    foreach ($groups as $i => $group) {
                        $gField = isset($group['field']) ? trim((string) $group['field']) : '';
                        $gOperator = isset($group['operator']) ? trim((string) $group['operator']) : '';
                        if ($gField === '' || $gOperator === '' || !in_array($gOperator, $allowedOperators, true)) {
                            return ['graph_json' => $nodeLabel . ': Condition ' . ($i + 1) . ' is invalid.'];
                        }
                        if (!in_array($gOperator, ['is_empty', 'is_not_empty'], true)) {
                            $gValue = isset($group['value']) ? trim((string) $group['value']) : '';
                            if ($gValue === '') {
                                return ['graph_json' => $nodeLabel . ': Condition ' . ($i + 1) . ' value is required.'];
                            }
                        }
                    }
                } else {
                    // Legacy single-condition format
                    $field = isset($settings['field']) ? trim((string) $settings['field']) : '';
                    $operator = isset($settings['operator']) ? trim((string) $settings['operator']) : '';
                    $value = isset($settings['value']) ? trim((string) $settings['value']) : '';

                    if ($field === '' || $operator === '' || !in_array($operator, $allowedOperators, true)) {
                        return ['graph_json' => $nodeLabel . ': Condition settings are required.'];
                    }

                    if (!in_array($operator, ['is_empty', 'is_not_empty'], true) && $value === '') {
                        return ['graph_json' => $nodeLabel . ': Condition value is required.'];
                    }
                }
            }

            if ($type === 'add_tag' || $type === 'remove_tag') {
                $tags = isset($settings['tags']) && is_array($settings['tags']) ? $settings['tags'] : [];
                $tags = array_values(array_filter($tags, fn ($t) => is_string($t) && trim($t) !== ''));
                if (empty($tags)) {
                    return ['graph_json' => $nodeLabel . ': At least one tag is required.'];
                }
            }

            if ($type === 'update_field') {
                $field = isset($settings['field']) ? trim((string) $settings['field']) : '';
                $value = isset($settings['value']) ? trim((string) $settings['value']) : '';
                if ($field === '') {
                    return ['graph_json' => $nodeLabel . ': Field is required.'];
                }
            }

            if ($type === 'ab_split') {
                $splitA = (int) ($settings['split_a_percent'] ?? 0);
                if ($splitA < 1 || $splitA > 99) {
                    return ['graph_json' => $nodeLabel . ': Split A % must be between 1 and 99.'];
                }
            }

            if ($type === 'wait_until') {
                $mode = (string) ($settings['mode'] ?? '');
                if (!in_array($mode, ['fixed', 'field'], true)) {
                    return ['graph_json' => $nodeLabel . ': Wait Until mode is required (fixed or field).'];
                }
                if ($mode === 'fixed') {
                    $dateStr = trim((string) ($settings['target_date'] ?? ''));
                    if ($dateStr === '') {
                        return ['graph_json' => $nodeLabel . ': Target date is required.'];
                    }
                    try {
                        \Carbon\Carbon::parse($dateStr);
                    } catch (\Exception $e) {
                        return ['graph_json' => $nodeLabel . ': Invalid target date format.'];
                    }
                }
                if ($mode === 'field') {
                    $field = trim((string) ($settings['field'] ?? ''));
                    if ($field === '') {
                        return ['graph_json' => $nodeLabel . ': Field is required for field mode.'];
                    }
                }
            }
        }

        return [];
    }

    protected function validateGraphDraftStructure(array $graph): array
    {
        $nodes = isset($graph['nodes']) && is_array($graph['nodes']) ? $graph['nodes'] : [];
        $edges = isset($graph['edges']) && is_array($graph['edges']) ? $graph['edges'] : [];

        if (empty($nodes)) {
            return ['graph_json' => 'Automation must contain at least one node.'];
        }

        $allowedNodeTypes = [
            'trigger',
            'email',
            'delay',
            'webhook',
            'condition',
            'run_campaign',
            'move_subscribers',
            'copy_subscribers',
            'add_tag',
            'remove_tag',
            'update_field',
            'goal',
            'ab_split',
            'wait_until',
        ];

        $nodeIds = [];
        foreach ($nodes as $n) {
            if (!is_array($n)) {
                return ['graph_json' => 'Automation nodes are invalid.'];
            }
            $id = isset($n['id']) ? (string) $n['id'] : '';
            if ($id === '') {
                return ['graph_json' => 'Automation node is missing an id.'];
            }
            $type = isset($n['type']) ? (string) $n['type'] : '';
            $nodeLabel = isset($n['label']) ? (string) $n['label'] : 'Node';
            if ($type === '' || !in_array($type, $allowedNodeTypes, true)) {
                return ['graph_json' => $nodeLabel . ': Node type is invalid.'];
            }
            $nodeIds[$id] = true;
        }

        if (!isset($nodeIds['trigger_1'])) {
            return ['graph_json' => 'Automation must include a trigger node.'];
        }

        foreach ($edges as $e) {
            if (!is_array($e)) {
                return ['graph_json' => 'Automation edges are invalid.'];
            }
            $from = isset($e['from']) ? (string) $e['from'] : '';
            $to = isset($e['to']) ? (string) $e['to'] : '';
            if ($from === '' || $to === '' || !isset($nodeIds[$from]) || !isset($nodeIds[$to])) {
                return ['graph_json' => 'Automation contains an invalid connection.'];
            }
        }

        return [];
    }

    public function update(Request $request, Automation $automation)
    {
        $this->authorizeOwnership($automation);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,active,inactive'],
            'graph_json' => ['nullable', 'string'],
            'automation_settings' => ['nullable', 'array'],
        ]);

        $graph = null;
        $warning = null;
        $nextStatus = $validated['status'] ?? $automation->status;

        if (isset($validated['graph_json']) && is_string($validated['graph_json']) && trim($validated['graph_json']) !== '') {
            $decoded = json_decode($validated['graph_json'], true);
            if (!is_array($decoded)) {
                return back()->withErrors(['graph_json' => 'Flow data is invalid JSON.'])->withInput();
            }

            $structureErrors = $this->validateGraphDraftStructure($decoded);
            if (!empty($structureErrors)) {
                return back()->withErrors($structureErrors)->withInput();
            }

            $activationErrors = $this->validateGraph($decoded);
            if (!empty($activationErrors)) {
                $message = $activationErrors['graph_json'] ?? reset($activationErrors);
                $reason = is_string($message) ? $message : 'Missing required settings.';

                if ($nextStatus === 'active') {
                    $warning = 'Automation saved as draft (not active): ' . $reason;
                    $nextStatus = 'draft';
                } else {
                    $warning = 'Automation saved, but cannot be activated yet: ' . $reason;
                }
            }

            $graph = $decoded;
        }

        $settings = is_array($validated['automation_settings'] ?? null) ? $validated['automation_settings'] : null;

        DB::transaction(function () use ($automation, $validated, $graph, $nextStatus, $settings) {
            $automation->forceFill([
                'name' => $validated['name'],
                'status' => $nextStatus,
            ]);

            if ($graph !== null) {
                $automation->forceFill(['graph' => $graph]);
            }

            if ($settings !== null) {
                $automation->forceFill([
                    'settings' => [
                        're_entry' => in_array($settings['re_entry'] ?? '', ['never', 'always', 'after_completed'], true)
                            ? $settings['re_entry']
                            : 'always',
                        'frequency_cap_days' => max(0, (int) ($settings['frequency_cap_days'] ?? 0)),
                    ],
                ]);
            }

            $automation->save();
        });

        $redirect = redirect()
            ->route('customer.automations.edit', $automation)
            ->with('success', 'Automation saved successfully.');

        if (is_string($warning) && $warning !== '') {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function destroy(Automation $automation)
    {
        $this->authorizeOwnership($automation);

        $automation->delete();

        return redirect()
            ->route('customer.automations.index')
            ->with('success', 'Automation deleted successfully.');
    }

    public function runTest(Request $request, Automation $automation)
    {
        $this->authorizeOwnership($automation);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $graph = $automation->graph ?? [];
        $nodes = $graph['nodes'] ?? [];
        $triggerNode = collect($nodes)->first(fn ($n) => ($n['type'] ?? '') === 'trigger');
        $triggerSettings = $triggerNode['settings'] ?? [];
        $trigger = $triggerSettings['trigger'] ?? '';
        $listId = (int) ($triggerSettings['list_id'] ?? 0);
        $isCampaignTrigger = str_starts_with($trigger, 'campaign_');

        if ($listId <= 0 && $isCampaignTrigger) {
            $campaignId = (int) ($triggerSettings['campaign_id'] ?? 0);
            if ($campaignId > 0) {
                $campaign = Campaign::query()
                    ->where('id', $campaignId)
                    ->where('customer_id', auth('customer')->id())
                    ->first();
                if ($campaign) {
                    $listId = (int) $campaign->list_id;
                }
            }
        }

        $isScheduledTrigger = $trigger === 'scheduled';

        if ($listId <= 0 && $isScheduledTrigger) {
            // For scheduled triggers without a list, look for subscriber in any of the customer's lists
            $subscriber = ListSubscriber::query()
                ->whereHas('emailList', fn ($q) => $q->where('customer_id', auth('customer')->id()))
                ->where('email', $validated['email'])
                ->first();

            if (!$subscriber) {
                // Create subscriber in the customer's first available list
                $firstList = EmailList::query()
                    ->where('customer_id', auth('customer')->id())
                    ->orderBy('id')
                    ->first();

                if (!$firstList) {
                    return response()->json(['message' => 'No email list available. Create a list first or configure a list on the trigger.'], 422);
                }

                $subscriber = ListSubscriber::create([
                    'list_id' => $firstList->id,
                    'email' => $validated['email'],
                    'status' => 'subscribed',
                    'source' => 'manual_run',
                    'subscribed_at' => now(),
                ]);
            }
        } else {
            if ($listId <= 0) {
                return response()->json(['message' => 'Automation trigger does not have an email list configured.'], 422);
            }

            $list = EmailList::query()
                ->where('id', $listId)
                ->where('customer_id', auth('customer')->id())
                ->first();

            if (!$list) {
                return response()->json(['message' => 'Trigger list not found or does not belong to you.'], 422);
            }

            $subscriber = ListSubscriber::query()
                ->where('list_id', $listId)
                ->where('email', $validated['email'])
                ->first();

            if (!$subscriber) {
                if ($isCampaignTrigger) {
                    $subscriber = ListSubscriber::create([
                        'list_id' => $listId,
                        'email' => $validated['email'],
                        'status' => 'subscribed',
                        'source' => 'manual_run',
                        'subscribed_at' => now(),
                    ]);
                } else {
                    return response()->json(['message' => 'Subscriber not found in the trigger list.'], 404);
                }
            }
        }

        if ($automation->status !== 'active') {
            return response()->json(['message' => 'Automation must be active to run.'], 422);
        }

        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'subscriber_id' => $subscriber->id,
            'status' => 'active',
            'trigger_event' => $trigger ?: 'manual_test',
            'trigger_context' => ['source' => 'manual_run', 'email' => $validated['email']],
            'current_node_id' => 'trigger_1',
            'triggered_at' => now(),
            'next_scheduled_for' => now(),
            'locked_at' => now(),
        ]);

        $results = $this->executeWorkflowTest($automation, $run, $subscriber);

        return response()->json([
            'success' => true,
            'email' => $validated['email'],
            'results' => $results,
        ]);
    }

    private function executeWorkflowTest(Automation $automation, AutomationRun $run, ListSubscriber $subscriber): array
    {
        $graph = (array) ($automation->graph ?? []);
        $nodes = (array) ($graph['nodes'] ?? []);
        $edges = (array) ($graph['edges'] ?? []);

        $byId = [];
        foreach ($nodes as $n) {
            if (is_array($n) && isset($n['id'])) {
                $byId[(string) $n['id']] = $n;
            }
        }

        $results = [];
        $currentId = 'trigger_1';
        $maxSteps = 100;
        $steps = 0;

        while ($currentId && $steps < $maxSteps) {
            $steps++;
            $node = $byId[$currentId] ?? null;
            if (!$node) {
                break;
            }

            $type = (string) ($node['type'] ?? '');
            $settings = is_array($node['settings'] ?? null) ? $node['settings'] : [];
            $label = (string) ($node['label'] ?? ($type === 'trigger' ? 'Trigger' : ucfirst($type)));

            $result = $this->executeTestNode($automation, $subscriber, $type, $settings);
            $result['node_id'] = $currentId;
            $result['type'] = $type;
            $result['label'] = $label;
            $results[] = $result;

            if ($result['status'] === 'failed') {
                $run->update(['status' => 'failed', 'current_node_id' => $currentId, 'locked_at' => null]);
                break;
            }

            $nextId = $this->firstOutgoing($edges, $currentId, $result['branch'] ?? null);
            if (!$nextId) {
                $run->update(['status' => 'completed', 'current_node_id' => null, 'next_scheduled_for' => null, 'locked_at' => null]);
                break;
            }

            $currentId = $nextId;
        }

        if ($steps >= $maxSteps) {
            $run->update(['status' => 'failed', 'locked_at' => null]);
            $results[] = [
                'node_id' => '',
                'type' => 'system',
                'label' => 'System',
                'status' => 'failed',
                'output' => 'Max step limit reached. Possible infinite loop.',
            ];
        }

        return $results;
    }

    private function executeTestNode(Automation $automation, ListSubscriber $subscriber, string $type, array $settings): array
    {
        $customer = Customer::query()->find($automation->customer_id);

        if ($type === 'trigger') {
            return [
                'status' => 'completed',
                'output' => 'Trigger evaluated',
            ];
        }

        if ($type === 'delay') {
            $value = (int) ($settings['delay_value'] ?? 0);
            $unit = (string) ($settings['delay_unit'] ?? 'hours');
            return [
                'status' => 'skipped',
                'output' => "Delay skipped in test mode ({$value} {$unit})",
            ];
        }

        if ($type === 'wait_until') {
            return [
                'status' => 'skipped',
                'output' => 'Wait until skipped in test mode',
            ];
        }

        if ($type === 'condition') {
            $result = $this->evaluateCondition($subscriber, $settings, []);
            $branch = $result ? 'yes' : 'no';
            return [
                'status' => 'completed',
                'output' => "Condition evaluated: {$branch}",
                'branch' => $branch,
            ];
        }

        if ($type === 'email') {
            try {
                $this->sendAutomationEmail($automation->customer_id, $subscriber, $settings);
                $subject = trim((string) ($settings['subject'] ?? ''));
                return [
                    'status' => 'completed',
                    'output' => "Email sent to {$subscriber->email}" . ($subject ? " (Subject: {$subject})" : ''),
                ];
            } catch (\Throwable $e) {
                return [
                    'status' => 'failed',
                    'output' => 'Failed to send email',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($type === 'webhook') {
            try {
                $url = trim((string) ($settings['url'] ?? ''));
                $method = strtoupper(trim((string) ($settings['method'] ?? 'POST')));
                if ($url === '') {
                    return [
                        'status' => 'completed',
                        'output' => 'Webhook skipped (no URL)',
                    ];
                }
                $payload = [
                    'subscriber' => [
                        'id' => $subscriber->id,
                        'email' => $subscriber->email,
                        'first_name' => $subscriber->first_name,
                        'last_name' => $subscriber->last_name,
                        'list_id' => $subscriber->list_id,
                        'status' => $subscriber->status,
                        'custom_fields' => $subscriber->custom_fields,
                        'tags' => $subscriber->tags,
                    ],
                    'context' => ['source' => 'manual_run'],
                ];
                $response = Http::timeout(10)->send($method, $url, ['json' => $payload]);
                return [
                    'status' => 'completed',
                    'output' => "Webhook {$method} to {$url} returned HTTP {$response->status()}",
                ];
            } catch (\Throwable $e) {
                return [
                    'status' => 'failed',
                    'output' => 'Webhook failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($type === 'run_campaign') {
            try {
                $campaignId = (int) ($settings['campaign_id'] ?? 0);
                if ($campaignId <= 0) {
                    return [
                        'status' => 'completed',
                        'output' => 'Campaign skipped (no campaign selected)',
                    ];
                }
                $campaign = Campaign::query()->find($campaignId);
                if (!$campaign) {
                    return [
                        'status' => 'failed',
                        'output' => 'Campaign not found',
                    ];
                }
                CampaignRecipient::query()->create([
                    'campaign_id' => $campaign->id,
                    'email' => $subscriber->email,
                    'first_name' => $subscriber->first_name,
                    'last_name' => $subscriber->last_name,
                    'status' => 'pending',
                ]);
                return [
                    'status' => 'completed',
                    'output' => "Campaign '{$campaign->name}' dispatched to {$subscriber->email}",
                ];
            } catch (\Throwable $e) {
                return [
                    'status' => 'failed',
                    'output' => 'Campaign dispatch failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($type === 'move_subscribers' || $type === 'copy_subscribers') {
            try {
                $targetListId = (int) ($settings['target_list_id'] ?? 0);
                if ($targetListId <= 0) {
                    return [
                        'status' => 'completed',
                        'output' => 'Skipped (no target list)',
                    ];
                }
                $targetList = EmailList::query()->find($targetListId);
                if (!$targetList) {
                    return [
                        'status' => 'failed',
                        'output' => 'Target list not found',
                    ];
                }
                $service = app(\App\Services\ListSubscriberService::class);
                $service->create($targetList, [
                    'email' => $subscriber->email,
                    'first_name' => $subscriber->first_name,
                    'last_name' => $subscriber->last_name,
                    'status' => 'confirmed',
                    'source' => 'automation_test',
                    'custom_fields' => $subscriber->custom_fields ?? [],
                    'tags' => $subscriber->tags ?? [],
                ]);
                if ($type === 'move_subscribers') {
                    $service->unsubscribe($subscriber);
                    return [
                        'status' => 'completed',
                        'output' => "Moved subscriber to '{$targetList->name}'",
                    ];
                }
                return [
                    'status' => 'completed',
                    'output' => "Copied subscriber to '{$targetList->name}'",
                ];
            } catch (\Throwable $e) {
                return [
                    'status' => 'failed',
                    'output' => 'Transfer failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($type === 'add_tag' || $type === 'remove_tag') {
            try {
                $tags = isset($settings['tags']) && is_array($settings['tags']) ? $settings['tags'] : [];
                $tags = array_values(array_filter($tags, fn ($t) => is_string($t) && trim($t) !== ''));
                if (empty($tags)) {
                    return [
                        'status' => 'completed',
                        'output' => 'No tags to ' . ($type === 'add_tag' ? 'add' : 'remove'),
                    ];
                }
                $current = is_array($subscriber->tags) ? $subscriber->tags : [];
                if ($type === 'add_tag') {
                    $subscriber->update(['tags' => array_values(array_unique(array_merge($current, $tags)))]);
                    return [
                        'status' => 'completed',
                        'output' => 'Added tags: ' . implode(', ', $tags),
                    ];
                }
                $subscriber->update(['tags' => array_values(array_diff($current, $tags))]);
                return [
                    'status' => 'completed',
                    'output' => 'Removed tags: ' . implode(', ', $tags),
                ];
            } catch (\Throwable $e) {
                return [
                    'status' => 'failed',
                    'output' => 'Tag update failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($type === 'add_segment' || $type === 'remove_segment') {
            try {
                $segmentId = (int) ($settings['segment_id'] ?? 0);
                if ($segmentId <= 0) {
                    return [
                        'status' => 'completed',
                        'output' => 'Skipped (no segment selected)',
                    ];
                }
                $segment = ListSegment::query()->find($segmentId);
                if (!$segment) {
                    return [
                        'status' => 'failed',
                        'output' => 'Segment not found',
                    ];
                }
                $exists = DB::table('list_segment_subscriber')
                    ->where('segment_id', $segmentId)
                    ->where('subscriber_id', $subscriber->id)
                    ->exists();
                if ($type === 'add_segment') {
                    if (!$exists) {
                        DB::table('list_segment_subscriber')->insert([
                            'segment_id' => $segmentId,
                            'subscriber_id' => $subscriber->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    return [
                        'status' => 'completed',
                        'output' => "Added to segment '{$segment->name}'",
                    ];
                }
                if ($exists) {
                    DB::table('list_segment_subscriber')
                        ->where('segment_id', $segmentId)
                        ->where('subscriber_id', $subscriber->id)
                        ->delete();
                }
                return [
                    'status' => 'completed',
                    'output' => "Removed from segment '{$segment->name}'",
                ];
            } catch (\Throwable $e) {
                return [
                    'status' => 'failed',
                    'output' => 'Segment update failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($type === 'update_field') {
            try {
                $field = trim((string) ($settings['field'] ?? ''));
                $value = trim((string) ($settings['value'] ?? ''));
                if ($field === '') {
                    return [
                        'status' => 'completed',
                        'output' => 'No field selected',
                    ];
                }
                $allowedBuiltIn = ['first_name', 'last_name', 'status'];
                if (in_array($field, $allowedBuiltIn, true)) {
                    $subscriber->update([$field => $value]);
                } elseif (str_starts_with($field, 'custom_fields.')) {
                    $key = substr($field, strlen('custom_fields.'));
                    $custom = is_array($subscriber->custom_fields) ? $subscriber->custom_fields : [];
                    $custom[$key] = $value;
                    $subscriber->update(['custom_fields' => $custom]);
                } elseif ($field === 'tags') {
                    $tags = array_values(array_filter(array_map('trim', explode(',', $value))));
                    $subscriber->update(['tags' => $tags]);
                }
                return [
                    'status' => 'completed',
                    'output' => "Updated {$field} to '{$value}'",
                ];
            } catch (\Throwable $e) {
                return [
                    'status' => 'failed',
                    'output' => 'Field update failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($type === 'goal') {
            return [
                'status' => 'completed',
                'output' => 'Goal reached',
            ];
        }

        if ($type === 'ab_split') {
            $splitA = (int) ($settings['split_a_percent'] ?? 50);
            $bucket = abs(crc32((string) $subscriber->email)) % 100;
            $branch = $bucket < $splitA ? 'a' : 'b';
            return [
                'status' => 'completed',
                'output' => "AB split: bucket {$bucket}, branch {$branch}",
                'branch' => $branch,
            ];
        }

        return [
            'status' => 'completed',
            'output' => 'Node passed through',
        ];
    }

    private function firstOutgoing(array $edges, string $from, ?string $branch): ?string
    {
        foreach ($edges as $e) {
            if (!is_array($e)) {
                continue;
            }
            if ((string) ($e['from'] ?? '') !== $from) {
                continue;
            }
            $b = $e['branch'] ?? null;
            $b = is_string($b) && $b !== '' ? $b : null;
            if ($branch === null) {
                if ($b === null) {
                    return (string) ($e['to'] ?? '');
                }
                continue;
            }
            if ($b === $branch) {
                return (string) ($e['to'] ?? '');
            }
        }
        return null;
    }

    private function evaluateCondition(ListSubscriber $subscriber, array $settings, array $context): bool
    {
        if (isset($settings['groups']) && is_array($settings['groups'])) {
            $logic = strtolower(trim((string) ($settings['logic'] ?? 'and')));
            $groups = array_values(array_filter($settings['groups'], 'is_array'));
            if (empty($groups)) {
                return false;
            }
            $results = array_map(
                fn (array $group) => $this->evaluateSingleCondition($subscriber, $group, $context),
                $groups
            );
            if ($logic === 'or') {
                return in_array(true, $results, true);
            }
            return !in_array(false, $results, true);
        }
        return $this->evaluateSingleCondition($subscriber, $settings, $context);
    }

    private function evaluateSingleCondition(ListSubscriber $subscriber, array $settings, array $context): bool
    {
        $field = trim((string) ($settings['field'] ?? ''));
        $operator = trim((string) ($settings['operator'] ?? 'equals'));
        $value = trim((string) ($settings['value'] ?? ''));
        if ($field === '') {
            return false;
        }
        $actual = null;
        if (str_starts_with($field, 'custom_fields.')) {
            $key = substr($field, strlen('custom_fields.'));
            $custom = is_array($subscriber->custom_fields) ? $subscriber->custom_fields : [];
            $actual = $custom[$key] ?? null;
        } elseif (str_starts_with($field, 'payload.') || str_starts_with($field, 'context.payload.')) {
            $path = str_starts_with($field, 'payload.')
                ? substr($field, strlen('payload.'))
                : substr($field, strlen('context.payload.'));
            $current = $context['payload'] ?? [];
            foreach (explode('.', $path) as $segment) {
                $segment = trim((string) $segment);
                if ($segment === '' || !is_array($current) || !array_key_exists($segment, $current)) {
                    $current = null;
                    break;
                }
                $current = $current[$segment];
            }
            $actual = $current;
        } else {
            $actual = $subscriber->{$field} ?? null;
        }
        if (is_scalar($actual)) {
            $actual = (string) $actual;
        } elseif (is_array($actual)) {
            $actual = json_encode($actual) ?: '';
        } else {
            $actual = '';
        }
        return match ($operator) {
            'equals' => strcasecmp($actual, $value) === 0,
            'not_equals' => strcasecmp($actual, $value) !== 0,
            'contains' => stripos($actual, $value) !== false,
            'not_contains' => stripos($actual, $value) === false,
            'starts_with' => strcasecmp(substr($actual, 0, strlen($value)), $value) === 0,
            'ends_with' => strcasecmp(substr($actual, -strlen($value)), $value) === 0,
            'greater_than', '>' => is_numeric($actual) && is_numeric($value) && (float) $actual > (float) $value,
            'less_than', '<' => is_numeric($actual) && is_numeric($value) && (float) $actual < (float) $value,
            'greater_or_equal', '>=' => is_numeric($actual) && is_numeric($value) && (float) $actual >= (float) $value,
            'less_or_equal', '<=' => is_numeric($actual) && is_numeric($value) && (float) $actual <= (float) $value,
            'is_empty' => $actual === '' || $actual === null,
            'is_not_empty' => $actual !== '' && $actual !== null,
            'in_list' => in_array(strtolower($actual), array_map('strtolower', array_map('trim', explode(',', $value))), true),
            default => false,
        };
    }

    private function sendAutomationEmail(int $customerId, ListSubscriber $subscriber, array $settings): void
    {
        $subject = trim((string) ($settings['subject'] ?? ''));
        $templateId = (int) ($settings['template_id'] ?? 0);
        $deliveryServerId = (int) ($settings['delivery_server_id'] ?? 0);
        $customer = Customer::query()->find($customerId);
        $mustAddDelivery = $customer ? (bool) $customer->groupSetting('servers.permissions.must_add_delivery_server', false) : false;
        $canUseSystem = $customer ? (bool) $customer->groupSetting('servers.permissions.can_use_system_servers', false) : false;
        $template = $templateId > 0 ? Template::query()->find($templateId) : null;
        if (!$template) {
            throw new \RuntimeException('Template not found');
        }
        $html = (string) ($template->html_content ?? '');
        $text = (string) ($template->plain_text_content ?? strip_tags($html));
        $html = app(PersonalizationService::class)->personalizeForSubscriber($html, $subscriber);
        $text = app(PersonalizationService::class)->personalizeForSubscriber($text, $subscriber);
        $subject = app(PersonalizationService::class)->personalizeForSubscriber($subject, $subscriber);
        $token = hash('sha256', $subscriber->email . $subscriber->list_id . config('app.key'));
        $unsubscribeUrl = route('public.unsubscribe', ['list' => $subscriber->list_id, 'email' => $subscriber->email, 'token' => $token]);
        if ($html !== '') {
            $footer = '<p style="font-size: 12px; color: #999; margin-top: 20px; text-align:center;"><a href="' . $unsubscribeUrl . '" style="color: #999;">Unsubscribe</a></p>';
            if (stripos($html, '</body>') !== false) {
                $html = str_ireplace('</body>', $footer . '</body>', $html);
            } else {
                $html .= $footer;
            }
        }
        $text = rtrim($text) . "\n\n---\nUnsubscribe: {$unsubscribeUrl}";
        $server = null;
        if ($customer) {
            $server = app(DeliveryServerService::class)->resolveDeliveryServerForCustomer(
                $customer,
                $deliveryServerId > 0 ? $deliveryServerId : null,
                $mustAddDelivery,
                $canUseSystem
            );
        } elseif ($deliveryServerId > 0) {
            $server = DeliveryServer::query()->with('bounceServer')->find($deliveryServerId);
        }
        if (!$server && $mustAddDelivery) {
            throw new \RuntimeException('Delivery server is required');
        }
        if ($server && $server->type === 'zeptomail-api') {
            $message = [
                'from_email' => (string) ($settings['from_email'] ?? ($server->from_email ?? config('mail.from.address'))),
                'from_name' => (string) ($settings['from_name'] ?? ($server->from_name ?? config('mail.from.name'))),
                'to_email' => (string) $subscriber->email,
                'to_name' => trim((string) (($subscriber->first_name ?? '') . ' ' . ($subscriber->last_name ?? ''))),
                'subject' => $subject,
                'htmlbody' => $html,
                'textbody' => $text,
                'client_reference' => 'automation-email-' . $subscriber->id . '-' . now()->timestamp,
            ];
            if (empty(($server->settings ?? [])['bounce_address']) && !empty($server->bounceServer?->username)) {
                $message['bounce_address'] = (string) $server->bounceServer->username;
            }
            app(ZeptoMailApiService::class)->sendRaw($server, $message);
            return;
        }
        if ($server) {
            app(DeliveryServerService::class)->configureMailFromServer($server);
        }
        Mail::raw($text, function ($message) use ($subscriber, $subject, $html, $settings, $server) {
            $fromEmail = (string) ($settings['from_email'] ?? ($server?->from_email ?? config('mail.from.address')));
            $fromName = (string) ($settings['from_name'] ?? ($server?->from_name ?? config('mail.from.name')));
            $message->to($subscriber->email)
                ->subject($subject)
                ->from($fromEmail, $fromName);
            $replyTo = trim((string) ($settings['reply_to'] ?? ''));
            if ($replyTo !== '') {
                $message->replyTo($replyTo);
            }
            if ($server && $server->bounceServer && $server->bounceServer->username) {
                $message->returnPath($server->bounceServer->username);
            }
            if ($html !== '') {
                $message->html($html);
            }
        });
    }
}

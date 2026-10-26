<?php

namespace App\Services;

use App\Jobs\CheckAutomationNegativeCampaignTriggerJob;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\ListSubscriber;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutomationTriggerService
{
    public function triggerAutomation(Automation $automation, string $event, ListSubscriber $subscriber, array $context = []): void
    {
        if ($automation->status !== 'active') {
            return;
        }

        try {
            if (!$this->automationMatchesEvent($automation, $event, $subscriber, $context)) {
                return;
            }

            $this->startRun($automation, $subscriber, $event, $context);
        } catch (\Throwable $e) {
            Log::error('Failed to trigger automation', [
                'automation_id' => $automation->id,
                'subscriber_id' => $subscriber->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function triggerSubscriberEvent(string $event, ListSubscriber $subscriber, array $context = []): void
    {
        $subscriber->loadMissing('list');

        $customerId = (int) ($subscriber->list?->customer_id ?? 0);
        if ($customerId <= 0) {
            return;
        }

        $automations = Automation::query()
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->get();

        foreach ($automations as $automation) {
            try {
                if (!$this->automationMatchesEvent($automation, $event, $subscriber, $context)) {
                    continue;
                }

                $this->startRun($automation, $subscriber, $event, $context);
            } catch (\Throwable $e) {
                Log::error('Failed to trigger automation', [
                    'automation_id' => $automation->id,
                    'subscriber_id' => $subscriber->id,
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function scheduleNegativeCampaignTriggersForRecipient(Campaign $campaign, CampaignRecipient $recipient): void
    {
        $campaign->loadMissing('customer');
        $customerId = (int) ($campaign->customer_id ?? 0);
        if ($customerId <= 0) {
            return;
        }

        $subscriber = ListSubscriber::query()
            ->where('list_id', $campaign->list_id)
            ->where('email', strtolower(trim((string) ($recipient->email ?? ''))))
            ->first();

        if (!$subscriber) {
            return;
        }

        $negative = ['campaign_not_opened', 'campaign_not_replied', 'campaign_opened_not_clicked'];

        $automations = Automation::query()
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->get();

        foreach ($automations as $automation) {
            $trigger = $this->triggerSettings($automation)['trigger'] ?? '';
            if (!in_array($trigger, $negative, true)) {
                continue;
            }

            $settings = $this->triggerSettings($automation);
            $campaignId = (int) ($settings['campaign_id'] ?? 0);
            if ($campaignId <= 0 || $campaignId !== (int) $campaign->id) {
                continue;
            }

            $windowValue = (int) ($settings['window_value'] ?? 0);
            $windowUnit = (string) ($settings['window_unit'] ?? 'hours');
            if ($windowValue <= 0) {
                continue;
            }

            $delayUntil = $this->applyDelay(now(), $windowValue, $windowUnit);

            CheckAutomationNegativeCampaignTriggerJob::dispatch(
                $automation->id,
                $subscriber->id,
                (int) $campaign->id,
                (int) $recipient->id,
                $trigger
            )
                ->delay($delayUntil)
                ->onQueue('automations');
        }
    }

    public function processDateTriggers(): void
    {
        $today = Carbon::now()->format('m-d');
        $now = Carbon::now();

        $automations = Automation::query()->where('status', 'active')->get();

        foreach ($automations as $automation) {
            $settings = $this->triggerSettings($automation);
            $trigger = (string) ($settings['trigger'] ?? '');

            if (!in_array($trigger, ['subscriber_birthday', 'subscriber_anniversary', 'scheduled'], true)) {
                continue;
            }

            $listId = (int) ($settings['list_id'] ?? 0);

            if (in_array($trigger, ['subscriber_birthday', 'subscriber_anniversary'], true)) {
                if ($listId <= 0) {
                    continue;
                }

                $dateField = (string) ($settings['date_field'] ?? '');
                if ($dateField === '') {
                    continue;
                }

                ListSubscriber::query()
                    ->where('list_id', $listId)
                    ->whereNotNull('custom_fields')
                    ->chunk(500, function ($subscribers) use ($automation, $trigger, $dateField, $today) {
                        foreach ($subscribers as $subscriber) {
                            $custom = is_array($subscriber->custom_fields) ? $subscriber->custom_fields : [];
                            $value = $custom[$dateField] ?? null;
                            if (!$value || !is_string($value)) {
                                continue;
                            }
                            try {
                                $date = Carbon::parse($value);
                                if ($date->format('m-d') !== $today) {
                                    continue;
                                }
                            } catch (\Exception $e) {
                                continue;
                            }

                            try {
                                $this->startRun($automation, $subscriber, $trigger, []);
                            } catch (\Throwable $e) {
                                Log::error('Failed to start date trigger run', [
                                    'automation_id' => $automation->id,
                                    'subscriber_id' => $subscriber->id,
                                    'trigger' => $trigger,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            }

            if ($trigger === 'scheduled') {
                $scheduleMode = (string) ($settings['schedule_mode'] ?? '');
                $allowedModes = ['every_day', 'weekly', 'monthly', 'every_x_months'];
                if (!in_array($scheduleMode, $allowedModes, true)) {
                    continue;
                }

                // Determine if today matches the schedule criteria
                $shouldFire = false;
                $dedupSince = null;

                if ($scheduleMode === 'every_day') {
                    $shouldFire = true;
                    $dedupSince = $now->copy()->startOfDay();
                } elseif ($scheduleMode === 'weekly') {
                    $days = isset($settings['schedule_days']) && is_array($settings['schedule_days']) ? $settings['schedule_days'] : [];
                    $todayShort = strtolower($now->format('D'));
                    // Map Carbon day abbreviations to our keys: mon, tue, wed, thu, fri, sat, sun
                    $dayMap = ['mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri', 'sat' => 'sat', 'sun' => 'sun'];
                    $todayKey = $dayMap[$todayShort] ?? '';
                    if ($todayKey && in_array($todayKey, $days, true)) {
                        $shouldFire = true;
                        $dedupSince = $now->copy()->startOfDay();
                    }
                } elseif ($scheduleMode === 'monthly') {
                    $scheduleDate = (int) ($settings['schedule_date'] ?? 1);
                    if ((int) $now->format('d') === $scheduleDate) {
                        $shouldFire = true;
                        $dedupSince = $now->copy()->startOfMonth();
                    }
                } elseif ($scheduleMode === 'every_x_months') {
                    $scheduleDate = (int) ($settings['schedule_date'] ?? 1);
                    $monthsInterval = (int) ($settings['schedule_months'] ?? 3);
                    if ((int) $now->format('d') === $scheduleDate) {
                        // Check if a run was already started within the last N months
                        $lastRun = AutomationRun::query()
                            ->where('automation_id', $automation->id)
                            ->where('trigger_event', $trigger)
                            ->latest('triggered_at')
                            ->first();

                        if (!$lastRun) {
                            $shouldFire = true;
                        } elseif ($lastRun->triggered_at) {
                            $monthsSince = $lastRun->triggered_at->diffInMonths($now);
                            if ($monthsSince >= $monthsInterval) {
                                $shouldFire = true;
                            }
                        }
                    }
                }

                if (!$shouldFire) {
                    continue;
                }

                // Prevent duplicate runs within the relevant period
                if ($dedupSince) {
                    $alreadyRun = AutomationRun::query()
                        ->where('automation_id', $automation->id)
                        ->where('trigger_event', $trigger)
                        ->where('triggered_at', '>=', $dedupSince)
                        ->exists();

                    if ($alreadyRun) {
                        continue;
                    }
                }

                // If no list is configured, skip subscriber-based runs.
                // The automation can still be saved and used with manual test runs.
                if ($listId <= 0) {
                    continue;
                }

                ListSubscriber::query()
                    ->where('list_id', $listId)
                    ->chunk(500, function ($subscribers) use ($automation, $trigger) {
                        foreach ($subscribers as $subscriber) {
                            try {
                                $this->startRun($automation, $subscriber, $trigger, []);
                            } catch (\Throwable $e) {
                                Log::error('Failed to start scheduled trigger run', [
                                    'automation_id' => $automation->id,
                                    'subscriber_id' => $subscriber->id,
                                    'trigger' => $trigger,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            }
        }
    }

    private function automationMatchesEvent(Automation $automation, string $event, ListSubscriber $subscriber, array $context): bool
    {
        $settings = $this->triggerSettings($automation);

        if (($settings['trigger'] ?? '') !== $event) {
            return false;
        }

        if ($event === 'webhook_received') {
            $listId = (int) ($settings['list_id'] ?? 0);
            if ($listId <= 0) {
                return true;
            }
            return $listId === (int) $subscriber->list_id;
        }

        if (str_starts_with($event, 'subscriber_')) {
            return (int) ($settings['list_id'] ?? 0) === (int) $subscriber->list_id;
        }

        if (str_starts_with($event, 'wp_') || str_starts_with($event, 'woo_')) {
            $listId = (int) ($settings['list_id'] ?? 0);
            if ($listId <= 0) {
                return true;
            }
            return $listId === (int) $subscriber->list_id;
        }

        if (str_starts_with($event, 'campaign_')) {
            $campaignId = (int) ($settings['campaign_id'] ?? 0);
            $ctxCampaignId = (int) ($context['campaign_id'] ?? 0);
            if ($campaignId <= 0 || $ctxCampaignId <= 0) {
                return false;
            }

            if ($campaignId !== $ctxCampaignId) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function triggerSettings(Automation $automation): array
    {
        $graph = (array) ($automation->graph ?? []);
        $nodes = (array) ($graph['nodes'] ?? []);

        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            if (($node['id'] ?? '') === 'trigger_1') {
                $settings = $node['settings'] ?? [];
                return is_array($settings) ? $settings : [];
            }
        }

        return [];
    }

    private function startRun(Automation $automation, ListSubscriber $subscriber, string $event, array $context): void
    {
        $settings = (array) ($automation->settings ?? []);
        $reEntry = (string) ($settings['re_entry'] ?? 'always');
        $frequencyCapDays = (int) ($settings['frequency_cap_days'] ?? 0);

        $existingQuery = AutomationRun::query()
            ->where('automation_id', $automation->id)
            ->where('subscriber_id', $subscriber->id);

        if ($reEntry === 'never') {
            if ($existingQuery->exists()) {
                return;
            }
        } elseif ($reEntry === 'after_completed') {
            if ($existingQuery->where('status', 'active')->exists()) {
                return;
            }
        }

        if ($frequencyCapDays > 0) {
            $lastRun = AutomationRun::query()
                ->where('automation_id', $automation->id)
                ->where('subscriber_id', $subscriber->id)
                ->latest('triggered_at')
                ->first();

            if ($lastRun && $lastRun->triggered_at && $lastRun->triggered_at->diffInDays(now()) < $frequencyCapDays) {
                return;
            }
        }

        DB::transaction(function () use ($automation, $subscriber, $event, $context) {
            AutomationRun::query()->create([
                'automation_id' => $automation->id,
                'subscriber_id' => $subscriber->id,
                'status' => 'active',
                'trigger_event' => $event,
                'trigger_context' => $context,
                'current_node_id' => 'trigger_1',
                'triggered_at' => now(),
                'next_scheduled_for' => now(),
                'locked_at' => null,
            ]);
        });
    }

    private function applyDelay($base, int $value, string $unit)
    {
        if ($value <= 0) {
            return $base;
        }

        return match ($unit) {
            'minutes' => $base->copy()->addMinutes($value),
            'hours' => $base->copy()->addHours($value),
            'days' => $base->copy()->addDays($value),
            'weeks' => $base->copy()->addWeeks($value),
            default => $base,
        };
    }
}

<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\DeliveryServer;
use App\Models\Setting;

/**
 * Three-layer resolution for campaign/outreach sending speed:
 * campaign override → delivery server override → global admin default.
 *
 * Values <= 0 / null / empty mean "inherit from the next layer".
 */
class SendingSpeedResolver
{
    public const TYPE_RATE_LIMITS = [
        'smtp' => 20,
        'gmail' => 20,
        'outlook' => 20,
        'amazon-ses' => 60,
        'mailgun' => 60,
        'sendgrid' => 60,
        'postmark' => 60,
        'resend' => 100,
        'zeptomail-api' => 100,
        'emailit' => 100,
    ];

    /**
     * Gap between individual sends, in milliseconds.
     */
    public function gapMs(?Campaign $campaign = null, ?DeliveryServer $deliveryServer = null): int
    {
        $campaignGap = $this->campaignSpeed($campaign)['gap_ms'] ?? null;
        if ($campaignGap !== null && $campaignGap >= 0) {
            return $campaignGap;
        }

        if ($deliveryServer !== null) {
            $serverGapSeconds = (int) ($deliveryServer->pause_after_send ?? 0);
            if ($serverGapSeconds > 0) {
                return $serverGapSeconds * 1000;
            }
        }

        $global = (int) Setting::get('campaign_send_gap_ms', 500);

        return max(0, $global);
    }

    /**
     * Max emails per minute. 0 means "no explicit limit" (type defaults still apply
     * only when no layer configured a positive value and $applyTypeDefault is true).
     */
    public function ratePerMinute(
        ?Campaign $campaign = null,
        ?DeliveryServer $deliveryServer = null,
        bool $applyTypeDefault = true
    ): int {
        $campaignRate = $this->campaignSpeed($campaign)['rate_per_minute'] ?? null;
        if ($campaignRate !== null && $campaignRate > 0) {
            return $campaignRate;
        }

        if ($deliveryServer !== null) {
            $serverRate = (int) ($deliveryServer->minute_quota ?? 0);
            if ($serverRate > 0) {
                return $serverRate;
            }
        }

        $global = (int) Setting::get('campaign_rate_limit_per_minute', 0);
        if ($global > 0) {
            return $global;
        }

        if (! $applyTypeDefault) {
            return 0;
        }

        return $this->typeDefaultRate($deliveryServer);
    }

    /**
     * Per-server-type fallback (kept for safety when nothing is configured).
     */
    public function typeDefaultRate(?DeliveryServer $deliveryServer): int
    {
        $serverType = $deliveryServer?->type ?? 'smtp';

        if ($serverType === 'smtp' && $deliveryServer) {
            $host = strtolower((string) ($deliveryServer->hostname ?? ''));
            if (str_contains($host, 'siteground') || str_contains($host, 'sg')) {
                return 15;
            }
        }

        return self::TYPE_RATE_LIMITS[$serverType] ?? 20;
    }

    public function chunkSize(): int
    {
        return max(1, (int) Setting::get('campaign_chunk_size', 50));
    }

    public function rateLimitErrorPauseMinutes(): int
    {
        return max(1, (int) Setting::get('rate_limit_error_pause_minutes', 5));
    }

    public function queueWorkerContinuous(): bool
    {
        return (bool) Setting::get('queue_worker_continuous', true);
    }

    public function scheduleEnforced(): bool
    {
        return (bool) Setting::get('campaign_schedule_enforced', true);
    }

    public function autoPauseEnabled(): bool
    {
        return (bool) Setting::get('campaign_auto_pause_enabled', false);
    }

    /**
     * Whether "now" falls inside the campaign's configured sending schedule.
     * Empty schedule = always allowed.
     */
    public function isInsideSendingSchedule(?Campaign $campaign): bool
    {
        if ($campaign === null) {
            return true;
        }

        $schedule = is_array($campaign->settings)
            ? ($campaign->settings['sending_schedule'] ?? null)
            : null;

        if (! is_array($schedule) || $schedule === []) {
            return true;
        }

        $tz = (string) ($schedule['timezone'] ?? 'UTC');
        if (! in_array($tz, timezone_identifiers_list(), true)) {
            $tz = 'UTC';
        }

        $now = \Carbon\Carbon::now($tz);

        $days = array_map('intval', (array) ($schedule['days'] ?? []));
        if ($days !== [] && ! in_array((int) $now->format('N'), $days, true)) {
            return false;
        }

        $blocks = (array) ($schedule['time_blocks'] ?? []);
        if ($blocks === []) {
            return true;
        }

        $currentTime = $now->format('H:i');
        foreach ($blocks as $block) {
            $from = (string) ($block['from'] ?? '00:00');
            $to = (string) ($block['to'] ?? '23:59');
            if ($currentTime >= $from && $currentTime <= $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{gap_ms?: int|null, rate_per_minute?: int|null}
     */
    protected function campaignSpeed(?Campaign $campaign): array
    {
        if ($campaign === null || ! is_array($campaign->settings)) {
            return [];
        }

        $speed = $campaign->settings['send_speed'] ?? null;

        return is_array($speed) ? $speed : [];
    }
}

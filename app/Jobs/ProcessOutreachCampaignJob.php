<?php

namespace App\Jobs;

use App\Models\OutreachCampaign;
use App\Services\OutreachSendService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessOutreachCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Hard timeout so a broken delivery server cannot block the queue worker
     * for more than 30 seconds.
     */
    public int $timeout = 300;

    /**
     * Do not retry — the next minute's scheduler will dispatch a fresh job.
     */
    public int $tries = 1;

    public function __construct(
        public int $campaignId
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('outreach-campaign-' . $this->campaignId))->releaseAfter(30)->expireAfter(300),
        ];
    }

    public function handle(OutreachSendService $service): void
    {
        $campaign = OutreachCampaign::find($this->campaignId);

        if (!$campaign) {
            Log::debug('[Outreach] Campaign not found in queue job, skipping', [
                'campaign_id' => $this->campaignId,
            ]);
            return;
        }

        if ($campaign->status !== 'active') {
            Log::debug('[Outreach] Campaign is not active, skipping queue job', [
                'campaign_id' => $this->campaignId,
                'status' => $campaign->status,
            ]);
            return;
        }

        try {
            $stats = $service->processCampaign($campaign);
            Log::debug('[Outreach] Queue job finished', [
                'campaign_id' => $this->campaignId,
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Outreach] Queue job failed for campaign', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage(),
            ]);
            // Swallow the exception — we do NOT want Laravel to retry a
            // campaign with a permanently broken delivery server.
        }
    }
}

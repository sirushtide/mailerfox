<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOutreachCampaignJob;
use App\Models\OutreachCampaign;
use App\Services\OutreachSendService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDueOutreachLeads extends Command
{
    protected $signature = 'outreach:process-due
        {--campaign= : Restrict processing to a specific campaign id}
        {--no-parallel : Run campaigns sequentially instead of dispatching to queue}';

    protected $description = 'Send the next-due cold-email outreach step for every lead whose next_send_at has elapsed.';

    public function handle(OutreachSendService $service): int
    {
        $campaignId = $this->option('campaign');

        try {
            if ($campaignId) {
                return $this->processSingleCampaign($service, (int) $campaignId);
            }

            if ($this->option('no-parallel')) {
                return $this->processSequentially($service);
            }

            return $this->dispatchToQueue();
        } catch (\Throwable $e) {
            Log::error('[outreach:process-due] failed', ['error' => $e->getMessage()]);
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function processSingleCampaign(OutreachSendService $service, int $campaignId): int
    {
        $campaign = OutreachCampaign::find($campaignId);
        if (!$campaign) {
            $this->error("Campaign #{$campaignId} not found.");
            return self::FAILURE;
        }

        if ($campaign->status !== 'active') {
            $this->warn("Campaign #{$campaign->id} is not active (status={$campaign->status}); processing anyway because it was explicitly requested.");
        }

        $stats = $service->processCampaign($campaign);

        $this->info(sprintf(
            'Outreach processed: %d leads, %d sent, %d failed.',
            $stats['leads_processed'] ?? 0,
            $stats['leads_sent'] ?? 0,
            $stats['leads_failed'] ?? 0,
        ));

        return self::SUCCESS;
    }

    protected function processSequentially(OutreachSendService $service): int
    {
        $stats = $service->processAll();

        $this->info(sprintf(
            'Outreach processed: %d leads, %d sent, %d failed.',
            $stats['leads_processed'] ?? 0,
            $stats['leads_sent'] ?? 0,
            $stats['leads_failed'] ?? 0,
        ));

        return self::SUCCESS;
    }

    protected function dispatchToQueue(): int
    {
        $campaigns = OutreachCampaign::where('status', 'active')->get(['id']);

        if ($campaigns->isEmpty()) {
            $this->info('No active campaigns to process.');
            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            ProcessOutreachCampaignJob::dispatch($campaign->id)->onQueue('campaigns');
        }

        Log::info('[outreach:process-due] Dispatched campaign jobs to queue', [
            'campaign_ids' => $campaigns->pluck('id')->toArray(),
        ]);

        $this->info("Dispatched {$campaigns->count()} outreach campaign job(s) to the queue.");

        return self::SUCCESS;
    }
}

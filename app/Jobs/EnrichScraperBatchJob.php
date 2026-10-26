<?php

namespace App\Jobs;

use App\Models\EnrichmentJob;
use App\Models\ScraperJob;
use App\Models\ScraperLead;
use App\Services\WaterfallEnrichmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnrichScraperBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 1800;

    public function __construct(
        private int $scraperJobId
    ) {}

    public function handle(WaterfallEnrichmentService $service): void
    {
        $scraperJob = ScraperJob::find($this->scraperJobId);
        if (!$scraperJob) {
            return;
        }

        $enrichmentJob = EnrichmentJob::create([
            'scraper_job_id' => $this->scraperJobId,
            'customer_id' => $scraperJob->customer_id,
            'status' => 'running',
            'total_leads' => ScraperLead::where('job_id', $this->scraperJobId)->count(),
        ]);

        $leads = ScraperLead::where('job_id', $this->scraperJobId)
            ->whereIn('enrichment_status', ['none', 'pending', 'failed'])
            ->cursor();

        $processed = 0;
        $failed = 0;

        try {
            foreach ($leads as $lead) {
                try {
                    $success = $service->enrich($lead);
                    $processed++;

                    if (!$success) {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    Log::error('Enrichment failed for lead', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
                    $failed++;
                }

                // Rate limit: pause briefly between API calls
                usleep(200000); // 200ms
            }
        } finally {
            $enrichmentJob->update([
                'status' => 'completed',
                'processed' => $processed,
                'failed' => $failed,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EnrichScraperBatchJob failed', [
            'scraper_job_id' => $this->scraperJobId,
            'message' => $exception->getMessage(),
        ]);

        EnrichmentJob::where('scraper_job_id', $this->scraperJobId)
            ->where('status', 'running')
            ->update([
                'status' => 'failed',
            ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignChunkJob;
use App\Models\Campaign;
use App\Services\SendingSpeedResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoPauseResumeCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:auto-pause-resume {--seconds=60} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically pause and resume campaigns after specified seconds to prevent stalling';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! (bool) \App\Models\Setting::get('campaign_auto_pause_enabled', false)) {
            $this->info('Auto pause-resume is disabled (Admin → Settings → Email → campaign_auto_pause_enabled).');

            return 0;
        }

        $seconds = $this->option('seconds');
        $dryRun = $this->option('dry-run');

        $threshold = now()->subSeconds($seconds);

        $this->info("Checking for campaigns running for more than {$seconds} seconds...");

        // Find campaigns that have been running for more than X seconds
        $campaigns = Campaign::where('status', 'running')
            ->where('started_at', '<=', $threshold)
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info("No campaigns found running for more than {$seconds} seconds.");

            return 0;
        }

        $this->info("Found {$campaigns->count()} campaign(s) to inspect:");

        foreach ($campaigns as $campaign) {
            $runningSeconds = $campaign->started_at->diffInSeconds(now());
            $this->line("- Campaign #{$campaign->id}: '{$campaign->name}' (running for {$runningSeconds} seconds)");

            // Stall detection: only act when the campaign has pending recipients
            // AND has made no progress during the threshold window.
            $pendingRecipients = $campaign->recipients()
                ->where('status', 'pending')
                ->pluck('id')
                ->toArray();

            $recentProgress = $campaign->recipients()
                ->whereIn('status', ['sent', 'failed'])
                ->where('updated_at', '>=', $threshold)
                ->exists();

            if (empty($pendingRecipients) || $recentProgress) {
                $this->line('  → skipped (progress detected or no pending recipients)');

                continue;
            }

            $totalRecipients = $campaign->recipients()->count();

            if ($dryRun) {
                $this->line("  [DRY RUN] Would pause and resume stalled campaign #{$campaign->id}");

                continue;
            }

            try {
                // Pause the campaign
                $pauseTime = now();
                $campaign->update(['status' => 'paused']);
                Log::info("Auto-paused stalled campaign #{$campaign->id}", [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'running_seconds' => $runningSeconds,
                    'paused_at' => $pauseTime->toIso8601String(),
                    'started_at' => $campaign->started_at->toIso8601String(),
                ]);

                if (! empty($pendingRecipients)) {
                    // Resume by dispatching new chunks
                    $resumeTime = now();
                    $chunkSize = max(1, (int) app(SendingSpeedResolver::class)->chunkSize());
                    $chunks = array_chunk($pendingRecipients, $chunkSize);
                    foreach ($chunks as $chunk) {
                        SendCampaignChunkJob::dispatch($campaign, $chunk)
                            ->onQueue('campaigns');
                    }

                    // Update campaign status back to running
                    $campaign->update(['status' => 'running']);

                    $pauseDuration = $pauseTime->diffInSeconds($resumeTime);
                    Log::info("Auto-resumed campaign #{$campaign->id} with fresh chunks", [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'pending_recipients' => count($pendingRecipients),
                        'chunks_dispatched' => count($chunks),
                        'paused_at' => $pauseTime->toIso8601String(),
                        'resumed_at' => $resumeTime->toIso8601String(),
                        'pause_duration_seconds' => $pauseDuration,
                        'total_running_seconds' => $runningSeconds,
                    ]);

                    $this->line('  ✓ Paused and resumed with '.count($chunks).' chunks ('.count($pendingRecipients)." recipients) - Pause duration: {$pauseDuration}s");
                } else {
                    // Unreachable when stall-only skips empty pending lists; mark failed only if campaign never had recipients.
                    if ($totalRecipients === 0) {
                        $campaign->update([
                            'status' => 'failed',
                            'finished_at' => now(),
                            'failure_reason' => 'No recipients were ever created. Check email list for confirmed subscribers.',
                        ]);

                        Log::warning("Auto-failed campaign #{$campaign->id} (no recipients ever created)", [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'paused_at' => $pauseTime->toIso8601String(),
                            'failed_at' => now()->toIso8601String(),
                            'total_running_seconds' => $runningSeconds,
                        ]);

                        $this->line('  ✗ No recipients ever created, marked as failed');
                    } else {
                        // Campaign had recipients but none are pending - it's actually complete
                        $sentCount = $campaign->recipients()->where('status', 'sent')->count();
                        $failedCount = $campaign->recipients()->where('status', 'failed')->count();

                        $campaign->update([
                            'status' => 'completed',
                            'finished_at' => now(),
                        ]);

                        Log::info("Auto-completed campaign #{$campaign->id} (no pending recipients)", [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'total_recipients' => $totalRecipients,
                            'sent_count' => $sentCount,
                            'failed_count' => $failedCount,
                            'paused_at' => $pauseTime->toIso8601String(),
                            'completed_at' => now()->toIso8601String(),
                            'total_running_seconds' => $runningSeconds,
                        ]);

                        $this->line("  ✓ No pending recipients, marked as completed (Total: {$totalRecipients}, Sent: {$sentCount}, Failed: {$failedCount})");
                    }
                }

            } catch (\Exception $e) {
                Log::error("Failed to auto pause-resume campaign #{$campaign->id}: ".$e->getMessage(), [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error('  ✗ Failed: '.$e->getMessage());
            }
        }

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\SyncUniboxAccountJob;
use App\Models\Addon;
use App\Models\Setting;
use App\Models\UniboxAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncDueUniboxAccounts extends Command
{
    protected $signature = 'unibox:sync-due
        {--account= : Restrict sync to a specific UniboxAccount id}
        {--force : Ignore the sync interval and dispatch for every active account}';

    protected $description = 'Dispatch SyncUniboxAccountJob for every active UniBox account whose last sync is older than the configured interval.';

    public function handle(): int
    {
        if (!Addon::isActive('unibox')) {
            $this->info('UniBox addon is not active; nothing to do.');
            return self::SUCCESS;
        }

        if (!Schema::hasTable('unibox_accounts')) {
            $this->warn('unibox_accounts table is missing; skipping.');
            return self::SUCCESS;
        }

        $accountId = $this->option('account');
        $force = (bool) $this->option('force');

        $intervalMinutes = (int) Setting::get('unibox_sync_interval', 5);
        if ($intervalMinutes < 1) {
            $intervalMinutes = 5;
        }

        $query = UniboxAccount::query()->where('status', 'active');

        if ($accountId) {
            $query->where('id', (int) $accountId);
        } elseif (!$force) {
            $threshold = now()->subMinutes($intervalMinutes);
            $query->where(function ($q) use ($threshold) {
                $q->whereNull('last_synced_at')
                  ->orWhere('last_synced_at', '<=', $threshold);
            });
        }

        $dispatched = 0;
        $query->orderBy('last_synced_at')
            ->chunkById(100, function ($accounts) use (&$dispatched) {
                foreach ($accounts as $account) {
                    try {
                        SyncUniboxAccountJob::dispatch($account->id);
                        $dispatched++;
                    } catch (\Throwable $e) {
                        Log::warning('UniBox sync dispatch failed', [
                            'account_id' => $account->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Dispatched UniBox sync for {$dispatched} account(s) (interval={$intervalMinutes}m).");
        return self::SUCCESS;
    }
}

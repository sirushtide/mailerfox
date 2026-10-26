<?php

namespace App\Jobs;

use App\Models\UniboxAccount;
use App\Services\UniboxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
class SyncUniboxAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 300;
    public int $tries = 5;

    public function __construct(public int $accountId)
    {
    }

    public function handle(UniboxService $service): void
    {
        $account = UniboxAccount::find($this->accountId);

        if (! $account || $account->status === 'paused') {
            return;
        }

        $service->syncAccount($account);
    }

    public function failed(\Throwable $exception): void
    {
        $account = UniboxAccount::find($this->accountId);
        if ($account) {
            $account->update([
                'status' => 'error',
                'last_error' => 'Auto-sync failed: ' . substr($exception->getMessage(), 0, 255),
            ]);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Addon;
use App\Models\ReplyServer;
use App\Models\UniboxAccount;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncReplyServersToUnibox extends Command
{
    protected $signature = 'unibox:sync-reply-servers
        {--customer= : Restrict to a specific customer ID}
        {--cleanup : Remove UniBox accounts with no matching active reply server}';

    protected $description = 'Sync reply servers to UniBox accounts and optionally clean up orphans.';

    public function handle(): int
    {
        if (!Addon::isActive('unibox')) {
            $this->warn('UniBox addon is not active.');
            return self::FAILURE;
        }

        if (!Schema::hasTable('unibox_accounts') || !Schema::hasTable('reply_servers')) {
            $this->warn('Required tables are missing.');
            return self::FAILURE;
        }

        $customerId = $this->option('customer');
        $cleanup = $this->option('cleanup');

        $query = ReplyServer::query()->where('active', true);
        if ($customerId) {
            $query->where('customer_id', (int) $customerId);
        }

        $replyServers = $query->get();
        $this->info("Found {$replyServers->count()} active reply server(s).");

        $created = 0;
        $skipped = 0;
        $skippedReasons = [];

        foreach ($replyServers as $server) {
            $reasons = [];

            if (empty(trim((string) $server->username))) {
                $reasons[] = 'empty username';
            }
            if (empty(trim((string) $server->password))) {
                $reasons[] = 'empty password';
            }
            if (strtolower((string) ($server->protocol ?? 'imap')) === 'pop3') {
                $reasons[] = 'protocol=pop3';
            }
            $host = UniboxAccount::sanitizeHostname($server->hostname);
            if ($host === '') {
                $reasons[] = 'empty hostname after sanitization';
            }

            if ($reasons) {
                $skipped++;
                $skippedReasons[] = "#{$server->id} {$server->name}: " . implode(', ', $reasons);
                $this->warn("SKIP  #{$server->id} {$server->name}: " . implode(', ', $reasons));
                continue;
            }

            $email = strtolower(trim((string) $server->username));
            $existing = UniboxAccount::where('customer_id', $server->customer_id)
                ->where('email', $email)
                ->first();

            if ($existing) {
                $this->warn("SKIP  #{$server->id} {$server->name}: UniBox account already exists (#{$existing->id})");
                $skipped++;
                continue;
            }

            $attributes = [
                'name' => $server->name ?: $email,
                'type' => 'imap',
                'status' => 'active',
                'imap_host' => $host,
                'imap_port' => (int) ($server->port ?? 993),
                'imap_encryption' => (string) ($server->encryption ?? 'ssl'),
                'imap_username' => $server->username,
                'smtp_host' => $host,
                'smtp_port' => (int) ($server->port ?? 587),
                'smtp_encryption' => (string) ($server->encryption ?? 'tls'),
                'smtp_username' => $server->username,
            ];

            $normalizedPassword = UniboxAccount::normalizeEncryptedValue($server->password);
            if ($normalizedPassword) {
                $attributes['imap_password'] = $normalizedPassword;
                $attributes['smtp_password'] = $normalizedPassword;
            } else {
                $this->warn("SKIP  #{$server->id} {$server->name}: password normalization failed (old APP_KEY?)");
                $skipped++;
                continue;
            }

            $account = UniboxAccount::create($attributes + ['customer_id' => $server->customer_id, 'email' => $email]);

            $created++;
            $this->info("OK    #{$server->id} {$server->name} → UniBox account #{$account->id} (email: {$email})");

            $syncInterval = (int) (Setting::get('unibox_sync_interval') ?? 5);
            $lastSync = $account->last_synced_at;
            if (!$lastSync || $lastSync->diffInMinutes(now()) >= $syncInterval) {
                \App\Jobs\SyncUniboxAccountJob::dispatch($account->id);
                $this->info("      Queued sync for account #{$account->id}");
            }
        }

        $this->newLine();
        $this->info("Summary: {$created} created/updated, {$skipped} skipped.");

        if ($skippedReasons) {
            $this->newLine();
            $this->warn('Skipped reasons:');
            foreach ($skippedReasons as $r) {
                $this->warn("  - {$r}");
            }
        }

        if ($cleanup) {
            $this->newLine();
            $orphanQuery = UniboxAccount::query();
            if ($customerId) {
                $orphanQuery->where('customer_id', (int) $customerId);
            }

            $orphans = $orphanQuery->get()->filter(function ($account) {
                // An account is an orphan if it has no matching active reply server
                // by email, and no matching delivery server (imap/smtp/gmail/outlook/gmail-api)
                $hasReplyServer = ReplyServer::where('customer_id', $account->customer_id)
                    ->where('active', true)
                    ->whereRaw('LOWER(username) = ?', [strtolower($account->email)])
                    ->exists();

                if ($hasReplyServer) {
                    return false;
                }

                $hasDeliveryServer = \App\Models\DeliveryServer::where('customer_id', $account->customer_id)
                    ->whereIn('type', ['smtp', 'gmail', 'outlook', 'gmail-api'])
                    ->where(function ($q) use ($account) {
                        $q->whereRaw('LOWER(username) = ?', [strtolower($account->email)])
                          ->orWhereRaw('LOWER(email) = ?', [strtolower($account->email)]);
                    })
                    ->exists();

                return !$hasDeliveryServer;
            });

            $deleted = 0;
            foreach ($orphans as $orphan) {
                $orphan->delete();
                $deleted++;
                $this->warn("DELETED orphan account #{$orphan->id} ({$orphan->email})");
            }
            $this->info("Cleanup: removed {$deleted} orphan account(s).");
        }

        return self::SUCCESS;
    }
}

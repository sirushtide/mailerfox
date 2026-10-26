<?php

namespace App\Console\Commands;

use App\Models\DeliveryServer;
use App\Models\ReplyServer;
use Illuminate\Console\Command;

class SanitizeServerHostnames extends Command
{
    protected $signature = 'servers:sanitize-hostnames {--dry-run}';

    protected $description = 'Sanitize malformed hostnames in delivery_servers and reply_servers tables.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $totalFixed = 0;

        // Delivery servers
        $deliveryServers = DeliveryServer::whereRaw("hostname REGEXP '[^a-zA-Z0-9._-]' OR hostname LIKE '% - %'")
            ->orWhereRaw("hostname LIKE '% %'")
            ->get();

        $this->info("Found {$deliveryServers->count()} delivery server(s) with malformed hostnames.");

        foreach ($deliveryServers as $server) {
            $original = $server->hostname;
            $fixed = $this->sanitize($original);

            if ($fixed !== $original) {
                $this->line("  [DeliveryServer #{$server->id}] '{$original}' -> '{$fixed}'");
                if (!$dryRun) {
                    $server->update(['hostname' => $fixed]);
                }
                $totalFixed++;
            }
        }

        // Reply servers
        $replyServers = ReplyServer::whereRaw("hostname REGEXP '[^a-zA-Z0-9._-]' OR hostname LIKE '% - %'")
            ->orWhereRaw("hostname LIKE '% %'")
            ->get();

        $this->info("Found {$replyServers->count()} reply server(s) with malformed hostnames.");

        foreach ($replyServers as $server) {
            $original = $server->hostname;
            $fixed = $this->sanitize($original);

            if ($fixed !== $original) {
                $this->line("  [ReplyServer #{$server->id}] '{$original}' -> '{$fixed}'");
                if (!$dryRun) {
                    $server->update(['hostname' => $fixed]);
                }
                $totalFixed++;
            }
        }

        if ($dryRun) {
            $this->warn("Dry run complete. {$totalFixed} record(s) would be fixed. Run without --dry-run to apply.");
        } else {
            $this->info("Done. {$totalFixed} record(s) fixed.");
        }

        return 0;
    }

    protected function sanitize(?string $host): string
    {
        $host = trim((string) $host);
        if (str_contains($host, ' - ')) {
            $parts = explode(' - ', $host);
            $candidate = trim(end($parts));
            if (str_contains($candidate, '.') && !str_contains($candidate, ' ')) {
                $host = $candidate;
            }
        }
        if (str_contains($host, ' ')) {
            $host = strtok($host, ' ');
        }
        return $host;
    }
}

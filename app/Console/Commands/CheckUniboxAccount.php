<?php

namespace App\Console\Commands;

use App\Models\UniboxAccount;
use App\Services\UniboxEncryptionService;
use Illuminate\Console\Command;

class CheckUniboxAccount extends Command
{
    protected $signature = 'unibox:check-account {id=28}';
    protected $description = 'Debug a UniBox account password state';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $a = UniboxAccount::find($id);

        if (! $a) {
            $this->error("Account #{$id} not found.");
            return self::FAILURE;
        }

        $this->info("Account #{$a->id} | {$a->email} | Status: {$a->status} | Type: {$a->type}");
        $this->line("IMAP: {$a->imap_host}:{$a->imap_port} ({$a->imap_encryption}) | User: {$a->imap_username}");
        $this->line("SMTP: {$a->smtp_host}:{$a->smtp_port} ({$a->smtp_encryption}) | User: {$a->smtp_username}");
        $this->newLine();

        $imapLen = strlen((string) $a->imap_password);
        $smtpLen = strlen((string) $a->smtp_password);
        $this->line("IMAP password DB length: {$imapLen}");
        $this->line("SMTP password DB length: {$smtpLen}");

        if ($imapLen > 0) {
            $eyj = str_starts_with((string) $a->imap_password, 'eyJ') ? 'yes (Laravel encrypted)' : 'no (plain/other)';
            $this->line("IMAP starts with eyJ: {$eyj}");
            try {
                $pwd = decrypt($a->imap_password);
                $this->info("IMAP decrypt OK | length=" . strlen($pwd) . " | first char=" . $pwd[0]);
            } catch (\Throwable $e) {
                $custom = UniboxEncryptionService::decrypt($a->imap_password);
                if ($custom !== null) {
                    $this->info("IMAP custom decrypt OK | length=" . strlen($custom) . " | first char=" . $custom[0]);
                } else {
                    $this->error("IMAP DECRYPT FAILS: " . $e->getMessage());
                }
            }
        } else {
            $this->warn("IMAP password is EMPTY");
        }

        if ($smtpLen > 0) {
            $eyj = str_starts_with((string) $a->smtp_password, 'eyJ') ? 'yes (Laravel encrypted)' : 'no (plain/other)';
            $this->line("SMTP starts with eyJ: {$eyj}");
            try {
                $pwd = decrypt($a->smtp_password);
                $this->info("SMTP decrypt OK | length=" . strlen($pwd) . " | first char=" . $pwd[0]);
            } catch (\Throwable $e) {
                $custom = UniboxEncryptionService::decrypt($a->smtp_password);
                if ($custom !== null) {
                    $this->info("SMTP custom decrypt OK | length=" . strlen($custom) . " | first char=" . $custom[0]);
                } else {
                    $this->error("SMTP DECRYPT FAILS: " . $e->getMessage());
                }
            }
        } else {
            $this->warn("SMTP password is EMPTY");
        }

        $this->newLine();
        $this->line("Last sync: " . ($a->last_synced_at ?? 'never'));
        $this->line("Last error: " . ($a->last_error ?? 'none'));

        return self::SUCCESS;
    }
}

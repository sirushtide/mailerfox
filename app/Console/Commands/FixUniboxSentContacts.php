<?php

namespace App\Console\Commands;

use App\Models\UniboxAccount;
use App\Models\UniboxContact;
use App\Models\UniboxConversation;
use Illuminate\Console\Command;

class FixUniboxSentContacts extends Command
{
    protected $signature = 'unibox:fix-sent-contacts {--dry-run}';

    protected $description = 'Fix UniBox conversations where the contact was incorrectly set to the account email (sent-message bug).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $fixed = 0;
        $skipped = 0;

        $accounts = UniboxAccount::all();

        foreach ($accounts as $account) {
            $accountEmails = array_unique(array_filter([
                strtolower($account->email ?? ''),
                strtolower($account->imap_username ?? ''),
            ]));

            if (empty($accountEmails)) {
                continue;
            }

            // Find conversations where the contact email matches the account email
            $conversations = UniboxConversation::query()
                ->where('account_id', $account->id)
                ->whereHas('contact', function ($q) use ($accountEmails) {
                    $q->whereIn('email', $accountEmails);
                })
                ->with(['messages'])
                ->get();

            foreach ($conversations as $conversation) {
                $correctEmail = null;
                $correctName = null;

                foreach ($conversation->messages as $message) {
                    $msgFrom = strtolower($message->from_email ?? '');

                    // If there's an inbound message from someone else, that's the correct contact
                    if ($message->type === 'inbound' || !in_array($msgFrom, $accountEmails, true)) {
                        $correctEmail = $message->from_email;
                        $correctName = $message->from_name;
                        break;
                    }

                    // If it's an outbound message and we haven't found a candidate yet,
                    // use the first recipient as the contact
                    if (!$correctEmail && !empty($message->to)) {
                        $firstTo = is_array($message->to) ? $message->to[0] : null;
                        if ($firstTo) {
                            $correctEmail = $firstTo['email'] ?? null;
                            $correctName = $firstTo['name'] ?? null;
                        }
                    }
                }

                if (!$correctEmail) {
                    $this->warn("Conversation #{$conversation->id}: could not determine correct contact. Skipping.");
                    $skipped++;
                    continue;
                }

                $contact = UniboxContact::firstOrCreate(
                    ['customer_id' => $account->customer_id, 'email' => strtolower($correctEmail)],
                    ['name' => $correctName, 'first_seen_at' => now()]
                );

                if ($conversation->contact_id === $contact->id) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->info("[DRY-RUN] Conversation #{$conversation->id}: would update contact from {$conversation->contact->email} -> {$contact->email}");
                } else {
                    $conversation->update(['contact_id' => $contact->id]);
                    $this->info("Conversation #{$conversation->id}: updated contact from {$conversation->contact->email} -> {$contact->email}");
                }

                $fixed++;
            }
        }

        $this->newLine();
        $this->info("Done. Fixed: {$fixed}, Skipped: {$skipped}" . ($dryRun ? ' (dry-run)' : ''));

        return 0;
    }
}

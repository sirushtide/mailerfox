<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\ChannelAccount;
use App\Models\ListSubscriber;
use App\Services\Sms\SmsSendService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3600;

    public function __construct(public int $campaignId)
    {
    }

    public function handle(SmsSendService $sender): void
    {
        $campaign = Campaign::find($this->campaignId);
        if (!$campaign || $campaign->channel !== 'sms') {
            return;
        }

        $account = ChannelAccount::find($campaign->channel_account_id);
        if (!$account) {
            $campaign->update(['status' => 'failed', 'failure_reason' => 'SMS account not found.']);

            return;
        }

        $body = (string) ($campaign->html_content ?? '');
        $campaign->update([
            'status'      => 'running',
            'started_at'  => $campaign->started_at ?? now(),
        ]);

        $counts = [
            'sent'   => 0,
            'failed' => 0,
        ];

        $campaign->emailList?->subscribers()
            ->where('status', 'confirmed')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(200, function ($subscribers) use ($campaign, $account, $sender, $body, &$counts) {
                foreach ($subscribers as $subscriber) {
                    try {
                        $message = $sender->send($account, (string) $subscriber->phone, $this->personalize($body, $subscriber), [], [
                            'customer_id' => $campaign->customer_id,
                            'campaign_id' => $campaign->id,
                        ]);

                        if ($message->status === 'failed') {
                            $counts['failed']++;
                        } else {
                            $counts['sent']++;
                        }
                    } catch (\Throwable $e) {
                        $counts['failed']++;
                        Log::warning('SMS campaign send failed', [
                            'campaign_id' => $campaign->id,
                            'subscriber_id' => $subscriber->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $campaign->update([
            'status'        => 'completed',
            'finished_at'   => now(),
            'sent_count'    => $counts['sent'],
            'failed_count'  => $counts['failed'],
            'total_recipients' => $counts['sent'] + $counts['failed'],
        ]);
    }

    protected function personalize(string $body, ListSubscriber $subscriber): string
    {
        $replacements = [
            '{{first_name}}' => (string) ($subscriber->first_name ?? ''),
            '{{last_name}}'  => (string) ($subscriber->last_name ?? ''),
            '{{email}}'      => (string) ($subscriber->email ?? ''),
            '{{phone}}'      => (string) ($subscriber->phone ?? ''),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $body);
    }
}

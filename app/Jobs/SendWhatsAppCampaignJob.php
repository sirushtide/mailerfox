<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\ChannelAccount;
use App\Models\ListSubscriber;
use App\Services\WhatsApp\WhatsAppSendService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3600;

    public function __construct(public int $campaignId)
    {
    }

    public function handle(WhatsAppSendService $sender): void
    {
        $campaign = Campaign::find($this->campaignId);
        if (!$campaign || $campaign->channel !== 'whatsapp') {
            return;
        }

        $account = ChannelAccount::find($campaign->channel_account_id);
        if (!$account) {
            $campaign->update(['status' => 'failed', 'failure_reason' => 'WhatsApp account not found.']);

            return;
        }

        $config = is_array($campaign->channel_config) ? $campaign->channel_config : [];
        $mode = $config['mode'] ?? 'text';
        $template = $config['template'] ?? null;
        $language = $config['language'] ?? 'en_US';
        $body = (string) ($campaign->html_content ?? '');

        $campaign->update(['status' => 'running', 'started_at' => $campaign->started_at ?? now()]);

        $counts = ['sent' => 0, 'failed' => 0];

        $campaign->emailList?->subscribers()
            ->where('status', 'confirmed')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(200, function ($subscribers) use ($campaign, $account, $sender, $mode, $template, $language, $body, &$counts) {
                foreach ($subscribers as $subscriber) {
                    try {
                        if ($mode === 'template' && $template) {
                            $params = [
                                (string) ($subscriber->first_name ?? ''),
                                (string) ($subscriber->last_name ?? ''),
                            ];
                            $message = $sender->sendTemplate($account, (string) $subscriber->phone, (string) $template, $params, [
                                'customer_id' => $campaign->customer_id,
                                'campaign_id' => $campaign->id,
                                'language'    => $language,
                            ]);
                        } else {
                            $message = $sender->send($account, (string) $subscriber->phone, $this->personalize($body, $subscriber), [], [
                                'customer_id' => $campaign->customer_id,
                                'campaign_id' => $campaign->id,
                            ]);
                        }

                        $message->status === 'failed' ? $counts['failed']++ : $counts['sent']++;
                    } catch (\Throwable $e) {
                        $counts['failed']++;
                        Log::warning('WhatsApp campaign send failed', [
                            'campaign_id' => $campaign->id,
                            'subscriber_id' => $subscriber->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $campaign->update([
            'status'           => 'completed',
            'finished_at'      => now(),
            'sent_count'       => $counts['sent'],
            'failed_count'     => $counts['failed'],
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

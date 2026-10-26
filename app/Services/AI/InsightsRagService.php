<?php

namespace App\Services\AI;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class InsightsRagService
{
    public function __construct(
        protected RagService $ragService,
        protected ?AiProviderRouter $router = null,
    ) {
        $this->router = $router ?? app(AiProviderRouter::class);
    }

    public function gatherInsightsData(Customer $customer): array
    {
        $campaignStats = Campaign::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'completed'])
            ->selectRaw("
                COUNT(*) as total_campaigns,
                SUM(total_recipients) as total_sent,
                SUM(delivered_count) as total_delivered,
                SUM(opened_count) as total_opened,
                SUM(clicked_count) as total_clicked,
                SUM(bounced_count) as total_bounced,
                SUM(unsubscribed_count) as total_unsubscribed,
                AVG(CASE WHEN total_recipients > 0 THEN (opened_count / total_recipients) * 100 ELSE NULL END) as avg_open_rate,
                AVG(CASE WHEN total_recipients > 0 THEN (clicked_count / total_recipients) * 100 ELSE NULL END) as avg_click_rate,
                AVG(CASE WHEN total_recipients > 0 THEN (bounced_count / total_recipients) * 100 ELSE NULL END) as avg_bounce_rate
            ")
            ->first();

        $topCampaigns = Campaign::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'completed'])
            ->orderByDesc('opened_count')
            ->limit(5)
            ->get(['name', 'subject', 'total_recipients', 'opened_count', 'clicked_count', 'bounced_count']);

        $subscriberStats = DB::table('subscribers')
            ->where('customer_id', $customer->id)
            ->selectRaw("
                COUNT(*) as total_subscribers,
                SUM(CASE WHEN status = 'subscribed' THEN 1 ELSE 0 END) as active_subscribers,
                SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
            ")
            ->first();

        $listStats = DB::table('email_lists')
            ->where('customer_id', $customer->id)
            ->selectRaw("
                COUNT(*) as total_lists,
                (SELECT COUNT(*) FROM subscribers s INNER JOIN email_lists el ON s.list_id = el.id WHERE el.customer_id = {$customer->id} AND s.status = 'subscribed') as total_active
            ")
            ->first();

        $recentActivity = Campaign::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'completed'])
            ->orderByDesc('finished_at')
            ->limit(5)
            ->get(['name', 'subject', 'sent_count', 'opened_count', 'clicked_count', 'finished_at']);

        return [
            'campaign_stats' => $campaignStats,
            'top_campaigns' => $topCampaigns,
            'subscriber_stats' => $subscriberStats,
            'list_stats' => $listStats,
            'recent_activity' => $recentActivity,
        ];
    }

    public function buildInsightsContext(Customer $customer, string $query): string
    {
        $data = $this->gatherInsightsData($customer);
        $parts = [];

        $cs = $data['campaign_stats'];
        if ($cs && $cs->total_campaigns > 0) {
            $parts[] = "Campaign Analytics:\n"
                . "- Total campaigns sent: {$cs->total_campaigns}\n"
                . "- Total emails sent: {$cs->total_sent}\n"
                . "- Total delivered: {$cs->total_delivered}\n"
                . "- Total opened: {$cs->total_opened}\n"
                . "- Total clicked: {$cs->total_clicked}\n"
                . "- Total bounced: {$cs->total_bounced}\n"
                . "- Total unsubscribed: {$cs->total_unsubscribed}\n"
                . "- Average open rate: " . round((float) $cs->avg_open_rate, 2) . "%\n"
                . "- Average click rate: " . round((float) $cs->avg_click_rate, 2) . "%\n"
                . "- Average bounce rate: " . round((float) $cs->avg_bounce_rate, 2) . "%";
        } else {
            $parts[] = "Campaign Analytics: No campaigns sent yet.";
        }

        if ($data['top_campaigns']->isNotEmpty()) {
            $topParts = [];
            foreach ($data['top_campaigns'] as $c) {
                $openRate = $c->total_recipients > 0 ? round(($c->opened_count / $c->total_recipients) * 100, 1) : 0;
                $topParts[] = "  - \"{$c->subject}\": {$c->total_recipients} sent, {$openRate}% open rate";
            }
            $parts[] = "Top performing campaigns:\n" . implode("\n", $topParts);
        }

        $ss = $data['subscriber_stats'];
        if ($ss) {
            $parts[] = "Subscriber Stats:\n"
                . "- Total subscribers: {$ss->total_subscribers}\n"
                . "- Active (subscribed): {$ss->active_subscribers}\n"
                . "- Unsubscribed: {$ss->unsubscribed}\n"
                . "- Bounced: {$ss->bounced}";
        }

        $ls = $data['list_stats'];
        if ($ls) {
            $parts[] = "List Stats:\n- Total lists: {$ls->total_lists}\n- Total active subscribers across lists: {$ls->total_active}";
        }

        if ($data['recent_activity']->isNotEmpty()) {
            $activityParts = [];
            foreach ($data['recent_activity'] as $a) {
                $activityParts[] = "  - \"{$a->subject}\" sent on {$a->finished_at}: {$a->sent_count} sent, {$a->opened_count} opened";
            }
            $parts[] = "Recent campaign activity:\n" . implode("\n", $activityParts);
        }

        $kbChunks = $this->ragService->retrieve($query, $customer->id, 2);
        foreach ($kbChunks as $chunk) {
            $parts[] = "[Knowledge Base: {$chunk['title']}]\n{$chunk['content']}";
        }

        return implode("\n\n---\n\n", $parts);
    }

    public function answer(
        Customer $customer,
        string $query,
        string $provider = 'chatgpt',
        ?string $model = null
    ): array {
        if (!AiProviderRegistry::exists($provider)) {
            return [
                'success' => false,
                'message' => 'Invalid AI provider.',
                'status' => 422,
            ];
        }

        $customerField = AiProviderRegistry::customerField($provider);
        $settingKey = AiProviderRegistry::settingKey($provider);

        $ownApiKey = is_string($customer->{$customerField} ?? null) ? trim((string) $customer->{$customerField}) : '';
        $adminApiKey = is_string(Setting::get($settingKey)) ? trim((string) Setting::get($settingKey)) : '';
        $usingAdminKeys = false;

        if ($ownApiKey !== '') {
            $apiKey = $ownApiKey;
        } else {
            $apiKey = $adminApiKey;
            $usingAdminKeys = true;
        }

        if (!is_string($apiKey) || trim($apiKey) === '') {
            return [
                'success' => false,
                'message' => 'No AI API key configured.',
                'status' => 422,
            ];
        }

        $context = $this->buildInsightsContext($customer, $query);

        $systemPrompt = "You are an email marketing analytics assistant. "
            . "Answer the user's question based on the provided real-time analytics data. "
            . "Provide specific numbers, percentages, and actionable recommendations. "
            . "If the data is insufficient, say so clearly.\n\n"
            . "Analytics data:\n{$context}";

        $fullPrompt = $systemPrompt . "\n\n---\n\nQuestion: " . $query;

        $result = $this->router->generateText($model ?? AiProviderRegistry::defaultModel($provider), $fullPrompt, 1500, $customer);

        if (!is_array($result) || !($result['success'] ?? false)) {
            return [
                'success' => false,
                'message' => is_array($result) && is_string($result['message'] ?? null) ? $result['message'] : 'AI generation failed.',
                'status' => 502,
            ];
        }

        $tokens = is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : null;

        if ($usingAdminKeys && $tokens) {
            $customer->ai_token_usage = (int) ($customer->ai_token_usage ?? 0) + $tokens;
            $customer->save();
        }

        return [
            'success' => true,
            'answer' => $result['text'],
            'tokens' => $tokens,
            'used_admin_keys' => $usingAdminKeys,
        ];
    }
}

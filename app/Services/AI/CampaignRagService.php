<?php

namespace App\Services\AI;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\KnowledgeBase;
use App\Models\Setting;

class CampaignRagService
{
    public function __construct(
        protected RagService $ragService,
        protected ?AiProviderRouter $router = null,
    ) {
        $this->router = $router ?? app(AiProviderRouter::class);
    }

    public function retrieveCampaignContext(Customer $customer, string $prompt): array
    {
        $contextParts = [];
        $sources = [];

        $pastCampaigns = Campaign::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'completed'])
            ->orderByDesc('sent_count')
            ->limit(5)
            ->get();

        foreach ($pastCampaigns as $campaign) {
            $openRate = $campaign->total_recipients > 0
                ? round(($campaign->opened_count / $campaign->total_recipients) * 100, 1)
                : 0;
            $clickRate = $campaign->total_recipients > 0
                ? round(($campaign->clicked_count / $campaign->total_recipients) * 100, 1)
                : 0;

            $textContent = strip_tags((string) $campaign->html_content);
            $textContent = mb_substr($textContent, 0, 800);

            $contextParts[] = "Past Campaign: \"{$campaign->subject}\"\n"
                . "Open Rate: {$openRate}%, Click Rate: {$clickRate}%\n"
                . "Content excerpt: {$textContent}";

            $sources[] = [
                'title' => "Campaign: {$campaign->subject}",
                'content' => "Open rate: {$openRate}%, Click rate: {$clickRate}%",
                'score' => 1.0,
            ];
        }

        $kbChunks = $this->ragService->retrieve($prompt, $customer->id, 3);
        foreach ($kbChunks as $chunk) {
            $contextParts[] = "[Knowledge Base: {$chunk['title']}]\n{$chunk['content']}";
            $sources[] = [
                'title' => $chunk['title'],
                'content' => mb_substr($chunk['content'], 0, 200),
                'score' => $chunk['score'],
            ];
        }

        $brandName = Setting::get('app_name', config('app.name', 'MailPurse'));
        $contextParts[] = "Brand name: {$brandName}";

        return [
            'context' => implode("\n\n---\n\n", $contextParts),
            'sources' => $sources,
        ];
    }

    public function generateCampaignContent(
        Customer $customer,
        string $prompt,
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

        $retrieved = $this->retrieveCampaignContext($customer, $prompt);

        $systemPrompt = "You are an expert email marketing copywriter. "
            . "Use the following context from the user's past campaigns and knowledge base to write compelling, on-brand email content. "
            . "Match the tone and style of their best-performing campaigns. "
            . "Output plain text with a subject line and email body.\n\n"
            . "Context:\n{$retrieved['context']}";

        $fullPrompt = $systemPrompt . "\n\n---\n\nUser request: " . $prompt;

        $result = $this->router->generateText($model ?? AiProviderRegistry::defaultModel($provider), $fullPrompt, 1500, $customer);

        if (!is_array($result) || !($result['success'] ?? false)) {
            return [
                'success' => false,
                'message' => is_array($result) && is_string($result['message'] ?? null) ? $result['message'] : 'AI generation failed.',
                'status' => 502,
                'sources' => $retrieved['sources'],
            ];
        }

        $tokens = is_numeric($result['tokens'] ?? null) ? (int) $result['tokens'] : null;

        if ($usingAdminKeys && $tokens) {
            $customer->ai_token_usage = (int) ($customer->ai_token_usage ?? 0) + $tokens;
            $customer->save();
        }

        return [
            'success' => true,
            'text' => $result['text'],
            'sources' => $retrieved['sources'],
            'tokens' => $tokens,
            'used_admin_keys' => $usingAdminKeys,
        ];
    }
}

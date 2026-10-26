<?php

namespace App\Services\WhatsApp;

use App\Models\ChannelAccount;
use App\Models\ChannelMessage;
use App\Services\Channels\ChannelMessageService;
use App\Services\Channels\ChannelResult;
use App\Services\Channels\OutboundMessage;

class WhatsAppSendService
{
    public function __construct(
        protected WhatsAppCloudClient $client,
        protected ChannelMessageService $messages,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function send(ChannelAccount $account, string $to, string $body, array $media = [], array $context = []): ChannelMessage
    {
        $customerId = $context['customer_id'] ?? $account->customer_id;

        $outbound = new OutboundMessage(
            channel: 'whatsapp',
            account: $account,
            to: $to,
            body: $body,
            from: $account->from_address,
            media: $media,
            meta: $context,
            customerId: $customerId,
            threadId: $context['thread_id'] ?? null,
            campaignId: $context['campaign_id'] ?? null,
        );

        $result = empty($media)
            ? $this->client->sendText($account, $to, $body)
            : $this->client->sendMedia($account, $to, $body, $media[0]);

        return $this->persist($outbound, $result, $customerId, $account);
    }

    /**
     * @param array<int, mixed> $params
     * @param array<string, mixed> $context
     */
    public function sendTemplate(ChannelAccount $account, string $to, string $template, array $params = [], array $context = []): ChannelMessage
    {
        $customerId = $context['customer_id'] ?? $account->customer_id;
        $language = (string) ($context['language'] ?? 'en_US');

        $outbound = new OutboundMessage(
            channel: 'whatsapp',
            account: $account,
            to: $to,
            body: null,
            from: $account->from_address,
            meta: $context + ['language' => $language],
            customerId: $customerId,
            threadId: $context['thread_id'] ?? null,
            campaignId: $context['campaign_id'] ?? null,
            templateName: $template,
            templateParams: $params,
        );

        $result = $this->client->sendTemplate($account, $to, $template, $params, $language);

        return $this->persist($outbound, $result, $customerId, $account, $template);
    }

    protected function persist(
        OutboundMessage $outbound,
        ChannelResult $result,
        ?int $customerId,
        ChannelAccount $account,
        ?string $template = null
    ): ChannelMessage {
        $message = $this->messages->recordOutbound($outbound, $result);

        if ($customerId !== null) {
            $this->messages->logUsage($customerId, 'whatsapp', $account->provider, 1, [
                'message_id' => $message->id,
                'account_id' => $account->id,
                'template'   => $template,
            ]);
        }

        return $message;
    }
}

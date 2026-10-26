<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;

class ResendTransport extends AbstractTransport
{
    protected string $apiKey;
    protected string $endpoint;
    protected array $options;

    public function __construct(string $apiKey, string $endpoint = 'https://api.resend.com', array $options = [])
    {
        $this->apiKey = $apiKey;
        $this->endpoint = rtrim($endpoint, '/');
        $this->options = $options;

        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $originalMessage = $message->getOriginalMessage();

        if (!$originalMessage instanceof Message) {
            throw new TransportException('ResendTransport only supports Symfony\\Component\\Mime\\Message instances.');
        }

        $from = $originalMessage->getFrom();
        if (empty($from)) {
            throw new TransportException('Resend requires a "from" address.');
        }

        $fromAddress = $from[0]->getAddress();
        $fromName = $from[0]->getName();
        // Quote/escape the display name so quotes or angle brackets in a from-name
        // cannot produce a malformed From header.
        $fromHeader = $fromName
            ? sprintf('%s <%s>', preg_match('/[",<>\r\n]/', $fromName) ? '"' . addcslashes($fromName, '"\\') . '"' : $fromName, $fromAddress)
            : $fromAddress;

        $payload = [
            'from' => $fromHeader,
            'to' => array_map(fn ($a) => $a->getAddress(), $originalMessage->getTo()),
            'subject' => (string) $originalMessage->getSubject(),
        ];

        $cc = $originalMessage->getCc();
        if (!empty($cc)) {
            $payload['cc'] = array_map(fn ($a) => $a->getAddress(), $cc);
        }

        $bcc = $originalMessage->getBcc();
        if (!empty($bcc)) {
            $payload['bcc'] = array_map(fn ($a) => $a->getAddress(), $bcc);
        }

        $replyTo = $originalMessage->getReplyTo();
        if (!empty($replyTo)) {
            $payload['reply_to'] = array_map(fn ($a) => $a->getAddress(), $replyTo);
        }

        if ($originalMessage instanceof Email) {
            $html = $originalMessage->getHtmlBody();
            $text = $originalMessage->getTextBody();

            if (is_string($html) && $html !== '') {
                $payload['html'] = $html;
            }
            if (is_string($text) && $text !== '') {
                $payload['text'] = $text;
            }
            if (empty($payload['html']) && empty($payload['text'])) {
                $payload['text'] = '';
            }

            // Attachments
            $attachments = [];
            foreach ($originalMessage->getAttachments() as $attachment) {
                $headers = $attachment->getPreparedHeaders();
                $filename = null;
                if ($headers->has('Content-Disposition')) {
                    $disposition = $headers->get('Content-Disposition');
                    if (method_exists($disposition, 'getParameter')) {
                        $filename = $disposition->getParameter('filename');
                    }
                }
                $attachments[] = array_filter([
                    'filename' => $filename ?: 'attachment',
                    'content' => base64_encode($attachment->getBody()),
                ]);
            }
            if (!empty($attachments)) {
                $payload['attachments'] = $attachments;
            }

            // Custom headers (forward selected non-standard headers)
            $customHeaders = [];
            foreach ($originalMessage->getHeaders()->all() as $header) {
                $name = $header->getName();
                $lower = strtolower($name);
                if (in_array($lower, [
                    'from', 'to', 'cc', 'bcc', 'subject', 'reply-to',
                    'content-type', 'content-transfer-encoding', 'mime-version',
                    'date', 'message-id',
                ], true)) {
                    continue;
                }
                $customHeaders[$name] = $header->getBodyAsString();
            }
            if (!empty($customHeaders)) {
                $payload['headers'] = $customHeaders;
            }
        } else {
            $payload['text'] = '';
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->endpoint . '/emails', $payload);
        } catch (\Throwable $e) {
            throw new TransportException(
                sprintf('Request to Resend API failed. Reason: %s.', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        if (!$response->successful()) {
            $errorBody = $response->body();
            if (mb_strlen($errorBody) > 500) {
                $errorBody = mb_substr($errorBody, 0, 500) . '...';
            }
            throw new TransportException(
                sprintf('Resend API request failed (HTTP %s). Reason: %s.', $response->status(), $errorBody)
            );
        }

        $result = $response->json();
        $messageId = $result['id'] ?? null;
        if ($messageId) {
            $originalMessage->getHeaders()->addHeader('X-Resend-Message-ID', $messageId);
            $originalMessage->getHeaders()->addHeader('X-Message-ID', $messageId);
        }
    }

    public function __toString(): string
    {
        return 'resend';
    }
}

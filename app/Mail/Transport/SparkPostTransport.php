<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;

class SparkPostTransport extends AbstractTransport
{
    /**
     * The SparkPost API key.
     *
     * @var string
     */
    protected $secret;

    /**
     * The SparkPost API endpoint.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * The SparkPost transmission options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new SparkPost transport instance.
     *
     * @param  string  $secret
     * @param  string  $endpoint
     * @param  array  $options
     */
    public function __construct(string $secret, string $endpoint = 'https://api.sparkpost.com', $options = [])
    {
        $this->secret = $secret;
        $this->endpoint = rtrim($endpoint, '/');
        $this->options = $options;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function doSend(SentMessage $message): void
    {
        $originalMessage = $message->getOriginalMessage();
        
        if (!$originalMessage instanceof Message) {
            throw new TransportException('SparkPostTransport only supports Symfony\Component\Mime\Message instances.');
        }

        $options = $this->options;

        // Extract email details
        $from = $originalMessage->getFrom();
        $toAddresses = [];
        $ccAddresses = [];
        $bccAddresses = [];
        $replyToAddresses = [];

        // Get recipients
        foreach ($originalMessage->getTo() as $address) {
            $toAddresses[] = ['address' => ['email' => $address->getAddress(), 'name' => $address->getName()]];
        }

        $toEmailList = implode(',', array_map(fn ($a) => $a['address']['email'], $toAddresses));

        foreach ($originalMessage->getCc() as $address) {
            // Mark CC recipients with header_to so SparkPost keeps proper CC semantics
            // instead of treating them as primary To recipients.
            $ccAddresses[] = ['address' => ['email' => $address->getAddress(), 'name' => $address->getName(), 'header_to' => $toEmailList]];
        }

        foreach ($originalMessage->getBcc() as $address) {
            // BCC recipients must be delivered but stay hidden: header_to keeps them
            // out of the visible To header.
            $bccAddresses[] = ['address' => ['email' => $address->getAddress(), 'header_to' => $toEmailList]];
        }

        // Get reply-to addresses
        foreach ($originalMessage->getReplyTo() as $address) {
            $replyToAddresses[] = ['address' => ['email' => $address->getAddress(), 'name' => $address->getName()]];
        }

        // Build transmission parameters
        $params = array_merge($options, [
            'content' => [
                'from' => [
                    'email' => $from[0]->getAddress(),
                    'name' => $from[0]->getName() ?? '',
                ],
                'subject' => $originalMessage->getSubject(),
                'text' => $originalMessage->getTextBody() ?? strip_tags($originalMessage->getHtmlBody() ?? ''),
            ],
            'recipients' => array_merge($toAddresses, $ccAddresses, $bccAddresses),
        ]);

        // Add HTML body if present
        if ($originalMessage->getHtmlBody()) {
            $params['content']['html'] = $originalMessage->getHtmlBody();
        }

        // Add ReplyTo if present
        if (!empty($replyToAddresses)) {
            $params['content']['reply_to'] = $replyToAddresses[0]['address']['email'];
        }

        // Preserve To/CC visibility so header_to has something to match against.
        $extraHeaders = [];
        if (!empty($toAddresses)) {
            $extraHeaders['To'] = implode(',', array_map(fn ($a) => $a['address']['email'], $toAddresses));
        }
        if (!empty($ccAddresses)) {
            $extraHeaders['CC'] = implode(',', array_map(fn ($a) => $a['address']['email'], $ccAddresses));
        }
        if (!empty($extraHeaders)) {
            $params['content']['headers'] = array_merge((array) ($params['content']['headers'] ?? []), $extraHeaders);
        }

        // Add return path if present
        if ($originalMessage instanceof Email) {
            $returnPath = $originalMessage->getReturnPath();
            if ($returnPath) {
                $returnPathAddress = trim((string) $returnPath->getAddress());
                if ($returnPathAddress !== '') {
                    $params['return_path'] = $returnPathAddress;
                }
            }
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => $this->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->endpoint . '/api/v1/transmissions', $params);
        } catch (\Exception $e) {
            throw new TransportException(
                sprintf('Request to SparkPost API failed. Reason: %s.', $e->getMessage()),
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
                sprintf('SparkPost API request failed (HTTP %s). Reason: %s.', $response->status(), $errorBody)
            );
        }

        $result = $response->json();
        $messageId = $result['results']['id'] ?? null;

        if ($messageId) {
            $originalMessage->getHeaders()->addHeader('X-Message-ID', $messageId);
            $originalMessage->getHeaders()->addHeader('X-SparkPost-Message-ID', $messageId);
        }
    }

    /**
     * Get the transmission options being used by the transport.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the transmission options being used by the transport.
     *
     * @param  array  $options
     * @return array
     */
    public function setOptions(array $options)
    {
        return $this->options = $options;
    }

    /**
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'sparkpost';
    }
}

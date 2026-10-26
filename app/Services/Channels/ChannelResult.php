<?php

namespace App\Services\Channels;

/**
 * Result of a channel send operation.
 */
class ChannelResult
{
    public function __construct(
        public bool $success,
        public ?string $providerMessageId = null,
        public ?string $status = null,
        public ?string $error = null,
        public array $raw = [],
    ) {
    }

    public static function ok(?string $providerMessageId = null, string $status = 'sent', array $raw = []): self
    {
        return new self(true, $providerMessageId, $status, null, $raw);
    }

    public static function fail(string $error, array $raw = []): self
    {
        return new self(false, null, 'failed', $error, $raw);
    }
}

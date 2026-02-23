<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Result from Letter::cancelQueued() or Letter::releaseQueued().
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterQueueResult schema
 */
class QueuedOperationResult
{
    public function __construct(
        private readonly string $message,
        private readonly ?int $letterId = null,
        private readonly ?bool $successful = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['message'] ?? '',
            isset($data['letterID']) ? (int) $data['letterID'] : null,
            isset($data['successful']) ? (bool) $data['successful'] : null,
        );
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLetterId(): ?int
    {
        return $this->letterId;
    }

    public function isSuccessful(): ?bool
    {
        return $this->successful;
    }
}

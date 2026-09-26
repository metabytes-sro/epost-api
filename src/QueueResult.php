<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Result of cancelling or releasing a queued letter.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterQueueResult schema
 */
final readonly class QueueResult
{
    public function __construct(
        public string $message,
        public ?int $letterId = null,
        public ?bool $successful = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Json::string($data['message'] ?? null) ?? '',
            Json::int($data['letterID'] ?? null),
            isset($data['successful']) ? Json::bool($data['successful']) : null,
        );
    }
}

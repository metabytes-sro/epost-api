<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Result from Letter::send() or Letter::sendBatch(): the letter ID assigned by the API.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterIdent schema
 */
class LetterSendResult
{
    public function __construct(
        private readonly int $letterId,
        private readonly ?string $fileName = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Json::int($data['letterID'] ?? null) ?? 0,
            Json::string($data['fileName'] ?? null),
        );
    }

    public function getLetterId(): int
    {
        return $this->letterId;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }
}

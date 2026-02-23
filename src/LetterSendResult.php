<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Result from Letter::send() or Letter::sendBatch().
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterIdent schema
 */
class LetterSendResult
{
    public function __construct(
        private readonly int $letterId,
        private readonly ?string $fileName = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['letterID'] ?? 0),
            isset($data['fileName']) ? (string) $data['fileName'] : null,
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

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Result from Letter::getTestResult().

 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterDataResult schema
 */
class LetterDataResult
{
    public function __construct(
        private readonly ?int $letterId = null,
        private readonly ?string $fileName = null,
        private readonly ?string $data = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['letterID']) ? (int) $data['letterID'] : null,
            isset($data['fileName']) ? (string) $data['fileName'] : null,
            isset($data['data']) ? (string) $data['data'] : null,
        );
    }

    public function getLetterId(): ?int
    {
        return $this->letterId;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * PDF content as Base64-encoded string.
     */
    public function getData(): ?string
    {
        return $this->data;
    }
}

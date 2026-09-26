<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Result from Letter::getTestResult(): the processed PDF of a test send.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterDataResult schema
 */
class LetterDataResult
{
    public function __construct(
        private readonly ?int $letterId = null,
        private readonly ?string $fileName = null,
        private readonly ?string $data = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Json::int($data['letterID'] ?? null),
            Json::string($data['fileName'] ?? null),
            Json::string($data['data'] ?? null),
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

    /**
     * PDF content decoded, or null when the API returned no data.
     */
    public function getPdf(): ?string
    {
        if ($this->data === null || $this->data === '') {
            return null;
        }
        $decoded = base64_decode($this->data, true);

        return $decoded === false ? null : $decoded;
    }
}

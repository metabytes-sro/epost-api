<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * The processed PDF of a letter that was sent in test mode.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterDataResult schema
 */
final readonly class TestResult
{
    /**
     * @param string|null $data PDF as Base64 string, as sent by the API
     */
    public function __construct(
        public ?int $letterId = null,
        public ?string $fileName = null,
        public ?string $data = null,
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

    /**
     * The PDF bytes, or null when the API returned no data.
     */
    public function pdf(): ?string
    {
        if ($this->data === null || $this->data === '') {
            return null;
        }
        $decoded = base64_decode($this->data, true);

        return $decoded === false ? null : $decoded;
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Result of submitting a letter: the ID assigned by the API, which identifies
 * the letter in every later status query.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterIdent schema
 */
final readonly class LetterSendResult
{
    public function __construct(
        public int $letterId,
        public ?string $fileName = null,
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
}

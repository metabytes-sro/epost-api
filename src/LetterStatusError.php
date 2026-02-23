<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Error item from LetterStatus::getErrors() (errorList).
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json Error schema
 */
class LetterStatusError
{
    public function __construct(
        private readonly string $level,
        private readonly string $code,
        private readonly string $description,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['level'] ?? '',
            $data['code'] ?? '',
            $data['description'] ?? '',
        );
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}

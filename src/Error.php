<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Error object returned by the E-POST API, both as an error response body and as
 * an item of LetterStatus::getErrors().
 *
 * The level is one of "Info", "Warning" or "Error"; the code is a short
 * identifier such as "E101" (expired token) or "W201" (address area exceeded).
 * The full catalogue is documented in the Error schema of the API definition,
 * see docs/api in the repository.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json Error schema
 */
class Error
{
    public const LEVEL_INFO = 'Info';
    public const LEVEL_WARNING = 'Warning';
    public const LEVEL_ERROR = 'Error';

    final public function __construct(
        private readonly string $level,
        private readonly string $code,
        private readonly string $description,
        private readonly ?string $date = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            Json::string($data['level'] ?? null) ?? '',
            Json::string($data['code'] ?? null) ?? '',
            Json::string($data['description'] ?? null) ?? '',
            Json::string($data['date'] ?? null),
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

    /**
     * Timestamp of the message as reported by the API, when present.
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    public function isError(): bool
    {
        return strcasecmp($this->level, self::LEVEL_ERROR) === 0;
    }

    public function isWarning(): bool
    {
        return strcasecmp($this->level, self::LEVEL_WARNING) === 0;
    }

    public function isInfo(): bool
    {
        return strcasecmp($this->level, self::LEVEL_INFO) === 0;
    }
}

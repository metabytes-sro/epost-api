<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use DateTimeImmutable;

/**
 * Error object of the E-POST API: the body of an error response, an item of
 * LetterStatus::$errors, and the result of the health check.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json Error schema
 */
final readonly class Error
{
    /**
     * @param string $level "Info", "Warning" or "Error"
     * @param string $code Short identifier such as "E101"; see ErrorCode for the catalogue
     * @param string $description Message text, usually German
     * @param DateTimeImmutable|null $date Time of the message when the API reports one
     */
    public function __construct(
        public string $level,
        public string $code,
        public string $description,
        public ?DateTimeImmutable $date = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Json::string($data['level'] ?? null) ?? '',
            Json::string($data['code'] ?? null) ?? '',
            Json::string($data['description'] ?? null) ?? '',
            Json::date($data['date'] ?? null),
        );
    }

    public function errorLevel(): ?ErrorLevel
    {
        return ErrorLevel::fromLabel($this->level);
    }

    /**
     * The code as enum, or null for a code this package does not know.
     */
    public function errorCode(): ?ErrorCode
    {
        return ErrorCode::tryFrom($this->code);
    }

    public function isError(): bool
    {
        return $this->errorLevel() === ErrorLevel::Error;
    }

    public function isWarning(): bool
    {
        return $this->errorLevel() === ErrorLevel::Warning;
    }

    public function isInfo(): bool
    {
        return $this->errorLevel() === ErrorLevel::Info;
    }
}

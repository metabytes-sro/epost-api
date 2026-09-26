<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Response of a login: the JSON Web Token for the other endpoints.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LoginResponse schema
 */
final readonly class LoginResponse
{
    public function __construct(
        public string $token,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(Json::string($data['token'] ?? null) ?? '');
    }
}

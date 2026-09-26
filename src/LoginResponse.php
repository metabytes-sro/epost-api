<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Response from Login::login(): the JSON Web Token for the other endpoints.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LoginResponse schema
 */
class LoginResponse
{
    public function __construct(
        private readonly string $token,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(Json::string($data['token'] ?? null) ?? '');
    }

    public function getToken(): string
    {
        return $this->token;
    }
}

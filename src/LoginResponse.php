<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Response from Login::login().
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LoginResponse schema
 */
class LoginResponse
{
    public function __construct(
        private readonly string $token,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['token'] ?? '',
        );
    }

    public function getToken(): string
    {
        return $this->token;
    }
}

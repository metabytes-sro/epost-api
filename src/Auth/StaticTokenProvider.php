<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Auth;

/**
 * A fixed token obtained elsewhere, for example from an OAuth2 provider.
 * It cannot be refreshed; an expired token surfaces as AuthenticationException.
 */
final readonly class StaticTokenProvider implements TokenProvider
{
    public function __construct(
        private string $token,
    ) {}

    public function getToken(): string
    {
        return $this->token;
    }

    public function invalidate(): void
    {
        // A static token has nothing to refresh.
    }
}

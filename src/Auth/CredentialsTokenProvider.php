<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Auth;

use MetabytesSRO\EPost\Api\Login;

/**
 * Logs in with credentials on first use and keeps the token for the lifetime
 * of the object. After invalidate() the next request logs in again.
 *
 * Wrap it in CachedTokenProvider to share the token between PHP processes.
 */
final class CredentialsTokenProvider implements TokenProvider
{
    private ?string $token = null;

    public function __construct(
        private readonly Credentials $credentials,
        private readonly Login $login = new Login(),
    ) {}

    public function getToken(): string
    {
        return $this->token ??= $this->login->login($this->credentials)->token;
    }

    public function invalidate(): void
    {
        $this->token = null;
    }
}

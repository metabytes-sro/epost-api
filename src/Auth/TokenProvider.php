<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Auth;

/**
 * Supplies the bearer token for API requests.
 */
interface TokenProvider
{
    /**
     * A token that is expected to be valid. Implementations may log in or read a cache.
     *
     * @throws \MetabytesSRO\EPost\Api\Exception\EPostException when no token can be obtained
     */
    public function getToken(): string;

    /**
     * Forget the current token so the next getToken() call obtains a fresh one.
     * Called by the client when the API reports an expired token (E101).
     */
    public function invalidate(): void;
}

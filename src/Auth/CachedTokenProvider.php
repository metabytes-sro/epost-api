<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Auth;

use Psr\SimpleCache\CacheInterface;

/**
 * Stores the token of another provider in a PSR-16 cache so that a token is
 * reused across PHP processes instead of logging in on every request.
 *
 * Requires a psr/simple-cache implementation.
 */
final readonly class CachedTokenProvider implements TokenProvider
{
    /** Default lifetime, one hour short of the API's 24 hour token validity. */
    public const int DEFAULT_TTL_SECONDS = 23 * 3600;

    /**
     * @param string $cacheKey Cache key; use one key per set of credentials
     * @param int $ttlSeconds How long a token is kept; keep it below the token duration
     */
    public function __construct(
        private TokenProvider $inner,
        private CacheInterface $cache,
        private string $cacheKey = 'epost_api_token',
        private int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ) {}

    public function getToken(): string
    {
        $cached = $this->cache->get($this->cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $this->inner->getToken();
        $this->cache->set($this->cacheKey, $token, $this->ttlSeconds);

        return $token;
    }

    public function invalidate(): void
    {
        $this->cache->delete($this->cacheKey);
        $this->inner->invalidate();
    }
}

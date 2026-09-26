<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

/**
 * Too many requests in a short time (HTTP 429, E322, E003).
 *
 * Status queries and the health check must be at least 5 seconds apart. Wait
 * and retry; the API does not send a Retry-After header.
 */
class RateLimitException extends ApiException
{
    /** Minimum interval between status queries required by the API, in seconds. */
    public const int MIN_INTERVAL_SECONDS = 5;
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * Thrown when a request could not be completed at the HTTP level: DNS failure,
 * connection refused, timeout, TLS error. The PSR-18 exception is available
 * through getPrevious().
 */
class TransportException extends RuntimeException implements EPostException
{
    public static function fromClientException(ClientExceptionInterface $e): self
    {
        return new self('The E-POST API could not be reached: ' . $e->getMessage(), 0, $e);
    }
}

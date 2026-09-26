<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use GuzzleHttp\Exception\ClientException;
use MetabytesSRO\EPost\Api\Error;
use MetabytesSRO\EPost\Api\Json;
use RuntimeException;
use Throwable;

/**
 * Thrown when the E-POST API answers with an error response (HTTP 4xx).
 *
 * The parsed API error is available through getError(). The exception message is
 * the error description and getCode() returns the E-POST error code, e.g. "E101".
 */
class ErrorException extends RuntimeException implements EPostException
{
    public function __construct(
        private readonly Error $error,
        private readonly int $statusCode = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($error->getDescription(), 0, $previous);
        $this->code = $error->getCode();
    }

    /**
     * Build the exception from a Guzzle 4xx response, parsing the Error body the API sends.
     */
    public static function fromClientException(ClientException $e): self
    {
        $response = $e->getResponse();
        $body = (string) $response->getBody();
        $data = Json::decodeObject($body);
        if ($data === []) {
            // No parsable Error object: keep the HTTP reason so the message is still useful.
            $data = [
                'level' => Error::LEVEL_ERROR,
                'code' => 'HTTP' . $response->getStatusCode(),
                'description' => trim($response->getReasonPhrase() . ' ' . $body),
            ];
        }

        return new self(Error::fromArray($data), $response->getStatusCode(), $e);
    }

    public function getError(): Error
    {
        return $this->error;
    }

    public function getLevel(): string
    {
        return $this->error->getLevel();
    }

    /**
     * HTTP status code of the API response, 0 when unknown.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * True when the API rejected the request because of too many calls in a short
     * time (HTTP 429 / E322). Status queries must be at least 5 seconds apart.
     */
    public function isRateLimited(): bool
    {
        return $this->statusCode === 429 || $this->error->getCode() === 'E322';
    }

    /**
     * True when the bearer token is expired or invalid (E101) or the login
     * credentials were rejected (E001, E002).
     */
    public function isAuthenticationError(): bool
    {
        return in_array($this->error->getCode(), ['E001', 'E002', 'E101'], true)
            || $this->statusCode === 401;
    }

    /**
     * True when no letter matched the query (HTTP 404 / E201).
     */
    public function isNotFound(): bool
    {
        return $this->statusCode === 404 || $this->error->getCode() === 'E201';
    }

    public function __toString(): string
    {
        return self::class . ": [{$this->error->getLevel()}] [{$this->error->getCode()}]: {$this->message}\n";
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use MetabytesSRO\EPost\Api\Error;
use MetabytesSRO\EPost\Api\ErrorCode;
use MetabytesSRO\EPost\Api\ErrorLevel;
use MetabytesSRO\EPost\Api\Json;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Thrown when the E-POST API answers a request with an error.
 *
 * The parsed Error object is available through getError(); getCode() returns the
 * E-POST error code such as "E101", and getMessage() its description. More
 * specific subclasses are thrown for authentication failures, unknown letters
 * and rate limiting so they can be caught separately.
 */
class ApiException extends RuntimeException implements EPostException
{
    public function __construct(
        private readonly Error $error,
        private readonly int $statusCode = 0,
        private readonly ?ResponseInterface $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($error->description, 0, $previous);
        $this->code = $error->code;
    }

    /**
     * Build the matching exception for an error response of the API.
     *
     * Responses without a parsable Error object get a synthetic error whose code
     * is "HTTP" followed by the status, so the message is still useful.
     */
    public static function fromResponse(ResponseInterface $response, ?Throwable $previous = null): self
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = Json::decodeObject($body);
        if (!isset($data['code'])) {
            $data = [
                'level' => ErrorLevel::Error->value,
                'code' => 'HTTP' . $status,
                'description' => trim($response->getReasonPhrase() . ' ' . $body),
            ];
        }
        $error = Error::fromArray($data);
        $code = $error->errorCode();

        $class = match (true) {
            $status === 401, $code === ErrorCode::E001, $code === ErrorCode::E002, $code === ErrorCode::E101 => AuthenticationException::class,
            $status === 404, $code === ErrorCode::E201 => NotFoundException::class,
            $status === 429, $code === ErrorCode::E003, $code === ErrorCode::E322 => RateLimitException::class,
            default => self::class,
        };

        return new $class($error, $status, $response, $previous);
    }

    public function getError(): Error
    {
        return $this->error;
    }

    /**
     * The E-POST error code as enum, or null for a code this package does not know.
     */
    public function getErrorCode(): ?ErrorCode
    {
        return $this->error->errorCode();
    }

    /**
     * HTTP status code of the API response, 0 when the exception was not built from a response.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function __toString(): string
    {
        return static::class . ": [{$this->error->level}] [{$this->error->code}]: {$this->message}\n";
    }
}

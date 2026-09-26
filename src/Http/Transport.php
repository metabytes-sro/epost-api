<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use MetabytesSRO\EPost\Api\Exception\ApiException;
use MetabytesSRO\EPost\Api\Exception\TransportException;
use MetabytesSRO\EPost\Api\Json;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Sends JSON requests to the E-POST API over any PSR-18 HTTP client.
 *
 * Without arguments a Guzzle client is used. Pass your own client to add
 * timeouts, logging, retries or to use a different HTTP library; PSR-17
 * factories are only needed when the client is not Guzzle.
 *
 * Error responses (HTTP 4xx and 5xx) become ApiException; connection failures
 * become TransportException.
 */
final class Transport
{
    public const string API_ENDPOINT = 'https://api.epost.docuguide.com';

    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;
    private readonly string $baseUri;

    /**
     * @param ClientInterface|null $httpClient PSR-18 client; defaults to Guzzle
     * @param RequestFactoryInterface|null $requestFactory PSR-17 request factory; defaults to Guzzle's
     * @param StreamFactoryInterface|null $streamFactory PSR-17 stream factory; defaults to Guzzle's
     * @param string $baseUri API base URI without trailing slash
     */
    public function __construct(
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        string $baseUri = self::API_ENDPOINT,
    ) {
        $this->httpClient = $httpClient ?? new GuzzleClient();
        $factory = ($requestFactory === null || $streamFactory === null) ? new HttpFactory() : null;
        $this->requestFactory = $requestFactory ?? $factory ?? new HttpFactory();
        $this->streamFactory = $streamFactory ?? $factory ?? new HttpFactory();
        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * Send a request and return the successful response.
     *
     * @param string $path Path starting with "/", e.g. "/api/Letter"
     * @param array<string, bool|int|string>|null $query Query parameters; booleans are sent as "true"/"false"
     * @param mixed $json Request body, JSON encoded when not null
     * @param string|null $bearerToken Token for the Authorization header
     *
     * @throws ApiException for HTTP 4xx and 5xx responses
     * @throws TransportException when the request could not be sent
     */
    public function request(
        string $method,
        string $path,
        ?array $query = null,
        mixed $json = null,
        ?string $bearerToken = null,
    ): ResponseInterface {
        $uri = $this->baseUri . $path;
        if ($query !== null && $query !== []) {
            $uri .= '?' . http_build_query(array_map(
                static fn(bool|int|string $value): string => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
                $query,
            ), '', '&', PHP_QUERY_RFC3986);
        }

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json');
        if ($json !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(Json::encode($json)));
        }
        if ($bearerToken !== null) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $bearerToken);
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw TransportException::fromClientException($e);
        }

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }

        return $response;
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }
}

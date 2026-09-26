<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use MetabytesSRO\EPost\Api\Exception\ErrorException;

/**
 * Authentication endpoints of the E-POSTBUSINESS API (/api/Login).
 *
 * API errors (HTTP 4xx) are converted to ErrorException. Timeouts and connection
 * failures (GuzzleHttp\Exception\ConnectException) are not caught; callers should
 * handle these themselves.
 */
class Login
{
    public function __construct(
        private readonly ?ClientInterface $httpClient = null,
    ) {}

    /**
     * Request a JSON Web Token for the other endpoints of the API.
     *
     * @param string $vendorId DPAG identifier of the software vendor
     * @param string $ekp DPAG identifier of the customer (10 characters)
     * @param string $secret Secret returned when the password was set (setPassword())
     * @param string $password Password chosen by the customer
     * @param string|null $vendorSubId Optional partner-managed customer identifier
     * @param int|null $tokenDuration Optional token lifetime in minutes; default and maximum is 1440 (24 hours)
     *
     * @throws ErrorException when the credentials are rejected (E001, E002) or the API returns another error
     */
    public function login(
        string $vendorId,
        string $ekp,
        string $secret,
        string $password,
        ?string $vendorSubId = null,
        ?int $tokenDuration = null,
    ): LoginResponse {
        $requestBody = [
            'vendorID' => $vendorId,
            'ekp' => $ekp,
            'secret' => $secret,
            'password' => $password,
        ];
        if ($vendorSubId !== null && $vendorSubId !== '') {
            $requestBody['vendorSubID'] = $vendorSubId;
        }
        if ($tokenDuration !== null) {
            $requestBody['tokenDuration'] = $tokenDuration;
        }

        $body = $this->post('/api/Login', $requestBody);

        return LoginResponse::fromArray(Json::decodeObject($body));
    }

    /**
     * Request an SMS code to the customer's registered mobile number, needed for setPassword().
     *
     * @return string Raw response body of the API
     */
    public function smsRequest(string $vendorId, string $ekp): string
    {
        return $this->post('/api/Login/smsRequest', ['vendorID' => $vendorId, 'ekp' => $ekp]);
    }

    /**
     * Set a new password using the SMS code from smsRequest().
     *
     * @return string The secret to use for login()
     */
    public function setPassword(string $vendorId, string $ekp, string $newPassword, string $smsCode): string
    {
        return $this->post('/api/Login/setPassword', [
            'vendorID' => $vendorId,
            'ekp' => $ekp,
            'newPassword' => $newPassword,
            'smsCode' => $smsCode,
        ]);
    }

    /**
     * Availability status of the API.
     *
     * The API answers with an Error object whose code is I501 (OK), W501 (maintenance
     * announced) or E501 (inactive). Calls must be at least 5 seconds apart.
     */
    public function healthCheck(): Error
    {
        try {
            $response = $this->getHttpClient()->request('GET', '/api/Login/HealthCheck');
        } catch (ClientException $e) {
            throw ErrorException::fromClientException($e);
        }

        return Error::fromArray(Json::decodeObject($response->getBody()->getContents()));
    }

    /**
     * @param array<string, mixed> $requestBody
     *
     * @throws ErrorException
     */
    private function post(string $uri, array $requestBody): string
    {
        try {
            $response = $this->getHttpClient()->request('POST', $uri, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => Json::encode($requestBody),
            ]);
        } catch (ClientException $e) {
            throw ErrorException::fromClientException($e);
        }

        return $response->getBody()->getContents();
    }

    private function getHttpClient(): ClientInterface
    {
        return $this->httpClient ?? $this->createHttpClient(['base_uri' => Letter::API_ENDPOINT]);
    }

    /**
     * Create the Guzzle client used when none was injected. Override to add
     * middleware, timeouts or a different base URI.
     *
     * @param array<string, mixed> $config Guzzle client configuration
     */
    protected function createHttpClient(array $config): ClientInterface
    {
        return new HttpClient($config);
    }
}

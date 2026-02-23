<?php

declare(strict_types = 1);

namespace MetabytesSRO\EPost\Api;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Client\ClientInterface;

/**
 * API errors (4xx) are converted to ErrorException. Timeouts and connection failures
 * (ConnectException, RequestException) are not caught—callers should handle these.
 */
class Login
{
    public function __construct(
        private readonly ?ClientInterface $httpClient = null,
    ) {
    }

    private function getHttpClient(): ClientInterface
    {
        return $this->httpClient ?? new HttpClient(['base_uri' => Letter::API_ENDPOINT]);
    }

    public function login(string $vendorId, string $ekp, string $secret, string $password): LoginResponse
    {
        $requestBody    = [
            'vendorID' => $vendorId,
            'ekp'      => $ekp,
            'secret'   => $secret,
            'password' => $password,
        ];
        $requestOptions = [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($requestBody),
        ];

        try {
            $response = $this->getHttpClient()
                ->request('POST', '/api/Login', $requestOptions)
            ;
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }

        return LoginResponse::fromArray(json_decode($response->getBody()->getContents(), true) ?? []);
    }

    public function smsRequest(string $vendorId, string $ekp): string
    {
        $requestBody    = ['vendorID' => $vendorId, 'ekp' => $ekp];
        $requestOptions = [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($requestBody),
        ];

        try {
            $response = $this->getHttpClient()
                ->request('POST', '/api/Login/smsRequest', $requestOptions)
            ;
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }

        return $response->getBody()->getContents();
    }

    public function setPassword(string $vendorId, string $ekp, string $newPassword, string $smsCode): string
    {
        $requestBody    = [
            'vendorID'    => $vendorId,
            'ekp'         => $ekp,
            'newPassword' => $newPassword,
            'smsCode'     => $smsCode,
        ];
        $requestOptions = [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($requestBody),
        ];

        try {
            $response = $this->getHttpClient()
                ->request('POST', '/api/Login/setPassword', $requestOptions)
            ;
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }

        return $response->getBody()->getContents();
    }

    protected function throwErrorException(ClientException $e): never
    {
        $body = $e->getResponse()->getBody()->getContents();
        throw new Exception\ErrorException(
            Error::fromArray(json_decode($body, true) ?? []),
        );
    }
}

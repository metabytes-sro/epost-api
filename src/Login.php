<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use MetabytesSRO\EPost\Api\Auth\Credentials;
use MetabytesSRO\EPost\Api\Exception\ApiException;
use MetabytesSRO\EPost\Api\Exception\TransportException;
use MetabytesSRO\EPost\Api\Http\Transport;

/**
 * Authentication endpoints of the E-POSTBUSINESS API (/api/Login).
 *
 * Usually you do not call login() yourself: EPostClient::withCredentials() does
 * it on demand through Auth\CredentialsTokenProvider. The other methods cover
 * the one-time account setup and the availability check.
 */
final readonly class Login
{
    public function __construct(
        private Transport $transport = new Transport(),
    ) {}

    /**
     * Request a JSON Web Token for the other endpoints of the API.
     *
     * @throws Exception\AuthenticationException when the credentials are rejected (E001, E002)
     * @throws ApiException for other API errors
     * @throws TransportException
     */
    public function login(Credentials $credentials): LoginResponse
    {
        $response = $this->transport->request('POST', '/api/Login', null, $credentials->toArray());

        return LoginResponse::fromArray(Json::decodeObject((string) $response->getBody()));
    }

    /**
     * Request an SMS code to the customer's registered mobile number, needed for setPassword().
     *
     * @return string Raw response body of the API
     * @throws ApiException
     * @throws TransportException
     */
    public function smsRequest(string $vendorId, string $ekp): string
    {
        $response = $this->transport->request('POST', '/api/Login/smsRequest', null, [
            'vendorID' => $vendorId,
            'ekp' => $ekp,
        ]);

        return (string) $response->getBody();
    }

    /**
     * Set a new password using the SMS code from smsRequest().
     *
     * @return string The secret to use in Credentials
     * @throws ApiException
     * @throws TransportException
     */
    public function setPassword(string $vendorId, string $ekp, string $newPassword, string $smsCode): string
    {
        $response = $this->transport->request('POST', '/api/Login/setPassword', null, [
            'vendorID' => $vendorId,
            'ekp' => $ekp,
            'newPassword' => $newPassword,
            'smsCode' => $smsCode,
        ]);

        return (string) $response->getBody();
    }

    /**
     * Availability status of the API.
     *
     * The API answers with an Error object whose code is I501 (OK), W501
     * (maintenance announced) or E501 (inactive). Calls must be at least 5
     * seconds apart.
     *
     * @throws ApiException
     * @throws TransportException
     */
    public function healthCheck(): Error
    {
        $response = $this->transport->request('GET', '/api/Login/HealthCheck');

        return Error::fromArray(Json::decodeObject((string) $response->getBody()));
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Auth;

use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\Validate;

/**
 * Login credentials for the E-POST API.
 */
final readonly class Credentials
{
    public const int MAX_TOKEN_DURATION_MINUTES = 1440;

    /**
     * @param string $vendorId DPAG identifier of the software vendor
     * @param string $ekp DPAG identifier of the customer (10 characters)
     * @param string $secret Secret returned when the password was set (Login::setPassword())
     * @param string $password Password chosen by the customer
     * @param string|null $vendorSubId Optional partner-managed customer identifier
     * @param int|null $tokenDuration Optional token lifetime in minutes; default and maximum is 1440 (24 hours)
     *
     * @throws ValidationException
     */
    public function __construct(
        public string $vendorId,
        public string $ekp,
        public string $secret,
        public string $password,
        public ?string $vendorSubId = null,
        public ?int $tokenDuration = null,
    ) {
        Validate::notBlank('vendorId', $vendorId);
        Validate::notBlank('ekp', $ekp);
        Validate::notBlank('secret', $secret);
        Validate::notBlank('password', $password);
        Validate::maxLength('ekp', $ekp, 10);
        Validate::maxLength('secret', $secret, 100);
        Validate::maxLength('password', $password, 100);
        Validate::maxLength('vendorSubId', $vendorSubId, 100);
        if ($tokenDuration !== null && ($tokenDuration < 1 || $tokenDuration > self::MAX_TOKEN_DURATION_MINUTES)) {
            throw new ValidationException(sprintf('tokenDuration must be between 1 and %d minutes', self::MAX_TOKEN_DURATION_MINUTES));
        }
    }

    /**
     * Request body for POST /api/Login.
     *
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        $body = [
            'vendorID' => $this->vendorId,
            'ekp' => $this->ekp,
            'secret' => $this->secret,
            'password' => $this->password,
        ];
        if ($this->vendorSubId !== null && $this->vendorSubId !== '') {
            $body['vendorSubID'] = $this->vendorSubId;
        }
        if ($this->tokenDuration !== null) {
            $body['tokenDuration'] = $this->tokenDuration;
        }

        return $body;
    }
}

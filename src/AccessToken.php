<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

class AccessToken
{
    private ?string $cachedToken = null;

    public function __construct(
        private readonly string $vendorId,
        private readonly string $ekp,
        private readonly string $secret,
        private readonly string $password,
    ) {
    }

    public function getToken(): string
    {
        if ($this->cachedToken === null) {
            $response = (new Login())->login($this->vendorId, $this->ekp, $this->secret, $this->password);
            $this->cachedToken = $response->getToken();
        }
        return $this->cachedToken;
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Lazily obtains and caches a bearer token for the E-POST API.
 *
 * The token is requested on the first call to getToken() and reused afterwards.
 * Tokens are valid for 24 hours by default; call refresh() or create a new
 * AccessToken when the API answers with E101 "token expired".
 */
class AccessToken
{
    private ?string $cachedToken = null;

    public function __construct(
        private readonly string $vendorId,
        private readonly string $ekp,
        private readonly string $secret,
        private readonly string $password,
        private ?Login $login = null,
    ) {}

    /**
     * Wrap a token obtained elsewhere, e.g. through an OAuth2 provider or a cache.
     */
    public static function fromToken(string $token): self
    {
        $instance = new self('', '', '', '');
        $instance->cachedToken = $token;

        return $instance;
    }

    /**
     * @throws Exception\ErrorException when the login is rejected by the API
     *
     * @phpstan-impure
     */
    public function getToken(): string
    {
        if ($this->cachedToken === null) {
            $response = $this->getLogin()->login(
                $this->vendorId,
                $this->ekp,
                $this->secret,
                $this->password,
            );
            $this->cachedToken = $response->getToken();
        }

        return $this->cachedToken;
    }

    /**
     * Discard the cached token so the next getToken() call logs in again.
     */
    public function refresh(): self
    {
        $this->cachedToken = null;

        return $this;
    }

    /**
     * The Login used to obtain tokens: the injected one, or a default instance.
     */
    protected function getLogin(): Login
    {
        return $this->login ??= new Login();
    }
}

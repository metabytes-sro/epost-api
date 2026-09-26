<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Auth;

use DateInterval;
use MetabytesSRO\EPost\Api\Auth\CachedTokenProvider;
use MetabytesSRO\EPost\Api\Auth\Credentials;
use MetabytesSRO\EPost\Api\Auth\CredentialsTokenProvider;
use MetabytesSRO\EPost\Api\Auth\StaticTokenProvider;
use MetabytesSRO\EPost\Api\Exception\AuthenticationException;
use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\Login;
use MetabytesSRO\EPost\Api\Tests\ApiTestCase;
use Psr\SimpleCache\CacheInterface;

class AuthTest extends ApiTestCase
{
    public function testCredentialsToArray(): void
    {
        $credentials = new Credentials('vendor', '1234567890', 'secret', 'password');

        self::assertSame([
            'vendorID' => 'vendor',
            'ekp' => '1234567890',
            'secret' => 'secret',
            'password' => 'password',
        ], $credentials->toArray());
    }

    public function testCredentialsWithOptionalFields(): void
    {
        $credentials = new Credentials('vendor', '1234567890', 'secret', 'password', 'sub-1', 60);

        self::assertSame('sub-1', $credentials->toArray()['vendorSubID']);
        self::assertSame(60, $credentials->toArray()['tokenDuration']);
        self::assertArrayNotHasKey('vendorSubID', (new Credentials('v', 'e', 's', 'p', ''))->toArray());
    }

    public function testCredentialsValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('ekp exceeds the maximum length of 10');
        new Credentials('vendor', '12345678901', 'secret', 'password');
    }

    public function testCredentialsRequirePassword(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('password must not be empty');
        new Credentials('vendor', '1234567890', 'secret', '');
    }

    public function testCredentialsTokenDurationBounds(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tokenDuration must be between 1 and 1440');
        new Credentials('vendor', '1234567890', 'secret', 'password', null, 1441);
    }

    public function testStaticTokenProvider(): void
    {
        $provider = new StaticTokenProvider('abc');

        self::assertSame('abc', $provider->getToken());
        $provider->invalidate();
        self::assertSame('abc', $provider->getToken());
    }

    public function testCredentialsTokenProviderLogsInOnceAndCaches(): void
    {
        $login = new Login($this->transport(
            self::jsonResponse(['token' => 'first']),
            self::jsonResponse(['token' => 'second']),
        ));
        $provider = new CredentialsTokenProvider(new Credentials('vendor', '1234567890', 'secret', 'password'), $login);

        self::assertSame('first', $provider->getToken());
        $provider->getToken();
        self::assertCount(1, $this->requests);
        $this->assertLastRequest('POST', '/api/Login', [], [
            'vendorID' => 'vendor',
            'ekp' => '1234567890',
            'secret' => 'secret',
            'password' => 'password',
        ]);

        $provider->invalidate();
        self::assertSame('second', $provider->getToken());
        self::assertCount(2, $this->requests);
    }

    public function testCredentialsTokenProviderPropagatesLoginErrors(): void
    {
        $login = new Login($this->transport(self::errorResponse(401, 'E001', 'Ungültige Zugangsdaten')));
        $provider = new CredentialsTokenProvider(new Credentials('vendor', '1234567890', 'secret', 'wrong'), $login);

        $this->expectException(AuthenticationException::class);
        $provider->getToken();
    }

    public function testCredentialsTokenProviderUsesDefaultLogin(): void
    {
        $provider = new CredentialsTokenProvider(new Credentials('vendor', '1234567890', 'secret', 'password'));

        $provider->invalidate();

        self::assertSame([], $this->requests, 'constructing the provider must not log in');
    }

    public function testCachedTokenProviderStoresAndReusesToken(): void
    {
        $cache = new ArrayCache();
        $login = new Login($this->transport(self::jsonResponse(['token' => 'from-login'])));
        $inner = new CredentialsTokenProvider(new Credentials('vendor', '1234567890', 'secret', 'password'), $login);
        $provider = new CachedTokenProvider($inner, $cache, 'key', 120);

        self::assertSame('from-login', $provider->getToken());
        self::assertSame('from-login', $cache->get('key'));
        self::assertSame(120, $cache->ttl['key']);

        $fresh = new CachedTokenProvider(new StaticTokenProvider('unused'), $cache, 'key');
        self::assertSame('from-login', $fresh->getToken(), 'the cached token wins over the inner provider');
        self::assertCount(1, $this->requests);
    }

    public function testCachedTokenProviderInvalidateClearsCacheAndInner(): void
    {
        $cache = new ArrayCache();
        $cache->set('epost_api_token', 'stale');
        $login = new Login($this->transport(self::jsonResponse(['token' => 'renewed'])));
        $inner = new CredentialsTokenProvider(new Credentials('vendor', '1234567890', 'secret', 'password'), $login);
        $provider = new CachedTokenProvider($inner, $cache);

        self::assertSame('stale', $provider->getToken());
        $provider->invalidate();
        self::assertFalse($cache->has('epost_api_token'));
        self::assertSame('renewed', $provider->getToken());
        self::assertSame(CachedTokenProvider::DEFAULT_TTL_SECONDS, $cache->ttl['epost_api_token']);
    }

    public function testCachedTokenProviderIgnoresEmptyCacheValue(): void
    {
        $cache = new ArrayCache();
        $cache->set('key', '');
        $provider = new CachedTokenProvider(new StaticTokenProvider('real'), $cache, 'key');

        self::assertSame('real', $provider->getToken());
    }
}

/**
 * Minimal in-memory PSR-16 cache for the tests.
 */
final class ArrayCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $items = [];

    /** @var array<string, DateInterval|int|null> */
    public array $ttl = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->items[$key] = $value;
        $this->ttl[$key] = $ttl;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->items[$key], $this->ttl[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];
        $this->ttl = [];

        return true;
    }

    /**
     * @param iterable<string> $keys
     *
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}

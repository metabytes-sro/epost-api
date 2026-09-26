<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\Exception\ErrorException;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Login;
use RuntimeException;

class LoginTest extends ApiTestCase
{
    public function testLoginPostsCredentialsAndReturnsToken(): void
    {
        $login = new Login($this->mockClient(self::jsonResponse(['token' => 'jwt-token-abc'])));

        $response = $login->login('vendor', '1234567890', 'secret', 'password');

        self::assertSame('jwt-token-abc', $response->getToken());
        $this->assertLastRequest('POST', '/api/Login', [], [
            'vendorID' => 'vendor',
            'ekp' => '1234567890',
            'secret' => 'secret',
            'password' => 'password',
        ]);
    }

    public function testLoginSendsOptionalFields(): void
    {
        $login = new Login($this->mockClient(self::jsonResponse(['token' => 't'])));

        $login->login('vendor', '1234567890', 'secret', 'password', 'sub-1', 60);

        $this->assertLastRequest('POST', '/api/Login', [], [
            'vendorID' => 'vendor',
            'ekp' => '1234567890',
            'secret' => 'secret',
            'password' => 'password',
            'vendorSubID' => 'sub-1',
            'tokenDuration' => 60,
        ]);
    }

    public function testLoginOmitsEmptyVendorSubId(): void
    {
        $login = new Login($this->mockClient(self::jsonResponse(['token' => 't'])));

        $login->login('vendor', '1234567890', 'secret', 'password', '');

        self::assertArrayNotHasKey('vendorSubID', $this->lastRequestJson());
    }

    public function testLoginRejectedCredentials(): void
    {
        $login = new Login($this->mockClient(self::errorResponse(401, 'E001', 'Ungültige Zugangsdaten')));

        try {
            $login->login('vendor', '1234567890', 'secret', 'wrong');
            self::fail('Expected ErrorException');
        } catch (ErrorException $e) {
            self::assertSame('E001', $e->getCode());
            self::assertTrue($e->isAuthenticationError());
            self::assertSame('Error', $e->getLevel());
        }
    }

    public function testSmsRequestReturnsResponseBody(): void
    {
        $login = new Login($this->mockClient(new Response(202, [], 'SMS sent')));

        $result = $login->smsRequest('vendor', '1234567890');

        self::assertSame('SMS sent', $result);
        $this->assertLastRequest('POST', '/api/Login/smsRequest', [], ['vendorID' => 'vendor', 'ekp' => '1234567890']);
    }

    public function testSetPasswordReturnsSecret(): void
    {
        $login = new Login($this->mockClient(new Response(200, [], 'new-secret-key')));

        $result = $login->setPassword('vendor', '1234567890', 'newPass123', '123456');

        self::assertSame('new-secret-key', $result);
        $this->assertLastRequest('POST', '/api/Login/setPassword', [], [
            'vendorID' => 'vendor',
            'ekp' => '1234567890',
            'newPassword' => 'newPass123',
            'smsCode' => '123456',
        ]);
    }

    public function testHealthCheckReturnsStatusAsError(): void
    {
        $login = new Login($this->mockClient(self::jsonResponse([
            'level' => 'Info',
            'code' => 'I501',
            'description' => 'API-Status: OK',
            'date' => '2026-09-26T10:00:00',
        ])));

        $status = $login->healthCheck();

        self::assertSame('I501', $status->getCode());
        self::assertTrue($status->isInfo());
        self::assertSame('2026-09-26T10:00:00', $status->getDate());
        $this->assertLastRequest('GET', '/api/Login/HealthCheck', []);
    }

    public function testHealthCheckRateLimited(): void
    {
        $login = new Login($this->mockClient(self::errorResponse(429, 'E322', 'Zu häufige Abfragen')));

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Zu häufige Abfragen');
        $login->healthCheck();
    }

    public function testDefaultHttpClientTargetsApiEndpoint(): void
    {
        $login = new class extends Login {
            /** @var array<string, mixed> */
            public array $config = [];
            public ?ClientInterface $created = null;

            /**
             * @param array<string, mixed> $config
             */
            protected function createHttpClient(array $config): ClientInterface
            {
                $this->config = $config;
                $this->created = parent::createHttpClient($config);

                // Never let the test reach the network.
                throw new RuntimeException('stop');
            }
        };

        try {
            $login->smsRequest('vendor', '1234567890');
        } catch (RuntimeException $e) {
            self::assertSame('stop', $e->getMessage());
        }

        self::assertInstanceOf(Client::class, $login->created);
        self::assertSame(['base_uri' => Letter::API_ENDPOINT], $login->config);
    }
}

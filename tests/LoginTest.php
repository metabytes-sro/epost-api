<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\Auth\Credentials;
use MetabytesSRO\EPost\Api\Exception\AuthenticationException;
use MetabytesSRO\EPost\Api\Exception\RateLimitException;
use MetabytesSRO\EPost\Api\Login;

class LoginTest extends ApiTestCase
{
    public function testLoginPostsCredentialsAndReturnsToken(): void
    {
        $login = new Login($this->transport(self::jsonResponse(['token' => 'jwt-token-abc'])));

        $response = $login->login(new Credentials('vendor', '1234567890', 'secret', 'password', 'sub', 30));

        self::assertSame('jwt-token-abc', $response->token);
        $this->assertLastRequest('POST', '/api/Login', [], [
            'vendorID' => 'vendor',
            'ekp' => '1234567890',
            'secret' => 'secret',
            'password' => 'password',
            'vendorSubID' => 'sub',
            'tokenDuration' => 30,
        ]);
        self::assertSame('', $this->lastRequest()->getHeaderLine('Authorization'));
    }

    public function testLoginRejectedCredentials(): void
    {
        $login = new Login($this->transport(self::errorResponse(401, 'E001', 'Ungültige Zugangsdaten')));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Ungültige Zugangsdaten');
        $login->login(new Credentials('vendor', '1234567890', 'secret', 'wrong'));
    }

    public function testSmsRequestReturnsResponseBody(): void
    {
        $login = new Login($this->transport(new Response(202, [], 'SMS sent')));

        self::assertSame('SMS sent', $login->smsRequest('vendor', '1234567890'));
        $this->assertLastRequest('POST', '/api/Login/smsRequest', [], ['vendorID' => 'vendor', 'ekp' => '1234567890']);
    }

    public function testSetPasswordReturnsSecret(): void
    {
        $login = new Login($this->transport(new Response(200, [], 'new-secret-key')));

        self::assertSame('new-secret-key', $login->setPassword('vendor', '1234567890', 'newPass123', '123456'));
        $this->assertLastRequest('POST', '/api/Login/setPassword', [], [
            'vendorID' => 'vendor',
            'ekp' => '1234567890',
            'newPassword' => 'newPass123',
            'smsCode' => '123456',
        ]);
    }

    public function testHealthCheckReturnsStatusAsError(): void
    {
        $login = new Login($this->transport(self::jsonResponse([
            'level' => 'Info',
            'code' => 'I501',
            'description' => 'API-Status: OK',
            'date' => '2026-09-26T10:00:00',
        ])));

        $status = $login->healthCheck();

        self::assertSame('I501', $status->code);
        self::assertTrue($status->isInfo());
        self::assertSame('2026-09-26', $status->date?->format('Y-m-d'));
        $this->assertLastRequest('GET', '/api/Login/HealthCheck', []);
    }

    public function testHealthCheckRateLimited(): void
    {
        $login = new Login($this->transport(self::errorResponse(429, 'E322', 'Zu häufige Abfragen')));

        $this->expectException(RateLimitException::class);
        $login->healthCheck();
    }

    public function testDefaultTransportDoesNotTouchTheNetwork(): void
    {
        $login = new Login();

        self::assertSame([], $this->requests, 'constructing ' . $login::class . ' must not send anything');
    }
}

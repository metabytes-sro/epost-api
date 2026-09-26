<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\AccessToken;
use MetabytesSRO\EPost\Api\Exception\ErrorException;
use MetabytesSRO\EPost\Api\Login;

class AccessTokenTest extends ApiTestCase
{
    public function testGetTokenLogsInOnceAndCachesTheToken(): void
    {
        $login = new Login($this->mockClient(
            self::jsonResponse(['token' => 'first']),
            self::jsonResponse(['token' => 'second']),
        ));
        $token = new AccessToken('vendor', '1234567890', 'secret', 'password', $login);

        self::assertSame('first', $token->getToken());
        $token->getToken();
        self::assertCount(1, $this->requests);
        $this->assertLastRequest('POST', '/api/Login', [], [
            'vendorID' => 'vendor',
            'ekp' => '1234567890',
            'secret' => 'secret',
            'password' => 'password',
        ]);
    }

    public function testRefreshLogsInAgain(): void
    {
        $login = new Login($this->mockClient(
            self::jsonResponse(['token' => 'first']),
            self::jsonResponse(['token' => 'second']),
        ));
        $token = new AccessToken('vendor', '1234567890', 'secret', 'password', $login);

        self::assertSame('first', $token->getToken());
        self::assertSame($token, $token->refresh());
        self::assertSame('second', $token->getToken());
        self::assertCount(2, $this->requests);
    }

    public function testFromTokenNeverLogsIn(): void
    {
        $token = AccessToken::fromToken('pre-obtained');

        self::assertSame('pre-obtained', $token->getToken());
        self::assertSame([], $this->requests);
    }

    public function testLoginErrorPropagates(): void
    {
        $login = new Login($this->mockClient(self::errorResponse(401, 'E001', 'Ungültige Zugangsdaten')));
        $token = new AccessToken('vendor', '1234567890', 'secret', 'password', $login);

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Ungültige Zugangsdaten');
        $token->getToken();
    }

    public function testDefaultLoginIsCreatedWhenNoneInjected(): void
    {
        $token = new class ('v', 'e', 's', 'p') extends AccessToken {
            public function exposeLogin(): Login
            {
                return $this->getLogin();
            }
        };

        $login = $token->exposeLogin();

        self::assertSame(Login::class, $login::class);
        self::assertSame($login, $token->exposeLogin(), 'the default Login is created once');
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\Login;
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    public function testLoginReturnsLoginResponse(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['token' => 'jwt-token-abc'])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => 'https://api.epost.docuguide.com',
        ]);
        $login = new Login($client);

        $response = $login->login('vendor', '1234567890', 'secret', 'password');

        $this->assertSame('jwt-token-abc', $response->getToken());
    }

    public function testSmsRequestReturnsResponseBody(): void
    {
        $mock = new MockHandler([
            new Response(202, [], 'SMS sent'),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => 'https://api.epost.docuguide.com',
        ]);
        $login = new Login($client);

        $result = $login->smsRequest('vendor', '1234567890');

        $this->assertSame('SMS sent', $result);
    }

    public function testSetPasswordReturnsSecret(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'new-secret-key'),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => 'https://api.epost.docuguide.com',
        ]);
        $login = new Login($client);

        $result = $login->setPassword('vendor', '1234567890', 'newPass123', '123456');

        $this->assertSame('new-secret-key', $result);
    }

    public function testLoginWithoutInjectedClientCreatesDefault(): void
    {
        $login = new Login();
        $this->assertNotNull($login);
    }
}

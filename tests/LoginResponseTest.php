<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\LoginResponse;
use PHPUnit\Framework\TestCase;

class LoginResponseTest extends TestCase
{
    public function testFromArray(): void
    {
        $response = LoginResponse::fromArray(['token' => 'jwt-token-123']);
        $this->assertSame('jwt-token-123', $response->getToken());
    }

    public function testFromArrayWithEmptyToken(): void
    {
        $response = LoginResponse::fromArray([]);
        $this->assertSame('', $response->getToken());
    }
}

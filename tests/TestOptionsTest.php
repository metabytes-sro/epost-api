<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\TestOptions;
use PHPUnit\Framework\TestCase;

class TestOptionsTest extends TestCase
{
    public function testToArray(): void
    {
        self::assertSame(
            ['testFlag' => true, 'testEMail' => 'test@example.com', 'testShowRestrictedArea' => false],
            (new TestOptions('test@example.com'))->toArray(),
        );
        self::assertSame(
            ['testFlag' => true, 'testEMail' => 'test@example.com', 'testShowRestrictedArea' => true],
            (new TestOptions('test@example.com', true))->toArray(),
        );
    }

    public function testEmailMustNotBeBlank(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('email must not be empty');
        new TestOptions('');
    }

    public function testEmailMustBeValid(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not a valid email address');
        new TestOptions('not-an-email');
    }

    public function testEmailMaxLength(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('email exceeds the maximum length of 100');
        new TestOptions(str_repeat('a', 95) . '@ex.com');
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\Error;
use MetabytesSRO\EPost\Api\LetterStatusError;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function testFromArray(): void
    {
        $error = Error::fromArray([
            'level' => 'Error',
            'code' => 'E101',
            'description' => 'Ungültiges Token - Abgelaufen',
            'date' => '2026-01-01T00:00:00',
        ]);

        self::assertSame('Error', $error->getLevel());
        self::assertSame('E101', $error->getCode());
        self::assertSame('Ungültiges Token - Abgelaufen', $error->getDescription());
        self::assertSame('2026-01-01T00:00:00', $error->getDate());
        self::assertTrue($error->isError());
        self::assertFalse($error->isWarning());
        self::assertFalse($error->isInfo());
    }

    public function testFromArrayDefaults(): void
    {
        $error = Error::fromArray([]);

        self::assertSame('', $error->getLevel());
        self::assertSame('', $error->getCode());
        self::assertSame('', $error->getDescription());
        self::assertNull($error->getDate());
        self::assertFalse($error->isError());
    }

    public function testLevelChecksAreCaseInsensitive(): void
    {
        self::assertTrue((new Error('warning', 'W201', ''))->isWarning());
        self::assertTrue((new Error('INFO', 'I501', ''))->isInfo());
        self::assertTrue((new Error('error', 'E399', ''))->isError());
    }

    public function testLetterStatusErrorIsAnError(): void
    {
        $error = LetterStatusError::fromArray(['level' => 'Info', 'code' => 'I101', 'description' => 'PDF -> PDFA']);

        self::assertSame(LetterStatusError::class, $error::class);
        self::assertContains(Error::class, class_parents($error));
        self::assertTrue($error->isInfo());
    }
}

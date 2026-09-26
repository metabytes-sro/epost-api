<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\LetterSendResult;
use MetabytesSRO\EPost\Api\LoginResponse;
use MetabytesSRO\EPost\Api\QueueResult;
use MetabytesSRO\EPost\Api\TestResult;
use PHPUnit\Framework\TestCase;

class ResultsTest extends TestCase
{
    public function testLetterSendResult(): void
    {
        $result = LetterSendResult::fromArray(['letterID' => 12345, 'fileName' => 'test.pdf']);

        self::assertSame(12345, $result->letterId);
        self::assertSame('test.pdf', $result->fileName);

        $minimal = LetterSendResult::fromArray([]);
        self::assertSame(0, $minimal->letterId);
        self::assertNull($minimal->fileName);
    }

    public function testQueueResult(): void
    {
        $result = QueueResult::fromArray(['letterID' => 100, 'successful' => true, 'message' => 'Abruch/Freigabe der Sendung war erfolgreich']);

        self::assertSame(100, $result->letterId);
        self::assertTrue($result->successful);
        self::assertSame('Abruch/Freigabe der Sendung war erfolgreich', $result->message);

        $minimal = QueueResult::fromArray(['message' => 'Fehler']);
        self::assertNull($minimal->letterId);
        self::assertNull($minimal->successful);
        self::assertSame('Fehler', $minimal->message);
        self::assertSame('', QueueResult::fromArray([])->message);
    }

    public function testTestResult(): void
    {
        $result = TestResult::fromArray(['letterID' => 5, 'fileName' => 'x.pdf', 'data' => base64_encode('%PDF-1.4 x')]);

        self::assertSame(5, $result->letterId);
        self::assertSame('x.pdf', $result->fileName);
        self::assertSame('%PDF-1.4 x', $result->pdf());

        self::assertNull(TestResult::fromArray([])->pdf());
        self::assertNull(TestResult::fromArray(['data' => ''])->pdf());
        self::assertNull(TestResult::fromArray(['data' => '%%%'])->pdf());
    }

    public function testLoginResponse(): void
    {
        self::assertSame('jwt', LoginResponse::fromArray(['token' => 'jwt'])->token);
        self::assertSame('', LoginResponse::fromArray([])->token);
    }
}

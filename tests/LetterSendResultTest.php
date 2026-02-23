<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\LetterSendResult;
use PHPUnit\Framework\TestCase;

class LetterSendResultTest extends TestCase
{
    public function testFromArray(): void
    {
        $result = LetterSendResult::fromArray(['letterID' => 12345, 'fileName' => 'test.pdf']);
        $this->assertSame(12345, $result->getLetterId());
        $this->assertSame('test.pdf', $result->getFileName());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $result = LetterSendResult::fromArray(['letterID' => 999]);
        $this->assertSame(999, $result->getLetterId());
        $this->assertNull($result->getFileName());
    }
}

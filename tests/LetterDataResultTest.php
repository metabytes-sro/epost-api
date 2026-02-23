<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\LetterDataResult;
use PHPUnit\Framework\TestCase;

class LetterDataResultTest extends TestCase
{
    public function testFromArray(): void
    {
        $result = LetterDataResult::fromArray([
            'letterID' => 12345,
            'fileName' => 'test.pdf',
            'data' => 'base64encodedcontent',
        ]);
        $this->assertSame(12345, $result->getLetterId());
        $this->assertSame('test.pdf', $result->getFileName());
        $this->assertSame('base64encodedcontent', $result->getData());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $result = LetterDataResult::fromArray([]);
        $this->assertNull($result->getLetterId());
        $this->assertNull($result->getFileName());
        $this->assertNull($result->getData());
    }
}

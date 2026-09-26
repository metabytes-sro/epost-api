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
            'data' => base64_encode('%PDF-1.4 content'),
        ]);

        self::assertSame(12345, $result->getLetterId());
        self::assertSame('test.pdf', $result->getFileName());
        self::assertSame(base64_encode('%PDF-1.4 content'), $result->getData());
        self::assertSame('%PDF-1.4 content', $result->getPdf());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $result = LetterDataResult::fromArray([]);

        self::assertNull($result->getLetterId());
        self::assertNull($result->getFileName());
        self::assertNull($result->getData());
        self::assertNull($result->getPdf());
    }

    public function testGetPdfWithInvalidBase64(): void
    {
        self::assertNull(LetterDataResult::fromArray(['data' => '%%%'])->getPdf());
        self::assertNull(LetterDataResult::fromArray(['data' => ''])->getPdf());
    }
}

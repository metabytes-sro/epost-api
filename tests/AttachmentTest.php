<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\Attachment;
use MetabytesSRO\EPost\Api\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;

class AttachmentTest extends ApiTestCase
{
    public function testFromFile(): void
    {
        $path = $this->createTempFile('.pdf', self::PDF);

        $attachment = Attachment::fromFile($path);

        self::assertSame(basename($path), $attachment->fileName);
        self::assertSame(self::PDF, $attachment->contents);
        self::assertSame(strlen(self::PDF), $attachment->size());
        self::assertSame(chunk_split(base64_encode(self::PDF)), $attachment->base64());
    }

    public function testFromFileWithExplicitName(): void
    {
        $path = $this->createTempFile('.pdf', self::PDF);

        self::assertSame('RE-2024-001.pdf', Attachment::fromFile($path, 'RE-2024-001.pdf')->fileName);
    }

    public function testFromFileMissing(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('does not exist');
        Attachment::fromFile('/nonexistent/file.pdf');
    }

    public function testFromFileNotPdf(): void
    {
        $path = $this->createTempFile('.pdf', 'just text');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('is not a PDF document');
        Attachment::fromFile($path);
    }

    public function testFromFileUnreadable(): void
    {
        $path = $this->createTempFile('.pdf', self::PDF);
        chmod($path, 0o000);
        if (is_readable($path)) {
            chmod($path, 0o644);
            self::markTestSkipped('File permissions are not enforced for this user');
        }

        try {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage('not readable');
            Attachment::fromFile($path);
        } finally {
            chmod($path, 0o644);
        }
    }

    public function testFromString(): void
    {
        $attachment = Attachment::fromString(self::PDF, 'letter_1.pdf');

        self::assertSame('letter_1.pdf', $attachment->fileName);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidNames(): iterable
    {
        yield 'blank' => ['  ', 'fileName must not be empty'];
        yield 'space' => ['my letter.pdf', 'may only contain'];
        yield 'umlaut' => ['brief-ä.pdf', 'may only contain'];
        yield 'slash' => ['a/b.pdf', 'may only contain'];
        yield 'too long' => [str_repeat('a', 197) . '.pdf', 'exceeds the maximum length of 200'];
    }

    #[DataProvider('invalidNames')]
    public function testFileNameValidation(string $name, string $message): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);
        Attachment::fromString(self::PDF, $name);
    }

    public function testAssertMaxSize(): void
    {
        $attachment = Attachment::fromString(self::PDF, 'a.pdf');

        $attachment->assertMaxSize(1000, 'document');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('the API accepts at most 10 bytes');
        $attachment->assertMaxSize(10, 'document');
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Exception;

use LogicException;
use MetabytesSRO\EPost\Api\Error;
use MetabytesSRO\EPost\Api\Exception\EPostException;
use MetabytesSRO\EPost\Api\Exception\ErrorException;
use MetabytesSRO\EPost\Api\Exception\InvalidFileFormat;
use MetabytesSRO\EPost\Api\Exception\InvalidFileFormatException;
use MetabytesSRO\EPost\Api\Exception\InvalidRecipientDataException;
use MetabytesSRO\EPost\Api\Exception\MissingAttachmentException;
use MetabytesSRO\EPost\Api\Exception\MissingAuthorizationTokenException;
use MetabytesSRO\EPost\Api\Exception\MissingEnvelopeException;
use MetabytesSRO\EPost\Api\Exception\MissingPreconditionException;
use MetabytesSRO\EPost\Api\Exception\MissingRecipientException;
use MetabytesSRO\EPost\Api\Exception\MissingReturnAddressException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

class ExceptionHierarchyTest extends TestCase
{
    /**
     * @return iterable<string, array{Throwable}>
     */
    public static function exceptions(): iterable
    {
        yield 'ErrorException' => [new ErrorException(new Error('Error', 'E900', 'x'))];
        yield 'InvalidFileFormat' => [new InvalidFileFormat()];
        yield 'InvalidFileFormatException' => [new InvalidFileFormatException()];
        yield 'InvalidRecipientDataException' => [new InvalidRecipientDataException()];
        yield 'MissingAttachmentException' => [new MissingAttachmentException()];
        yield 'MissingAuthorizationTokenException' => [new MissingAuthorizationTokenException()];
        yield 'MissingEnvelopeException' => [new MissingEnvelopeException()];
        yield 'MissingPreconditionException' => [new MissingPreconditionException()];
        yield 'MissingRecipientException' => [new MissingRecipientException()];
        yield 'MissingReturnAddressException' => [new MissingReturnAddressException()];
    }

    #[DataProvider('exceptions')]
    public function testEveryExceptionImplementsTheMarkerInterface(Throwable $exception): void
    {
        self::assertContains(EPostException::class, class_implements($exception), $exception::class);
    }

    public function testPreconditionExceptionsShareABase(): void
    {
        foreach ([
            MissingAttachmentException::class,
            MissingAuthorizationTokenException::class,
            MissingEnvelopeException::class,
            MissingRecipientException::class,
        ] as $class) {
            self::assertContains(MissingPreconditionException::class, class_parents($class), $class);
        }
        self::assertContains(LogicException::class, class_parents(MissingPreconditionException::class));
    }

    public function testNewFileFormatExceptionExtendsDeprecatedOne(): void
    {
        self::assertContains(InvalidFileFormat::class, class_parents(InvalidFileFormatException::class));
    }
}

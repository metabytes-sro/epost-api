<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Exception;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use MetabytesSRO\EPost\Api\Error;
use MetabytesSRO\EPost\Api\Exception\ApiException;
use MetabytesSRO\EPost\Api\Exception\AuthenticationException;
use MetabytesSRO\EPost\Api\Exception\EPostException;
use MetabytesSRO\EPost\Api\Exception\NotFoundException;
use MetabytesSRO\EPost\Api\Exception\RateLimitException;
use MetabytesSRO\EPost\Api\Exception\TransportException;
use MetabytesSRO\EPost\Api\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class ExceptionHierarchyTest extends TestCase
{
    /**
     * @return iterable<string, array{Throwable}>
     */
    public static function exceptions(): iterable
    {
        $error = new Error('Error', 'E900', 'x');
        yield 'ApiException' => [new ApiException($error)];
        yield 'AuthenticationException' => [new AuthenticationException($error)];
        yield 'NotFoundException' => [new NotFoundException($error)];
        yield 'RateLimitException' => [new RateLimitException($error)];
        yield 'TransportException' => [new TransportException('x')];
        yield 'ValidationException' => [new ValidationException('x')];
    }

    #[DataProvider('exceptions')]
    public function testEveryExceptionImplementsTheMarkerInterface(Throwable $exception): void
    {
        self::assertContains(EPostException::class, class_implements($exception), $exception::class);
    }

    public function testBaseClasses(): void
    {
        self::assertContains(RuntimeException::class, class_parents(ApiException::class));
        self::assertContains(ApiException::class, class_parents(AuthenticationException::class));
        self::assertContains(ApiException::class, class_parents(NotFoundException::class));
        self::assertContains(ApiException::class, class_parents(RateLimitException::class));
        self::assertContains(RuntimeException::class, class_parents(TransportException::class));
        self::assertContains(InvalidArgumentException::class, class_parents(ValidationException::class));
    }

    public function testTransportExceptionFromClientException(): void
    {
        $cause = new ConnectException('timed out', new Request('GET', '/'));

        $exception = TransportException::fromClientException($cause);

        self::assertSame('The E-POST API could not be reached: timed out', $exception->getMessage());
        self::assertSame($cause, $exception->getPrevious());
    }
}

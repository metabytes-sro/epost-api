<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Exception;

use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\Error;
use MetabytesSRO\EPost\Api\ErrorCode;
use MetabytesSRO\EPost\Api\Exception\ApiException;
use MetabytesSRO\EPost\Api\Exception\AuthenticationException;
use MetabytesSRO\EPost\Api\Exception\NotFoundException;
use MetabytesSRO\EPost\Api\Exception\RateLimitException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ApiExceptionTest extends TestCase
{
    public function testWrapsErrorObject(): void
    {
        $error = new Error('Error', 'E301', 'Kein PDF-Format erkannt');
        $previous = new RuntimeException('cause');
        $response = new Response(400);

        $exception = new ApiException($error, 400, $response, $previous);

        self::assertSame($error, $exception->getError());
        self::assertSame(ErrorCode::E301, $exception->getErrorCode());
        self::assertSame('Kein PDF-Format erkannt', $exception->getMessage());
        self::assertSame('E301', $exception->getCode());
        self::assertSame(400, $exception->getStatusCode());
        self::assertSame($response, $exception->getResponse());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(ApiException::class . ": [Error] [E301]: Kein PDF-Format erkannt\n", (string) $exception);
    }

    public function testDefaults(): void
    {
        $exception = new ApiException(new Error('Error', 'X999', 'unknown'));

        self::assertSame(0, $exception->getStatusCode());
        self::assertNull($exception->getResponse());
        self::assertNull($exception->getErrorCode());
    }

    /**
     * @return iterable<string, array{int, string, class-string<ApiException>}>
     */
    public static function classification(): iterable
    {
        yield '401' => [401, 'E399', AuthenticationException::class];
        yield 'E001' => [400, 'E001', AuthenticationException::class];
        yield 'E002' => [400, 'E002', AuthenticationException::class];
        yield 'E101' => [400, 'E101', AuthenticationException::class];
        yield '404' => [404, 'E399', NotFoundException::class];
        yield 'E201' => [400, 'E201', NotFoundException::class];
        yield '429' => [429, 'E399', RateLimitException::class];
        yield 'E322' => [400, 'E322', RateLimitException::class];
        yield 'E003' => [400, 'E003', RateLimitException::class];
        yield 'other' => [400, 'E301', ApiException::class];
        yield 'server' => [500, 'E900', ApiException::class];
    }

    /**
     * @param class-string<ApiException> $expectedClass
     */
    #[DataProvider('classification')]
    public function testFromResponseChoosesTheSubclass(int $status, string $code, string $expectedClass): void
    {
        $body = json_encode(['level' => 'Error', 'code' => $code, 'description' => 'd', 'date' => '2026-01-01T10:00:00'], JSON_THROW_ON_ERROR);

        $exception = ApiException::fromResponse(new Response($status, [], $body));

        self::assertSame($expectedClass, $exception::class);
        self::assertSame($code, $exception->getCode());
        self::assertSame($status, $exception->getStatusCode());
        self::assertSame('2026-01-01', $exception->getError()->date?->format('Y-m-d'));
    }

    public function testFromResponseWithoutErrorBody(): void
    {
        $exception = ApiException::fromResponse(new Response(404, [], ''));

        self::assertSame(NotFoundException::class, $exception::class);
        self::assertSame('HTTP404', $exception->getCode());
        self::assertSame('Not Found', $exception->getMessage());
        self::assertTrue($exception->getError()->isError());
    }

    public function testFromResponseWithPlainTextBody(): void
    {
        $previous = new RuntimeException('x');

        $exception = ApiException::fromResponse(new Response(502, [], 'upstream down'), $previous);

        self::assertSame(ApiException::class, $exception::class);
        self::assertSame('HTTP502', $exception->getCode());
        self::assertSame('Bad Gateway upstream down', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}

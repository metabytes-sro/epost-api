<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Exception;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\Error;
use MetabytesSRO\EPost\Api\Exception\EPostException;
use MetabytesSRO\EPost\Api\Exception\ErrorException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ErrorExceptionTest extends TestCase
{
    public function testWrapsErrorObject(): void
    {
        $error = new Error('Error', 'E301', 'Kein PDF-Format erkannt');
        $previous = new RuntimeException('cause');

        $exception = new ErrorException($error, 400, $previous);

        self::assertContains(EPostException::class, class_implements($exception));
        self::assertContains(RuntimeException::class, class_parents($exception));
        self::assertSame($error, $exception->getError());
        self::assertSame('Kein PDF-Format erkannt', $exception->getMessage());
        self::assertSame('E301', $exception->getCode());
        self::assertSame('Error', $exception->getLevel());
        self::assertSame(400, $exception->getStatusCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(
            ErrorException::class . ": [Error] [E301]: Kein PDF-Format erkannt\n",
            (string) $exception,
        );
    }

    public function testStatusCodeDefaultsToZero(): void
    {
        $exception = new ErrorException(new Error('Error', 'E900', 'x'));

        self::assertSame(0, $exception->getStatusCode());
        self::assertFalse($exception->isRateLimited());
        self::assertFalse($exception->isNotFound());
        self::assertFalse($exception->isAuthenticationError());
    }

    public function testFromClientExceptionParsesErrorBody(): void
    {
        $exception = ErrorException::fromClientException(self::clientException(
            401,
            '{"level":"Error","code":"E101","description":"Ungültiges Token - Abgelaufen","date":"2026-01-01"}',
        ));

        self::assertSame('E101', $exception->getCode());
        self::assertSame('Ungültiges Token - Abgelaufen', $exception->getMessage());
        self::assertSame('2026-01-01', $exception->getError()->getDate());
        self::assertSame(401, $exception->getStatusCode());
        self::assertInstanceOf(ClientException::class, $exception->getPrevious());
        self::assertTrue($exception->isAuthenticationError());
    }

    public function testFromClientExceptionWithoutErrorBody(): void
    {
        $exception = ErrorException::fromClientException(self::clientException(404, ''));

        self::assertSame('HTTP404', $exception->getCode());
        self::assertSame('Not Found', $exception->getMessage());
        self::assertSame('Error', $exception->getLevel());
        self::assertTrue($exception->isNotFound());
    }

    public function testFromClientExceptionWithPlainTextBody(): void
    {
        $exception = ErrorException::fromClientException(self::clientException(429, 'slow down'));

        self::assertSame('HTTP429', $exception->getCode());
        self::assertSame('Too Many Requests slow down', $exception->getMessage());
        self::assertTrue($exception->isRateLimited());
    }

    public function testClassificationByErrorCode(): void
    {
        self::assertTrue((new ErrorException(new Error('Error', 'E322', '')))->isRateLimited());
        self::assertTrue((new ErrorException(new Error('Error', 'E201', '')))->isNotFound());
        self::assertTrue((new ErrorException(new Error('Error', 'E001', '')))->isAuthenticationError());
        self::assertTrue((new ErrorException(new Error('Error', 'E002', '')))->isAuthenticationError());
        self::assertTrue((new ErrorException(new Error('Error', 'E101', '')))->isAuthenticationError());
        self::assertTrue((new ErrorException(new Error('Error', 'E399', ''), 401))->isAuthenticationError());
        self::assertFalse((new ErrorException(new Error('Error', 'E399', ''), 400))->isAuthenticationError());
    }

    private static function clientException(int $status, string $body): ClientException
    {
        return new ClientException(
            'client error',
            new Request('GET', '/api/Letter/1'),
            new Response($status, [], $body),
        );
    }
}

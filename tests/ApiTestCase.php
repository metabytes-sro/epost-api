<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\Attachment;
use MetabytesSRO\EPost\Api\EPostClient;
use MetabytesSRO\EPost\Api\Http\Transport;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Recipient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Base class for tests that talk to a mocked E-POST API.
 *
 * Every request sent through mockClient() is recorded, so tests can assert the
 * exact method, path, query string and JSON body the package produced.
 */
abstract class ApiTestCase extends TestCase
{
    public const string PDF = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";

    /** @var list<RequestInterface> */
    protected array $requests = [];

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
        parent::tearDown();
    }

    /**
     * A Guzzle client answering with the given responses (or throwing the given exceptions) in order.
     */
    protected function mockClient(Response|Throwable ...$responses): Client
    {
        $this->requests = [];
        $stack = HandlerStack::create(new MockHandler(array_values($responses)));
        $stack->push(function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler): mixed {
                $this->requests[] = $request;

                return $handler($request, $options);
            };
        });

        return new Client(['handler' => $stack]);
    }

    /**
     * A Transport bound to a mocked client.
     */
    protected function transport(Response|Throwable ...$responses): Transport
    {
        return new Transport($this->mockClient(...$responses));
    }

    /**
     * A client with a static token, bound to a mocked transport.
     */
    protected function client(Response|Throwable ...$responses): EPostClient
    {
        return EPostClient::withToken('test-token', $this->transport(...$responses));
    }

    protected static function jsonResponse(mixed $data, int $status = 200): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($data, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * An error response in the shape the API sends for 4xx.
     */
    protected static function errorResponse(int $status, string $code, string $description, string $level = 'Error'): Response
    {
        return self::jsonResponse(['level' => $level, 'code' => $code, 'description' => $description], $status);
    }

    protected function lastRequest(): RequestInterface
    {
        self::assertNotEmpty($this->requests, 'No request was sent');

        return $this->requests[count($this->requests) - 1];
    }

    /**
     * Assert the last request's method, path, query string (as key/value pairs)
     * and, when given, its decoded JSON body.
     *
     * @param array<string, string>|null $query
     */
    protected function assertLastRequest(string $method, string $path, ?array $query = null, mixed $json = null): void
    {
        $request = $this->lastRequest();
        self::assertSame($method, $request->getMethod());
        self::assertSame('https', $request->getUri()->getScheme());
        self::assertSame('api.epost.docuguide.com', $request->getUri()->getHost());
        self::assertSame($path, $request->getUri()->getPath());
        self::assertSame('application/json', $request->getHeaderLine('Accept'));

        if ($query !== null) {
            parse_str($request->getUri()->getQuery(), $actualQuery);
            self::assertSame($query, $actualQuery);
        }

        if ($json !== null) {
            self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
            self::assertSame($json, $this->lastRequestJson());
        }
    }

    /**
     * Decoded JSON body of the last request.
     *
     * @return array<mixed>
     */
    protected function lastRequestJson(): array
    {
        $decoded = json_decode((string) $this->lastRequest()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    protected static function recipient(): Recipient
    {
        return new Recipient('Max Mustermann', '53115', 'Bonn', 'Musterstraße 1');
    }

    protected static function document(string $fileName = 'letter.pdf', string $contents = self::PDF): Attachment
    {
        return Attachment::fromString($contents, $fileName);
    }

    protected static function letter(): Letter
    {
        return new Letter(self::recipient(), self::document());
    }

    /**
     * A file in the temp directory, removed after the test.
     */
    protected function createTempFile(string $suffix, string $content): string
    {
        $file = sys_get_temp_dir() . '/epost_test_' . uniqid('', true) . $suffix;
        file_put_contents($file, $content);
        $this->tempFiles[] = $file;

        return $file;
    }
}

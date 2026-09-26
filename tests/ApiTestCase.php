<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\AccessToken;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Metadata\Envelope;
use MetabytesSRO\EPost\Api\Metadata\Envelope\Recipient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Base class for tests that talk to a mocked E-POST API.
 *
 * Every request sent through mockClient() is recorded, so tests can assert the
 * exact method, path, query string and JSON body the package produced.
 */
abstract class ApiTestCase extends TestCase
{
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
     * A Guzzle client answering with the given responses in order.
     */
    protected function mockClient(Response ...$responses): Client
    {
        $this->requests = [];
        $stack = HandlerStack::create(new MockHandler(array_values($responses)));
        $stack->push(function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler): mixed {
                $this->requests[] = $request;

                return $handler($request, $options);
            };
        });

        return new Client(['handler' => $stack, 'base_uri' => Letter::API_ENDPOINT]);
    }

    /**
     * A JSON response.
     */
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
        self::assertSame($path, $request->getUri()->getPath());

        if ($query !== null) {
            parse_str($request->getUri()->getQuery(), $actualQuery);
            self::assertSame($query, $actualQuery);
        }

        if ($json !== null) {
            self::assertStringStartsWith('application/json', $request->getHeaderLine('Content-Type'));
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

    protected function createEnvelope(): Envelope
    {
        $recipient = (new Recipient())
            ->setAddressLine('Test', 0)
            ->setZipCode('53115')
            ->setCity('Bonn');

        return (new Envelope())->setRecipient($recipient);
    }

    protected function createToken(): AccessToken
    {
        return AccessToken::fromToken('test-token');
    }

    /**
     * A minimal but valid PDF file in the temp directory, removed after the test.
     */
    protected function createTempPdf(string $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF"): string
    {
        return $this->createTempFile('.pdf', $content);
    }

    protected function createTempFile(string $suffix, string $content): string
    {
        $file = sys_get_temp_dir() . '/epost_test_' . uniqid('', true) . $suffix;
        file_put_contents($file, $content);
        $this->tempFiles[] = $file;

        return $file;
    }
}

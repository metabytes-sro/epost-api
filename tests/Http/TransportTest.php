<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\Exception\ApiException;
use MetabytesSRO\EPost\Api\Exception\TransportException;
use MetabytesSRO\EPost\Api\Http\Transport;
use MetabytesSRO\EPost\Api\Tests\ApiTestCase;

class TransportTest extends ApiTestCase
{
    public function testGetWithQueryParameters(): void
    {
        $transport = $this->transport(new Response(200, [], 'ok'));

        $response = $transport->request('GET', '/api/Letter/Date', [
            'fromDate' => '2024-01-01',
            'onlyIssues' => true,
            'onlyOpen' => false,
            'batchId' => 12,
            'custom1' => 'RE 1&2',
        ]);

        self::assertSame('ok', (string) $response->getBody());
        $this->assertLastRequest('GET', '/api/Letter/Date', [
            'fromDate' => '2024-01-01',
            'onlyIssues' => 'true',
            'onlyOpen' => 'false',
            'batchId' => '12',
            'custom1' => 'RE 1&2',
        ]);
        self::assertStringContainsString('custom1=RE%201%262', $this->lastRequest()->getUri()->getQuery());
        self::assertSame('', $this->lastRequest()->getHeaderLine('Content-Type'));
        self::assertSame('', $this->lastRequest()->getHeaderLine('Authorization'));
    }

    public function testEmptyQueryIsOmitted(): void
    {
        $transport = $this->transport(new Response(200));

        $transport->request('GET', '/api/Letter/Open', []);

        self::assertSame('', $this->lastRequest()->getUri()->getQuery());
    }

    public function testPostWithJsonBodyAndBearerToken(): void
    {
        $transport = $this->transport(new Response(200));

        $transport->request('POST', '/api/Letter', null, [['fileName' => 'ä.pdf', 'path' => 'a/b']], 'secret-token');

        $this->assertLastRequest('POST', '/api/Letter', [], [['fileName' => 'ä.pdf', 'path' => 'a/b']]);
        self::assertSame('[{"fileName":"ä.pdf","path":"a/b"}]', (string) $this->lastRequest()->getBody());
        self::assertSame('Bearer secret-token', $this->lastRequest()->getHeaderLine('Authorization'));
    }

    public function testListBodyIsEncodedAsJsonArray(): void
    {
        $transport = $this->transport(new Response(200));

        $transport->request('POST', '/api/Letter/StatusQuery', null, [1, 2]);

        self::assertSame('[1,2]', (string) $this->lastRequest()->getBody());
    }

    public function testErrorResponseBecomesApiException(): void
    {
        $transport = $this->transport(self::errorResponse(400, 'E301', 'Kein PDF-Format erkannt'));

        try {
            $transport->request('POST', '/api/Letter', null, []);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('E301', $e->getCode());
            self::assertSame(400, $e->getStatusCode());
            self::assertNotNull($e->getResponse());
        }
    }

    public function testServerErrorBecomesApiException(): void
    {
        $transport = $this->transport(new Response(503, [], 'Service Unavailable'));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Service Unavailable');
        $transport->request('GET', '/api/Letter/Open');
    }

    public function testConnectionFailureBecomesTransportException(): void
    {
        $transport = $this->transport(new ConnectException('Connection refused', new Request('GET', '/')));

        try {
            $transport->request('GET', '/api/Letter/Open');
            self::fail('Expected TransportException');
        } catch (TransportException $e) {
            self::assertStringContainsString('Connection refused', $e->getMessage());
            self::assertInstanceOf(ConnectException::class, $e->getPrevious());
        }
    }

    public function testBaseUriTrailingSlashIsTrimmed(): void
    {
        $transport = new Transport($this->mockClient(new Response(200)), null, null, 'https://example.test/epost/');

        $transport->request('GET', '/api/Letter/Open');

        self::assertSame('https://example.test/epost', $transport->getBaseUri());
        self::assertSame('https://example.test/epost/api/Letter/Open', (string) $this->lastRequest()->getUri());
    }

    public function testCustomFactoriesAreUsed(): void
    {
        $factory = new HttpFactory();
        $transport = new Transport($this->mockClient(new Response(200)), $factory, $factory);

        $transport->request('POST', '/api/Login', null, ['a' => 1]);

        $this->assertLastRequest('POST', '/api/Login', [], ['a' => 1]);
    }

    public function testDefaultsToGuzzleWithTheApiEndpoint(): void
    {
        $transport = new Transport();

        self::assertSame('https://api.epost.docuguide.com', $transport->getBaseUri());
        self::assertSame(Transport::API_ENDPOINT, $transport->getBaseUri());
    }
}

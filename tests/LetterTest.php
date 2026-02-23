<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\AccessToken;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\LetterDataResult;
use MetabytesSRO\EPost\Api\LetterSendResult;
use MetabytesSRO\EPost\Api\LetterStatus;
use MetabytesSRO\EPost\Api\Metadata\Envelope;
use MetabytesSRO\EPost\Api\Metadata\Envelope\Recipient;
use MetabytesSRO\EPost\Api\QueuedOperationResult;
use PHPUnit\Framework\TestCase;

class LetterTest extends TestCase
{
    public function testBuildLetterPayloadRequiresEnvelope(): void
    {
        $letter = new Letter();
        $letter->setAttachment($this->createTempPdf());
        $letter->setAccessToken($this->createMockToken());

        $this->expectException(\MetabytesSRO\EPost\Api\Exception\MissingEnvelopeException::class);
        $letter->buildLetterPayload();
    }

    public function testBuildLetterPayloadRequiresAttachment(): void
    {
        $letter = new Letter();
        $letter->setEnvelope($this->createEnvelope());
        $letter->setAccessToken($this->createMockToken());

        $this->expectException(\MetabytesSRO\EPost\Api\Exception\MissingAttachmentException::class);
        $letter->buildLetterPayload();
    }

    public function testBuildLetterPayloadDoesNotRequireAccessToken(): void
    {
        $letter = new Letter();
        $letter->setEnvelope($this->createEnvelope());
        $letter->setAttachment($this->createTempPdf());

        $payload = $letter->buildLetterPayload();
        $this->assertIsArray($payload);
    }

    public function testBuildLetterPayloadReturnsExpectedStructure(): void
    {
        $letter = new Letter();
        $letter
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf())
            ->setAccessToken($this->createMockToken());

        $payload = $letter->buildLetterPayload();

        $this->assertArrayHasKey('fileName', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('addressLine1', $payload);
        $this->assertArrayHasKey('zipCode', $payload);
        $this->assertArrayHasKey('city', $payload);
        $this->assertArrayHasKey('coverLetter', $payload);
    }

    public function testSendBatchRequiresLetterInstances(): void
    {
        $letter = new Letter();
        $letter->setAccessToken($this->createMockToken());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Letter instances');
        $letter->sendBatch([new \stdClass()]);
    }

    public function testSendBatchReturnsEmptyForEmptyArray(): void
    {
        $letter = new Letter();
        $letter->setAccessToken($this->createMockToken());

        $result = $letter->sendBatch([]);
        $this->assertSame([], $result);
    }

    public function testSendWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([['letterID' => 99999]])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());
        $letter->setEnvelope($this->createEnvelope());
        $letter->setAttachment($this->createTempPdf());

        $letter->send();
        $this->assertSame('99999', $letter->getLetterId());
    }

    public function testSendBatchWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([['letterID' => 12345]])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());
        $letter->setEnvelope($this->createEnvelope());
        $letter->setAttachment($this->createTempPdf());

        $results = $letter->sendBatch([$letter]);
        $this->assertCount(1, $results);
        $this->assertInstanceOf(LetterSendResult::class, $results[0]);
        $this->assertSame(12345, $results[0]->getLetterId());
    }

    public function testGetLetterStatusWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'letterID' => 123,
                'statusID' => 4,
                'fileName' => 'test.pdf',
            ])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());
        $letter->setLetterId('123');

        $status = $letter->getLetterStatus('123');
        $this->assertInstanceOf(LetterStatus::class, $status);
        $this->assertSame(123, $status->getLetterId());
        $this->assertSame(4, $status->getStatusId());
        $this->assertSame(\MetabytesSRO\EPost\Api\LetterStatusId::ProcessingInPrintingCenter, $status->getStatus());
    }

    public function testLetterStatusMapsStatusIdToEnum(): void
    {
        $status = new LetterStatus(['letterID' => 1, 'statusID' => 99]);
        $this->assertSame(\MetabytesSRO\EPost\Api\LetterStatusId::ProcessingError, $status->getStatus());

        $unknown = new LetterStatus(['letterID' => 2, 'statusID' => 50]);
        $this->assertNull($unknown->getStatus());
    }

    public function testGetMultipleLetterStatusesWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                ['letterID' => 1, 'statusID' => 4],
                ['letterID' => 2, 'statusID' => 4],
            ])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());

        $statuses = $letter->getMultipleLetterStatuses([1, 2]);
        $this->assertCount(2, $statuses);
        $this->assertSame(1, $statuses[0]->getLetterId());
        $this->assertSame(2, $statuses[1]->getLetterId());
    }

    public function testGetLetterStatusByDateRangeWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([['letterID' => 99, 'statusID' => 1]])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());

        $statuses = $letter->getLetterStatusByDateRange('2024-01-01', '2024-01-31');
        $this->assertCount(1, $statuses);
        $this->assertSame(99, $statuses[0]->getLetterId());
    }

    public function testGetOpenLettersWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([['letterID' => 10, 'statusID' => 2]])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());

        $statuses = $letter->getOpenLetters();
        $this->assertCount(1, $statuses);
        $this->assertSame(10, $statuses[0]->getLetterId());
    }

    public function testGetRegisteredLetterStatusWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([['letterID' => 5, 'statusID' => 4]])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());

        $statuses = $letter->getRegisteredLetterStatus('2024-01-01', '2024-01-31');
        $this->assertCount(1, $statuses);
        $this->assertSame(5, $statuses[0]->getLetterId());
    }

    public function testGetLetterStatusByCustom1WithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([['letterID' => 7, 'statusID' => 4, 'custom1' => 'RE-001']])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());

        $statuses = $letter->getLetterStatusByCustom1('RE-001');
        $this->assertCount(1, $statuses);
        $this->assertSame(7, $statuses[0]->getLetterId());
    }

    public function testGetLetterStatusByBatchWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([['letterID' => 8, 'statusID' => 4, 'batchID' => 100]])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());

        $statuses = $letter->getLetterStatusByBatch(100);
        $this->assertCount(1, $statuses);
        $this->assertSame(8, $statuses[0]->getLetterId());
    }

    public function testCancelQueuedWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                ['letterID' => 100, 'successful' => true, 'message' => 'Abruch/Freigabe der Sendung war erfolgreich'],
            ])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());

        $results = $letter->cancelQueued([100]);
        $this->assertCount(1, $results);
        $this->assertInstanceOf(QueuedOperationResult::class, $results[0]);
        $this->assertSame('Abruch/Freigabe der Sendung war erfolgreich', $results[0]->getMessage());
    }

    public function testReleaseQueuedWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                ['letterID' => 101, 'successful' => true, 'message' => 'Abruch/Freigabe der Sendung war erfolgreich'],
            ])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());

        $results = $letter->releaseQueued([101]);
        $this->assertCount(1, $results);
        $this->assertInstanceOf(QueuedOperationResult::class, $results[0]);
    }

    public function testGetPremiumAdressFeedbackWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([['letterID' => 20, 'statusID' => 4]])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());

        $feedback = $letter->getPremiumAdressFeedback('2024-01-01', '2024-01-31');
        $this->assertCount(1, $feedback);
        $this->assertSame(20, $feedback[0]->getLetterId());
    }

    public function testGetTestResultWithMockClient(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'letterID' => 123,
                'fileName' => 'test.pdf',
                'data' => 'base64content',
            ])),
        ]);
        $client = new HttpClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => Letter::API_ENDPOINT,
        ]);
        $letter = new Letter($client);
        $letter->setAccessToken($this->createMockToken());
        $letter->setLetterId('123');

        $result = $letter->getTestResult('123');
        $this->assertInstanceOf(LetterDataResult::class, $result);
        $this->assertSame(123, $result->getLetterId());
        $this->assertSame('base64content', $result->getData());
    }

    private function createEnvelope(): Envelope
    {
        $envelope = new Envelope();
        $recipient = new Recipient();
        $recipient
            ->setAddressLine('Test', 0)
            ->setZipCode('53115')
            ->setCity('Bonn');
        $envelope->setRecipient($recipient);
        return $envelope;
    }

    private function createMockToken(): AccessToken
    {
        return new AccessToken('vendor', '1234567890', 'secret', 'password');
    }

    private function createTempPdf(): string
    {
        $file = sys_get_temp_dir() . '/epost_test_' . uniqid('', true) . '.pdf';
        file_put_contents($file, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");
        return $file;
    }
}

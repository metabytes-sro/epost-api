<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\Exception\ErrorException;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\LetterSendResult;
use MetabytesSRO\EPost\Api\LetterStatusId;
use MetabytesSRO\EPost\Api\QueuedOperationResult;

/**
 * Every /api/Letter endpoint: the request the package sends and how it reads the response.
 */
class LetterApiTest extends ApiTestCase
{
    public function testSendPostsLetterArrayAndStoresLetterId(): void
    {
        $letter = $this->letter(self::jsonResponse([['letterID' => 99999, 'fileName' => 'x.pdf']]))
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf('%PDF-1.4 x'));

        $result = $letter->send();

        self::assertSame($letter, $result);
        self::assertSame('99999', $letter->getLetterId());
        $this->assertLastRequest('POST', '/api/Letter', [], [$letter->buildLetterPayload()]);
        self::assertStringContainsString(
            '"data":"' . str_replace("\r\n", '\r\n', chunk_split(base64_encode('%PDF-1.4 x'))) . '"',
            (string) $this->lastRequest()->getBody(),
        );
    }

    public function testSendFailsWhenApiReturnsNoLetterId(): void
    {
        $letter = $this->letter(self::jsonResponse([]))
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf());

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('did not return a letter ID');
        $letter->send();
    }

    public function testSendConvertsApiErrorToErrorException(): void
    {
        $letter = $this->letter(self::errorResponse(400, 'E301', 'Kein PDF-Format erkannt'))
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf());

        try {
            $letter->send();
            self::fail('Expected ErrorException');
        } catch (ErrorException $e) {
            self::assertSame('E301', $e->getCode());
            self::assertSame('Kein PDF-Format erkannt', $e->getMessage());
            self::assertSame(400, $e->getStatusCode());
        }
    }

    public function testSendBatchPostsAllLettersInOneRequest(): void
    {
        $client = $this->letter(self::jsonResponse([['letterID' => 1], ['letterID' => 2]]));
        $letter1 = (new Letter())->setEnvelope($this->createEnvelope())->setAttachment($this->createTempPdf('%PDF-1.4 a'));
        $letter2 = (new Letter())->setEnvelope($this->createEnvelope())->setAttachment($this->createTempPdf('%PDF-1.4 b'));

        $results = $client->sendBatch([$letter1, $letter2]);

        self::assertCount(2, $results);
        self::assertContainsOnlyInstancesOf(LetterSendResult::class, $results);
        self::assertSame(1, $results[0]->getLetterId());
        self::assertSame(2, $results[1]->getLetterId());
        $this->assertLastRequest('POST', '/api/Letter', [], [$letter1->buildLetterPayload(), $letter2->buildLetterPayload()]);
    }

    public function testGetLetterStatusFetchesById(): void
    {
        $letter = $this->letter(self::jsonResponse(['letterID' => 123, 'statusID' => 4, 'fileName' => 'test.pdf']));

        $status = $letter->getLetterStatus('123');

        self::assertSame(123, $status->getLetterId());
        self::assertSame(LetterStatusId::ProcessingInPrintingCenter, $status->getStatus());
        $this->assertLastRequest('GET', '/api/Letter/123', []);
    }

    public function testGetLetterStatusDefaultsToOwnLetterId(): void
    {
        $letter = $this->letter(self::jsonResponse(['letterID' => 77, 'statusID' => 1]))->setLetterId('77');

        self::assertSame(77, $letter->getLetterStatus()->getLetterId());
        $this->assertLastRequest('GET', '/api/Letter/77');
    }

    public function testGetLetterStatusNotFound(): void
    {
        $letter = $this->letter(self::errorResponse(404, 'E201', 'Ungültige Statusabfrage'));

        try {
            $letter->getLetterStatus('1');
            self::fail('Expected ErrorException');
        } catch (ErrorException $e) {
            self::assertTrue($e->isNotFound());
            self::assertFalse($e->isRateLimited());
        }
    }

    public function testGetLetterStatusRateLimited(): void
    {
        $letter = $this->letter(self::errorResponse(429, 'E322', 'Zu hohe Abfragefrequenz'));

        try {
            $letter->getLetterStatus('1');
            self::fail('Expected ErrorException');
        } catch (ErrorException $e) {
            self::assertTrue($e->isRateLimited());
        }
    }

    public function testGetMultipleLetterStatuses(): void
    {
        $letter = $this->letter(self::jsonResponse([['letterID' => 1, 'statusID' => 4], ['letterID' => 2, 'statusID' => 4]]));

        $statuses = $letter->getMultipleLetterStatuses([1, 2]);

        self::assertCount(2, $statuses);
        self::assertSame(1, $statuses[0]->getLetterId());
        self::assertSame(2, $statuses[1]->getLetterId());
        $this->assertLastRequest('POST', '/api/Letter/StatusQuery', ['onlyIssues' => 'false'], [1, 2]);
    }

    public function testGetMultipleLetterStatusesOnlyIssues(): void
    {
        $letter = $this->letter(self::jsonResponse([]));

        self::assertSame([], $letter->getMultipleLetterStatuses([5 => 3], true));
        $this->assertLastRequest('POST', '/api/Letter/StatusQuery', ['onlyIssues' => 'true'], [3]);
    }

    public function testGetLetterStatusByDateRange(): void
    {
        $letter = $this->letter(self::jsonResponse([['letterID' => 99, 'statusID' => 1]]));

        $statuses = $letter->getLetterStatusByDateRange('2024-01-01', '2024-01-31', true);

        self::assertCount(1, $statuses);
        self::assertSame(99, $statuses[0]->getLetterId());
        $this->assertLastRequest('GET', '/api/Letter/Date', [
            'fromDate' => '2024-01-01',
            'tillDate' => '2024-01-31',
            'onlyIssues' => 'true',
        ]);
    }

    public function testGetOpenLetters(): void
    {
        $letter = $this->letter(self::jsonResponse([['letterID' => 10, 'statusID' => 2]]));

        $statuses = $letter->getOpenLetters();

        self::assertCount(1, $statuses);
        self::assertTrue($statuses[0]->isOpen());
        $this->assertLastRequest('GET', '/api/Letter/Open', []);
    }

    public function testGetRegisteredLetterStatus(): void
    {
        $letter = $this->letter(self::jsonResponse([['letterID' => 5, 'statusID' => 4, 'registeredLetterStatus' => 'DELIVERED']]));

        $statuses = $letter->getRegisteredLetterStatus('2024-01-01', '2024-01-31');

        self::assertCount(1, $statuses);
        self::assertSame('DELIVERED', $statuses[0]->getRegisteredLetterStatus());
        $this->assertLastRequest('GET', '/api/Letter/Registered', [
            'fromDate' => '2024-01-01',
            'tillDate' => '2024-01-31',
            'onlyOpen' => 'false',
        ]);
    }

    public function testGetRegisteredLetterStatusOnlyOpen(): void
    {
        $letter = $this->letter(self::jsonResponse([]));

        $letter->getRegisteredLetterStatus('2024-01-01', '2024-01-31', true);

        $this->assertLastRequest('GET', '/api/Letter/Registered', [
            'fromDate' => '2024-01-01',
            'tillDate' => '2024-01-31',
            'onlyOpen' => 'true',
        ]);
    }

    public function testGetLetterStatusByCustom1(): void
    {
        $letter = $this->letter(self::jsonResponse([['letterID' => 7, 'statusID' => 4, 'custom1' => 'RE-001']]));

        $statuses = $letter->getLetterStatusByCustom1('RE-001');

        self::assertCount(1, $statuses);
        self::assertSame('RE-001', $statuses[0]->getCustom1());
        $this->assertLastRequest('GET', '/api/Letter/Custom1', ['custom1' => 'RE-001', 'onlyIssues' => 'false']);
    }

    public function testGetLetterStatusByBatch(): void
    {
        $letter = $this->letter(self::jsonResponse([['letterID' => 8, 'statusID' => 4, 'batchID' => 100]]));

        $statuses = $letter->getLetterStatusByBatch(100, true);

        self::assertCount(1, $statuses);
        self::assertSame(100, $statuses[0]->getBatchId());
        $this->assertLastRequest('GET', '/api/Letter/Batch', ['batchId' => '100', 'onlyIssues' => 'true']);
    }

    public function testCancelQueued(): void
    {
        $letter = $this->letter(self::jsonResponse([
            ['letterID' => 100, 'successful' => true, 'message' => 'Abruch/Freigabe der Sendung war erfolgreich'],
        ]));

        $results = $letter->cancelQueued([100]);

        self::assertCount(1, $results);
        self::assertInstanceOf(QueuedOperationResult::class, $results[0]);
        self::assertTrue($results[0]->isSuccessful());
        self::assertSame('Abruch/Freigabe der Sendung war erfolgreich', $results[0]->getMessage());
        $this->assertLastRequest('POST', '/api/Letter/CancelQueued', [], [100]);
    }

    public function testReleaseQueued(): void
    {
        $letter = $this->letter(self::jsonResponse([['letterID' => 101, 'successful' => false, 'message' => 'Fehler']]));

        $results = $letter->releaseQueued([101, 102]);

        self::assertCount(1, $results);
        self::assertFalse($results[0]->isSuccessful());
        $this->assertLastRequest('POST', '/api/Letter/ReleaseQueued', [], [101, 102]);
    }

    public function testGetPremiumAdressFeedback(): void
    {
        $letter = $this->letter(self::jsonResponse([['letterID' => 20, 'statusID' => 4]]));

        $feedback = $letter->getPremiumAdressFeedback('2024-01-01', '2024-01-31');

        self::assertCount(1, $feedback);
        self::assertSame(20, $feedback[0]->getLetterId());
        $this->assertLastRequest('GET', '/api/Letter/PremiumAdressFeedback', [
            'fromDate' => '2024-01-01',
            'tillDate' => '2024-01-31',
            'onlyFeedback' => 'false',
        ]);
    }

    public function testGetPremiumAdressFeedbackOnlyFeedback(): void
    {
        $letter = $this->letter(self::jsonResponse([]));

        $letter->getPremiumAdressFeedback('2024-01-01', '2024-01-31', true);

        $this->assertLastRequest('GET', '/api/Letter/PremiumAdressFeedback', [
            'fromDate' => '2024-01-01',
            'tillDate' => '2024-01-31',
            'onlyFeedback' => 'true',
        ]);
    }

    public function testGetTestResult(): void
    {
        $letter = $this->letter(self::jsonResponse(['letterID' => 123, 'fileName' => 'test.pdf', 'data' => base64_encode('%PDF')]));

        $result = $letter->getTestResult('123');

        self::assertSame(123, $result->getLetterId());
        self::assertSame('%PDF', $result->getPdf());
        $this->assertLastRequest('GET', '/api/Letter/TestResult', ['letterID' => '123']);
    }

    public function testGetTestResultDefaultsToOwnLetterId(): void
    {
        $letter = $this->letter(self::jsonResponse(['letterID' => 5]))->setLetterId('5');

        self::assertSame(5, $letter->getTestResult()->getLetterId());
        $this->assertLastRequest('GET', '/api/Letter/TestResult', ['letterID' => '5']);
    }

    public function testErrorWithoutJsonBodyKeepsHttpStatus(): void
    {
        $letter = $this->letter(new Response(403, [], 'Forbidden by gateway'));

        try {
            $letter->getOpenLetters();
            self::fail('Expected ErrorException');
        } catch (ErrorException $e) {
            self::assertSame('HTTP403', $e->getCode());
            self::assertSame(403, $e->getStatusCode());
            self::assertStringContainsString('Forbidden by gateway', $e->getMessage());
        }
    }

    public function testNonListResponseYieldsEmptyList(): void
    {
        $letter = $this->letter(new Response(200, [], 'not json'));

        self::assertSame([], $letter->getOpenLetters());
    }

    public function testInjectedClientIsUsedAsIs(): void
    {
        $letter = new Letter($this->mockClient(self::jsonResponse([])));

        // No access token was set, and none is needed with an injected client.
        self::assertSame([], $letter->getOpenLetters());
        self::assertSame('', $this->lastRequest()->getHeaderLine('Authorization'));
    }

    private function letter(Response ...$responses): Letter
    {
        return (new Letter($this->mockClient(...$responses)))->setAccessToken($this->createToken());
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use ArrayIterator;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Response;
use MetabytesSRO\EPost\Api\Auth\Credentials;
use MetabytesSRO\EPost\Api\Auth\TokenProvider;
use MetabytesSRO\EPost\Api\EPostClient;
use MetabytesSRO\EPost\Api\Exception\ApiException;
use MetabytesSRO\EPost\Api\Exception\AuthenticationException;
use MetabytesSRO\EPost\Api\Exception\NotFoundException;
use MetabytesSRO\EPost\Api\Exception\RateLimitException;
use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\LetterStatusId;
use MetabytesSRO\EPost\Api\QueueResult;
use MetabytesSRO\EPost\Api\TrackStatusCode;

class EPostClientTest extends ApiTestCase
{
    public function testSendLetter(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 99999, 'fileName' => 'letter.pdf']]));
        $letter = self::letter();

        $result = $client->sendLetter($letter);

        self::assertSame(99999, $result->letterId);
        self::assertSame('letter.pdf', $result->fileName);
        $this->assertLastRequest('POST', '/api/Letter', [], [$letter->toPayload()]);
        self::assertSame('Bearer test-token', $this->lastRequest()->getHeaderLine('Authorization'));
    }

    public function testSendLetterFailsWhenApiReturnsNoLetterId(): void
    {
        $client = $this->client(self::jsonResponse([]));

        try {
            $client->sendLetter(self::letter());
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame('E900', $e->getCode());
            self::assertStringContainsString('did not return a letter ID', $e->getMessage());
        }
    }

    public function testSendLetterValidatesBeforeSending(): void
    {
        $client = $this->client();

        try {
            $client->sendLetter(new Letter());
            self::fail('Expected ValidationException');
        } catch (ValidationException) {
            self::assertSame([], $this->requests);
        }
    }

    public function testSendLettersInOneRequest(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 1], ['letterID' => 2]]));
        $first = self::letter();
        $second = (new Letter(self::recipient(), self::document('second.pdf')))->color();

        $results = $client->sendLetters([$first, $second]);

        self::assertSame([1, 2], array_map(static fn($r) => $r->letterId, $results));
        $this->assertLastRequest('POST', '/api/Letter', [], [$first->toPayload(), $second->toPayload()]);
    }

    public function testSendLettersWithNothingToSend(): void
    {
        self::assertSame([], $this->client()->sendLetters([]));
        self::assertSame([], $this->requests);
    }

    public function testGetLetterStatus(): void
    {
        $client = $this->client(self::jsonResponse(['letterID' => 123, 'statusID' => 4, 'registeredLetterStatus' => 'IN_DELIVERY']));

        $status = $client->getLetterStatus(123);

        self::assertSame(123, $status->letterId);
        self::assertSame(LetterStatusId::ProcessingInPrintingCenter, $status->status());
        self::assertSame(TrackStatusCode::InDelivery, $status->trackingStatus());
        $this->assertLastRequest('GET', '/api/Letter/123', []);
    }

    public function testGetLetterStatusNotFound(): void
    {
        $client = $this->client(self::errorResponse(404, 'E201', 'Ungültige Statusabfrage'));

        $this->expectException(NotFoundException::class);
        $client->getLetterStatus(1);
    }

    public function testGetLetterStatusRateLimited(): void
    {
        $client = $this->client(self::errorResponse(429, 'E322', 'Zu hohe Abfragefrequenz'));

        $this->expectException(RateLimitException::class);
        $client->getLetterStatus(1);
    }

    public function testGetLetterStatuses(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 1, 'statusID' => 4], ['letterID' => 2, 'statusID' => 99]]));

        $statuses = $client->getLetterStatuses([5 => 1, 7 => 2], true);

        self::assertSame([1, 2], array_map(static fn($s) => $s->letterId, $statuses));
        self::assertTrue($statuses[1]->hasError());
        $this->assertLastRequest('POST', '/api/Letter/StatusQuery', ['onlyIssues' => 'true'], [1, 2]);
    }

    public function testGetLetterStatusByDateRange(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 99, 'statusID' => 1]]));

        $statuses = $client->getLetterStatusByDateRange(new DateTimeImmutable('2024-01-01 15:00'), new DateTimeImmutable('2024-01-31'));

        self::assertCount(1, $statuses);
        $this->assertLastRequest('GET', '/api/Letter/Date', ['fromDate' => '2024-01-01', 'tillDate' => '2024-01-31', 'onlyIssues' => 'false']);
    }

    public function testGetOpenLetters(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 10, 'statusID' => 2]]));

        $statuses = $client->getOpenLetters();

        self::assertTrue($statuses[0]->isOpen());
        $this->assertLastRequest('GET', '/api/Letter/Open', []);
    }

    public function testGetRegisteredLetterStatus(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 5, 'statusID' => 4]]));

        $client->getRegisteredLetterStatus(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-31'), true);

        $this->assertLastRequest('GET', '/api/Letter/Registered', ['fromDate' => '2024-01-01', 'tillDate' => '2024-01-31', 'onlyOpen' => 'true']);
    }

    public function testGetLetterStatusByCustom1(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 7, 'statusID' => 4, 'custom1' => 'RE-001']]));

        $statuses = $client->getLetterStatusByCustom1('RE-001');

        self::assertSame('RE-001', $statuses[0]->custom1);
        $this->assertLastRequest('GET', '/api/Letter/Custom1', ['custom1' => 'RE-001', 'onlyIssues' => 'false']);
    }

    public function testGetLetterStatusByBatch(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 8, 'statusID' => 4, 'batchID' => 100]]));

        $statuses = $client->getLetterStatusByBatch(100, true);

        self::assertSame(100, $statuses[0]->batchId);
        $this->assertLastRequest('GET', '/api/Letter/Batch', ['batchId' => '100', 'onlyIssues' => 'true']);
    }

    public function testCancelQueued(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 100, 'successful' => true, 'message' => 'ok']]));

        $results = $client->cancelQueued([100]);

        self::assertInstanceOf(QueueResult::class, $results[0]);
        self::assertTrue($results[0]->successful);
        $this->assertLastRequest('POST', '/api/Letter/CancelQueued', [], [100]);
    }

    public function testReleaseQueued(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 101, 'successful' => false, 'message' => 'Fehler']]));

        $results = $client->releaseQueued(new ArrayIterator([101, 102]));

        self::assertFalse($results[0]->successful);
        $this->assertLastRequest('POST', '/api/Letter/ReleaseQueued', [], [101, 102]);
    }

    public function testGetPremiumAdressFeedback(): void
    {
        $client = $this->client(self::jsonResponse([['letterID' => 20, 'statusID' => 4]]));

        $client->getPremiumAdressFeedback(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-31'), true);

        $this->assertLastRequest('GET', '/api/Letter/PremiumAdressFeedback', ['fromDate' => '2024-01-01', 'tillDate' => '2024-01-31', 'onlyFeedback' => 'true']);
    }

    public function testGetTestResult(): void
    {
        $client = $this->client(self::jsonResponse(['letterID' => 123, 'fileName' => 'test.pdf', 'data' => base64_encode('%PDF')]));

        $result = $client->getTestResult(123);

        self::assertSame('%PDF', $result->pdf());
        $this->assertLastRequest('GET', '/api/Letter/TestResult', ['letterID' => '123']);
    }

    public function testHealthCheckNeedsNoToken(): void
    {
        $client = $this->client(self::jsonResponse(['level' => 'Warning', 'code' => 'W501', 'description' => 'Wartung']));

        $status = $client->healthCheck();

        self::assertTrue($status->isWarning());
        $this->assertLastRequest('GET', '/api/Login/HealthCheck', []);
        self::assertSame('', $this->lastRequest()->getHeaderLine('Authorization'));
    }

    public function testExpiredTokenIsRefreshedAndTheRequestRetriedOnce(): void
    {
        $tokens = new SpyTokenProvider(['old', 'new']);
        $client = new EPostClient($tokens, $this->transport(
            self::errorResponse(401, 'E101', 'Ungültiges Token - Abgelaufen'),
            self::jsonResponse([['letterID' => 1, 'statusID' => 1]]),
        ));

        $statuses = $client->getOpenLetters();

        self::assertCount(1, $statuses);
        self::assertCount(2, $this->requests);
        self::assertSame('Bearer old', $this->requests[0]->getHeaderLine('Authorization'));
        self::assertSame('Bearer new', $this->requests[1]->getHeaderLine('Authorization'));
        self::assertSame(1, $tokens->invalidations);
    }

    public function testExpiredTokenIsNotRetriedTwice(): void
    {
        $tokens = new SpyTokenProvider(['old', 'new']);
        $client = new EPostClient($tokens, $this->transport(
            self::errorResponse(401, 'E101', 'expired'),
            self::errorResponse(401, 'E101', 'still expired'),
        ));

        try {
            $client->getOpenLetters();
            self::fail('Expected AuthenticationException');
        } catch (AuthenticationException $e) {
            self::assertSame('still expired', $e->getMessage());
            self::assertCount(2, $this->requests);
        }
    }

    public function testOtherAuthenticationErrorsAreNotRetried(): void
    {
        $tokens = new SpyTokenProvider(['token']);
        $client = new EPostClient($tokens, $this->transport(self::errorResponse(401, 'E004', 'EKP gesperrt')));

        try {
            $client->getOpenLetters();
            self::fail('Expected AuthenticationException');
        } catch (AuthenticationException $e) {
            self::assertSame('E004', $e->getCode());
            self::assertCount(1, $this->requests);
            self::assertSame(0, $tokens->invalidations);
        }
    }

    public function testWithCredentialsLogsInBeforeTheFirstRequest(): void
    {
        $client = EPostClient::withCredentials(
            new Credentials('vendor', '1234567890', 'secret', 'password'),
            $this->transport(
                self::jsonResponse(['token' => 'jwt-1']),
                self::jsonResponse([]),
                self::jsonResponse([]),
            ),
        );

        $client->getOpenLetters();
        $client->getOpenLetters();

        self::assertCount(3, $this->requests);
        self::assertSame('/api/Login', $this->requests[0]->getUri()->getPath());
        self::assertSame('Bearer jwt-1', $this->requests[1]->getHeaderLine('Authorization'));
        self::assertSame('Bearer jwt-1', $this->requests[2]->getHeaderLine('Authorization'));
    }

    public function testFactoriesWithDefaultTransportDoNotTouchTheNetwork(): void
    {
        $clients = [
            EPostClient::withCredentials(new Credentials('v', 'e', 's', 'p')),
            EPostClient::withToken('t'),
            new EPostClient(new SpyTokenProvider(['t'])),
        ];

        self::assertCount(3, $clients);
        self::assertSame([], $this->requests);
    }

    public function testNonJsonResponseYieldsEmptyList(): void
    {
        $client = $this->client(new Response(200, [], 'not json'));

        self::assertSame([], $client->getOpenLetters());
    }
}

/**
 * Hands out tokens in order and counts invalidations.
 */
final class SpyTokenProvider implements TokenProvider
{
    public int $invalidations = 0;
    private int $index = 0;

    /**
     * @param list<string> $tokens
     */
    public function __construct(private readonly array $tokens) {}

    public function getToken(): string
    {
        return $this->tokens[min($this->index, count($this->tokens) - 1)];
    }

    public function invalidate(): void
    {
        ++$this->invalidations;
        ++$this->index;
    }
}

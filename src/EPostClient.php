<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use DateTimeInterface;
use MetabytesSRO\EPost\Api\Auth\Credentials;
use MetabytesSRO\EPost\Api\Auth\CredentialsTokenProvider;
use MetabytesSRO\EPost\Api\Auth\StaticTokenProvider;
use MetabytesSRO\EPost\Api\Auth\TokenProvider;
use MetabytesSRO\EPost\Api\Exception\ApiException;
use MetabytesSRO\EPost\Api\Exception\AuthenticationException;
use MetabytesSRO\EPost\Api\Exception\TransportException;
use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\Http\Transport;

/**
 * Client for the letter endpoints of the E-POSTBUSINESS API (/api/Letter).
 *
 * Every method throws ApiException (or a subclass) when the API rejects the
 * request and TransportException when it cannot be reached. When the API
 * reports an expired token, the token provider is asked for a fresh one and
 * the request is retried once.
 *
 * Status queries are rate limited by the API to one call per 5 seconds; more
 * frequent calls fail with RateLimitException.
 */
final class EPostClient
{
    public function __construct(
        private readonly TokenProvider $tokens,
        private readonly Transport $transport = new Transport(),
    ) {}

    /**
     * A client that logs in with the given credentials on first use.
     */
    public static function withCredentials(Credentials $credentials, ?Transport $transport = null): self
    {
        $transport ??= new Transport();

        return new self(new CredentialsTokenProvider($credentials, new Login($transport)), $transport);
    }

    /**
     * A client using a token obtained elsewhere.
     */
    public static function withToken(string $token, ?Transport $transport = null): self
    {
        return new self(new StaticTokenProvider($token), $transport ?? new Transport());
    }

    /**
     * Submit one letter.
     *
     * @throws ValidationException when the letter is incomplete or its options conflict
     * @throws ApiException
     * @throws TransportException
     */
    public function sendLetter(Letter $letter): LetterSendResult
    {
        $results = $this->sendLetters([$letter]);
        if (!isset($results[0])) {
            throw new ApiException(new Error(ErrorLevel::Error->value, ErrorCode::E900->value, 'The API did not return a letter ID for the submitted letter'));
        }

        return $results[0];
    }

    /**
     * Submit several letters in one request (up to 300 MB of PDFs per request).
     *
     * @param iterable<Letter> $letters
     *
     * @return list<LetterSendResult> One result per letter, in the same order
     * @throws ValidationException when a letter is incomplete or its options conflict
     * @throws ApiException
     * @throws TransportException
     */
    public function sendLetters(iterable $letters): array
    {
        $payloads = [];
        foreach ($letters as $letter) {
            $payloads[] = $letter->toPayload();
        }
        if ($payloads === []) {
            return [];
        }

        return array_map(
            static fn(array $item): LetterSendResult => LetterSendResult::fromArray($item),
            Json::decodeList($this->request('POST', '/api/Letter', null, $payloads)),
        );
    }

    /**
     * Status of one letter.
     *
     * @throws Exception\NotFoundException when the ID is unknown
     * @throws Exception\RateLimitException when polled more often than every 5 seconds
     * @throws ApiException
     * @throws TransportException
     */
    public function getLetterStatus(int $letterId): LetterStatus
    {
        return LetterStatus::fromArray(Json::decodeObject($this->request('GET', '/api/Letter/' . $letterId)));
    }

    /**
     * Status of several letters by ID.
     *
     * @param iterable<int> $letterIds
     * @param bool $onlyIssues Return only letters with errors or warnings
     *
     * @return list<LetterStatus>
     */
    public function getLetterStatuses(iterable $letterIds, bool $onlyIssues = false): array
    {
        $ids = [];
        foreach ($letterIds as $id) {
            $ids[] = $id;
        }

        return $this->statusList($this->request('POST', '/api/Letter/StatusQuery', ['onlyIssues' => $onlyIssues], $ids));
    }

    /**
     * Status of all letters created in a date range (inclusive).
     *
     * @return list<LetterStatus>
     */
    public function getLetterStatusByDateRange(DateTimeInterface $from, DateTimeInterface $till, bool $onlyIssues = false): array
    {
        return $this->statusList($this->request('GET', '/api/Letter/Date', [
            'fromDate' => self::date($from),
            'tillDate' => self::date($till),
            'onlyIssues' => $onlyIssues,
        ]));
    }

    /**
     * Status of all live letters that have not reached the print centre's final feedback (status 1 to 3).
     *
     * @return list<LetterStatus>
     */
    public function getOpenLetters(): array
    {
        return $this->statusList($this->request('GET', '/api/Letter/Open'));
    }

    /**
     * Status of registered letters created in a date range.
     *
     * @param bool $onlyOpen Return only letters whose tracking status is not final
     *
     * @return list<LetterStatus>
     */
    public function getRegisteredLetterStatus(DateTimeInterface $from, DateTimeInterface $till, bool $onlyOpen = false): array
    {
        return $this->statusList($this->request('GET', '/api/Letter/Registered', [
            'fromDate' => self::date($from),
            'tillDate' => self::date($till),
            'onlyOpen' => $onlyOpen,
        ]));
    }

    /**
     * Status of all letters submitted with the given custom1 value.
     *
     * @return list<LetterStatus>
     */
    public function getLetterStatusByCustom1(string $custom1, bool $onlyIssues = false): array
    {
        return $this->statusList($this->request('GET', '/api/Letter/Custom1', [
            'custom1' => $custom1,
            'onlyIssues' => $onlyIssues,
        ]));
    }

    /**
     * Status of all letters submitted with the given batch ID.
     *
     * @return list<LetterStatus>
     */
    public function getLetterStatusByBatch(int $batchId, bool $onlyIssues = false): array
    {
        return $this->statusList($this->request('GET', '/api/Letter/Batch', [
            'batchId' => $batchId,
            'onlyIssues' => $onlyIssues,
        ]));
    }

    /**
     * Cancel letters that were submitted with the UploadManagement plugin and are still queued.
     *
     * @param iterable<int> $letterIds
     *
     * @return list<QueueResult>
     */
    public function cancelQueued(iterable $letterIds): array
    {
        return $this->queueOperation('/api/Letter/CancelQueued', $letterIds);
    }

    /**
     * Release letters that were submitted with the UploadManagement plugin ahead of their due date.
     *
     * @param iterable<int> $letterIds
     *
     * @return list<QueueResult>
     */
    public function releaseQueued(iterable $letterIds): array
    {
        return $this->queueOperation('/api/Letter/ReleaseQueued', $letterIds);
    }

    /**
     * Status of letters sent with the PremiumAdress plugin in a date range.
     *
     * @param bool $onlyFeedback Return only letters that received PremiumAdress feedback
     *
     * @return list<LetterStatus>
     */
    public function getPremiumAdressFeedback(DateTimeInterface $from, DateTimeInterface $till, bool $onlyFeedback = false): array
    {
        return $this->statusList($this->request('GET', '/api/Letter/PremiumAdressFeedback', [
            'fromDate' => self::date($from),
            'tillDate' => self::date($till),
            'onlyFeedback' => $onlyFeedback,
        ]));
    }

    /**
     * The processed PDF of a letter that was sent in test mode.
     */
    public function getTestResult(int $letterId): TestResult
    {
        return TestResult::fromArray(Json::decodeObject(
            $this->request('GET', '/api/Letter/TestResult', ['letterID' => $letterId]),
        ));
    }

    /**
     * Availability status of the API; no token needed. See Login::healthCheck().
     */
    public function healthCheck(): Error
    {
        return (new Login($this->transport))->healthCheck();
    }

    /**
     * @param iterable<int> $letterIds
     *
     * @return list<QueueResult>
     */
    private function queueOperation(string $path, iterable $letterIds): array
    {
        $ids = [];
        foreach ($letterIds as $id) {
            $ids[] = $id;
        }

        return array_map(
            static fn(array $item): QueueResult => QueueResult::fromArray($item),
            Json::decodeList($this->request('POST', $path, null, $ids)),
        );
    }

    /**
     * @return list<LetterStatus>
     */
    private function statusList(string $body): array
    {
        return array_map(
            static fn(array $item): LetterStatus => LetterStatus::fromArray($item),
            Json::decodeList($body),
        );
    }

    /**
     * Send an authenticated request and return the response body, retrying once
     * with a fresh token when the API reports the current one as expired.
     *
     * @param array<string, bool|int|string>|null $query
     */
    private function request(string $method, string $path, ?array $query = null, mixed $json = null): string
    {
        try {
            $response = $this->transport->request($method, $path, $query, $json, $this->tokens->getToken());
        } catch (AuthenticationException $e) {
            if ($e->getErrorCode() !== ErrorCode::E101) {
                throw $e;
            }
            $this->tokens->invalidate();
            $response = $this->transport->request($method, $path, $query, $json, $this->tokens->getToken());
        }

        return (string) $response->getBody();
    }

    private static function date(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}

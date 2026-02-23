<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use MetabytesSRO\EPost\Api\Exception\InvalidFileFormat;
use MetabytesSRO\EPost\Api\Exception\MissingAuthorizationTokenException;
use MetabytesSRO\EPost\Api\Exception\MissingAttachmentException;
use MetabytesSRO\EPost\Api\Exception\MissingEnvelopeException;
use MetabytesSRO\EPost\Api\Exception\MissingPreconditionException;
use MetabytesSRO\EPost\Api\Exception\MissingRecipientException;
use MetabytesSRO\EPost\Api\Metadata\DeliveryOptions;
use MetabytesSRO\EPost\Api\Metadata\Envelope;
use MetabytesSRO\EPost\Api\LetterSendResult;
use MetabytesSRO\EPost\Api\LetterDataResult;
use MetabytesSRO\EPost\Api\QueuedOperationResult;

/**
 * API errors (4xx) are converted to ErrorException. Timeouts and connection failures
 * (ConnectException, RequestException) are not caught—callers should handle these.
 */
class Letter
{
    public const API_ENDPOINT = 'https://api.epost.docuguide.com';

    private ?HttpClient $httpClient = null;

    private bool $testEnvironment = false;
    private ?string $testEmail = null;
    private ?AccessToken $accessToken = null;
    private ?Envelope $envelope = null;
    private ?string $coverLetterPath = null;
    private ?string $attachmentPath = null;
    private ?DeliveryOptions $deliveryOptions = null;
    private ?string $letterId = null;

    public function __construct(?HttpClient $httpClient = null)
    {
        $this->httpClient = $httpClient;
    }

    public function setAccessToken(AccessToken $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getAccessToken(): AccessToken
    {
        if ($this->accessToken === null) {
            throw new MissingAuthorizationTokenException('An AccessToken instance must be passed');
        }
        return $this->accessToken;
    }

    public function setEnvelope(Envelope $envelope): self
    {
        $this->envelope = $envelope;
        return $this;
    }

    public function getEnvelope(): Envelope
    {
        if ($this->envelope === null) {
            throw new MissingEnvelopeException('No Envelope provided! Provide one beforehand');
        }
        if ($this->envelope->getData() === null) {
            throw new MissingRecipientException('No recipient provided! Add them beforehand');
        }
        return $this->envelope;
    }

    public function setCoverLetter(?string $coverLetterPath): self
    {
        $this->coverLetterPath = $coverLetterPath;
        return $this;
    }

    public function getCoverLetter(): ?string
    {
        return $this->coverLetterPath;
    }

    public function setAttachment(string $attachmentPath): self
    {
        if (mime_content_type($attachmentPath) !== 'application/pdf') {
            throw new InvalidFileFormat('Unallowed file format. Allowed: pdf');
        }
        $this->attachmentPath = $attachmentPath;
        return $this;
    }

    public function getAttachment(): string
    {
        if ($this->attachmentPath === null || $this->attachmentPath === '') {
            throw new MissingAttachmentException('No attachment provided! Please add an attachment.');
        }
        return $this->attachmentPath;
    }

    public function setDeliveryOptions(?DeliveryOptions $deliveryOptions): self
    {
        $this->deliveryOptions = $deliveryOptions;
        return $this;
    }

    public function getDeliveryOptions(): ?DeliveryOptions
    {
        return $this->deliveryOptions;
    }

    public function setLetterId(?string $letterId): self
    {
        $this->letterId = $letterId;
        return $this;
    }

    public function getLetterId(): string
    {
        if ($this->letterId === null || $this->letterId === '') {
            throw new MissingPreconditionException('No letter id provided! Set letter id beforehand');
        }
        return $this->letterId;
    }

    public function setTestEnvironment(bool $testEnvironment): self
    {
        $this->testEnvironment = $testEnvironment;
        return $this;
    }

    public function setTestEmail(?string $testEmail): self
    {
        $this->testEmail = $testEmail;
        return $this;
    }

    public function isTestEnvironment(): bool
    {
        return $this->testEnvironment;
    }

    public function buildLetterPayload(): array
    {
        $payload = $this->getEnvelope()->getData();

        if ($this->coverLetterPath !== null && $this->coverLetterPath !== '') {
            $payload['coverLetter'] = true;
            $payload['coverData'] = chunk_split(base64_encode(file_get_contents($this->coverLetterPath)));
        } else {
            $payload['coverLetter'] = false;
        }

        $attachmentPath = $this->getAttachment();
        $payload['fileName'] = basename($attachmentPath);
        $payload['data'] = chunk_split(base64_encode(file_get_contents($attachmentPath)));

        if ($this->deliveryOptions !== null) {
            $payload = array_merge($payload, $this->deliveryOptions->getData());
        }

        if ($this->testEmail !== null && $this->testEmail !== '') {
            $payload['testFlag'] = true;
            $payload['testEMail'] = $this->testEmail;
        }

        return $payload;
    }

    public function send(): self
    {
        $results = $this->postLetters([$this->buildLetterPayload()]);
        $this->letterId = (string) $results[0]->getLetterId();
        return $this;
    }

    /**
     * @param Letter[] $letters
     * @return LetterSendResult[]
     */
    public function sendBatch(array $letters): array
    {
        if ($letters === []) {
            return [];
        }
        $payloads = [];
        foreach ($letters as $letter) {
            if (!$letter instanceof Letter) {
                throw new \InvalidArgumentException('All items must be Letter instances');
            }
            $payloads[] = $letter->buildLetterPayload();
        }
        return $this->postLetters($payloads);
    }

    /**
     * @param array<int, array<string, mixed>> $payloads
     * @return LetterSendResult[]
     */
    private function postLetters(array $payloads): array
    {
        $requestOptions = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payloads),
        ];
        try {
            $response = $this->getHttpClient(self::API_ENDPOINT)
                ->request('POST', '/api/Letter', $requestOptions);
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => LetterSendResult::fromArray($item), $decoded);
    }

    public function getLetterStatus(?string $letterId = null): LetterStatus
    {
        $id = $letterId ?? $this->getLetterId();
        try {
            $response = $this->getHttpClient(self::API_ENDPOINT)
                ->request('GET', '/api/Letter/' . $id);
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }
        return new LetterStatus(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * @param int[] $letterIds
     * @return LetterStatus[]
     */
    public function getMultipleLetterStatuses(array $letterIds = [], bool $onlyIssues = false): array
    {
        $requestOptions = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($letterIds),
        ];
        try {
            $response = $this->getHttpClient(self::API_ENDPOINT)
                ->request('POST', '/api/Letter/StatusQuery?onlyIssues=' . ($onlyIssues ? 'true' : 'false'), $requestOptions);
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => new LetterStatus($item), $decoded);
    }

    /**
     * @return LetterStatus[]
     */
    public function getLetterStatusByDateRange(string $fromDate, string $tillDate, bool $onlyIssues = false): array
    {
        $response = $this->getHttpClient(self::API_ENDPOINT)->request('GET', '/api/Letter/Date', [
            'query' => [
                'fromDate' => $fromDate,
                'tillDate' => $tillDate,
                'onlyIssues' => $onlyIssues ? 'true' : 'false',
            ],
        ]);
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => new LetterStatus($item), $decoded);
    }

    /**
     * @return LetterStatus[]
     */
    public function getOpenLetters(): array
    {
        $response = $this->getHttpClient(self::API_ENDPOINT)->request('GET', '/api/Letter/Open');
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => new LetterStatus($item), $decoded);
    }

    /**
     * @return LetterStatus[]
     */
    public function getRegisteredLetterStatus(string $fromDate, string $tillDate, bool $onlyOpen = false): array
    {
        $response = $this->getHttpClient(self::API_ENDPOINT)->request('GET', '/api/Letter/Registered', [
            'query' => [
                'fromDate' => $fromDate,
                'tillDate' => $tillDate,
                'onlyOpen' => $onlyOpen ? 'true' : 'false',
            ],
        ]);
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => new LetterStatus($item), $decoded);
    }

    /**
     * @return LetterStatus[]
     */
    public function getLetterStatusByCustom1(string $custom1): array
    {
        $response = $this->getHttpClient(self::API_ENDPOINT)->request('GET', '/api/Letter/Custom1', [
            'query' => ['custom1' => $custom1],
        ]);
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => new LetterStatus($item), $decoded);
    }

    /**
     * @return LetterStatus[]
     */
    public function getLetterStatusByBatch(int $batchId): array
    {
        $response = $this->getHttpClient(self::API_ENDPOINT)->request('GET', '/api/Letter/Batch', [
            'query' => ['batchID' => $batchId],
        ]);
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => new LetterStatus($item), $decoded);
    }

    /**
     * @param int[] $letterIds
     * @return QueuedOperationResult[]
     */
    public function cancelQueued(array $letterIds): array
    {
        $requestOptions = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($letterIds),
        ];
        $response = $this->getHttpClient(self::API_ENDPOINT)->request('POST', '/api/Letter/CancelQueued', $requestOptions);
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => QueuedOperationResult::fromArray($item), $decoded);
    }

    /**
     * @param int[] $letterIds
     * @return QueuedOperationResult[]
     */
    public function releaseQueued(array $letterIds): array
    {
        $requestOptions = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($letterIds),
        ];
        $response = $this->getHttpClient(self::API_ENDPOINT)->request('POST', '/api/Letter/ReleaseQueued', $requestOptions);
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => QueuedOperationResult::fromArray($item), $decoded);
    }

    /**
     * @return LetterStatus[]
     */
    public function getPremiumAdressFeedback(string $fromDate, string $tillDate): array
    {
        $response = $this->getHttpClient(self::API_ENDPOINT)->request('GET', '/api/Letter/PremiumAdressFeedback', [
            'query' => ['fromDate' => $fromDate, 'tillDate' => $tillDate],
        ]);
        $decoded = json_decode($response->getBody()->getContents(), true) ?? [];
        return array_map(fn (array $item) => new LetterStatus($item), $decoded);
    }

    /**
     * @param string|null $letterId Letter ID for the test send (defaults to current letter ID if set)
     */
    public function getTestResult(?string $letterId = null): LetterDataResult
    {
        $id = $letterId ?? $this->getLetterId();
        $response = $this->getHttpClient(self::API_ENDPOINT)->request('GET', '/api/Letter/TestResult', [
            'query' => ['letterID' => $id],
        ]);
        return LetterDataResult::fromArray(json_decode($response->getBody()->getContents(), true) ?? []);
    }

    private function getHttpClient(string $baseUri): HttpClient
    {
        if ($this->httpClient !== null) {
            return $this->httpClient;
        }
        return new HttpClient([
            'base_uri' => $baseUri,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken()->getToken(),
            ],
        ]);
    }

    protected function throwErrorException(ClientException $e): never
    {
        $body = $e->getResponse()->getBody()->getContents();
        throw new Exception\ErrorException(
            Error::fromArray(json_decode($body, true) ?? [])
        );
    }
}

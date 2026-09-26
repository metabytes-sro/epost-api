<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use MetabytesSRO\EPost\Api\Exception\InvalidFileFormat;
use MetabytesSRO\EPost\Api\Exception\InvalidFileFormatException;
use MetabytesSRO\EPost\Api\Exception\MissingAttachmentException;
use MetabytesSRO\EPost\Api\Exception\MissingAuthorizationTokenException;
use MetabytesSRO\EPost\Api\Exception\MissingEnvelopeException;
use MetabytesSRO\EPost\Api\Exception\MissingPreconditionException;
use MetabytesSRO\EPost\Api\Exception\MissingRecipientException;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Metadata\DeliveryOptions;
use MetabytesSRO\EPost\Api\Metadata\Envelope;
use RuntimeException;
use stdClass;

/**
 * Building a letter and its payload, without any HTTP.
 */
class LetterTest extends ApiTestCase
{
    public function testBuildLetterPayloadRequiresEnvelope(): void
    {
        $letter = (new Letter())->setAttachment($this->createTempPdf());

        $this->expectException(MissingEnvelopeException::class);
        $letter->buildLetterPayload();
    }

    public function testBuildLetterPayloadRequiresRecipient(): void
    {
        $letter = (new Letter())
            ->setEnvelope(new Envelope())
            ->setAttachment($this->createTempPdf());

        $this->expectException(MissingRecipientException::class);
        $letter->buildLetterPayload();
    }

    public function testBuildLetterPayloadRequiresAttachment(): void
    {
        $letter = (new Letter())->setEnvelope($this->createEnvelope());

        $this->expectException(MissingAttachmentException::class);
        $letter->buildLetterPayload();
    }

    public function testBuildLetterPayloadDoesNotRequireAccessToken(): void
    {
        $letter = (new Letter())
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf());

        $payload = $letter->buildLetterPayload();

        self::assertArrayHasKey('data', $payload);
    }

    public function testBuildLetterPayloadContainsRecipientAndEncodedAttachment(): void
    {
        $pdf = $this->createTempPdf('%PDF-1.4 test');
        $letter = (new Letter())
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($pdf);

        $payload = $letter->buildLetterPayload();

        self::assertSame('Test', $payload['addressLine1']);
        self::assertSame('53115', $payload['zipCode']);
        self::assertSame('Bonn', $payload['city']);
        self::assertSame(basename($pdf), $payload['fileName']);
        self::assertSame(chunk_split(base64_encode('%PDF-1.4 test')), $payload['data']);
        self::assertFalse($payload['coverLetter']);
        self::assertArrayNotHasKey('coverData', $payload);
        self::assertArrayNotHasKey('testFlag', $payload);
    }

    public function testBuildLetterPayloadIncludesCoverLetter(): void
    {
        $cover = $this->createTempPdf('%PDF-1.4 cover');
        $letter = (new Letter())
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf())
            ->setCoverLetter($cover);

        $payload = $letter->buildLetterPayload();

        self::assertSame($cover, $letter->getCoverLetter());
        self::assertTrue($payload['coverLetter']);
        self::assertSame(chunk_split(base64_encode('%PDF-1.4 cover')), $payload['coverData']);
    }

    public function testCoverLetterCanBeUnset(): void
    {
        $letter = (new Letter())
            ->setCoverLetter($this->createTempPdf())
            ->setCoverLetter(null);

        self::assertNull($letter->getCoverLetter());
    }

    public function testBuildLetterPayloadMergesDeliveryOptions(): void
    {
        $options = (new DeliveryOptions())->setColorColored()->setDuplex(true)->setRegisteredStandard();
        $letter = (new Letter())
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf())
            ->setDeliveryOptions($options);

        $payload = $letter->buildLetterPayload();

        self::assertSame($options, $letter->getDeliveryOptions());
        self::assertTrue($payload['isColor']);
        self::assertTrue($payload['isDuplex']);
        self::assertSame('Einschreiben', $payload['registeredLetter']);
    }

    public function testBuildLetterPayloadSetsTestFlagForTestEmail(): void
    {
        $letter = (new Letter())
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf())
            ->setTestEmail('test@example.com');

        $payload = $letter->buildLetterPayload();

        self::assertSame('test@example.com', $letter->getTestEmail());
        self::assertTrue($payload['testFlag']);
        self::assertSame('test@example.com', $payload['testEMail']);
    }

    public function testEmptyTestEmailDoesNotSetTestFlag(): void
    {
        $letter = (new Letter())
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf())
            ->setTestEmail('');

        self::assertArrayNotHasKey('testFlag', $letter->buildLetterPayload());
    }

    public function testSetAttachmentRejectsMissingFile(): void
    {
        $this->expectException(InvalidFileFormatException::class);
        $this->expectExceptionMessage('does not exist');
        (new Letter())->setAttachment('/nonexistent/file.pdf');
    }

    public function testSetAttachmentRejectsNonPdf(): void
    {
        $file = $this->createTempFile('.txt', 'just text');

        $this->expectException(InvalidFileFormatException::class);
        $this->expectExceptionMessage('Allowed: pdf');
        (new Letter())->setAttachment($file);
    }

    public function testInvalidFileFormatExceptionIsCatchableUnderDeprecatedName(): void
    {
        try {
            (new Letter())->setAttachment('/nonexistent/file.pdf');
            self::fail('Expected exception');
        } catch (InvalidFileFormat $e) {
            self::assertInstanceOf(InvalidFileFormatException::class, $e);
        }
    }

    public function testSetCoverLetterRejectsNonPdf(): void
    {
        $file = $this->createTempFile('.txt', 'just text');

        $this->expectException(InvalidFileFormatException::class);
        $this->expectExceptionMessage('cover letter');
        (new Letter())->setCoverLetter($file);
    }

    public function testAttachmentDeletedAfterSetIsReportedWhenBuildingPayload(): void
    {
        $pdf = $this->createTempPdf();
        $letter = (new Letter())->setEnvelope($this->createEnvelope())->setAttachment($pdf);
        unlink($pdf);

        $this->expectException(InvalidFileFormatException::class);
        $this->expectExceptionMessage('could not be read');
        @$letter->buildLetterPayload();
    }

    public function testGetAttachmentRequiresAttachment(): void
    {
        $this->expectException(MissingAttachmentException::class);
        (new Letter())->getAttachment();
    }

    public function testGetAttachmentReturnsPath(): void
    {
        $pdf = $this->createTempPdf();

        self::assertSame($pdf, (new Letter())->setAttachment($pdf)->getAttachment());
    }

    public function testGetEnvelopeReturnsEnvelope(): void
    {
        $envelope = $this->createEnvelope();

        self::assertSame($envelope, (new Letter())->setEnvelope($envelope)->getEnvelope());
    }

    public function testGetAccessTokenRequiresToken(): void
    {
        $this->expectException(MissingAuthorizationTokenException::class);
        (new Letter())->getAccessToken();
    }

    public function testGetAccessTokenReturnsToken(): void
    {
        $token = $this->createToken();

        self::assertSame($token, (new Letter())->setAccessToken($token)->getAccessToken());
    }

    public function testGetLetterIdRequiresLetterId(): void
    {
        $this->expectException(MissingPreconditionException::class);
        (new Letter())->getLetterId();
    }

    public function testGetLetterIdRejectsEmptyString(): void
    {
        $this->expectException(MissingPreconditionException::class);
        (new Letter())->setLetterId('')->getLetterId();
    }

    public function testLetterIdCanBeSet(): void
    {
        self::assertSame('42', (new Letter())->setLetterId('42')->getLetterId());
    }

    public function testTestEnvironmentFlag(): void
    {
        $letter = new Letter();

        self::assertFalse($letter->isTestEnvironment());
        self::assertTrue($letter->setTestEnvironment(true)->isTestEnvironment());
    }

    public function testSendBatchRequiresLetterInstances(): void
    {
        $letter = (new Letter())->setAccessToken($this->createToken());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Letter instances');
        // Deliberately wrong input: the runtime check guards callers without static analysis.
        // @phpstan-ignore argument.type
        $letter->sendBatch([new stdClass()]);
    }

    public function testSendBatchReturnsEmptyForEmptyArray(): void
    {
        self::assertSame([], (new Letter())->sendBatch([]));
    }

    public function testSendWithoutAccessTokenFailsBeforeAnyRequest(): void
    {
        $letter = (new Letter())
            ->setEnvelope($this->createEnvelope())
            ->setAttachment($this->createTempPdf());

        $this->expectException(MissingAuthorizationTokenException::class);
        $letter->send();
    }

    public function testDefaultHttpClientCarriesBaseUriAndBearerToken(): void
    {
        $letter = new class extends Letter {
            /** @var array<string, mixed> */
            public array $config = [];
            public ?ClientInterface $created = null;

            /**
             * @param array<string, mixed> $config
             */
            protected function createHttpClient(array $config): ClientInterface
            {
                $this->config = $config;
                $this->created = parent::createHttpClient($config);

                // Never let the test reach the network.
                throw new RuntimeException('stop');
            }
        };
        $letter->setAccessToken($this->createToken())->setLetterId('1');

        try {
            $letter->getLetterStatus();
        } catch (RuntimeException $e) {
            self::assertSame('stop', $e->getMessage());
        }

        self::assertInstanceOf(Client::class, $letter->created);
        self::assertSame(Letter::API_ENDPOINT, $letter->config['base_uri']);
        self::assertSame(['Authorization' => 'Bearer test-token'], $letter->config['headers']);
    }
}

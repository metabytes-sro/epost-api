<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\LetterStatus;
use MetabytesSRO\EPost\Api\LetterStatusId;
use MetabytesSRO\EPost\Api\RegisteredMailType;
use MetabytesSRO\EPost\Api\TrackStatusCode;
use PHPUnit\Framework\TestCase;

class LetterStatusTest extends TestCase
{
    /**
     * A full LetterStatus object as the API sends it (all fields of the v2.6.1 schema).
     *
     * @return array<string, mixed>
     */
    public static function fixture(): array
    {
        return [
            'letterID' => 43556780,
            'fileName' => 'RE0136645.pdf',
            'statusID' => 4,
            'statusDetails' => 'Verarbeitung in Druckzentrum',
            'createdDate' => '2024-01-10T08:00:00',
            'processedDate' => '2024-01-10T08:05:00',
            'printUploadDate' => '2024-01-10T12:00:00',
            'printFeedbackDate' => '2024-01-11T09:00:00',
            'testFlag' => false,
            'testEMail' => null,
            'testShowRestrictedArea' => false,
            'registeredLetter' => 'Einschreiben',
            'registeredLetterID' => 'RR123456789DE',
            'batchID' => 12345,
            'coverLetter' => true,
            'noOfPages' => 3,
            'subVendorID' => 'sub-7',
            'custom1' => 'RE0136645',
            'custom2' => 'c2',
            'custom3' => 'c3',
            'custom4' => 'c4',
            'custom5' => 'c5',
            'zipCode' => '53115',
            'city' => 'Bonn',
            'country' => '',
            'isColor' => true,
            'isDuplex' => false,
            'registeredLetterStatus' => 'DELIVERED',
            'registeredLetterStatusDate' => '2024-01-15',
            'vendorSystemInformation' => 'my-erp 1.2',
            'costCenter' => 'KST01',
            'frankierID' => 'FR-1',
            'destinationAreaStatus' => 'ARRIVED',
            'destinationAreaStatusDate' => '2024-01-12',
            'errorList' => [
                ['level' => 'Warning', 'code' => 'W201', 'description' => 'Überschreitung Adressbereich', 'date' => '2024-01-10T08:05:00'],
                ['level' => 'Error', 'code' => 'E318', 'description' => 'Ablehnung in Druckzentrum'],
                ['level' => 'Info', 'code' => 'I101', 'description' => 'PDF -> PDFA'],
                'not an object',
            ],
            'plugInFeedbackList' => [
                ['plugInName' => 'PremiumAdress', 'plugInFeedbackModel' => ['productVariants' => 'Basic']],
            ],
        ];
    }

    public function testFromArrayMapsEveryField(): void
    {
        $status = LetterStatus::fromArray(self::fixture());

        self::assertSame(43556780, $status->letterId);
        self::assertSame('RE0136645.pdf', $status->fileName);
        self::assertSame(4, $status->statusId);
        self::assertSame(LetterStatusId::ProcessingInPrintingCenter, $status->status());
        self::assertSame('Verarbeitung in Druckzentrum', $status->statusDetails);
        self::assertSame('2024-01-10 08:00:00', $status->createdDate?->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-10 08:05:00', $status->processedDate?->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-10 12:00:00', $status->printUploadDate?->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-11 09:00:00', $status->printFeedbackDate?->format('Y-m-d H:i:s'));
        self::assertFalse($status->testFlag);
        self::assertNull($status->testEmail);
        self::assertFalse($status->testShowRestrictedArea);
        self::assertSame('Einschreiben', $status->registeredLetter);
        self::assertTrue($status->isRegisteredMail());
        self::assertSame(RegisteredMailType::Standard, $status->registeredMailType());
        self::assertSame('RR123456789DE', $status->registeredLetterId);
        self::assertSame('DELIVERED', $status->registeredLetterStatus);
        self::assertSame(TrackStatusCode::Delivered, $status->trackingStatus());
        self::assertSame('2024-01-15', $status->registeredLetterStatusDate?->format('Y-m-d'));
        self::assertSame(12345, $status->batchId);
        self::assertTrue($status->coverLetter);
        self::assertSame(3, $status->numberOfPages);
        self::assertSame('sub-7', $status->subVendorId);
        self::assertSame('RE0136645', $status->custom1);
        self::assertSame('c2', $status->custom2);
        self::assertSame('c3', $status->custom3);
        self::assertSame('c4', $status->custom4);
        self::assertSame('c5', $status->custom5);
        self::assertSame('53115', $status->zipCode);
        self::assertSame('Bonn', $status->city);
        self::assertSame('', $status->country);
        self::assertTrue($status->isColor);
        self::assertFalse($status->isDuplex);
        self::assertSame('my-erp 1.2', $status->vendorSystemInformation);
        self::assertSame('KST01', $status->costCenter);
        self::assertSame('FR-1', $status->frankierId);
        self::assertSame('ARRIVED', $status->destinationAreaStatus);
        self::assertSame('2024-01-12', $status->destinationAreaStatusDate?->format('Y-m-d'));
        self::assertSame(self::fixture(), $status->raw);

        self::assertCount(3, $status->errors);
        self::assertSame('W201', $status->errors[0]->code);
        self::assertSame('2024-01-10 08:05:00', $status->errors[0]->date?->format('Y-m-d H:i:s'));
        self::assertSame(['E318'], array_map(static fn($e) => $e->code, $status->errorsOnly()));
        self::assertSame(['W201'], array_map(static fn($e) => $e->code, $status->warnings()));

        self::assertCount(1, $status->plugInFeedback);
        self::assertSame('PremiumAdress', $status->plugInFeedback[0]->name);
        self::assertSame(['productVariants' => 'Basic'], $status->plugInFeedback[0]->model);
    }

    public function testDefaultsForEmptyObject(): void
    {
        $status = LetterStatus::fromArray([]);

        self::assertSame(0, $status->letterId);
        self::assertSame(0, $status->statusId);
        self::assertNull($status->status());
        self::assertNull($status->fileName);
        self::assertNull($status->createdDate);
        self::assertFalse($status->testFlag);
        self::assertNull($status->registeredLetter);
        self::assertFalse($status->isRegisteredMail());
        self::assertNull($status->registeredMailType());
        self::assertNull($status->trackingStatus());
        self::assertNull($status->batchId);
        self::assertSame([], $status->errors);
        self::assertSame([], $status->plugInFeedback);
        self::assertSame([], $status->raw);
        self::assertFalse($status->isOpen());
        self::assertFalse($status->isSent());
        self::assertFalse($status->hasError());
    }

    public function testUnknownEnumValuesYieldNull(): void
    {
        $status = LetterStatus::fromArray([
            'statusID' => 50,
            'registeredLetter' => 'Einschreiben eigenhändig',
            'registeredLetterStatus' => 'TELEPORTED',
        ]);

        self::assertNull($status->status());
        self::assertTrue($status->isRegisteredMail());
        self::assertNull($status->registeredMailType());
        self::assertNull($status->trackingStatus());
    }

    public function testEmptyRegisteredLetterCountsAsNotRegistered(): void
    {
        self::assertFalse(LetterStatus::fromArray(['registeredLetter' => ''])->isRegisteredMail());
    }

    public function testStatusHelpers(): void
    {
        self::assertTrue(LetterStatus::fromArray(['statusID' => 1])->isOpen());
        self::assertTrue(LetterStatus::fromArray(['statusID' => 3])->isOpen());
        self::assertFalse(LetterStatus::fromArray(['statusID' => 4])->isOpen());
        self::assertTrue(LetterStatus::fromArray(['statusID' => 4])->isSent());
        self::assertTrue(LetterStatus::fromArray(['statusID' => '99'])->hasError());
    }

    public function testMalformedListsAreIgnored(): void
    {
        $status = LetterStatus::fromArray(['errorList' => 'oops', 'plugInFeedbackList' => 42]);

        self::assertSame([], $status->errors);
        self::assertSame([], $status->plugInFeedback);
    }
}

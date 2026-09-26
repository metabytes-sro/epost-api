<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\LetterStatus;
use MetabytesSRO\EPost\Api\LetterStatusError;
use MetabytesSRO\EPost\Api\LetterStatusId;
use PHPUnit\Framework\TestCase;

class LetterStatusTest extends TestCase
{
    /**
     * A full LetterStatus object as the API sends it (all fields of the v2.6.1 schema).
     *
     * @return array<string, mixed>
     */
    private static function fixture(): array
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
            ],
            'plugInFeedbackList' => [
                ['plugInName' => 'PremiumAdress', 'plugInFeedbackModel' => ['productVariants' => 'Basic']],
            ],
        ];
    }

    public function testTypedGetters(): void
    {
        $status = new LetterStatus(self::fixture());

        self::assertSame(43556780, $status->getLetterId());
        self::assertSame('RE0136645.pdf', $status->getFileName());
        self::assertSame(4, $status->getStatusId());
        self::assertSame(LetterStatusId::ProcessingInPrintingCenter, $status->getStatus());
        self::assertSame('Verarbeitung in Druckzentrum', $status->getStatusDetails());
        self::assertSame('2024-01-10T08:00:00', $status->getCreatedDate());
        self::assertSame('2024-01-10T08:05:00', $status->getProcessedDate());
        self::assertSame('2024-01-10T12:00:00', $status->getPrintUploadDate());
        self::assertSame('2024-01-11T09:00:00', $status->getPrintFeedbackDate());
        self::assertFalse($status->isTestFlag());
        self::assertNull($status->getTestEmail());
        self::assertFalse($status->isTestShowRestrictedArea());
        self::assertSame('Einschreiben', $status->getRegisteredLetter());
        self::assertTrue($status->isRegisteredLetter());
        self::assertSame('RR123456789DE', $status->getRegisteredLetterId());
        self::assertSame(12345, $status->getBatchId());
        self::assertTrue($status->hasCoverLetter());
        self::assertSame(3, $status->getNumberOfPages());
        self::assertSame('sub-7', $status->getSubVendorId());
        self::assertSame('RE0136645', $status->getCustom1());
        self::assertSame('c2', $status->getCustom2());
        self::assertSame('c3', $status->getCustom3());
        self::assertSame('c4', $status->getCustom4());
        self::assertSame('c5', $status->getCustom5());
        self::assertSame('53115', $status->getZipCode());
        self::assertSame('Bonn', $status->getCity());
        self::assertSame('', $status->getCountry());
        self::assertTrue($status->isColor());
        self::assertFalse($status->isDuplex());
        self::assertSame('DELIVERED', $status->getRegisteredLetterStatus());
        self::assertSame('2024-01-15', $status->getRegisteredLetterStatusDate());
        self::assertSame('my-erp 1.2', $status->getVendorSystemInformation());
        self::assertSame('KST01', $status->getCostCenter());
        self::assertSame('FR-1', $status->getFrankierId());
        self::assertSame('ARRIVED', $status->getDestinationAreaStatus());
        self::assertSame('2024-01-12', $status->getDestinationAreaStatusDate());
    }

    public function testGettersReturnNullOrFalseForMissingFields(): void
    {
        $status = new LetterStatus([]);

        self::assertSame(0, $status->getLetterId());
        self::assertSame(0, $status->getStatusId());
        self::assertNull($status->getStatus());
        self::assertNull($status->getFileName());
        self::assertNull($status->getStatusDetails());
        self::assertNull($status->getCreatedDate());
        self::assertNull($status->getProcessedDate());
        self::assertNull($status->getPrintUploadDate());
        self::assertNull($status->getPrintFeedbackDate());
        self::assertFalse($status->isTestFlag());
        self::assertNull($status->getTestEmail());
        self::assertNull($status->getRegisteredLetter());
        self::assertFalse($status->isRegisteredLetter());
        self::assertNull($status->getRegisteredLetterId());
        self::assertNull($status->getBatchId());
        self::assertFalse($status->hasCoverLetter());
        self::assertNull($status->getNumberOfPages());
        self::assertNull($status->getSubVendorId());
        self::assertNull($status->getCustom1());
        self::assertNull($status->getZipCode());
        self::assertNull($status->getCity());
        self::assertNull($status->getCountry());
        self::assertFalse($status->isColor());
        self::assertFalse($status->isDuplex());
        self::assertNull($status->getRegisteredLetterStatus());
        self::assertNull($status->getRegisteredLetterStatusDate());
        self::assertNull($status->getVendorSystemInformation());
        self::assertNull($status->getCostCenter());
        self::assertNull($status->getFrankierId());
        self::assertNull($status->getDestinationAreaStatus());
        self::assertNull($status->getDestinationAreaStatusDate());
        self::assertSame([], $status->getErrors());
        self::assertSame([], $status->getPlugInFeedback());
        self::assertFalse($status->isOpen());
        self::assertFalse($status->isSent());
        self::assertFalse($status->hasError());
    }

    public function testEmptyRegisteredLetterCountsAsNotRegistered(): void
    {
        self::assertFalse((new LetterStatus(['registeredLetter' => '']))->isRegisteredLetter());
    }

    public function testStatusHelpers(): void
    {
        self::assertTrue((new LetterStatus(['statusID' => 1]))->isOpen());
        self::assertTrue((new LetterStatus(['statusID' => 3]))->isOpen());
        self::assertFalse((new LetterStatus(['statusID' => 4]))->isOpen());
        self::assertTrue((new LetterStatus(['statusID' => 4]))->isSent());
        self::assertTrue((new LetterStatus(['statusID' => 99]))->hasError());
        self::assertNull((new LetterStatus(['statusID' => 50]))->getStatus());
        self::assertSame(LetterStatusId::ProcessingError, (new LetterStatus(['statusID' => '99']))->getStatus());
    }

    public function testGetErrorsReturnsTypedList(): void
    {
        $status = new LetterStatus(self::fixture());

        $errors = $status->getErrors();

        self::assertCount(1, $errors);
        self::assertInstanceOf(LetterStatusError::class, $errors[0]);
        self::assertSame('W201', $errors[0]->getCode());
        self::assertSame('Überschreitung Adressbereich', $errors[0]->getDescription());
        self::assertSame('2024-01-10T08:05:00', $errors[0]->getDate());
        self::assertTrue($errors[0]->isWarning());
    }

    public function testGetErrorsIgnoresMalformedItems(): void
    {
        $status = new LetterStatus(['errorList' => ['not an object', ['code' => 'E301']]]);

        $errors = $status->getErrors();

        self::assertCount(1, $errors);
        self::assertSame('E301', $errors[0]->getCode());
    }

    public function testGetErrorsWithNonListValue(): void
    {
        self::assertSame([], (new LetterStatus(['errorList' => 'oops']))->getErrors());
    }

    public function testGetPlugInFeedback(): void
    {
        $status = new LetterStatus(self::fixture());

        $feedback = $status->getPlugInFeedback();

        self::assertCount(1, $feedback);
        self::assertSame('PremiumAdress', $feedback[0]['plugInName']);
        self::assertSame(['productVariants' => 'Basic'], $feedback[0]['plugInFeedbackModel']);
    }

    public function testRawAccess(): void
    {
        $status = new LetterStatus(['letterID' => 1, 'somethingNew' => 'x']);

        self::assertSame('x', $status->get('somethingNew'));
        self::assertNull($status->get('missing'));
        self::assertSame(['letterID' => 1, 'somethingNew' => 'x'], $status->toArray());
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Metadata;

use InvalidArgumentException;
use MetabytesSRO\EPost\Api\Metadata\DeliveryOptions;
use MetabytesSRO\EPost\Api\Metadata\RegisteredLetterReturnAddress;
use PHPUnit\Framework\TestCase;

class DeliveryOptionsTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $options = new DeliveryOptions();

        self::assertSame([], $options->getData());
        self::assertSame([], $options->jsonSerialize());
        self::assertFalse($options->getColor());
        self::assertFalse($options->getDuplex());
        self::assertFalse($options->getTestFlag());
        self::assertSame('', $options->getTestEMail());
        self::assertFalse($options->getTestShowRestrictedArea());
        self::assertFalse($options->getCoverLetter());
        self::assertNull($options->getRegistered());
        self::assertFalse($options->isRegistered());
        self::assertNull($options->getRegisteredLetterReturnAddress());
    }

    public function testColorOptions(): void
    {
        self::assertTrue((new DeliveryOptions())->setColorColored()->getColor());
        self::assertFalse((new DeliveryOptions())->setColorColored()->setColorGrayscale()->getColor());
        self::assertSame(['isColor' => true], (new DeliveryOptions())->setColor(true)->getData());
    }

    public function testDuplex(): void
    {
        self::assertSame(['isDuplex' => true], (new DeliveryOptions())->setDuplex(true)->getData());
        self::assertTrue((new DeliveryOptions())->setDuplex(true)->getDuplex());
    }

    public function testTestModeOptions(): void
    {
        $options = (new DeliveryOptions())
            ->setTestFlag(true)
            ->setTestEMail('test@example.com')
            ->setTestShowRestrictedArea(true);

        self::assertTrue($options->getTestFlag());
        self::assertSame('test@example.com', $options->getTestEMail());
        self::assertTrue($options->getTestShowRestrictedArea());
        self::assertSame([
            'testFlag' => true,
            'testEMail' => 'test@example.com',
            'testShowRestrictedArea' => true,
        ], $options->getData());
    }

    public function testCoverLetterOptions(): void
    {
        self::assertTrue((new DeliveryOptions())->setCoverLetterIncluded()->getCoverLetter());
        self::assertFalse((new DeliveryOptions())->setCoverLetterIncluded()->setCoverLetterGenerate()->getCoverLetter());
        self::assertSame(['coverLetter' => true], (new DeliveryOptions())->setCoverLetter(true)->getData());
    }

    public function testRegisteredOptions(): void
    {
        self::assertSame('Einschreiben', (new DeliveryOptions())->setRegisteredStandard()->getRegistered());
        self::assertSame('Einwurf Einschreiben', (new DeliveryOptions())->setRegisteredSubmissionOnly()->getRegistered());
        self::assertSame('Einschreiben Rückschein', (new DeliveryOptions())->setRegisteredWithReturnReceipt()->getRegistered());
        self::assertNull((new DeliveryOptions())->setRegisteredStandard()->setRegisteredNo()->getRegistered());
        self::assertTrue((new DeliveryOptions())->setRegisteredStandard()->isRegistered());
        self::assertFalse((new DeliveryOptions())->setRegisteredNo()->isRegistered());
    }

    public function testDeprecatedRegisteredOptionsStillAccepted(): void
    {
        self::assertSame('Einschreiben eigenhändig', (new DeliveryOptions())->setRegisteredAddresseeOnly()->getRegistered());
        self::assertSame(
            'Einschreiben eigenhändig Rückschein',
            (new DeliveryOptions())->setRegisteredAddresseeOnlyWithReturnReceipt()->getRegistered(),
        );
    }

    public function testSetRegisteredAcceptsEveryListedOption(): void
    {
        foreach (DeliveryOptions::getOptionsForRegistered() as $option) {
            self::assertSame($option, (new DeliveryOptions())->setRegistered($option)->getRegistered());
        }
    }

    public function testSetRegisteredRejectsUnknownValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Eilbrief');
        (new DeliveryOptions())->setRegistered('Eilbrief');
    }

    public function testRegisteredNoIsSentAsNull(): void
    {
        $data = (new DeliveryOptions())->setRegisteredNo()->getData();

        self::assertArrayHasKey('registeredLetter', $data);
        self::assertNull($data['registeredLetter']);
    }

    public function testReturnReceiptNoLongerRequiresReturnAddress(): void
    {
        $data = (new DeliveryOptions())->setRegisteredWithReturnReceipt()->getData();

        self::assertSame(['registeredLetter' => 'Einschreiben Rückschein'], $data);
    }

    public function testReturnAddressIsStillSentWhenGiven(): void
    {
        $returnAddress = (new RegisteredLetterReturnAddress())
            ->setAddressLine1('Company')
            ->setZipCode('53115')
            ->setCity('Bonn');
        $options = (new DeliveryOptions())
            ->setRegisteredWithReturnReceipt()
            ->setRegisteredLetterReturnAddress($returnAddress);

        $data = $options->getData();

        self::assertSame($returnAddress, $options->getRegisteredLetterReturnAddress());
        self::assertSame('Einschreiben Rückschein', $data['registeredLetter']);
        self::assertSame('Company', $data['registeredLetterAdressLine1']);
        self::assertSame('53115', $data['registeredLetterZipCode']);
        self::assertSame('Bonn', $data['registeredLetterCity']);
        self::assertSame($data, $options->jsonSerialize());
    }
}

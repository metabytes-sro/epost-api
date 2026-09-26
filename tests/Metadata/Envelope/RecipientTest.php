<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Metadata\Envelope;

use InvalidArgumentException;
use MetabytesSRO\EPost\Api\Exception\InvalidRecipientDataException;
use MetabytesSRO\EPost\Api\Metadata\Envelope\Recipient;
use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{
    public function testGetDataReturnsAddressFields(): void
    {
        $recipient = (new Recipient())
            ->setAddressLine('Max Mustermann', 0)
            ->setAddressLine('Musterstrasse 1', 1)
            ->setAddressLine('Hinterhaus', 2)
            ->setAddressLine('2. OG', 3)
            ->setAddressLine('links', 4)
            ->setZipCode('53115')
            ->setCity('Bonn')
            ->setCountry('DEUTSCHLAND');

        self::assertSame([
            'addressLine1' => 'Max Mustermann',
            'addressLine2' => 'Musterstrasse 1',
            'addressLine3' => 'Hinterhaus',
            'addressLine4' => '2. OG',
            'addressLine5' => 'links',
            'zipCode' => '53115',
            'city' => 'Bonn',
            'country' => 'DEUTSCHLAND',
        ], $recipient->getData());
        self::assertSame($recipient->getData(), $recipient->jsonSerialize());
    }

    public function testGetters(): void
    {
        $recipient = (new Recipient())->setAddressLine('Max', 0)->setZipCode('53115')->setCity('Bonn')->setCountry('ITALIEN');

        self::assertSame('Max', $recipient->getAddressLine(0));
        self::assertNull($recipient->getAddressLine(1));
        self::assertSame('53115', $recipient->getZipCode());
        self::assertSame('Bonn', $recipient->getCity());
        self::assertSame('ITALIEN', $recipient->getCountry());
    }

    public function testEmptyRecipient(): void
    {
        $recipient = new Recipient();

        self::assertSame([], $recipient->getData());
        self::assertNull($recipient->getZipCode());
        self::assertNull($recipient->getCity());
        self::assertNull($recipient->getCountry());
    }

    public function testJsonSerializeThrowsWhenRequiredFieldsMissing(): void
    {
        $recipient = (new Recipient())->setAddressLine('Max', 0)->setCity('Bonn');

        $this->expectException(InvalidRecipientDataException::class);
        $this->expectExceptionMessage('address line 1');
        $recipient->jsonSerialize();
    }

    public function testSetAddressLineRejectsInvalidLineNumber(): void
    {
        $this->expectException(InvalidRecipientDataException::class);
        $this->expectExceptionMessage('between 0 and 4');
        (new Recipient())->setAddressLine('Test', 5);
    }

    public function testSetAddressLineRejectsNegativeLineNumber(): void
    {
        $this->expectException(InvalidRecipientDataException::class);
        (new Recipient())->setAddressLine('Test', -1);
    }

    public function testLengthIsCountedInCharactersNotBytes(): void
    {
        $eighty = str_repeat('ü', 80);

        self::assertSame($eighty, (new Recipient())->setAddressLine($eighty, 0)->getAddressLine(0));
    }

    public function testAddressLineTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"addressLine1" exceeds maximum length of 80');
        (new Recipient())->setAddressLine(str_repeat('a', 81), 0);
    }

    public function testZipCodeTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"zipCode" exceeds maximum length of 20');
        (new Recipient())->setZipCode(str_repeat('1', 21));
    }

    public function testCityTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Recipient())->setCity(str_repeat('a', 81));
    }

    public function testCountryTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Recipient())->setCountry(str_repeat('a', 81));
    }
}

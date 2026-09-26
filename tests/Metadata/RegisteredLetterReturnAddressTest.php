<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Metadata;

use InvalidArgumentException;
use MetabytesSRO\EPost\Api\Exception\InvalidRecipientDataException;
use MetabytesSRO\EPost\Api\Metadata\RegisteredLetterReturnAddress;
use PHPUnit\Framework\TestCase;

class RegisteredLetterReturnAddressTest extends TestCase
{
    public function testGetDataWithRequiredFields(): void
    {
        $address = (new RegisteredLetterReturnAddress())
            ->setAddressLine1('Company GmbH')
            ->setZipCode('53115')
            ->setCity('Bonn');

        self::assertSame([
            'registeredLetterAdressLine1' => 'Company GmbH',
            'registeredLetterZipCode' => '53115',
            'registeredLetterCity' => 'Bonn',
        ], $address->getData());
        self::assertSame($address->getData(), $address->jsonSerialize());
    }

    public function testGetters(): void
    {
        $address = (new RegisteredLetterReturnAddress())
            ->setAddressLine1('Company')
            ->setAddressLine2('Street 1')
            ->setAddressLine3('Floor 2')
            ->setZipCode('53115')
            ->setCity('Bonn');

        self::assertSame('Company', $address->getAddressLine1());
        self::assertSame('Street 1', $address->getAddressLine2());
        self::assertSame('Floor 2', $address->getAddressLine3());
        self::assertSame('53115', $address->getZipCode());
        self::assertSame('Bonn', $address->getCity());
    }

    public function testGettersReturnNullWhenUnset(): void
    {
        $address = new RegisteredLetterReturnAddress();

        self::assertNull($address->getAddressLine1());
        self::assertNull($address->getAddressLine2());
        self::assertNull($address->getAddressLine3());
        self::assertNull($address->getZipCode());
        self::assertNull($address->getCity());
    }

    public function testGetDataThrowsWhenAddressLine1Missing(): void
    {
        $address = (new RegisteredLetterReturnAddress())->setZipCode('53115')->setCity('Bonn');

        $this->expectException(InvalidRecipientDataException::class);
        $this->expectExceptionMessage('addressLine1, zipCode and city');
        $address->getData();
    }

    public function testGetDataThrowsWhenZipCodeMissing(): void
    {
        $address = (new RegisteredLetterReturnAddress())->setAddressLine1('Company')->setCity('Bonn');

        $this->expectException(InvalidRecipientDataException::class);
        $address->getData();
    }

    public function testGetDataThrowsWhenCityMissing(): void
    {
        $address = (new RegisteredLetterReturnAddress())->setAddressLine1('Company')->setZipCode('53115');

        $this->expectException(InvalidRecipientDataException::class);
        $address->getData();
    }

    public function testSetZipCodeRejectsLessThan5Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 5 characters');
        (new RegisteredLetterReturnAddress())->setZipCode('1234');
    }

    public function testOptionalAddressLinesIncludedInOutput(): void
    {
        $address = (new RegisteredLetterReturnAddress())
            ->setAddressLine1('Company')
            ->setAddressLine2('Street 1')
            ->setAddressLine3('Floor 2')
            ->setZipCode('53115')
            ->setCity('Bonn');

        $data = $address->getData();

        self::assertSame('Street 1', $data['registeredLetterAdressLine2']);
        self::assertSame('Floor 2', $data['registeredLetterAdressLine3']);
    }

    public function testOptionalAddressLinesCanBeUnsetAgain(): void
    {
        $address = (new RegisteredLetterReturnAddress())
            ->setAddressLine1('Company')
            ->setAddressLine2('Street 1')
            ->setAddressLine2(null)
            ->setAddressLine3('')
            ->setZipCode('53115')
            ->setCity('Bonn');

        $data = $address->getData();

        self::assertNull($address->getAddressLine2());
        self::assertArrayNotHasKey('registeredLetterAdressLine2', $data);
        self::assertArrayNotHasKey('registeredLetterAdressLine3', $data);
    }

    public function testAddressLineTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"registeredLetterAdressLine2" exceeds maximum length of 80');
        (new RegisteredLetterReturnAddress())->setAddressLine2(str_repeat('a', 81));
    }

    public function testAddressLine3TooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RegisteredLetterReturnAddress())->setAddressLine3(str_repeat('a', 81));
    }

    public function testLengthIsCountedInCharacters(): void
    {
        $eighty = str_repeat('ö', 80);

        self::assertSame($eighty, (new RegisteredLetterReturnAddress())->setAddressLine1($eighty)->getAddressLine1());
    }
}

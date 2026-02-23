<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Metadata;

use MetabytesSRO\EPost\Api\Exception\InvalidRecipientDataException;
use MetabytesSRO\EPost\Api\Metadata\RegisteredLetterReturnAddress;
use PHPUnit\Framework\TestCase;

class RegisteredLetterReturnAddressTest extends TestCase
{
    public function testGetDataRequiresAddressLine1ZipCodeAndCity(): void
    {
        $address = new RegisteredLetterReturnAddress();
        $address->setAddressLine1('Company GmbH');
        $address->setZipCode('53115');
        $address->setCity('Bonn');

        $data = $address->getData();
        $this->assertSame('Company GmbH', $data['registeredLetterAdressLine1']);
        $this->assertSame('53115', $data['registeredLetterZipCode']);
        $this->assertSame('Bonn', $data['registeredLetterCity']);
    }

    public function testGetDataThrowsWhenAddressLine1Missing(): void
    {
        $address = new RegisteredLetterReturnAddress();
        $address->setZipCode('53115');
        $address->setCity('Bonn');

        $this->expectException(InvalidRecipientDataException::class);
        $this->expectExceptionMessage('addressLine1, zipCode and city');
        $address->getData();
    }

    public function testGetDataThrowsWhenZipCodeMissing(): void
    {
        $address = new RegisteredLetterReturnAddress();
        $address->setAddressLine1('Company');
        $address->setCity('Bonn');

        $this->expectException(InvalidRecipientDataException::class);
        $address->getData();
    }

    public function testGetDataThrowsWhenCityMissing(): void
    {
        $address = new RegisteredLetterReturnAddress();
        $address->setAddressLine1('Company');
        $address->setZipCode('53115');

        $this->expectException(InvalidRecipientDataException::class);
        $address->getData();
    }

    public function testSetZipCodeRejectsLessThan5Characters(): void
    {
        $address = new RegisteredLetterReturnAddress();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 5 characters');
        $address->setZipCode('1234');
    }

    public function testOptionalAddressLinesIncludedInOutput(): void
    {
        $address = new RegisteredLetterReturnAddress();
        $address->setAddressLine1('Company');
        $address->setAddressLine2('Street 1');
        $address->setAddressLine3('Floor 2');
        $address->setZipCode('53115');
        $address->setCity('Bonn');

        $data = $address->getData();
        $this->assertSame('Street 1', $data['registeredLetterAdressLine2']);
        $this->assertSame('Floor 2', $data['registeredLetterAdressLine3']);
    }

    public function testJsonSerialize(): void
    {
        $address = new RegisteredLetterReturnAddress();
        $address->setAddressLine1('Company');
        $address->setZipCode('53115');
        $address->setCity('Bonn');

        $this->assertIsArray($address->jsonSerialize());
        $this->assertArrayHasKey('registeredLetterAdressLine1', $address->jsonSerialize());
    }
}

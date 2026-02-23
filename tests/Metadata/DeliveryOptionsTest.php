<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Metadata;

use MetabytesSRO\EPost\Api\Exception\MissingReturnAddressException;
use MetabytesSRO\EPost\Api\Metadata\DeliveryOptions;
use MetabytesSRO\EPost\Api\Metadata\RegisteredLetterReturnAddress;
use PHPUnit\Framework\TestCase;

class DeliveryOptionsTest extends TestCase
{
    public function testGetDataRequiresReturnAddressForRueckschein(): void
    {
        $options = new DeliveryOptions();
        $options->setRegisteredWithReturnReceipt();

        $this->expectException(MissingReturnAddressException::class);
        $this->expectExceptionMessage('RegisteredLetterReturnAddress is required');
        $options->getData();
    }

    public function testGetDataRequiresReturnAddressForAddresseeOnlyRueckschein(): void
    {
        $options = new DeliveryOptions();
        $options->setRegisteredAddresseeOnlyWithReturnReceipt();

        $this->expectException(MissingReturnAddressException::class);
        $options->getData();
    }

    public function testGetDataIncludesReturnAddressWhenSet(): void
    {
        $options = new DeliveryOptions();
        $options->setRegisteredWithReturnReceipt();
        $returnAddress = new RegisteredLetterReturnAddress();
        $returnAddress->setAddressLine1('Company')->setZipCode('53115')->setCity('Bonn');
        $options->setRegisteredLetterReturnAddress($returnAddress);

        $data = $options->getData();
        $this->assertSame('Einschreiben Rückschein', $data['registeredLetter']);
        $this->assertSame('Company', $data['registeredLetterAdressLine1']);
        $this->assertSame('53115', $data['registeredLetterZipCode']);
        $this->assertSame('Bonn', $data['registeredLetterCity']);
    }

    public function testGetDataNoReturnAddressRequiredForStandardRegistered(): void
    {
        $options = new DeliveryOptions();
        $options->setRegisteredStandard();

        $data = $options->getData();
        $this->assertSame('Einschreiben', $data['registeredLetter']);
        $this->assertArrayNotHasKey('registeredLetterAdressLine1', $data);
    }

    public function testGetDataNoReturnAddressRequiredForNoRegistered(): void
    {
        $options = new DeliveryOptions();
        $options->setRegisteredNo();

        $data = $options->getData();
        $this->assertNull($data['registeredLetter'] ?? null);
    }
}

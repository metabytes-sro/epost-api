<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Metadata\Envelope;

use MetabytesSRO\EPost\Api\Exception\InvalidRecipientDataException;
use MetabytesSRO\EPost\Api\Metadata\Envelope\Recipient;
use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{
    public function testGetDataReturnsAddressFields(): void
    {
        $recipient = new Recipient();
        $recipient
            ->setAddressLine('Max Mustermann', 0)
            ->setAddressLine('Musterstrasse 1', 1)
            ->setZipCode('53115')
            ->setCity('Bonn');

        $data = $recipient->getData();
        $this->assertSame('Max Mustermann', $data['addressLine1']);
        $this->assertSame('Musterstrasse 1', $data['addressLine2']);
        $this->assertSame('53115', $data['zipCode']);
        $this->assertSame('Bonn', $data['city']);
    }

    public function testJsonSerializeThrowsWhenRequiredFieldsMissing(): void
    {
        $recipient = new Recipient();
        // Recipient requires addressLine1 (via getAddressLine(0)), city and zipCode
        // Empty recipient should throw
        $this->expectException(InvalidRecipientDataException::class);
        $this->expectExceptionMessage('address line 1');
        $recipient->jsonSerialize();
    }

    public function testSetAddressLineRejectsInvalidLineNumber(): void
    {
        $recipient = new Recipient();
        $this->expectException(InvalidRecipientDataException::class);
        $this->expectExceptionMessage('between 0 and 4');
        $recipient->setAddressLine('Test', 5);
    }
}

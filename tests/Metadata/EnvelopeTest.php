<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Metadata;

use MetabytesSRO\EPost\Api\Metadata\Envelope;
use MetabytesSRO\EPost\Api\Metadata\Envelope\Recipient;
use PHPUnit\Framework\TestCase;

class EnvelopeTest extends TestCase
{
    public function testEmptyEnvelope(): void
    {
        $envelope = new Envelope();

        self::assertNull($envelope->getRecipient());
        self::assertNull($envelope->getData());
        self::assertSame([], $envelope->jsonSerialize());
    }

    public function testEnvelopeWithRecipient(): void
    {
        $recipient = (new Recipient())->setAddressLine('Max', 0)->setZipCode('53115')->setCity('Bonn');
        $envelope = (new Envelope())->setRecipient($recipient);

        self::assertSame($recipient, $envelope->getRecipient());
        self::assertSame(['addressLine1' => 'Max', 'zipCode' => '53115', 'city' => 'Bonn'], $envelope->getData());
        self::assertSame($envelope->getData(), $envelope->jsonSerialize());
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\LetterStatusId;
use PHPUnit\Framework\TestCase;

class LetterStatusIdTest extends TestCase
{
    public function testFromStatusId(): void
    {
        self::assertSame(LetterStatusId::AcceptanceOfShipment, LetterStatusId::fromStatusId(1));
        self::assertSame(LetterStatusId::ProcessingTheShipment, LetterStatusId::fromStatusId(2));
        self::assertSame(LetterStatusId::DeliveryToThePrintingCenter, LetterStatusId::fromStatusId(3));
        self::assertSame(LetterStatusId::ProcessingInPrintingCenter, LetterStatusId::fromStatusId(4));
        self::assertSame(LetterStatusId::ProcessingError, LetterStatusId::fromStatusId(99));
        self::assertNull(LetterStatusId::fromStatusId(5));
        self::assertNull(LetterStatusId::fromStatusId(0));
    }

    public function testPhaseHelpers(): void
    {
        foreach ([LetterStatusId::AcceptanceOfShipment, LetterStatusId::ProcessingTheShipment, LetterStatusId::DeliveryToThePrintingCenter] as $open) {
            self::assertTrue($open->isOpen(), $open->name);
            self::assertFalse($open->isSent(), $open->name);
            self::assertFalse($open->isError(), $open->name);
        }

        self::assertFalse(LetterStatusId::ProcessingInPrintingCenter->isOpen());
        self::assertTrue(LetterStatusId::ProcessingInPrintingCenter->isSent());
        self::assertFalse(LetterStatusId::ProcessingInPrintingCenter->isError());

        self::assertFalse(LetterStatusId::ProcessingError->isOpen());
        self::assertFalse(LetterStatusId::ProcessingError->isSent());
        self::assertTrue(LetterStatusId::ProcessingError->isError());
    }

    public function testLabels(): void
    {
        self::assertSame('Annahme der Sendung', LetterStatusId::AcceptanceOfShipment->label());
        self::assertSame('Verarbeitung der Sendung', LetterStatusId::ProcessingTheShipment->label());
        self::assertSame('Einlieferung in Druckzentrum', LetterStatusId::DeliveryToThePrintingCenter->label());
        self::assertSame('Verarbeitung in Druckzentrum', LetterStatusId::ProcessingInPrintingCenter->label());
        self::assertSame('Verarbeitungsfehler', LetterStatusId::ProcessingError->label());
    }
}

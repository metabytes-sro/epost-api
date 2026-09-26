<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\LetterStatusId;
use MetabytesSRO\EPost\Api\RegisteredMailType;
use MetabytesSRO\EPost\Api\TrackStatusCode;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function testLetterStatusIdPhases(): void
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

    public function testLetterStatusIdLabels(): void
    {
        self::assertSame('Annahme der Sendung', LetterStatusId::AcceptanceOfShipment->label());
        self::assertSame('Verarbeitung der Sendung', LetterStatusId::ProcessingTheShipment->label());
        self::assertSame('Einlieferung in Druckzentrum', LetterStatusId::DeliveryToThePrintingCenter->label());
        self::assertSame('Verarbeitung in Druckzentrum', LetterStatusId::ProcessingInPrintingCenter->label());
        self::assertSame('Verarbeitungsfehler', LetterStatusId::ProcessingError->label());
    }

    public function testRegisteredMailTypesMatchTheApiDefinition(): void
    {
        $json = file_get_contents(__DIR__ . '/../docs/api/epost-api-v2.6.1.swagger.json');
        self::assertIsString($json);
        $description = SpecReader::string($json, 'components', 'schemas', 'Letter', 'properties', 'registeredLetter', 'description');

        foreach (RegisteredMailType::cases() as $type) {
            self::assertStringContainsString("'" . $type->value . "'", $description, $type->name);
        }
        preg_match_all("/'([^']+)'/", $description, $matches);
        self::assertCount(count(RegisteredMailType::cases()), $matches[1]);
    }

    /**
     * The enum must match the snapshot of the upstream trackStatusCodes.json kept
     * in docs/api. Refresh the snapshot and the enum together when Deutsche
     * Post changes the list.
     */
    public function testTrackStatusCodesMatchUpstreamSnapshot(): void
    {
        $json = file_get_contents(__DIR__ . '/../docs/api/trackStatusCodes.json');
        self::assertIsString($json);
        $upstream = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($upstream);

        $expected = [];
        foreach ($upstream as $row) {
            self::assertIsArray($row);
            self::assertIsString($row['statusCode']);
            $expected[$row['statusCode']] = ['description' => $row['description'], 'final' => $row['final'] === 'true'];
        }

        $actual = [];
        foreach (TrackStatusCode::cases() as $code) {
            $actual[$code->value] = ['description' => $code->description(), 'final' => $code->isFinal()];
        }

        self::assertSame($expected, $actual);
    }
}

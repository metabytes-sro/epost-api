<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\TrackStatusCodes;
use PHPUnit\Framework\TestCase;

class TrackStatusCodesTest extends TestCase
{
    public function testGetSourceUrl(): void
    {
        self::assertSame(
            'https://api.epost.docuguide.com/trackStatusCodes.json',
            TrackStatusCodes::getSourceUrl(),
        );
    }

    public function testGetDescription(): void
    {
        self::assertSame('Die Sendung wurde zugestellt.', TrackStatusCodes::getDescription('DELIVERED'));
        self::assertSame('Die Sendung befindet sich in der Zustellung.', TrackStatusCodes::getDescription('IN_DELIVERY'));
        self::assertNull(TrackStatusCodes::getDescription('UNKNOWN_CODE'));
    }

    public function testIsFinal(): void
    {
        self::assertTrue(TrackStatusCodes::isFinal('DELIVERED'));
        self::assertFalse(TrackStatusCodes::isFinal('IN_DELIVERY'));
        self::assertNull(TrackStatusCodes::isFinal('UNKNOWN_CODE'));
    }

    public function testHasCode(): void
    {
        self::assertTrue(TrackStatusCodes::hasCode('DELIVERED'));
        self::assertFalse(TrackStatusCodes::hasCode('UNKNOWN_CODE'));
    }

    public function testGetAll(): void
    {
        $all = TrackStatusCodes::getAll();

        self::assertArrayHasKey('DELIVERED', $all);
        self::assertSame('Die Sendung wurde zugestellt.', $all['DELIVERED']['description']);
        self::assertTrue($all['DELIVERED']['final']);
    }

    /**
     * The table in TrackStatusCodes must match the snapshot of the upstream
     * trackStatusCodes.json kept in docs/api. Refresh the snapshot and this
     * class together when Deutsche Post changes the list.
     */
    public function testMatchesUpstreamSnapshot(): void
    {
        $json = file_get_contents(__DIR__ . '/../docs/api/trackStatusCodes.json');
        self::assertIsString($json);
        $upstream = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($upstream);

        $expected = [];
        foreach ($upstream as $row) {
            self::assertIsArray($row);
            self::assertIsString($row['statusCode']);
            $expected[$row['statusCode']] = [
                'description' => $row['description'],
                'final' => $row['final'] === 'true',
            ];
        }

        self::assertSame($expected, TrackStatusCodes::getAll());
    }
}

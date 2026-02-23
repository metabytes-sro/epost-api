<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\TrackStatusCodes;
use PHPUnit\Framework\TestCase;

class TrackStatusCodesTest extends TestCase
{
    public function testGetSourceUrl(): void
    {
        $this->assertSame(
            'https://api.epost.docuguide.com/trackStatusCodes.json',
            TrackStatusCodes::getSourceUrl()
        );
    }

    public function testGetDescription(): void
    {
        $this->assertSame(
            'Die Sendung wurde zugestellt.',
            TrackStatusCodes::getDescription('DELIVERED')
        );
        $this->assertSame(
            'Die Sendung befindet sich in der Zustellung.',
            TrackStatusCodes::getDescription('IN_DELIVERY')
        );
    }

    public function testGetDescriptionReturnsNullForUnknownCode(): void
    {
        $this->assertNull(TrackStatusCodes::getDescription('UNKNOWN_CODE'));
    }

    public function testIsFinal(): void
    {
        $this->assertTrue(TrackStatusCodes::isFinal('DELIVERED'));
        $this->assertFalse(TrackStatusCodes::isFinal('IN_DELIVERY'));
    }

    public function testIsFinalReturnsNullForUnknownCode(): void
    {
        $this->assertNull(TrackStatusCodes::isFinal('UNKNOWN_CODE'));
    }

    public function testHasCode(): void
    {
        $this->assertTrue(TrackStatusCodes::hasCode('DELIVERED'));
        $this->assertFalse(TrackStatusCodes::hasCode('UNKNOWN_CODE'));
    }

    public function testGetAll(): void
    {
        $all = TrackStatusCodes::getAll();
        $this->assertArrayHasKey('DELIVERED', $all);
        $this->assertSame('Die Sendung wurde zugestellt.', $all['DELIVERED']['description']);
        $this->assertTrue($all['DELIVERED']['final']);
    }
}

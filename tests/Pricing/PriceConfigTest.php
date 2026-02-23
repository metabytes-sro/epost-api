<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Pricing;

use MetabytesSRO\EPost\Api\Pricing\PriceConfig;
use PHPUnit\Framework\TestCase;

class PriceConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('EPOST_TARIFF');
        putenv('EPOST_PRICES_JSON');
        parent::tearDown();
    }

    public function testDefaultConstructor(): void
    {
        $config = new PriceConfig();
        $this->assertSame(PriceConfig::TARIFF_BASIS, $config->getTariff());
        $national = $config->getNationalPrices();
        $this->assertArrayHasKey(PriceConfig::TARIFF_BASIS, $national);
        $this->assertArrayHasKey(PriceConfig::TARIFF_250PLUS, $national);
        $this->assertEqualsWithDelta(0.80, $national['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testGetTariffDefaultsToBasis(): void
    {
        putenv('EPOST_TARIFF');
        $config = new PriceConfig();
        $this->assertSame(PriceConfig::TARIFF_BASIS, $config->getTariff());
    }

    public function testGetTariff250Plus(): void
    {
        putenv('EPOST_TARIFF=250plus');
        $config = new PriceConfig();
        $this->assertSame(PriceConfig::TARIFF_250PLUS, $config->getTariff());
    }

    public function testGetInternationalPostagePrice(): void
    {
        $config = new PriceConfig();
        $porto = $config->getInternationalPostagePrice();
        $this->assertEqualsWithDelta(1.25, $porto['standard'], 0.001);
        $this->assertEqualsWithDelta(1.80, $porto['kompakt'], 0.001);
        $this->assertEqualsWithDelta(3.30, $porto['gross'], 0.001);
    }

    public function testGetInternationalPrintPrice(): void
    {
        $config = new PriceConfig();
        $druck = $config->getInternationalPrintPrice();
        $this->assertEqualsWithDelta(0.27, $druck['basis']['standard']['sw_simplex'], 0.001);
        $this->assertEqualsWithDelta(0.20, $druck['250plus']['standard']['sw_simplex'], 0.001);
    }

    public function testFromEnvWithEmptyJson(): void
    {
        putenv('EPOST_PRICES_JSON=');
        $config = PriceConfig::fromEnv();
        $this->assertSame(PriceConfig::TARIFF_BASIS, $config->getTariff());
        $this->assertEqualsWithDelta(0.80, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testFromEnvWithInvalidJson(): void
    {
        putenv('EPOST_PRICES_JSON=invalid');
        $config = PriceConfig::fromEnv();
        $this->assertEqualsWithDelta(0.80, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testFromEnvWithValidOverride(): void
    {
        putenv('EPOST_PRICES_JSON=' . json_encode([
            'national' => [
                'basis' => [
                    'standard' => [
                        'sw_simplex' => 0.75,
                        'sw_duplex' => 0.76,
                        'color_simplex' => 0.78,
                        'color_duplex' => 0.85,
                    ],
                ],
            ],
        ]));
        $config = PriceConfig::fromEnv();
        $this->assertEqualsWithDelta(0.75, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testConstructorWithPartialOverride(): void
    {
        $config = new PriceConfig(
            national: [
                'basis' => [
                    'standard' => [
                        'sw_simplex' => 0.70,
                        'sw_duplex' => 0.71,
                        'color_simplex' => 0.72,
                        'color_duplex' => 0.79,
                    ],
                ],
            ],
        );
        $this->assertEqualsWithDelta(0.70, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
    }
}

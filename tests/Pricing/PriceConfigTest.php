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

        self::assertSame(PriceConfig::TARIFF_BASIS, $config->getTariff());
        $national = $config->getNationalPrices();
        self::assertArrayHasKey(PriceConfig::TARIFF_BASIS, $national);
        self::assertArrayHasKey(PriceConfig::TARIFF_250PLUS, $national);
        self::assertEqualsWithDelta(0.80, $national['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testGetTariffDefaultsToBasis(): void
    {
        putenv('EPOST_TARIFF');

        self::assertSame(PriceConfig::TARIFF_BASIS, (new PriceConfig())->getTariff());
    }

    public function testGetTariffIgnoresUnknownValue(): void
    {
        putenv('EPOST_TARIFF=gold');

        self::assertSame(PriceConfig::TARIFF_BASIS, (new PriceConfig())->getTariff());
    }

    public function testGetTariff250Plus(): void
    {
        putenv('EPOST_TARIFF=250plus');
        self::assertSame(PriceConfig::TARIFF_250PLUS, (new PriceConfig())->getTariff());
    }

    public function testGetTariff250PlusAlternateSpelling(): void
    {
        putenv('EPOST_TARIFF=250+');
        self::assertSame(PriceConfig::TARIFF_250PLUS, (new PriceConfig())->getTariff());
    }

    public function testGetInternationalPostagePrice(): void
    {
        $porto = (new PriceConfig())->getInternationalPostagePrice();

        self::assertEqualsWithDelta(1.25, $porto['standard'], 0.001);
        self::assertEqualsWithDelta(1.80, $porto['kompakt'], 0.001);
        self::assertEqualsWithDelta(3.30, $porto['gross'], 0.001);
    }

    public function testGetInternationalPrintPrice(): void
    {
        $druck = (new PriceConfig())->getInternationalPrintPrice();

        self::assertEqualsWithDelta(0.27, $druck['basis']['standard']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.20, $druck['250plus']['standard']['sw_simplex'], 0.001);
    }

    public function testFromEnvWithoutVariableUsesDefaults(): void
    {
        putenv('EPOST_PRICES_JSON');

        self::assertEqualsWithDelta(0.80, PriceConfig::fromEnv()->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testFromEnvWithEmptyJson(): void
    {
        putenv('EPOST_PRICES_JSON=');

        $config = PriceConfig::fromEnv();

        self::assertSame(PriceConfig::TARIFF_BASIS, $config->getTariff());
        self::assertEqualsWithDelta(0.80, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testFromEnvWithInvalidJson(): void
    {
        putenv('EPOST_PRICES_JSON=invalid');

        self::assertEqualsWithDelta(0.80, PriceConfig::fromEnv()->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
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
            'international_porto' => ['standard' => '1.30'],
            'international_druck' => ['250plus' => ['gross' => ['sw_simplex' => 0.60]]],
        ], JSON_THROW_ON_ERROR));

        $config = PriceConfig::fromEnv();

        $national = $config->getNationalPrices();
        self::assertEqualsWithDelta(0.75, $national['basis']['standard']['sw_simplex'], 0.001);
        // Prices that were not overridden keep their defaults.
        self::assertEqualsWithDelta(1.12, $national['basis']['kompakt']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.73, $national['250plus']['standard']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(1.30, $config->getInternationalPostagePrice()['standard'], 0.001);
        self::assertEqualsWithDelta(1.80, $config->getInternationalPostagePrice()['kompakt'], 0.001);
        self::assertEqualsWithDelta(0.60, $config->getInternationalPrintPrice()['250plus']['gross']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.75, $config->getInternationalPrintPrice()['250plus']['gross']['sw_duplex'], 0.001);
    }

    public function testFromEnvIgnoresValuesThatAreNotPrices(): void
    {
        putenv('EPOST_PRICES_JSON=' . json_encode([
            'national' => [
                'basis' => ['standard' => ['sw_simplex' => 'free', 'sw_duplex' => 0.70], 'kompakt' => 'nope'],
                '250plus' => 'nope',
            ],
            'international_porto' => 'nope',
            'international_druck' => 7,
        ], JSON_THROW_ON_ERROR));

        $config = PriceConfig::fromEnv();

        $national = $config->getNationalPrices();
        self::assertEqualsWithDelta(0.80, $national['basis']['standard']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.70, $national['basis']['standard']['sw_duplex'], 0.001);
        self::assertEqualsWithDelta(1.12, $national['basis']['kompakt']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.73, $national['250plus']['standard']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(1.25, $config->getInternationalPostagePrice()['standard'], 0.001);
        self::assertEqualsWithDelta(0.27, $config->getInternationalPrintPrice()['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testConstructorMergesPartialOverride(): void
    {
        $config = new PriceConfig(
            national: [
                'basis' => [
                    'standard' => ['sw_simplex' => 0.70],
                ],
            ],
            internationalPorto: ['gross' => 3.00],
        );

        self::assertEqualsWithDelta(0.70, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.81, $config->getNationalPrices()['basis']['standard']['sw_duplex'], 0.001);
        self::assertEqualsWithDelta(3.00, $config->getInternationalPostagePrice()['gross'], 0.001);
        self::assertEqualsWithDelta(1.25, $config->getInternationalPostagePrice()['standard'], 0.001);
    }
}

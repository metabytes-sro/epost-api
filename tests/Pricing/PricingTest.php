<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Pricing;

use MetabytesSRO\EPost\Api\Pricing\LetterFormat;
use MetabytesSRO\EPost\Api\Pricing\LetterPriceCalculator;
use MetabytesSRO\EPost\Api\Pricing\PriceConfig;
use MetabytesSRO\EPost\Api\Pricing\Tariff;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PricingTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('EPOST_TARIFF');
        putenv('EPOST_PRICES_JSON');
        parent::tearDown();
    }

    public function testTariffFromLabel(): void
    {
        self::assertSame(Tariff::Basis, Tariff::fromLabel('basis'));
        self::assertSame(Tariff::Basis, Tariff::fromLabel(' BASIS '));
        self::assertSame(Tariff::Plus250, Tariff::fromLabel('250plus'));
        self::assertSame(Tariff::Plus250, Tariff::fromLabel('250+'));
        self::assertNull(Tariff::fromLabel('gold'));
    }

    public function testLetterFormat(): void
    {
        self::assertSame(LetterFormat::Standard, LetterFormat::fromWeight(1));
        self::assertSame(LetterFormat::Standard, LetterFormat::fromWeight(20));
        self::assertSame(LetterFormat::Kompakt, LetterFormat::fromWeight(21));
        self::assertSame(LetterFormat::Kompakt, LetterFormat::fromWeight(50));
        self::assertSame(LetterFormat::Gross, LetterFormat::fromWeight(51));
        self::assertSame(LetterFormat::Gross, LetterFormat::fromWeight(500));
        self::assertSame([20, 50, 500], array_map(static fn(LetterFormat $f) => $f->maxWeightGrams(), LetterFormat::cases()));
        self::assertSame([1, 4, 10], array_map(static fn(LetterFormat $f) => $f->includedSheets(), LetterFormat::cases()));
    }

    public function testDefaultConfig(): void
    {
        $config = new PriceConfig();

        self::assertSame(Tariff::Basis, $config->tariff);
        self::assertEqualsWithDelta(0.80, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(1.25, $config->getInternationalPostagePrices()['standard'], 0.001);
        self::assertEqualsWithDelta(0.27, $config->getInternationalPrintPrices()['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testConfigMergesOverrides(): void
    {
        $config = new PriceConfig(
            Tariff::Plus250,
            national: ['basis' => ['standard' => ['sw_simplex' => 0.70]]],
            internationalPostage: ['gross' => 3.00],
            internationalPrint: ['250plus' => ['gross' => ['sw_simplex' => 0.60]]],
        );

        self::assertSame(Tariff::Plus250, $config->tariff);
        self::assertEqualsWithDelta(0.70, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.81, $config->getNationalPrices()['basis']['standard']['sw_duplex'], 0.001);
        self::assertEqualsWithDelta(3.00, $config->getInternationalPostagePrices()['gross'], 0.001);
        self::assertEqualsWithDelta(1.25, $config->getInternationalPostagePrices()['standard'], 0.001);
        self::assertEqualsWithDelta(0.60, $config->getInternationalPrintPrices()['250plus']['gross']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.75, $config->getInternationalPrintPrices()['250plus']['gross']['sw_duplex'], 0.001);
    }

    public function testFromEnvDefaults(): void
    {
        putenv('EPOST_TARIFF');
        putenv('EPOST_PRICES_JSON');

        $config = PriceConfig::fromEnv();

        self::assertSame(Tariff::Basis, $config->tariff);
        self::assertEqualsWithDelta(0.80, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testFromEnvTariff(): void
    {
        putenv('EPOST_TARIFF=250+');
        self::assertSame(Tariff::Plus250, PriceConfig::fromEnv()->tariff);

        putenv('EPOST_TARIFF=unknown');
        self::assertSame(Tariff::Basis, PriceConfig::fromEnv()->tariff);
    }

    public function testFromEnvIgnoresInvalidJson(): void
    {
        putenv('EPOST_PRICES_JSON=invalid');
        self::assertEqualsWithDelta(0.80, PriceConfig::fromEnv()->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);

        putenv('EPOST_PRICES_JSON=');
        self::assertEqualsWithDelta(0.80, PriceConfig::fromEnv()->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
    }

    public function testFromEnvWithOverrides(): void
    {
        putenv('EPOST_PRICES_JSON=' . json_encode([
            'national' => ['basis' => ['standard' => ['sw_simplex' => 0.75, 'sw_duplex' => 'free'], 'kompakt' => 'nope'], '250plus' => 7],
            'international_porto' => ['standard' => '1.30'],
            'international_druck' => 'nope',
        ], JSON_THROW_ON_ERROR));

        $config = PriceConfig::fromEnv();

        self::assertEqualsWithDelta(0.75, $config->getNationalPrices()['basis']['standard']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.81, $config->getNationalPrices()['basis']['standard']['sw_duplex'], 0.001);
        self::assertEqualsWithDelta(1.12, $config->getNationalPrices()['basis']['kompakt']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(0.73, $config->getNationalPrices()['250plus']['standard']['sw_simplex'], 0.001);
        self::assertEqualsWithDelta(1.30, $config->getInternationalPostagePrices()['standard'], 0.001);
        self::assertEqualsWithDelta(0.27, $config->getInternationalPrintPrices()['basis']['standard']['sw_simplex'], 0.001);
    }

    /**
     * @return iterable<string, array{Tariff, int, int, bool, bool, bool, float}>
     */
    public static function prices(): iterable
    {
        yield 'national standard sw simplex' => [Tariff::Basis, 20, 1, false, false, false, 0.80];
        yield 'national standard sw duplex' => [Tariff::Basis, 20, 1, false, true, false, 0.81];
        yield 'national standard color simplex' => [Tariff::Basis, 20, 1, true, false, false, 0.83];
        yield 'national kompakt color duplex' => [Tariff::Basis, 50, 4, true, true, false, 1.52];
        yield 'national gross sw duplex' => [Tariff::Basis, 500, 10, false, true, false, 2.05];
        yield 'national extra sheets' => [Tariff::Basis, 20, 3, false, false, false, 0.88];
        yield 'national within included sheets' => [Tariff::Basis, 50, 2, false, false, false, 1.12];
        yield 'national 250plus' => [Tariff::Plus250, 20, 1, false, false, false, 0.73];
        yield 'international standard' => [Tariff::Basis, 20, 1, false, false, true, 1.52];
        yield 'international kompakt' => [Tariff::Basis, 50, 4, false, false, true, 2.22];
        yield 'international gross' => [Tariff::Basis, 500, 10, false, false, true, 4.02];
        yield 'international color duplex' => [Tariff::Basis, 20, 1, true, true, true, 1.62];
        yield 'international extra sheets' => [Tariff::Basis, 50, 6, false, false, true, 2.30];
        yield 'international 250plus' => [Tariff::Plus250, 20, 1, false, false, true, 1.45];
    }

    #[DataProvider('prices')]
    public function testCalculate(Tariff $tariff, int $weight, int $pages, bool $color, bool $duplex, bool $international, float $expected): void
    {
        $calculator = new LetterPriceCalculator(new PriceConfig($tariff));

        self::assertSame($expected, $calculator->calculate($weight, $pages, $color, $duplex, $international));
    }

    public function testCalculateWithMissingPricesFallsBackToZero(): void
    {
        $calculator = new LetterPriceCalculator(new PriceConfig(internationalPostage: ['standard' => 0.0]));

        self::assertSame(0.27, $calculator->calculate(20, 1, false, false, true));
    }

    public function testCalculateBatch(): void
    {
        $calculator = new LetterPriceCalculator();

        self::assertSame(8.0, $calculator->calculateBatch(10, 20, 1));
        self::assertSame(2.4, $calculator->calculateBatch(3, 20, 1));
        self::assertSame(7.6, $calculator->calculateBatch(5, 20, 1, false, false, true));
    }

    public function testFromEnvAndConfigAccessor(): void
    {
        putenv('EPOST_TARIFF=250plus');

        $calculator = LetterPriceCalculator::fromEnv();

        self::assertSame(Tariff::Plus250, $calculator->getConfig()->tariff);
        self::assertSame(0.73, $calculator->calculate(20, 1));
    }
}

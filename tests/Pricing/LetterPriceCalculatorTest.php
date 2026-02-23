<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\Pricing;

use MetabytesSRO\EPost\Api\Pricing\LetterFormat;
use MetabytesSRO\EPost\Api\Pricing\LetterPriceCalculator;
use MetabytesSRO\EPost\Api\Pricing\PriceConfig;
use PHPUnit\Framework\TestCase;

class LetterPriceCalculatorTest extends TestCase
{
    public function testNationalStandardSwSimplex(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $price = $calc->calculate(20, 1, false, false, false);
        $this->assertEqualsWithDelta(0.80, $price, 0.001);
    }

    public function testNationalKompaktColorDuplex(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $price = $calc->calculate(50, 4, true, true, false);
        $this->assertEqualsWithDelta(1.52, $price, 0.001);
    }

    public function testNationalGrossFormat(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $price = $calc->calculate(500, 10, false, true, false);
        $this->assertEqualsWithDelta(2.05, $price, 0.001);
    }

    public function testNationalSwDuplexColorSimplex(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $this->assertEqualsWithDelta(0.81, $calc->calculate(20, 1, false, true, false), 0.001);
        $this->assertEqualsWithDelta(0.83, $calc->calculate(20, 1, true, false, false), 0.001);
    }

    public function testNationalWithExtraSheets(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $price = $calc->calculate(20, 3, false, false, false);
        $this->assertEqualsWithDelta(0.80 + (2 * 0.04), $price, 0.001);
    }

    public function testNationalPageCountWithinIncludedNoExtraCharge(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $price = $calc->calculate(50, 2, false, false, false);
        $this->assertEqualsWithDelta(1.12, $price, 0.001);
    }

    public function testInternationalStandard(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $price = $calc->calculate(20, 1, false, false, true);
        $this->assertEqualsWithDelta(1.25 + 0.27, $price, 0.001);
    }

    public function testInternationalKompaktGross(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $this->assertEqualsWithDelta(1.80 + 0.42, $calc->calculate(50, 4, false, false, true), 0.001);
        $this->assertEqualsWithDelta(3.30 + 0.72, $calc->calculate(500, 10, false, false, true), 0.001);
    }

    public function testInternationalColorDuplex(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $price = $calc->calculate(20, 1, true, true, true);
        $this->assertEqualsWithDelta(1.25 + 0.37, $price, 0.001);
    }

    public function testInternationalWithExtraSheets(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $price = $calc->calculate(50, 6, false, false, true);
        $this->assertEqualsWithDelta(1.80 + 0.42 + (2 * 0.04), $price, 0.001);
    }

    public function testInternationalTariff250Plus(): void
    {
        putenv('EPOST_TARIFF=250plus');
        try {
            $calc = new LetterPriceCalculator(new PriceConfig());
            $price = $calc->calculate(20, 1, false, false, true);
            $this->assertEqualsWithDelta(1.25 + 0.20, $price, 0.001);
        } finally {
            putenv('EPOST_TARIFF');
        }
    }

    public function testTariff250Plus(): void
    {
        putenv('EPOST_TARIFF=250plus');
        try {
            $calc = new LetterPriceCalculator(new PriceConfig());
            $price = $calc->calculate(20, 1, false, false, false);
            $this->assertEqualsWithDelta(0.73, $price, 0.001);
        } finally {
            putenv('EPOST_TARIFF');
        }
    }

    public function testTariff250PlusAlternateFormat(): void
    {
        putenv('EPOST_TARIFF=250+');
        try {
            $calc = new LetterPriceCalculator(new PriceConfig());
            $price = $calc->calculate(20, 1, false, false, false);
            $this->assertEqualsWithDelta(0.73, $price, 0.001);
        } finally {
            putenv('EPOST_TARIFF');
        }
    }

    public function testFromEnv(): void
    {
        $calc = LetterPriceCalculator::fromEnv();
        $this->assertEqualsWithDelta(0.80, $calc->calculate(20, 1, false, false, false), 0.001);
    }

    public function testLetterFormatFromWeight(): void
    {
        $this->assertSame(LetterFormat::Standard, LetterFormat::fromWeight(20));
        $this->assertSame(LetterFormat::Standard, LetterFormat::fromWeight(1));
        $this->assertSame(LetterFormat::Kompakt, LetterFormat::fromWeight(21));
        $this->assertSame(LetterFormat::Kompakt, LetterFormat::fromWeight(50));
        $this->assertSame(LetterFormat::Gross, LetterFormat::fromWeight(51));
        $this->assertSame(LetterFormat::Gross, LetterFormat::fromWeight(500));
    }

    public function testLetterFormatGetters(): void
    {
        $this->assertSame(20, LetterFormat::Standard->getMaxWeightGrams());
        $this->assertSame(1, LetterFormat::Standard->getIncludedSheets());
        $this->assertSame(50, LetterFormat::Kompakt->getMaxWeightGrams());
        $this->assertSame(4, LetterFormat::Kompakt->getIncludedSheets());
        $this->assertSame(500, LetterFormat::Gross->getMaxWeightGrams());
        $this->assertSame(10, LetterFormat::Gross->getIncludedSheets());
    }

    public function testLetterFormatFromWeightAndPagesDeprecated(): void
    {
        $this->assertSame(LetterFormat::Standard, LetterFormat::fromWeightAndPages(20, 5));
    }

    public function testCalculateBatch(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $total = $calc->calculateBatch(10, 20, 1, false, false, false);
        $this->assertEqualsWithDelta(8.0, $total, 0.001);
    }

    public function testCalculateBatchInternational(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $total = $calc->calculateBatch(5, 20, 1, false, false, true);
        $this->assertEqualsWithDelta(5 * (1.25 + 0.27), $total, 0.001);
    }

    public function testCalculateBatchRounding(): void
    {
        $calc = new LetterPriceCalculator(new PriceConfig());
        $total = $calc->calculateBatch(3, 20, 1, false, false, false);
        $this->assertSame(2.4, $total);
    }

    public function testCalculateWithCustomPriceOverride(): void
    {
        $config = new PriceConfig(
            national: [
                PriceConfig::TARIFF_BASIS => [
                    'standard' => ['sw_simplex' => 0.70, 'sw_duplex' => 0.71, 'color_simplex' => 0.72, 'color_duplex' => 0.79],
                    'kompakt' => ['sw_simplex' => 1.00, 'sw_duplex' => 1.04, 'color_simplex' => 1.12, 'color_duplex' => 1.40],
                    'gross' => ['sw_simplex' => 1.80, 'sw_duplex' => 1.90, 'color_simplex' => 2.10, 'color_duplex' => 2.80],
                    'per_sheet' => ['sw_simplex' => 0.04, 'sw_duplex' => 0.05, 'color_simplex' => 0.07, 'color_duplex' => 0.14],
                ],
                PriceConfig::TARIFF_250PLUS => [
                    'standard' => ['sw_simplex' => 0.73, 'sw_duplex' => 0.74, 'color_simplex' => 0.76, 'color_duplex' => 0.83],
                    'kompakt' => ['sw_simplex' => 1.05, 'sw_duplex' => 1.09, 'color_simplex' => 1.17, 'color_duplex' => 1.45],
                    'gross' => ['sw_simplex' => 1.88, 'sw_duplex' => 1.98, 'color_simplex' => 2.18, 'color_duplex' => 2.88],
                    'per_sheet' => ['sw_simplex' => 0.04, 'sw_duplex' => 0.05, 'color_simplex' => 0.07, 'color_duplex' => 0.14],
                ],
            ],
        );
        $calc = new LetterPriceCalculator($config);
        $this->assertEqualsWithDelta(0.70, $calc->calculate(20, 1, false, false, false), 0.001);
    }
}

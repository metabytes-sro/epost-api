<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Pricing;

/**
 * Estimates the price of a letter from the E-POST MAILER price lists.
 *
 * The E-POSTBUSINESS API has no pricing endpoint for letters (its cost estimate
 * only covers Dialogpost campaigns), so this calculator works locally from the
 * published prices in PriceConfig. Prices are in EUR net.
 */
final readonly class LetterPriceCalculator
{
    public function __construct(
        private PriceConfig $config = new PriceConfig(),
    ) {}

    /**
     * Calculator configured from the EPOST_TARIFF and EPOST_PRICES_JSON environment variables.
     */
    public static function fromEnv(): self
    {
        return new self(PriceConfig::fromEnv());
    }

    public function getConfig(): PriceConfig
    {
        return $this->config;
    }

    /**
     * Price of one letter.
     *
     * @param int $weightGrams Weight in grams including the envelope; decides the format
     * @param int $pages Number of printed sheets; sheets beyond the format's included ones cost extra
     * @param bool $color Colour instead of black and white
     * @param bool $duplex Both sides of each sheet
     * @param bool $international Destination outside Germany
     */
    public function calculate(
        int $weightGrams,
        int $pages,
        bool $color = false,
        bool $duplex = false,
        bool $international = false,
    ): float {
        $format = LetterFormat::fromWeight($weightGrams);
        $tariff = $this->config->tariff->value;
        $option = ($color ? 'color' : 'sw') . '_' . ($duplex ? 'duplex' : 'simplex');
        $extraSheets = max(0, $pages - $format->includedSheets());

        if ($international) {
            $postage = $this->config->getInternationalPostagePrices()[$format->value] ?? 0.0;
            $print = $this->config->getInternationalPrintPrices()[$tariff][$format->value][$option] ?? 0.0;
            $perSheet = $this->config->getInternationalPrintPrices()[$tariff]['per_sheet'][$option] ?? 0.0;

            return round($postage + $print + $extraSheets * $perSheet, 2);
        }

        $base = $this->config->getNationalPrices()[$tariff][$format->value][$option] ?? 0.0;
        $perSheet = $this->config->getNationalPrices()[$tariff]['per_sheet'][$option] ?? 0.0;

        return round($base + $extraSheets * $perSheet, 2);
    }

    /**
     * Total price of several identical letters.
     */
    public function calculateBatch(
        int $quantity,
        int $weightGrams,
        int $pages,
        bool $color = false,
        bool $duplex = false,
        bool $international = false,
    ): float {
        return round($this->calculate($weightGrams, $pages, $color, $duplex, $international) * $quantity, 2);
    }
}

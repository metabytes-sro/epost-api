<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Pricing;

/**
 * Calculates letter shipping costs based on E-POST MAILER price lists.
 *
 * Note: The E-POSTBUSINESS API does not provide a pricing endpoint for Letter (hybrid mail).
 * CostEstimate exists only for Campaign/Dialogpost. This calculator uses the official
 * price lists for local estimation.
 *
 * @see https://www.deutschepost.de/dam/jcr:4f6b160f-5beb-470a-9891-81e02acdd6e6/dp-epost-preisliste-mailer-basis_250+-ab%2001012025.pdf
 * @see https://www.deutschepost.de/dam/jcr:d7e72ba2-a855-4b1d-9300-3c5c6745bf86/dp-epost-preisliste-international-mailer-basis-ab-01012025_vf.pdf
 */
class LetterPriceCalculator
{
    public function __construct(
        private readonly PriceConfig $config,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(PriceConfig::fromEnv());
    }

    /**
     * Calculate the price for a single letter.
     *
     * @param int  $weightInGrams   Letter weight in grams (incl. envelope)
     * @param int  $pageCount     Number of pages
     * @param bool $isColor       Color (true) or B/W (false)
     * @param bool $isDuplex      Duplex (true) or simplex (false)
     * @param bool $isInternational International shipment
     */
    public function calculate(
        int  $weightInGrams,
        int  $pageCount,
        bool $isColor = false,
        bool $isDuplex = false,
        bool $isInternational = false,
    ): float {
        $format = LetterFormat::fromWeight($weightInGrams);
        $formatKey = $format->value;
        $tariff = $this->config->getTariff();
        $priceKey = ($isColor ? 'color' : 'sw') . '_' . ($isDuplex ? 'duplex' : 'simplex');

        if ($isInternational) {
            $postagePrice = $this->config->getInternationalPostagePrice()[$formatKey] ?? 0.0;
            $printPrice = $this->config->getInternationalPrintPrice()[$tariff][$formatKey][$priceKey] ?? 0.0;
            $includedSheetQty = $format->getIncludedSheets();
            $extraSheets = max(0, $pageCount - $includedSheetQty);
            $perSheetPrice = $this->config->getInternationalPrintPrice()[$tariff]['per_sheet'][$priceKey] ?? 0.0;
            return $postagePrice + $printPrice + ($extraSheets * $perSheetPrice);
        }

        $basePrice = $this->config->getNationalPrices()[$tariff][$formatKey][$priceKey] ?? 0.0;
        $includedSheetQty = $format->getIncludedSheets();
        $extraSheets = max(0, $pageCount - $includedSheetQty);
        $perSheetPrice = $this->config->getNationalPrices()[$tariff]['per_sheet'][$priceKey] ?? 0.0;
        return $basePrice + ($extraSheets * $perSheetPrice);
    }

    /**
     * Calculate the total price for multiple identical letters.
     */
    public function calculateBatch(
        int $quantity,
        int $weightGrams,
        int $pageCount,
        bool $isColor = false,
        bool $isDuplex = false,
        bool $isInternational = false,
    ): float {
        $unitPrice = $this->calculate($weightGrams, $pageCount, $isColor, $isDuplex, $isInternational);
        return round($unitPrice * $quantity, 2);
    }
}

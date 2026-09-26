<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Pricing;

/**
 * Tariff and price tables for LetterPriceCalculator.
 *
 * The built-in prices are the Deutsche Post E-POST MAILER price lists valid
 * from 01.01.2025. Overrides are merged into them, so only negotiated prices
 * need to be given.
 *
 * fromEnv() reads EPOST_TARIFF ("basis" or "250plus") and EPOST_PRICES_JSON, a
 * JSON object with the same structure as the constructor arguments:
 *
 * {
 *   "national": { "basis": {...}, "250plus": {...} },
 *   "international_porto": { "standard": 1.25, "kompakt": 1.80, "gross": 3.30 },
 *   "international_druck": { "basis": {...}, "250plus": {...} }
 * }
 *
 * Each tariff has "standard", "kompakt", "gross" and "per_sheet", each with
 * "sw_simplex", "sw_duplex", "color_simplex" and "color_duplex" prices in EUR.
 *
 * @see https://www.deutschepost.de/dam/jcr:4f6b160f-5beb-470a-9891-81e02acdd6e6/dp-epost-preisliste-mailer-basis_250+-ab%2001012025.pdf
 * @see https://www.deutschepost.de/dam/jcr:d7e72ba2-a855-4b1d-9300-3c5c6745bf86/dp-epost-preisliste-international-mailer-basis-ab-01012025_vf.pdf
 */
final readonly class PriceConfig
{
    public const string PRICE_LIST_VALID_FROM = '2025-01-01';

    /** @var array<string, array<string, array<string, float>>> */
    private array $national;

    /** @var array<string, float> */
    private array $internationalPostage;

    /** @var array<string, array<string, array<string, float>>> */
    private array $internationalPrint;

    /**
     * @param array<string, array<string, array<string, float>>>|null $national Overrides per tariff, format and print option
     * @param array<string, float>|null $internationalPostage Overrides per format
     * @param array<string, array<string, array<string, float>>>|null $internationalPrint Overrides per tariff, format and print option
     */
    public function __construct(
        public Tariff $tariff = Tariff::Basis,
        ?array $national = null,
        ?array $internationalPostage = null,
        ?array $internationalPrint = null,
    ) {
        $this->national = array_replace_recursive(self::defaultNationalPrices(), $national ?? []);
        $this->internationalPostage = array_replace(self::defaultInternationalPostage(), $internationalPostage ?? []);
        $this->internationalPrint = array_replace_recursive(self::defaultInternationalPrint(), $internationalPrint ?? []);
    }

    /**
     * Build the configuration from the EPOST_TARIFF and EPOST_PRICES_JSON
     * environment variables. Unknown tariffs fall back to Basis; invalid JSON
     * and values that are not numbers are ignored.
     */
    public static function fromEnv(): self
    {
        $tariffValue = getenv('EPOST_TARIFF');
        $tariff = ($tariffValue === false ? null : Tariff::fromLabel($tariffValue)) ?? Tariff::Basis;

        $json = getenv('EPOST_PRICES_JSON');
        $decoded = ($json === false || $json === '') ? null : json_decode($json, true);
        if (!is_array($decoded)) {
            return new self($tariff);
        }

        return new self(
            $tariff,
            self::priceTable($decoded['national'] ?? null),
            self::priceMap($decoded['international_porto'] ?? null),
            self::priceTable($decoded['international_druck'] ?? null),
        );
    }

    /**
     * @return array<string, array<string, array<string, float>>>
     */
    public function getNationalPrices(): array
    {
        return $this->national;
    }

    /**
     * @return array<string, float>
     */
    public function getInternationalPostagePrices(): array
    {
        return $this->internationalPostage;
    }

    /**
     * @return array<string, array<string, array<string, float>>>
     */
    public function getInternationalPrintPrices(): array
    {
        return $this->internationalPrint;
    }

    /**
     * @return array<string, float>|null
     */
    private static function priceMap(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }
        $map = [];
        foreach ($value as $key => $price) {
            if (is_int($price) || is_float($price) || (is_string($price) && is_numeric($price))) {
                $map[(string) $key] = (float) $price;
            }
        }

        return $map;
    }

    /**
     * @return array<string, array<string, array<string, float>>>|null
     */
    private static function priceTable(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }
        $table = [];
        foreach ($value as $tariff => $formats) {
            if (!is_array($formats)) {
                continue;
            }
            foreach ($formats as $format => $prices) {
                $map = self::priceMap($prices);
                if ($map !== null) {
                    $table[(string) $tariff][(string) $format] = $map;
                }
            }
        }

        return $table;
    }

    /**
     * @return array<string, array<string, array<string, float>>>
     */
    private static function defaultNationalPrices(): array
    {
        return [
            Tariff::Basis->value => [
                'standard' => ['sw_simplex' => 0.80, 'sw_duplex' => 0.81, 'color_simplex' => 0.83, 'color_duplex' => 0.90],
                'kompakt' => ['sw_simplex' => 1.12, 'sw_duplex' => 1.16, 'color_simplex' => 1.24, 'color_duplex' => 1.52],
                'gross' => ['sw_simplex' => 1.95, 'sw_duplex' => 2.05, 'color_simplex' => 2.25, 'color_duplex' => 2.95],
                'per_sheet' => ['sw_simplex' => 0.04, 'sw_duplex' => 0.05, 'color_simplex' => 0.07, 'color_duplex' => 0.14],
            ],
            Tariff::Plus250->value => [
                'standard' => ['sw_simplex' => 0.73, 'sw_duplex' => 0.74, 'color_simplex' => 0.76, 'color_duplex' => 0.83],
                'kompakt' => ['sw_simplex' => 1.05, 'sw_duplex' => 1.09, 'color_simplex' => 1.17, 'color_duplex' => 1.45],
                'gross' => ['sw_simplex' => 1.88, 'sw_duplex' => 1.98, 'color_simplex' => 2.18, 'color_duplex' => 2.88],
                'per_sheet' => ['sw_simplex' => 0.04, 'sw_duplex' => 0.05, 'color_simplex' => 0.07, 'color_duplex' => 0.14],
            ],
        ];
    }

    /**
     * @return array<string, float>
     */
    private static function defaultInternationalPostage(): array
    {
        return [
            'standard' => 1.25,
            'kompakt' => 1.80,
            'gross' => 3.30,
        ];
    }

    /**
     * @return array<string, array<string, array<string, float>>>
     */
    private static function defaultInternationalPrint(): array
    {
        return [
            Tariff::Basis->value => [
                'standard' => ['sw_simplex' => 0.27, 'sw_duplex' => 0.28, 'color_simplex' => 0.30, 'color_duplex' => 0.37],
                'kompakt' => ['sw_simplex' => 0.42, 'sw_duplex' => 0.46, 'color_simplex' => 0.54, 'color_duplex' => 0.82],
                'gross' => ['sw_simplex' => 0.72, 'sw_duplex' => 0.82, 'color_simplex' => 1.02, 'color_duplex' => 1.72],
                'per_sheet' => ['sw_simplex' => 0.04, 'sw_duplex' => 0.05, 'color_simplex' => 0.07, 'color_duplex' => 0.14],
            ],
            Tariff::Plus250->value => [
                'standard' => ['sw_simplex' => 0.20, 'sw_duplex' => 0.21, 'color_simplex' => 0.23, 'color_duplex' => 0.30],
                'kompakt' => ['sw_simplex' => 0.35, 'sw_duplex' => 0.39, 'color_simplex' => 0.47, 'color_duplex' => 0.75],
                'gross' => ['sw_simplex' => 0.65, 'sw_duplex' => 0.75, 'color_simplex' => 0.95, 'color_duplex' => 1.65],
                'per_sheet' => ['sw_simplex' => 0.04, 'sw_duplex' => 0.05, 'color_simplex' => 0.07, 'color_duplex' => 0.14],
            ],
        ];
    }
}

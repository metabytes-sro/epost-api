<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Pricing;

/**
 * Price configuration with optional environment variable overrides.
 *
 * Set EPOST_TARIFF to "basis" or "250plus" (default: basis).
 * Set EPOST_PRICES_JSON to a JSON object to override default prices. Overrides
 * are merged into the defaults, so a partial object (for example only the
 * negotiated national "basis" prices) keeps every other price intact.
 *
 * JSON structure:
 * {
 *   "national": { "basis": {...}, "250plus": {...} },
 *   "international_porto": { "standard": 1.25, "kompakt": 1.80, "gross": 3.30 },
 *   "international_druck": { "basis": {...}, "250plus": {...} }
 * }
 *
 * Each tariff has "standard", "kompakt", "gross" and "per_sheet", each with
 * "sw_simplex", "sw_duplex", "color_simplex" and "color_duplex" prices in EUR.
 *
 * Prices are valid from 01.01.2025.
 *
 * @see https://www.deutschepost.de/dam/jcr:4f6b160f-5beb-470a-9891-81e02acdd6e6/dp-epost-preisliste-mailer-basis_250+-ab%2001012025.pdf
 * @see https://www.deutschepost.de/dam/jcr:d7e72ba2-a855-4b1d-9300-3c5c6745bf86/dp-epost-preisliste-international-mailer-basis-ab-01012025_vf.pdf
 */
class PriceConfig
{
    public const TARIFF_BASIS = 'basis';
    public const TARIFF_250PLUS = '250plus';

    /** @var array<string, array<string, array<string, float>>> */
    private readonly array $national;

    /** @var array<string, float> */
    private readonly array $internationalPorto;

    /** @var array<string, array<string, array<string, float>>> */
    private readonly array $internationalDruck;

    /**
     * Each argument is merged into the built-in defaults, so only the prices that
     * differ need to be given.
     *
     * @param array<string, array<string, array<string, float>>>|null $national
     * @param array<string, float>|null $internationalPorto
     * @param array<string, array<string, array<string, float>>>|null $internationalDruck
     */
    public function __construct(
        ?array $national = null,
        ?array $internationalPorto = null,
        ?array $internationalDruck = null,
    ) {
        $this->national = array_replace_recursive(self::getDefaultNationalPrices(), $national ?? []);
        $this->internationalPorto = array_replace(self::getDefaultInternationalPorto(), $internationalPorto ?? []);
        $this->internationalDruck = array_replace_recursive(self::getDefaultInternationalDruck(), $internationalDruck ?? []);
    }

    /**
     * Build the configuration from the EPOST_PRICES_JSON environment variable.
     * Invalid JSON or values that are not numbers are ignored.
     */
    public static function fromEnv(): self
    {
        $json = getenv('EPOST_PRICES_JSON');
        if ($json !== false && $json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return new self(
                    self::priceTable($decoded['national'] ?? null),
                    self::priceMap($decoded['international_porto'] ?? null),
                    self::priceTable($decoded['international_druck'] ?? null),
                );
            }
        }

        return new self();
    }

    /**
     * Tariff from the EPOST_TARIFF environment variable: "250plus" (or "250+") or "basis".
     *
     * @phpstan-impure
     */
    public function getTariff(): string
    {
        $tariff = getenv('EPOST_TARIFF');

        return ($tariff === '250plus' || $tariff === '250+') ? self::TARIFF_250PLUS : self::TARIFF_BASIS;
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
    public function getInternationalPostagePrice(): array
    {
        return $this->internationalPorto;
    }

    /**
     * @return array<string, array<string, array<string, float>>>
     */
    public function getInternationalPrintPrice(): array
    {
        return $this->internationalDruck;
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
    private static function getDefaultNationalPrices(): array
    {
        return [
            self::TARIFF_BASIS => [
                'standard' => ['sw_simplex' => 0.80, 'sw_duplex' => 0.81, 'color_simplex' => 0.83, 'color_duplex' => 0.90],
                'kompakt' => ['sw_simplex' => 1.12, 'sw_duplex' => 1.16, 'color_simplex' => 1.24, 'color_duplex' => 1.52],
                'gross' => ['sw_simplex' => 1.95, 'sw_duplex' => 2.05, 'color_simplex' => 2.25, 'color_duplex' => 2.95],
                'per_sheet' => ['sw_simplex' => 0.04, 'sw_duplex' => 0.05, 'color_simplex' => 0.07, 'color_duplex' => 0.14],
            ],
            self::TARIFF_250PLUS => [
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
    private static function getDefaultInternationalPorto(): array
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
    private static function getDefaultInternationalDruck(): array
    {
        return [
            self::TARIFF_BASIS => [
                'standard' => ['sw_simplex' => 0.27, 'sw_duplex' => 0.28, 'color_simplex' => 0.30, 'color_duplex' => 0.37],
                'kompakt' => ['sw_simplex' => 0.42, 'sw_duplex' => 0.46, 'color_simplex' => 0.54, 'color_duplex' => 0.82],
                'gross' => ['sw_simplex' => 0.72, 'sw_duplex' => 0.82, 'color_simplex' => 1.02, 'color_duplex' => 1.72],
                'per_sheet' => ['sw_simplex' => 0.04, 'sw_duplex' => 0.05, 'color_simplex' => 0.07, 'color_duplex' => 0.14],
            ],
            self::TARIFF_250PLUS => [
                'standard' => ['sw_simplex' => 0.20, 'sw_duplex' => 0.21, 'color_simplex' => 0.23, 'color_duplex' => 0.30],
                'kompakt' => ['sw_simplex' => 0.35, 'sw_duplex' => 0.39, 'color_simplex' => 0.47, 'color_duplex' => 0.75],
                'gross' => ['sw_simplex' => 0.65, 'sw_duplex' => 0.75, 'color_simplex' => 0.95, 'color_duplex' => 1.65],
                'per_sheet' => ['sw_simplex' => 0.04, 'sw_duplex' => 0.05, 'color_simplex' => 0.07, 'color_duplex' => 0.14],
            ],
        ];
    }
}

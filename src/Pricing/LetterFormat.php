<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Pricing;

/**
 * Letter format based on weight and included sheets.
 *
 * @see https://www.deutschepost.de/dam/jcr:4f6b160f-5beb-470a-9891-81e02acdd6e6/dp-epost-preisliste-mailer-basis_250+-ab%2001012025.pdf
 * @see https://www.deutschepost.de/dam/jcr:d7e72ba2-a855-4b1d-9300-3c5c6745bf86/dp-epost-preisliste-international-mailer-basis-ab-01012025_vf.pdf
 */
enum LetterFormat: string
{
    case Standard = 'standard';   // bis 20 g, inkl. 1 Blatt
    case Kompakt = 'kompakt';    // bis 50 g, inkl. 4 Blatt
    case Gross = 'gross';        // bis 500 g, inkl. 10 Blatt

    public function getMaxWeightGrams(): int
    {
        return match ($this) {
            self::Standard => 20,
            self::Kompakt => 50,
            self::Gross => 500,
        };
    }

    public function getIncludedSheets(): int
    {
        return match ($this) {
            self::Standard => 1,
            self::Kompakt => 4,
            self::Gross => 10,
        };
    }

    /**
     * Determine the format from weight (format is weight-based; page count affects extra-sheet charges).
     */
    public static function fromWeight(int $weightGrams): self
    {
        if ($weightGrams <= 20) {
            return self::Standard;
        }
        if ($weightGrams <= 50) {
            return self::Kompakt;
        }
        return self::Gross;
    }

    /** @deprecated Use fromWeight() - format is weight-based */
    public static function fromWeightAndPages(int $weightGrams, int $pageCount): self
    {
        return self::fromWeight($weightGrams);
    }
}

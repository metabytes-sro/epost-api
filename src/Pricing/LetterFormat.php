<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Pricing;

/**
 * Letter format by weight, with the number of sheets included in the base price.
 *
 * @see https://www.deutschepost.de/dam/jcr:4f6b160f-5beb-470a-9891-81e02acdd6e6/dp-epost-preisliste-mailer-basis_250+-ab%2001012025.pdf
 * @see https://www.deutschepost.de/dam/jcr:d7e72ba2-a855-4b1d-9300-3c5c6745bf86/dp-epost-preisliste-international-mailer-basis-ab-01012025_vf.pdf
 */
enum LetterFormat: string
{
    /** Up to 20 g, 1 sheet included. */
    case Standard = 'standard';

    /** Up to 50 g, 4 sheets included. */
    case Kompakt = 'kompakt';

    /** Up to 500 g, 10 sheets included. */
    case Gross = 'gross';

    public function maxWeightGrams(): int
    {
        return match ($this) {
            self::Standard => 20,
            self::Kompakt => 50,
            self::Gross => 500,
        };
    }

    public function includedSheets(): int
    {
        return match ($this) {
            self::Standard => 1,
            self::Kompakt => 4,
            self::Gross => 10,
        };
    }

    /**
     * Format for a letter of the given weight in grams, including the envelope.
     */
    public static function fromWeight(int $weightGrams): self
    {
        if ($weightGrams <= self::Standard->maxWeightGrams()) {
            return self::Standard;
        }
        if ($weightGrams <= self::Kompakt->maxWeightGrams()) {
            return self::Kompakt;
        }

        return self::Gross;
    }
}

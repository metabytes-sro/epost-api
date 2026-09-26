<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Pricing;

/**
 * Deutsche Post E-POST MAILER tariffs.
 */
enum Tariff: string
{
    /** Tarif Basis: no minimum volume. */
    case Basis = 'basis';

    /** Tarif 250+: reduced prices from 250 letters per month. */
    case Plus250 = '250plus';

    /**
     * Lenient lookup accepting "basis", "250plus" and "250+" in any case; null for anything else.
     */
    public static function fromLabel(string $label): ?self
    {
        return match (strtolower(trim($label))) {
            'basis' => self::Basis,
            '250plus', '250+' => self::Plus250,
            default => null,
        };
    }
}

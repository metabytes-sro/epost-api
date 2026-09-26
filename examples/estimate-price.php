<?php

/**
 * Local price estimates from the Deutsche Post price lists. Needs no
 * credentials; reads EPOST_TARIFF and EPOST_PRICES_JSON when set.
 *
 * Usage: php examples/estimate-price.php
 */

declare(strict_types=1);

use MetabytesSRO\EPost\Api\Pricing\LetterPriceCalculator;
use MetabytesSRO\EPost\Api\Pricing\PriceConfig;

require_once __DIR__ . '/../vendor/autoload.php';

$calculator = LetterPriceCalculator::fromEnv();
printf("Tariff %s, price list valid from %s\n\n", $calculator->getConfig()->tariff->value, PriceConfig::PRICE_LIST_VALID_FROM);

$cases = [
    ['1 page, black and white', 20, 1, false, false, false],
    ['3 pages, black and white', 20, 3, false, false, false],
    ['4 pages, colour, duplex', 50, 4, true, true, false],
    ['10 pages, colour', 500, 10, true, false, false],
    ['1 page, international', 20, 1, false, false, true],
    ['6 pages, international, duplex', 50, 6, false, true, true],
];

foreach ($cases as [$label, $weight, $pages, $color, $duplex, $international]) {
    printf("%-32s %5d g  %6.2f EUR\n", $label, $weight, $calculator->calculate($weight, $pages, $color, $duplex, $international));
}

printf("\n100 letters, 2 pages, black and white: %.2f EUR\n", $calculator->calculateBatch(100, 20, 2));

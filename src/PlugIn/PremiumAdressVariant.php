<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\PlugIn;

/**
 * Product variants of Deutsche Post PREMIUMADRESS.
 */
enum PremiumAdressVariant: string
{
    case Basic = 'Basic';
    case Report = 'Report';
}

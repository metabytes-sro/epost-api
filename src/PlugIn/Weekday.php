<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\PlugIn;

/**
 * Weekday abbreviations accepted by the UploadManagement plugin's due-day option.
 */
enum Weekday: string
{
    case Monday = 'Mo';
    case Tuesday = 'Di';
    case Wednesday = 'Mi';
    case Thursday = 'Do';
    case Friday = 'Fr';
    case Saturday = 'Sa';
    case Sunday = 'So';
}

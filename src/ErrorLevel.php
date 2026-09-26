<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Severity of an Error object returned by the API.
 */
enum ErrorLevel: string
{
    /** Information only, no action needed. */
    case Info = 'Info';

    /** Something to look at, processing continues. */
    case Warning = 'Warning';

    /** Processing stopped, action needed. */
    case Error = 'Error';

    /**
     * Case-insensitive lookup; null for an unknown level.
     */
    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $label) === 0) {
                return $case;
            }
        }

        return null;
    }
}

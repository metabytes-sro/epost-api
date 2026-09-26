<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use MetabytesSRO\EPost\Api\Exception\ValidationException;

/**
 * Field validation shared by the value objects. Lengths are counted in
 * characters, as the API's JSON schema does.
 *
 * @internal
 */
final class Validate
{
    /**
     * @throws ValidationException
     */
    public static function notBlank(string $field, string $value): void
    {
        if (trim($value) === '') {
            throw new ValidationException(sprintf('%s must not be empty', $field));
        }
    }

    /**
     * @throws ValidationException
     */
    public static function maxLength(string $field, ?string $value, int $max): void
    {
        if ($value !== null && mb_strlen($value) > $max) {
            throw new ValidationException(sprintf('%s exceeds the maximum length of %d characters', $field, $max));
        }
    }

    /**
     * @throws ValidationException
     */
    public static function matches(string $field, ?string $value, string $pattern, string $requirement): void
    {
        if ($value !== null && $value !== '' && preg_match($pattern, $value) !== 1) {
            throw new ValidationException(sprintf('%s %s', $field, $requirement));
        }
    }
}

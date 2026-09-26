<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use JsonException;

/**
 * JSON helpers shared by the API classes: encoding, decoding and reading typed
 * values out of the loosely typed arrays json_decode() produces.
 *
 * @internal
 */
final class Json
{
    /**
     * @throws JsonException when the value cannot be encoded
     */
    public static function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode a JSON object. Anything that is not a JSON object (invalid JSON, a
     * scalar, an empty body) yields an empty array so callers can rely on the shape.
     *
     * @return array<string, mixed>
     */
    public static function decodeObject(string $json): array
    {
        $decoded = self::decode($json);

        return is_array($decoded) ? self::toObject($decoded) : [];
    }

    /**
     * Decode a JSON array of objects, dropping any item that is not an object.
     *
     * @return list<array<string, mixed>>
     */
    public static function decodeList(string $json): array
    {
        return self::objectList(self::decode($json));
    }

    /**
     * Normalise an already decoded value to a list of objects, dropping anything else.
     *
     * @return list<array<string, mixed>>
     */
    public static function objectList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $list = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $list[] = self::toObject($item);
            }
        }

        return $list;
    }

    /**
     * Scalar value as string, null for null, arrays and objects.
     */
    public static function string(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        return null;
    }

    /**
     * Numeric value as int, null for anything else.
     */
    public static function int(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return null;
    }

    /**
     * Numeric value as float, null for anything else.
     */
    public static function float(mixed $value): ?float
    {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * Truthiness of a decoded value; the strings "false" and "0" count as false.
     */
    public static function bool(mixed $value): bool
    {
        if (is_string($value)) {
            return !in_array(strtolower($value), ['', '0', 'false'], true);
        }

        return (bool) $value;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function toObject(array $data): array
    {
        $object = [];
        foreach ($data as $key => $value) {
            $object[(string) $key] = $value;
        }

        return $object;
    }

    private static function decode(string $json): mixed
    {
        if ($json === '') {
            return null;
        }
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}

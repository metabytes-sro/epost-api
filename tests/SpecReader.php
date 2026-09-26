<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use PHPUnit\Framework\Assert;

/**
 * Reads values out of the API definition snapshot in docs/api.
 */
final class SpecReader
{
    /**
     * A string at the given path of keys in the decoded JSON document.
     */
    public static function string(string $json, string ...$path): string
    {
        $node = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        foreach ($path as $key) {
            Assert::assertIsArray($node, 'expected an object at "' . $key . '"');
            Assert::assertArrayHasKey($key, $node);
            $node = $node[$key];
        }
        Assert::assertIsString($node);

        return $node;
    }
}

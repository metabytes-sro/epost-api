<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testEncodeKeepsUnicodeAndSlashes(): void
    {
        self::assertSame('{"city":"Köln","path":"a/b"}', Json::encode(['city' => 'Köln', 'path' => 'a/b']));
    }

    public function testDecodeObject(): void
    {
        self::assertSame(['a' => 1, '2' => 'b'], Json::decodeObject('{"a":1,"2":"b"}'));
        self::assertSame([], Json::decodeObject(''));
        self::assertSame([], Json::decodeObject('not json'));
        self::assertSame([], Json::decodeObject('"scalar"'));
        self::assertSame([], Json::decodeObject('null'));
    }

    public function testDecodeList(): void
    {
        self::assertSame([['id' => 1], ['id' => 2]], Json::decodeList('[{"id":1},"skip",{"id":2},3]'));
        self::assertSame([], Json::decodeList(''));
        self::assertSame([], Json::decodeList('{'));
        self::assertSame([], Json::decodeList('42'));
    }

    public function testObjectList(): void
    {
        self::assertSame([['a' => 1]], Json::objectList([['a' => 1], null, 'x']));
        self::assertSame([], Json::objectList(null));
        self::assertSame([], Json::objectList('string'));
    }

    /**
     * @return iterable<string, array{mixed, ?string}>
     */
    public static function strings(): iterable
    {
        yield 'string' => ['abc', 'abc'];
        yield 'int' => [42, '42'];
        yield 'float' => [1.5, '1.5'];
        yield 'true' => [true, '1'];
        yield 'false' => [false, ''];
        yield 'null' => [null, null];
        yield 'array' => [['x'], null];
    }

    #[DataProvider('strings')]
    public function testString(mixed $value, ?string $expected): void
    {
        self::assertSame($expected, Json::string($value));
    }

    /**
     * @return iterable<string, array{mixed, ?int}>
     */
    public static function ints(): iterable
    {
        yield 'int' => [42, 42];
        yield 'float' => [4.9, 4];
        yield 'numeric string' => ['17', 17];
        yield 'non-numeric string' => ['abc', null];
        yield 'true' => [true, 1];
        yield 'false' => [false, 0];
        yield 'null' => [null, null];
        yield 'array' => [[1], null];
    }

    #[DataProvider('ints')]
    public function testInt(mixed $value, ?int $expected): void
    {
        self::assertSame($expected, Json::int($value));
    }

    /**
     * @return iterable<string, array{mixed, ?float}>
     */
    public static function floats(): iterable
    {
        yield 'float' => [0.8, 0.8];
        yield 'int' => [2, 2.0];
        yield 'numeric string' => ['1.25', 1.25];
        yield 'non-numeric string' => ['abc', null];
        yield 'null' => [null, null];
        yield 'bool' => [true, null];
    }

    #[DataProvider('floats')]
    public function testFloat(mixed $value, ?float $expected): void
    {
        self::assertSame($expected, Json::float($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function bools(): iterable
    {
        yield 'true' => [true, true];
        yield 'false' => [false, false];
        yield 'one' => [1, true];
        yield 'zero' => [0, false];
        yield 'string true' => ['true', true];
        yield 'string TRUE' => ['TRUE', true];
        yield 'string false' => ['false', false];
        yield 'string zero' => ['0', false];
        yield 'empty string' => ['', false];
        yield 'other string' => ['yes', true];
        yield 'null' => [null, false];
    }

    #[DataProvider('bools')]
    public function testBool(mixed $value, bool $expected): void
    {
        self::assertSame($expected, Json::bool($value));
    }

    /**
     * @return iterable<string, array{mixed, ?string}>
     */
    public static function dates(): iterable
    {
        yield 'date time' => ['2024-01-15T10:30:00', '2024-01-15 10:30:00'];
        yield 'date only' => ['2024-01-15', '2024-01-15 00:00:00'];
        yield 'empty' => ['', null];
        yield 'blank' => ['   ', null];
        yield 'null' => [null, null];
        yield 'garbage' => ['not a date', null];
        yield 'array' => [[], null];
    }

    #[DataProvider('dates')]
    public function testDate(mixed $value, ?string $expected): void
    {
        self::assertSame($expected, Json::date($value)?->format('Y-m-d H:i:s'));
    }
}

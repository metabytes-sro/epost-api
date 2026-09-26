<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\Error;
use MetabytesSRO\EPost\Api\ErrorCode;
use MetabytesSRO\EPost\Api\ErrorLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function testFromArray(): void
    {
        $error = Error::fromArray([
            'level' => 'Error',
            'code' => 'E101',
            'description' => 'Ungültiges Token - Abgelaufen',
            'date' => '2026-01-01T10:30:00',
        ]);

        self::assertSame('Error', $error->level);
        self::assertSame('E101', $error->code);
        self::assertSame('Ungültiges Token - Abgelaufen', $error->description);
        self::assertSame('2026-01-01 10:30:00', $error->date?->format('Y-m-d H:i:s'));
        self::assertSame(ErrorLevel::Error, $error->errorLevel());
        self::assertSame(ErrorCode::E101, $error->errorCode());
        self::assertTrue($error->isError());
        self::assertFalse($error->isWarning());
        self::assertFalse($error->isInfo());
    }

    public function testFromArrayDefaults(): void
    {
        $error = Error::fromArray([]);

        self::assertSame('', $error->level);
        self::assertSame('', $error->code);
        self::assertSame('', $error->description);
        self::assertNull($error->date);
        self::assertNull($error->errorLevel());
        self::assertNull($error->errorCode());
        self::assertFalse($error->isError());
        self::assertFalse($error->isWarning());
        self::assertFalse($error->isInfo());
    }

    public function testLevelChecksAreCaseInsensitive(): void
    {
        self::assertTrue((new Error('warning', 'W201', ''))->isWarning());
        self::assertTrue((new Error('INFO', 'I501', ''))->isInfo());
        self::assertTrue((new Error('error', 'E399', ''))->isError());
    }

    public function testUnknownCodeYieldsNullEnum(): void
    {
        self::assertNull((new Error('Error', 'E998', ''))->errorCode());
    }

    public function testErrorLevelFromLabel(): void
    {
        self::assertSame(ErrorLevel::Info, ErrorLevel::fromLabel('info'));
        self::assertSame(ErrorLevel::Warning, ErrorLevel::fromLabel('WARNING'));
        self::assertSame(ErrorLevel::Error, ErrorLevel::fromLabel('Error'));
        self::assertNull(ErrorLevel::fromLabel('fatal'));
    }

    /**
     * @return iterable<string, array{ErrorCode}>
     */
    public static function codes(): iterable
    {
        foreach (ErrorCode::cases() as $code) {
            yield $code->value => [$code];
        }
    }

    #[DataProvider('codes')]
    public function testEveryErrorCodeHasLevelAndDescription(ErrorCode $code): void
    {
        $expectedLevel = match ($code->value[0]) {
            'E' => ErrorLevel::Error,
            'W' => ErrorLevel::Warning,
            'I' => ErrorLevel::Info,
        };

        self::assertSame($expectedLevel, $code->level());
        self::assertNotSame('', $code->description());
    }

    public function testErrorCodeCatalogueMatchesTheApiDefinition(): void
    {
        $json = file_get_contents(__DIR__ . '/../docs/api/epost-api-v2.6.1.swagger.json');
        self::assertIsString($json);
        $description = SpecReader::string($json, 'components', 'schemas', 'Error', 'description');

        preg_match_all('/\b([EWI]\d{3})\b/', $description, $matches);
        $documented = array_values(array_unique($matches[1]));
        sort($documented);
        $implemented = array_map(static fn(ErrorCode $c): string => $c->value, ErrorCode::cases());
        sort($implemented);

        self::assertSame($documented, $implemented);
    }
}

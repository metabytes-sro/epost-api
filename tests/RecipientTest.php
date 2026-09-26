<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\Recipient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{
    public function testToArrayContainsOnlyGivenFields(): void
    {
        $recipient = new Recipient('Max Mustermann', '53115', 'Bonn', 'Musterstraße 1');

        self::assertSame([
            'addressLine1' => 'Max Mustermann',
            'addressLine2' => 'Musterstraße 1',
            'zipCode' => '53115',
            'city' => 'Bonn',
        ], $recipient->toArray());
        self::assertFalse($recipient->isInternational());
    }

    public function testAllFields(): void
    {
        $recipient = new Recipient(
            addressLine1: 'Mario Rossi',
            zipCode: '00100',
            city: 'Roma',
            addressLine2: 'Via Roma 1',
            addressLine3: 'Scala B',
            addressLine4: 'Piano 2',
            country: 'ITALIEN',
        );

        self::assertSame([
            'addressLine1' => 'Mario Rossi',
            'addressLine2' => 'Via Roma 1',
            'addressLine3' => 'Scala B',
            'addressLine4' => 'Piano 2',
            'zipCode' => '00100',
            'city' => 'Roma',
            'country' => 'ITALIEN',
        ], $recipient->toArray());
        self::assertTrue($recipient->isInternational());
    }

    public function testAddressLine5ForGermanAddress(): void
    {
        $recipient = new Recipient('Max', '53115', 'Bonn', addressLine5: 'Hinterhaus');

        self::assertSame('Hinterhaus', $recipient->toArray()['addressLine5']);
    }

    public function testAddressLine5RejectedWithCountry(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('addressLine5');
        new Recipient('Max', '1010', 'Wien', addressLine5: 'x', country: 'ÖSTERREICH');
    }

    /**
     * @return iterable<string, array{?string, bool}>
     */
    public static function countries(): iterable
    {
        yield 'none' => [null, false];
        yield 'empty' => ['', false];
        yield 'blank' => ['  ', false];
        yield 'Deutschland' => ['DEUTSCHLAND', false];
        yield 'deutschland lower' => ['deutschland', false];
        yield 'DE' => ['DE', false];
        yield 'Germany' => ['Germany', false];
        yield 'Italien' => ['ITALIEN', true];
    }

    #[DataProvider('countries')]
    public function testIsInternational(?string $country, bool $expected): void
    {
        self::assertSame($expected, (new Recipient('Max', '1', 'City', country: $country))->isInternational());
    }

    public function testNoZipCodePlaceholder(): void
    {
        $recipient = new Recipient('Someone', Recipient::NO_ZIP_CODE, 'Dublin', country: 'IRLAND');

        self::assertSame('   ', $recipient->toArray()['zipCode']);
    }

    public function testForAutomover(): void
    {
        $recipient = Recipient::forAutomover();

        self::assertSame(['addressLine1' => 'AUTOMOVER', 'zipCode' => '99999', 'city' => 'AUTOMOVER'], $recipient->toArray());
    }

    /**
     * @return iterable<string, array{callable(): Recipient, string}>
     */
    public static function invalid(): iterable
    {
        yield 'blank name' => [static fn() => new Recipient(' ', '53115', 'Bonn'), 'addressLine1 must not be empty'];
        yield 'blank zip' => [static fn() => new Recipient('Max', '', 'Bonn'), 'zipCode must not be empty'];
        yield 'blank city' => [static fn() => new Recipient('Max', '53115', ''), 'city must not be empty'];
        yield 'name too long' => [static fn() => new Recipient(str_repeat('a', 81), '53115', 'Bonn'), 'addressLine1 exceeds the maximum length of 80'];
        yield 'line2 too long' => [static fn() => new Recipient('Max', '53115', 'Bonn', str_repeat('a', 81)), 'addressLine2 exceeds'];
        yield 'line3 too long' => [static fn() => new Recipient('Max', '53115', 'Bonn', null, str_repeat('a', 81)), 'addressLine3 exceeds'];
        yield 'line4 too long' => [static fn() => new Recipient('Max', '53115', 'Bonn', null, null, str_repeat('a', 81)), 'addressLine4 exceeds'];
        yield 'line5 too long' => [static fn() => new Recipient('Max', '53115', 'Bonn', null, null, null, str_repeat('a', 81)), 'addressLine5 exceeds'];
        yield 'zip too long' => [static fn() => new Recipient('Max', str_repeat('1', 21), 'Bonn'), 'zipCode exceeds the maximum length of 20'];
        yield 'city too long' => [static fn() => new Recipient('Max', '53115', str_repeat('a', 81)), 'city exceeds'];
        yield 'country too long' => [static fn() => new Recipient('Max', '53115', 'Bonn', country: str_repeat('a', 81)), 'country exceeds'];
    }

    /**
     * @param callable(): Recipient $factory
     */
    #[DataProvider('invalid')]
    public function testValidation(callable $factory, string $message): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);
        $factory();
    }

    public function testLengthIsCountedInCharacters(): void
    {
        $eighty = str_repeat('ü', 80);

        self::assertSame($eighty, (new Recipient($eighty, '53115', 'Bonn'))->addressLine1);
    }
}

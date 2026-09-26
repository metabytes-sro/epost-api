<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\SenderAddress;
use PHPUnit\Framework\TestCase;

class SenderAddressTest extends TestCase
{
    public function testFromFields(): void
    {
        $sender = SenderAddress::fromFields('Fa. Huber GmbH', 'Am Weg 1', '76887', 'Bad Bergzabern');

        self::assertSame([
            'senderAdressLine1' => 'Fa. Huber GmbH',
            'senderStreet' => 'Am Weg 1',
            'senderZipCode' => '76887',
            'senderCity' => 'Bad Bergzabern',
        ], $sender->toArray());
        self::assertNull($sender->completeLine);
    }

    public function testFromCompleteLine(): void
    {
        $sender = SenderAddress::fromCompleteLine('Fa. Huber GmbH, Am Weg 1, 76887 Bad Bergzabern');

        self::assertSame(['senderAdressLineComplete' => 'Fa. Huber GmbH, Am Weg 1, 76887 Bad Bergzabern'], $sender->toArray());
        self::assertNull($sender->line1);
    }

    public function testFieldsMustNotBeBlank(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('street must not be empty');
        SenderAddress::fromFields('Fa. Huber GmbH', ' ', '76887', 'Bad Bergzabern');
    }

    public function testFieldsMaxLength(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('city exceeds the maximum length of 80');
        SenderAddress::fromFields('Fa. Huber GmbH', 'Am Weg 1', '76887', str_repeat('a', 81));
    }

    public function testCompleteLineMustNotBeBlank(): void
    {
        $this->expectException(ValidationException::class);
        SenderAddress::fromCompleteLine('');
    }

    public function testCompleteLineMaxLength(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('completeLine exceeds the maximum length of 200');
        SenderAddress::fromCompleteLine(str_repeat('a', 201));
    }
}

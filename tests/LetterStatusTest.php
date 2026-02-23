<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\LetterStatus;
use PHPUnit\Framework\TestCase;

class LetterStatusTest extends TestCase
{
    public function testGetErrorsReturnsEmptyArrayWhenMissing(): void
    {
        $status = new LetterStatus([
            'letterID' => 123,
            'statusID' => 1,
        ]);
        $this->assertSame([], $status->getErrors());
    }

    public function testGetErrorsReturnsErrorList(): void
    {
        $status = new LetterStatus([
            'letterID' => 123,
            'statusID' => 99,
            'errorList' => [
                ['level' => 'Error', 'code' => 'E301', 'description' => 'Invalid PDF'],
            ],
        ]);
        $errors = $status->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('E301', $errors[0]->getCode());
        $this->assertSame('Invalid PDF', $errors[0]->getDescription());
    }

    public function testGetRegisteredLetterStatus(): void
    {
        $status = new LetterStatus([
            'letterID' => 123,
            'statusID' => 4,
            'registeredLetterStatus' => 'DELIVERED',
            'registeredLetterStatusDate' => '2024-01-15T10:30:00',
        ]);
        $this->assertSame('DELIVERED', $status->getRegisteredLetterStatus());
        $this->assertSame('2024-01-15T10:30:00', $status->getRegisteredLetterStatusDate());
    }

    public function testGetRegisteredLetterStatusReturnsNullWhenMissing(): void
    {
        $status = new LetterStatus(['letterID' => 123, 'statusID' => 1]);
        $this->assertNull($status->getRegisteredLetterStatus());
        $this->assertNull($status->getRegisteredLetterStatusDate());
    }
}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\QueuedOperationResult;
use PHPUnit\Framework\TestCase;

class QueuedOperationResultTest extends TestCase
{
    public function testFromArray(): void
    {
        $result = QueuedOperationResult::fromArray([
            'letterID' => 12345,
            'successful' => true,
            'message' => 'Abruch/Freigabe der Sendung war erfolgreich',
        ]);
        $this->assertSame(12345, $result->getLetterId());
        $this->assertTrue($result->isSuccessful());
        $this->assertSame('Abruch/Freigabe der Sendung war erfolgreich', $result->getMessage());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $result = QueuedOperationResult::fromArray(['message' => 'Error message']);
        $this->assertNull($result->getLetterId());
        $this->assertNull($result->isSuccessful());
        $this->assertSame('Error message', $result->getMessage());
    }
}

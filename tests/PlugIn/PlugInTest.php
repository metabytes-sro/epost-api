<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests\PlugIn;

use DateTimeImmutable;
use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\PlugIn\Automover;
use MetabytesSRO\EPost\Api\PlugIn\PremiumAdress;
use MetabytesSRO\EPost\Api\PlugIn\PremiumAdressVariant;
use MetabytesSRO\EPost\Api\PlugIn\UploadManagement;
use MetabytesSRO\EPost\Api\PlugIn\Weekday;
use MetabytesSRO\EPost\Api\PlugInFeedback;
use PHPUnit\Framework\TestCase;

class PlugInTest extends TestCase
{
    public function testUploadManagementDueInDays(): void
    {
        $plugIn = UploadManagement::dueInDays(3);

        self::assertSame('UploadManagement', $plugIn->name());
        self::assertSame(['useMinimumQuantity' => false, 'dueDays' => 3], $plugIn->model());
        self::assertSame(3, $plugIn->dueDays);
    }

    public function testUploadManagementDueInDaysWithMinimumQuantity(): void
    {
        self::assertSame(['useMinimumQuantity' => true, 'dueDays' => 31], UploadManagement::dueInDays(31, true)->model());
    }

    public function testUploadManagementDueInDaysBounds(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('between 1 and 31');
        UploadManagement::dueInDays(32);
    }

    public function testUploadManagementDueInDaysZero(): void
    {
        $this->expectException(ValidationException::class);
        UploadManagement::dueInDays(0);
    }

    public function testUploadManagementDueOn(): void
    {
        $plugIn = UploadManagement::dueOn(new DateTimeImmutable('2026-10-15 13:00'));

        self::assertSame(['useMinimumQuantity' => false, 'dueDate' => '2026-10-15'], $plugIn->model());
    }

    public function testUploadManagementDueOnWeekday(): void
    {
        $plugIn = UploadManagement::dueOnWeekday(Weekday::Friday, true);

        self::assertSame(['useMinimumQuantity' => true, 'dueDayofWeek' => 'Fr'], $plugIn->model());
        self::assertSame(Weekday::Friday, $plugIn->dueDayOfWeek);
    }

    public function testUploadManagementMinimumQuantity(): void
    {
        self::assertSame(['useMinimumQuantity' => true], UploadManagement::minimumQuantity()->model());
    }

    public function testWeekdayValues(): void
    {
        self::assertSame(['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'], array_map(static fn(Weekday $d): string => $d->value, Weekday::cases()));
    }

    public function testAutomoverDefaults(): void
    {
        $plugIn = new Automover();

        self::assertSame('Automover', $plugIn->name());
        self::assertSame(['useAutomover' => true, 'parameters' => []], $plugIn->model());
    }

    public function testAutomoverParameters(): void
    {
        $plugIn = new Automover('10,10,100,90', true);

        self::assertSame([
            'useAutomover' => true,
            'parameters' => [
                ['name' => 'searchAreaXYWH', 'value' => '10,10,100,90'],
                ['name' => 'ShowSearchAreaOnTest', 'value' => '1'],
            ],
        ], $plugIn->model());
    }

    public function testPremiumAdress(): void
    {
        self::assertSame('PremiumAdress', (new PremiumAdress())->name());
        self::assertSame(['productVariants' => 'Basic'], (new PremiumAdress())->model());
        self::assertSame(['productVariants' => 'Report'], (new PremiumAdress(PremiumAdressVariant::Report))->model());
    }

    public function testPlugInFeedbackFromArray(): void
    {
        $feedback = PlugInFeedback::fromArray([
            'plugInName' => 'PremiumAdress',
            'plugInFeedbackModel' => ['productVariants' => 'Basic', 'premiumAdressResult' => ['sdgS' => 1]],
        ]);

        self::assertSame('PremiumAdress', $feedback->name);
        self::assertSame(['productVariants' => 'Basic', 'premiumAdressResult' => ['sdgS' => 1]], $feedback->model);
    }

    public function testPlugInFeedbackWithoutModel(): void
    {
        $feedback = PlugInFeedback::fromArray(['plugInName' => 'UploadManagement', 'plugInFeedbackModel' => null]);

        self::assertSame('UploadManagement', $feedback->name);
        self::assertSame([], $feedback->model);
        self::assertSame('', PlugInFeedback::fromArray([])->name);
    }
}

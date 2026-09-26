<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\PlugIn;

use DateTimeInterface;
use MetabytesSRO\EPost\Api\Exception\ValidationException;

/**
 * UploadManagement plugin: hold letters back for a minimum daily quantity or
 * until a due date instead of forwarding them to the print centre at once.
 *
 * With minimum quantity enabled the API collects letters from 14:00 to 14:00
 * the next day and releases them once 50 letters were processed; letters that
 * did not make the quantity are sent on their due date. Without minimum quantity
 * only the due date counts. Exactly one due option may be given; the default
 * is one day after processing. Due dates must be between 1 and 31 days ahead.
 * Queued letters can be cancelled or released early with
 * EPostClient::cancelQueued() and releaseQueued().
 */
final readonly class UploadManagement implements PlugInInterface
{
    public const string NAME = 'UploadManagement';
    public const int MIN_DUE_DAYS = 1;
    public const int MAX_DUE_DAYS = 31;

    private function __construct(
        public bool $useMinimumQuantity,
        public ?int $dueDays,
        public ?DateTimeInterface $dueDate,
        public ?Weekday $dueDayOfWeek,
    ) {}

    /**
     * Send after the given number of days from processing (1 to 31).
     *
     * @throws ValidationException
     */
    public static function dueInDays(int $days, bool $useMinimumQuantity = false): self
    {
        if ($days < self::MIN_DUE_DAYS || $days > self::MAX_DUE_DAYS) {
            throw new ValidationException(sprintf('dueDays must be between %d and %d', self::MIN_DUE_DAYS, self::MAX_DUE_DAYS));
        }

        return new self($useMinimumQuantity, $days, null, null);
    }

    /**
     * Send on the given date (1 to 31 days ahead).
     */
    public static function dueOn(DateTimeInterface $date, bool $useMinimumQuantity = false): self
    {
        return new self($useMinimumQuantity, null, $date, null);
    }

    /**
     * Send on the next given weekday.
     */
    public static function dueOnWeekday(Weekday $weekday, bool $useMinimumQuantity = false): self
    {
        return new self($useMinimumQuantity, null, null, $weekday);
    }

    /**
     * Collect for the minimum quantity with the API's default due date of one day.
     */
    public static function minimumQuantity(): self
    {
        return new self(true, null, null, null);
    }

    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @return array<string, mixed>
     */
    public function model(): array
    {
        $model = ['useMinimumQuantity' => $this->useMinimumQuantity];
        if ($this->dueDays !== null) {
            $model['dueDays'] = $this->dueDays;
        }
        if ($this->dueDate !== null) {
            $model['dueDate'] = $this->dueDate->format('Y-m-d');
        }
        if ($this->dueDayOfWeek !== null) {
            $model['dueDayofWeek'] = $this->dueDayOfWeek->value;
        }

        return $model;
    }
}

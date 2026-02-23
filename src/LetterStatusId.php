<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Letter processing status IDs from the E-POSTBUSINESS API.
 */
enum LetterStatusId: int
{
    /** Letter accepted for processing. (open) */
    case AcceptanceOfShipment = 1;

    /** Letter is being processed. (open) */
    case ProcessingTheShipment = 2;

    /** Letter is being delivered to the printing center. (open) */
    case DeliveryToThePrintingCenter = 3;

    /** Letter is being processed at the printing center (sent). */
    case ProcessingInPrintingCenter = 4;

    /** Processing error occurred. (failed) */
    case ProcessingError = 99;

    /**
     * Map a raw statusID from the API to the enum. Returns null for unknown IDs.
     */
    public static function fromStatusId(int $statusId): ?self
    {
        return self::tryFrom($statusId);
    }
}

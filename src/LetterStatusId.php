<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Letter processing status IDs from the E-POSTBUSINESS API.
 *
 * A letter moves through 1, 2, 3 and 4 in order. Status 99 can follow any of
 * them when processing fails; the reasons are in LetterStatus::$errors.
 */
enum LetterStatusId: int
{
    /** Letter accepted: JSON validated, no schema violations. (open) */
    case AcceptanceOfShipment = 1;

    /** PDF checked for E-POST conformity and released for the print centre, a few minutes after acceptance. (open) */
    case ProcessingTheShipment = 2;

    /** Letter transferred to the print centre, within hours of processing. (open) */
    case DeliveryToThePrintingCenter = 3;

    /** Print centre reported the letter as sent, 1 to 2 working days after transfer. (sent) */
    case ProcessingInPrintingCenter = 4;

    /** Processing failed, see LetterStatus::$errors. (failed) */
    case ProcessingError = 99;

    /**
     * True while the letter has not reached the print centre's final feedback (status 1 to 3).
     */
    public function isOpen(): bool
    {
        return $this->value >= 1 && $this->value <= 3;
    }

    /**
     * True once the print centre reported the letter as sent (status 4).
     */
    public function isSent(): bool
    {
        return $this === self::ProcessingInPrintingCenter;
    }

    /**
     * True when processing failed (status 99).
     */
    public function isError(): bool
    {
        return $this === self::ProcessingError;
    }

    /**
     * German status label as used by the API's statusDetails field.
     */
    public function label(): string
    {
        return match ($this) {
            self::AcceptanceOfShipment => 'Annahme der Sendung',
            self::ProcessingTheShipment => 'Verarbeitung der Sendung',
            self::DeliveryToThePrintingCenter => 'Einlieferung in Druckzentrum',
            self::ProcessingInPrintingCenter => 'Verarbeitung in Druckzentrum',
            self::ProcessingError => 'Verarbeitungsfehler',
        };
    }
}

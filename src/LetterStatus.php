<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Status of a letter as returned by the status endpoints of the API.
 *
 * Dates are returned as the API sends them, ISO 8601 strings such as
 * "2024-01-15T10:30:00", and are null when the letter has not reached that step.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterStatus schema
 */
class LetterStatus
{
    /**
     * @param array<string, mixed> $data Raw LetterStatus object from the API
     */
    public function __construct(
        private readonly array $data,
    ) {}

    public function getLetterId(): int
    {
        return $this->int('letterID') ?? 0;
    }

    public function getStatusId(): int
    {
        return $this->int('statusID') ?? 0;
    }

    /**
     * Map statusID to LetterStatusId enum. Returns null for unknown status IDs.
     */
    public function getStatus(): ?LetterStatusId
    {
        $statusId = $this->getStatusId();

        return $statusId > 0 ? LetterStatusId::fromStatusId($statusId) : null;
    }

    /**
     * True while the letter is still being processed (status 1 to 3).
     */
    public function isOpen(): bool
    {
        return $this->getStatus()?->isOpen() ?? false;
    }

    /**
     * True once the print centre reported the letter as sent (status 4).
     */
    public function isSent(): bool
    {
        return $this->getStatus()?->isSent() ?? false;
    }

    /**
     * True when processing failed (status 99). The reasons are in getErrors().
     */
    public function hasError(): bool
    {
        return $this->getStatus()?->isError() ?? false;
    }

    public function getFileName(): ?string
    {
        return $this->string('fileName');
    }

    public function getStatusDetails(): ?string
    {
        return $this->string('statusDetails');
    }

    /** Time the letter was accepted. */
    public function getCreatedDate(): ?string
    {
        return $this->string('createdDate');
    }

    /** Time the PDF was processed (status 2). */
    public function getProcessedDate(): ?string
    {
        return $this->string('processedDate');
    }

    /** Time the letter was transferred to the print centre (status 3). */
    public function getPrintUploadDate(): ?string
    {
        return $this->string('printUploadDate');
    }

    /** Time the print centre reported the letter as sent (status 4). */
    public function getPrintFeedbackDate(): ?string
    {
        return $this->string('printFeedbackDate');
    }

    public function isTestFlag(): bool
    {
        return $this->bool('testFlag');
    }

    public function getTestEmail(): ?string
    {
        return $this->string('testEMail');
    }

    public function isTestShowRestrictedArea(): bool
    {
        return $this->bool('testShowRestrictedArea');
    }

    /**
     * Registered-mail option the letter was sent with, one of the
     * DeliveryOptions::OPTION_REGISTERED_* values, or null for an ordinary letter.
     */
    public function getRegisteredLetter(): ?string
    {
        return $this->string('registeredLetter');
    }

    public function isRegisteredLetter(): bool
    {
        return $this->getRegisteredLetter() !== null && $this->getRegisteredLetter() !== '';
    }

    public function getBatchId(): ?int
    {
        return $this->int('batchID');
    }

    public function hasCoverLetter(): bool
    {
        return $this->bool('coverLetter');
    }

    public function getNumberOfPages(): ?int
    {
        return $this->int('noOfPages');
    }

    /** Partner-managed customer identifier (vendorSubID at login), when set. */
    public function getSubVendorId(): ?string
    {
        return $this->string('subVendorID');
    }

    public function getCustom1(): ?string
    {
        return $this->string('custom1');
    }

    public function getCustom2(): ?string
    {
        return $this->string('custom2');
    }

    public function getCustom3(): ?string
    {
        return $this->string('custom3');
    }

    public function getCustom4(): ?string
    {
        return $this->string('custom4');
    }

    public function getCustom5(): ?string
    {
        return $this->string('custom5');
    }

    /** Recipient zip code. */
    public function getZipCode(): ?string
    {
        return $this->string('zipCode');
    }

    /** Recipient city. */
    public function getCity(): ?string
    {
        return $this->string('city');
    }

    /** Recipient country, empty or null for domestic letters. */
    public function getCountry(): ?string
    {
        return $this->string('country');
    }

    public function isColor(): bool
    {
        return $this->bool('isColor');
    }

    public function isDuplex(): bool
    {
        return $this->bool('isDuplex');
    }

    /**
     * Registered-mail tracking number, assigned once the letter reached the print centre (status 3).
     */
    public function getRegisteredLetterId(): ?string
    {
        return $this->string('registeredLetterID');
    }

    /**
     * Einschreiben tracking status code (e.g. DELIVERED, IN_DELIVERY).
     * Resolve description via TrackStatusCodes::getDescription().
     */
    public function getRegisteredLetterStatus(): ?string
    {
        return $this->string('registeredLetterStatus');
    }

    /**
     * Date of the latest Einschreiben status update.
     */
    public function getRegisteredLetterStatusDate(): ?string
    {
        return $this->string('registeredLetterStatusDate');
    }

    public function getVendorSystemInformation(): ?string
    {
        return $this->string('vendorSystemInformation');
    }

    /** Cost centre the letter is billed to, when one was given. */
    public function getCostCenter(): ?string
    {
        return $this->string('costCenter');
    }

    /**
     * Franking ID of the letter, assigned after successful processing in the print centre (status 4).
     */
    public function getFrankierId(): ?string
    {
        return $this->string('frankierID');
    }

    /** Latest status of the letter's arrival in the destination area. */
    public function getDestinationAreaStatus(): ?string
    {
        return $this->string('destinationAreaStatus');
    }

    /** Date of the latest destination area status. */
    public function getDestinationAreaStatusDate(): ?string
    {
        return $this->string('destinationAreaStatusDate');
    }

    /**
     * Feedback of the plugins the letter was sent with, as raw arrays with
     * "plugInName" and "plugInFeedbackModel" keys.
     *
     * @return list<array<string, mixed>>
     */
    public function getPlugInFeedback(): array
    {
        return Json::objectList($this->data['plugInFeedbackList'] ?? null);
    }

    /**
     * Access raw data by key. Prefer typed getters when available.
     */
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * The raw LetterStatus object as received from the API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Errors, warnings and infos reported for the letter.
     *
     * @return LetterStatusError[]
     */
    public function getErrors(): array
    {
        return array_map(
            static fn(array $item) => LetterStatusError::fromArray($item),
            Json::objectList($this->data['errorList'] ?? null),
        );
    }

    private function string(string $key): ?string
    {
        return Json::string($this->data[$key] ?? null);
    }

    private function int(string $key): ?int
    {
        return Json::int($this->data[$key] ?? null);
    }

    private function bool(string $key): bool
    {
        return Json::bool($this->data[$key] ?? null);
    }
}

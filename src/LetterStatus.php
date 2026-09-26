<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use DateTimeImmutable;

/**
 * Status of a letter as returned by the status endpoints of the API.
 *
 * All properties mirror the LetterStatus schema of the API definition. Dates
 * are null while the letter has not reached that step.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json LetterStatus schema
 */
final readonly class LetterStatus
{
    /**
     * @param list<Error> $errors Errors, warnings and infos reported for the letter
     * @param list<PlugInFeedback> $plugInFeedback Feedback of the plugins the letter was sent with
     * @param array<string, mixed> $raw The LetterStatus object as received, for fields this class does not map
     */
    public function __construct(
        public int $letterId,
        public int $statusId,
        public ?string $fileName = null,
        public ?string $statusDetails = null,
        public ?DateTimeImmutable $createdDate = null,
        public ?DateTimeImmutable $processedDate = null,
        public ?DateTimeImmutable $printUploadDate = null,
        public ?DateTimeImmutable $printFeedbackDate = null,
        public bool $testFlag = false,
        public ?string $testEmail = null,
        public bool $testShowRestrictedArea = false,
        public ?string $registeredLetter = null,
        public ?string $registeredLetterId = null,
        public ?string $registeredLetterStatus = null,
        public ?DateTimeImmutable $registeredLetterStatusDate = null,
        public ?int $batchId = null,
        public bool $coverLetter = false,
        public ?int $numberOfPages = null,
        public ?string $subVendorId = null,
        public ?string $custom1 = null,
        public ?string $custom2 = null,
        public ?string $custom3 = null,
        public ?string $custom4 = null,
        public ?string $custom5 = null,
        public ?string $zipCode = null,
        public ?string $city = null,
        public ?string $country = null,
        public bool $isColor = false,
        public bool $isDuplex = false,
        public ?string $vendorSystemInformation = null,
        public ?string $costCenter = null,
        public ?string $frankierId = null,
        public ?string $destinationAreaStatus = null,
        public ?DateTimeImmutable $destinationAreaStatusDate = null,
        public array $errors = [],
        public array $plugInFeedback = [],
        public array $raw = [],
    ) {}

    /**
     * @param array<string, mixed> $data Raw LetterStatus object from the API
     */
    public static function fromArray(array $data): self
    {
        $string = static fn(string $key): ?string => Json::string($data[$key] ?? null);
        $int = static fn(string $key): ?int => Json::int($data[$key] ?? null);
        $bool = static fn(string $key): bool => Json::bool($data[$key] ?? null);
        $date = static fn(string $key): ?DateTimeImmutable => Json::date($data[$key] ?? null);

        return new self(
            letterId: $int('letterID') ?? 0,
            statusId: $int('statusID') ?? 0,
            fileName: $string('fileName'),
            statusDetails: $string('statusDetails'),
            createdDate: $date('createdDate'),
            processedDate: $date('processedDate'),
            printUploadDate: $date('printUploadDate'),
            printFeedbackDate: $date('printFeedbackDate'),
            testFlag: $bool('testFlag'),
            testEmail: $string('testEMail'),
            testShowRestrictedArea: $bool('testShowRestrictedArea'),
            registeredLetter: $string('registeredLetter'),
            registeredLetterId: $string('registeredLetterID'),
            registeredLetterStatus: $string('registeredLetterStatus'),
            registeredLetterStatusDate: $date('registeredLetterStatusDate'),
            batchId: $int('batchID'),
            coverLetter: $bool('coverLetter'),
            numberOfPages: $int('noOfPages'),
            subVendorId: $string('subVendorID'),
            custom1: $string('custom1'),
            custom2: $string('custom2'),
            custom3: $string('custom3'),
            custom4: $string('custom4'),
            custom5: $string('custom5'),
            zipCode: $string('zipCode'),
            city: $string('city'),
            country: $string('country'),
            isColor: $bool('isColor'),
            isDuplex: $bool('isDuplex'),
            vendorSystemInformation: $string('vendorSystemInformation'),
            costCenter: $string('costCenter'),
            frankierId: $string('frankierID'),
            destinationAreaStatus: $string('destinationAreaStatus'),
            destinationAreaStatusDate: $date('destinationAreaStatusDate'),
            errors: array_map(
                static fn(array $item): Error => Error::fromArray($item),
                Json::objectList($data['errorList'] ?? null),
            ),
            plugInFeedback: array_map(
                static fn(array $item): PlugInFeedback => PlugInFeedback::fromArray($item),
                Json::objectList($data['plugInFeedbackList'] ?? null),
            ),
            raw: $data,
        );
    }

    /**
     * The processing status as enum, or null for a status ID this package does not know.
     */
    public function status(): ?LetterStatusId
    {
        return LetterStatusId::tryFrom($this->statusId);
    }

    /**
     * True while the letter is still being processed (status 1 to 3).
     */
    public function isOpen(): bool
    {
        return $this->status()?->isOpen() ?? false;
    }

    /**
     * True once the print centre reported the letter as sent (status 4).
     */
    public function isSent(): bool
    {
        return $this->status()?->isSent() ?? false;
    }

    /**
     * True when processing failed (status 99). The reasons are in $errors.
     */
    public function hasError(): bool
    {
        return $this->status()?->isError() ?? false;
    }

    public function isRegisteredMail(): bool
    {
        return $this->registeredLetter !== null && $this->registeredLetter !== '';
    }

    /**
     * The registered-mail option as enum, or null for an ordinary letter or an unknown value.
     */
    public function registeredMailType(): ?RegisteredMailType
    {
        return $this->registeredLetter === null ? null : RegisteredMailType::tryFrom($this->registeredLetter);
    }

    /**
     * The tracking status of a registered letter as enum, or null when there is
     * none yet or the code is unknown to this package.
     */
    public function trackingStatus(): ?TrackStatusCode
    {
        return $this->registeredLetterStatus === null ? null : TrackStatusCode::tryFrom($this->registeredLetterStatus);
    }

    /**
     * Only the entries of $errors with level "Error".
     *
     * @return list<Error>
     */
    public function errorsOnly(): array
    {
        return array_values(array_filter($this->errors, static fn(Error $e): bool => $e->isError()));
    }

    /**
     * Only the entries of $errors with level "Warning".
     *
     * @return list<Error>
     */
    public function warnings(): array
    {
        return array_values(array_filter($this->errors, static fn(Error $e): bool => $e->isWarning()));
    }
}

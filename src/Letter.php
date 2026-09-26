<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\PlugIn\Automover;
use MetabytesSRO\EPost\Api\PlugIn\PlugInInterface;
use MetabytesSRO\EPost\Api\PlugIn\PremiumAdress;

/**
 * A letter to submit through EPostClient::sendLetter().
 *
 * The recipient and the PDF document are mandatory; everything else is
 * optional. toPayload() validates the combination of options against the
 * rules of the API before anything is sent.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json Letter schema
 */
final class Letter
{
    public const int MAX_CUSTOM_LENGTH = 80;
    public const int MAX_VENDOR_SYSTEM_INFORMATION_LENGTH = 120;
    public const int MAX_COST_CENTER_LENGTH = 8;

    private ?Recipient $recipient;
    private ?Attachment $document;
    private ?Attachment $coverSheet = null;
    private bool $generateCoverSheet = false;
    private bool $color = false;
    private bool $duplex = false;
    private ?RegisteredMailType $registeredMail = null;
    private ?int $batchId = null;

    /** @var array<int, string> */
    private array $custom = [];
    private ?string $costCenter = null;
    private ?string $vendorSystemInformation = null;
    private ?SenderAddress $sender = null;
    private ?TestOptions $test = null;

    /** @var list<PlugInInterface> */
    private array $plugIns = [];
    private bool $duplicateFailsafe = false;

    public function __construct(?Recipient $recipient = null, ?Attachment $document = null)
    {
        $this->recipient = $recipient;
        $this->document = $document;
    }

    public function recipient(Recipient $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getRecipient(): ?Recipient
    {
        return $this->recipient;
    }

    /**
     * The PDF/A document to send.
     */
    public function document(Attachment $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getDocument(): ?Attachment
    {
        return $this->document;
    }

    /**
     * Use your own PDF as cover sheet. The API prints the recipient address on it.
     */
    public function coverSheet(?Attachment $coverSheet): self
    {
        $this->coverSheet = $coverSheet;
        $this->generateCoverSheet = $coverSheet !== null;

        return $this;
    }

    public function getCoverSheet(): ?Attachment
    {
        return $this->coverSheet;
    }

    /**
     * Let the API generate its standard cover sheet with the recipient address.
     */
    public function generateCoverSheet(bool $generate = true): self
    {
        $this->generateCoverSheet = $generate;
        if (!$generate) {
            $this->coverSheet = null;
        }

        return $this;
    }

    public function hasCoverSheet(): bool
    {
        return $this->generateCoverSheet;
    }

    public function color(bool $color = true): self
    {
        $this->color = $color;

        return $this;
    }

    public function isColor(): bool
    {
        return $this->color;
    }

    /**
     * Duplex printing. Not allowed together with registered mail (E312).
     */
    public function duplex(bool $duplex = true): self
    {
        $this->duplex = $duplex;

        return $this;
    }

    public function isDuplex(): bool
    {
        return $this->duplex;
    }

    /**
     * Send as registered mail. Not allowed with duplex printing (E312) or an
     * international recipient (E311).
     */
    public function registeredMail(?RegisteredMailType $type): self
    {
        $this->registeredMail = $type;

        return $this;
    }

    public function getRegisteredMail(): ?RegisteredMailType
    {
        return $this->registeredMail;
    }

    /**
     * Group letters for status queries with EPostClient::getLetterStatusByBatch().
     */
    public function batchId(?int $batchId): self
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function getBatchId(): ?int
    {
        return $this->batchId;
    }

    /**
     * Free text stored with the letter and returned in status queries;
     * custom1 can be searched with EPostClient::getLetterStatusByCustom1().
     *
     * @param int $number 1 to 5
     *
     * @throws ValidationException
     */
    public function custom(int $number, ?string $value): self
    {
        if ($number < 1 || $number > 5) {
            throw new ValidationException('Custom field number must be between 1 and 5');
        }
        Validate::maxLength('custom' . $number, $value, self::MAX_CUSTOM_LENGTH);
        if ($value === null || $value === '') {
            unset($this->custom[$number]);
        } else {
            $this->custom[$number] = $value;
        }

        return $this;
    }

    public function getCustom(int $number): ?string
    {
        return $this->custom[$number] ?? null;
    }

    /**
     * Cost centre for invoice grouping: up to 8 characters, letters and digits only.
     *
     * @throws ValidationException
     */
    public function costCenter(?string $costCenter): self
    {
        Validate::maxLength('costCenter', $costCenter, self::MAX_COST_CENTER_LENGTH);
        Validate::matches('costCenter', $costCenter, '/^[A-Za-z0-9]+$/', 'may only contain letters and digits');
        $this->costCenter = $costCenter;

        return $this;
    }

    public function getCostCenter(): ?string
    {
        return $this->costCenter;
    }

    /**
     * Identifier of the sending software, up to 120 characters.
     *
     * @throws ValidationException
     */
    public function vendorSystemInformation(?string $information): self
    {
        Validate::maxLength('vendorSystemInformation', $information, self::MAX_VENDOR_SYSTEM_INFORMATION_LENGTH);
        $this->vendorSystemInformation = $information;

        return $this;
    }

    public function getVendorSystemInformation(): ?string
    {
        return $this->vendorSystemInformation;
    }

    /**
     * Sender address printed on a generated cover sheet or by the Automover plugin.
     */
    public function sender(?SenderAddress $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getSender(): ?SenderAddress
    {
        return $this->sender;
    }

    /**
     * Send in test mode: processed and emailed, not printed.
     */
    public function test(?TestOptions $test): self
    {
        $this->test = $test;

        return $this;
    }

    public function getTest(): ?TestOptions
    {
        return $this->test;
    }

    /**
     * Attach a plugin (UploadManagement, Automover, PremiumAdress). One instance per plugin.
     */
    public function plugIn(PlugInInterface $plugIn): self
    {
        $this->plugIns = array_values(array_filter(
            $this->plugIns,
            static fn(PlugInInterface $existing): bool => $existing->name() !== $plugIn->name(),
        ));
        $this->plugIns[] = $plugIn;

        return $this;
    }

    /**
     * @return list<PlugInInterface>
     */
    public function getPlugIns(): array
    {
        return $this->plugIns;
    }

    /**
     * Reject the letter if an identical one was submitted with the same flag
     * within the last hour (E324).
     */
    public function duplicateFailsafe(bool $enabled = true): self
    {
        $this->duplicateFailsafe = $enabled;

        return $this;
    }

    public function hasDuplicateFailsafe(): bool
    {
        return $this->duplicateFailsafe;
    }

    /**
     * The JSON object for POST /api/Letter, validated against the API's rules.
     *
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function toPayload(): array
    {
        if ($this->recipient === null) {
            throw new ValidationException('A recipient is required');
        }
        if ($this->document === null) {
            throw new ValidationException('A PDF document is required');
        }
        $this->document->assertMaxSize(Attachment::MAX_LETTER_BYTES, 'document');
        $this->coverSheet?->assertMaxSize(Attachment::MAX_COVER_SHEET_BYTES, 'cover sheet');

        if ($this->registeredMail !== null) {
            if ($this->duplex) {
                throw new ValidationException('Registered mail cannot be printed duplex (API error E312)');
            }
            if ($this->recipient->isInternational()) {
                throw new ValidationException('Registered mail is only available for German addresses (API error E311)');
            }
            foreach ($this->plugIns as $plugIn) {
                if ($plugIn instanceof PremiumAdress) {
                    throw new ValidationException('PremiumAdress is not available for registered mail');
                }
                if ($plugIn instanceof Automover) {
                    throw new ValidationException('Automover cannot reposition addresses on registered mail');
                }
            }
        }

        $payload = $this->recipient->toArray();
        $payload['fileName'] = $this->document->fileName;
        $payload['data'] = $this->document->base64();
        $payload['isColor'] = $this->color;
        $payload['isDuplex'] = $this->duplex;
        $payload['coverLetter'] = $this->generateCoverSheet;
        if ($this->coverSheet !== null) {
            $payload['coverData'] = $this->coverSheet->base64();
        }
        if ($this->registeredMail !== null) {
            $payload['registeredLetter'] = $this->registeredMail->value;
        }
        if ($this->batchId !== null) {
            $payload['batchID'] = $this->batchId;
        }
        foreach ($this->custom as $number => $value) {
            $payload['custom' . $number] = $value;
        }
        if ($this->costCenter !== null && $this->costCenter !== '') {
            $payload['costCenter'] = $this->costCenter;
        }
        if ($this->vendorSystemInformation !== null && $this->vendorSystemInformation !== '') {
            $payload['vendorSystemInformation'] = $this->vendorSystemInformation;
        }
        if ($this->sender !== null) {
            $payload = array_merge($payload, $this->sender->toArray());
        }
        if ($this->test !== null) {
            $payload = array_merge($payload, $this->test->toArray());
        }
        if ($this->plugIns !== []) {
            $payload['plugInList'] = array_map(
                static fn(PlugInInterface $plugIn): array => ['plugInName' => $plugIn->name(), 'plugInModel' => $plugIn->model()],
                $this->plugIns,
            );
        }
        if ($this->duplicateFailsafe) {
            $payload['activateDuplicateFailsafe'] = true;
        }

        return $payload;
    }
}

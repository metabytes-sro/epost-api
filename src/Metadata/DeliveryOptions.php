<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Metadata;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Print and delivery options of a letter: colour, duplex, registered mail, test mode.
 */
class DeliveryOptions implements JsonSerializable
{
    /** Einschreiben: registered letter, signature on delivery. */
    public const OPTION_REGISTERED_STANDARD = 'Einschreiben';

    /** Einwurf Einschreiben: registered letter, delivery into the mailbox is documented. */
    public const OPTION_REGISTERED_SUBMISSION_ONLY = 'Einwurf Einschreiben';

    /**
     * @deprecated since 1.1, the E-POST API (v2.6.1) no longer accepts this option and answers
     *             with error E317. Will be removed in 2.0.
     */
    public const OPTION_REGISTERED_ADDRESSEE_ONLY = 'Einschreiben eigenhändig';

    /** Einschreiben Rückschein: registered letter with return receipt to the sender. */
    public const OPTION_REGISTERED_WITH_RETURN_RECEIPT = 'Einschreiben Rückschein';

    /**
     * @deprecated since 1.1, the E-POST API (v2.6.1) no longer accepts this option and answers
     *             with error E317. Will be removed in 2.0.
     */
    public const OPTION_REGISTERED_ADDRESSEE_ONLY_WITH_RETURN_RECEIPT = 'Einschreiben eigenhändig Rückschein';

    public const OPTION_REGISTERED_NO = null;

    /** @var array<string, bool|string|null> */
    private array $options = [];

    private ?RegisteredLetterReturnAddress $returnAddress = null;

    public function setColorGrayscale(): self
    {
        return $this->setColor(false);
    }

    public function setColorColored(): self
    {
        return $this->setColor(true);
    }

    public function setColor(bool $enabled): self
    {
        $this->options['isColor'] = $enabled;

        return $this;
    }

    public function getColor(): bool
    {
        return (bool) ($this->options['isColor'] ?? false);
    }

    public function setTestFlag(bool $enabled): self
    {
        $this->options['testFlag'] = $enabled;

        return $this;
    }

    public function getTestFlag(): bool
    {
        return (bool) ($this->options['testFlag'] ?? false);
    }

    public function setTestEMail(string $emailAddress): self
    {
        $this->options['testEMail'] = $emailAddress;

        return $this;
    }

    public function getTestEMail(): string
    {
        return (string) ($this->options['testEMail'] ?? '');
    }

    /**
     * In test mode, overlay the returned PDF with the restricted-area template so
     * violations of the address window are easy to spot.
     */
    public function setTestShowRestrictedArea(bool $enabled): self
    {
        $this->options['testShowRestrictedArea'] = $enabled;

        return $this;
    }

    public function getTestShowRestrictedArea(): bool
    {
        return (bool) ($this->options['testShowRestrictedArea'] ?? false);
    }

    public function setCoverLetterIncluded(): self
    {
        return $this->setCoverLetter(true);
    }

    public function setCoverLetterGenerate(): self
    {
        return $this->setCoverLetter(false);
    }

    public function setCoverLetter(bool $enabled): self
    {
        $this->options['coverLetter'] = $enabled;

        return $this;
    }

    public function getCoverLetter(): bool
    {
        return (bool) ($this->options['coverLetter'] ?? false);
    }

    /**
     * Duplex printing. Note that the API rejects duplex for registered letters (error E312).
     */
    public function setDuplex(bool $duplex): self
    {
        $this->options['isDuplex'] = $duplex;

        return $this;
    }

    public function getDuplex(): bool
    {
        return (bool) ($this->options['isDuplex'] ?? false);
    }

    public function setRegisteredStandard(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_STANDARD);
    }

    public function setRegisteredSubmissionOnly(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_SUBMISSION_ONLY);
    }

    /**
     * @deprecated since 1.1, the E-POST API (v2.6.1) no longer offers "Einschreiben eigenhändig".
     *             Use setRegisteredStandard() instead. Will be removed in 2.0.
     */
    public function setRegisteredAddresseeOnly(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_ADDRESSEE_ONLY);
    }

    public function setRegisteredWithReturnReceipt(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_WITH_RETURN_RECEIPT);
    }

    /**
     * @deprecated since 1.1, the E-POST API (v2.6.1) no longer offers "Einschreiben eigenhändig Rückschein".
     *             Use setRegisteredWithReturnReceipt() instead. Will be removed in 2.0.
     */
    public function setRegisteredAddresseeOnlyWithReturnReceipt(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_ADDRESSEE_ONLY_WITH_RETURN_RECEIPT);
    }

    public function setRegisteredNo(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_NO);
    }

    /**
     * @throws InvalidArgumentException for a value that is not one of the OPTION_REGISTERED_* constants
     */
    public function setRegistered(?string $registered): self
    {
        if (!in_array($registered, self::getOptionsForRegistered(), true)) {
            throw new InvalidArgumentException(
                sprintf('Property %s is not supported for setRegistered()', $registered ?? 'null'),
            );
        }
        $this->options['registeredLetter'] = $registered;

        return $this;
    }

    public function getRegistered(): ?string
    {
        $registered = $this->options['registeredLetter'] ?? self::OPTION_REGISTERED_NO;

        return $registered === null ? null : (string) $registered;
    }

    /**
     * True for any registered-mail option.
     */
    public function isRegistered(): bool
    {
        return $this->getRegistered() !== null;
    }

    /**
     * All values accepted by setRegistered(), including null for "not registered".
     *
     * @return array<string|null>
     */
    public static function getOptionsForRegistered(): array
    {
        return [
            self::OPTION_REGISTERED_STANDARD,
            self::OPTION_REGISTERED_SUBMISSION_ONLY,
            self::OPTION_REGISTERED_ADDRESSEE_ONLY,
            self::OPTION_REGISTERED_WITH_RETURN_RECEIPT,
            self::OPTION_REGISTERED_ADDRESSEE_ONLY_WITH_RETURN_RECEIPT,
            self::OPTION_REGISTERED_NO,
        ];
    }

    /**
     * @deprecated since 1.1. The E-POST API reads the return address for "Einschreiben Rückschein"
     *             from the sender line in the letter's address window since October 2022 and ignores
     *             these fields (warning W220). Will be removed in 2.0.
     */
    public function setRegisteredLetterReturnAddress(RegisteredLetterReturnAddress $address): self
    {
        $this->returnAddress = $address;

        return $this;
    }

    /**
     * @deprecated since 1.1, see setRegisteredLetterReturnAddress(). Will be removed in 2.0.
     */
    public function getRegisteredLetterReturnAddress(): ?RegisteredLetterReturnAddress
    {
        return $this->returnAddress;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        if ($this->returnAddress !== null) {
            return array_merge($this->options, $this->returnAddress->getData());
        }

        return $this->options;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->getData();
    }
}

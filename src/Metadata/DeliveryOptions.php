<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Metadata;

use InvalidArgumentException;
use JsonSerializable;
use MetabytesSRO\EPost\Api\Exception\MissingReturnAddressException;

class DeliveryOptions implements JsonSerializable
{
    public const OPTION_REGISTERED_STANDARD = 'Einschreiben';
    public const OPTION_REGISTERED_SUBMISSION_ONLY = 'Einwurf Einschreiben';
    public const OPTION_REGISTERED_ADDRESSEE_ONLY = 'Einschreiben eigenhändig';
    public const OPTION_REGISTERED_WITH_RETURN_RECEIPT = 'Einschreiben Rückschein';
    public const OPTION_REGISTERED_ADDRESSEE_ONLY_WITH_RETURN_RECEIPT = 'Einschreiben eigenhändig Rückschein';
    public const OPTION_REGISTERED_NO = null;

    private const OPTIONS_REQUIRING_RETURN_ADDRESS = [
        self::OPTION_REGISTERED_WITH_RETURN_RECEIPT,
        self::OPTION_REGISTERED_ADDRESSEE_ONLY_WITH_RETURN_RECEIPT,
    ];

    /** @var array<string, mixed> */
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
        return $this->options['isColor'] ?? false;
    }

    public function setTestFlag(bool $enabled): self
    {
        $this->options['testFlag'] = $enabled;
        return $this;
    }

    public function getTestFlag(): bool
    {
        return $this->options['testFlag'] ?? false;
    }

    public function setTestEMail(string $emailAddress): self
    {
        $this->options['testEMail'] = $emailAddress;
        return $this;
    }

    public function getTestEMail(): string
    {
        return $this->options['testEMail'] ?? '';
    }

    public function setTestShowRestrictedArea(bool $enabled): self
    {
        $this->options['testShowRestrictedArea'] = $enabled;
        return $this;
    }

    public function getTestShowRestrictedArea(): bool
    {
        return $this->options['testShowRestrictedArea'] ?? false;
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
        return $this->options['coverLetter'] ?? false;
    }

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

    public function setRegisteredAddresseeOnly(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_ADDRESSEE_ONLY);
    }

    public function setRegisteredWithReturnReceipt(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_WITH_RETURN_RECEIPT);
    }

    public function setRegisteredAddresseeOnlyWithReturnReceipt(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_ADDRESSEE_ONLY_WITH_RETURN_RECEIPT);
    }

    public function setRegisteredNo(): self
    {
        return $this->setRegistered(self::OPTION_REGISTERED_NO);
    }

    public function setRegistered(?string $registered): self
    {
        if (!in_array($registered, self::getOptionsForRegistered(), true)) {
            throw new InvalidArgumentException(
                sprintf('Property %s is not supported for setRegistered()', $registered ?? 'null')
            );
        }
        $this->options['registeredLetter'] = $registered;
        return $this;
    }

    public function getRegistered(): ?string
    {
        return $this->options['registeredLetter'] ?? self::OPTION_REGISTERED_NO;
    }

    /**
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

    public function setRegisteredLetterReturnAddress(RegisteredLetterReturnAddress $address): self
    {
        $this->returnAddress = $address;
        return $this;
    }

    public function getRegisteredLetterReturnAddress(): ?RegisteredLetterReturnAddress
    {
        return $this->returnAddress;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $registered = $this->options['registeredLetter'] ?? null;
        if (in_array($registered, self::OPTIONS_REQUIRING_RETURN_ADDRESS, true)) {
            if ($this->returnAddress === null) {
                throw new MissingReturnAddressException(
                    'RegisteredLetterReturnAddress is required when using Einschreiben Rückschein. '
                    . 'Call setRegisteredLetterReturnAddress() with the address where the return receipt should be sent.'
                );
            }
            return array_merge($this->options, $this->returnAddress->getData());
        }
        return $this->options;
    }

    public function jsonSerialize(): array
    {
        return $this->getData();
    }
}

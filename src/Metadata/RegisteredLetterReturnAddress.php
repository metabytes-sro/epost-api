<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Metadata;

use InvalidArgumentException;
use JsonSerializable;
use MetabytesSRO\EPost\Api\Exception\InvalidRecipientDataException;

/**
 * Return address for Einschreiben Rückschein (registered letter with return receipt).
 * Required when using DeliveryOptions::setRegisteredWithReturnReceipt() or
 * DeliveryOptions::setRegisteredAddresseeOnlyWithReturnReceipt().
 */
class RegisteredLetterReturnAddress implements JsonSerializable
{
    private const VALIDATION_LENGTHS = [
        'registeredLetterAdressLine1' => 80,
        'registeredLetterAdressLine2' => 80,
        'registeredLetterAdressLine3' => 80,
        'registeredLetterZipCode' => 20,
        'registeredLetterCity' => 80,
    ];

    /** @var array<string, string|null> */
    private array $fields = [];

    public function setAddressLine1(string $line): self
    {
        $this->validateLength('registeredLetterAdressLine1', $line);
        $this->fields['registeredLetterAdressLine1'] = $line;
        return $this;
    }

    public function getAddressLine1(): ?string
    {
        return $this->fields['registeredLetterAdressLine1'] ?? null;
    }

    public function setAddressLine2(?string $line): self
    {
        if ($line !== null) {
            $this->validateLength('registeredLetterAdressLine2', $line);
        }
        $this->fields['registeredLetterAdressLine2'] = $line;
        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->fields['registeredLetterAdressLine2'] ?? null;
    }

    public function setAddressLine3(?string $line): self
    {
        if ($line !== null) {
            $this->validateLength('registeredLetterAdressLine3', $line);
        }
        $this->fields['registeredLetterAdressLine3'] = $line;
        return $this;
    }

    public function getAddressLine3(): ?string
    {
        return $this->fields['registeredLetterAdressLine3'] ?? null;
    }

    public function setZipCode(string $zipCode): self
    {
        $this->validateLength('registeredLetterZipCode', $zipCode);
        if (strlen($zipCode) < 5) {
            throw new InvalidArgumentException('registeredLetterZipCode must be at least 5 characters');
        }
        $this->fields['registeredLetterZipCode'] = $zipCode;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->fields['registeredLetterZipCode'] ?? null;
    }

    public function setCity(string $city): self
    {
        $this->validateLength('registeredLetterCity', $city);
        $this->fields['registeredLetterCity'] = $city;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->fields['registeredLetterCity'] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getData(): array
    {
        if (empty($this->fields['registeredLetterAdressLine1'])
            || empty($this->fields['registeredLetterZipCode'])
            || empty($this->fields['registeredLetterCity'])
        ) {
            throw new InvalidRecipientDataException(
                'RegisteredLetterReturnAddress requires addressLine1, zipCode and city'
            );
        }
        return array_filter($this->fields, fn ($v) => $v !== null && $v !== '');
    }

    public function jsonSerialize(): array
    {
        return $this->getData();
    }

    private function validateLength(string $key, string $value): void
    {
        $max = self::VALIDATION_LENGTHS[$key] ?? 80;
        if (strlen($value) > $max) {
            throw new InvalidArgumentException(
                sprintf('Value of "%s" exceeds maximum length of %u', $key, $max)
            );
        }
    }
}

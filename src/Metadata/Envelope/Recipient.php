<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Metadata\Envelope;

use InvalidArgumentException;
use JsonSerializable;
use MetabytesSRO\EPost\Api\Exception\InvalidRecipientDataException;

class Recipient implements JsonSerializable
{
    private const MAX_LENGTHS = [
        'addressLine1' => 80,
        'addressLine2' => 80,
        'addressLine3' => 80,
        'addressLine4' => 80,
        'addressLine5' => 80,
        'zipCode' => 20,
        'city' => 80,
        'country' => 80,
    ];

    /** @var array<string, string> */
    private array $fields = [];

    public function setAddressLine(string $value, int $lineIndex): self
    {
        if ($lineIndex < 0 || $lineIndex >= 5) {
            throw new InvalidRecipientDataException('Address line index must be between 0 and 4');
        }
        $key = 'addressLine' . ($lineIndex + 1);
        $this->validateLength($key, $value);
        $this->fields[$key] = $value;
        return $this;
    }

    public function getAddressLine(int $lineIndex): ?string
    {
        $key = 'addressLine' . ($lineIndex + 1);
        return $this->fields[$key] ?? null;
    }

    public function setZipCode(string $zipCode): self
    {
        $this->validateLength('zipCode', $zipCode);
        $this->fields['zipCode'] = $zipCode;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->fields['zipCode'] ?? null;
    }

    public function setCity(string $city): self
    {
        $this->validateLength('city', $city);
        $this->fields['city'] = $city;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->fields['city'] ?? null;
    }

    public function setCountry(string $country): self
    {
        $this->validateLength('country', $country);
        $this->fields['country'] = $country;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->fields['country'] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getData(): array
    {
        return $this->fields;
    }

    public function jsonSerialize(): array
    {
        if ($this->getAddressLine(0) === null || $this->getCity() === null || $this->getZipCode() === null) {
            throw new InvalidRecipientDataException(
                'An address line 1, city and zip code must be set at least'
            );
        }
        return $this->getData();
    }

    private function validateLength(string $key, string $value): void
    {
        $max = self::MAX_LENGTHS[$key] ?? 80;
        if (strlen($value) > $max) {
            throw new InvalidArgumentException(
                sprintf('Value of "%s" exceeds maximum length of %u', $key, $max)
            );
        }
    }
}

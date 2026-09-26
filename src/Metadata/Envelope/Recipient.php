<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Metadata\Envelope;

use InvalidArgumentException;
use JsonSerializable;
use MetabytesSRO\EPost\Api\Exception\InvalidRecipientDataException;

/**
 * Recipient address of a letter.
 *
 * Address line 1 (name or company), zip code and city are mandatory. Address line 5
 * may only be used for German addresses. For international letters set the country
 * to the German ISO 3166-1 name in capitals, e.g. "ÖSTERREICH", and use three
 * spaces as zip code when the destination has no postal codes.
 */
class Recipient implements JsonSerializable
{
    public const MAX_ADDRESS_LINES = 5;

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

    /**
     * @param int $lineIndex Zero-based: 0 is addressLine1 (name or company), 4 is addressLine5
     *
     * @throws InvalidRecipientDataException for an index outside 0 to 4
     * @throws InvalidArgumentException when the value is longer than 80 characters
     */
    public function setAddressLine(string $value, int $lineIndex): self
    {
        if ($lineIndex < 0 || $lineIndex >= self::MAX_ADDRESS_LINES) {
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

    /**
     * Country name in German capitals as per ISO 3166-1, e.g. "ITALIEN". Leave unset for Germany.
     */
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

    /**
     * @throws InvalidRecipientDataException when address line 1, zip code or city is missing
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        if ($this->getAddressLine(0) === null || $this->getCity() === null || $this->getZipCode() === null) {
            throw new InvalidRecipientDataException(
                'An address line 1, city and zip code must be set at least',
            );
        }

        return $this->getData();
    }

    private function validateLength(string $key, string $value): void
    {
        $max = self::MAX_LENGTHS[$key] ?? 80;
        if (mb_strlen($value) > $max) {
            throw new InvalidArgumentException(
                sprintf('Value of "%s" exceeds maximum length of %u', $key, $max),
            );
        }
    }
}

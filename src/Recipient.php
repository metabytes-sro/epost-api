<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use MetabytesSRO\EPost\Api\Exception\ValidationException;

/**
 * Recipient address of a letter.
 *
 * Address line 1 (name or company), zip code and city are mandatory. Address
 * line 5 may only be used for German addresses. For international letters give
 * the country as its German name in capitals according to ISO 3166-1, for
 * example "ÖSTERREICH" or "ITALIEN", and use Recipient::NO_ZIP_CODE as zip code
 * when the destination country has no postal codes.
 */
final readonly class Recipient
{
    /** Zip code value the API expects for countries without postal codes: three spaces. */
    public const string NO_ZIP_CODE = '   ';

    public const int MAX_LINE_LENGTH = 80;
    public const int MAX_ZIP_CODE_LENGTH = 20;

    /**
     * @throws ValidationException when a mandatory field is empty or a field is too long
     */
    public function __construct(
        public string $addressLine1,
        public string $zipCode,
        public string $city,
        public ?string $addressLine2 = null,
        public ?string $addressLine3 = null,
        public ?string $addressLine4 = null,
        public ?string $addressLine5 = null,
        public ?string $country = null,
    ) {
        Validate::notBlank('addressLine1', $addressLine1);
        if ($zipCode !== self::NO_ZIP_CODE) {
            Validate::notBlank('zipCode', $zipCode);
        }
        Validate::notBlank('city', $city);
        Validate::maxLength('addressLine1', $addressLine1, self::MAX_LINE_LENGTH);
        Validate::maxLength('addressLine2', $addressLine2, self::MAX_LINE_LENGTH);
        Validate::maxLength('addressLine3', $addressLine3, self::MAX_LINE_LENGTH);
        Validate::maxLength('addressLine4', $addressLine4, self::MAX_LINE_LENGTH);
        Validate::maxLength('addressLine5', $addressLine5, self::MAX_LINE_LENGTH);
        Validate::maxLength('zipCode', $zipCode, self::MAX_ZIP_CODE_LENGTH);
        Validate::maxLength('city', $city, self::MAX_LINE_LENGTH);
        Validate::maxLength('country', $country, self::MAX_LINE_LENGTH);
        if ($country !== null && $country !== '' && $addressLine5 !== null && $addressLine5 !== '') {
            throw new ValidationException('addressLine5 may only be used for German addresses');
        }
    }

    /**
     * Placeholder recipient for the Automover plugin when the address is only
     * present as text on the document and the API should find and reposition
     * it. The API definition prescribes these marker values.
     */
    public static function forAutomover(): self
    {
        return new self('AUTOMOVER', '99999', 'AUTOMOVER');
    }

    /**
     * True when a country other than Germany is set.
     */
    public function isInternational(): bool
    {
        return $this->country !== null && trim($this->country) !== ''
            && !in_array(mb_strtoupper(trim($this->country)), ['DEUTSCHLAND', 'DE', 'GERMANY'], true);
    }

    /**
     * The address fields as the API expects them, without empty optional lines.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $fields = [
            'addressLine1' => $this->addressLine1,
            'addressLine2' => $this->addressLine2,
            'addressLine3' => $this->addressLine3,
            'addressLine4' => $this->addressLine4,
            'addressLine5' => $this->addressLine5,
            'zipCode' => $this->zipCode,
            'city' => $this->city,
            'country' => $this->country,
        ];

        return array_filter($fields, static fn(?string $value): bool => $value !== null && $value !== '');
    }
}

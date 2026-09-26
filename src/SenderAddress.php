<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use MetabytesSRO\EPost\Api\Exception\ValidationException;

/**
 * Sender address printed by the API when it generates a cover sheet or
 * repositions the address with the Automover plugin. Either the four separate
 * fields or one complete line.
 */
final readonly class SenderAddress
{
    public const int MAX_FIELD_LENGTH = 80;
    public const int MAX_COMPLETE_LINE_LENGTH = 200;

    private function __construct(
        public ?string $line1,
        public ?string $street,
        public ?string $zipCode,
        public ?string $city,
        public ?string $completeLine,
    ) {}

    /**
     * @param string $line1 Name or company, e.g. "Fa. Huber GmbH"
     *
     * @throws ValidationException
     */
    public static function fromFields(string $line1, string $street, string $zipCode, string $city): self
    {
        Validate::notBlank('line1', $line1);
        Validate::notBlank('street', $street);
        Validate::notBlank('zipCode', $zipCode);
        Validate::notBlank('city', $city);
        Validate::maxLength('line1', $line1, self::MAX_FIELD_LENGTH);
        Validate::maxLength('street', $street, self::MAX_FIELD_LENGTH);
        Validate::maxLength('zipCode', $zipCode, self::MAX_FIELD_LENGTH);
        Validate::maxLength('city', $city, self::MAX_FIELD_LENGTH);

        return new self($line1, $street, $zipCode, $city, null);
    }

    /**
     * The whole sender address in one line, e.g. "Fa. Huber GmbH, Am Weg 1, 76887 Bad Bergzabern".
     *
     * @throws ValidationException
     */
    public static function fromCompleteLine(string $line): self
    {
        Validate::notBlank('completeLine', $line);
        Validate::maxLength('completeLine', $line, self::MAX_COMPLETE_LINE_LENGTH);

        return new self(null, null, null, null, $line);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        if ($this->completeLine !== null) {
            return ['senderAdressLineComplete' => $this->completeLine];
        }

        return [
            'senderAdressLine1' => (string) $this->line1,
            'senderStreet' => (string) $this->street,
            'senderZipCode' => (string) $this->zipCode,
            'senderCity' => (string) $this->city,
        ];
    }
}

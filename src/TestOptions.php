<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use MetabytesSRO\EPost\Api\Exception\ValidationException;

/**
 * Test mode: the API processes the letter, emails the resulting PDF to the given
 * address and does not print or post anything. The result can also be fetched
 * with EPostClient::getTestResult().
 */
final readonly class TestOptions
{
    public const int MAX_EMAIL_LENGTH = 100;

    /**
     * @param string $email Address that receives the processed PDF
     * @param bool $showRestrictedArea Overlay the PDF with the address-window template to spot violations
     *
     * @throws ValidationException
     */
    public function __construct(
        public string $email,
        public bool $showRestrictedArea = false,
    ) {
        Validate::notBlank('email', $email);
        Validate::maxLength('email', $email, self::MAX_EMAIL_LENGTH);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException(sprintf('"%s" is not a valid email address', $email));
        }
    }

    /**
     * @return array<string, bool|string>
     */
    public function toArray(): array
    {
        return [
            'testFlag' => true,
            'testEMail' => $this->email,
            'testShowRestrictedArea' => $this->showRestrictedArea,
        ];
    }
}

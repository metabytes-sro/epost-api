<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\PlugIn;

/**
 * PremiumAdress plugin: address correction feedback from Deutsche Post for
 * national letters. Not available for registered mail. The feedback is
 * delivered through EPostClient::getPremiumAdressFeedback() and
 * LetterStatus::$plugInFeedback.
 */
final readonly class PremiumAdress implements PlugInInterface
{
    public const string NAME = 'PremiumAdress';

    public function __construct(
        public PremiumAdressVariant $variant = PremiumAdressVariant::Basic,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @return array<string, mixed>
     */
    public function model(): array
    {
        return ['productVariants' => $this->variant->value];
    }
}

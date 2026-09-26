<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Metadata;

use JsonSerializable;
use MetabytesSRO\EPost\Api\Metadata\Envelope\Recipient;

/**
 * The envelope of a letter: currently only the recipient address.
 */
class Envelope implements JsonSerializable
{
    private ?Recipient $recipient = null;

    public function setRecipient(Recipient $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getRecipient(): ?Recipient
    {
        return $this->recipient;
    }

    /**
     * Recipient fields, or null when no recipient was set.
     *
     * @return array<string, string>|null
     */
    public function getData(): ?array
    {
        return $this->recipient?->getData();
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->recipient?->getData() ?? [];
    }
}

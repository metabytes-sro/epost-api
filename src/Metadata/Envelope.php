<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Metadata;

use JsonSerializable;
use MetabytesSRO\EPost\Api\Metadata\Envelope\Recipient;

class Envelope implements JsonSerializable
{
    private ?Recipient $recipient = null;

    public function setRecipient(Recipient $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * @return null|array<string, mixed>
     */
    public function getData(): ?array
    {
        return $this->recipient?->getData();
    }

    public function jsonSerialize(): array
    {
        return $this->recipient?->getData() ?? [];
    }
}

<?php

declare(strict_types = 1);

namespace MetabytesSRO\EPost\Api\Exception;

use LogicException;
use MetabytesSRO\EPost\Api\Error;
use Throwable;

class ErrorException extends LogicException
{
    public function __construct(
        private readonly Error $error,
        ?Throwable             $previous = null,
    ) {
        parent::__construct($error->getDescription(), 0, $previous);
        $this->code = $error->getCode();
    }

    public function getError(): Error
    {
        return $this->error;
    }

    public function getLevel(): string
    {
        return $this->error->getLevel();
    }

    public function __toString(): string
    {
        return self::class . ": [{$this->error->getLevel()}] [{$this->error->getCode()}]: {$this->message}\n";
    }
}

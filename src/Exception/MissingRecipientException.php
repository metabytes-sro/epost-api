<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

/**
 * Thrown when the Envelope of a letter has no Recipient.
 */
class MissingRecipientException extends MissingPreconditionException {}

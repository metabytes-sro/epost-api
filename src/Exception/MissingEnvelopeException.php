<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

/**
 * Thrown when a letter is built without an Envelope.
 */
class MissingEnvelopeException extends MissingPreconditionException {}

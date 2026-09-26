<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use LogicException;

/**
 * Thrown when recipient or return address data is incomplete or invalid.
 */
class InvalidRecipientDataException extends LogicException implements EPostException {}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use InvalidArgumentException;

/**
 * Thrown before a request is sent when the input would be rejected by the API:
 * a missing recipient, a file that is not a PDF, a field over its maximum
 * length, or a combination of options the API does not accept.
 */
class ValidationException extends InvalidArgumentException implements EPostException {}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use Throwable;

/**
 * Marker interface implemented by every exception this package throws.
 *
 * Catch it to handle any failure raised by the client, whether it comes from
 * the E-POST API (ErrorException) or from local validation.
 */
interface EPostException extends Throwable {}

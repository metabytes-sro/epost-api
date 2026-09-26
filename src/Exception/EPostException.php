<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use Throwable;

/**
 * Marker interface implemented by every exception this package throws.
 *
 * Catch it to handle any failure in one place, whether the E-POST API rejected
 * a request (ApiException), the request never reached the API
 * (TransportException) or the input was invalid before sending
 * (ValidationException).
 */
interface EPostException extends Throwable {}

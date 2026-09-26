<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

/**
 * Thrown when an API call is made without an AccessToken.
 */
class MissingAuthorizationTokenException extends MissingPreconditionException {}

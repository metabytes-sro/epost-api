<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

/**
 * The API rejected the credentials (E001, E002) or the bearer token is expired
 * or invalid (E101, HTTP 401). Obtain a new token and retry.
 */
class AuthenticationException extends ApiException {}

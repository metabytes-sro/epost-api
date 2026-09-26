<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use LogicException;

/**
 * Base class for "something required was not set before calling this" errors.
 */
class MissingPreconditionException extends LogicException implements EPostException {}

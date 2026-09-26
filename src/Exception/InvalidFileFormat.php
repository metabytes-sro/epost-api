<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use LogicException;

/**
 * @deprecated since 1.1, catch InvalidFileFormatException instead. This class will be removed in 2.0.
 */
class InvalidFileFormat extends LogicException implements EPostException {}

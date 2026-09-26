<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

/**
 * Thrown when an attachment or cover letter is not a PDF file or cannot be read.
 */
class InvalidFileFormatException extends InvalidFileFormat {}

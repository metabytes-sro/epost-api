<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

/**
 * Thrown when a letter is built without an attachment, or the attachment file does not exist.
 */
class MissingAttachmentException extends MissingPreconditionException {}

<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Error item from LetterStatus::getErrors() (errorList).
 *
 * The API uses the same Error schema for error responses and for the error list
 * of a letter status, so this class only exists for backwards compatibility and
 * adds nothing to Error.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json Error schema
 */
class LetterStatusError extends Error {}

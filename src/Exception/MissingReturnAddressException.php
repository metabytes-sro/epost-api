<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Exception;

use LogicException;

/**
 * @deprecated since 1.1, no longer thrown. The E-POST API reads the return address for
 *             "Einschreiben Rückschein" from the letter's address window since October 2022
 *             and ignores explicit values. This class will be removed in 2.0.
 */
class MissingReturnAddressException extends LogicException implements EPostException {}

<?php

/**
 * Availability of the E-POSTBUSINESS API. Needs no credentials.
 *
 * Usage: php examples/health-check.php
 */

declare(strict_types=1);

use MetabytesSRO\EPost\Api\Login;

require_once __DIR__ . '/bootstrap.php';

run(static function (): void {
    $status = (new Login())->healthCheck();

    printf("%s %s: %s\n", $status->level, $status->code, $status->description);
    if ($status->date !== null) {
        printf("Reported at %s\n", $status->date->format(DATE_ATOM));
    }

    // I501 = OK, W501 = maintenance announced, E501 = inactive
    exit($status->isError() ? 1 : 0);
});

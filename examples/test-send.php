<?php

/**
 * Send a letter in test mode, wait for the API to process it and save the
 * PDF the API produced. Nothing is printed or posted.
 *
 * Usage: php examples/test-send.php path/to/letter.pdf you@example.com
 */

declare(strict_types=1);

use MetabytesSRO\EPost\Api\Attachment;
use MetabytesSRO\EPost\Api\Exception\RateLimitException;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Recipient;
use MetabytesSRO\EPost\Api\TestOptions;

require_once __DIR__ . '/bootstrap.php';

$path = $argv[1] ?? null;
$email = $argv[2] ?? getenv('EPOST_TEST_EMAIL');
if ($path === null || !is_string($email) || $email === '') {
    fwrite(STDERR, "Usage: php examples/test-send.php path/to/letter.pdf you@example.com\n");
    exit(2);
}

run(static function () use ($path, $email): void {
    $client = client();

    $letter = new Letter(
        new Recipient('Max Mustermann', '53115', 'Bonn', 'Musterstraße 1'),
        Attachment::fromFile($path),
    );
    // showRestrictedArea overlays the address-window template on the returned PDF.
    $letter->test(new TestOptions($email, showRestrictedArea: true));

    $letterId = $client->sendLetter($letter)->letterId;
    printf('Submitted as letter %d, waiting for processing', $letterId);

    // Status queries are limited to one per 5 seconds.
    for ($attempt = 0; $attempt < 24; ++$attempt) {
        sleep(RateLimitException::MIN_INTERVAL_SECONDS);
        $status = $client->getLetterStatus($letterId);
        echo '.';
        if (!$status->isOpen() || $status->status()?->value === 2) {
            break;
        }
    }
    echo "\n";

    $status = $client->getLetterStatus($letterId);
    printf("Status %d: %s\n", $status->statusId, $status->statusDetails ?? $status->status()?->label() ?? '');
    foreach ($status->errors as $error) {
        printf("  %s %s: %s\n", $error->level, $error->code, $error->description);
    }
    if ($status->hasError()) {
        exit(1);
    }

    $pdf = $client->getTestResult($letterId)->pdf();
    if ($pdf === null) {
        echo "The API has not produced the result PDF yet; run track-letter.php later.\n";

        return;
    }
    $out = sprintf('%s/epost-test-%d.pdf', sys_get_temp_dir(), $letterId);
    file_put_contents($out, $pdf);
    echo "Processed PDF saved to {$out}\n";
});

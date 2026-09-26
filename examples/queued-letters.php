<?php

/**
 * Schedule a letter with the UploadManagement plugin, look at the open
 * letters, then cancel it again while it is still queued.
 *
 * Usage: php examples/queued-letters.php path/to/letter.pdf
 */

declare(strict_types=1);

use MetabytesSRO\EPost\Api\Attachment;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\PlugIn\UploadManagement;
use MetabytesSRO\EPost\Api\Recipient;

require_once __DIR__ . '/bootstrap.php';

$path = $argv[1] ?? null;
if ($path === null) {
    fwrite(STDERR, "Usage: php examples/queued-letters.php path/to/letter.pdf\n");
    exit(2);
}

run(static function () use ($path): void {
    $client = client();

    $letter = (new Letter(new Recipient('Max Mustermann', '53115', 'Bonn', 'Musterstraße 1'), Attachment::fromFile($path)))
        ->plugIn(UploadManagement::dueInDays(7));

    // Letters in test mode are not queued by the API, so this example always submits live.
    $letterId = $client->sendLetter($letter)->letterId;
    printf("Letter %d is queued for sending in 7 days.\n", $letterId);

    $open = $client->getOpenLetters();
    printf("%d open letter(s) in total.\n", count($open));

    foreach ($client->cancelQueued([$letterId]) as $result) {
        printf("Cancel %d: %s (%s)\n", $result->letterId ?? $letterId, $result->message, $result->successful ? 'ok' : 'failed');
    }
});

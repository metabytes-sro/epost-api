<?php

/**
 * Send a PDF as a letter.
 *
 * Usage: php examples/send-letter.php path/to/letter.pdf [--registered]
 *
 * The recipient below is a placeholder; replace it with a real address. The
 * PDF must be PDF/A-1b in DIN A4 portrait with the recipient address in the
 * address window, see the API documentation for the template.
 */

declare(strict_types=1);

use MetabytesSRO\EPost\Api\Attachment;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Recipient;
use MetabytesSRO\EPost\Api\RegisteredMailType;
use MetabytesSRO\EPost\Api\SenderAddress;

require_once __DIR__ . '/bootstrap.php';

$path = $argv[1] ?? null;
if ($path === null) {
    fwrite(STDERR, "Usage: php examples/send-letter.php path/to/letter.pdf [--registered]\n");
    exit(2);
}
$registered = in_array('--registered', $argv, true);

run(static function () use ($path, $registered): void {
    $letter = (new Letter())
        ->recipient(new Recipient('Max Mustermann', '53115', 'Bonn', 'Musterstraße 1'))
        ->document(Attachment::fromFile($path))
        ->sender(SenderAddress::fromFields('Beispiel GmbH', 'Beispielweg 1', '10115', 'Berlin'))
        ->custom(1, 'EXAMPLE-' . date('Ymd-His'))
        ->duplicateFailsafe();

    if ($registered) {
        // Registered mail cannot be duplex and needs a German address.
        $letter->registeredMail(RegisteredMailType::Standard);
    } else {
        $letter->duplex();
    }

    $result = client()->sendLetter(applyTestMode($letter));

    printf("Accepted as letter %d (%s)\n", $result->letterId, $result->fileName ?? basename($path));
    echo "Track it with: php examples/track-letter.php {$result->letterId}\n";
});

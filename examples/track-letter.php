<?php

/**
 * Status of a letter: processing phase, errors and warnings, and the tracking
 * status of registered mail.
 *
 * Usage: php examples/track-letter.php 43556780
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$letterId = isset($argv[1]) && ctype_digit($argv[1]) ? (int) $argv[1] : null;
if ($letterId === null) {
    fwrite(STDERR, "Usage: php examples/track-letter.php <letterID>\n");
    exit(2);
}

run(static function () use ($letterId): void {
    $status = client()->getLetterStatus($letterId);

    printf("Letter %d: %s\n", $status->letterId, $status->fileName ?? '');
    printf("Status %d (%s)%s\n", $status->statusId, $status->status()?->label() ?? 'unknown', $status->isSent() ? ', sent' : '');
    foreach ([
        'Accepted' => $status->createdDate,
        'Processed' => $status->processedDate,
        'To print centre' => $status->printUploadDate,
        'Print feedback' => $status->printFeedbackDate,
    ] as $label => $date) {
        if ($date !== null) {
            printf("  %-16s %s\n", $label, $date->format('Y-m-d H:i'));
        }
    }

    if ($status->isRegisteredMail()) {
        printf("Registered mail: %s, tracking number %s\n", $status->registeredLetter, $status->registeredLetterId ?? 'not yet assigned');
        $tracking = $status->trackingStatus();
        if ($tracking !== null) {
            printf("  %s: %s%s\n", $tracking->value, $tracking->description(), $tracking->isFinal() ? ' (final)' : '');
        }
    }

    foreach ($status->errors as $error) {
        printf("%-7s %s: %s\n", $error->level, $error->code, $error->description);
    }
    foreach ($status->plugInFeedback as $feedback) {
        printf("Plugin %s: %s\n", $feedback->name, json_encode($feedback->model, JSON_UNESCAPED_UNICODE));
    }
});

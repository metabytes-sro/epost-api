<?php

/**
 * Fails when line coverage in a Clover report is below the given threshold.
 *
 * Usage: php check-coverage.php build/clover.xml 100
 */

declare(strict_types=1);

[$script, $cloverFile, $threshold] = $argv + [null, null, '100'];

if ($cloverFile === null || !is_file($cloverFile)) {
    fwrite(STDERR, "Clover file not found: {$cloverFile}\n");
    exit(2);
}

$xml = simplexml_load_file($cloverFile);
if ($xml === false) {
    fwrite(STDERR, "Could not parse {$cloverFile}\n");
    exit(2);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$percent = $statements === 0 ? 100.0 : $covered / $statements * 100;

printf("Line coverage: %.2f%% (%d/%d statements)\n", $percent, $covered, $statements);

if ($percent + 1e-9 < (float) $threshold) {
    $uncovered = [];
    foreach ($xml->xpath('//file') as $file) {
        foreach ($file->line as $line) {
            if ((string) $line['type'] === 'stmt' && (int) $line['count'] === 0) {
                $uncovered[] = sprintf('%s:%d', $file['name'], $line['num']);
            }
        }
    }
    fwrite(STDERR, sprintf("Coverage is below %s%%. Uncovered statements:\n  %s\n", $threshold, implode("\n  ", $uncovered)));
    exit(1);
}

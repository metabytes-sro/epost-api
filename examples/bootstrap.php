<?php

/**
 * Shared setup for the example scripts: autoloading, environment and client.
 *
 * Returns the configured EPostClient. Set EPOST_TEST_EMAIL to run every example
 * in test mode, in which case the API emails the processed PDF instead of
 * printing it.
 */

declare(strict_types=1);

use MetabytesSRO\EPost\Api\Auth\Credentials;
use MetabytesSRO\EPost\Api\EPostClient;
use MetabytesSRO\EPost\Api\Exception\EPostException;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\TestOptions;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Read a required environment variable or stop with a hint.
 */
function env(string $name, ?string $default = null): string
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        if ($default !== null) {
            return $default;
        }
        fwrite(STDERR, "Environment variable {$name} is not set. See examples/README.md.\n");
        exit(2);
    }

    return $value;
}

/**
 * The client for the examples, logging in with the credentials from the environment.
 */
function client(): EPostClient
{
    return EPostClient::withCredentials(new Credentials(
        env('EPOST_VENDOR_ID'),
        env('EPOST_EKP'),
        env('EPOST_SECRET'),
        env('EPOST_PASSWORD'),
        env('EPOST_VENDOR_SUB_ID', '') ?: null,
    ));
}

/**
 * Put the letter into test mode when EPOST_TEST_EMAIL is set.
 */
function applyTestMode(Letter $letter): Letter
{
    $email = getenv('EPOST_TEST_EMAIL');
    if (is_string($email) && $email !== '') {
        echo "Test mode: the API will email the result to {$email} and print nothing.\n";
        $letter->test(new TestOptions($email));
    }

    return $letter;
}

/**
 * Run an example and report package exceptions in a readable way.
 *
 * @param callable(): void $example
 */
function run(callable $example): void
{
    try {
        $example();
    } catch (EPostException $e) {
        fwrite(STDERR, sprintf("%s: %s\n", (new ReflectionClass($e))->getShortName(), $e->getMessage()));
        exit(1);
    }
}

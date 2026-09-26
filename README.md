# E-POSTBUSINESS API client for PHP

[![CI](https://github.com/metabytes-sro/epost-api/actions/workflows/ci.yml/badge.svg)](https://github.com/metabytes-sro/epost-api/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/metabytes-sro/epost-api.svg)](https://packagist.org/packages/metabytes-sro/epost-api)
[![PHP Version](https://img.shields.io/packagist/dependency-v/metabytes-sro/epost-api/php.svg)](https://packagist.org/packages/metabytes-sro/epost-api)
[![License](https://img.shields.io/packagist/l/metabytes-sro/epost-api.svg)](LICENSE)

Send PDF documents as physical letters through the Deutsche Post
[E-POSTBUSINESS API](https://api.epost.docuguide.com/swagger/index.html), track
their delivery, manage queued letters and estimate postage.

- Typed request builders and response objects for every `/api/Letter` and `/api/Login` endpoint
- One exception hierarchy: every API error becomes an `ErrorException` with the E-POST error code
- Tracking status codes for registered mail (Einschreiben) with German descriptions
- Local price calculator based on the official Deutsche Post price lists
- Tested against a mocked API on PHP 8.1 to 8.5, with 100% line coverage enforced in CI

## Installation

```bash
composer require metabytes-sro/epost-api
```

Requires PHP 8.1 or newer, the `fileinfo` extension and Guzzle 7.

## Quick start

```php
use MetabytesSRO\EPost\Api\AccessToken;
use MetabytesSRO\EPost\Api\Exception\ErrorException;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Metadata\Envelope;
use MetabytesSRO\EPost\Api\Metadata\Envelope\Recipient;

$token = new AccessToken($vendorId, $ekp, $secret, $password);

$recipient = (new Recipient())
    ->setAddressLine('Max Mustermann AG', 0)   // addressLine1: name or company
    ->setAddressLine('Musterstrasse 99', 1)    // addressLine2: street
    ->setZipCode('12345')
    ->setCity('Bonn');

$letter = (new Letter())
    ->setAccessToken($token)
    ->setEnvelope((new Envelope())->setRecipient($recipient))
    ->setAttachment('/path/to/document.pdf');

try {
    $letter->send();
    $letterId = $letter->getLetterId();
} catch (ErrorException $e) {
    // $e->getCode() is the E-POST error code, e.g. "E301" (no PDF detected)
    // $e->getMessage() is the description, $e->getError() the full Error object
}
```

The attachment must be a PDF/A-1b document in DIN A4 portrait format with the
recipient address positioned in the address window. See the
[API documentation](https://api.epost.docuguide.com/swagger/index.html) for the
address template.

## Authentication

`AccessToken` logs in lazily on first use and caches the JSON Web Token, which
is valid for 24 hours by default.

```php
$token = new AccessToken($vendorId, $ekp, $secret, $password);

// Reuse a token obtained elsewhere (OAuth2 provider, cache, previous request):
$token = AccessToken::fromToken($jwt);

// Log in again after the API reported E101 (token expired):
$token->refresh();
```

The `Login` class exposes the underlying endpoints, including the first-time
setup flow and the health check:

```php
use MetabytesSRO\EPost\Api\Login;

$login = new Login();
$login->smsRequest($vendorId, $ekp);                              // SMS code to the registered mobile
$secret = $login->setPassword($vendorId, $ekp, $newPassword, $smsCode);
$response = $login->login($vendorId, $ekp, $secret, $password);   // ->getToken()

$status = $login->healthCheck();   // Error object: I501 = OK, W501 = maintenance announced, E501 = inactive
```

## Sending letters

### Delivery options

```php
use MetabytesSRO\EPost\Api\Metadata\DeliveryOptions;

$options = (new DeliveryOptions())
    ->setColorColored()          // or setColorGrayscale()
    ->setDuplex(true)
    ->setRegisteredStandard();   // Einschreiben

$letter->setDeliveryOptions($options);
```

Registered mail options accepted by the API:

| Method | API value |
|--------|-----------|
| `setRegisteredStandard()` | `Einschreiben` |
| `setRegisteredSubmissionOnly()` | `Einwurf Einschreiben` |
| `setRegisteredWithReturnReceipt()` | `Einschreiben Rückschein` |
| `setRegisteredNo()` | not registered |

The API rejects duplex printing for registered letters (E312) and registered
letters to international addresses (E311). For "Einschreiben Rückschein" the
return address is read from the sender line in the letter's address window;
explicit return address fields are obsolete and ignored by the API.

### Cover letter

```php
// Let the API generate a standard cover sheet with the address, or supply your own PDF:
$letter->setCoverLetter('/path/to/cover.pdf');
```

### Test mode

In test mode the API processes the letter and emails the result as PDF instead
of printing it:

```php
$letter->setTestEmail('test@example.com');
$letter->send();

$result = $letter->getTestResult();   // LetterDataResult
file_put_contents('result.pdf', $result->getPdf());
```

### International letters

```php
$recipient
    ->setAddressLine('Mario Rossi', 0)
    ->setAddressLine('Via Roma 1', 1)
    ->setZipCode('00100')          // three spaces when the country has no postal codes
    ->setCity('Roma')
    ->setCountry('ITALIEN');       // German name in capitals as per ISO 3166-1
```

### Batch sending

Several letters in one request (up to 300 MB of PDFs per request):

```php
$client = (new Letter())->setAccessToken($token);
$results = $client->sendBatch([$letter1, $letter2, $letter3]);   // LetterSendResult[]

foreach ($results as $result) {
    $result->getLetterId();
    $result->getFileName();
}
```

## Status queries

All status methods return `LetterStatus` objects. The API allows one status
query every 5 seconds; more frequent calls fail with `ErrorException::isRateLimited()`.

```php
$client = (new Letter())->setAccessToken($token);

$status = $client->getLetterStatus($letterId);
$status->getStatus();          // LetterStatusId enum, or null for an unknown ID
$status->isOpen();             // status 1-3: accepted, processed, sent to the print centre
$status->isSent();             // status 4: print centre reported the letter as sent
$status->hasError();           // status 99: see $status->getErrors()
$status->getRegisteredLetterId();
$status->getFrankierId();
$status->getNumberOfPages();

$client->getMultipleLetterStatuses([123, 456], onlyIssues: false);
$client->getLetterStatusByDateRange('2024-01-01', '2024-01-31', onlyIssues: false);
$client->getOpenLetters();
$client->getLetterStatusByCustom1('RE-000123');
$client->getLetterStatusByBatch(12345);
$client->getRegisteredLetterStatus('2024-01-01', '2024-01-31', onlyOpen: false);
$client->getPremiumAdressFeedback('2024-01-01', '2024-01-31', onlyFeedback: false);
```

### Registered mail tracking

```php
use MetabytesSRO\EPost\Api\TrackStatusCodes;

$code = $status->getRegisteredLetterStatus();        // e.g. "DELIVERED", "IN_DELIVERY"
TrackStatusCodes::getDescription($code);             // German description
TrackStatusCodes::isFinal($code);                    // true once delivery is complete
```

### Processing status IDs

| ID | `LetterStatusId` | Meaning |
|----|------------------|---------|
| 1 | `AcceptanceOfShipment` | Letter accepted, JSON validated |
| 2 | `ProcessingTheShipment` | PDF checked and released for the print centre |
| 3 | `DeliveryToThePrintingCenter` | Transferred to the print centre |
| 4 | `ProcessingInPrintingCenter` | Reported as sent by the print centre |
| 99 | `ProcessingError` | Failed, see `getErrors()` |

## Queued letters (UploadManagement plugin)

Letters submitted with the UploadManagement plugin wait for a minimum quantity
or a due date. They can be cancelled or released early while they are queued:

```php
$client->cancelQueued([74567567, 65765678]);    // QueuedOperationResult[]
$client->releaseQueued([74567567, 65765678]);
```

## Error handling

Every exception thrown by this package implements
`MetabytesSRO\EPost\Api\Exception\EPostException`.

| Exception | Thrown when |
|-----------|-------------|
| `ErrorException` | The API answered with an error (HTTP 4xx). `getCode()` is the E-POST code, `getError()` the parsed Error object. |
| `InvalidFileFormatException` | An attachment or cover letter is missing on disk or not a PDF. |
| `InvalidRecipientDataException` | Recipient data is incomplete or invalid. |
| `MissingPreconditionException` and subclasses | Something required was not set, e.g. no envelope, attachment or access token. |

```php
use MetabytesSRO\EPost\Api\Exception\EPostException;
use MetabytesSRO\EPost\Api\Exception\ErrorException;

try {
    $client->getLetterStatus($letterId);
} catch (ErrorException $e) {
    if ($e->isRateLimited()) {          // HTTP 429 / E322: wait 5 seconds and retry
    } elseif ($e->isNotFound()) {       // HTTP 404 / E201: unknown letter ID
    } elseif ($e->isAuthenticationError()) {   // E001, E002, E101: log in again
    }
} catch (EPostException $e) {
    // anything else raised by this package
}
```

Connection failures and timeouts are not converted; Guzzle's
`ConnectException` propagates so you can apply your own retry policy.

The complete catalogue of error, warning and info codes is in the `Error`
schema of the [API definition](docs/api/README.md).

## Price estimation

The E-POSTBUSINESS API has no pricing endpoint for letters. The calculator uses
the official Deutsche Post price lists (valid from 01.01.2025):

```php
use MetabytesSRO\EPost\Api\Pricing\LetterPriceCalculator;
use MetabytesSRO\EPost\Api\Pricing\PriceConfig;

$calculator = LetterPriceCalculator::fromEnv();

// weight in grams, pages, colour, duplex, international
$price = $calculator->calculate(20, 1, false, false, false);        // 0.80 EUR
$total = $calculator->calculateBatch(100, 50, 4, true, false, false);
```

Environment variables:

| Variable | Description |
|----------|-------------|
| `EPOST_TARIFF` | `basis` (default) or `250plus` |
| `EPOST_PRICES_JSON` | JSON object with negotiated prices; merged into the defaults |

```
EPOST_TARIFF=250plus
EPOST_PRICES_JSON={"national":{"basis":{"standard":{"sw_simplex":0.75}}}}
```

Price lists: [national](https://www.deutschepost.de/dam/jcr:4f6b160f-5beb-470a-9891-81e02acdd6e6/dp-epost-preisliste-mailer-basis_250+-ab%2001012025.pdf),
[international](https://www.deutschepost.de/dam/jcr:d7e72ba2-a855-4b1d-9300-3c5c6745bf86/dp-epost-preisliste-international-mailer-basis-ab-01012025_vf.pdf).

## Custom HTTP client

Pass a configured Guzzle client to add timeouts, logging or retries. It must
carry the base URI and the `Authorization: Bearer` header itself:

```php
use GuzzleHttp\Client;

$http = new Client([
    'base_uri' => Letter::API_ENDPOINT,
    'timeout' => 30,
    'headers' => ['Authorization' => 'Bearer ' . $token->getToken()],
]);

$client = new Letter($http);
```

Alternatively extend `Letter` or `Login` and override `createHttpClient()`.

## Upgrading

See [UPGRADE.md](UPGRADE.md) for behaviour changes and deprecations between
versions, and [CHANGELOG.md](CHANGELOG.md) for the full history.

## Contributing

Bug reports and pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md)
for the development setup; `composer check` runs the same code style, static
analysis and test checks as CI.

## Supporting the project

If this package is useful to you, consider supporting further development.
Get in touch via [metabytes.eu](https://metabytes.eu) or
[info@metabytes.eu](mailto:info@metabytes.eu). Feature requests are welcome too.

## License

LGPL-3.0-or-later. See [LICENSE](LICENSE).

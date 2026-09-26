# E-POSTBUSINESS API client for PHP

[![CI](https://github.com/metabytes-sro/epost-api/actions/workflows/ci.yml/badge.svg)](https://github.com/metabytes-sro/epost-api/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/metabytes-sro/epost-api.svg)](https://packagist.org/packages/metabytes-sro/epost-api)
[![PHP Version](https://img.shields.io/packagist/dependency-v/metabytes-sro/epost-api/php.svg)](https://packagist.org/packages/metabytes-sro/epost-api)
[![License](https://img.shields.io/packagist/l/metabytes-sro/epost-api.svg)](LICENSE)

Send PDF documents as physical letters through the Deutsche Post
[E-POSTBUSINESS API](https://api.epost.docuguide.com/swagger/index.html), track
their delivery, manage queued letters and estimate postage.

- One client class for every `/api/Letter` endpoint, plus `Login` for account setup and the health check
- Immutable, validated value objects: the client refuses letters the API would reject before sending anything
- One exception hierarchy: `ApiException` with `AuthenticationException`, `NotFoundException` and `RateLimitException`, `TransportException` for network failures, `ValidationException` for bad input
- Automatic re-login when the API reports an expired token
- Enums for status IDs, registered-mail options, tracking codes and all 51 documented error codes
- Plugins for scheduled sending (UploadManagement), address positioning (Automover) and address correction (PremiumAdress)
- Any PSR-18 HTTP client; Guzzle is the default
- Local price calculator based on the official Deutsche Post price lists
- Tested against a mocked API on PHP 8.3 to 8.5 with 100% line coverage enforced in CI

Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md).

## Scope

| API area | Covered |
|----------|---------|
| `/api/Letter` (sending, status queries, queued letters, PremiumAdress feedback, test results) | Yes, completely |
| `/api/Login` (token, SMS code, password, health check) | Yes, completely |
| `/api/Vendor` (reporting for software vendors) | No, and not planned |
| `/api/Campaign` (Dialogpost mass mailings) | No, and not planned |
| `/api/Client` (changing the customer's contact email and mobile number) | No, and not planned |

The vendor, campaign and client endpoints are out of scope for this package and
will not be added. If you need them, call them with your own HTTP client; the
API definition is in [`docs/api/`](docs/api/README.md).

## Installation

```bash
composer require metabytes-sro/epost-api
```

Requires PHP 8.3 or newer. Version 1.x supports PHP 8.1 and 8.2.

## Quick start

```php
use MetabytesSRO\EPost\Api\Attachment;
use MetabytesSRO\EPost\Api\Auth\Credentials;
use MetabytesSRO\EPost\Api\EPostClient;
use MetabytesSRO\EPost\Api\Exception\EPostException;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Recipient;

$client = EPostClient::withCredentials(new Credentials($vendorId, $ekp, $secret, $password));

$letter = new Letter(
    new Recipient('Max Mustermann AG', '12345', 'Bonn', 'Musterstraße 99'),
    Attachment::fromFile('/path/to/document.pdf'),
);

try {
    $result = $client->sendLetter($letter);
    $letterId = $result->letterId;
} catch (EPostException $e) {
    // see "Error handling" below
}
```

The document must be PDF/A-1b in DIN A4 portrait with the recipient address in
the address window, at most 20 MB and 94 pages. See the
[API documentation](https://api.epost.docuguide.com/swagger/index.html) for the
address template.

## Authentication

`EPostClient::withCredentials()` logs in on the first request and reuses the
token, which the API issues for 24 hours. When the API reports the token as
expired, the client logs in again and retries the request once.

```php
use MetabytesSRO\EPost\Api\Auth\CachedTokenProvider;
use MetabytesSRO\EPost\Api\Auth\Credentials;
use MetabytesSRO\EPost\Api\Auth\CredentialsTokenProvider;
use MetabytesSRO\EPost\Api\EPostClient;

// Credentials with the optional partner sub-ID and a shorter token lifetime in minutes
$credentials = new Credentials($vendorId, $ekp, $secret, $password, vendorSubId: 'shop-7', tokenDuration: 60);

// A token obtained elsewhere (OAuth2 provider, previous request)
$client = EPostClient::withToken($jwt);

// Share the token between PHP processes through any PSR-16 cache
$tokens = new CachedTokenProvider(new CredentialsTokenProvider($credentials), $psr16Cache);
$client = new EPostClient($tokens);
```

Account setup and the availability check live in `Login`:

```php
use MetabytesSRO\EPost\Api\Login;

$login = new Login();
$login->smsRequest($vendorId, $ekp);                               // SMS code to the registered mobile
$secret = $login->setPassword($vendorId, $ekp, $newPassword, $smsCode);

$status = $client->healthCheck();   // Error object: I501 = OK, W501 = maintenance announced, E501 = inactive
```

## Building a letter

```php
use MetabytesSRO\EPost\Api\Attachment;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Recipient;
use MetabytesSRO\EPost\Api\RegisteredMailType;
use MetabytesSRO\EPost\Api\SenderAddress;
use MetabytesSRO\EPost\Api\TestOptions;

$letter = (new Letter())
    ->recipient(new Recipient('Max Mustermann', '53115', 'Bonn', 'Musterstraße 1'))
    ->document(Attachment::fromString($pdfBytes, 'RE-2024-001.pdf'))
    ->color()
    ->duplex()
    ->batchId(4711)                          // group letters for getLetterStatusByBatch()
    ->custom(1, 'RE-2024-001')               // custom1..custom5, searchable with getLetterStatusByCustom1()
    ->costCenter('SALES01')
    ->vendorSystemInformation('my-erp 1.2')
    ->sender(SenderAddress::fromFields('Fa. Huber GmbH', 'Am Weg 1', '76887', 'Bad Bergzabern'))
    ->duplicateFailsafe();                   // reject a duplicate submitted within the last hour
```

Every value object validates its input and throws `ValidationException` for
empty mandatory fields, over-long values or invalid file names. `toPayload()`,
called by `sendLetter()`, checks the combinations the API rejects: registered
mail with duplex printing (E312), with an international recipient (E311) or with
the PremiumAdress and Automover plugins.

### Registered mail

```php
$letter->registeredMail(RegisteredMailType::Standard);        // Einschreiben
$letter->registeredMail(RegisteredMailType::Submission);      // Einwurf Einschreiben
$letter->registeredMail(RegisteredMailType::ReturnReceipt);   // Einschreiben Rückschein
```

For the return receipt the API takes the return address from the sender line in
the letter's address window.

### Cover sheet

```php
$letter->generateCoverSheet();                                        // standard cover sheet with the address
$letter->coverSheet(Attachment::fromFile('/path/to/cover.pdf'));      // your own PDF, at most 500 KB
```

### Test mode

In test mode the API processes the letter and emails the result as PDF instead
of printing it:

```php
$result = $client->sendLetter($letter->test(new TestOptions('test@example.com', showRestrictedArea: true)));

$pdf = $client->getTestResult($result->letterId)->pdf();
```

### International letters

```php
new Recipient('Mario Rossi', '00100', 'Roma', 'Via Roma 1', country: 'ITALIEN');   // German name in capitals (ISO 3166-1)
new Recipient('Someone', Recipient::NO_ZIP_CODE, 'Dublin', country: 'IRLAND');     // three spaces where there is no postal code
```

### Plugins

```php
use MetabytesSRO\EPost\Api\PlugIn\Automover;
use MetabytesSRO\EPost\Api\PlugIn\PremiumAdress;
use MetabytesSRO\EPost\Api\PlugIn\PremiumAdressVariant;
use MetabytesSRO\EPost\Api\PlugIn\UploadManagement;
use MetabytesSRO\EPost\Api\PlugIn\Weekday;

// Hold the letter until a due date, or collect for the minimum quantity of 50 per day
$letter->plugIn(UploadManagement::dueInDays(3));
$letter->plugIn(UploadManagement::dueOn(new DateTimeImmutable('2026-10-15')));
$letter->plugIn(UploadManagement::dueOnWeekday(Weekday::Friday, useMinimumQuantity: true));

// Let the API position the addresses on the first page
$letter->plugIn(new Automover());
$letter->recipient(Recipient::forAutomover());   // when the address is only printed on the document

// Address correction feedback from Deutsche Post
$letter->plugIn(new PremiumAdress(PremiumAdressVariant::Report));
```

### Batch sending

```php
$results = $client->sendLetters([$letter1, $letter2, $letter3]);   // one request, up to 300 MB of PDFs

foreach ($results as $result) {
    $result->letterId;
}
```

## Status queries

All status methods return `LetterStatus` objects with a typed, read-only
property for every field of the API. The API allows one status query every
5 seconds; more frequent calls throw `RateLimitException`.

```php
$status = $client->getLetterStatus($letterId);

$status->status();               // LetterStatusId enum, or null for an unknown ID
$status->isOpen();               // status 1-3: accepted, processed, transferred to the print centre
$status->isSent();               // status 4: reported as sent by the print centre
$status->hasError();             // status 99: see $status->errors
$status->errorsOnly();           // Error objects with level "Error"
$status->warnings();
$status->printFeedbackDate;      // DateTimeImmutable or null
$status->registeredLetterId;     // tracking number of registered mail
$status->trackingStatus();       // TrackStatusCode enum with ->description() and ->isFinal()
$status->frankierId;
$status->numberOfPages;
$status->plugInFeedback;         // PlugInFeedback objects, e.g. the PremiumAdress result
$status->raw;                    // the JSON object as received

$client->getLetterStatuses([123, 456], onlyIssues: true);
$client->getLetterStatusByDateRange(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-31'));
$client->getOpenLetters();
$client->getLetterStatusByCustom1('RE-2024-001');
$client->getLetterStatusByBatch(4711);
$client->getRegisteredLetterStatus($from, $till, onlyOpen: true);
$client->getPremiumAdressFeedback($from, $till, onlyFeedback: true);
```

### Processing status IDs

| ID | `LetterStatusId` | Meaning |
|----|------------------|---------|
| 1 | `AcceptanceOfShipment` | Letter accepted, JSON validated |
| 2 | `ProcessingTheShipment` | PDF checked and released for the print centre |
| 3 | `DeliveryToThePrintingCenter` | Transferred to the print centre |
| 4 | `ProcessingInPrintingCenter` | Reported as sent by the print centre |
| 99 | `ProcessingError` | Failed, see `$errors` |

## Queued letters

Letters submitted with the UploadManagement plugin wait for their due date or
the minimum quantity. While queued they can be cancelled or released early:

```php
$client->cancelQueued([74567567, 65765678]);    // QueueResult[] with ->successful and ->message
$client->releaseQueued([74567567, 65765678]);
```

## Error handling

Every exception thrown by this package implements
`MetabytesSRO\EPost\Api\Exception\EPostException`.

| Exception | Thrown when |
|-----------|-------------|
| `ValidationException` | Input would be rejected by the API: missing fields, over-long values, not a PDF, conflicting options. Nothing was sent. |
| `TransportException` | The API could not be reached: DNS, connection, TLS or timeout failure. The PSR-18 exception is `getPrevious()`. |
| `ApiException` | The API answered with an error. `getCode()` is the E-POST code, `getError()` the parsed Error object, `getErrorCode()` the `ErrorCode` enum. |
| `AuthenticationException` | Credentials rejected (E001, E002), token expired (E101) or HTTP 401. |
| `NotFoundException` | No letter matched (E201, HTTP 404). |
| `RateLimitException` | Too many calls (E322, E003, HTTP 429). Wait 5 seconds. |

```php
use MetabytesSRO\EPost\Api\Exception\ApiException;
use MetabytesSRO\EPost\Api\Exception\RateLimitException;
use MetabytesSRO\EPost\Api\Exception\TransportException;
use MetabytesSRO\EPost\Api\Exception\ValidationException;

try {
    $client->sendLetter($letter);
} catch (ValidationException $e) {
    // fix the input
} catch (RateLimitException $e) {
    sleep(RateLimitException::MIN_INTERVAL_SECONDS);
} catch (ApiException $e) {
    $e->getErrorCode()?->description();   // German text from the API definition
} catch (TransportException $e) {
    // retry later
}
```

The catalogue of error, warning and info codes is available as the `ErrorCode`
enum, each with `level()` and `description()`.

## Price estimation

The API has no pricing endpoint for letters. The calculator uses the official
Deutsche Post price lists valid from 01.01.2025:

```php
use MetabytesSRO\EPost\Api\Pricing\LetterPriceCalculator;
use MetabytesSRO\EPost\Api\Pricing\PriceConfig;
use MetabytesSRO\EPost\Api\Pricing\Tariff;

$calculator = new LetterPriceCalculator(new PriceConfig(Tariff::Plus250));

$calculator->calculate(weightGrams: 20, pages: 1);                                  // 0.73
$calculator->calculate(50, 4, color: true, duplex: false, international: true);
$calculator->calculateBatch(quantity: 100, weightGrams: 20, pages: 2);
```

Negotiated prices are merged into the defaults, either in code or through the
environment:

| Variable | Description |
|----------|-------------|
| `EPOST_TARIFF` | `basis` (default) or `250plus` |
| `EPOST_PRICES_JSON` | JSON object with the prices that differ from the defaults |

```php
$calculator = LetterPriceCalculator::fromEnv();
```

```
EPOST_TARIFF=250plus
EPOST_PRICES_JSON={"national":{"250plus":{"standard":{"sw_simplex":0.70}}}}
```

## Custom HTTP client

The client accepts any PSR-18 client through `Transport`. Guzzle is used when
none is given.

```php
use GuzzleHttp\Client;
use MetabytesSRO\EPost\Api\EPostClient;
use MetabytesSRO\EPost\Api\Http\Transport;

$transport = new Transport(new Client(['timeout' => 30]));
$client = EPostClient::withCredentials($credentials, $transport);

// Any other PSR-18 client, with PSR-17 factories:
$transport = new Transport($symfonyPsr18Client, $psr17Factory, $psr17Factory);
```

## Contributing

Bug reports and pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md)
for the development setup; `composer check` runs the same code style, static
analysis and test checks as CI.

## Supporting the project

If this package is useful to you, consider supporting further development.
Get in touch via [metabytes.eu](https://metabytes.eu) or
[info@metabytes.eu](mailto:info@metabytes.eu). Feature requests are welcome too.

## License

This package is licensed under the GNU Lesser General Public License,
version 3 or later (`LGPL-3.0-or-later`). The LGPL text is in
[LICENSE](LICENSE); it incorporates the GNU General Public License, whose
text is in [COPYING](COPYING).

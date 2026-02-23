# E-POSTBUSINESS API V2 PHP integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/metabytes-sro/epost-api.svg?style=flat-square)](https://packagist.org/packages/metabytes-sro/epost-api)

PHP integration for the [E-POSTBUSINESS API](https://api.epost.docuguide.com/swagger/index.html) for electronic submission of documents that are subsequently sent as physical letters.

## Install

```bash
composer require metabytes-sro/epost-api
```

## Upgrading

See [UPGRADE.md](UPGRADE.md) for migration instructions when upgrading between versions.

## Requirements

- PHP ^8.1
- guzzlehttp/guzzle ^7.0.1

## Usage

### Authentication

Obtain an access token via the [OAuth2 Provider](https://github.com/richardhj/oauth2-epost) or the built-in Login:

```php
use MetabytesSRO\EPost\Api\AccessToken;
use MetabytesSRO\EPost\Api\Letter;

$token = new AccessToken($vendorID, $ekp, $secret, $password);
```

### Sending a letter

```php
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\Metadata\Envelope;
use MetabytesSRO\EPost\Api\Metadata\Envelope\Recipient;
use MetabytesSRO\EPost\Api\Metadata\DeliveryOptions;

$letter = new Letter();
$envelope = new Envelope();
$recipient = new Recipient();
$recipient
    ->setAddressLine('Max Mustermann AG', 0)   // addressLine1
    ->setAddressLine('Musterstrasse 99', 1)    // addressLine2
    ->setZipCode('12345')
    ->setCity('Bonn');

$envelope->setRecipient($recipient);

$letter
    ->setAccessToken($token)
    ->setEnvelope($envelope)
    ->setAttachment('/path/to/document.pdf')
    ->setTestEnvironment(true);

// Optional: cover letter (PDF path)
$letter->setCoverLetter('/path/to/cover.pdf');

// Optional: test mode - receive PDF at email instead of physical send
$letter->setTestEmail('test@example.com');

try {
    $letter->send();
    $letterId = $letter->getLetterId();
} catch (MetabytesSRO\EPost\Api\Exception\ErrorException $e) {
    $error = $e->getError();
    // $error->getCode(), $error->getDescription()
}
```

### Einschreiben (registered mail) with return receipt

When using "Einschreiben Rückschein" or "Einschreiben eigenhändig Rückschein", you must provide the return address where the handwritten delivery confirmation is sent:

```php
use MetabytesSRO\EPost\Api\Metadata\DeliveryOptions;
use MetabytesSRO\EPost\Api\Metadata\RegisteredLetterReturnAddress;

$deliveryOptions = new DeliveryOptions();
$deliveryOptions
    ->setRegisteredWithReturnReceipt()  // or setRegisteredAddresseeOnlyWithReturnReceipt()
    ->setColorColored();

$returnAddress = new RegisteredLetterReturnAddress();
$returnAddress
    ->setAddressLine1('My Company GmbH')
    ->setZipCode('53115')
    ->setCity('Bonn');

$deliveryOptions->setRegisteredLetterReturnAddress($returnAddress);
$letter->setDeliveryOptions($deliveryOptions);
```

### Batch sending

Send multiple letters in one API request:

```php
$letters = [$letter1, $letter2, $letter3];
$letter->setAccessToken($token);
$results = $letter->sendBatch($letters);
foreach ($results as $result) {
    $letterId = $result->getLetterId();
}
```

### Status queries

```php
// Single letter
$status = $letter->getLetterStatus($letterId);

// Multiple by IDs
$statuses = $letter->getMultipleLetterStatuses([123, 456], $onlyIssues = false);

// By date range
$statuses = $letter->getLetterStatusByDateRange('2024-01-01', '2024-01-31');

// Open letters (status 1–3, not yet sent)
$statuses = $letter->getOpenLetters();

// Einschreiben (registered letters) by date range
$statuses = $letter->getRegisteredLetterStatus('2024-01-01', '2024-01-31', $onlyOpen = false);

// Einschreiben tracking status (resolve code to description)
$status = $letter->getLetterStatus($letterId);
$trackCode = $status->getRegisteredLetterStatus();  // e.g. "DELIVERED", "IN_DELIVERY"
$description = \MetabytesSRO\EPost\Api\TrackStatusCodes::getDescription($trackCode);  // German description
$isFinal = \MetabytesSRO\EPost\Api\TrackStatusCodes::isFinal($trackCode);  // true if delivery complete

// Search by custom1 field
$statuses = $letter->getLetterStatusByCustom1('RE-000123');

// By batch ID
$statuses = $letter->getLetterStatusByBatch(12345);
```

### UploadManagement plugin (queued letters)

For letters submitted with the UploadManagement plugin:

```php
// Cancel queued letters
$results = $letter->cancelQueued([74567567, 65765678]);

// Release (expedite) queued letters
$results = $letter->releaseQueued([74567567, 65765678]);
```

### PremiumAdress feedback

```php
$feedback = $letter->getPremiumAdressFeedback('2024-01-01', '2024-01-31');
```

### Price estimation

The E-POSTBUSINESS API does not provide a pricing endpoint for Letter (hybrid mail). Use the built-in calculator with official price lists (valid from 01.01.2025):

```php
use MetabytesSRO\EPost\Api\Pricing\LetterPriceCalculator;
use MetabytesSRO\EPost\Api\Pricing\PriceConfig;

// Default prices from Deutsche Post (Tarif Basis)
$calculator = LetterPriceCalculator::fromEnv();

// Single letter: weight (g), pages, color, duplex, international
$price = $calculator->calculate(20, 1, false, false, false);  // 0.80 € national standard

// Batch
$total = $calculator->calculateBatch(100, 50, 4, true, false, false);
```

**Environment variables** (optional):

| Variable | Description |
|----------|-------------|
| `EPOST_TARIFF` | `basis` (default) or `250plus` |
| `EPOST_PRICES_JSON` | JSON object to override default prices (e.g. negotiated rates) |

Example `.env`:

```
EPOST_TARIFF=250plus
# EPOST_PRICES_JSON={"national":{"basis":{"standard":{"sw_simplex":0.75}}}}
```

Price sources: [National](https://www.deutschepost.de/dam/jcr:4f6b160f-5beb-470a-9891-81e02acdd6e6/dp-epost-preisliste-mailer-basis_250+-ab%2001012025.pdf), [International](https://www.deutschepost.de/dam/jcr:d7e72ba2-a855-4b1d-9300-3c5c6745bf86/dp-epost-preisliste-international-mailer-basis-ab-01012025_vf.pdf)

## API reference

See the [E-POSTBUSINESS API Swagger documentation](https://api.epost.docuguide.com/swagger/index.html) for full details.

## Supporting the project

If this package is useful to you and you would like to support further development, we welcome donations. Please get in touch via [metabytes.eu](https://metabytes.eu) or [info@metabytes.eu](mailto:info@metabytes.eu). We are also open to feature requests.

## License

LGPL-3.0+

## Contributing

Please follow the [Symfony Coding Standards](http://symfony.com/doc/current/contributing/code/standards.html).

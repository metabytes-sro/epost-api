# Upgrading

This document describes breaking changes, behaviour changes and deprecations,
and how to adapt your code when upgrading between versions.

## Upgrading to 1.1 from 1.0

1.1 is backwards compatible with 1.0. A few behaviours changed in ways that may
be visible, and some API surface was deprecated for removal in 2.0.

### Behaviour changes

- **All `Letter` methods throw `ErrorException` for API errors.** In 1.0 only
  `send()`, `sendBatch()`, `getLetterStatus()` and `getMultipleLetterStatuses()`
  converted HTTP 4xx responses; the other methods let Guzzle's
  `ClientException` through. If you catch `GuzzleHttp\Exception\ClientException`
  around status queries, catch `MetabytesSRO\EPost\Api\Exception\ErrorException`
  instead. `ErrorException` now extends `RuntimeException` rather than
  `LogicException`.
- **`DeliveryOptions::getData()` no longer throws `MissingReturnAddressException`**
  for "Einschreiben Rückschein" without a return address. The API takes the
  return address from the letter's address window and ignores explicit values.
  Code that relied on the exception should validate the sender line on the PDF
  instead.
- **`Letter::setAttachment()` and `setCoverLetter()` fail early** with
  `InvalidFileFormatException` when the file does not exist or is not readable.
  Previously a PHP warning was emitted and the error surfaced later.
- **`Letter::send()` throws `ErrorException`** (code `E900`) when the API returns
  an empty list instead of failing with a PHP error.
- **Field length limits count characters, not bytes.** "Müller" now counts as
  6 characters. Values that were rejected before because of multibyte characters
  are accepted.
- **`PriceConfig` merges overrides into the defaults.** In 1.0 a partial
  `EPOST_PRICES_JSON` replaced the whole table and left the unmentioned prices at
  0.00. Prices you did not override now keep their default value.
- **`Login` expects a `GuzzleHttp\ClientInterface`.** The 1.0 signature accepted
  the PSR-18 `Psr\Http\Client\ClientInterface` but called Guzzle's `request()`,
  so only Guzzle clients ever worked. Guzzle's `Client` implements both
  interfaces; nothing changes for Guzzle users.

### Deprecations (removed in 2.0)

| Deprecated | Replacement | Reason |
|------------|-------------|--------|
| `DeliveryOptions::setRegisteredAddresseeOnly()`, `OPTION_REGISTERED_ADDRESSEE_ONLY` | `setRegisteredStandard()` | The API no longer offers "Einschreiben eigenhändig" and rejects it with E317 |
| `DeliveryOptions::setRegisteredAddresseeOnlyWithReturnReceipt()`, `OPTION_REGISTERED_ADDRESSEE_ONLY_WITH_RETURN_RECEIPT` | `setRegisteredWithReturnReceipt()` | Same as above |
| `RegisteredLetterReturnAddress`, `DeliveryOptions::setRegisteredLetterReturnAddress()`, `getRegisteredLetterReturnAddress()` | Place the sender line in the letter's address window | The API ignores these fields since October 2022 (warning W220) |
| `Exception\MissingReturnAddressException` | none, no longer thrown | See above |
| `Exception\InvalidFileFormat` | `Exception\InvalidFileFormatException` | Naming consistency. The new class extends the old one, so existing `catch` blocks keep working |

### Recommended changes

- Catch `Exception\EPostException` to handle every failure raised by the
  package in one place.
- Use `ErrorException::isRateLimited()`, `isNotFound()` and
  `isAuthenticationError()` instead of comparing error codes by hand.
- Use `LetterStatus::isOpen()`, `isSent()` and `hasError()` instead of comparing
  status IDs.
- Use `AccessToken::fromToken()` when you obtain the JWT elsewhere, instead of
  building a Guzzle client with the `Authorization` header yourself.

## Upgrading to 1.0 from 0.x

The 1.0 release introduces strict types for API responses. Update your code as follows:

### Login::login() return type

**Before:**
```php
$response = (new Login())->login($vendorId, $ekp, $secret, $password);
$token = $response['token'];
```

**After:**
```php
$response = (new Login())->login($vendorId, $ekp, $secret, $password);
$token = $response->getToken();
```

### Letter::sendBatch() return type

**Before:**
```php
$results = $letter->sendBatch($letters);
foreach ($results as $result) {
    $letterId = $result['letterID'];
}
```

**After:**
```php
$results = $letter->sendBatch($letters);
foreach ($results as $result) {
    $letterId = $result->getLetterId();
}
```

### Letter::cancelQueued() / releaseQueued() return type

**Before:**
```php
$results = $letter->cancelQueued($letterIds);
$message = $results[0]['message'];
```

**After:**
```php
$results = $letter->cancelQueued($letterIds);
$message = $results[0]->getMessage();
```

### LetterStatus::getErrors() return type

**Before:**
```php
$errors = $status->getErrors();
$code = $errors[0]['code'];
```

**After:**
```php
$errors = $status->getErrors();
$code = $errors[0]->getCode();
```

### Letter::getPremiumAdressFeedback() return type

Now returns `LetterStatus[]` instead of raw arrays. Use typed getters (e.g. `$item->getLetterId()`).

### Letter::getTestResult() return type

**Before:**
```php
$data = $letter->getTestResult();
```

**After:**
```php
$result = $letter->getTestResult($letterId);  // $letterId optional if set via send()
$data = $result->getData();
```

### LetterStatus status ID constants

**Before:**
```php
if ($status->getStatusId() === LetterStatus::PROCESSING_ERROR_ID) {
    // handle error
}
```

**After:**
```php
use MetabytesSRO\EPost\Api\LetterStatusId;

if ($status->getStatus() === LetterStatusId::ProcessingError) {
    // ...
}
// or compare via getStatusId():
if ($status->getStatusId() === LetterStatusId::ProcessingError->value) {
    // ...
}
```

### New classes (no migration needed)

- `LetterStatusId` (enum), `LoginResponse`, `LetterSendResult`, `QueuedOperationResult`, `LetterStatusError`, `LetterDataResult`
- `TrackStatusCodes` for Einschreiben status descriptions
- `LetterPriceCalculator` and `Pricing\*` for cost estimation

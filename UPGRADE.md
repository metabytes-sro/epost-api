# Upgrading

This document describes breaking changes, behaviour changes and deprecations,
and how to adapt your code when upgrading between versions.

## Upgrading to 2.0 from 1.x

2.0 requires PHP 8.3 and is a redesign of the public API. The 1.x line keeps
receiving bug fixes on the `1.x` branch for PHP 8.1 and 8.2.

### The client and the letter are separate objects

In 1.x, `Letter` was both the letter and the API client. In 2.0, `EPostClient`
talks to the API and `Letter` only describes what to send.

**Before:**
```php
$letter = (new Letter())
    ->setAccessToken(new AccessToken($vendorId, $ekp, $secret, $password))
    ->setEnvelope((new Envelope())->setRecipient($recipient))
    ->setAttachment('/path/to/document.pdf');
$letter->send();
$letterId = $letter->getLetterId();
$status = $letter->getLetterStatus($letterId);
```

**After:**
```php
$client = EPostClient::withCredentials(new Credentials($vendorId, $ekp, $secret, $password));
$letter = new Letter($recipient, Attachment::fromFile('/path/to/document.pdf'));
$letterId = $client->sendLetter($letter)->letterId;
$status = $client->getLetterStatus($letterId);
```

### Class and method mapping

| 1.x | 2.0 |
|-----|-----|
| `AccessToken` | `Auth\Credentials` with `EPostClient::withCredentials()`, or `Auth\CredentialsTokenProvider` |
| `AccessToken::fromToken()` | `EPostClient::withToken()` or `Auth\StaticTokenProvider` |
| `AccessToken::refresh()` | Automatic: the client re-logs in on E101 and retries once; `TokenProvider::invalidate()` forces it |
| `Login::login($vendorId, $ekp, $secret, $password)` | `Login::login(Credentials)` |
| `Login::healthCheck()` | Unchanged, also `EPostClient::healthCheck()` |
| `Envelope`, `Recipient` setters | `new Recipient(addressLine1, zipCode, city, addressLine2, ..., country)` passed to `Letter` |
| `Letter::setAttachment($path)` | `Letter::document(Attachment::fromFile($path))` or `Attachment::fromString()` |
| `Letter::setCoverLetter($path)` | `Letter::coverSheet(Attachment)`; `DeliveryOptions::setCoverLetterGenerate()` is `Letter::generateCoverSheet()` |
| `DeliveryOptions::setColor*()`, `setDuplex()` | `Letter::color()`, `Letter::duplex()` |
| `DeliveryOptions::setRegistered*()` | `Letter::registeredMail(RegisteredMailType::...)` |
| `DeliveryOptions::setTestFlag()`, `setTestEMail()`, `setTestShowRestrictedArea()`, `Letter::setTestEmail()` | `Letter::test(new TestOptions($email, $showRestrictedArea))` |
| `RegisteredLetterReturnAddress` | Removed; the API reads the return address from the letter's address window |
| `Letter::send()` | `EPostClient::sendLetter(Letter): LetterSendResult` |
| `Letter::sendBatch()` | `EPostClient::sendLetters(iterable<Letter>): list<LetterSendResult>` |
| `Letter::getLetterStatus()` and the other status methods | Same names on `EPostClient`; `getMultipleLetterStatuses()` is `getLetterStatuses()`; letter IDs are `int`, dates are `DateTimeInterface` |
| `Letter::cancelQueued()`, `releaseQueued()` | Same names on `EPostClient`, returning `QueueResult[]` |
| `Letter::getTestResult()` | `EPostClient::getTestResult(int): TestResult` with `->pdf()` |
| `LetterStatus::getLetterId()` and other getters | Public readonly properties: `$status->letterId`, `$status->createdDate` (`DateTimeImmutable`), ... |
| `LetterStatus::getStatus()` | `LetterStatus::status()` |
| `LetterStatus::getErrors()` | `$status->errors` (list of `Error`), plus `errorsOnly()` and `warnings()` |
| `LetterStatusError` | `Error` |
| `TrackStatusCodes::getDescription($code)`, `isFinal($code)` | `TrackStatusCode` enum: `$status->trackingStatus()?->description()`, `->isFinal()` |
| `Exception\ErrorException` | `Exception\ApiException`; `isAuthenticationError()`, `isNotFound()`, `isRateLimited()` became the subclasses `AuthenticationException`, `NotFoundException`, `RateLimitException` |
| `Exception\Missing*Exception`, `InvalidRecipientDataException`, `InvalidFileFormatException`, `InvalidArgumentException` from setters | `Exception\ValidationException` |
| Guzzle `ConnectException` leaking through | `Exception\TransportException` |
| `Pricing\PriceConfig::TARIFF_BASIS`, `getTariff()` reading the environment on every call | `Pricing\Tariff` enum passed to the constructor; `PriceConfig::fromEnv()` reads the environment once |
| `Pricing\PriceConfig::getInternationalPostagePrice()`, `getInternationalPrintPrice()` | `getInternationalPostagePrices()`, `getInternationalPrintPrices()` |
| `Pricing\LetterFormat::getMaxWeightGrams()`, `getIncludedSheets()`, `fromWeightAndPages()` | `maxWeightGrams()`, `includedSheets()`; `fromWeightAndPages()` removed |
| `Letter::createHttpClient()` override, `new Letter($guzzleClient)` | `new Http\Transport($psr18Client, $requestFactory, $streamFactory, $baseUri)` passed to `EPostClient` |

### New in 2.0

- `Letter::batchId()`, `custom()`, `costCenter()`, `vendorSystemInformation()`, `sender()`, `duplicateFailsafe()`: send fields that 1.x could only read back.
- Plugins: `PlugIn\UploadManagement`, `PlugIn\Automover`, `PlugIn\PremiumAdress`, attached with `Letter::plugIn()`.
- `ErrorCode` enum with the documented level and description of every API code.
- `Auth\CachedTokenProvider` to share a token between processes through PSR-16.
- Pre-flight validation of the option combinations the API rejects (E311, E312), file names, sizes and field lengths.

### Behaviour changes

- Letter IDs are `int` everywhere; 1.x used `string` in places.
- Dates in `LetterStatus` and `Error` are `DateTimeImmutable` instead of strings; status query methods take `DateTimeInterface` instead of `"Y-m-d"` strings.
- Query flags such as `onlyIssues` are keyword arguments with the same defaults as before.
- `Attachment` checks the PDF header itself; the `fileinfo` extension is no longer required, `mbstring` is.
- HTTP 5xx responses become `ApiException` (with a synthetic `HTTPxxx` code) instead of a Guzzle exception.

## Upgrading to 1.1 from 1.0

1.1 is backwards compatible with 1.0. A few behaviours changed in ways that may
be visible, and some API surface was deprecated for removal in 2.0.

### Behaviour changes

- **All `Letter` methods throw `ErrorException` for API errors.** In 1.0 only
  `send()`, `sendBatch()`, `getLetterStatus()` and `getMultipleLetterStatuses()`
  converted HTTP 4xx responses; the other methods let Guzzle's
  `ClientException` through. `ErrorException` now extends `RuntimeException`
  rather than `LogicException`.
- **`DeliveryOptions::getData()` no longer throws `MissingReturnAddressException`**
  for "Einschreiben Rückschein" without a return address. The API takes the
  return address from the letter's address window and ignores explicit values.
- **`Letter::setAttachment()` and `setCoverLetter()` fail early** with
  `InvalidFileFormatException` when the file does not exist or is not readable.
- **`Letter::send()` throws `ErrorException`** (code `E900`) when the API returns
  an empty list instead of failing with a PHP error.
- **Field length limits count characters, not bytes.**
- **`PriceConfig` merges overrides into the defaults.** In 1.0 a partial
  `EPOST_PRICES_JSON` replaced the whole table and left the unmentioned prices at
  0.00.
- **`Login` expects a `GuzzleHttp\ClientInterface`.** The 1.0 signature accepted
  the PSR-18 interface but called Guzzle's `request()`, so only Guzzle clients
  ever worked.

### Deprecations (removed in 2.0)

| Deprecated | Replacement | Reason |
|------------|-------------|--------|
| `DeliveryOptions::setRegisteredAddresseeOnly()`, `OPTION_REGISTERED_ADDRESSEE_ONLY` | `setRegisteredStandard()` | The API no longer offers "Einschreiben eigenhändig" and rejects it with E317 |
| `DeliveryOptions::setRegisteredAddresseeOnlyWithReturnReceipt()`, `OPTION_REGISTERED_ADDRESSEE_ONLY_WITH_RETURN_RECEIPT` | `setRegisteredWithReturnReceipt()` | Same as above |
| `RegisteredLetterReturnAddress`, `DeliveryOptions::setRegisteredLetterReturnAddress()`, `getRegisteredLetterReturnAddress()` | Place the sender line in the letter's address window | The API ignores these fields since October 2022 (warning W220) |
| `Exception\MissingReturnAddressException` | none, no longer thrown | See above |
| `Exception\InvalidFileFormat` | `Exception\InvalidFileFormatException` | Naming consistency |

## Upgrading to 1.0 from 0.x

The 1.0 release introduces strict types for API responses.

- `Login::login()` returns `LoginResponse` instead of `array`: use `->getToken()`.
- `Letter::sendBatch()` returns `LetterSendResult[]`: use `->getLetterId()`.
- `Letter::cancelQueued()` and `releaseQueued()` return `QueuedOperationResult[]`: use `->getMessage()`.
- `LetterStatus::getErrors()` returns `LetterStatusError[]`: use `->getCode()`.
- `Letter::getPremiumAdressFeedback()` returns `LetterStatus[]`.
- `Letter::getTestResult()` returns `LetterDataResult` and accepts an optional letter ID.
- `LetterStatus::*_ID` constants were replaced by the `LetterStatusId` enum.

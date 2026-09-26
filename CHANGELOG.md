# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `Login::healthCheck()` for `GET /api/Login/HealthCheck`
- `Login::login()` accepts the optional `vendorSubId` and `tokenDuration` parameters
- `AccessToken::fromToken()` to wrap a token obtained elsewhere, `AccessToken::refresh()` to force a new login, and an optional `Login` constructor argument for injection
- `Exception\EPostException` marker interface implemented by every exception of the package
- `Exception\InvalidFileFormatException` (replaces `InvalidFileFormat`)
- `ErrorException::getStatusCode()`, `isRateLimited()`, `isNotFound()` and `isAuthenticationError()`
- `Error::getDate()`, `isError()`, `isWarning()`, `isInfo()`; `LetterStatusError` now extends `Error`
- Typed getters on `LetterStatus` for every field of the API's LetterStatus schema: `getRegisteredLetter()`, `isRegisteredLetter()`, `hasCoverLetter()`, `getNumberOfPages()`, `getSubVendorId()`, `getZipCode()`, `getCity()`, `getCountry()`, `isColor()`, `isDuplex()`, `isTestShowRestrictedArea()`, `getVendorSystemInformation()`, `getCostCenter()`, `getFrankierId()`, `getDestinationAreaStatus()`, `getDestinationAreaStatusDate()`, `getPlugInFeedback()`, `toArray()`
- `LetterStatus::isOpen()`, `isSent()`, `hasError()` and `LetterStatusId::isOpen()`, `isSent()`, `isError()`, `label()`
- `onlyIssues` parameter on `Letter::getLetterStatusByCustom1()` and `getLetterStatusByBatch()`, `onlyFeedback` on `getPremiumAdressFeedback()`
- `LetterDataResult::getPdf()` returning the decoded PDF
- `DeliveryOptions::isRegistered()`, `Envelope::getRecipient()`, `Letter::getTestEmail()`
- `Letter::createHttpClient()` and `Login::createHttpClient()` hooks to customise the default Guzzle client
- Snapshots of the E-POSTBUSINESS API definition (v2.6.1) and tracking status codes under `docs/api/`
- GitHub Actions CI on PHP 8.1 to 8.5 with PHPStan, PHP-CS-Fixer and 100% line coverage enforced
- LICENSE file, CONTRIBUTING.md, SECURITY.md, issue and pull request templates

### Changed

- Every `Letter` method now converts API errors (HTTP 4xx) to `ErrorException`; previously nine of them let Guzzle's `ClientException` through
- `ErrorException` extends `RuntimeException` instead of `LogicException`
- `Letter::setAttachment()` and `setCoverLetter()` check that the file exists and is readable, throwing `InvalidFileFormatException` instead of a PHP warning
- `Login` and `Letter` accept any `GuzzleHttp\ClientInterface`; `Login` previously type-hinted the PSR-18 interface but called Guzzle-specific methods
- `Recipient` and `RegisteredLetterReturnAddress` count field lengths in characters instead of bytes
- `PriceConfig` merges overrides into the default price lists; a partial `EPOST_PRICES_JSON` no longer zeroes the prices it does not mention
- `Letter::getLetterStatusByBatch()` sends the query parameter as `batchId`, matching the API definition
- JSON request bodies are encoded with unescaped unicode and slashes
- Coding standard changed to PER Coding Style 2.0 enforced by PHP-CS-Fixer
- Development dependencies: PHPUnit 10.5 or newer, PHPStan, PHP-CS-Fixer; the test bootstrap file was replaced by the Composer autoloader

### Deprecated

- `DeliveryOptions::OPTION_REGISTERED_ADDRESSEE_ONLY`, `OPTION_REGISTERED_ADDRESSEE_ONLY_WITH_RETURN_RECEIPT`, `setRegisteredAddresseeOnly()` and `setRegisteredAddresseeOnlyWithReturnReceipt()`: the API (v2.6.1) no longer offers "Einschreiben eigenhändig" and rejects it with E317
- `RegisteredLetterReturnAddress`, `DeliveryOptions::setRegisteredLetterReturnAddress()`, `getRegisteredLetterReturnAddress()` and `MissingReturnAddressException`: the API reads the return address from the letter's address window since October 2022 and ignores these fields (warning W220)
- `Exception\InvalidFileFormat`, use `InvalidFileFormatException`

### Removed

- `DeliveryOptions::getData()` no longer throws `MissingReturnAddressException` for "Einschreiben Rückschein" without a return address, because the API does not require one
- Travis CI configuration

### Fixed

- `Letter::send()` throws `ErrorException` instead of a PHP error when the API returns an empty list
- Responses that are not JSON objects or lists no longer cause type errors

## [1.0.0] - 2026-02-23

### Added

- `LoginResponse` for typed login response
- `LetterSendResult` for send/sendBatch results
- `QueuedOperationResult` for cancelQueued/releaseQueued results
- `LetterStatusError` for LetterStatus error items
- `LetterDataResult` for getTestResult response
- `LetterStatusId` enum for status IDs (replaces LetterStatus constants)
- `LetterStatus::getStatus()` returns `LetterStatusId|null` mapped from statusID
- `TrackStatusCodes` for Einschreiben tracking status descriptions
- `LetterPriceCalculator` and `Pricing\*` for cost estimation (national/international)
- Typed getters on `LetterStatus` (getRegisteredLetterStatus, getRegisteredLetterStatusDate, etc.)
- Environment variables for pricing: `EPOST_TARIFF`, `EPOST_PRICES_JSON`
- Funding and donation info in composer.json and README
- UPGRADE.md and CHANGELOG.md for migration guidance
- Support for Laravel 12 and PHP 8.1+

### Changed

- **BREAKING:** `Login::login()` returns `LoginResponse` instead of `array`
- **BREAKING:** `Letter::sendBatch()` returns `LetterSendResult[]` instead of `array`
- **BREAKING:** `Letter::cancelQueued()` / `releaseQueued()` return `QueuedOperationResult[]` instead of `array`
- **BREAKING:** `LetterStatus::ACCEPTANCE_OF_SHIPMENT_ID` etc. removed; use `LetterStatusId` enum instead
- **BREAKING:** `LetterStatus::getErrors()` returns `LetterStatusError[]` instead of `array`
- **BREAKING:** `Letter::getPremiumAdressFeedback()` returns `LetterStatus[]` instead of `array`
- **BREAKING:** `Letter::getTestResult()` returns `LetterDataResult` and accepts optional `?string $letterId`
- License identifier updated to `LGPL-3.0-or-later`

### Fixed

- `Letter::getTestResult()` now passes letterID to API as required

### Documentation

- Class docblocks for Letter and Login: timeouts/connection errors are not caught by caller
- README: pricing examples, environment variables, Einschreiben tracking

[Unreleased]: https://github.com/metabytes-sro/epost-api/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/metabytes-sro/epost-api/compare/v0.11-beta...v1.0.0

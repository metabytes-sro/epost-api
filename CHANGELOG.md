# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

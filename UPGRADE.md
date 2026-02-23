# Upgrading

This document describes breaking changes and how to upgrade between versions.

## Upgrading to 1.x from 0.x

The 1.x release introduces strict types for API responses. Update your code as follows:

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
    // handle error
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

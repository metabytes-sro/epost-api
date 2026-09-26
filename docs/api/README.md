# E-POSTBUSINESS API reference material

Snapshots of the upstream API definition this package is built against. They
are kept here so changes in the API can be diffed against the code.

| File | Source | Snapshot |
|------|--------|----------|
| `epost-api-v2.6.1.swagger.json` | https://api.epost.docuguide.com/swagger/v2/swagger.json | 2026-09-26, API v2.6.1 |
| `trackStatusCodes.json` | https://api.epost.docuguide.com/trackStatusCodes.json | 2026-09-26 |

The human-readable Swagger UI is at https://api.epost.docuguide.com/swagger/index.html.

## Refreshing the snapshots

```bash
curl -sS https://api.epost.docuguide.com/swagger/v2/swagger.json -o docs/api/epost-api-vX.Y.Z.swagger.json
curl -sS https://api.epost.docuguide.com/trackStatusCodes.json -o docs/api/trackStatusCodes.json
```

Then update the table above and check:

- `TrackStatusCodes` against `trackStatusCodes.json` (the test suite compares
  the two, so a stale table fails `composer test`).
- The `Letter` and `LetterStatus` schemas against `Letter`, `DeliveryOptions`,
  `Recipient` and `LetterStatus`.
- The `registeredLetter` field description for the list of accepted
  registered-mail options.
- The `Error` schema description for the catalogue of error codes.

## Endpoints covered by this package

| Endpoint | Method on `Letter` / `Login` |
|----------|------------------------------|
| `POST /api/Login` | `Login::login()` |
| `POST /api/Login/smsRequest` | `Login::smsRequest()` |
| `POST /api/Login/setPassword` | `Login::setPassword()` |
| `GET /api/Login/HealthCheck` | `Login::healthCheck()` |
| `POST /api/Letter` | `Letter::send()`, `Letter::sendBatch()` |
| `GET /api/Letter/{letterID}` | `Letter::getLetterStatus()` |
| `POST /api/Letter/StatusQuery` | `Letter::getMultipleLetterStatuses()` |
| `GET /api/Letter/Date` | `Letter::getLetterStatusByDateRange()` |
| `GET /api/Letter/Open` | `Letter::getOpenLetters()` |
| `GET /api/Letter/Registered` | `Letter::getRegisteredLetterStatus()` |
| `GET /api/Letter/Custom1` | `Letter::getLetterStatusByCustom1()` |
| `GET /api/Letter/Batch` | `Letter::getLetterStatusByBatch()` |
| `GET /api/Letter/PremiumAdressFeedback` | `Letter::getPremiumAdressFeedback()` |
| `GET /api/Letter/TestResult` | `Letter::getTestResult()` |
| `POST /api/Letter/CancelQueued` | `Letter::cancelQueued()` |
| `POST /api/Letter/ReleaseQueued` | `Letter::releaseQueued()` |

Not covered: the `Campaign` (Dialogpost), `Client`, `Vendor` and `PlugIn`
documentation endpoints.

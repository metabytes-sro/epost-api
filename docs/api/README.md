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

## Endpoints out of scope

The following areas of the API are deliberately not covered and will not be
added to this package:

| Endpoints | Purpose |
|-----------|---------|
| `/api/Vendor/*` | Reporting for software vendors: letters per day, letters with errors, campaign details, contact information |
| `/api/Campaign/*` | Dialogpost mass mailings (cost estimate, samples, open, confirm, finalise, cancel, status) |
| `/api/Client/*` | Changing the customer's contact email address and mobile number |
| `/api/PlugIn/*` | Plugin documentation pages |

Pull requests adding them will not be merged. Users who need these endpoints
can call them with their own Guzzle client using the definition in this
directory.

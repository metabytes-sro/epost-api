# Examples

Runnable scripts that exercise the package against the E-POSTBUSINESS API.
They read the credentials from environment variables, so nothing sensitive is
stored in the repository.

```bash
composer install
cp .env.example .env      # fill in EPOST_VENDOR_ID, EPOST_EKP, EPOST_SECRET, EPOST_PASSWORD
set -a; source .env; set +a

php examples/health-check.php
php examples/send-letter.php path/to/letter.pdf
php examples/test-send.php path/to/letter.pdf you@example.com
php examples/track-letter.php 43556780
php examples/queued-letters.php path/to/letter.pdf
php examples/estimate-price.php
```

| Script | What it shows |
|--------|---------------|
| `health-check.php` | Availability of the API, without credentials |
| `send-letter.php` | Build a letter, send it, print the letter ID; use `--registered` for Einschreiben |
| `test-send.php` | Send in test mode, poll the status, save the processed PDF the API returns |
| `track-letter.php` | Status of one letter, its errors and warnings, tracking of registered mail |
| `queued-letters.php` | Schedule a letter with the UploadManagement plugin, then cancel it |
| `estimate-price.php` | Local price estimate for a few letter configurations |

`send-letter.php`, `test-send.php` and `queued-letters.php` submit real letters
to the API. Use an EKP that is not yet cleared for live sending, or add the
`EPOST_TEST_EMAIL` variable: when it is set, every example submits in test
mode and nothing is printed or posted.

The scripts share `bootstrap.php`, which loads the autoloader, reads the
environment and builds the client. Errors are reported through the package's
exception hierarchy; see the README for the full list.

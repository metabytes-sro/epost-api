# Framework integration

The package has no framework dependencies. Wire `EPostClient` into your
container once and inject it where letters are sent. The examples below use a
PSR-16 cache so the API token is shared between requests instead of logging in
on every one.

## Laravel

`config/services.php`:

```php
'epost' => [
    'vendor_id' => env('EPOST_VENDOR_ID'),
    'ekp' => env('EPOST_EKP'),
    'secret' => env('EPOST_SECRET'),
    'password' => env('EPOST_PASSWORD'),
    'vendor_sub_id' => env('EPOST_VENDOR_SUB_ID'),
],
```

A service provider, for example `app/Providers/EPostServiceProvider.php`:

```php
<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use MetabytesSRO\EPost\Api\Auth\CachedTokenProvider;
use MetabytesSRO\EPost\Api\Auth\Credentials;
use MetabytesSRO\EPost\Api\Auth\CredentialsTokenProvider;
use MetabytesSRO\EPost\Api\EPostClient;
use MetabytesSRO\EPost\Api\Http\Transport;
use MetabytesSRO\EPost\Api\Login;
use Psr\SimpleCache\CacheInterface;

final class EPostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EPostClient::class, function ($app): EPostClient {
            $config = $app['config']['services.epost'];
            $transport = new Transport(new Client(['timeout' => 30]));

            $credentials = new Credentials(
                $config['vendor_id'],
                $config['ekp'],
                $config['secret'],
                $config['password'],
                $config['vendor_sub_id'] ?: null,
            );

            // Laravel's cache store implements PSR-16.
            $tokens = new CachedTokenProvider(
                new CredentialsTokenProvider($credentials, new Login($transport)),
                $app->make(CacheInterface::class),
                'epost_api_token_' . $config['ekp'],
            );

            return new EPostClient($tokens, $transport);
        });
    }
}
```

Register the provider in `bootstrap/providers.php` (Laravel 11+) or
`config/app.php`, then inject the client:

```php
use MetabytesSRO\EPost\Api\EPostClient;
use MetabytesSRO\EPost\Api\Exception\EPostException;

final class SendInvoiceLetter
{
    public function __construct(private readonly EPostClient $epost) {}

    public function handle(Invoice $invoice): int
    {
        $letter = new Letter(
            new Recipient($invoice->customer->name, $invoice->customer->zip, $invoice->customer->city, $invoice->customer->street),
            Attachment::fromString($invoice->pdf(), sprintf('RE-%s.pdf', $invoice->number)),
        );
        $letter->custom(1, $invoice->number);

        try {
            return $this->epost->sendLetter($letter)->letterId;
        } catch (EPostException $e) {
            report($e);
            throw $e;
        }
    }
}
```

For queued jobs, catch `RateLimitException` and `TransportException` and
release the job with a delay; both are safe to retry. `ValidationException`
and other `ApiException`s are not: the input has to change first.

## Symfony

`config/packages/epost.yaml`:

```yaml
parameters:
    env(EPOST_VENDOR_SUB_ID): ''

services:
    MetabytesSRO\EPost\Api\Http\Transport:
        arguments:
            $httpClient: '@Psr\Http\Client\ClientInterface'
            $requestFactory: '@Psr\Http\Message\RequestFactoryInterface'
            $streamFactory: '@Psr\Http\Message\StreamFactoryInterface'

    MetabytesSRO\EPost\Api\Auth\Credentials:
        arguments:
            $vendorId: '%env(EPOST_VENDOR_ID)%'
            $ekp: '%env(EPOST_EKP)%'
            $secret: '%env(EPOST_SECRET)%'
            $password: '%env(EPOST_PASSWORD)%'
            $vendorSubId: '%env(nullable:EPOST_VENDOR_SUB_ID)%'

    MetabytesSRO\EPost\Api\Login: ~

    MetabytesSRO\EPost\Api\Auth\CredentialsTokenProvider: ~

    MetabytesSRO\EPost\Api\Auth\TokenProvider:
        class: MetabytesSRO\EPost\Api\Auth\CachedTokenProvider
        arguments:
            $inner: '@MetabytesSRO\EPost\Api\Auth\CredentialsTokenProvider'
            $cache: '@Symfony\Component\Cache\Psr16Cache'
            $cacheKey: 'epost_api_token'

    Symfony\Component\Cache\Psr16Cache:
        arguments: ['@cache.app']

    MetabytesSRO\EPost\Api\EPostClient: ~
```

With `symfony/http-client` and `nyholm/psr7` installed, the PSR-18 client and
PSR-17 factories above are provided by the framework (`Psr18Client`). Without
them, drop the three `Transport` arguments and Guzzle is used.

Inject `MetabytesSRO\EPost\Api\EPostClient` into your services as usual.

## Any PSR-11 container

The minimum wiring is two objects:

```php
$credentials = new Credentials(getenv('EPOST_VENDOR_ID'), getenv('EPOST_EKP'), getenv('EPOST_SECRET'), getenv('EPOST_PASSWORD'));
$client = EPostClient::withCredentials($credentials);
```

Add `CachedTokenProvider` with any PSR-16 implementation (Symfony Cache,
Laravel Cache, `cache/filesystem-adapter`, ...) when the process handling
requests is short-lived; otherwise `CredentialsTokenProvider` keeps the token
in memory for the lifetime of the client.

## Long-running workers

- Status queries are limited to one per 5 seconds per account. Poll with
  `sleep(RateLimitException::MIN_INTERVAL_SECONDS)` between calls, or fetch
  many letters at once with `getLetterStatuses()` and the date-range methods.
- Tokens expire after 24 hours. The client re-logs in automatically once per
  request when the API reports E101; with `CachedTokenProvider` the fresh token
  is written back to the cache.
- Batch up to 300 MB of PDFs per `sendLetters()` call; the API assigns one
  letter ID per document.

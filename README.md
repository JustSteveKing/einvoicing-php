# einvoicing/sdk

PHP client for the [einvoicing.dev](https://www.einvoicing.dev) API: validate,
convert and look up Peppol e-invoices.

It brings no HTTP client of its own. You hand it a PSR-18 client and PSR-17
factories, so it uses whatever your application already has and adds nothing
to your dependency tree that you have not already agreed to.

Using Laravel? [einvoicing/laravel](https://github.com/JustSteveKing/einvoicing-laravel)
wraps this package in a service provider, a config file and a testing fake.

## Install

```bash
composer require einvoicing/sdk
```

Plus an HTTP client and factories, if you do not already have them:

```bash
composer require nyholm/psr7 symfony/http-client
```

## Getting started

```php
use Einvoicing\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$psr17 = new Psr17Factory();

$client = new Client(
    http: new Psr18Client(),
    requests: $psr17,
    streams: $psr17,
    key: getenv('EINVOICING_API_KEY'),
);
```

The base URL is a fifth argument, for pointing at a local instance.

## Validating a document

```php
$report = $client->validations()->validate($xml);

if (! $report->valid) {
    foreach ($report->errors() as $finding) {
        echo "{$finding->ruleId}: {$finding->message}\n";
        echo "  {$finding->explanation}\n";
        echo "  {$finding->fix}\n";
    }
}
```

**An invalid document is a successful request.** It comes back as a report
whose `valid` is false, with every finding on it — not as an exception
carrying only the first. The second finding is usually the interesting one.

Each finding carries the official rule text in `message`, this API's plain
English in `explanation`, and the layer it came from. A schema error and a
Peppol rule error are different kinds of problem, and the layer says which
you have.

Pin the ruleset in your own configuration rather than letting it float:

```php
$report = $client->validations()->validate($xml, 'peppol-bis-billing-3.0.21');
```

Otherwise a release elsewhere can turn your passing build red without
anything of yours changing.

## Converting your own data

```php
$conversion = $client->conversions()->convert([
    'number' => 'INV-2026-0042',
    'issued' => '2026-09-18',
    'currency' => 'GBP',
    // seller, buyer, lines, payment...
]);

$xml = $conversion->document;
```

Totals and the VAT breakdown are worked out from the lines, and the result is
validated before it is returned, so a conversion never hands back an invalid
document. An invoice that cannot produce one throws
`InvalidInvoiceException`, whose `findings()` say why.

## Looking a participant up

```php
$participant = $client->participants()->find('9932:GB123456789');

if (! $participant->registered) {
    // Not on the network. Not an error — a fact about the world.
}

if (! $participant->accepts('Invoice-2::Invoice')) {
    // Registered, but not for invoices. A different problem, different fix.
}
```

Two traps this handles for you. A business absent from the optional Peppol
Directory (`directory` is null) may still be registered and perfectly
reachable — only the SML and SMP are authoritative. And a UK VAT number is
registered with or without its `GB` prefix, as two different participants;
the lookup tries both and reports the form that answered. Store that form,
not the one you sent.

## Account and keys

```php
$usage = $client->usage()->get();
$usage->documents->remaining(); // derived; the API sends used, included, overage

$key = $client->keys()->create('CI', mode: 'test');
$key->secret; // The only time this exists. Store it now.

$client->keys()->revoke($key->id);
$client->rulesets()->current();
$client->account()->get();
```

`test` keys are free, unmetered, and cannot touch the account — which makes
them the right thing to put in CI.

## Errors

Every failure is an `Einvoicing\Exceptions\EinvoicingException`. The API
answers with RFC 9457 problem documents, and the common ones have their own
class:

| Exception | When |
| --- | --- |
| `UnauthenticatedException` | The key is missing, wrong or revoked |
| `AllowanceExhaustedException` | The period's allowance is used up |
| `RateLimitedException` | Too many requests; `retryAfter()` says how long |
| `NotFoundException` | No such resource |
| `InvalidInvoiceException` | A conversion could not produce a valid document; `findings()` say why |
| `ProblemException` | Anything else the API reported |
| `TransportException` | The request never got an answer |

Branch on `$e->type` or `$e->slug()`, which are stable. Never on the title or
the detail: those are prose for a human reading a log, and they change.

## Testing

Pass a PSR-18 client that answers from a fixture. There is nothing else to
mock — the client holds no global state and reaches for nothing on its own.

## Development

```bash
composer test   # Pest
composer stan   # PHPStan, level 10
composer lint   # Pint
```

## Licence

MIT.

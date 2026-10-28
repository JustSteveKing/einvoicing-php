<?php

declare(strict_types=1);

use Einvoicing\Client;
use Einvoicing\Exceptions\ProblemException;
use Einvoicing\Exceptions\TransportException;
use Einvoicing\Tests\Support\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

it('identifies itself on every request', function (): void {
    $http = http()->queue(['data' => []]);

    client($http)->account()->get();

    $request = $http->lastRequest();
    // Whatever composer installed, never the hardcoded fallback drifting.
    expect($request->getHeaderLine('User-Agent'))->toStartWith('einvoicing-php/')
        ->and($request->getHeaderLine('User-Agent'))->not->toBe('einvoicing-php/')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json');
});

it('can be pointed somewhere else', function (): void {
    $http = (new FakeHttpClient)->queue(['data' => []]);
    $psr17 = new Psr17Factory;

    $client = new Client('sk_test', $http, $psr17, $psr17, 'http://localhost:8787');
    $client->account()->get();

    expect((string) $http->lastRequest()->getUri())->toBe('http://localhost:8787/v1/account');
});

it('wraps a transport failure rather than leaking the implementation', function (): void {
    $broken = new class implements ClientInterface
    {
        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            throw new class extends RuntimeException implements ClientExceptionInterface
            {
                protected $message = 'Connection refused';
            };
        }
    };

    $psr17 = new Psr17Factory;
    $client = new Client('sk_test', $broken, $psr17, $psr17);

    expect(fn () => $client->account()->get())
        ->toThrow(TransportException::class, 'Connection refused');
});

it('falls back to a generic problem for an unknown type', function (): void {
    $http = http()->queue([
        'type' => 'https://www.einvoicing.dev/problems/unsupported-document',
        'title' => 'That document type is not supported.',
        'status' => 415,
    ], 415);

    try {
        client($http)->account()->get();
        expect(false)->toBeTrue('Expected a ProblemException.');
    } catch (ProblemException $e) {
        expect($e::class)->toBe(ProblemException::class)
            ->and($e->slug())->toBe('unsupported-document')
            ->and($e->getMessage())->toBe('That document type is not supported.');
    }
});

it('raises a problem even when the body is not a problem document', function (): void {
    $http = http()->queue('', 500);

    expect(fn () => client($http)->account()->get())
        ->toThrow(ProblemException::class, 'The request failed.');
});

it('discovers the HTTP client and factories when none are given', function (): void {
    // Nothing is wired up here. symfony/http-client and nyholm/psr7 are
    // installed, so php-http/discovery should find both without being told.
    $client = new Client('sk_test');

    $http = $client->__debugInfo()['http'];

    expect(is_string($http) && is_subclass_of($http, ClientInterface::class))->toBeTrue();
});

it('still lets one piece be overridden while the rest is discovered', function (): void {
    $http = (new FakeHttpClient)->queue(['data' => ['email' => 'steve@example.com']]);

    // Only the transport is given; the factories are found.
    $client = new Client('sk_test', http: $http);

    expect($client->account()->get()->email)->toBe('steve@example.com')
        ->and($http->lastRequest()->getHeaderLine('Authorization'))->toBe('Bearer sk_test');
});

it('reports the version composer installed, not a constant someone forgot', function (): void {
    $http = http()->queue(['data' => []]);

    client($http)->account()->get();

    $agent = $http->lastRequest()->getHeaderLine('User-Agent');

    // Inside this repository composer answers dev-main, which is the truth.
    // In an install it is the tag. Either way it is not a stale literal.
    // The toolbox adds its own name and PHP's after it.
    expect($agent)->toStartWith('einvoicing-php/'.Composer\InstalledVersions::getPrettyVersion('einvoicing/sdk').' sdk-toolbox/');
});

it('retries a read the API was briefly unable to answer', function (): void {
    $http = http()->queue(['type' => 'about:blank', 'title' => 'Unavailable'], 503, ['Retry-After' => '0'])->queue(['data' => ['email' => 'steve@example.com']]);
    $psr17 = new Psr17Factory;

    $client = new Client('sk_test', $http, $psr17, $psr17);

    expect($client->account()->get()->email)->toBe('steve@example.com')
        ->and($http->requests)->toHaveCount(2);
});

it('never retries a conversion that may have landed', function (): void {
    $http = http()->queue(['title' => 'Bad gateway'], 502);
    $psr17 = new Psr17Factory;

    expect(fn () => new Client('sk_test', $http, $psr17, $psr17)->conversions()->convert(['number' => 'INV-1']))
        ->toThrow(ProblemException::class, 'Bad gateway');
    expect($http->requests)->toHaveCount(1);
});

it('refuses to send the key over plain HTTP to anything but this machine', function (): void {
    expect(fn () => new Client('sk_test', http(), baseUrl: 'http://api.einvoicing.dev'))
        ->toThrow(JustSteveKing\SdkToolbox\Errors\InvalidRequestException::class);
});

it('keeps the key out of exceptions', function (): void {
    $http = http()->queue(['type' => 'https://www.einvoicing.dev/problems/unauthenticated', 'title' => 'No.', 'status' => 401], 401);

    try {
        client($http)->account()->get();
    } catch (ProblemException $e) {
        expect(print_r($e, true))->not->toContain('sk_test_example');
    }
});

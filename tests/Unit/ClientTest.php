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
    expect($request->getHeaderLine('User-Agent'))->toBe('einvoicing-php/'.Client::VERSION)
        ->and($request->getHeaderLine('Accept'))->toBe('application/json');
});

it('can be pointed somewhere else', function (): void {
    $http = (new FakeHttpClient)->queue(['data' => []]);
    $psr17 = new Psr17Factory;

    $client = new Client($http, $psr17, $psr17, 'sk_test', 'http://localhost:8787');
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
    $client = new Client($broken, $psr17, $psr17, 'sk_test');

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

<?php

declare(strict_types=1);

use Einvoicing\Client;
use Einvoicing\Tests\Support\FakeHttpClient;
use JustSteveKing\SdkToolbox\Retries\RetryPolicy;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * A client wired to a fake transport. The factory is Nyholm's here; in an
 * application it is whatever PSR-17 implementation you already have.
 */
function client(FakeHttpClient $http): Client
{
    $psr17 = new Psr17Factory;

    // One attempt, so each test sees exactly the answer it queued; retries
    // have their own test.
    return new Client(
        http: $http,
        requests: $psr17,
        streams: $psr17,
        key: 'sk_test_example',
        retry: RetryPolicy::none(),
    );
}

function http(): FakeHttpClient
{
    return new FakeHttpClient;
}

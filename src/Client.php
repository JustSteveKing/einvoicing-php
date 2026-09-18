<?php

declare(strict_types=1);

namespace Einvoicing;

use Composer\InstalledVersions;
use Einvoicing\Exceptions\NoHttpClientException;
use Einvoicing\Exceptions\ProblemException;
use Einvoicing\Exceptions\TransportException;
use Einvoicing\Resources\Account;
use Einvoicing\Resources\Billing;
use Einvoicing\Resources\Conversions;
use Einvoicing\Resources\Keys;
use Einvoicing\Resources\Participants;
use Einvoicing\Resources\Rulesets;
use Einvoicing\Resources\Usage;
use Einvoicing\Resources\Validations;
use Http\Discovery\Exception as DiscoveryException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use JsonException;
use OutOfBoundsException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * The einvoicing.dev API.
 *
 * Bring your own HTTP, but do not wire it up unless you want to:
 *
 *     $client = new Client($key);
 *
 * The PSR-18 client and PSR-17 factories are discovered from whatever is
 * installed, so this works with Guzzle, Symfony, curl or whatever your
 * framework already has, and still brings no implementation of its own.
 * Pass any of them explicitly to override the discovery — a framework's
 * container should, and the tests here do.
 */
final class Client
{
    /**
     * What to call this client when composer cannot say.
     *
     * Only a fallback: the User-Agent reports the installed version, because
     * a hand-maintained constant drifts. This one had been claiming 0.1.0
     * since three releases earlier.
     */
    public const string VERSION = '0.2.4';

    private readonly ClientInterface $http;

    private readonly RequestFactoryInterface $requests;

    private readonly StreamFactoryInterface $streams;

    public function __construct(
        private readonly string $key,
        ?ClientInterface $http = null,
        ?RequestFactoryInterface $requests = null,
        ?StreamFactoryInterface $streams = null,
        private readonly string $baseUrl = 'https://api.einvoicing.dev',
    ) {
        // Discovery throws when nothing is installed. That is a composer
        // problem, not a runtime one, so say which package is missing rather
        // than letting php-http's own wording reach the application.
        try {
            $this->http = $http ?? Psr18ClientDiscovery::find();
        } catch (DiscoveryException $e) {
            throw new NoHttpClientException(
                message: 'No PSR-18 HTTP client was found. Install one — '
                    .'symfony/http-client or guzzlehttp/guzzle will do — or pass '
                    .'your own to the constructor.',
                previous: $e,
            );
        }

        try {
            $this->requests = $requests ?? Psr17FactoryDiscovery::findRequestFactory();
            $this->streams = $streams ?? Psr17FactoryDiscovery::findStreamFactory();
        } catch (DiscoveryException $e) {
            throw new NoHttpClientException(
                message: 'No PSR-17 HTTP factories were found. Install an '
                    .'implementation — nyholm/psr7 will do — or pass your own to '
                    .'the constructor.',
                previous: $e,
            );
        }
    }

    public function validations(): Validations
    {
        return new Validations($this);
    }

    public function conversions(): Conversions
    {
        return new Conversions($this);
    }

    public function participants(): Participants
    {
        return new Participants($this);
    }

    public function rulesets(): Rulesets
    {
        return new Rulesets($this);
    }

    public function usage(): Usage
    {
        return new Usage($this);
    }

    public function keys(): Keys
    {
        return new Keys($this);
    }

    public function account(): Account
    {
        return new Account($this);
    }

    public function billing(): Billing
    {
        return new Billing($this);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    /**
     * @param  array<string, mixed>|string  $body  An array is sent as JSON; a
     *                                             string is sent as it is,
     *                                             which is how XML gets in.
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function post(string $path, array|string $body = [], string $contentType = 'application/json', array $query = []): array
    {
        return $this->send('POST', $path, query: $query, body: $body, contentType: $contentType);
    }

    public function delete(string $path): void
    {
        $this->send('DELETE', $path);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @param  array<string, mixed>|string|null  $body
     * @return array<string, mixed>
     */
    private function send(
        string $method,
        string $path,
        array $query = [],
        array|string|null $body = null,
        string $contentType = 'application/json',
    ): array {
        $url = $this->baseUrl.$path;

        if ($query !== []) {
            $url .= '?'.http_build_query(array_filter($query, static fn (mixed $value): bool => $value !== null));
        }

        $request = $this->requests->createRequest($method, $url)
            ->withHeader('Authorization', 'Bearer '.$this->key)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', 'einvoicing-php/'.self::version());

        if ($body !== null) {
            $payload = is_string($body) ? $body : self::encode($body);

            $request = $request
                ->withHeader('Content-Type', $contentType)
                ->withBody($this->streams->createStream($payload));
        }

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException(
                message: "The request to {$url} could not be sent: {$e->getMessage()}",
                previous: $e,
            );
        }

        return $this->decode($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $contents = (string) $response->getBody();

        // 204, and anything else with nothing to say.
        if ($contents === '') {
            $decoded = [];
        } else {
            try {
                /** @var array<string, mixed> $decoded */
                $decoded = (array) json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException(
                    message: "The API answered {$status} with a body that is not JSON.",
                    previous: $e,
                );
            }
        }

        if ($status >= 400) {
            throw ProblemException::fromResponse($decoded, $status);
        }

        return $decoded;
    }

    /**
     * The installed version, so the User-Agent says something true.
     *
     * Composer knows what it installed; this class does not. Inside this
     * repository that answers "dev-main", which is also true.
     */
    private static function version(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return self::VERSION;
        }

        try {
            $version = InstalledVersions::getPrettyVersion('einvoicing/sdk');
        } catch (OutOfBoundsException) {
            // Not installed as a package: a checkout, or a bespoke autoloader.
            return self::VERSION;
        }

        return $version === null ? self::VERSION : ltrim($version, 'v');
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function encode(array $body): string
    {
        try {
            return json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $e) {
            throw new RuntimeException('The request body could not be encoded as JSON.', previous: $e);
        }
    }
}

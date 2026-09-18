<?php

declare(strict_types=1);

namespace Einvoicing;

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
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * The einvoicing.dev API.
 *
 * Bring your own HTTP: this takes a PSR-18 client and PSR-17 factories, so it
 * works with Guzzle, Symfony, curl or whatever your framework already has,
 * and adds no transitive dependency of its own.
 */
final class Client
{
    public const string VERSION = '0.1.0';

    public function __construct(
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface $requests,
        private readonly StreamFactoryInterface $streams,
        private readonly string $key,
        private readonly string $baseUrl = 'https://api.einvoicing.dev',
    ) {}

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
            ->withHeader('User-Agent', 'einvoicing-php/'.self::VERSION);

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

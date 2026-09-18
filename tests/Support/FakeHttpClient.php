<?php

declare(strict_types=1);

namespace Einvoicing\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * A PSR-18 client that answers from a queue and keeps what it was asked, so a
 * test can assert on the request as well as the result.
 */
final class FakeHttpClient implements ClientInterface
{
    /** @var list<ResponseInterface> */
    private array $queue = [];

    /** @var list<RequestInterface> */
    public array $requests = [];

    /**
     * @param  array<string, mixed>|string  $body
     * @param  array<string, string>  $headers
     */
    public function queue(array|string $body = [], int $status = 200, array $headers = []): self
    {
        $payload = is_string($body) ? $body : (string) json_encode($body);

        $this->queue[] = new Response($status, $headers + ['Content-Type' => 'application/json'], $payload);

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return array_shift($this->queue) ?? new Response(200, [], '{"data":{}}');
    }

    public function lastRequest(): RequestInterface
    {
        $last = end($this->requests);

        if ($last === false) {
            throw new RuntimeException('Nothing has been sent.');
        }

        return $last;
    }
}

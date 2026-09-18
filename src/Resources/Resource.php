<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Client;
use Einvoicing\Support\Data;

/**
 * Shared plumbing for the resource groups hanging off the client. A resource
 * is a thin thing: it knows its endpoints and which object to build from the
 * answer, and nothing about HTTP.
 */
abstract class Resource
{
    public function __construct(protected readonly Client $client) {}

    /**
     * Every response puts the thing you asked for under `data`.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected static function data(array $response): array
    {
        return Data::map($response, 'data');
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<array<string, mixed>>
     */
    protected static function collection(array $response): array
    {
        return Data::maps($response, 'data');
    }
}

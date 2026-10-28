<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Client;

/**
 * Shared plumbing for the resource groups hanging off the client. A resource
 * is a thin thing: it picks the request for each endpoint and sends it, and
 * knows nothing about HTTP.
 */
abstract class Resource
{
    public function __construct(protected readonly Client $client) {}
}

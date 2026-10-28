<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Responses\ApiKey;
use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * @implements Request<list<ApiKey>>
 */
final readonly class ListKeys implements Request
{
    public function method(): Method
    {
        return Method::Get;
    }

    public function path(): string
    {
        return '/v1/keys';
    }

    public function result(Response $response): array
    {
        return array_map(ApiKey::fromArray(...), Envelope::collection($response));
    }
}

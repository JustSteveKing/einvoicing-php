<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Responses\Usage;
use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * @implements Request<Usage>
 */
final readonly class GetUsage implements Request
{
    public function method(): Method
    {
        return Method::Get;
    }

    public function path(): string
    {
        return '/v1/usage';
    }

    public function result(Response $response): Usage
    {
        return Usage::fromArray(Envelope::data($response));
    }
}

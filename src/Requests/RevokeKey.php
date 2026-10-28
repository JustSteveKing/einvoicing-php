<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * @implements Request<null>
 */
final readonly class RevokeKey implements Request
{
    public function __construct(
        private string $id,
    ) {}

    public function method(): Method
    {
        return Method::Delete;
    }

    public function path(): string
    {
        return '/v1/keys/'.rawurlencode($this->id);
    }

    public function result(Response $response): null
    {
        return null;
    }
}

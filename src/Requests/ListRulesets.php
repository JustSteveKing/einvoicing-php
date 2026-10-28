<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Responses\Ruleset;
use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * @implements Request<list<Ruleset>>
 */
final readonly class ListRulesets implements Request
{
    public function method(): Method
    {
        return Method::Get;
    }

    public function path(): string
    {
        return '/v1/rulesets';
    }

    public function result(Response $response): array
    {
        return array_map(Ruleset::fromArray(...), Envelope::collection($response));
    }
}

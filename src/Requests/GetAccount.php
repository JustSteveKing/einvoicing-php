<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Responses\Account;
use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * @implements Request<Account>
 */
final readonly class GetAccount implements Request
{
    public function method(): Method
    {
        return Method::Get;
    }

    public function path(): string
    {
        return '/v1/account';
    }

    public function result(Response $response): Account
    {
        return Account::fromArray(Envelope::data($response));
    }
}

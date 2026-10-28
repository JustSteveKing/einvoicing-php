<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Responses\BillingSession;
use JustSteveKing\SdkToolbox\Bodies\Body;
use JustSteveKing\SdkToolbox\Bodies\JsonBody;
use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\HasBody;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * @implements Request<BillingSession>
 */
final readonly class CreateCheckoutSession implements HasBody, Request
{
    public function __construct(
        private string $plan,
    ) {}

    public function method(): Method
    {
        return Method::Post;
    }

    public function path(): string
    {
        return '/v1/billing/checkout-sessions';
    }

    public function body(): Body
    {
        return new JsonBody(['plan' => $this->plan]);
    }

    public function result(Response $response): BillingSession
    {
        return BillingSession::fromArray(Envelope::data($response));
    }
}

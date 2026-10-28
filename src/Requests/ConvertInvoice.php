<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Responses\Conversion;
use JustSteveKing\SdkToolbox\Bodies\Body;
use JustSteveKing\SdkToolbox\Bodies\JsonBody;
use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\HasBody;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * @implements Request<Conversion>
 */
final readonly class ConvertInvoice implements HasBody, Request
{
    /**
     * @param  array<string, mixed>  $invoice
     */
    public function __construct(
        private array $invoice,
        private string $target = 'peppol-bis-billing-3',
        private ?string $ruleset = null,
    ) {}

    public function method(): Method
    {
        return Method::Post;
    }

    public function path(): string
    {
        return '/v1/conversions';
    }

    public function body(): Body
    {
        $body = ['target' => $this->target, 'invoice' => $this->invoice];

        if ($this->ruleset !== null) {
            $body['ruleset'] = $this->ruleset;
        }

        return new JsonBody($body);
    }

    public function result(Response $response): Conversion
    {
        return Conversion::fromArray(Envelope::data($response));
    }
}

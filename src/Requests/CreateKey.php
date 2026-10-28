<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Responses\NewApiKey;
use JustSteveKing\SdkToolbox\Bodies\Body;
use JustSteveKing\SdkToolbox\Bodies\JsonBody;
use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\HasBody;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * @implements Request<NewApiKey>
 */
final readonly class CreateKey implements HasBody, Request
{
    public function __construct(
        private string $name,
        private string $mode = 'live',
        private ?string $expiresAt = null,
    ) {}

    public function method(): Method
    {
        return Method::Post;
    }

    public function path(): string
    {
        return '/v1/keys';
    }

    public function body(): Body
    {
        return new JsonBody(array_filter(
            ['name' => $this->name, 'mode' => $this->mode, 'expires_at' => $this->expiresAt],
            static fn (?string $value): bool => $value !== null,
        ));
    }

    public function result(Response $response): NewApiKey
    {
        return NewApiKey::fromArray(Envelope::data($response));
    }
}

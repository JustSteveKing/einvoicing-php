<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Responses\Participant;
use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * @implements Request<Participant>
 */
final readonly class FindParticipant implements Request
{
    public function __construct(
        private string $id,
    ) {}

    public function method(): Method
    {
        return Method::Get;
    }

    public function path(): string
    {
        return '/v1/participants/'.rawurlencode($this->id);
    }

    public function result(Response $response): Participant
    {
        return Participant::fromArray(Envelope::data($response));
    }
}

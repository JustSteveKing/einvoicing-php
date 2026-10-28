<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Responses\ValidationReport;
use JustSteveKing\SdkToolbox\Bodies\Body;
use JustSteveKing\SdkToolbox\Bodies\JsonBody;
use JustSteveKing\SdkToolbox\Bodies\RawBody;
use JustSteveKing\SdkToolbox\Http\Method;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Requests\HasBody;
use JustSteveKing\SdkToolbox\Requests\HasQuery;
use JustSteveKing\SdkToolbox\Requests\Request;

/**
 * The document as XML, or wrapped in JSON for HTTP stacks that would rather
 * not send an XML body.
 *
 * @implements Request<ValidationReport>
 */
final readonly class ValidateDocument implements HasBody, HasQuery, Request
{
    public function __construct(
        private string $document,
        private ?string $ruleset = null,
        private bool $asJson = false,
    ) {}

    public function method(): Method
    {
        return Method::Post;
    }

    public function path(): string
    {
        return '/v1/validations';
    }

    public function query(): array
    {
        return ['ruleset' => $this->ruleset];
    }

    public function body(): Body
    {
        return $this->asJson
            ? new JsonBody(['document' => $this->document])
            : new RawBody($this->document, 'application/xml');
    }

    public function result(Response $response): ValidationReport
    {
        return ValidationReport::fromArray(Envelope::data($response));
    }
}

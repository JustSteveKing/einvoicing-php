<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

use JsonException;
use JustSteveKing\SdkToolbox\Errors\ExceptionMapper;
use JustSteveKing\SdkToolbox\Errors\HttpException;
use JustSteveKing\SdkToolbox\Errors\ProblemDetails;
use JustSteveKing\SdkToolbox\Http\Response;
use JustSteveKing\SdkToolbox\Redaction\Redactor;

/**
 * Which exception an error response becomes.
 *
 * The API's problems are matched on the slug of their type, so a new host
 * for the problem pages doesn't change which exception you catch. The body is
 * read as a problem whatever its Content-Type says, because the API's errors
 * are always problem-shaped; anything that isn't still raises a
 * ProblemException, never a TypeError.
 *
 * @internal
 */
final readonly class Problems implements ExceptionMapper
{
    public function map(Response $response, Redactor $redactor): HttpException
    {
        $problem = self::problem($response);

        $class = match (ProblemException::slugOf($problem->type ?? '')) {
            'unauthenticated' => UnauthenticatedException::class,
            'allowance-exhausted' => AllowanceExhaustedException::class,
            'rate-limited' => RateLimitedException::class,
            'not-found' => NotFoundException::class,
            'invalid-invoice' => InvalidInvoiceException::class,
            default => ProblemException::class,
        };

        return new $class($response, $problem, $redactor);
    }

    private static function problem(Response $response): ?ProblemDetails
    {
        try {
            $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($body) || array_is_list($body)) {
            return null;
        }

        /** @var array<string, mixed> $body */
        return ProblemDetails::fromArray($body, (string) $response->request()->getUri());
    }
}

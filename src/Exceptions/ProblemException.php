<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

use JustSteveKing\SdkToolbox\Errors\HttpException;
use JustSteveKing\SdkToolbox\Errors\ProblemDetails;
use JustSteveKing\SdkToolbox\Http\Response;

/**
 * An RFC 9457 problem the API answered with.
 *
 * Branch on `$problem->type`, which is stable, and never on the title or the
 * detail: those are prose written for a human reading a log, and they change.
 * The common types have their own subclass so they can be caught directly.
 *
 * `type`, `title`, `detail` and `members` read the problem the toolbox
 * parsed; `status`, the response and a redacted snapshot of the request come
 * from HttpException.
 */
class ProblemException extends HttpException implements EinvoicingException
{
    public string $type {
        get => $this->problem->type ?? ProblemDetails::BLANK;
    }

    public string $title {
        get => $this->problem->title ?? 'The request failed.';
    }

    public ?string $detail {
        get => $this->problem?->detail;
    }

    /**
     * Everything the problem carried, including extensions such as
     * `findings` or `retry_after`.
     *
     * @var array<string, mixed>
     */
    public array $members {
        get => $this->problem === null ? [] : [
            ...array_filter([
                'type' => $this->problem->type,
                'title' => $this->problem->title,
                'status' => $this->problem->status,
                'detail' => $this->problem->detail,
                'instance' => $this->problem->instance,
            ], static fn (mixed $value): bool => $value !== null),
            ...$this->problem->extensions,
        ];
    }

    /** The short name at the end of the type URL: `allowance-exhausted`. */
    public function slug(): string
    {
        return self::slugOf($this->type);
    }

    public static function slugOf(string $type): string
    {
        $path = parse_url($type, PHP_URL_PATH);

        return is_string($path) ? basename($path) : $type;
    }

    protected function describe(Response $response, ?ProblemDetails $problem): string
    {
        $title = $problem->title ?? 'The request failed.';

        return $problem?->detail === null ? $title : "{$title} {$problem->detail}";
    }
}

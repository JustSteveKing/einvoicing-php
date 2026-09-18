<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

use RuntimeException;

/**
 * An RFC 9457 problem the API answered with.
 *
 * Branch on `$problem->type`, which is stable, and never on the title or the
 * detail: those are prose written for a human reading a log, and they change.
 * The common types have their own subclass so they can be caught directly.
 */
class ProblemException extends RuntimeException implements EinvoicingException
{
    /**
     * @param  array<string, mixed>  $members  Everything the problem carried,
     *                                         including extensions such as
     *                                         `findings` or `attempts_left`.
     */
    final public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly int $status,
        public readonly ?string $detail = null,
        public readonly array $members = [],
    ) {
        parent::__construct(
            message: $detail === null ? $title : "{$title} {$detail}",
            code: $status,
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromResponse(array $body, int $status): self
    {
        $type = is_string($body['type'] ?? null) ? $body['type'] : 'about:blank';
        $title = is_string($body['title'] ?? null) ? $body['title'] : 'The request failed.';
        $detail = is_string($body['detail'] ?? null) ? $body['detail'] : null;

        $class = self::classFor($type);

        return new $class(
            type: $type,
            title: $title,
            status: is_int($body['status'] ?? null) ? $body['status'] : $status,
            detail: $detail,
            members: $body,
        );
    }

    /** The short name at the end of the type URL: `allowance-exhausted`. */
    public function slug(): string
    {
        $path = parse_url($this->type, PHP_URL_PATH);

        return is_string($path) ? basename($path) : $this->type;
    }

    /**
     * @return class-string<self>
     */
    private static function classFor(string $type): string
    {
        $path = parse_url($type, PHP_URL_PATH);
        $slug = is_string($path) ? basename($path) : $type;

        return match ($slug) {
            'unauthenticated' => UnauthenticatedException::class,
            'allowance-exhausted' => AllowanceExhaustedException::class,
            'rate-limited' => RateLimitedException::class,
            'not-found' => NotFoundException::class,
            'invalid-invoice' => InvalidInvoiceException::class,
            default => self::class,
        };
    }
}

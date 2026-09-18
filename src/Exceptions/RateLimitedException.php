<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

/**
 * Too many requests in a short window. Waiting for `retryAfter()` seconds is
 * the whole fix: this is a rate limit, not an allowance.
 */
final class RateLimitedException extends ProblemException
{
    public function retryAfter(): ?int
    {
        $seconds = $this->members['retry_after'] ?? null;

        return is_int($seconds) ? $seconds : null;
    }
}

<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/** A short-lived link to a Stripe-hosted page. Redirect the browser to it. */
final readonly class BillingSession
{
    public function __construct(
        public string $url,
        public ?string $expiresAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            url: Data::string($data, 'url'),
            expiresAt: Data::nullableString($data, 'expires_at'),
        );
    }
}

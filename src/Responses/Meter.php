<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * One metered thing in the current billing period.
 *
 * `overage` is what has been used beyond the allowance — on a metered plan it
 * is what gets billed, and on a capped one it stays zero because the requests
 * that would have caused it were refused.
 */
final readonly class Meter
{
    public function __construct(
        public int $included,
        public int $used,
        public int $overage,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            included: Data::int($data, 'included'),
            used: Data::int($data, 'used'),
            overage: Data::int($data, 'overage'),
        );
    }

    /** What is left of the allowance. Derived, not something the API sends. */
    public function remaining(): int
    {
        return max(0, $this->included - $this->used);
    }

    /** How much of the allowance is gone, 0.0 to 1.0. */
    public function fraction(): float
    {
        return $this->included > 0 ? min(1.0, $this->used / $this->included) : 0.0;
    }
}

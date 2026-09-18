<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * One metered thing in the current billing period: what is included, what has
 * been used, and what is left.
 */
final readonly class Meter
{
    public function __construct(
        public int $included,
        public int $used,
        public int $remaining,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $included = Data::int($data, 'included');
        $used = Data::int($data, 'used');

        return new self(
            included: $included,
            used: $used,
            remaining: Data::int($data, 'remaining', max(0, $included - $used)),
        );
    }

    /** How much of the allowance is gone, 0.0 to 1.0. */
    public function fraction(): float
    {
        return $this->included > 0 ? min(1.0, $this->used / $this->included) : 0.0;
    }
}

<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * Where the account stands in the current billing period.
 *
 * Worth reading on a schedule rather than after a request fails: an exhausted
 * allowance arrives as an AllowanceExhaustedException, which is late.
 */
final readonly class Usage
{
    public function __construct(
        public string $plan,
        public string $periodStart,
        public string $periodEnd,
        public Meter $documents,
        public Meter $lookups,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            plan: Data::string($data, 'plan'),
            periodStart: Data::string($data, 'period_start'),
            periodEnd: Data::string($data, 'period_end'),
            documents: Meter::fromArray(Data::map($data, 'documents')),
            lookups: Meter::fromArray(Data::map($data, 'lookups')),
        );
    }
}

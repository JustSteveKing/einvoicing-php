<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * A published release of a rule set, such as Peppol BIS Billing 3.0.21.
 *
 * Rule sets are versioned and dated because a document valid last quarter can
 * be invalid this one. Pin the version you validate against in your own code,
 * and use `mandatoryFrom` to know when you have to move.
 */
final readonly class Ruleset
{
    public function __construct(
        public string $id,
        public string $name,
        public string $version,
        public string $status,
        public ?string $released,
        public ?string $mandatoryFrom,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Data::string($data, 'id'),
            name: Data::string($data, 'name'),
            version: Data::string($data, 'version'),
            status: Data::string($data, 'status'),
            released: Data::nullableString($data, 'released'),
            mandatoryFrom: Data::nullableString($data, 'mandatory_from'),
        );
    }

    public function isCurrent(): bool
    {
        return $this->status === 'current';
    }
}

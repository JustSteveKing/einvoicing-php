<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * The outcome of validating one document.
 *
 * A document that breaks the rules is a successful request: this arrives with
 * `valid` false rather than as an exception.
 */
final readonly class ValidationReport
{
    /**
     * @param  array<string, mixed>  $ruleset  Which release it was checked against.
     * @param  array<string, mixed>  $document  What the document declared itself to be.
     * @param  list<array<string, mixed>>  $layers  Each layer and whether it passed, failed or was skipped.
     * @param  array<string, mixed>  $summary
     * @param  list<Finding>  $findings
     */
    public function __construct(
        public bool $valid,
        public array $ruleset,
        public array $document,
        public array $layers,
        public array $summary,
        public array $findings,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            valid: Data::bool($data, 'valid'),
            ruleset: Data::map($data, 'ruleset'),
            document: Data::map($data, 'document'),
            layers: Data::maps($data, 'layers'),
            summary: Data::map($data, 'summary'),
            findings: array_map(
                static fn (array $finding): Finding => Finding::fromArray($finding),
                Data::maps($data, 'findings'),
            ),
        );
    }

    /** @return list<Finding> */
    public function errors(): array
    {
        return array_values(array_filter($this->findings, static fn (Finding $f): bool => $f->isError()));
    }

    /** @return list<Finding> */
    public function warnings(): array
    {
        return array_values(array_filter($this->findings, static fn (Finding $f): bool => ! $f->isError()));
    }
}

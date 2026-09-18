<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * One thing a validator found. The rule text is the official wording, so it
 * can be quoted to whoever asks where a requirement comes from; the
 * explanation and the fix are this API's, in plain English.
 */
final readonly class Finding
{
    /**
     * @param  list<string>  $businessTerms  The EN 16931 terms it concerns, such as BT-10.
     * @param  array<string, mixed>  $location  Where in the document it failed.
     */
    public function __construct(
        public string $ruleId,
        public string $layer,
        public string $severity,
        public string $message,
        public string $explanation,
        public ?string $fix,
        public array $businessTerms,
        public array $location,
        public string $docsUrl,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            ruleId: Data::string($data, 'rule_id'),
            layer: Data::string($data, 'layer'),
            severity: Data::string($data, 'severity'),
            message: Data::string($data, 'message'),
            explanation: Data::string($data, 'explanation'),
            fix: Data::nullableString($data, 'fix'),
            businessTerms: Data::strings($data, 'business_terms'),
            location: Data::map($data, 'location'),
            docsUrl: Data::string($data, 'docs_url'),
        );
    }

    /** An error makes the document invalid; a warning does not. */
    public function isError(): bool
    {
        return $this->severity === 'error' || $this->severity === 'fatal';
    }
}

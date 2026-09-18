<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * A JSON invoice turned into a Peppol document. The totals and the VAT
 * breakdown were computed from the lines, and the document was validated
 * before it was returned: a conversion never hands back an invalid document.
 */
final readonly class Conversion
{
    /**
     * @param  string  $document  The UBL, as XML.
     * @param  array<string, mixed>  $totals
     * @param  list<array<string, mixed>>  $vatBreakdown
     */
    public function __construct(
        public string $target,
        public string $document,
        public array $totals,
        public array $vatBreakdown,
        public ValidationReport $validation,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            target: Data::string($data, 'target'),
            document: Data::string($data, 'document'),
            totals: Data::map($data, 'totals'),
            vatBreakdown: Data::maps($data, 'vat_breakdown'),
            validation: ValidationReport::fromArray(Data::map($data, 'validation')),
        );
    }
}

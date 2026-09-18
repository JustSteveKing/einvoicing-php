<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * One document type a participant accepts.
 *
 * Only e-invoicing types are listed — Peppol BIS Billing 3.0 and PINT Billing
 * invoices and credit notes. A participant registered for orders or
 * catalogues will not show those here, so an empty list means "cannot receive
 * an invoice", not "not on the network".
 */
final readonly class Capability
{
    public function __construct(
        public string $name,
        public string $documentTypeId,
        public string $processId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Data::string($data, 'name'),
            documentTypeId: Data::string($data, 'document_type_id'),
            processId: Data::string($data, 'process_id'),
        );
    }
}

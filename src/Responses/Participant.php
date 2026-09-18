<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * Whether a business can receive Peppol documents, and which ones.
 *
 * A participant that is not on the network is not an error: `registered` is
 * false. And `directory` being null means only that they are absent from the
 * optional Peppol Directory, never that they are unreachable.
 */
final readonly class Participant
{
    /**
     * @param  list<Capability>  $capabilities  The document types they accept.
     * @param  array<string, mixed>|null  $directory
     */
    public function __construct(
        public string $id,
        public string $scheme,
        public string $identifier,
        public bool $registered,
        public array $capabilities,
        public ?array $directory,
        public string $checkedAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Data::string($data, 'id'),
            scheme: Data::string($data, 'scheme'),
            identifier: Data::string($data, 'identifier'),
            registered: Data::bool($data, 'registered'),
            capabilities: array_map(
                static fn (array $capability): Capability => Capability::fromArray($capability),
                Data::maps($data, 'capabilities'),
            ),
            directory: Data::nullableMap($data, 'directory'),
            checkedAt: Data::string($data, 'checked_at'),
        );
    }

    /**
     * Does this participant accept a given document type?
     *
     * Takes a full Peppol document type identifier, or any distinctive part
     * of one, so `Invoice-2::Invoice` matches without pasting the whole
     * `urn:oasis:…` string.
     */
    public function accepts(string $documentType): bool
    {
        foreach ($this->capabilities as $capability) {
            if (str_contains($capability->documentTypeId, $documentType)) {
                return true;
            }
        }

        return false;
    }
}

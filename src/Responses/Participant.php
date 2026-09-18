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
     * @param  list<array<string, mixed>>  $capabilities  The document types they accept.
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
            capabilities: Data::maps($data, 'capabilities'),
            directory: Data::nullableMap($data, 'directory'),
            checkedAt: Data::string($data, 'checked_at'),
        );
    }

    /** Does this participant accept a given document type identifier? */
    public function accepts(string $documentType): bool
    {
        foreach ($this->capabilities as $capability) {
            foreach ($capability as $value) {
                if (is_string($value) && str_contains($value, $documentType)) {
                    return true;
                }
            }
        }

        return false;
    }
}

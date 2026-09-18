<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * A key that has just been created, and the only time its secret exists
 * anywhere you can read it. Store it now; the API cannot show it again.
 */
final class NewApiKey extends ApiKey
{
    public function __construct(
        string $id,
        string $name,
        string $mode,
        string $prefix,
        string $createdAt,
        ?string $lastUsedAt,
        ?string $expiresAt,
        ?string $revokedAt,
        public readonly string $secret,
    ) {
        parent::__construct($id, $name, $mode, $prefix, $createdAt, $lastUsedAt, $expiresAt, $revokedAt);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Data::string($data, 'id'),
            name: Data::string($data, 'name'),
            mode: Data::string($data, 'mode'),
            prefix: Data::string($data, 'prefix'),
            createdAt: Data::string($data, 'created_at'),
            lastUsedAt: Data::nullableString($data, 'last_used_at'),
            expiresAt: Data::nullableString($data, 'expires_at'),
            revokedAt: Data::nullableString($data, 'revoked_at'),
            secret: Data::string($data, 'secret'),
        );
    }
}

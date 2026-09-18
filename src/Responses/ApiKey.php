<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/**
 * An API key as the API will show it to you: the prefix, never the secret.
 * The secret exists once, in the response to creating the key.
 *
 * @see NewApiKey
 */
class ApiKey
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $mode,
        public readonly string $prefix,
        public readonly string $createdAt,
        public readonly ?string $lastUsedAt,
        public readonly ?string $expiresAt,
        public readonly ?string $revokedAt,
    ) {}

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
        );
    }

    public function isLive(): bool
    {
        return $this->mode === 'live';
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }
}

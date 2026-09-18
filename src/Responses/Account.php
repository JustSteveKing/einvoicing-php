<?php

declare(strict_types=1);

namespace Einvoicing\Responses;

use Einvoicing\Support\Data;

/** The account the API key belongs to. */
final readonly class Account
{
    public function __construct(
        public string $id,
        public string $email,
        public string $plan,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Data::string($data, 'id'),
            email: Data::string($data, 'email'),
            plan: Data::string($data, 'plan'),
            createdAt: Data::string($data, 'created_at'),
        );
    }
}

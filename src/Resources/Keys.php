<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Responses\ApiKey;
use Einvoicing\Responses\NewApiKey;

/**
 * Managing the account's API keys. Live keys only: a test key can validate
 * and convert all day, and cannot touch the account.
 */
final class Keys extends Resource
{
    /** @return list<ApiKey> */
    public function all(): array
    {
        return array_map(
            static fn (array $key): ApiKey => ApiKey::fromArray($key),
            self::collection($this->client->get('/v1/keys')),
        );
    }

    /**
     * Create a key. The secret comes back exactly once, here — store it now,
     * because the API cannot show it to you again.
     *
     * @param  string  $name  What the key is for. Not secret.
     * @param  string  $mode  `live` is metered and can manage the account;
     *                        `test` is free and cannot.
     * @param  string|null  $expiresAt  When it stops working. Short expiries suit CI.
     */
    public function create(string $name, string $mode = 'live', ?string $expiresAt = null): NewApiKey
    {
        $body = ['name' => $name, 'mode' => $mode];

        if ($expiresAt !== null) {
            $body['expires_at'] = $expiresAt;
        }

        return NewApiKey::fromArray(self::data($this->client->post('/v1/keys', $body)));
    }

    /** Revoke a key. It stops working immediately, and there is no undo. */
    public function revoke(string $id): void
    {
        $this->client->delete('/v1/keys/'.rawurlencode($id));
    }
}

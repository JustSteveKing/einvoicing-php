<?php

declare(strict_types=1);

use Einvoicing\Exceptions\AllowanceExhaustedException;
use Einvoicing\Responses\NewApiKey;

it('lists rulesets and finds the current one', function (): void {
    $http = http()->queue([
        'data' => [
            ['id' => 'peppol-bis-billing-3.0.21', 'name' => 'Peppol BIS Billing', 'version' => '3.0.21', 'status' => 'current', 'released' => '2026-05-15'],
            ['id' => 'peppol-bis-billing-3.0.20', 'name' => 'Peppol BIS Billing', 'version' => '3.0.20', 'status' => 'superseded', 'released' => '2025-11-15'],
        ],
    ]);

    $rulesets = client($http)->rulesets();
    $all = $rulesets->all();

    expect($all)->toHaveCount(2)
        ->and($all[0]->isCurrent())->toBeTrue()
        ->and($all[1]->mandatoryFrom)->toBeNull();
});

it('reads usage as meters', function (): void {
    $http = http()->queue([
        'data' => [
            'plan' => 'developer',
            'period_start' => '2026-09-01T00:00:00.000Z',
            'period_end' => '2026-10-01T00:00:00.000Z',
            'documents' => ['included' => 1000, 'used' => 250, 'remaining' => 750],
            'lookups' => ['included' => 500, 'used' => 500, 'remaining' => 0],
        ],
    ]);

    $usage = client($http)->usage()->get();

    expect($usage->plan)->toBe('developer')
        ->and($usage->documents->remaining)->toBe(750)
        ->and($usage->documents->fraction())->toBe(0.25)
        ->and($usage->lookups->fraction())->toBe(1.0);
});

it('works out what is left when the API does not say', function (): void {
    $http = http()->queue([
        'data' => ['documents' => ['included' => 100, 'used' => 30], 'lookups' => []],
    ]);

    expect(client($http)->usage()->get()->documents->remaining)->toBe(70);
});

it('creates a key and hands back the secret once', function (): void {
    $http = http()->queue([
        'data' => [
            'id' => '01K5GQ2R7P0000000000000000',
            'name' => 'CI',
            'mode' => 'test',
            'prefix' => 'sk_test_9f2a',
            'created_at' => '2026-09-18T09:00:00.000Z',
            'last_used_at' => null,
            'expires_at' => '2026-12-18T00:00:00.000Z',
            'revoked_at' => null,
            'secret' => 'sk_test_9f2a0000000000000000000000000000',
        ],
    ]);

    $key = client($http)->keys()->create('CI', 'test', '2026-12-18T00:00:00.000Z');

    expect(json_decode((string) $http->lastRequest()->getBody(), true))
        ->toBe(['name' => 'CI', 'mode' => 'test', 'expires_at' => '2026-12-18T00:00:00.000Z']);

    expect($key)->toBeInstanceOf(NewApiKey::class)
        ->and($key->secret)->toStartWith('sk_test_')
        ->and($key->isLive())->toBeFalse()
        ->and($key->isRevoked())->toBeFalse();
});

it('revokes a key without expecting a body back', function (): void {
    $http = http()->queue('', 204);

    client($http)->keys()->revoke('01K5GQ2R7P0000000000000000');

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and((string) $request->getUri())
        ->toBe('https://api.einvoicing.dev/v1/keys/01K5GQ2R7P0000000000000000');
});

it('reads the account', function (): void {
    $http = http()->queue([
        'data' => [
            'id' => '01K5GQ2R7P0000000000000000',
            'email' => 'steve@example.com',
            'plan' => 'developer',
            'created_at' => '2026-09-01T09:00:00.000Z',
        ],
    ]);

    expect(client($http)->account()->get()->email)->toBe('steve@example.com');
});

it('opens a billing session', function (): void {
    $http = http()->queue([
        'data' => ['url' => 'https://billing.stripe.com/session/abc', 'expires_at' => null],
    ]);

    $session = client($http)->billing()->checkout('pro');

    expect($session->url)->toStartWith('https://billing.stripe.com/')
        ->and($session->expiresAt)->toBeNull()
        ->and(json_decode((string) $http->lastRequest()->getBody(), true))->toBe(['plan' => 'pro']);
});

it('says when the allowance is gone', function (): void {
    $http = http()->queue([
        'type' => 'https://www.einvoicing.dev/problems/allowance-exhausted',
        'title' => 'This period\'s allowance is used up.',
        'status' => 402,
    ], 402);

    expect(fn () => client($http)->usage()->get())->toThrow(AllowanceExhaustedException::class);
});

<?php

declare(strict_types=1);

use Einvoicing\Exceptions\NotFoundException;

it('looks a participant up by its scheme and value', function (): void {
    $http = http()->queue([
        'data' => [
            'id' => '9932:gb123456789',
            'scheme' => '9932',
            'identifier' => 'gb123456789',
            'registered' => true,
            'capabilities' => [
                [
                    'name' => 'Peppol BIS Billing 3.0 Invoice',
                    'document_type_id' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::Invoice##urn:cen.eu:en16931:2017…',
                    'process_id' => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
                ],
            ],
            'directory' => null,
            'checked_at' => '2026-09-18T09:00:00.000Z',
        ],
    ]);

    $participant = client($http)->participants()->find('9932:GB123456789');

    expect((string) $http->lastRequest()->getUri())
        ->toBe('https://api.einvoicing.dev/v1/participants/9932%3AGB123456789');

    expect($participant->registered)->toBeTrue()
        ->and($participant->identifier)->toBe('gb123456789')
        ->and($participant->directory)->toBeNull()
        ->and($participant->capabilities[0]->name)->toBe('Peppol BIS Billing 3.0 Invoice')
        ->and($participant->accepts('Invoice-2::Invoice'))->toBeTrue()
        ->and($participant->accepts('CreditNote-2::CreditNote'))->toBeFalse();
});

it('reads an unregistered participant as an answer', function (): void {
    $http = http()->queue([
        'data' => [
            'id' => '9932:gb999999999',
            'scheme' => '9932',
            'identifier' => 'gb999999999',
            'registered' => false,
            'capabilities' => [],
            'checked_at' => '2026-09-18T09:00:00.000Z',
        ],
    ]);

    $participant = client($http)->participants()->find('9932:GB999999999');

    expect($participant->registered)->toBeFalse()
        ->and($participant->capabilities)->toBe([]);
});

it('still throws when the identifier itself is not a thing', function (): void {
    $http = http()->queue([
        'type' => 'https://www.einvoicing.dev/problems/not-found',
        'title' => 'No such resource.',
        'status' => 404,
    ], 404);

    expect(fn () => client($http)->participants()->find('nonsense'))
        ->toThrow(NotFoundException::class);
});

<?php

declare(strict_types=1);

use Einvoicing\Exceptions\InvalidInvoiceException;

it('converts an invoice and validates the result', function (): void {
    $http = http()->queue([
        'data' => [
            'target' => 'peppol-bis-billing-3',
            'document' => '<?xml version="1.0"?><Invoice/>',
            'totals' => ['payable' => '1200.00'],
            'vat_breakdown' => [
                ['category' => 'S', 'rate' => '20.00', 'taxable' => '1000.00', 'amount' => '200.00'],
            ],
            'validation' => ['valid' => true, 'findings' => []],
        ],
    ]);

    $conversion = client($http)->conversions()->convert(['number' => 'INV-1']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('https://api.einvoicing.dev/v1/conversions')
        ->and(json_decode((string) $request->getBody(), true))
        ->toBe(['target' => 'peppol-bis-billing-3', 'invoice' => ['number' => 'INV-1']]);

    expect($conversion->document)->toContain('<Invoice/>')
        ->and($conversion->validation->valid)->toBeTrue()
        ->and($conversion->vatBreakdown)->toHaveCount(1);
});

it('sends a pinned ruleset in the body', function (): void {
    $http = http()->queue(['data' => []]);

    client($http)->conversions()->convert(['number' => 'INV-1'], ruleset: 'peppol-bis-billing-3.0.21');

    expect(json_decode((string) $http->lastRequest()->getBody(), true))
        ->toHaveKey('ruleset', 'peppol-bis-billing-3.0.21');
});

it('carries the findings on an invoice it could not convert', function (): void {
    $http = http()->queue([
        'type' => 'https://www.einvoicing.dev/problems/invalid-invoice',
        'title' => 'The invoice could not be converted.',
        'status' => 422,
        'findings' => [
            [
                'rule_id' => 'BR-CO-10',
                'layer' => 'en16931',
                'severity' => 'error',
                'message' => 'Sum of line amounts must equal the invoice total.',
                'explanation' => 'The lines do not add up to the total you sent.',
                'business_terms' => ['BT-106'],
                'location' => [],
                'docs_url' => 'https://www.einvoicing.dev/rules/BR-CO-10',
            ],
        ],
    ], 422);

    try {
        client($http)->conversions()->convert(['number' => 'INV-1']);
        expect(false)->toBeTrue('Expected an InvalidInvoiceException.');
    } catch (InvalidInvoiceException $e) {
        expect($e->findings())->toHaveCount(1)
            ->and($e->findings()[0]->ruleId)->toBe('BR-CO-10')
            ->and($e->findings()[0]->isError())->toBeTrue();
    }
});

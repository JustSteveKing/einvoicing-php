<?php

declare(strict_types=1);

use Einvoicing\Exceptions\RateLimitedException;
use Einvoicing\Exceptions\UnauthenticatedException;
use Einvoicing\Responses\Finding;

it('sends the document as XML and reads the report back', function (): void {
    $http = http()->queue([
        'data' => [
            'valid' => false,
            'ruleset' => ['id' => 'peppol-bis-billing-3.0.21'],
            'document' => ['type' => 'Invoice'],
            'layers' => [
                ['name' => 'schema', 'status' => 'passed'],
                ['name' => 'peppol', 'status' => 'failed'],
            ],
            'summary' => ['errors' => 1, 'warnings' => 1],
            'findings' => [
                [
                    'rule_id' => 'PEPPOL-EN16931-R003',
                    'layer' => 'peppol',
                    'severity' => 'error',
                    'message' => 'A buyer reference or purchase order reference MUST be provided.',
                    'explanation' => 'Peppol needs something the buyer can match the invoice against.',
                    'fix' => 'Set BT-10 or BT-13.',
                    'business_terms' => ['BT-10', 'BT-13'],
                    'location' => ['line' => 14],
                    'docs_url' => 'https://www.einvoicing.dev/rules/PEPPOL-EN16931-R003',
                ],
                [
                    'rule_id' => 'PEPPOL-EN16931-R110',
                    'layer' => 'peppol',
                    'severity' => 'warning',
                    'message' => 'Start date should be before end date.',
                    'explanation' => 'The invoicing period reads backwards.',
                    'business_terms' => ['BT-73'],
                    'location' => [],
                    'docs_url' => 'https://www.einvoicing.dev/rules/PEPPOL-EN16931-R110',
                ],
            ],
        ],
    ]);

    $report = client($http)->validations()->validate('<Invoice/>');

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('https://api.einvoicing.dev/v1/validations')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/xml')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk_test_example')
        ->and((string) $request->getBody())->toBe('<Invoice/>');

    expect($report->valid)->toBeFalse()
        ->and($report->findings)->toHaveCount(2)
        ->and($report->findings[0])->toBeInstanceOf(Finding::class)
        ->and($report->findings[0]->ruleId)->toBe('PEPPOL-EN16931-R003')
        ->and($report->findings[0]->businessTerms)->toBe(['BT-10', 'BT-13'])
        ->and($report->errors())->toHaveCount(1)
        ->and($report->warnings())->toHaveCount(1)
        ->and($report->warnings()[0]->fix)->toBeNull();
});

it('pins the ruleset when one is given', function (): void {
    $http = http()->queue(['data' => ['valid' => true]]);

    client($http)->validations()->validate('<Invoice/>', 'peppol-bis-billing-3.0.21');

    expect((string) $http->lastRequest()->getUri())
        ->toBe('https://api.einvoicing.dev/v1/validations?ruleset=peppol-bis-billing-3.0.21');
});

it('can wrap the document in JSON instead', function (): void {
    $http = http()->queue(['data' => ['valid' => true]]);

    client($http)->validations()->validateAsJson('<Invoice/>');

    $request = $http->lastRequest();
    expect($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and((string) $request->getBody())->toBe('{"document":"<Invoice/>"}');
});

it('treats an invalid document as an answer, not an exception', function (): void {
    $http = http()->queue(['data' => ['valid' => false, 'findings' => []]]);

    expect(client($http)->validations()->validate('<Invoice/>')->valid)->toBeFalse();
});

it('throws a typed exception for a problem', function (): void {
    $http = http()->queue([
        'type' => 'https://www.einvoicing.dev/problems/unauthenticated',
        'title' => 'The API key is missing or not valid.',
        'status' => 401,
        'detail' => 'Send it as a bearer token.',
    ], 401);

    expect(fn () => client($http)->validations()->validate('<Invoice/>'))
        ->toThrow(UnauthenticatedException::class);
});

it('reports how long to wait when rate limited', function (): void {
    $http = http()->queue([
        'type' => 'https://www.einvoicing.dev/problems/rate-limited',
        'title' => 'Too many requests.',
        'status' => 429,
        'retry_after' => 30,
    ], 429);

    try {
        client($http)->validations()->validate('<Invoice/>');
        expect(false)->toBeTrue('Expected a RateLimitedException.');
    } catch (RateLimitedException $e) {
        expect($e->retryAfter())->toBe(30)
            ->and($e->slug())->toBe('rate-limited')
            ->and($e->status)->toBe(429);
    }
});

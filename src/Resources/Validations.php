<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Responses\ValidationReport;

/**
 * Checking a document against the rules.
 *
 * An invalid document is a successful request. You get a report back saying
 * so, with every finding, rather than an exception carrying only the first —
 * because the second finding is often the interesting one.
 */
final class Validations extends Resource
{
    /**
     * Validate a UBL 2.1 Invoice or CreditNote.
     *
     * @param  string  $document  The XML, as a string.
     * @param  string|null  $ruleset  A ruleset id to pin to, such as
     *                                `peppol-bis-billing-3.0.21`. Omit it and
     *                                the current ruleset is used, which means
     *                                a release can change the answer.
     */
    public function validate(string $document, ?string $ruleset = null): ValidationReport
    {
        $response = $this->client->post(
            path: '/v1/validations',
            body: $document,
            contentType: 'application/xml',
            query: ['ruleset' => $ruleset],
        );

        return ValidationReport::fromArray(self::data($response));
    }

    /**
     * The same check, with the document wrapped in JSON. For HTTP stacks that
     * would rather not send an XML body.
     */
    public function validateAsJson(string $document, ?string $ruleset = null): ValidationReport
    {
        $response = $this->client->post(
            path: '/v1/validations',
            body: ['document' => $document],
            query: ['ruleset' => $ruleset],
        );

        return ValidationReport::fromArray(self::data($response));
    }
}

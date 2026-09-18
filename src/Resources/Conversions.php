<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Responses\Conversion;

/**
 * Turning your own invoice data into a Peppol document.
 *
 * The totals and the VAT breakdown are worked out from the lines, so you send
 * what you have rather than what the standard wants, and the result is
 * validated before it comes back.
 */
final class Conversions extends Resource
{
    /**
     * @param  array<string, mixed>  $invoice  The invoice, in this API's JSON shape.
     * @param  string  $target  The format to produce.
     * @param  string|null  $ruleset  A ruleset id to validate the result against.
     */
    public function convert(
        array $invoice,
        string $target = 'peppol-bis-billing-3',
        ?string $ruleset = null,
    ): Conversion {
        $body = ['target' => $target, 'invoice' => $invoice];

        if ($ruleset !== null) {
            $body['ruleset'] = $ruleset;
        }

        return Conversion::fromArray(self::data($this->client->post('/v1/conversions', $body)));
    }
}

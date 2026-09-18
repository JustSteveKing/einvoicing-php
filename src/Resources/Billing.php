<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Responses\BillingSession;

/**
 * Subscribing and managing the subscription. Both calls hand back a
 * short-lived Stripe URL to redirect a browser to; no card details pass
 * through this client.
 */
final class Billing extends Resource
{
    /** Start a paid plan: `developer` or `pro`. */
    public function checkout(string $plan): BillingSession
    {
        $response = $this->client->post('/v1/billing/checkout-sessions', ['plan' => $plan]);

        return BillingSession::fromArray(self::data($response));
    }

    /** Open the billing portal, for changing the card, plan or invoices. */
    public function portal(): BillingSession
    {
        return BillingSession::fromArray(self::data($this->client->post('/v1/billing/portal-sessions')));
    }
}

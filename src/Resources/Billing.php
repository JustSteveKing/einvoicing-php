<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Requests\CreateCheckoutSession;
use Einvoicing\Requests\CreatePortalSession;
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
        return $this->client->send(new CreateCheckoutSession($plan));
    }

    /** Open the billing portal, for changing the card, plan or invoices. */
    public function portal(): BillingSession
    {
        return $this->client->send(new CreatePortalSession);
    }
}

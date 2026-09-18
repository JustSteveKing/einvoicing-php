<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Responses\Participant;

/**
 * Asking the network whether a business can receive, and what.
 *
 * A business that is not registered is a successful answer with `registered`
 * false, not a 404: absence from a public register is a fact about the world,
 * and handling it as an exception puts it in front of a user as "something
 * went wrong" instead of "this customer cannot receive e-invoices yet".
 */
final class Participants extends Resource
{
    /**
     * Look a participant up by its `scheme:value` identifier, such as
     * `9932:GB123456789`.
     */
    public function find(string $id): Participant
    {
        $response = $this->client->get('/v1/participants/'.rawurlencode($id));

        return Participant::fromArray(self::data($response));
    }
}

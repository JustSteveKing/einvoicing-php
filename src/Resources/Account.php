<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Responses\Account as AccountDetails;

/** The account behind the key. */
final class Account extends Resource
{
    public function get(): AccountDetails
    {
        return AccountDetails::fromArray(self::data($this->client->get('/v1/account')));
    }
}

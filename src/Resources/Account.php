<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Requests\GetAccount;
use Einvoicing\Responses\Account as AccountDetails;

/** The account behind the key. */
final class Account extends Resource
{
    public function get(): AccountDetails
    {
        return $this->client->send(new GetAccount);
    }
}

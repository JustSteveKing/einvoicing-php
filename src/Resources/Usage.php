<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Requests\GetUsage;
use Einvoicing\Responses\Usage as UsageReport;

/** What this account has spent in the current billing period. */
final class Usage extends Resource
{
    public function get(): UsageReport
    {
        return $this->client->send(new GetUsage);
    }
}

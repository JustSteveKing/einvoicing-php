<?php

declare(strict_types=1);

namespace Einvoicing\Requests;

use Einvoicing\Support\Data;
use JustSteveKing\SdkToolbox\Http\Response;

/**
 * Every response puts the thing you asked for under `data`.
 *
 * @internal
 */
final class Envelope
{
    /**
     * @return array<string, mixed>
     */
    public static function data(Response $response): array
    {
        /** @var array<string, mixed> $body */
        $body = $response->json();

        return Data::map($body, 'data');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function collection(Response $response): array
    {
        /** @var array<string, mixed> $body */
        $body = $response->json();

        return Data::maps($body, 'data');
    }
}

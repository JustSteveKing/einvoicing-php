<?php

declare(strict_types=1);

namespace Einvoicing\Resources;

use Einvoicing\Responses\Ruleset;

/**
 * The rule sets available to validate against.
 *
 * Read this once and pin the id in your own configuration. Validating against
 * whatever is current means a release elsewhere can turn your passing build
 * red without anything of yours changing.
 */
final class Rulesets extends Resource
{
    /** @return list<Ruleset> */
    public function all(): array
    {
        return array_map(
            static fn (array $ruleset): Ruleset => Ruleset::fromArray($ruleset),
            self::collection($this->client->get('/v1/rulesets')),
        );
    }

    /** The one in force today, if the API names one. */
    public function current(): ?Ruleset
    {
        foreach ($this->all() as $ruleset) {
            if ($ruleset->isCurrent()) {
                return $ruleset;
            }
        }

        return null;
    }
}

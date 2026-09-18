<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

use Einvoicing\Responses\Finding;
use Einvoicing\Support\Data;

/**
 * A conversion could not produce a valid document. The findings say why, in
 * the same shape validation returns them, so the two paths can share code.
 *
 * Note that this is conversion only. Validating a document that breaks the
 * rules is a success: it answers with a report whose `valid` is false.
 */
final class InvalidInvoiceException extends ProblemException
{
    /** @return list<Finding> */
    public function findings(): array
    {
        return array_map(
            static fn (array $finding): Finding => Finding::fromArray($finding),
            Data::maps($this->members, 'findings'),
        );
    }
}

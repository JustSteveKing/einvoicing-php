<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

/** The plan's allowance for this period is used up. Paid plans are billed for usage past the allowance and never see this. */
final class AllowanceExhaustedException extends ProblemException {}

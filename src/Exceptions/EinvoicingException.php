<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

use Throwable;

/**
 * Every exception this package throws implements this, so an application can
 * catch the whole library without catching the world.
 */
interface EinvoicingException extends Throwable {}

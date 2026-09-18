<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

use RuntimeException;

/**
 * No PSR-18 client or PSR-17 factory could be discovered, and none was passed.
 *
 * This is a composer problem rather than a runtime one: the package declares
 * `psr/http-client-implementation` and `psr/http-factory-implementation`, so
 * it should normally surface at install time. It can still reach here when
 * discovery is disabled or a strategy is filtered out.
 */
final class NoHttpClientException extends RuntimeException implements EinvoicingException {}

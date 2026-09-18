<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

use RuntimeException;

/**
 * No PSR-18 client or PSR-17 factory could be discovered, and none was passed.
 *
 * This is a composer problem wearing a runtime disguise. The package requires
 * psr/http-client-implementation and psr/http-factory-implementation, which
 * php-http/discovery's composer plugin reads and satisfies by installing a
 * real pair — but only in a project that allows the plugin. Decline it and
 * the install succeeds with nothing behind it, so the message has to name
 * what is missing.
 */
final class NoHttpClientException extends RuntimeException implements EinvoicingException {}

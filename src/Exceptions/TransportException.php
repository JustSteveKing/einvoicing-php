<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

use RuntimeException;

/**
 * The request never got an answer: DNS, TLS, a timeout, a refused connection.
 * The PSR-18 client's own exception is the previous.
 */
final class TransportException extends RuntimeException implements EinvoicingException {}

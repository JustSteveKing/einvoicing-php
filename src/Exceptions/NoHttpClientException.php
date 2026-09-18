<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

use RuntimeException;

/**
 * No PSR-18 client or PSR-17 factory could be discovered, and none was passed.
 *
 * This is a composer problem wearing a runtime disguise. Nothing catches it at
 * install time: php-http/discovery satisfies the implementation requirement on
 * paper, and its composer plugin only installs a real one when the project
 * allows the plugin. So the message names what to install.
 */
final class NoHttpClientException extends RuntimeException implements EinvoicingException {}

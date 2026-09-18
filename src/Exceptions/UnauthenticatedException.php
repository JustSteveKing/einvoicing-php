<?php

declare(strict_types=1);

namespace Einvoicing\Exceptions;

/** No key, or a key that is unknown or revoked. */
final class UnauthenticatedException extends ProblemException {}

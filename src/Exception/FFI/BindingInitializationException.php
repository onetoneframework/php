<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Exception\FFI;

use Throwable;

/**
 * Raised when `FFI::cdef()` cannot build a binding — a missing shared library,
 * an unreadable header, or a C declaration the parser rejects.
 */
final class BindingInitializationException extends FFIException
{
	/**
	 * @param string    $binding     Name of the binding that failed to initialise.
	 * @param string    $libraryPath Shared library the binding was pointed at.
	 * @param Throwable $previous    The underlying `FFI\Exception`.
	 */
	public static function forBinding(string $binding, string $libraryPath, Throwable $previous): self
	{
		return new self(
			sprintf('%s could not be initialised from "%s": %s', $binding, $libraryPath, $previous->getMessage()),
			0,
			$previous
		);
	}
}

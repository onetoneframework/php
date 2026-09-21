<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Exception\FFI;

/**
 * Raised when a native binding is constructed while `ext-ffi` is not loaded.
 */
final class ExtensionNotLoadedException extends FFIException
{
	/**
	 * @param string $binding Name of the binding that needed the extension.
	 */
	public static function forBinding(string $binding): self
	{
		return new self(sprintf(
			'The ffi extension is not loaded, so %s cannot be initialised. Enable ext-ffi and set ffi.enable in php.ini.',
			$binding
		));
	}
}

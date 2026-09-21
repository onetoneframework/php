<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Message\Functions;

/**
 * Function Message Class
 *
 * Provides static methods for retrieving function-related error messages.
 * Centralizes error message generation for function operations.
 */
class FunctionMessage
{
	/**
	 * Get function is not file message
	 * 
	 * @return string
	 */
	public static function getFunctionIsNotFileMessage(): string
	{
		return 'Function is not exists';
	}
}

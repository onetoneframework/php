<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Exception\Argument;

use Clover\Exception\FileHandler\IOException;

/**
 * Argument Empty Exception
 */
class ArgumentEmptyException extends IOException
{

	public function __construct(?string $message = null, int $code = 0, ?\Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}

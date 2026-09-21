<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Exception\Interpreter;

/**
 * Invalid Express Exception
 */
class InvalidExpressException extends \RuntimeException
{
	/**
	 * Constructor
	 *
	 * @param string          $message
	 * @param int             $code
	 * @param \Exception|null $previous
	 */
	public function __construct(string $message, int $code = 0, ?\Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}

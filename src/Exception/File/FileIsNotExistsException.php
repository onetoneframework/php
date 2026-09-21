<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Exception\FileHandler;

use Clover\Exception\FileHandler\IOException;

/**
 * File Not Found Exception
 */
class FileNotFoundException extends IOException
{

	/**
	 * Constructor
	 *
	 * @param string|null     $message
	 * @param int             $code
	 * @param \Exception|null $previous
	 */
	public function __construct(?string $message = null, int $code = 0, ?\Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}

<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class PHPProtocol
{
	public const FILTER = "php://filter";
	public const INPUT = "php://input";
	public const MEMORY = "php://memory";
	public const OUTPUT = "php://output";
	public const STANDARD_ERROR = "php://stderr";
	public const STANDARD_INPUT = "php://stdin";
	public const STANDARD_OUTPUT = "php://stdout";
	public const TEMPORARY = "php://temp";
}

<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Exception\AI;

use RuntimeException;

/**
 * Raised when a required orchestration attribute does not exist.
 */
final class AttributeNotFoundException extends RuntimeException
{
	/**
	 * Create an exception for the requested attribute key.
	 */
	public function __construct(string $key)
	{
		parent::__construct(sprintf('AI orchestration attribute "%s" was not found.', $key));
	}
}

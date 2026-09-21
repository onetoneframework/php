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
 * Raised when a requested workflow step result is unavailable.
 */
final class StepResultNotFoundException extends RuntimeException
{
	/**
	 * Create an exception for the requested step identifier.
	 */
	public function __construct(string $stepIdentifier)
	{
		parent::__construct(sprintf('AI workflow step result "%s" was not found.', $stepIdentifier));
	}
}

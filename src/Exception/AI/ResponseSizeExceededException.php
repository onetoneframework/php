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
 * Raised when an agent returns more content than the execution limit permits.
 */
final class ResponseSizeExceededException extends RuntimeException
{
	public function __construct(string $stepIdentifier)
	{
		parent::__construct(sprintf('AI workflow step "%s" exceeded the response size limit.', $stepIdentifier));
	}
}

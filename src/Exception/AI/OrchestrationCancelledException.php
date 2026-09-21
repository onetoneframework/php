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
 * Raised when cancellation stops an AI workflow.
 */
final class OrchestrationCancelledException extends RuntimeException
{
	public function __construct()
	{
		parent::__construct('AI workflow execution was cancelled.');
	}
}

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
 * Raised when an AI workflow exhausts its cumulative response budget.
 */
final class WorkflowResponseBudgetExceededException extends RuntimeException
{
	public function __construct()
	{
		parent::__construct('AI workflow execution exhausted its response budget.');
	}
}

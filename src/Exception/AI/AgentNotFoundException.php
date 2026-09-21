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
 * Raised when a workflow references an unregistered agent.
 */
final class AgentNotFoundException extends RuntimeException
{
	/**
	 * Create an exception for the requested agent name.
	 */
	public function __construct(string $agentName)
	{
		parent::__construct(sprintf('AI agent "%s" is not registered.', $agentName));
	}
}

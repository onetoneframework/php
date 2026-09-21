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
use Throwable;

/**
 * Wraps a failure while creating a run-scoped AI agent.
 */
final class AgentCreationException extends RuntimeException
{
	private string $agentName;

	public function __construct(string $agentName, Throwable $previous)
	{
		$this->agentName = $agentName;

		parent::__construct('AI agent creation failed.', previous: $previous);
	}

	public function agentName(): string
	{
		return $this->agentName;
	}
}

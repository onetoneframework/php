<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

/**
 * Completed output of one AI workflow step.
 */
final class StepResult
{
	private string $stepIdentifier;

	private AgentResponse $response;

	/**
	 * Associate a normalized response with its workflow step.
	 */
	public function __construct(string $stepIdentifier, AgentResponse $response)
	{
		$this->stepIdentifier = (new Identifier($stepIdentifier))->value();
		$this->response = $response;
	}

	/**
	 * Return the completed step identifier.
	 */
	public function stepIdentifier(): string
	{
		return $this->stepIdentifier;
	}

	/**
	 * Return the normalized agent response.
	 */
	public function response(): AgentResponse
	{
		return $this->response;
	}
}
